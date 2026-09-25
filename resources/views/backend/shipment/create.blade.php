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

<section class="forms">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex align-items-center"><h4>New Shipment</h4></div>
            <div class="card-body">
                <p class="italic"><small>Pick the products that are on this shipment. A purchase can be split over several shipments, and one shipment can carry products of several purchases or suppliers.</small></p>
                {!! Form::open(['route' => 'shipments.store', 'method' => 'post', 'id' => 'shipment-form']) !!}
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label>Shipment type *</label>
                        <select name="shipment_type" id="shipment_type" class="form-control" required>
                            @foreach(\App\Models\Shipment::TYPES as $k => $v)
                            <option value="{{ $k }}" {{ old('shipment_type', 'cargo') == $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group" id="other-method-group" style="display:none">
                        <label>Other method (describe)</label>
                        <input type="text" name="other_method" class="form-control" value="{{ old('other_method') }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Carrier <small class="text-muted">(cargo company / hand carry person)</small></label>
                        <input type="text" name="carrier_name" id="carrier_name" list="carrier-list" class="form-control" value="{{ old('carrier_name') }}" placeholder="Choose from the list or type a name">
                        <datalist id="carrier-list">
                            @foreach($cargoCompanies as $c)<option value="{{ $c->name }}" data-type="cargo" data-contact="{{ $c->code }}">@endforeach
                            @foreach($handCarry as $h)<option value="{{ $h->name }}" data-type="hand_carry" data-contact="{{ $h->code }}">@endforeach
                        </datalist>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Carrier contact</label>
                        <input type="text" name="carrier_contact" id="carrier_contact" class="form-control" value="{{ old('carrier_contact') }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Tracking / reference no.</label>
                        <input type="text" name="tracking_no" class="form-control" value="{{ old('tracking_no') }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Responsible person</label>
                        <input type="text" name="responsible_person" class="form-control" value="{{ old('responsible_person') }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Shipment date</label>
                        <input type="date" name="shipment_date" class="form-control" value="{{ old('shipment_date', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Expected arrival</label>
                        <input type="date" name="expected_arrival" class="form-control" value="{{ old('expected_arrival') }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Destination (Bangladesh) *</label>
                        <select name="destination_warehouse_id" class="form-control" required>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" {{ old('destination_warehouse_id', $defaultWarehouse) == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-9 form-group">
                        <label>Remarks</label>
                        <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}">
                    </div>
                </div>

                <h5 class="mt-3">Products on this shipment</h5>
                @if($lines->isEmpty())
                    <div class="alert alert-info">There is nothing left to ship. Create a purchase of type <strong>Foreign</strong> first.</div>
                @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Purchase</th>
                                <th>Supplier</th>
                                <th>Product</th>
                                <th class="text-right">Ordered</th>
                                <th class="text-right">Left to ship</th>
                                <th style="width:140px">Quantity on this shipment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lines as $l)
                            <tr>
                                <td>{{ $l->reference_no }}</td>
                                <td>{{ $l->supplier_name ?: '-' }}</td>
                                <td>{{ $l->product_name }} @if($l->product_model)<small class="text-muted">({{ $l->product_model }})</small>@endif</td>
                                <td class="text-right">{{ amount_format($l->qty) }}</td>
                                <td class="text-right">{{ amount_format($l->remaining) }}</td>
                                <td><input type="number" min="0" max="{{ $l->remaining }}" step="any" name="items[{{ $l->id }}]" value="{{ old('items.'.$l->id, 0) }}" class="form-control form-control-sm"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h5 class="mt-3">Costs <small class="text-muted">(can also be entered later, at the final entry)</small></h5>
                <div class="row">
                    <div class="col-md-3 form-group"><label>Shipping cost (৳)</label><input type="number" step="any" min="0" name="shipping_cost" class="form-control" value="{{ old('shipping_cost') }}"></div>
                    <div class="col-md-3 form-group"><label>Customs / Cargo cost (৳)</label><input type="number" step="any" min="0" name="customs_cost" class="form-control" value="{{ old('customs_cost') }}"></div>
                    <div class="col-md-3 form-group"><label>Additional cost (৳)</label><input type="number" step="any" min="0" name="additional_cost" class="form-control" value="{{ old('additional_cost') }}"></div>
                    <div class="col-md-3 form-group">
                        <label>Paid from account</label>
                        <select name="cost_account_id" class="form-control">
                            <option value="">Choose at final entry</option>
                            @foreach($accounts as $a)<option value="{{ $a->id }}" {{ old('cost_account_id') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Create Shipment</button>
                @endif
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
    $("ul#purchase").siblings('a').attr('aria-expanded','true');
    $("ul#purchase").addClass("show");
    $("ul#purchase #shipment-create-menu").addClass("active");

    function toggleOther() { $('#other-method-group').toggle($('#shipment_type').val() === 'other'); }
    $('#shipment_type').on('change', toggleOther); toggleOther();

    $('#carrier_name').on('change', function () {
        var v = $(this).val();
        $('#carrier-list option').each(function () {
            if ($(this).val() === v) {
                if (!$('#carrier_contact').val()) { $('#carrier_contact').val($(this).data('contact') || ''); }
                if ($(this).data('type')) { $('#shipment_type').val($(this).data('type')); toggleOther(); }
            }
        });
    });
</script>
@endpush
