<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Ticket update</title></head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f6f8;">
<table align="center" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
    <tr><td style="background:#111827;padding:20px 32px;color:#ffffff;font-size:18px;font-weight:bold;">
        Shipxpeed &middot; Ticket update
    </td></tr>
    <tr><td style="padding:24px 32px 8px;color:#333;font-size:15px;line-height:1.6;">
        <p style="margin:0 0 12px;">Hello {{ $seller->name ?? 'there' }},</p>
        <p style="margin:0 0 12px;">Your ticket <b>{{ $ticket->ticket_id }}</b> (AWB {{ $ticket->order_id }}) is now <b>{{ $ticket->status }}</b>.</p>
    </td></tr>
    @if($ticket->admin_reply)
    <tr><td style="padding:0 32px 16px;">
        <p style="margin:0 0 6px;font-weight:bold;color:#333;font-size:14px;">Reply from Shipxpeed support</p>
        <div style="background:#f7f8fa;border-left:4px solid #22c55e;padding:12px 14px;color:#333;font-size:14px;line-height:1.6;">{!! nl2br(e($ticket->admin_reply)) !!}</div>
    </td></tr>
    @endif
    <tr><td style="padding:0 32px 16px;">
        <p style="margin:0 0 6px;font-weight:bold;color:#333;font-size:14px;">Your message</p>
        <div style="background:#f7f8fa;padding:12px 14px;color:#555;font-size:14px;line-height:1.6;">{!! nl2br(e($ticket->message)) !!}</div>
    </td></tr>
    <tr><td style="padding:8px 32px 28px;">
        <a href="{{ route('seller.ticket.get') }}" style="background:#6366f1;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;display:inline-block;">View your tickets</a>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
