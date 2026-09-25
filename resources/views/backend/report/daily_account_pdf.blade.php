<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Daily Account {{ $r['date']->format('Y-m-d') }}</title>
<style>
    @page { margin: 14mm 12mm; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #111; }
    .kg-da h3 { font-size: 12px; margin: 14px 0 4px; border-bottom: 1px solid #111; padding-bottom: 2px; }
    table { width: 100%; border-collapse: collapse; }
    table.da th { background: #111; color: #fff; text-align: left; padding: 4px 5px; font-size: 9px; }
    table.da td { border-bottom: 1px solid #ccc; padding: 4px 5px; vertical-align: top; }
    tr.tot td { font-weight: bold; border-top: 1.5px solid #111; border-bottom: none; }
    .r { text-align: right; }
    .muted { color: #666; font-size: 9px; }
    table.da-head td { border-bottom: 2px solid #111; padding-bottom: 6px; vertical-align: top; }
</style>
</head>
<body>
@include('backend.report._daily_account_body', ['r' => $r])
</body>
</html>
