<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Secure Payment · Shipxpeed</title>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        :root { color-scheme: dark; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
               background:#0f1420; color:#e6e9f0; font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; }
        .card { background:#171e2e; border:1px solid #26304a; border-radius:14px; padding:32px 28px;
                width:min(380px, calc(100% - 32px)); text-align:center; }
        .amt { font-size:28px; font-weight:700; margin:6px 0 18px; }
        .muted { color:#8b95ab; font-size:14px; }
        .spinner { width:34px; height:34px; border:3px solid #2c3754; border-top-color:#4f8cff; border-radius:50%;
                   margin:0 auto 16px; animation:spin .8s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        button, a.btn { display:inline-block; margin-top:18px; padding:10px 18px; border-radius:8px; border:0;
                        background:#4f8cff; color:#fff; font-weight:600; cursor:pointer; text-decoration:none; font-size:14px; }
        a.link { display:block; margin-top:12px; color:#8b95ab; font-size:13px; }
        #err { color:#ff7a7a; font-size:14px; margin-top:12px; display:none; }
    </style>
</head>
<body>
<div class="card">
    <div class="spinner" id="spin"></div>
    <div class="muted">Wallet recharge</div>
    <div class="amt">₹{{ number_format($amount, 2) }}</div>
    <div class="muted" id="msg">Opening Cashfree secure checkout…</div>
    <div id="err"></div>
    <button type="button" id="retry" style="display:none">Pay now</button>
    <a class="link" href="{{ route('seller.dashboard') }}">Cancel and go back</a>
</div>

<script>
    (function () {
        var sessionId = @json($paymentSessionId);
        var mode = @json($mode);

        function pay() {
            try {
                var cashfree = Cashfree({ mode: mode });
                cashfree.checkout({ paymentSessionId: sessionId, redirectTarget: "_self" });
            } catch (e) {
                document.getElementById('spin').style.display = 'none';
                document.getElementById('msg').style.display = 'none';
                var err = document.getElementById('err');
                err.textContent = 'Could not open the payment window. Please try again.';
                err.style.display = 'block';
                document.getElementById('retry').style.display = 'inline-block';
            }
        }

        document.getElementById('retry').addEventListener('click', pay);
        window.addEventListener('load', pay);
    })();
</script>
</body>
</html>
