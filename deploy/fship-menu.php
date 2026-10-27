<?php
/* Moves the Fship Integration link from the admin sidebar into the Logistics Partners page.
 * Usage: php deploy/fship-menu.php <app dir>   (works on files with local edits; idempotent) */
chdir($argv[1] ?? '.');
$sb = 'resources/views/partial/sidebar.blade.php';
$lp = 'resources/views/logistics/index.blade.php';

// 1. sidebar: drop the Fship menu item, keep Logistics Partners highlighted on the Fship page
if (is_file($sb)) {
    $s = file_get_contents($sb); $o = $s;
    $s = preg_replace('~[ \t]*@if\s*\(\s*\\\\?(Illuminate\\\\Support\\\\Facades\\\\)?Route::has\(\'admin\.fship\.index\'\)\s*\)\s*\n\s*<li[^>]*admin\.fship\.index.*?</li>\s*\n\s*@endif[ \t]*\n~s', '', $s);
    $s = preg_replace('~[ \t]*<li[^>]*@routeis\(\'admin\.fship\.index\'\).*?</li>[ \t]*\n~s', '', $s);
    $s = str_replace("<li class=\"menu @routeis('logistics') active @endrouteis\">",
        "<li class=\"menu {{ request()->routeIs('logistics', 'admin.fship.*') ? 'active' : '' }}\">", $s);
    if ($s !== $o) { file_put_contents($sb, $s); echo "OK   sidebar: Fship link removed\n"; } else echo "OK   sidebar: nothing to change\n";
    if (strpos($s, 'admin.fship.index') !== false) echo "WARN sidebar: a Fship link is still present\n";
}

// 2. Logistics Partners page: Fship button in the header + Settings button on the Fship row
if (!is_file($lp)) { echo "FAIL $lp missing\n"; exit(2); }
$s = file_get_contents($lp); $o = $s;
if (strpos($s, 'admin.fship.index') === false) {
    $btn = "                        @if (Route::has('admin.fship.index'))\n"
         . "                            <a href=\"{{ route('admin.fship.index') }}\" class=\"btn btn-outline-primary me-2\">\n"
         . "                                <i class=\"fa-solid fa-plug me-1\"></i> Fship Settings\n"
         . "                            </a>\n"
         . "                        @endif\n";
    $p = strpos($s, "@if (Helper::userCan(104, 'can_add'))");
    if ($p === false) $p = strpos($s, "route('logistics.add')");
    if ($p === false) { echo "FAIL logistics page: header anchor not found\n"; exit(2); }
    $ls = strrpos(substr($s, 0, $p), "\n") + 1;
    $s = substr($s, 0, $ls) . $btn . substr($s, $ls);

    $row = "                @if (Route::has('admin.fship.index') && (\$brand->code ?? null) === 'fship')\n"
         . "                    <a href=\"{{ route('admin.fship.index') }}\" class=\"btn btn-sm btn-primary\">Settings</a>\n"
         . "                @endif\n";
    $p = strpos($s, "route('logistics.delete'");
    if ($p !== false) { $ls = strrpos(substr($s, 0, $p), "\n") + 1; $s = substr($s, 0, $ls) . $row . substr($s, $ls); }
    else echo "WARN logistics page: row button not added (header button is there)\n";
    file_put_contents($lp, $s); echo "OK   Logistics Partners: Fship Settings added\n";
} else echo "OK   Logistics Partners: already has Fship\n";
