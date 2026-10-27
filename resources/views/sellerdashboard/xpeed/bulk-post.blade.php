<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Preparing {{ $what }}…</title>
<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#11161f;color:#dfe5f0}</style></head>
<body>
<form id="f" method="POST" action="{{ $action }}">
    @csrf
    @foreach($ids as $id)<input type="hidden" name="order_ids[]" value="{{ $id }}">@endforeach
    <noscript><button>Open {{ $what }}</button></noscript>
</form>
<p>Preparing {{ $what }}…</p>
<script>document.getElementById('f').submit();</script>
</body>
</html>
