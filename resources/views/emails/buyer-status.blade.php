@php
    $accent = match ($event) {
        'delivered' => '#0f9f6e',
        'ndr', 'cancelled', 'rto_initiated' => '#d97706',
        default => '#2563eb',
    };
    $steps = ['booked' => 'Shipped', 'in_transit' => 'In transit', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered'];
    $pos = ['booked' => 0, 'picked_up' => 0, 'in_transit' => 1, 'out_for_delivery' => 2, 'ndr' => 2, 'delivered' => 3][$event] ?? null;
    $inr = fn ($n) => '₹' . number_format((float) $n, 2);
@endphp
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $label }}</title></head>
<body style="margin:0;padding:0;background:#f2f4f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f4f8;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb">
    <tr><td style="padding:18px 24px;border-bottom:1px solid #eef0f4;font-size:16px;font-weight:700;color:#111827">{{ $brand }}</td></tr>
    <tr><td style="padding:24px 24px 8px">
        <div style="display:inline-block;padding:4px 10px;border-radius:999px;background:{{ $accent }}1a;color:{{ $accent }};font-size:12px;font-weight:700">{{ $label }}</div>
        <h1 style="margin:12px 0 6px;font-size:22px;line-height:1.3;color:#111827">{{ $headline }}</h1>
        <p style="margin:0;font-size:14px;line-height:1.6;color:#4b5563">
            Hi {{ $name }},
            @switch($event)
                @case('booked') your order <b>#{{ $orderNo }}</b> is packed and handed to {{ $courier ?: 'our courier partner' }}. @break
                @case('picked_up') your order <b>#{{ $orderNo }}</b> has been picked up and will be on its way soon. @break
                @case('in_transit') your order <b>#{{ $orderNo }}</b> is moving through the courier network. @break
                @case('out_for_delivery') your order <b>#{{ $orderNo }}</b> will reach you today. Please keep your phone reachable{{ $cod ? ' and keep ' . $inr($collect) . ' ready for cash on delivery' : '' }}. @break
                @case('ndr') the courier could not deliver order <b>#{{ $orderNo }}</b> today. They will try again; if your address or phone needs a change, reply to the seller. @break
                @case('delivered') order <b>#{{ $orderNo }}</b> has been delivered. Thank you for shopping with {{ $brand }}! @break
                @case('rto_initiated') order <b>#{{ $orderNo }}</b> could not be delivered and is being returned to {{ $brand }}. @break
                @case('cancelled') order <b>#{{ $orderNo }}</b> has been cancelled. @break
            @endswitch
        </p>
    </td></tr>

    @if($pos !== null)
    <tr><td style="padding:16px 24px 4px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
            @foreach($steps as $i => $s)
                @php $k = array_search($i, array_keys($steps)); $done = $k <= $pos; @endphp
                <td align="center" style="font-size:11px;color:{{ $done ? $accent : '#9ca3af' }};font-weight:{{ $done ? '700' : '400' }}">
                    <div style="height:6px;border-radius:3px;margin:0 3px 6px;background:{{ $done ? $accent : '#e5e7eb' }}"></div>{{ $s }}
                </td>
            @endforeach
        </tr></table>
    </td></tr>
    @endif

    <tr><td style="padding:16px 24px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:10px;font-size:13px">
            <tr><td style="padding:12px 14px;color:#6b7280">Order</td><td align="right" style="padding:12px 14px;font-weight:700">#{{ $orderNo }}</td></tr>
            @if($courier)<tr><td style="padding:0 14px 12px;color:#6b7280">Courier</td><td align="right" style="padding:0 14px 12px">{{ $courier }}</td></tr>@endif
            @if($awb)<tr><td style="padding:0 14px 12px;color:#6b7280">AWB</td><td align="right" style="padding:0 14px 12px;font-family:monospace">{{ $awb }}</td></tr>@endif
            @if($showPrice && $amount)<tr><td style="padding:0 14px 12px;color:#6b7280">Order value</td><td align="right" style="padding:0 14px 12px">{{ $inr($amount) }}{{ $cod ? ' · COD' : ' · Prepaid' }}</td></tr>@endif
        </table>
    </td></tr>

    @if(!$hideProduct && count($items))
    <tr><td style="padding:0 24px 8px">
        <div style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Items in this shipment</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px">
            @foreach($items as $it)
            <tr>
                @if($it['image'])<td width="48" style="padding:6px 10px 6px 0"><img src="{{ $it['image'] }}" width="44" height="44" alt="" style="border-radius:6px;object-fit:cover;display:block"></td>@endif
                <td style="padding:6px 0">{{ $it['name'] }} <span style="color:#9ca3af">× {{ $it['qty'] }}</span></td>
                @if($showPrice && $it['price'] !== null)<td align="right" style="padding:6px 0">{{ $inr($it['price'] * max(1, $it['qty'])) }}</td>@endif
            </tr>
            @endforeach
        </table>
    </td></tr>
    @endif

    @if($awb && !in_array($event, ['cancelled'], true))
    <tr><td align="center" style="padding:16px 24px 26px">
        <a href="{{ $trackUrl }}" style="display:inline-block;background:{{ $accent }};color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 26px;border-radius:8px">Track your order</a>
    </td></tr>
    @endif

    <tr><td style="padding:14px 24px;border-top:1px solid #eef0f4;font-size:11px;color:#9ca3af;line-height:1.5">
        You are receiving this because you placed an order with {{ $brand }}. Shipping by Shipxpeed.
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
