@extends('backend.layout.main')
@section('content')

<x-success-message key="message" />
<x-error-message key="not_permitted" />
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

@php
    $cls = ['in_transit' => 'warning', 'received' => 'info', 'final_entry' => 'success', 'cancelled' => 'secondary'][$shipment->status] ?? 'secondary';
    $steps = ['in_transit' => 'Shipped / In-Transit', 'received' => 'BD Warehouse Received', 'final_entry' => 'Added to Stock'];
    $order = array_keys($steps);
    $currentIdx = array_search($shipment->status, $order);
@endphp

<section class="forms">
    <div class="container-fluid">
        <a href="{{ route('shipments.index') }}" class="btn btn-sm btn-default mb-2"><i class="dripicons-arrow-thin-left"></i> All shipments</a>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h4 class="mb-0">{{ $shipment->reference_no }} <span class="badge badge-{{ $cls }} ml-2">{{ $shipment->status_label }}</span></h4>
                @if(in_array($shipment->status, ['in_transit', 'received']))
                {!! Form::open(['route' => ['shipments.cancel', $shipment->id], 'method' => 'post', 'onsubmit' => "return confirm('Cancel this shipment? The quantities become available for a new shipment.')"]) !!}
                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel shipment</button>
                {!! Form::close() !!}
                @endif
            </div>
            <div class="card-body">
                @if($shipment->status !== 'cancelled')
                <div class="d-flex mb-3 flex-wrap">
                    @foreach($steps as $key => $label)
                        @php $done = $currentIdx !== false && array_search($key, $order) <= $currentIdx; @endphp
                        <div class="mr-4 mb-2">
                            <span class="badge badge-{{ $done ? 'dark' : 'light' }}" style="font-size:.95rem">{{ $loop->iteration }}</span>
                            <span class="{{ $done ? 'font-weight-bold' : 'text-muted' }}">{{ $label }}</span>
                            @if($key === 'in_transit')<small class="text-muted">{{ optional($shipment->shipment_date)->format('d/m/Y') }}</small>@endif
                            @if($key === 'received' && $shipment->received_at)<small class="text-muted">{{ optional($shipment->actual_arrival)->format('d/m/Y') }}</small>@endif
                            @if($key === 'final_entry' && $shipment->finalized_at)<small class="text-muted">{{ $shipment->finalized_at->format('d/m/Y') }}</small>@endif
                        </div>
                    @endforeach
                </div>
                @endif

                <div class="row">
                    <div class="col-md-3"><strong>Type</strong><br>{{ $shipment->type_label }}@if($shipment->other_method) ({{ $shipment->other_method }})@endif</div>
                    <div class="col-md-3"><strong>Carrier</strong><br>{{ $shipment->carrier_name ?: '-' }} @if($shipment->carrier_contact)<small class="text-muted">{{ $shipment->carrier_contact }}</small>@endif</div>
                    <div class="col-md-3"><strong>Tracking</strong><br>{{ $shipment->tracking_no ?: '-' }}</div>
                    <div class="col-md-3"><strong>Responsible person</strong><br>{{ $shipment->responsible_person ?: '-' }}</div>
                    <div class="col-md-3 mt-2"><strong>Shipment date</strong><br>{{ optional($shipment->shipment_date)->format('d/m/Y') ?: '-' }}</div>
                    <div class="col-md-3 mt-2"><strong>Expected arrival</strong><br>{{ optional($shipment->expected_arrival)->format('d/m/Y') ?: '-' }}</div>
                    <div class="col-md-3 mt-2"><strong>Actual arrival</strong><br>{{ optional($shipment->actual_arrival)->format('d/m/Y') ?: '-' }}</div>
                    <div class="col-md-3 mt-2"><strong>Destination</strong><br>{{ $shipment->warehouse->name ?? '-' }}</div>
                    @if($shipment->remarks)<div class="col-md-12 mt-2"><strong>Remarks</strong><br>{{ $shipment->remarks }}</div>@endif
                </div>
            </div>
        </div>

        {{-- STEP 2: receive --}}
        @if($shipment->status === 'in_transit')
        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">Products on this shipment</h5></div>
            <div class="card-body">
                {!! Form::open(['route' => ['shipments.receive', $shipment->id], 'method' => 'post']) !!}
                <p class="text-muted">When the goods reach the Bangladesh warehouse, check the quantities and mark the shipment as received. Stock is added only at the final entry.</p>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>Purchase</th><th>Product</th><th class="text-right">Shipped</th><th style="width:150px">Received</th><th>Remarks (missing / defective)</th></tr></thead>
                        <tbody>
                        @foreach($shipment->items as $item)
                            <tr>
                                <td>{{ $item->purchase->reference_no ?? '-' }}</td>
                                <td>{{ $item->product->name ?? '-' }}</td>
                                <td class="text-right">{{ amount_format($item->qty_shipped) }}</td>
                                <td><input type="number" min="0" max="{{ $item->qty_shipped }}" step="any" name="received[{{ $item->id }}][qty]" value="{{ $item->qty_shipped + 0 }}" class="form-control form-control-sm"></td>
                                <td><input type="text" name="received[{{ $item->id }}][remarks]" class="form-control form-control-sm" value="{{ $item->remarks }}"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group"><label>Arrival date</label><input type="date" name="actual_arrival" class="form-control" value="{{ date('Y-m-d') }}"></div>
                </div>
                <button type="submit" class="btn btn-primary">Mark as received in Bangladesh warehouse</button>
                {!! Form::close() !!}
            </div>
        </div>
        @endif

        {{-- STEP 3: final entry --}}
        @if($shipment->status === 'received')
        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">Final entry &mdash; serial numbers and costs</h5></div>
            <div class="card-body">
                {!! Form::open(['route' => ['shipments.finalize', $shipment->id], 'method' => 'post', 'id' => 'final-form']) !!}
                <p class="text-muted">Enter one serial number per unit (one per line; <code>SN123:Body scratch</code> also saves a detailed condition). Landed cost (shipping + customs + additional) is split equally over every piece and added to each unit's cost.</p>

                @foreach($shipment->items->where('qty_received', '>', 0) as $item)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between">
                        <div><strong>{{ $item->product->name ?? '-' }}</strong> <small class="text-muted">{{ $item->purchase->reference_no ?? '' }}</small></div>
                        <div>Received: <strong>{{ amount_format($item->qty_received) }}</strong> &middot; Unit purchase cost: <strong>{{ money($item->productPurchase->net_unit_cost ?? 0) }}</strong></div>
                    </div>
                    <div class="checkbox mt-2">
                        <input type="checkbox" class="track-serials" id="track-{{ $item->id }}" name="lines[{{ $item->id }}][track_serials]" value="1" data-item="{{ $item->id }}" {{ old('lines.'.$item->id.'.track_serials', $trackDefault[$item->id] ?? false) ? 'checked' : '' }}>
                        <label for="track-{{ $item->id }}">Track each unit by serial number</label>
                    </div>
                    <div class="serial-box" id="serials-{{ $item->id }}">
                        <textarea name="lines[{{ $item->id }}][serials]" rows="{{ min(8, max(3, (int) $item->qty_received)) }}" class="form-control serial-input" data-qty="{{ (int) $item->qty_received }}" placeholder="One serial number per line ({{ (int) $item->qty_received }} needed)">{{ old('lines.'.$item->id.'.serials') }}</textarea>
                        <small class="text-muted serial-count">0 / {{ (int) $item->qty_received }} entered</small>
                    </div>
                </div>
                @endforeach

                <h6>Costs</h6>
                <div class="row">
                    <div class="col-md-3 form-group"><label>Shipping cost (৳)</label><input type="number" step="any" min="0" name="shipping_cost" class="form-control cost-input" value="{{ old('shipping_cost', $shipment->shipping_cost + 0) }}"></div>
                    <div class="col-md-3 form-group"><label>Customs / Cargo cost (৳)</label><input type="number" step="any" min="0" name="customs_cost" class="form-control cost-input" value="{{ old('customs_cost', $shipment->customs_cost + 0) }}"></div>
                    <div class="col-md-3 form-group"><label>Additional cost (৳)</label><input type="number" step="any" min="0" name="additional_cost" class="form-control cost-input" value="{{ old('additional_cost', $shipment->additional_cost + 0) }}"></div>
                    <div class="col-md-3 form-group">
                        <label>Paid from account</label>
                        <select name="cost_account_id" class="form-control">
                            <option value="">Select account</option>
                            @foreach($accounts as $a)<option value="{{ $a->id }}" {{ old('cost_account_id', $shipment->cost_account_id) == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <p class="mb-3">Landed cost per piece: <strong id="per-piece">{{ money(0) }}</strong> <small class="text-muted">({{ $pieces }} pieces)</small></p>
                <button type="submit" class="btn btn-success" onclick="return confirm('Add these products to stock? This cannot be undone.')">Complete final entry &amp; add to stock</button>
                {!! Form::close() !!}
            </div>
        </div>
        @endif

        {{-- summary once finished --}}
        @if($shipment->status === 'final_entry')
        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">Final entry summary</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>Purchase</th><th>Product</th><th class="text-right">Shipped</th><th class="text-right">Received</th><th class="text-right">Added to stock</th><th>Remarks</th></tr></thead>
                        <tbody>
                        @foreach($shipment->items as $item)
                            <tr>
                                <td>{{ $item->purchase->reference_no ?? '-' }}</td>
                                <td>{{ $item->product->name ?? '-' }}</td>
                                <td class="text-right">{{ amount_format($item->qty_shipped) }}</td>
                                <td class="text-right">{{ amount_format($item->qty_received) }}</td>
                                <td class="text-right">{{ amount_format($item->qty_finalized) }}</td>
                                <td>{{ $item->remarks }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-md-3"><strong>Shipping</strong><br>{{ money($shipment->shipping_cost) }}</div>
                    <div class="col-md-3"><strong>Customs / Cargo</strong><br>{{ money($shipment->customs_cost) }}</div>
                    <div class="col-md-3"><strong>Additional</strong><br>{{ money($shipment->additional_cost) }}</div>
                    <div class="col-md-3"><strong>Total landed cost</strong><br>{{ money($shipment->total_landed_cost) }}
                        @if($shipment->expense)<small class="text-muted d-block">Expense {{ $shipment->expense->reference_no }}</small>@endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#purchase").siblings('a').attr('aria-expanded','true');
    $("ul#purchase").addClass("show");
    $("ul#purchase #shipment-list-menu").addClass("active");

    function serialLines(text) {
        return $.grep(text.split(/[\r\n,]+/), function (l) { return $.trim(l) !== ''; }).length;
    }
    function refreshSerials() {
        $('.serial-box').each(function () {
            var box = $(this), ta = box.find('textarea'), id = box.attr('id').replace('serials-', '');
            var on = $('#track-' + id).is(':checked');
            box.toggle(on);
            var need = parseInt(ta.data('qty'), 10), have = serialLines(ta.val());
            box.find('.serial-count').text(have + ' / ' + need + ' entered').toggleClass('text-danger', on && have !== need);
        });
    }
    $(document).on('change keyup', '.track-serials, .serial-input', refreshSerials);
    refreshSerials();

    var pieces = {{ (int) $pieces }};
    function refreshCost() {
        var total = 0;
        $('.cost-input').each(function () { total += parseFloat($(this).val()) || 0; });
        $('#per-piece').text(kgMoney(pieces ? total / pieces : 0));
    }
    $(document).on('input keyup change', '.cost-input', refreshCost);
    refreshCost();
</script>
@endpush
