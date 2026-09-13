@extends('backend.layout.main') @section('content')

<x-error-message key="not_permitted" />

<style>
@media print {
    * {
        font-size:12px;
        line-height: 20px;
    }
    td,th {padding: 5px 0;}
    .hidden-print {
        display: none !important;
    }
    @page { size: landscape; margin: 0 !important; }
    .barcodelist {
        max-width: 378px;
    }
    .barcodelist img {
        max-width: 150px;
    }
}
.locked-qty {
    background-color: #e9ecef !important;
    cursor: not-allowed !important;
    font-weight: bold;
}
.serial-lock-note {
    font-size: 11px;
    color: #d97706;
    font-weight: 600;
    margin-top: 4px;
    display: block;
}
.badge-specs {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #cbd5e1;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 4px;
    display: inline-block;
    margin-top: 2px;
}
</style>
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>{{__('db.print_barcode')}}</h4>
                    </div>

	                {!! Form::open(['url' => route('print.label'), 'method' => 'post', 'id' => 'preview_setting_form', 'target' => '_blank']) !!}
                        @csrf
                        <div class="card-body">
                            <p class="italic"><small>{{__('db.The field labels marked with * are required input fields')}}.</small></p>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>{{__('db.add_product')}} *</label>
                                            <div class="search-box input-group">
                                                <button type="button" class="btn btn-secondary btn-lg"><i class="fa fa-barcode"></i></button>
                                                <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="{{__('db.Please type product code and select')}}" class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3 mb-5">
                                        <div class="col-md-12">
                                            <div class="table-responsive mt-3">
                                                <table id="myTable" class="table table-hover order-list">
                                                    <thead>
                                                        <tr>
                                                            <th style="min-width: 250px;">{{__('db.name')}}</th>
                                                            <th>{{__('db.Code')}}</th>
                                                            <th style="min-width: 140px;">{{__('db.Quantity')}}</th>
                                                            <th>{{__('db.Warehouse/Price')}}</th>
                                                            <th><i class="dripicons-trash"></i></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($preLoadedproducts as $key=>$preLoadedproduct)
                                                        @php
                                                            $specsList = array_filter([$preLoadedproduct[17] ?? null, $preLoadedproduct[18] ?? null, $preLoadedproduct[19] ?? null]);
                                                            $isImei = !empty($preLoadedproduct[22]);
                                                            $availSerials = $preLoadedproduct[23] ?? [];
                                                        @endphp
                                                        <tr data-imagedata="{{$preLoadedproduct[3]}}" data-price="{{$preLoadedproduct[2]}}" data-promo-price="{{$preLoadedproduct[4]}}" data-currency="{{$preLoadedproduct[5]}}" data-currency-position="{{$preLoadedproduct[6]}}">
                                                            <td>
                                                                <strong>{{$preLoadedproduct[0]}}</strong>
                                                                @if(!empty($specsList))
                                                                    <br><span class="badge-specs"><i class="fa fa-microchip"></i> {{implode(' | ', $specsList)}}</span>
                                                                @endif
                                                                @if(!empty($preLoadedproduct[21]))
                                                                    <span class="badge badge-warning text-dark">{{$preLoadedproduct[21]}}</span>
                                                                @endif
                                                                @if($isImei)
                                                                    <div class="mt-1">
                                                                        <button type="button" class="btn btn-xs btn-outline-primary btn-select-serials" data-row="{{$key}}">
                                                                            <i class="fa fa-barcode"></i> Select Serials (<span class="selected-serials-count">0</span> / {{count($availSerials)}} avail)
                                                                        </button>
                                                                    </div>
                                                                @endif
                                                            </td>
                                                            <td class="product-code">{{$preLoadedproduct[1]}}</td>
                                                            <td>
                                                                <input type="number" class="form-control qty" name="products[{{$key}}][quantity]" value="1" min="1" />
                                                                <small class="serial-lock-note text-warning font-weight-bold" style="display:none;"><i class="fa fa-lock"></i> Quantity is locked by selected serials</small>
                                                                <div class="selected-serials-container"></div>
                                                            </td>
                                                            <td>
                                                                <select name="products[{{$key}}][warehouse_id]" class="form-control selectpicker" required>
                                                                    <option value="">{{__('db.Choose Warehouse')}}</option>
                                                                    @foreach($warehouses as $id => $warehouse)
                                                                        <option value="{{ $id }}">{{ $warehouse }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                            <td><button type="button" class="ibtnDel btn btn-md btn-danger"><i class="dripicons-trash"></i></button></td>
                                                            <td><input type="hidden" name="products[{{$key}}][product_id]" value="{{$preLoadedproduct[8]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][product_name]" value="{{$preLoadedproduct[0]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][sub_sku]" value="{{$preLoadedproduct[1]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][product_price]" value="{{$preLoadedproduct[2]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][default_price]" value="{{$preLoadedproduct[2]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][product_promo_price]" value="{{$preLoadedproduct[4]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][currency]" value="{{$preLoadedproduct[5]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][currency_position]" value="{{$preLoadedproduct[6]}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][brand_name]" value="{{$preLoadedproduct['11']}}"></td>

                                                            {{-- Gadget Specs hidden inputs --}}
                                                            <td><input type="hidden" name="products[{{$key}}][model]" value="{{$preLoadedproduct[16] ?? ''}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][processor]" value="{{$preLoadedproduct[17] ?? ''}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][ram]" value="{{$preLoadedproduct[18] ?? ''}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][storage]" value="{{$preLoadedproduct[19] ?? ''}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][display]" value="{{$preLoadedproduct[20] ?? ''}}"></td>
                                                            <td><input type="hidden" name="products[{{$key}}][product_condition]" value="{{$preLoadedproduct[21] ?? ''}}"></td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <hr>

                                    <label><strong>{{__('db.Information on Label')}} *</strong></label>

                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <strong><input type="checkbox" name="print[name]" checked value="1" /> {{__('db.Product Name')}}</strong>&nbsp;
                                            <div class="d-flex justify-content-start align-items-center mt-1">
                                                <small class="mr-2">Size (px):</small>
                                                <div><input type="number" class="form-control form-control-sm" style="width: 80px;" name="print[name_size]" value="11"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <strong><input type="checkbox" name="print[price]" checked value="1" /> {{__('db.Price')}}</strong>&nbsp;
                                            <div class="d-flex justify-content-start align-items-center mt-1">
                                                <small class="mr-2">Size (px):</small>
                                                <div><input type="number" class="form-control form-control-sm" style="width: 80px;" name="print[price_size]" value="11"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <strong><input type="checkbox" name="print[promo_price]" checked value="1" /> {{__('db.Promotional Price')}}</strong>
                                            <div class="d-flex justify-content-start align-items-center mt-1">
                                                <small class="mr-2">Size (px):</small>
                                                <div><input type="number" class="form-control form-control-sm" style="width: 80px;" name="print[promo_price_size]" value="11"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <strong><input type="checkbox" name="print[business_name]" checked value="1" /> {{__('db.Business Name')}}</strong>
                                            <div class="d-flex justify-content-start align-items-center mt-1">
                                                <small class="mr-2">Size (px):</small>
                                                <div><input type="number" class="form-control form-control-sm" style="width: 80px;" name="print[business_name_size]" value="10"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <strong><input type="checkbox" name="print[brand_name]" checked value="1" /> {{__('db.Brand')}}</strong>
                                            <div class="d-flex justify-content-start align-items-center mt-1">
                                                <small class="mr-2">Size (px):</small>
                                                <div><input type="number" class="form-control form-control-sm" style="width: 80px;" name="print[brand_name_size]" value="9"></div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Gadget Options Row --}}
                                    <div class="row mt-3 p-3 bg-light border rounded">
                                        <div class="col-md-3">
                                            <strong><input type="checkbox" name="print[specs]" checked value="1" /> Technical Specs</strong>
                                            <div class="d-flex justify-content-start align-items-center mt-1">
                                                <small class="mr-2">Size (px):</small>
                                                <div><input type="number" class="form-control form-control-sm" style="width: 80px;" name="print[specs_size]" value="9"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <strong><input type="checkbox" name="print[condition]" checked value="1" /> Product Condition</strong>
                                            <div class="d-flex justify-content-start align-items-center mt-1">
                                                <small class="mr-2">Size (px):</small>
                                                <div><input type="number" class="form-control form-control-sm" style="width: 80px;" name="print[condition_size]" value="8"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <strong><input type="checkbox" name="print[serial_number]" checked value="1" /> Serial Number / S/N</strong>
                                            <div class="d-flex justify-content-start align-items-center mt-1">
                                                <small class="mr-2">Size (px):</small>
                                                <div><input type="number" class="form-control form-control-sm" style="width: 80px;" name="print[serial_number_size]" value="8"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="mb-1 font-weight-bold">Barcode Type:</label>
                                            <select name="print[barcode_format]" class="form-control">
                                                <option value="C128" selected>1D Barcode (Code 128)</option>
                                                <option value="QR">2D Barcode (QR Code)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <input type="hidden" name="print[variations]" value="1">
                                    <input type="hidden" name="print[variations_size]" value="17">
                                    <input type="hidden" name="print[packing_date]" value="1">
                                    <input type="hidden" name="print[packing_date_size]" value="12">
                                    <hr>
                                    <div class="row mt-4">
                                        <div class="col-md-8">
                                            <label><strong>Paper Size *</strong></label>
                                            {!! Form::select('barcode_setting', $barcode_settings, !empty($default) ? $default->id : null, ['class' => 'form-control', 'id' => 'barcode_setting']); !!}
                                            <small class="text-muted mt-1 d-block">
                                                Tip: Pick <strong>Continuous Thermal - 50mm x 30mm</strong> for laptop labels with full specs and serials.
                                            </small>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group mt-3">
				                                <button type="button" id="labels_preview" class="btn btn-primary btn-big"><i class="dripicons-print"></i> Generate & Print Labels</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
	                {!! Form::close() !!}

                </div>
            </div>
        </div>
    </div>

    {{-- Modal for Selecting Individual Serials --}}
    <div id="serial-select-modal" tabindex="-1" role="dialog" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fa fa-barcode text-primary mr-1"></i> Select Serials for <span id="serial-modal-product-name" class="text-primary font-italic"></span>
                    </h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-7">
                            <input type="text" id="serial-search-filter" class="form-control" placeholder="Search serial numbers..." />
                        </div>
                        <div class="col-md-5 text-right">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-select-all-serials"><i class="fa fa-check-square-o"></i> Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary ml-1" id="btn-deselect-all-serials"><i class="fa fa-square-o"></i> Deselect All</button>
                        </div>
                    </div>
                    <div id="serials-list-box" style="max-height: 280px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 12px; background: #fafbfc;">
                        <!-- Serials checkboxes dynamically rendered here -->
                    </div>
                    <div class="mt-2 text-muted" style="font-size: 12px;">
                        <i class="fa fa-info-circle text-info"></i> Each selected serial will print on its own sticker with its unique barcode and technical specs. The row quantity will automatically lock to match your selected count.
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between">
                    <span class="font-weight-bold text-dark" id="modal-selected-summary">0 selected</span>
                    <div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary ml-1" id="btn-apply-serials"><i class="fa fa-check"></i> Apply Selection</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</section>

