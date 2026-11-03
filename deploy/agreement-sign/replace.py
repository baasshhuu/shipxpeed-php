#!/usr/bin/env python3
"""Replace acceptAgreement() in Dashboard.php with the Aadhaar (DigiLocker) signing version."""
import re, sys
target, newfn = sys.argv[1], sys.argv[2]
L = open(target, encoding='utf-8').read().split('\n')
s0 = next((i for i, l in enumerate(L) if re.search(r'public function acceptAgreement\(', l)), None)
if s0 is None: sys.exit('FAIL: acceptAgreement not found')
depth, started, e = 0, False, None
for i in range(s0, len(L)):
    depth += L[i].count('{') - L[i].count('}')
    if '{' in L[i]: started = True
    if started and depth == 0: e = i; break
if e is None: sys.exit('FAIL: end of acceptAgreement not found')
if 'CashfreeController::sessionKey' in '\n'.join(L[s0:e+1]):
    print('already up to date'); sys.exit(0)
L[s0:e+1] = open(newfn, encoding='utf-8').read().rstrip('\n').split('\n')
open(target, 'w', encoding='utf-8').write('\n'.join(L))
print('acceptAgreement replaced')
