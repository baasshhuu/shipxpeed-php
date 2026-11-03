#!/usr/bin/env python3
"""Wallet recharge (Cashfree): ask for the mobile number in the Recharge window when the seller
has none, save it, and show recharge errors inside the window. Patches in place; re-runnable.
Usage: python3 patch.py /var/www/shipxpeed"""
import re, sys, os

ROOT = sys.argv[1] if len(sys.argv) > 1 else '.'
MARK = 'sx-recharge-phone-v1'
done = []


def rd(p):
    return open(os.path.join(ROOT, p), encoding='utf-8').read()


def wr(p, s):
    open(os.path.join(ROOT, p), 'w', encoding='utf-8').write(s)
    done.append(p)


# ---- 1. controller: accept a phone from the form and save it ----
p = 'app/Http/Controllers/selleradmin/WalletRechargeController.php'
s = rd(p)
if MARK not in s:
    old = re.search(r"\n(\s*)if \(strlen\(\$phone\) !== 10\) \{\n\s*return back\(\)->with\('error', 'Please add a valid 10-digit mobile number[^\n]*\n\s*\}", s)
    if not old:
        sys.exit('FAIL: phone check not found in ' + p)
    ind = old.group(1)
    new = f"""
{ind}// {MARK}: take the mobile from the Recharge window when the profile has none, and save it
{ind}if (strlen($phone) !== 10 && $request->filled('phone')) {{
{ind}    $typed = substr(preg_replace('/\\D/', '', (string) $request->input('phone')), -10);
{ind}    if (preg_match('/^[6-9]\\d{{9}}$/', $typed)) {{
{ind}        $phone = $typed;
{ind}        try {{
{ind}            $seller->forceFill(['phone_number' => $typed])->save();
{ind}        }} catch (\\Throwable $e) {{
{ind}            Log::warning('Could not save seller mobile from recharge', ['seller_id' => $seller->id, 'error' => $e->getMessage()]);
{ind}        }}
{ind}    }}
{ind}}}
{ind}if (strlen($phone) !== 10) {{
{ind}    return back()->withInput()->with('error', 'Please enter a valid 10-digit mobile number. Cashfree needs it to process the payment.');
{ind}}}"""
    s = s[:old.start()] + new + s[old.end():]
    # tag errors from start() so only they reopen the Recharge window
    a = s.index('public function start(')
    b = s.find('public function ', a + 10)
    b = len(s) if b < 0 else b
    body = re.sub(r"back\(\)(->withInput\(\))?->with\('error', ([^;]+)\);",
                  lambda m: "back()" + (m.group(1) or '') + "->with('error', " + m.group(2) + ")->with('recharge_error', true);", s[a:b])
    s = s[:a] + body + s[b:]
    wr(p, s)

# ---- 2. recharge window: mobile field + inline error + reopen on error ----
p = 'resources/views/partial/sellerdash/common/footer.blade.php'
s = rd(p)
if MARK not in s:
    m = re.search(r"<form[^>]*action=\"\{\{\s*route\('recharge\.add'\)\s*\}\}\"[^>]*>\s*\n(\s*)@csrf[^\n]*\n", s)
    if not m:
        sys.exit('FAIL: recharge form not found in ' + p)
    ind = m.group(1)
    block = f"""{ind}{{{{-- {MARK} --}}}}
{ind}@php $sxPhone = substr(preg_replace('/\\D/', '', (string) (auth('seller')->user()->phone_number ?? '')), -10); @endphp
{ind}@if (session('recharge_error') && session('error'))
{ind}    <div class="alert alert-danger py-2 small mb-3" id="sxRechargeErr">{{{{ session('error') }}}}</div>
{ind}@endif
{ind}@if (strlen($sxPhone) !== 10)
{ind}    <div class="mb-3">
{ind}        <label for="sxRechargePhone" class="form-label">Mobile number <span class="text-danger">*</span></label>
{ind}        <input type="tel" class="form-control" id="sxRechargePhone" name="phone" value="{{{{ old('phone') }}}}" inputmode="numeric" pattern="[6-9][0-9]{{9}}" maxlength="10" placeholder="10-digit mobile number" required>
{ind}        <div class="form-text">Needed by the payment gateway. Saved to your profile.</div>
{ind}    </div>
{ind}@endif
"""
    s = s[:m.end()] + block + s[m.end():]
    reopen = f"""
{{{{-- {MARK}: reopen the Recharge window when it returned an error --}}}}
<script>
window.addEventListener('load', function () {{
    var err = document.getElementById('sxRechargeErr');
    if (!err || !window.bootstrap) return;
    var modal = err.closest('.modal');
    if (modal) bootstrap.Modal.getOrCreateInstance(modal).show();
}});
</script>
"""
    s = s.rstrip('\n') + '\n' + reopen
    wr(p, s)

print('\n'.join('updated ' + d for d in done) or 'already up to date')
print('PATCH OK')
