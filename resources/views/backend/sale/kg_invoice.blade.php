@php
    $kgLogo = !empty($general_setting->site_logo) ? url('logo', $general_setting->site_logo) : null;
    $kgCompany = $general_setting->company_name ?? $general_setting->site_title ?? 'KHAN GADGET';
    $kgBranch = $lims_warehouse_data;
    $kgGrand = (float) $lims_sale_data->grand_total;
    $kgPaid = (float) $lims_payment_data->sum('amount');
    $kgDue = max(0, round($kgGrand - $kgPaid, 2));
    $kgSubtotal = 0;
    foreach ($lims_product_sale_data as $row) {
        $kgSubtotal += (float) $row->net_unit_price * (float) $row->qty;
    }
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice {{ $lims_sale_data->reference_no }}</title>
<style>
    @page { size: A4; margin: 12mm; }
    .kg-inv { font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12px; line-height: 1.35; max-width: 190mm; margin: 0 auto; background: #fff; }
    .kg-inv * { box-sizing: border-box; }
    .kg-inv table { width: 100%; border-collapse: collapse; }
    .kg-inv .kg-head { display: table; width: 100%; border-bottom: 2px solid #111; padding-bottom: 8px; }
    .kg-inv .kg-head > div { display: table-cell; vertical-align: top; }
    .kg-inv .kg-logo img { max-height: 60px; max-width: 200px; }
    .kg-inv .kg-branch { text-align: right; font-size: 12px; }
    .kg-inv .kg-branch strong { font-size: 15px; }
    .kg-inv h1 { font-size: 20px; letter-spacing: 2px; margin: 12px 0 6px; }
    .kg-inv .kg-meta { display: table; width: 100%; margin-bottom: 10px; }
    .kg-inv .kg-meta > div { display: table-cell; width: 50%; vertical-align: top; }
    .kg-inv .kg-meta .lbl { color: #555; font-size: 11px; text-transform: uppercase; }
    .kg-inv table.kg-items th { background: #111; color: #fff; padding: 5px 6px; text-align: left; font-size: 11px; }
    .kg-inv table.kg-items td { border-bottom: 1px solid #ccc; padding: 6px; vertical-align: top; }
    .kg-inv .r { text-align: right; }
    .kg-inv .c { text-align: center; }
    .kg-inv .kg-specs { color: #444; font-size: 11px; }
    .kg-inv .kg-tag { display: inline-block; border: 1px solid #111; padding: 0 4px; font-size: 10px; font-weight: bold; margin-left: 4px; }
    .kg-inv .kg-totals { width: 46%; margin-left: auto; margin-top: 10px; }
    .kg-inv .kg-totals td { padding: 3px 6px; }
    .kg-inv .kg-totals tr.grand td { border-top: 2px solid #111; font-weight: bold; font-size: 14px; }
    .kg-inv .kg-words { margin-top: 8px; font-style: italic; }
    .kg-inv .kg-notes { margin-top: 14px; font-size: 11px; color: #333; }
    .kg-inv .kg-sign { display: table; width: 100%; margin-top: 46px; }
    .kg-inv .kg-sign > div { display: table-cell; width: 33%; text-align: center; }
    .kg-inv .kg-sign span { display: inline-block; border-top: 1px solid #111; padding-top: 3px; min-width: 130px; }
    .kg-inv .kg-foot { margin-top: 18px; text-align: center; font-size: 11px; color: #555; border-top: 1px solid #ccc; padding-top: 6px; }
    @media print { .kg-noprint { display: none !important; } }
</style>
</head>
<body>
<div class="kg-inv">
    <div class="kg-head">
        <div class="kg-logo">
            @if($kgLogo)<img src="{{ $kgLogo }}" alt="{{ $kgCompany }}">@else<strong style="font-size:20px">{{ $kgCompany }}</strong>@endif
        </div>
        <div class="kg-branch">
            <strong>{{ $kgBranch->name ?? $kgCompany }}</strong><br>
            @if(!empty($kgBranch->address)){{ $kgBranch->address }}<br>@endif
            @if(!empty($kgBranch->phone))Phone: {{ $kgBranch->phone }}<br>@endif
            @if(!empty($kgBranch->email)){{ $kgBranch->email }}@endif
        </div>
    </div>

    <h1>INVOICE</h1>
    <div class="kg-meta">
        <div>
            <div class="lbl">Bill to</div>
            <strong>{{ $lims_customer_data->name }}</strong><br>
            @if($lims_customer_data->company_name){{ $lims_customer_data->company_name }}<br>@endif
            @if($lims_customer_data->phone_number)Phone: {{ $lims_customer_data->phone_number }}<br>@endif
            @if($lims_customer_data->address){{ $lims_customer_data->address }}@endif
        </div>
        <div class="r">
            <div><span class="lbl">Invoice no.</span> <strong>{{ $lims_sale_data->reference_no }}</strong></div>
            <div><span class="lbl">Date</span> {{ $lims_sale_data->created_at->format('d M Y, h:i A') }}</div>
            <div><span class="lbl">Sold by</span> {{ $lims_bill_by['name'] ?? '' }}</div>
        </div>
    </div>

    <table class="kg-items">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th>Description</th>
                <th style="width:19%">Serial / IMEI</th>
                <th style="width:14%">Warranty</th>
                <th class="c" style="width:5%">Qty</th>
                <th class="r" style="width:12%">Unit price</th>
                <th class="r" style="width:12%">Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($lims_product_sale_data as $i => $row)
            @php
                $kgProduct = \App\Models\Product::find($row->product_id);
                $kgSerial = ($row->imei_number && !str_contains($row->imei_number, 'null')) ? $row->imei_number : null;
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <strong>{{ $kgProduct->name ?? '' }}</strong>
                    @if(!empty($row->product_condition))<span class="kg-tag">{{ ucfirst($row->product_condition) }}</span>@endif
                    @if(!empty($row->specs_line))<div class="kg-specs">{{ $row->specs_line }}</div>@endif
                </td>
                <td>{{ $kgSerial ?: '-' }}</td>
                <td>
                    @if(isset($row->warranty_duration))
                        {{ $row->warranty_duration }}<div class="kg-specs">until {{ $row->warranty_end }}</div>
                    @else - @endif
                </td>
                <td class="c">{{ $row->qty }}</td>
                <td class="r">{{ money($row->net_unit_price) }}@if($row->discount > 0)<div class="kg-specs">after discount {{ money($row->discount) }}</div>@endif</td>
                <td class="r">{{ money($row->total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="kg-totals">
        <tr><td>Subtotal</td><td class="r">{{ money($kgSubtotal) }}</td></tr>
        @if($lims_sale_data->order_discount > 0)<tr><td>Order discount</td><td class="r">- {{ money($lims_sale_data->order_discount) }}</td></tr>@endif
        @if($lims_sale_data->total_tax + $lims_sale_data->order_tax > 0)<tr><td>Tax</td><td class="r">{{ money($lims_sale_data->total_tax + $lims_sale_data->order_tax) }}</td></tr>@endif
        @if($lims_sale_data->shipping_cost > 0)<tr><td>Shipping</td><td class="r">{{ money($lims_sale_data->shipping_cost) }}</td></tr>@endif
        <tr class="grand"><td>Grand total</td><td class="r">{{ money($kgGrand) }}</td></tr>
        @foreach($lims_payment_data as $pay)
            <tr><td>Paid ({{ $pay->paying_method }})</td><td class="r">{{ money($pay->amount) }}</td></tr>
        @endforeach
        <tr><td><strong>Due</strong></td><td class="r"><strong>{{ money($kgDue) }}</strong></td></tr>
    </table>

    @if(!empty($numberInWords))<div class="kg-words">In words: {{ $numberInWords }}</div>@endif

    <div class="kg-notes">
        @if($lims_sale_data->sale_note)<div><strong>Note:</strong> {{ $lims_sale_data->sale_note }}</div>@endif
        <div>Please check the product, serial number and accessories before leaving the shop. Warranty is void for physical or liquid damage and for a removed or damaged warranty seal. Keep this invoice for any warranty claim.</div>
    </div>

    <div class="kg-sign">
        <div><span>Customer signature</span></div>
        <div></div>
        <div><span>Authorised signature</span></div>
    </div>

    <div class="kg-foot">{{ $invoice_settings->footer_text ?? 'Thank you for shopping with us' }} &middot; {{ $kgCompany }}</div>
</div>
</body>
</html>
