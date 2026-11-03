@php
    $m = fn ($v) => '₹' . number_format((float) $v, 2);
    $acct = $payout->account_number ? 'XXXX' . substr($payout->account_number, -4) : null;
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>COD paid</title></head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f6f8;">
<table align="center" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 0;">
<tr><td align="center">
<table width="640" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:100%;">
    <tr><td style="background:#111827;padding:20px 32px;color:#ffffff;font-size:18px;font-weight:bold;">
        Shipxpeed &middot; COD paid
    </td></tr>
    <tr><td style="padding:24px 32px 8px;color:#333;font-size:15px;line-height:1.6;">
        <p style="margin:0 0 12px;">Hello {{ $seller->name ?? 'there' }},</p>
        <p style="margin:0 0 16px;">Your COD remittance of <b style="color:#16a34a;font-size:18px;">{{ $m($payout->amount) }}</b> has been paid on <b>{{ \Carbon\Carbon::parse($payout->paid_on)->format('d M Y') }}</b>{{ $acct ? ' to your bank account ' . ($payout->bank_name ? $payout->bank_name . ' ' : '') . $acct : '' }}.</p>
    </td></tr>
    <tr><td style="padding:0 32px 16px;">
        <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:6px;font-size:14px;color:#333;">
            <tr><td style="padding:9px 14px;border-bottom:1px solid #f0f1f3;">Orders</td><td align="right" style="padding:9px 14px;border-bottom:1px solid #f0f1f3;">{{ $payout->orders }}</td></tr>
            <tr><td style="padding:9px 14px;border-bottom:1px solid #f0f1f3;">COD amount</td><td align="right" style="padding:9px 14px;border-bottom:1px solid #f0f1f3;">{{ $m($payout->cod_amount) }}</td></tr>
            @if($payout->fee_amount > 0)
            <tr><td style="padding:9px 14px;border-bottom:1px solid #f0f1f3;">Early COD deduction</td><td align="right" style="padding:9px 14px;border-bottom:1px solid #f0f1f3;color:#dc2626;">&minus;{{ $m($payout->fee_amount) }}</td></tr>
            @endif
            @if($payout->wallet_adjusted > 0)
            <tr><td style="padding:9px 14px;border-bottom:1px solid #f0f1f3;">Adjusted to your wallet</td><td align="right" style="padding:9px 14px;border-bottom:1px solid #f0f1f3;color:#dc2626;">&minus;{{ $m($payout->wallet_adjusted) }}</td></tr>
            @endif
            <tr><td style="padding:10px 14px;font-weight:bold;">Paid to bank</td><td align="right" style="padding:10px 14px;font-weight:bold;">{{ $m($payout->amount) }}</td></tr>
            @if($payout->reference)
            <tr><td style="padding:9px 14px;border-top:1px solid #f0f1f3;">Reference</td><td align="right" style="padding:9px 14px;border-top:1px solid #f0f1f3;font-family:monospace;">{{ $payout->reference }}</td></tr>
            @endif
        </table>
    </td></tr>
    <tr><td style="padding:0 32px 16px;">
        <p style="margin:0 0 6px;font-weight:bold;color:#333;font-size:14px;">Orders in this payout</p>
        <table width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;color:#333;border-collapse:collapse;">
            <tr style="background:#f7f8fa;">
                <th align="left" style="padding:6px 8px;">AWB</th><th align="left" style="padding:6px 8px;">Delivered</th>
                <th align="right" style="padding:6px 8px;">COD</th><th align="right" style="padding:6px 8px;">Deduction</th><th align="right" style="padding:6px 8px;">Net</th>
            </tr>
            @foreach($lines as $l)
            <tr>
                <td style="padding:5px 8px;border-bottom:1px solid #f0f1f3;font-family:monospace;">{{ $l->awb }}</td>
                <td style="padding:5px 8px;border-bottom:1px solid #f0f1f3;">{{ \Carbon\Carbon::parse($l->delivered_on)->format('d M') }}</td>
                <td align="right" style="padding:5px 8px;border-bottom:1px solid #f0f1f3;">{{ $m($l->cod_amount) }}</td>
                <td align="right" style="padding:5px 8px;border-bottom:1px solid #f0f1f3;">{{ ($l->fee_amount + $l->wallet_adjusted) > 0 ? '−' . $m($l->fee_amount + $l->wallet_adjusted) : '—' }}</td>
                <td align="right" style="padding:5px 8px;border-bottom:1px solid #f0f1f3;">{{ $m($l->net_payable) }}</td>
            </tr>
            @endforeach
        </table>
        @if($more > 0)<p style="margin:8px 0 0;font-size:12px;color:#666;">+ {{ $more }} more orders. See the full list in your panel.</p>@endif
    </td></tr>
    <tr><td style="padding:8px 32px 28px;">
        <a href="{{ $url }}" style="background:#6366f1;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;display:inline-block;">View COD remittance</a>
        <p style="margin:16px 0 0;font-size:12px;color:#888;">Questions? Reply to this email or raise a ticket in your Shipxpeed panel.</p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
