@php
    $color = match ($t['stage']) {
        'delivered' => '#16a34a', 'ndr', 'lost' => '#d97706', 'rto', 'rto_delivered', 'cancelled' => '#dc2626', 'ofd' => '#ea8a00', default => '#4f46e5',
    };
    $orderNo = ltrim($t['order_no'], '# ');
@endphp
<!doctype html>
<html><body style="margin:0;padding:0;background:#f3f5fb;font-family:Segoe UI,Helvetica,Arial,sans-serif;color:#1f2937">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f5fb;padding:24px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e8f2">
    <tr><td style="background:#0b1226;padding:18px 24px;color:#ffffff;font-size:15px">
        <b style="font-size:17px">{{ $t['brand'] }}</b>
        <span style="color:#9aa6c8"> · Order #{{ $orderNo }}</span>
    </td></tr>
    <tr><td style="padding:24px 24px 8px">
        @if($welcome)
            <p style="margin:0 0 14px;font-size:14px;color:#4b5563">You're subscribed. We'll email you the latest tracking every 24 hours until delivery.</p>
        @endif
        <div style="display:inline-block;padding:4px 10px;border-radius:999px;background:{{ $color }}1a;color:{{ $color }};font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em">{{ $t['status_label'] }}</div>
        <p style="margin:12px 0 4px;font-size:18px;font-weight:700;line-height:1.35">{{ $t['status_note'] }}</p>
        <p style="margin:0;font-size:13px;color:#6b7280">
            {{ $t['courier'] }} · AWB {{ $t['awb'] }}
            @if($t['updated_at']) · last scan {{ $t['updated_at']->format('j M, g:i A') }} @endif
        </p>
        @if($t['edd'] && !$final)
            <p style="margin:10px 0 0;font-size:14px;color:#92400e"><b>Expected delivery:</b> {{ $t['edd']->format('D, j M') }}</p>
        @endif
    </td></tr>
    <tr><td style="padding:16px 24px 4px">
        <p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.05em">
            @if($welcome) Latest updates @elseif($noMovement) No new scans in the last 24 hours @else Updates since our last email @endif
        </p>
        @if($noMovement && !empty($t['events'][0]))
            @php $newEvents = [$t['events'][0]]; @endphp
            <p style="margin:0 0 8px;font-size:13px;color:#6b7280">Most recent courier remark:</p>
        @endif
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            @forelse($newEvents as $i => $e)
            <tr>
                <td valign="top" style="width:96px;padding:8px 10px 8px 0;font-size:12.5px;color:#6b7280;border-top:1px solid #eef0f6">{{ $e['time'] ? $e['time']->format('j M') : '' }}<br>{{ $e['time'] ? $e['time']->format('g:i A') : '' }}</td>
                <td valign="top" style="padding:8px 0;border-top:1px solid #eef0f6;font-size:14px">
                    <b style="color:{{ $i === 0 ? $color : '#111827' }}">{{ $e['status'] }}</b>
                    @if($e['remark'] !== '')<br><span style="color:#374151">{{ $e['remark'] }}</span>@endif
                    @if($e['location'] !== '')<br><span style="color:#9ca3af;font-size:12.5px">{{ $e['location'] }}</span>@endif
                </td>
            </tr>
            @empty
            <tr><td style="padding:8px 0;font-size:14px;color:#6b7280;border-top:1px solid #eef0f6">The courier has not shared any scans yet.</td></tr>
            @endforelse
        </table>
    </td></tr>
    <tr><td style="padding:18px 24px 26px" align="left">
        <a href="{{ $trackUrl }}" style="display:inline-block;background:#ff9f0a;color:#1b1200;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:10px;font-size:14px">View full tracking</a>
        @if($final)
            <p style="margin:16px 0 0;font-size:13px;color:#6b7280">This is the last update for this shipment. Thank you for shopping with {{ $t['brand'] }}.</p>
        @endif
    </td></tr>
    <tr><td style="padding:14px 24px;background:#f8f9fc;border-top:1px solid #eef0f6;font-size:12px;color:#9ca3af">
        You asked for tracking emails for this order on the {{ $t['brand'] }} tracking page.
        <a href="{{ $stopUrl }}" style="color:#6b7280">Stop these emails</a> · Shipping by Shipxpeed · Times in IST
    </td></tr>
</table>
</td></tr></table>
</body></html>