@endsection

@push('scripts')
<script type="text/javascript">

    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    $("ul#product #printBarcode-menu").addClass("active");

    <?php $productArray = []; ?>
    var lims_product_code = [
    @foreach($lims_product_list_without_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->code . ' (' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name)) . ')');
        ?>
    @endforeach
    @foreach($lims_product_list_with_variant as $product)
        <?php
            $productArray[] = htmlspecialchars($product->item_code . ' (' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name)) . ')');
        ?>
    @endforeach
    <?php
        echo  '"'.implode('","', $productArray).'"';
    ?>
    ];

    var lims_productcodeSearch = $('#lims_productcodeSearch');
    var key = {{ count($preLoadedproducts) > 0 ? count($preLoadedproducts) + 1 : 1 }};

    // Attach data to preloaded rows if any exist
    @foreach ($preLoadedproducts as $k => $p)
        (function() {
            var row = $('table.order-list tbody tr').eq({{$k}});
            row.data('available-serials', @json($p[23] ?? []));
            row.data('selected-serials', []);
        })();
    @endforeach

    lims_productcodeSearch.autocomplete({
        source: function(request, response) {
            var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
            response($.grep(lims_product_code, function(item) {
                return matcher.test(item);
            }));
        },
        select: function(event, ui) {
            var data = ui.item.value;
            $.ajax({
                type: 'GET',
                url: 'lims_product_search',
                data: {
                    data: data,
                    warehouse_id: $('#warehouse_id').val(),
                    barcode: true
                },
                success: function(responseData) {
                    data = responseData[0];
                    var flag = 1;

                    $(".product-code").each(function() {
                        if ($(this).text() == data[1]) {
                            alert('Duplicate input is not allowed!');
                            flag = 0;
                        }
                    });
                    $("input[name='product_code_name']").val('');

                    if (flag) {
                        var newRow = $('<tr data-imagedata="'+data[3]+'" data-price="'+data[2]+'" data-promo-price="'+data[4]+'" data-currency="'+data[5]+'" data-currency-position="'+data[6]+'">');
                        
                        // Name column with specs and serial button
                        var nameHtml = '<strong>' + data[0] + '</strong>';
                        var specs = [];
                        if (data[17]) specs.push(data[17]);
                        if (data[18]) specs.push(data[18]);
                        if (data[19]) specs.push(data[19]);
                        if (specs.length > 0) {
                            nameHtml += '<br><span class="badge-specs"><i class="fa fa-microchip"></i> ' + specs.join(' | ') + '</span>';
                        }
                        if (data[21]) {
                            nameHtml += ' <span class="badge badge-warning text-dark">' + data[21] + '</span>';
                        }
                        if (data[22] == 1) {
                            var availCount = (data[23] && Array.isArray(data[23])) ? data[23].length : 0;
                            nameHtml += '<div class="mt-1"><button type="button" class="btn btn-xs btn-outline-primary btn-select-serials" data-row="' + key + '"><i class="fa fa-barcode"></i> Select Serials (<span class="selected-serials-count">0</span> / ' + availCount + ' avail)</button></div>';
                        }

                        var cols = '';
                        cols += '<td>' + nameHtml + '</td>';
                        cols += '<td class="product-code">' + data[1] + '</td>';
                        
                        // Quantity column with lock message and serial container
                        cols += '<td>';
                        cols += '  <input type="number" class="form-control qty" name="products['+ key +'][quantity]" value="1" min="1" />';
                        cols += '  <small class="serial-lock-note text-warning font-weight-bold" style="display:none;"><i class="fa fa-lock"></i> Quantity is locked by selected serials</small>';
                        cols += '  <div class="selected-serials-container"></div>';
                        cols += '</td>';

                        // Warehouse select column
                        cols += '<td>';
                        if (data[14] == true) {
                            cols += '<select name="products['+ key +'][product_price]" class="form-control" required>';
                            cols += '<option value="">{{__("db.Choose Warehouse")}}</option>';
                            for (var i = 0; i < data[15].length; i++) {
                                cols += '<option value="'+data[15][i].price+'">'+data[15][i].warehouse_name+' | Price: '+data[15][i].price+'</option>';
                            }
                            cols += '</select>';
                        } else {
                            cols += '<input type="text" class="form-control" readonly name="products['+ key +'][product_price]" value="'+data[2]+'">';
                        }
                        cols += '</td>';

                        cols += '<td><button type="button" class="ibtnDel btn btn-md btn-danger"><i class="dripicons-trash"></i></button></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][product_id]" value="'+data[8]+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][product_name]" value="'+data[0]+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][sub_sku]" value="'+data[1]+'"></td>';
                        cols += '<td><input type="hidden" class="form-control" name="products['+ key +'][default_price]" value="'+data[2]+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][product_promo_price]" value="'+data[4]+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][currency]" value="'+data[5]+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][currency_position]" value="'+data[6]+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][brand_name]" value="'+data[11]+'"></td>';

                        // Gadget Specs hidden inputs
                        cols += '<td><input type="hidden" name="products['+ key +'][model]" value="'+(data[16]||'')+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][processor]" value="'+(data[17]||'')+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][ram]" value="'+(data[18]||'')+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][storage]" value="'+(data[19]||'')+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][display]" value="'+(data[20]||'')+'"></td>';
                        cols += '<td><input type="hidden" name="products['+ key +'][product_condition]" value="'+(data[21]||'')+'"></td>';

                        newRow.append(cols);
                        newRow.data('available-serials', data[23] || []);
                        newRow.data('selected-serials', []);

                        $("table.order-list tbody").append(newRow);
                        key++;
                    }
                }
            });
        }
    });

    // Delete product row
    $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
        $(this).closest("tr").remove();
    });

    // Submit / Print handler
    $('#labels_preview').click(function() {
        if ($('table.order-list tbody tr').length === 0) {
            alert('Please add at least one product before printing labels.');
            return;
        }
        var form = $('form#preview_setting_form');
        form.attr('action', "{{route('print.label')}}");
        form.attr('method', "POST");
        form.attr('target', "_blank");
        form[0].submit();
    });

    // -------------------------------------------------------------
    // Serial Selection Modal Logic & Lock Interactions
    // -------------------------------------------------------------
    var activeRow = null;
    var activeRowKey = null;

    $(document).on('click', '.btn-select-serials', function() {
        activeRow = $(this).closest('tr');
        activeRowKey = $(this).data('row');

        var productName = activeRow.find('input[name*="[product_name]"]').val();
        var availableSerials = activeRow.data('available-serials') || [];
        var selectedSerials = activeRow.data('selected-serials') || [];

        $('#serial-modal-product-name').text(productName);
        $('#serial-search-filter').val('');

        renderSerialCheckboxes(availableSerials, selectedSerials);
        $('#serial-select-modal').modal('show');
    });

    function renderSerialCheckboxes(availableSerials, selectedSerials, filterText) {
        var box = $('#serials-list-box');
        box.empty();
        filterText = (filterText || '').toLowerCase().trim();

        var filtered = availableSerials.filter(function(sn) {
            return !filterText || sn.toLowerCase().indexOf(filterText) !== -1;
        });

        if (availableSerials.length === 0) {
            box.html('<div class="text-center text-muted p-4"><i class="fa fa-exclamation-circle text-warning fa-2x mb-2"></i><br>No active serial numbers registered in database for this product.<br><small>You can still print generic barcode labels by setting the quantity directly.</small></div>');
            $('#modal-selected-summary').text('0 selected');
            return;
        }

        if (filtered.length === 0) {
            box.html('<div class="text-center text-muted p-3">No serials match filter "'+filterText+'".</div>');
            $('#modal-selected-summary').text(selectedSerials.length + ' selected');
            return;
        }

        var html = '<div class="row">';
        filtered.forEach(function(sn) {
            var isChecked = selectedSerials.indexOf(sn) !== -1 ? 'checked' : '';
            html += '<div class="col-md-4 col-sm-6 mb-2">';
            html += '  <div class="custom-control custom-checkbox p-1 border rounded bg-white">';
            html += '    <input type="checkbox" class="custom-control-input serial-item-checkbox" id="sn_' + sn + '" value="' + sn + '" ' + isChecked + '>';
            html += '    <label class="custom-control-label font-weight-bold ml-1" for="sn_' + sn + '" style="font-family: monospace; font-size: 13px; cursor: pointer;">' + sn + '</label>';
            html += '  </div>';
            html += '</div>';
        });
        html += '</div>';
        box.html(html);
        updateModalSummary();
    }

    function updateModalSummary() {
        var count = $('.serial-item-checkbox:checked').length;
        $('#modal-selected-summary').text(count + ' selected');
    }

    $(document).on('change', '.serial-item-checkbox', function() {
        updateModalSummary();
    });

    $('#btn-select-all-serials').click(function() {
        $('.serial-item-checkbox').prop('checked', true);
        updateModalSummary();
    });

    $('#btn-deselect-all-serials').click(function() {
        $('.serial-item-checkbox').prop('checked', false);
        updateModalSummary();
    });

    $('#serial-search-filter').on('input', function() {
        var filter = $(this).val();
        var availableSerials = activeRow.data('available-serials') || [];
        var currentChecked = [];
        $('.serial-item-checkbox:checked').each(function() {
            currentChecked.push($(this).val());
        });
        var previous = activeRow.data('selected-serials') || [];
        var allSelected = Array.from(new Set(previous.concat(currentChecked)));
        renderSerialCheckboxes(availableSerials, allSelected, filter);
    });

    // Apply Serials to Row
    $('#btn-apply-serials').click(function() {
        if (!activeRow) return;

        var chosen = [];
        $('.serial-item-checkbox:checked').each(function() {
            chosen.push($(this).val());
        });
        chosen = Array.from(new Set(chosen));

        activeRow.data('selected-serials', chosen);
        activeRow.find('.selected-serials-count').text(chosen.length);

        // Update hidden inputs for serials
        var container = activeRow.find('.selected-serials-container');
        container.empty();
        chosen.forEach(function(sn) {
            container.append('<input type="hidden" name="products[' + activeRowKey + '][serials][]" value="' + sn + '">');
        });

        // Quantity lock & visual feedback (User Note 2)
        var qtyInput = activeRow.find('input.qty');
        var lockNote = activeRow.find('.serial-lock-note');

        if (chosen.length > 0) {
            qtyInput.val(chosen.length);
            qtyInput.prop('readonly', true);
            qtyInput.addClass('locked-qty');
            qtyInput.attr('title', 'Quantity is locked (' + chosen.length + ' serials selected). Click "Select Serials" to modify.');
            lockNote.show();
        } else {
            qtyInput.prop('readonly', false);
            qtyInput.removeClass('locked-qty');
            qtyInput.removeAttr('title');
            lockNote.hide();
        }

        $('#serial-select-modal').modal('hide');
    });

    // Helpful visual feedback when staff clicks or focuses a locked quantity input
    $(document).on('click focus keydown', '.locked-qty', function(e) {
        var note = $(this).closest('td').find('.serial-lock-note');
        note.stop(true, true).fadeOut(150).fadeIn(150).fadeOut(150).fadeIn(150);
    });

    // When warehouse changes
    $('#warehouse_id').on('change', function() {
        $('#lims_productcodeSearch').val('');
        $('table.order-list tbody').empty();
        key = 1;
    });

</script>
@endpush
