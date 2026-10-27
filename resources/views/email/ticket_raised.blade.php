<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>New support ticket</title></head>
<body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#f4f6f8;">
<table align="center" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
    <tr><td style="background:#111827;padding:20px 32px;color:#ffffff;font-size:18px;font-weight:bold;">
        Shipxpeed &middot; New support ticket
    </td></tr>
    <tr><td style="padding:24px 32px 8px;">
        <p style="margin:0 0 16px;color:#333;font-size:15px;">A seller has raised a new ticket.</p>
        <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px;color:#333;">
            <tr style="background:#f7f8fa;"><td width="35%"><b>Ticket no.</b></td><td>{{ $ticket->ticket_id }}</td></tr>
            <tr><td><b>Subject</b></td><td>{{ $ticket->subject ?: '—' }}</td></tr>
            <tr style="background:#f7f8fa;"><td><b>AWB / Order ID</b></td><td>{{ $ticket->order_id }}</td></tr>
            <tr><td><b>Seller</b></td><td>{{ $seller->name ?? '—' }} (ID {{ $ticket->seller_id }})</td></tr>
            <tr style="background:#f7f8fa;"><td><b>Seller email</b></td><td>{{ $seller->email ?? '—' }}</td></tr>
            <tr><td><b>Seller phone</b></td><td>{{ $seller->phone_number ?? '—' }}</td></tr>
            <tr style="background:#f7f8fa;"><td><b>Raised at</b></td><td>{{ $ticket->created_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</td></tr>
        </table>
    </td></tr>
    <tr><td style="padding:16px 32px;">
        <p style="margin:0 0 6px;font-weight:bold;color:#333;font-size:14px;">Message</p>
        <div style="background:#f7f8fa;border-left:4px solid #6366f1;padding:12px 14px;color:#333;font-size:14px;line-height:1.6;">{!! nl2br(e($ticket->message)) !!}</div>
    </td></tr>
    <tr><td style="padding:8px 32px 28px;">
        <a href="{{ route('ticket') }}" style="background:#6366f1;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;display:inline-block;">Open in admin panel</a>
        <p style="margin:14px 0 0;color:#888;font-size:12px;">Replying to this email goes straight to the seller.</p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
