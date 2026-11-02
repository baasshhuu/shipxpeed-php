@extends('layouts.sellerdash')

@section('content')
@php
    use App\Support\Xpeed\Kit;
    $tabs = [
        'eligible' => ['Delivered · create return', 'ti ti-package-import', 'Forward orders delivered in the last 90 days'],
        'pending'  => ['To be picked up', 'ti ti-clock-hour-4', 'Returns waiting for a courier'],
        'transit'  => ['In progress', 'ti ti-truck-return', 'Pickup booked or on the way back'],
        'received' => ['Received', 'ti ti-circle-check', 'Delivered back to your warehouse'],
        'cancelled'=> ['Cancelled', 'ti ti-circle-x', 'Cancelled returns'],
    ];
    $q = request('q');
@endphp
<div class="pc-container rv">
<div class="pc-content">
<style>
    .rv { --rv-bg: var(--sx-surface, #fff); --rv-raised: var(--sx-raised, #f6f8fb); --rv-line: var(--sx-line, rgba(20,30,60,.09)); --rv-line2: var(--sx-line-2, rgba(20,30,60,.16));
          --rv-fg: var(--sx-fg, #141a2b); --rv-fg2: var(--sx-fg-2, #4d576e); --rv-fg3: var(--sx-fg-3, #7b849a); --rv-blue: var(--sx-blue, #3b7bff); --rv-blue-bg: var(--sx-blue-bg, rgba(59,123,255,.1));
          --rv-ok: #22a565; --rv-ok-bg: rgba(34,165,101,.13); --rv-warn: #d48a12; --rv-warn-bg: rgba(212,138,18,.14); --rv-bad: #e2493f; --rv-bad-bg: rgba(226,73,63,.13); color: var(--rv-fg); }
    .rv .rv-top { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
    .rv .rv-top h1 { font-size:1.35rem; font-weight:700; margin:0; display:flex; align-items:center; gap:10px; color:var(--rv-fg); }
    .rv .rv-top h1 i { color:var(--rv-blue); }
    .rv .rv-top p { margin:4px 0 0; color:var(--rv-fg2); font-size:.86rem; max-width:640px; }
    .rv .rv-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:9px; font-size:.84rem; font-weight:600; border:1px solid var(--rv-line2); background:var(--rv-raised); color:var(--rv-fg); text-decoration:none; cursor:pointer; white-space:nowrap; }
    .rv .rv-btn:hover { border-color:var(--rv-blue); color:var(--rv-fg); }
    .rv .rv-btn.pri, .rv a.rv-btn.pri, .rv a.rv-btn.pri:visited { background:var(--rv-blue); border-color:var(--rv-blue); color:#fff !important; }
    .rv .rv-btn.pri:hover { filter:brightness(1.08); color:#fff !important; }
    .rv a.rv-btn:not(.pri), .rv a.rv-stat { color:var(--rv-fg) !important; }
    .rv .rv-btn.sm { padding:5px 10px; font-size:.78rem; border-radius:7px; }
    .rv .rv-btn.danger:hover { border-color:var(--rv-bad); color:var(--rv-bad); }
    .rv .rv-btn[disabled] { opacity:.55; pointer-events:none; }
    .rv .rv-stats { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:10px; margin-bottom:16px; }
    .rv .rv-stat { background:var(--rv-bg); border:1px solid var(--rv-line); border-radius:12px; padding:12px 14px; text-decoration:none; color:inherit; transition:border-color .15s; }
    .rv .rv-stat:hover { border-color:var(--rv-line2); color:inherit; }
    .rv .rv-stat.on { border-color:var(--rv-blue); box-shadow:inset 0 0 0 1px var(--rv-blue); }
    .rv .rv-stat .l { display:flex; align-items:center; gap:6px; font-size:.75rem; color:var(--rv-fg3); font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
    .rv .rv-stat .n { font-size:1.55rem; font-weight:700; margin-top:4px; line-height:1.1; }
    .rv .rv-stat .s { font-size:.74rem; color:var(--rv-fg3); margin-top:2px; }
    .rv .rv-card { background:var(--rv-bg); border:1px solid var(--rv-line); border-radius:14px; overflow:hidden; }
    .rv .rv-bar { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; padding:12px 14px; border-bottom:1px solid var(--rv-line); }
    .rv .rv-bar .grp { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .rv .rv-input { background:var(--rv-raised); border:1px solid var(--rv-line2); color:var(--rv-fg); border-radius:8px; padding:7px 11px; font-size:.84rem; }
    .rv .rv-input:focus { outline:none; border-color:var(--rv-blue); }
    .rv .rv-sel-count { font-size:.8rem; color:var(--rv-fg2); }
    .rv .rv-wrap { overflow-x:auto; }
    .rv table { width:100%; border-collapse:collapse; font-size:.84rem; }
    .rv th { text-align:left; font-size:.72rem; letter-spacing:.05em; text-transform:uppercase; color:var(--rv-fg3); font-weight:600; padding:10px 14px; border-bottom:1px solid var(--rv-line); white-space:nowrap; }
    .rv td { padding:12px 14px; border-bottom:1px solid var(--rv-line); vertical-align:middle; }
    .rv tr:last-child td { border-bottom:0; }
    .rv tbody tr:hover td { background:var(--rv-raised); }
    .rv .mono { font-family:var(--sx-mono, ui-monospace, Menlo, monospace); font-size:.8rem; }
    .rv .sub { color:var(--rv-fg3); font-size:.76rem; margin-top:2px; }
    .rv .pill { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:.72rem; font-weight:600; white-space:nowrap; }
    .rv .pill.ok { background:var(--rv-ok-bg); color:var(--rv-ok); } .rv .pill.warn { background:var(--rv-warn-bg); color:var(--rv-warn); }
    .rv .pill.bad { background:var(--rv-bad-bg); color:var(--rv-bad); } .rv .pill.info { background:var(--rv-blue-bg); color:var(--rv-blue); }
    .rv .pill.mut { background:var(--rv-raised); color:var(--rv-fg2); border:1px solid var(--rv-line); }
    .rv .acts { display:flex; gap:6px; justify-content:flex-end; flex-wrap:wrap; }
    .rv .rv-empty { text-align:center; padding:56px 16px; color:var(--rv-fg2); }
    .rv .rv-empty i { font-size:2.4rem; color:var(--rv-fg3); display:block; margin-bottom:10px; }
    .rv .rv-empty b { display:block; color:var(--rv-fg); font-size:1rem; margin-bottom:4px; }
    .rv .rv-how { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-top:16px; }
    .rv .rv-how div { border:1px dashed var(--rv-line2); border-radius:12px; padding:12px 14px; font-size:.8rem; color:var(--rv-fg2); }
    .rv .rv-how b { display:block; color:var(--rv-fg); font-size:.84rem; margin-bottom:2px; }
    .rv input[type=checkbox] { width:16px; height:16px; accent-color:var(--rv-blue); cursor:pointer; }
    @media (max-width: 991px) { .rv .rv-stats { grid-template-columns:repeat(3, minmax(0,1fr)); } .rv .rv-how { grid-template-columns:1fr; } }
    @media (max-width: 575px) { .rv .rv-stats { grid-template-columns:repeat(2, minmax(0,1fr)); } .rv td, .rv th { padding:10px; } }
</style>

    <div class="rv-top">
        <div>
            <h1><i class="ti ti-arrow-back-up"></i> Reverse orders</h1>
            <p>Bring products back from your buyers. Create a return from any delivered order in one click and book the pickup automatically with Delhivery reverse pickup.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if(Route::has('seller.reverseorderadd'))
                <a href="{{ route('seller.reverseorderadd') }}" class="rv-btn"><i class="ti ti-plus"></i> New return manually</a>
            @endif
            <a href="{{ route('seller.reverse.order', ['tab' => 'eligible']) }}" class="rv-btn pri"><i class="ti ti-package-import"></i> Return a delivered order</a>
        </div>
    </div>

    @unless($hasLinks)
        <div class="alert alert-warning">Returns from delivered orders are being set up on the server. You can still create and ship returns manually.</div>
    @endunless

    <div class="rv-stats">
        @foreach($tabs as $k => [$label, $icon, $hint])
            <a href="{{ route('seller.reverse.order', array_filter(['tab' => $k, 'q' => $q])) }}" class="rv-stat {{ $tab === $k ? 'on' : '' }}">
                <div class="l"><i class="{{ $icon }}"></i> {{ $label }}</div>
                <div class="n">{{ $counts[$k] ?? 0 }}</div>
                <div class="s">{{ $hint }}</div>
            </a>
        @endforeach
    </div>

    <div class="rv-card">
        <div class="rv-bar">
            <form method="GET" action="{{ route('seller.reverse.order') }}" class="grp">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input name="q" value="{{ $q }}" class="rv-input" style="min-width:250px" placeholder="AWB, order no., buyer name or phone">
                <button class="rv-btn sm"><i class="ti ti-search"></i> Search</button>
                @if($q)<a href="{{ route('seller.reverse.order', ['tab' => $tab]) }}" class="rv-btn sm">Clear</a>@endif
            </form>

            @if($tab === 'eligible' && $rows->isNotEmpty())
                <form id="rvBulk" method="POST" action="{{ route('seller.reverse.fromforward') }}" class="grp">
                    @csrf
                    <span class="rv-sel-count"><b class="rv-n">0</b> selected</span>
                    <select name="reason" class="rv-input">
                        @foreach($reasons as $r)<option>{{ $r }}</option>@endforeach
                    </select>
                    <button name="auto" value="0" class="rv-btn sm" onclick="return rvPick('Create returns for')"><i class="ti ti-plus"></i> Create return</button>
                    <button name="auto" value="1" class="rv-btn sm pri" onclick="return rvPick('Create returns and book Delhivery reverse pickup for')"><i class="ti ti-bolt"></i> Create &amp; auto-assign</button>
                </form>
            @elseif($tab === 'pending' && $rows->isNotEmpty())
                <form id="rvBulk" method="POST" action="{{ route('seller.reverse.autoassign') }}" class="grp">
                    @csrf
                    <span class="rv-sel-count"><b class="rv-n">0</b> selected</span>
                    <button class="rv-btn sm pri" onclick="return rvPick('Book Delhivery reverse pickup for')"><i class="ti ti-bolt"></i> Auto-assign selected</button>
                </form>
            @endif
        </div>

        @if($rows->isEmpty())
            <div class="rv-empty">
                @switch($tab)
                    @case('eligible') <i class="ti ti-package-import"></i><b>No delivered orders to return</b>Delivered forward orders from the last 90 days show up here. @break
                    @case('pending') <i class="ti ti-mood-smile"></i><b>Nothing waiting for pickup</b>Create a return from a delivered order and it will appear here. @break
                    @case('transit') <i class="ti ti-truck-return"></i><b>No returns on the way</b>Booked reverse pickups show up here until they reach your warehouse. @break
                    @case('received') <i class="ti ti-circle-check"></i><b>No returns received yet</b>Returns delivered back to your warehouse show up here. @break
                    @default <i class="ti ti-circle-x"></i><b>No cancelled returns</b>
                @endswitch
                @if($tab === 'pending' || $tab === 'eligible')
                <div class="rv-how text-start">
                    <div><b>1 · Pick a delivered order</b>From the "Delivered · create return" tab, with the reason.</div>
                    <div><b>2 · Auto-assign</b>Delhivery picks up from the buyer when their pincode is serviceable. Otherwise choose a courier yourself.</div>
                    <div><b>3 · Track it back</b>Follow the pickup in "In progress" until it is received at your warehouse.</div>
                </div>
                @endif
            </div>
        @else
        <div class="rv-wrap">
            <table>
                <thead><tr>
                    @if(in_array($tab, ['eligible', 'pending']))<th style="width:34px"><input type="checkbox" onclick="document.querySelectorAll('.rv-cb').forEach(c => c.checked = this.checked); rvCount()"></th>@endif
                    <th>{{ $tab === 'eligible' ? 'Delivered order' : 'Return' }}</th>
                    <th>Buyer (pickup from)</th>
                    <th>Return to</th>
                    <th>Package</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr></thead>
                <tbody>
                @foreach($rows as $o)
                    @php
                        $c = Kit::consignee($o); $p = Kit::pickup($o);
                        $m = $meta->get($o->id);
                        $orig = $m ? $origRefs->get($m->original_order_id) : null;
                        $bucket = Kit::bucket($o);
                    @endphp
                    <tr>
                        @if(in_array($tab, ['eligible', 'pending']))
                            <td><input type="checkbox" class="rv-cb" form="rvBulk" name="order_ids[]" value="{{ $o->id }}" onchange="rvCount()"></td>
                        @endif
                        <td>
                            <div class="fw-semibold">#{{ Kit::ref($o) }}</div>
                            @if($o->awb_number)<div class="mono sub">{{ Kit::courierLabel($o) }} · {{ $o->awb_number }}</div>@endif
                            @if($orig)<div class="sub">Return of #{{ Kit::ref($orig) }}{{ $m->reason ? ' · ' . $m->reason : '' }}</div>@endif
                            <div class="sub">{{ $tab === 'eligible' ? 'Delivered ' : 'Created ' }}{{ optional($tab === 'eligible' ? $o->updated_at : $o->created_at)->format('d M Y') }}</div>
                        </td>
                        <td>
                            <div>{{ $c['name'] ?? '—' }}</div>
                            <div class="sub">{{ $c['phone'] ?? '' }}</div>
                            <div class="sub">{{ $c['city'] ?? '' }} {{ $c['pincode'] ?? '' }}</div>
                        </td>
                        <td>
                            <div>{{ $p['warehouse_name'] ?? ($p['name'] ?? '—') }}</div>
                            <div class="sub">{{ $p['city'] ?? '' }} {{ $p['pincode'] ?? '' }}</div>
                        </td>
                        <td>
                            <div>{{ rtrim(rtrim(number_format((float) $o->package_weight, 2), '0'), '.') }} {{ (float) $o->package_weight > 50 ? 'g' : 'kg' }}</div>
                            <div class="sub">{{ (float) $o->package_length }}×{{ (float) $o->package_breadth }}×{{ (float) $o->package_height }} cm</div>
                            @if($tab === 'eligible')<div class="sub">{{ strtoupper($o->payment_type) }} · {{ Kit::money($o->order_amount) }}</div>@endif
                        </td>
                        <td>
                            @if($tab === 'eligible')
                                <span class="pill ok"><i class="ti ti-check"></i> Delivered</span>
                            @elseif($tab === 'pending')
                                @if($m && $m->assign_status === 'failed')
                                    <span class="pill bad">Auto-assign failed</span><div class="sub" style="max-width:220px">{{ \Illuminate\Support\Str::limit($m->assign_message, 90) }}</div>
                                @elseif($m && $m->assign_status === 'manual')
                                    <span class="pill warn">Choose a courier</span><div class="sub" style="max-width:220px">{{ \Illuminate\Support\Str::limit($m->assign_message, 90) }}</div>
                                @else
                                    <span class="pill mut">Awaiting courier</span>
                                @endif
                            @elseif($tab === 'received')
                                <span class="pill ok"><i class="ti ti-circle-check"></i> Received</span>
                            @elseif($tab === 'cancelled')
                                <span class="pill bad">Cancelled</span>
                            @else
                                <span class="pill info">{{ Kit::BUCKET_LABELS[$bucket] ?? ucwords(str_replace('_', ' ', (string) $o->shipping_status)) }}</span>
                                @if($o->shipping_status)<div class="sub">{{ ucwords(str_replace('_', ' ', $o->shipping_status)) }}</div>@endif
                            @endif
                        </td>
                        <td>
                            <div class="acts">
                            @if($tab === 'eligible')
                                <form method="POST" action="{{ route('seller.reverse.fromforward') }}" onsubmit="this.querySelectorAll('button').forEach(b => b.disabled = true)">
                                    @csrf
                                    <input type="hidden" name="order_ids[]" value="{{ $o->id }}">
                                    <input type="hidden" name="reason" value="Customer return">
                                    <button name="auto" value="0" class="rv-btn sm">Create return</button>
                                    <button name="auto" value="1" class="rv-btn sm pri" title="Create the return and book Delhivery reverse pickup"><i class="ti ti-bolt"></i> Auto-assign</button>
                                </form>
                            @elseif($tab === 'pending')
                                <form method="POST" action="{{ route('seller.reverse.autoassign') }}" onsubmit="this.querySelector('button').disabled = true">
                                    @csrf
                                    <input type="hidden" name="order_ids[]" value="{{ $o->id }}">
                                    <button class="rv-btn sm pri" title="Book Delhivery reverse pickup"><i class="ti ti-bolt"></i> Auto-assign</button>
                                </form>
                                @if(Route::has('seller.reverse.courier'))
                                    <a href="{{ route('seller.reverse.courier', $o->id) }}" class="rv-btn sm">Choose courier</a>
                                @endif
                                @if(Route::has('orderdelete'))
                                    <a href="{{ route('orderdelete', $o->id) }}" class="rv-btn sm danger" onclick="return confirm('Delete this return?')" title="Delete"><i class="ti ti-trash"></i></a>
                                @endif
                            @elseif($o->awb_number)
                                <a href="{{ url('/t/' . $o->awb_number) }}" target="_blank" class="rv-btn sm"><i class="ti ti-map-pin"></i> Track</a>
                            @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
</div>
<script>
function rvCount() { var n = document.querySelectorAll('.rv-cb:checked').length; document.querySelectorAll('.rv-n').forEach(function (e) { e.textContent = n; }); }
function rvPick(what) {
    var n = document.querySelectorAll('.rv-cb:checked').length;
    if (!n) { alert('Select at least one order.'); return false; }
    return confirm(what + ' ' + n + ' order(s)?');
}
</script>
@endsection
