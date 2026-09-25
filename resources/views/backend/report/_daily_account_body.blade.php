@php
    // the PDF font has no taka sign, so the PDF writes "Tk" instead
    $num = fn($v) => ($pdf ?? false) ? str_replace('৳', 'Tk ', money($v)) : money($v);
    $logoFile = !empty($general_setting->site_logo) ? public_path('logo/' . $general_setting->site_logo) : null;
    $logoSrc = null;
    if ($logoFile && file_exists($logoFile)) {
        $logoSrc = ($pdf ?? false)
            ? 'data:' . (mime_content_type($logoFile) ?: 'image/png') . ';base64,' . base64_encode(file_get_contents($logoFile))
            : url('logo', $general_setting->site_logo);
    }
@endphp
<div class="kg-da">
    <table class="da-head">
        <tr>
            <td>
                @if($logoSrc)<img src="{{ $logoSrc }}" style="height:38px" alt="">@else<strong style="font-size:18px">{{ $general_setting->company_name ?? 'KHAN GADGET' }}</strong>@endif
            </td>
            <td style="text-align:right">
                <div style="font-size:16px;font-weight:bold">Daily Account</div>
                <div>{{ $r['branch_name'] }}</div>
                <div>{{ $r['date']->format('l, d F Y') }}</div>
            </td>
        </tr>
    </table>

    <h3>1. Opening balance</h3>
    <table class="da">
        <thead><tr><th>Account</th><th class="r">Opening balance</th></tr></thead>
        <tbody>
        @foreach($r['cash_rows'] as $row)
            <tr><td>{{ $row['name'] }}</td><td class="r">{{ $num($row['opening']) }}</td></tr>
        @endforeach
            <tr class="tot"><td>Total opening</td><td class="r">{{ $num($r['cash_sum']['opening']) }}</td></tr>
        </tbody>
    </table>

    <h3>2. Sales summary</h3>
    <table class="da">
        <thead><tr><th>Invoices</th><th class="r">Quantity</th><th class="r">Discount</th><th class="r">Total sales</th><th class="r">Paid</th><th class="r">Due</th></tr></thead>
        <tbody>
            <tr>
                <td>{{ $r['summary']['invoices'] }}</td>
                <td class="r">{{ rtrim(rtrim(number_format($r['summary']['qty'], 2, '.', ''), '0'), '.') }}</td>
                <td class="r">{{ $num($r['summary']['discount']) }}</td>
                <td class="r">{{ $num($r['summary']['total']) }}</td>
                <td class="r">{{ $num($r['summary']['paid']) }}</td>
                <td class="r">{{ $num($r['summary']['due']) }}</td>
            </tr>
        </tbody>
    </table>

    <h3>3. Sales details</h3>
    <table class="da">
        <thead><tr><th style="width:15%">Invoice</th><th>Product</th><th style="width:17%">Serial</th><th class="r" style="width:5%">Qty</th><th class="r" style="width:11%">Price</th><th class="r" style="width:9%">Discount</th><th class="r" style="width:11%">Total</th><th style="width:10%">Paid by</th></tr></thead>
        <tbody>
        @forelse($r['sale_rows'] as $row)
            <tr>
                <td>@if($row['first'])<strong>{{ $row['invoice'] }}</strong><br><span class="muted">{{ $row['time'] }}</span>@endif</td>
                <td>{{ $row['product'] }}@if($row['specs'])<br><span class="muted">{{ $row['specs'] }}</span>@endif</td>
                <td>{{ $row['serial'] }}</td>
                <td class="r">{{ $row['qty'] + 0 }}</td>
                <td class="r">{{ $num($row['unit_price']) }}</td>
                <td class="r">{{ $row['discount'] > 0 ? $num($row['discount']) : '-' }}</td>
                <td class="r">{{ $num($row['total']) }}</td>
                <td>{{ $row['paid_by'] }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted">No sales today.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3>4. Expenses</h3>
    <table class="da">
        <thead><tr><th>Time</th><th>Reference</th><th>Category</th><th>Note</th><th class="r">Amount</th></tr></thead>
        <tbody>
        @forelse($r['expenses'] as $e)
            <tr>
                <td>{{ $e->created_at->format('h:i A') }}</td>
                <td>{{ $e->reference_no }}</td>
                <td>{{ $e->expenseCategory->name ?? '-' }}</td>
                <td>{{ $e->note }}</td>
                <td class="r">{{ $num($e->amount) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">No expenses today.</td></tr>
        @endforelse
            <tr class="tot"><td colspan="4">Total expenses</td><td class="r">{{ $num($r['expense_total']) }}</td></tr>
        </tbody>
    </table>

    <h3>5. Transfer history</h3>
    <table class="da">
        <thead><tr><th>Reference</th><th>Type</th><th>From</th><th>To</th><th class="r">Sent</th><th class="r">Received</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($r['transfers'] as $t)
            @php $mineOut = in_array($t->from_account_id, $r['account_ids']); $mineIn = in_array($t->to_account_id, $r['account_ids']); @endphp
            <tr>
                <td>{{ $t->reference_no }}</td>
                <td>{{ $t->type_label }}</td>
                <td>{{ $t->fromAccount->name ?? '-' }}</td>
                <td>{{ $t->toAccount->name ?? ($t->third_party_name ?: '-') }}</td>
                <td class="r">{{ $mineOut ? $num($t->amount) : '-' }}</td>
                <td class="r">{{ $mineIn ? $num($t->accepted_amount) : '-' }}</td>
                <td>{{ $t->status_label }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted">No transfers today.</td></tr>
        @endforelse
            <tr class="tot"><td colspan="4">Total transferred</td><td class="r">{{ $num($r['transfer_sent']) }}</td><td class="r">{{ $num($r['transfer_received']) }}</td><td></td></tr>
        </tbody>
    </table>

    <h3>6. Final cash summary</h3>
    <table class="da">
        <thead><tr><th>Account</th><th class="r">Opening</th><th class="r">Money in</th><th class="r">Money out</th><th class="r">Closing</th></tr></thead>
        <tbody>
        @foreach($r['cash_rows'] as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td class="r">{{ $num($row['opening']) }}</td>
                <td class="r">{{ $num($row['in']) }}</td>
                <td class="r">{{ $num($row['out']) }}</td>
                <td class="r">{{ $num($row['closing']) }}</td>
            </tr>
        @endforeach
            <tr class="tot">
                <td>Total</td>
                <td class="r">{{ $num($r['cash_sum']['opening']) }}</td>
                <td class="r">{{ $num($r['cash_sum']['in']) }}</td>
                <td class="r">{{ $num($r['cash_sum']['out']) }}</td>
                <td class="r">{{ $num($r['cash_sum']['closing']) }}</td>
            </tr>
        </tbody>
    </table>
    <p class="muted" style="margin-top:8px">The next day opens with today's closing balance. Generated {{ now()->format('d M Y, h:i A') }}@if($r['generated_by']) by {{ $r['generated_by'] }}@endif.</p>
</div>
