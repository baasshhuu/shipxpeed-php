<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Delivery update{{ isset($brand) ? ' · ' . $brand : '' }}</title>
<style>
    :root { --bg:#f4f6f9; --card:#fff; --text:#1d2433; --muted:#667085; --line:#e3e7ee; --accent:#128c4b; --accent-50:rgba(18,140,75,.10); --danger:#b3261e; --warn-bg:#fff4e5; --warn:#8a5300; }
    @media (prefers-color-scheme: dark) { :root { --bg:#0f141c; --card:#171e29; --text:#e7ebf2; --muted:#98a2b3; --line:#2a3342; --accent:#3ccf7f; --accent-50:rgba(60,207,127,.12); --danger:#ff8a80; --warn-bg:#2d2414; --warn:#f3c26b; } }
    * { box-sizing: border-box; }
    body { margin:0; background:var(--bg); color:var(--text); font:15px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; }
    .wrap { max-width:520px; margin:0 auto; padding:20px 16px 40px; }
    .card { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:20px; margin-bottom:14px; }
    h1 { font-size:1.25rem; margin:0 0 4px; } .muted { color:var(--muted); font-size:.88rem; }
    .meta { display:grid; grid-template-columns:auto 1fr; gap:4px 12px; font-size:.88rem; margin-top:12px; }
    .meta dt { color:var(--muted); } .meta dd { margin:0; word-break:break-word; }
    .opt { display:block; border:1px solid var(--line); border-radius:12px; padding:14px; margin-bottom:10px; cursor:pointer; }
    .opt:has(input:checked) { border-color:var(--accent); background:var(--accent-50); }
    .opt-h { display:block; cursor:pointer; }
    .opt input[type=radio] { margin-right:8px; accent-color:var(--accent); }
    .opt strong { font-size:.98rem; } .opt .muted { display:block; margin:2px 0 0 24px; }
    .more { display:none; margin:12px 0 0 24px; }
    .opt:has(input:checked) .more { display:block; }
    label.f { display:block; font-size:.82rem; color:var(--muted); margin:8px 0 4px; }
    input.i, textarea.i { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:10px; background:var(--bg); color:var(--text); font:inherit; }
    textarea.i { min-height:76px; resize:vertical; }
    input.i:focus, textarea.i:focus { outline:2px solid var(--accent); outline-offset:1px; }
    .btn { width:100%; padding:13px; border:0; border-radius:12px; background:var(--accent); color:#fff; font-family:inherit; font-size:1rem; font-weight:600; cursor:pointer; margin-top:6px; }
    .btn:disabled { opacity:.6; }
    .err { color:var(--danger); font-size:.85rem; margin-top:4px; }
    .box { padding:12px 14px; border-radius:10px; background:var(--warn-bg); color:var(--warn); font-size:.9rem; margin-top:12px; }
    .ok-ico { width:48px; height:48px; border-radius:50%; background:var(--accent-50); color:var(--accent); display:grid; place-items:center; margin-bottom:10px; }
    a { color:var(--accent); }
    .foot { text-align:center; color:var(--muted); font-size:.78rem; margin-top:18px; }
</style>
</head>
<body>
<div class="wrap">
@if ($state === 'invalid')
    <div class="card"><h1>Link not valid</h1><p class="muted">This link is incorrect or no longer available. Please contact the store you ordered from.</p></div>
@else
    <div class="card">
        <div class="muted">Your order from <strong>{{ $brand }}</strong></div>
        @if ($state === 'done')
            <div class="ok-ico" style="margin-top:12px"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg></div>
            <h1>Thanks, we've got your request</h1>
            <p class="muted" style="margin:0">
                @switch($row->action)
                    @case('re-attempt') We've asked {{ $courier }} to deliver again{{ $row->reattempt_date ? ' on ' . \Illuminate\Support\Carbon::parse($row->reattempt_date)->format('D, d M') : '' }}. @break
                    @case('change-address') We've passed your new address to {{ $courier }} for the next delivery attempt. @break
                    @case('change-phone') We've passed your new phone number to {{ $courier }} for the next delivery attempt. @break
                    @case('rto') We'll return this parcel to {{ $brand }}. @break
                @endswitch
                You'll get a WhatsApp update when it moves.
            </p>
        @elseif ($state === 'expired')
            <h1>This link has expired</h1>
            <p class="muted">Please contact {{ $brand }} about your delivery.</p>
        @elseif ($state === 'closed')
            <h1>No action needed</h1>
            <p class="muted">This shipment has already moved on. <a href="{{ $trackUrl }}">Track it here</a>.</p>
        @else
            <h1>We couldn't deliver your order</h1>
            <p class="muted" style="margin:0">{{ $courier }} tried to deliver but couldn't. Tell us what to do next.</p>
        @endif
        <dl class="meta">
            <dt>Order</dt><dd>{{ $orderNo }}</dd>
            <dt>AWB</dt><dd>{{ $awb }}</dd>
            @if ($cod)<dt>Pay on delivery</dt><dd>₹{{ number_format((float) $cod, 2) }}</dd>@endif
        </dl>
    </div>

    @if ($state === 'open')
    <form class="card" method="POST" action="{{ route('ndr.respond.store', $token) }}" id="ndrForm">
        @csrf
        @php $act = old('action', 're-attempt'); @endphp

        <div class="opt">
            <label class="opt-h"><input type="radio" name="action" value="re-attempt" @checked($act === 're-attempt')><strong>Deliver again</strong>
            <span class="muted">Same address: {{ \Illuminate\Support\Str::limit($address, 70) }}</span></label>
            <div class="more">
                <label class="f" for="d1">Preferred delivery date</label>
                <input class="i" type="date" id="d1" name="reattempt_date" min="{{ $minDate }}" max="{{ $maxDate }}" value="{{ old('reattempt_date', $minDate) }}">
            </div>
        </div>

        <div class="opt">
            <label class="opt-h"><input type="radio" name="action" value="change-address" @checked($act === 'change-address')><strong>Deliver to a corrected address</strong>
            <span class="muted">Same city and pincode only.</span></label>
            <div class="more">
                <label class="f" for="addr">Full address (house no., street, area)</label>
                <textarea class="i" id="addr" name="address" maxlength="300">{{ old('address') }}</textarea>
                @error('address')<div class="err">{{ $message }}</div>@enderror
                <label class="f" for="lm">Landmark (optional)</label>
                <input class="i" id="lm" name="landmark" maxlength="120" value="{{ old('landmark') }}">
            </div>
        </div>

        <div class="opt">
            <label class="opt-h"><input type="radio" name="action" value="change-phone" @checked($act === 'change-phone')><strong>Use a different phone number</strong>
            <span class="muted">Current: {{ $phoneMask }}</span></label>
            <div class="more">
                <label class="f" for="ph">Phone the delivery agent should call</label>
                <input class="i" id="ph" name="phone" type="tel" inputmode="numeric" maxlength="10" placeholder="10-digit mobile" value="{{ old('phone') }}">
                @error('phone')<div class="err">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="opt">
            <label class="opt-h"><input type="radio" name="action" value="rto" @checked($act === 'rto')><strong>I don't want this order</strong>
            <span class="muted">The parcel goes back to {{ $brand }}.</span></label>
        </div>

        <label class="f" for="rm">Note for the delivery agent (optional)</label>
        <input class="i" id="rm" name="remark" maxlength="200" placeholder="e.g. Call before coming, available after 6 pm" value="{{ old('remark') }}">
        @if ($errors->any() && !$errors->has('address') && !$errors->has('phone'))<div class="err">{{ $errors->first() }}</div>@endif

        <button class="btn" id="go" type="submit" style="margin-top:14px">Submit</button>
    </form>
    <script>
        document.getElementById('ndrForm').addEventListener('submit', function () { var b = document.getElementById('go'); b.disabled = true; b.textContent = 'Sending…'; });
    </script>
    @endif
@endif
    <p class="foot">Secure delivery link · Shipxpeed</p>
</div>
</body>
</html>
