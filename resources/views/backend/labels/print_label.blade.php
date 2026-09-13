@php
    $is_50x30 = (str_contains($barcode_details->name, '50mm x 30mm') || ($barcode_details->is_continuous && abs($barcode_details->width - 1.9685) < 0.05 && abs($barcode_details->height - 1.1811) < 0.05));
    $is_38x25 = (str_contains($barcode_details->name, '38mm x 25mm') || ($barcode_details->is_continuous && abs($barcode_details->width - 1.4961) < 0.05 && abs($barcode_details->height - 0.9842) < 0.05));
    $is_thermal_gadget = $is_50x30 || $is_38x25;
    $barcode_format = $print['barcode_format'] ?? 'C128';
@endphp
<style type="text/css">
    * {
        box-sizing: border-box;
    }
    body, html {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff;
    }
	td {
        padding: 0px !important;
        margin: 0px !important;
	}
	@media print {
		table {
			page-break-after: always;
            page-break-inside: avoid;
		}
        body, html {
            margin: 0 !important;
            padding: 0 !important;
        }

        @if($is_50x30)
		@page {
		    size: 50mm 30mm;
            margin: 0mm !important;
        }
        @elseif($is_38x25)
		@page {
		    size: 38mm 25mm;
            margin: 0mm !important;
        }
        @elseif($barcode_details->is_continuous)
		@page {
		    size: {{$paper_width}}in {{$paper_height}}in;
            margin: 0 !important;
        }
        @else
		@page {
		    size: {{$paper_width}}in {{$paper_height}}in;
            margin-top: {{$margin_top}}in !important;
            margin-bottom: {{$margin_top}}in !important;
            margin-left: {{$margin_left}}in !important;
            margin-right: {{$margin_left}}in !important;
        }
        @endif
	}

    .sticker-wrapper {
        overflow: hidden !important;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        color: #000;
        @if($is_50x30)
            width: 50mm;
            height: 30mm;
            max-width: 50mm;
            max-height: 30mm;
            padding: 1.2mm 1.5mm;
        @elseif($is_38x25)
            width: 38mm;
            height: 25mm;
            max-width: 38mm;
            max-height: 25mm;
            padding: 0.8mm 1mm;
        @else
            width: {{$barcode_details->width}}in;
            height: {{$barcode_details->height}}in;
            padding: 2px;
        @endif
    }
</style>

