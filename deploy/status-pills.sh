#!/usr/bin/env bash
# Status filter pills on every order page: size to their text and wrap instead of squeezing,
# so icon, label and count never overlap. Adds one style block to the shared count partial
# (keeps whatever else the live file has). Re-runnable; backs up and restores on failure.
# Usage: cd /var/www/shipxpeed && git fetch -q origin && git show origin/main:deploy/status-pills.sh | bash
set -uo pipefail
cd /var/www/shipxpeed || { echo "FAIL: /var/www/shipxpeed not found"; exit 1; }
F=resources/views/sellerdashboard/partials/status-count.blade.php
[ -f "$F" ] || { echo "FAIL: $F missing"; exit 1; }
rm -f "$F.rej" "$F.orig"
if grep -q "status-pills-v2" "$F"; then echo "already in file"; else
  cp -p "$F" "/root/pre-status-pills-$(date +%Y%m%d-%H%M%S).blade.php"
  cat >> "$F" <<'CSS'

@once
<style>
    /* status-pills-v2: one consistent pill row on every order page - pills size to their text and
       wrap to the next line instead of squeezing (icon, label and count never overlap). */
    .status-filter-row .status-btns-container,
    .status-filter-row > .d-flex.flex-wrap {
        display: flex !important; flex-wrap: wrap !important; gap: 8px !important;
        height: auto !important; grid-template-columns: none !important;
    }
    .status-filter-row .status-btns-container > .status-btn-premium.btn,
    .status-filter-row > .d-flex.flex-wrap > .btn {
        flex: 0 0 auto !important; width: auto !important; max-width: none !important; min-width: 0 !important;
        height: 34px !important; padding: 0 !important; margin: 0 !important;
        display: inline-flex !important; align-items: center !important;
        border: 1px solid var(--sx-line-2, rgba(196,206,235,.16)) !important; border-radius: 999px !important;
        background: var(--sx-raised, #152036) !important; color: var(--sx-fg-2, #a3abc2) !important;
        font-size: 13px !important; font-weight: 500 !important; line-height: 1 !important;
        box-shadow: none !important; transform: none !important; white-space: nowrap !important;
    }
    .status-filter-row > .d-flex.flex-wrap > .btn { padding: 0 14px !important; gap: 7px; }
    .status-filter-row .status-btns-container > .status-btn-premium.btn:hover,
    .status-filter-row > .d-flex.flex-wrap > .btn:hover {
        background: var(--sx-hover, #1a2742) !important; color: var(--sx-fg, #e9ecf5) !important;
        border-color: var(--sx-blue, #3b7bff) !important;
    }
    .status-filter-row .status-btns-container > .status-btn-premium.btn.active {
        background: var(--sx-blue-bg, rgba(59,123,255,.14)) !important; color: var(--sx-blue-2, #6b9cff) !important;
        border-color: var(--sx-blue, #3b7bff) !important;
    }
    .status-filter-row .status-btn-content {
        display: inline-flex !important; align-items: center !important; gap: 7px !important;
        height: 34px !important; width: auto !important; padding: 0 14px !important;
        font-size: 13px !important; white-space: nowrap !important; line-height: 1 !important;
    }
    .status-filter-row .status-btn-content > i,
    .status-filter-row > .d-flex.flex-wrap > .btn > i {
        position: static !important; margin: 0 !important; font-size: 12px !important; flex: 0 0 auto;
    }
    .status-filter-row .status-count {
        position: static !important; margin-left: 0 !important; flex: 0 0 auto;
        min-width: 20px !important; height: 18px !important; padding: 0 6px !important; font-size: 11px !important;
    }
    @media (max-width: 575.98px) {
        .status-filter-row .status-btns-container > .status-btn-premium.btn,
        .status-filter-row > .d-flex.flex-wrap > .btn { height: 32px !important; }
        .status-filter-row .status-btn-content { height: 32px !important; padding: 0 11px !important; font-size: 12.5px !important; }
    }
</style>
@endonce
CSS
fi
php artisan view:clear >/dev/null 2>&1
php artisan view:cache >/dev/null 2>&1 || { echo "FAIL: view compile"; exit 1; }
echo "STATUS PILLS FIX LIVE ($(grep -c status-pills-v2 "$F")) - reload any order page"
