<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Service Token #{{ $job->ticket_no }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 5px;
            color: #000;
            font-size: 12px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .border-top { border-top: 1px dashed #000; margin-top: 8px; padding-top: 8px; }
        .border-bottom { border-bottom: 1px dashed #000; margin-bottom: 8px; padding-bottom: 8px; }
        .cut-line {
            border-top: 2px dashed #333;
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        .cut-line span {
            background: #fff;
            padding: 0 5px;
            position: relative;
            top: -9px;
            font-size: 10px;
            text-transform: uppercase;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        td { vertical-align: top; font-size: 11px; padding: 2px 0; }
        .td-label { width: 38%; font-weight: bold; }
        .badge {
            display: inline-block;
            border: 1px solid #000;
            padding: 2px 6px;
            font-weight: bold;
            font-size: 11px;
        }
        .barcode {
            margin: 5px auto;
            text-align: center;
        }
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body { margin: 0.5cm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; text-align: center;">
        <button onclick="window.print()" style="padding: 6px 12px; font-weight: bold; cursor: pointer;">
            Print Service Token
        </button>
    </div>

    <!-- PART 1: CUSTOMER COPY -->
    <div class="text-center">
        <h2 style="margin: 0; font-size: 16px;">{{ $general_setting->site_title ?? 'KGERP' }}</h2>
        <div style="font-size: 10px;">{{ $job->warehouse ? $job->warehouse->address : 'Dhaka, Bangladesh' }}</div>
        <div style="font-size: 10px;">Helpline: {{ $job->warehouse ? $job->warehouse->phone : '' }}</div>
        <div class="border-bottom" style="margin-top: 4px;">
            <span class="bold" style="font-size: 13px;">SERVICE INTAKE TOKEN</span><br>
            <span style="font-size: 10px;">(CUSTOMER COPY)</span>
        </div>
    </div>

    <div class="barcode">
        <?php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG($job->ticket_no, 'C128', 1.5, 35) . '" alt="barcode" />'; ?>
        <div class="bold" style="font-size: 13px; margin-top: 3px;">{{ $job->ticket_no }}</div>
    </div>

    <table>
        <tr>
            <td class="td-label">Date:</td>
            <td>{{ $job->received_at }}</td>
        </tr>
        <tr>
            <td class="td-label">Customer:</td>
            <td class="bold">{{ $job->customer_name }}</td>
        </tr>
        <tr>
            <td class="td-label">Phone:</td>
            <td>{{ $job->customer_phone }}</td>
        </tr>
        <tr>
            <td class="td-label">Device:</td>
            <td class="bold">{{ $job->product ? $job->product->name : 'N/A' }}</td>
        </tr>
        <tr>
            <td class="td-label">Serial No:</td>
            <td><span class="bold">{{ $job->serial_number }}</span></td>
        </tr>
        <tr>
            <td class="td-label">Warranty:</td>
            <td>
                @if($job->is_warranty_covered)
                    <span class="badge">UNDER WARRANTY</span>
                @else
                    <span class="badge">PAID SERVICE</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="td-label">Reported Issue:</td>
            <td>{{ $job->problem_description }}</td>
        </tr>
        @if($job->condition_notes)
        <tr>
            <td class="td-label">Condition:</td>
            <td>{{ $job->condition_notes }}</td>
        </tr>
        @endif
        @if($job->accessories_received)
        <tr>
            <td class="td-label">Accessories:</td>
            <td>{{ $job->accessories_received }}</td>
        </tr>
        @endif
    </table>

    <div class="border-top" style="font-size: 9px; line-height: 1.3;">
        * Please bring this token when picking up your device.<br>
        * {{ $general_setting->site_title ?? 'Company' }} is not responsible for any internal data loss. Please back up data before repair.<br>
        * Devices unclaimed within 30 days of completion may be recycled/disposed.
    </div>

    <!-- CUT LINE -->
    <div class="cut-line">
        <span>--- CUT HERE - ATTACH TO DEVICE ---</span>
    </div>

    <!-- PART 2: LAPTOP CHASSIS TAG -->
    <div class="text-center">
        <span class="bold" style="font-size: 13px;">{{ $general_setting->site_title ?? 'DEVICE' }} - SERVICE TAG</span>
    </div>

    <div class="barcode">
        <?php echo '<img src="data:image/png;base64,' . DNS1D::getBarcodePNG($job->serial_number, 'C128', 1.5, 30) . '" alt="barcode" />'; ?>
        <div class="bold" style="font-size: 11px;">S/N: {{ $job->serial_number }}</div>
    </div>

    <table>
        <tr>
            <td class="td-label">Ticket #:</td>
            <td class="bold">{{ $job->ticket_no }}</td>
        </tr>
        <tr>
            <td class="td-label">Customer:</td>
            <td>{{ $job->customer_name }} ({{ $job->customer_phone }})</td>
        </tr>
        <tr>
            <td class="td-label">Intake Date:</td>
            <td>{{ $job->received_at }}</td>
        </tr>
        <tr>
            <td class="td-label">Issue:</td>
            <td>{{ \Illuminate\Support\Str::limit($job->problem_description, 60) }}</td>
        </tr>
    </table>

    <script type="text/javascript">
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
