@php use App\Support\Xpeed\Kit; @endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pickup manifest {{ $no }}</title>
<style>
    * { box-sizing:border-box; }
    body { font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; color:#111; margin:0; padding:24px; font-size:12px; }
    .bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
    .bar button { padding:8px 16px; border:0; border-radius:6px; background:#2f6fed; color:#fff; font-weight:600; cursor:pointer; }
    .sheet { page-break-after:always; margin-bottom:28px; }
    .sheet:last-child { page-break-after:auto; }
    h1 { font-size:18px; margin:0; } h2 { font-size:15px; margin:0 0 2px; }
    .meta { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; margin:12px 0; padding:10px; border:1px solid #ccc; border-radius:6px; }
    .meta b { display:block; font-size:10px; color:#666; text-transform:uppercase; letter-spacing:.04em; }
    table { width:100%; border-collapse:collapse; }
    th, td { border:1px solid #bbb; padding:5px 6px; text-align:left; }
    th { background:#f1f3f7; font-size:11px; }
    td.n { text-align:right; }
    .mono { font-family:ui-monospace,Menlo,Consolas,monospace; }
    .sign { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:28px; }
    .sign div { border-top:1px solid #333; padding-top:6px; }
    @media print { .bar { display:none; } body { padding:0; } }
</style>
</head>
<body>
<div class="bar">
    <div><h1>Pickup manifest</h1><div>{{ $no }} · {{ now()->format('d M Y, h:i A') }}</div></div>
    <button onclick="window.print()">Print</button>
</div>
@foreach($groups as $courier => $orders)
    @php $pick = Kit::pickup($orders->first()); @endphp
    <div class="sheet">
        <h2>{{ $courier }}</h2>
        <div class="meta">
            <div><b>Seller</b>{{ $seller->name }}</div>
            <div><b>Pickup from</b>{{ $pick['warehouse_name'] ?? ($pick['name'] ?? '') }}<br>{{ $pick['city'] ?? '' }} {{ $pick['pincode'] ?? ($pick['pin_code'] ?? '') }}</div>
            <div><b>Shipments</b>{{ $orders->count() }}</div>
            <div><b>COD to collect</b>{{ Kit::money($orders->filter(fn ($o) => strtolower($o->payment_type) === 'cod')->sum('collectable_amount')) }}</div>
        </div>
        <table>
            <thead><tr><th>#</th><th>AWB</th><th>Order</th><th>Consignee</th><th>Destination</th><th>Payment</th><th class="n">COD ₹</th><th class="n">Weight</th></tr></thead>
            <tbody>
            @foreach($orders as $i => $o)
                @php $c = Kit::consignee($o); $w = (float) $o->package_weight; $kg = $w > 0 && $w < 10 ? $w : $w / 1000; @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="mono">{{ $o->awb_number }}</td>
                    <td>{{ Kit::ref($o) }}</td>
                    <td>{{ $c['name'] ?? '' }}</td>
                    <td>{{ $c['city'] ?? '' }} {{ $c['pincode'] ?? '' }}</td>
                    <td>{{ strtoupper($o->payment_type) }}</td>
                    <td class="n">{{ strtolower($o->payment_type) === 'cod' ? number_format((float) $o->collectable_amount, 2) : '—' }}</td>
                    <td class="n">{{ $kg ? number_format($kg, 2) . ' kg' : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="sign">
            <div>Handed over by (seller) — name, signature, date</div>
            <div>Received by ({{ $courier }} pickup) — name, signature, time</div>
        </div>
    </div>
@endforeach
</body>
</html>
