<?php
/**
 * Removes unused items from the admin sidebar (menu only; pages and code stay, so this is reversible).
 * Run from the app root. Idempotent.
 */
$f = 'resources/views/partial/sidebar.blade.php';
$s = file_get_contents($f);
$orig = $s;

/** Remove the <li> ... </li> (with nested lists) that contains $needle. Returns true when removed. */
function removeLi(string &$s, string $needle): bool
{
    $p = strpos($s, $needle);
    if ($p === false) {
        return false;
    }
    // nearest <li before the needle that is not closed before it
    $start = $p;
    do {
        $start = strrpos(substr($s, 0, $start), '<li');
        if ($start === false) {
            return false;
        }
        $between = substr($s, $start, $p - $start);
    } while (substr_count($between, '<li') <= substr_count($between, '</li>'));

    // matching </li>
    $depth = 0;
    $i = $start;
    while (preg_match('/<li\b|<\/li>/', $s, $m, PREG_OFFSET_CAPTURE, $i)) {
        [$tag, $pos] = $m[0];
        $depth += $tag === '</li>' ? -1 : 1;
        $i = $pos + strlen($tag);
        if ($depth === 0) {
            // take the whole lines
            $ls = strrpos(substr($s, 0, $start), "\n");
            $ls = $ls === false ? $start : $ls + 1;
            $le = strpos($s, "\n", $i);
            $le = $le === false ? strlen($s) : $le + 1;
            $s = substr($s, 0, $ls) . substr($s, $le);
            return true;
        }
    }
    return false;
}

$remove = [
    // Couriers & Rates (replaced by Rate Manager / Courier Status)
    "route('courier.rate.manager')" => 'Courier & Rate Manager',
    "route('courier.master.index')" => 'Courier Master',
    "route('pricesetting.add')" => 'Price Setting',
    "route('zone.pricesetting.add')" => 'Zone Price Setting',
    // Website
    "route('landing-brands')" => 'Landing Brands',
    "route('team-spirit')" => 'Team Spirit',
    "route('beyond-work')" => 'Beyond Work',
    'href="#static_content"' => 'Content',
    // Sellers & Support
    "route('SendWhatsApp.index')" => 'WhatsApp Messages',
    "route('careers-details')" => 'Career Details',
    'href="#location_content"' => 'Location',
];
foreach ($remove as $needle => $label) {
    echo (removeLi($s, $needle) ? 'removed: ' : 'not in menu: ') . $label . "\n";
}

// "Active Slabs" is the Courier Status page
if (strpos($s, '<span>Active Slabs</span>') !== false) {
    $s = str_replace('<span>Active Slabs</span>', '<span>Courier Status</span>', $s);
    echo "renamed: Active Slabs -> Courier Status\n";
}

// Drop permission blocks left empty by the removals
$s = preg_replace('/\n[ \t]*@if\(Helper::userCan\(\[[0-9, ]*\]\)\)\s*@endif[ \t]*(?=\n)/', '', $s);

// Drop the "Website" heading when nothing is left under it
if (preg_match('/\n[ \t]*<li class="sx-menu-heading">\s*Website\s*<\/li>[ \t]*\n(\s*)(<li class="sx-menu-heading"|<\/ul>)/', $s)) {
    $s = preg_replace('/\n[ \t]*<li class="sx-menu-heading">\s*Website\s*<\/li>[ \t]*(?=\n\s*(<li class="sx-menu-heading"|<\/ul>))/', '', $s, 1);
    echo "removed: empty Website heading\n";
}

// Top bar "Couriers & Rates" button -> Rate Manager
$lf = 'resources/views/layouts/app.blade.php';
if (file_exists($lf)) {
    $l = file_get_contents($lf);
    if (strpos($l, "route('courier.rate.manager')") !== false) {
        file_put_contents($lf, str_replace("route('courier.rate.manager')", "route('rate.manager')", $l));
        echo "top bar: Couriers & Rates -> Rate Manager\n";
    }
}

if ($s !== $orig) {
    file_put_contents($f, $s);
    echo "saved: $f\n";
} else {
    echo "nothing to change\n";
}