<table align="center" style="border-spacing: {{$barcode_details->col_distance * 1}}in {{$barcode_details->row_distance * 1}}in; overflow: hidden !important; border-collapse: separate;">
    @foreach($page_products as $page_product)

	@if($loop->index % $barcode_details->stickers_in_one_row == 0)
    <tr>
    @endif

    <td align="center" valign="middle">
        <div class="sticker-wrapper">
            <div style="width: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center;">

                {{-- Header row: Business Name & Condition --}}
                <div style="width: 100%; display: flex; justify-content: center; align-items: center; gap: 4px; overflow: hidden; line-height: 1.1;">
                    @if(!empty($print['business_name']))
                        <b style="font-size: {{$print['business_name_size'] ?? ($is_38x25 ? 9 : 10)}}px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 75%;">
                            {{$business_name}}
                        </b>
                    @endif
                    @if(!empty($print['condition']) && !empty($page_product['condition']))
                        <span style="font-size: {{$print['condition_size'] ?? ($is_38x25 ? 8 : 8.5)}}px; font-weight: 700; text-transform: uppercase; border: 1px solid #000; border-radius: 2px; padding: 0 2px; line-height: 1.1; white-space: nowrap;">
                            {{$page_product['condition']}}
                        </span>
                    @endif
                </div>

                {{-- Product Name --}}
                @if(!empty($print['name']))
                    <span style="display: block !important; font-size: {{$print['name_size'] ?? ($is_38x25 ? 9.5 : 10.5)}}px; font-weight: 600; line-height: 1.15; max-height: 2.3em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; margin-top: 1px;">
                        {{$page_product['product_actual_name']}}
                    </span>
                @endif

                {{-- Brand Name --}}
                @if(!empty($print['brand_name']) && !empty($page_product['brand_name']) && $page_product['brand_name'] != 'N/A')
                    <span style="display: block !important; font-size: {{$print['brand_name_size'] ?? ($is_38x25 ? 8 : 9)}}px; line-height: 1.1; color: #444; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;">
                        {{$page_product['brand_name']}}
                    </span>
                @endif

                {{-- Gadget Technical Specs (Processor | RAM | Storage) --}}
                @if(!empty($print['specs']) && !empty($page_product['specs_line']))
                    <span style="display: block !important; font-size: {{$print['specs_size'] ?? ($is_38x25 ? 8 : 9)}}px; font-weight: 500; line-height: 1.15; color: #111; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; margin-top: 1px;">
                        {{$page_product['specs_line']}}
                    </span>
                @endif

                {{-- Price if not in QR mode (in QR mode price is rendered next to QR) --}}
                @if(!empty($print['price']) && $barcode_format !== 'QR')
                <div style="font-size: {{$print['price_size'] ?? ($is_38x25 ? 9.5 : 11)}}px; font-weight: bold; line-height: 1.15; margin-top: 1px;">
                    @if(isset($print['promo_price']) && ($page_product['product_promo_price'] != 'null' && !empty($page_product['product_promo_price'])))
                        @if($page_product['currency_position'] == 'prefix')
                            <span style="text-decoration: line-through; font-size: 80%; font-weight: normal;">{{$page_product['currency']}}{{$page_product['product_price']}}</span> {{$page_product['currency']}}{{$page_product['product_promo_price']}}
                        @else
                            <span style="text-decoration: line-through; font-size: 80%; font-weight: normal;">{{$page_product['product_price']}}{{$page_product['currency']}}</span> {{$page_product['product_promo_price']}}{{$page_product['currency']}}
                        @endif
                    @else
                        @if($page_product['currency_position'] == 'prefix')
                            {{$page_product['currency']}}{{$page_product['product_price']}}
                        @else
                            {{$page_product['product_price']}} {{$page_product['currency']}}
                        @endif
                    @endif
                </div>
                @endif

                {{-- Barcode / QR Code --}}
                @if($barcode_format === 'QR')
                    {{-- 2D QR Code Layout --}}
                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; margin-top: 1px;">
                        <img style="width: @if($is_38x25) 15mm @elseif($is_50x30) 18mm @else 22mm @endif; height: @if($is_38x25) 15mm @elseif($is_50x30) 18mm @else 22mm @endif; display: block;" src="data:image/png;base64,{{DNS2D::getBarcodePNG($page_product['sub_sku'], 'QRCODE', 3, 3)}}">
                        <div style="text-align: left; line-height: 1.15;">
                            @if(!empty($print['price']))
                                <div style="font-size: {{$print['price_size'] ?? ($is_38x25 ? 9 : 10.5)}}px; font-weight: bold; margin-bottom: 2px;">
                                    @if(isset($print['promo_price']) && ($page_product['product_promo_price'] != 'null' && !empty($page_product['product_promo_price'])))
                                        {{$page_product['currency_position'] == 'prefix' ? $page_product['currency'] . $page_product['product_promo_price'] : $page_product['product_promo_price'] . ' ' . $page_product['currency']}}
                                    @else
                                        {{$page_product['currency_position'] == 'prefix' ? $page_product['currency'] . $page_product['product_price'] : $page_product['product_price'] . ' ' . $page_product['currency']}}
                                    @endif
                                </div>
                            @endif
                            @if(!empty($print['serial_number']) && !empty($page_product['serial_number']))
                                <span style="font-size: {{$print['serial_number_size'] ?? ($is_38x25 ? 7.5 : 8.5)}}px; font-family: monospace; font-weight: bold; display: block; word-break: break-all;">
                                    S/N: {{$page_product['serial_number']}}
                                </span>
                            @else
                                <span style="font-size: 8px; font-family: monospace; display: block; word-break: break-all;">
                                    {{$page_product['sub_sku']}}
                                </span>
                            @endif
                        </div>
                    </div>
                @else
                    {{-- 1D Barcode (C128) --}}
                    <div style="width: 100%; display: flex; flex-direction: column; align-items: center; margin-top: 1px;">
                        <img style="max-width: 96% !important; height: @if($is_38x25) 17px @elseif($is_50x30) 22px @else {{$barcode_details->height*0.24}}in @endif !important; display: block;" src="data:image/png;base64,{{DNS1D::getBarcodePNG($page_product['sub_sku'], $page_product['barcode_type'], 1, 30, array(0, 0, 0), false)}}">
                        @if(!empty($print['serial_number']) && !empty($page_product['serial_number']))
                            <span style="font-size: {{$print['serial_number_size'] ?? ($is_38x25 ? 7.5 : 8.5)}}px !important; font-family: monospace; font-weight: bold; line-height: 1.1; margin-top: 1px;">
                                S/N: {{$page_product['serial_number']}}
                            </span>
                        @else
                            <span style="font-size: 8px !important; font-family: monospace; line-height: 1.1; margin-top: 1px;">
                                {{$page_product['sub_sku']}}
                            </span>
                        @endif
                    </div>
                @endif

            </div>
        </div>
    </td>

    @if($loop->iteration % $barcode_details->stickers_in_one_row == 0)
        </tr>
    @endif
    @endforeach
</table>
