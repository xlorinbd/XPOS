<?php

namespace App\Http\Controllers;

use App\Models\Barcode;
use App\Models\Product;
use App\SellingPriceGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Cache;

class LabelsController extends Controller
{
    /**
     * Display labels
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $barcode_settings = Barcode::select(DB::raw('CONCAT(name, ", ", COALESCE(description, "")) as name, id, is_default'))->get();
        $default = $barcode_settings->where('is_default', 1)->first();
        $barcode_settings = $barcode_settings->pluck('name', 'id');

        return view('backend.labels.show',compact('barcode_settings'));
    }

    public function printLabel(Request $request)
    {
        try {
            $products = $request->get('products');
            $print = $request->get('print');

            $barcode_setting = $request->get('barcode_setting');

            $barcode_details = Barcode::find($barcode_setting);
            $barcode_details->stickers_in_one_sheet = $barcode_details->is_continuous ? $barcode_details->stickers_in_one_row : $barcode_details->stickers_in_one_sheet;
            $barcode_details->paper_height = $barcode_details->is_continuous ? $barcode_details->height : $barcode_details->paper_height;
            if ($barcode_details->stickers_in_one_row == 1) {
                $barcode_details->col_distance = 0;
                $barcode_details->row_distance = 0;
            }
            // if($barcode_details->is_continuous){
            //     $barcode_details->row_distance = 0;
            // }
            $general_setting =  Cache::remember('general_setting', 60*60*24*365, function () {
                return DB::table('general_settings')->latest()->first();
            });
            $business_name = $general_setting->company_name;

            $product_details_page_wise = [];
            $total_qty = 0;
            foreach ($products as $value) {
                $details = [];
                $details['product_name'] = $value['product_name'];
                $details['product_actual_name'] = $value['product_name'];
                $details['product_price'] = $value['product_price'] ?? ($value['default_price'] ?? 0);
                $details['product_promo_price'] = $value['product_promo_price'] ?? null;
                $details['currency'] = $value['currency'] ?? '';
                $details['currency_position'] = $value['currency_position'] ?? 'prefix';
                $details['product_id'] = $value['product_id'] ?? null;
                $details['brand_name'] = $value['brand_name'] ?? '';
                $details['product_type'] = 'standard';
                $details['sub_sku'] = $value['sub_sku'];
                $details['barcode_type'] = 'C128';
                $details['unit'] = 1;

                // Gadget Specs & Condition
                $details['model'] = $value['model'] ?? '';
                $details['processor'] = $value['processor'] ?? '';
                $details['ram'] = $value['ram'] ?? '';
                $details['storage'] = $value['storage'] ?? '';
                $details['display'] = $value['display'] ?? '';
                $details['condition'] = $value['product_condition'] ?? '';

                // Fallback from DB if specs are empty
                if (!empty($details['product_id']) && empty($details['processor'])) {
                    $dbProd = \App\Models\Product::find($details['product_id']);
                    if ($dbProd) {
                        $details['model'] = $details['model'] ?: ($dbProd->model ?? '');
                        $details['processor'] = $details['processor'] ?: ($dbProd->processor ?? '');
                        $details['ram'] = $details['ram'] ?: ($dbProd->ram ?? '');
                        $details['storage'] = $details['storage'] ?: ($dbProd->storage ?? '');
                        $details['display'] = $details['display'] ?: ($dbProd->display ?? '');
                        $details['condition'] = $details['condition'] ?: ($dbProd->product_condition ?? '');
                    }
                }

                $specsParts = [];
                if (!empty($details['processor'])) $specsParts[] = $details['processor'];
                if (!empty($details['ram'])) $specsParts[] = $details['ram'];
                if (!empty($details['storage'])) $specsParts[] = $details['storage'];
                $details['specs_line'] = implode(' | ', $specsParts);

                // Serial processing: If specific serials selected, generate 1 sticker per serial
                $selectedSerials = !empty($value['serials']) ? array_filter((array)$value['serials']) : [];
                if (!empty($selectedSerials)) {
                    foreach ($selectedSerials as $serial) {
                        $itemDetails = $details;
                        $itemDetails['serial_number'] = $serial;
                        $itemDetails['sub_sku'] = $serial; // Barcode encodes individual serial

                        $page = intdiv($total_qty, $barcode_details->stickers_in_one_sheet);
                        if ($total_qty % $barcode_details->stickers_in_one_sheet == 0) {
                            $product_details_page_wise[$page] = [];
                        }
                        $product_details_page_wise[$page][] = $itemDetails;
                        $total_qty++;
                    }
                } else {
                    $qty = max(1, (int)($value['quantity'] ?? 1));
                    for ($i = 0; $i < $qty; $i++) {
                        $page = intdiv($total_qty, $barcode_details->stickers_in_one_sheet);
                        if ($total_qty % $barcode_details->stickers_in_one_sheet == 0) {
                            $product_details_page_wise[$page] = [];
                        }
                        $product_details_page_wise[$page][] = $details;
                        $total_qty++;
                    }
                }
            }

            $margin_top = $barcode_details->is_continuous ? 0 : $barcode_details->top_margin * 1;
            $margin_left = $barcode_details->is_continuous ? 0 : $barcode_details->left_margin * 1;
            $paper_width = $barcode_details->paper_width * 1;
            $paper_height = $barcode_details->paper_height * 1;

            $i = 0;
            $len = count($product_details_page_wise);
            $is_first = false;
            $is_last = false;
            //$original_aspect_ratio = 4;//(w/h)
            $factor = (($barcode_details->width / $barcode_details->height)) / ($barcode_details->is_continuous ? 2 : 4);
            $html = '';
            foreach ($product_details_page_wise as $page => $page_products) {
                if ($i == 0) {
                    $is_first = true;
                }

                if ($i == $len - 1) {
                    $is_last = true;
                }
                $output = view('backend.labels.print_label')
                            ->with(compact('print', 'page_products', 'business_name', 'barcode_details', 'margin_top', 'margin_left', 'paper_width', 'paper_height', 'is_first', 'is_last', 'factor'))->render();
                print_r($output);
                //$mpdf->WriteHTML($output);

                // if($i < $len - 1){
                //     // '', '', '', '', '', '', $margin_left, $margin_left, $margin_top, $margin_top, '', '', '', '', '', '', 0, 0, 0, 0, '', [$barcode_details->paper_width*1, $barcode_details->paper_height*1]
                //     $mpdf->AddPage();
                // }

                $i++;
            }

            // $url = '/'
            print_r('<script>window.print()</script>');
            exit;
            //return $output;

            //$mpdf->Output();

            // $page_height = null;
            // if ($barcode_details->is_continuous) {
            //     $rows = ceil($total_qty/$barcode_details->stickers_in_one_row) + 0.4;
            //     $barcode_details->paper_height = $barcode_details->top_margin + ($rows*$barcode_details->height) + ($rows*$barcode_details->row_distance);
            // }

            // $output = view('labels.partials.preview')
            //     ->with(compact('print', 'product_details', 'business_name', 'barcode_details', 'product_details_page_wise'))->render();

            // $output = ['html' => $html,
            //                 'success' => true,
            //                 'msg' => ''
            //             ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = __('lang_v1.barcode_label_error');
        }

        // return view('backend.labels.print_label');
    }
}
