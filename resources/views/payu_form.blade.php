<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to PayU...</title>
</head>
<body onload="document.forms['payuForm'].submit();">
    <h3>Redirecting to PayU, please wait...</h3>

    <form action="{{ $PAYU_BASE_URL }}/_payment" method="post" name="payuForm">
        <input type="hidden" name="key" value="{{ $MERCHANT_KEY }}">
        <input type="hidden" name="txnid" value="{{ $txnid }}">
        <input type="hidden" name="amount" value="{{ $amount }}">
        <input type="hidden" name="productinfo" value="{{ $productinfo }}">
        <input type="hidden" name="firstname" value="{{ $firstname }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="phone" value="{{ $phone }}">
        <input type="hidden" name="surl" value="{{ $successUrl }}">
        <input type="hidden" name="furl" value="{{ $failureUrl }}">
        <input type="hidden" name="hash" value="{{ $hash }}">
        <input type="submit" value="Pay Now">
    </form>

    <p>If you are not redirected automatically, <button onclick="document.forms['payuForm'].submit();">click here</button>.</p>
</body>
</html>

<script>
    window.onload = function() {
        document.forms['payuForm'].submit();
    }
</script>





{{-- 
<form action="{{ $PAYU_BASE_URL }}/_payment" method="post" name="payuForm">
    <input type="hidden" name="key" value="{{ $MERCHANT_KEY }}">
    <input type="hidden" name="txnid" value="{{ $txnid }}">
    <input type="hidden" name="amount" value="{{ $amount }}">
    <input type="hidden" name="productinfo" value="{{ $productinfo }}">
    <input type="hidden" name="firstname" value="{{ $firstname }}">
    <input type="hidden" name="email" value="{{ $email }}">
    <input type="hidden" name="phone" value="{{ $phone }}">
    <input type="hidden" name="surl" value="{{ $successUrl }}">
    <input type="hidden" name="furl" value="{{ $failureUrl }}">
    <input type="hidden" name="hash" value="{{ $hash }}">
    <input type="submit" value="Pay Now">
</form>
 --}}
