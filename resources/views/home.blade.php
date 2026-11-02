@extends('layouts.app')

@section('content')
@php
    $kpis = [
        ['label' => "Today's Orders", 'value' => number_format($todayOrder), 'sub' => number_format($weekOrder) . ' in last 7 days', 'icon' => 'fa-solid fa-calendar-day', 'tone' => 'blue', 'route' => route('get.orders')],
        ['label' => 'Shipments (AWB)', 'value' => number_format($allOrder), 'sub' => number_format($monthOrder) . ' orders this month', 'icon' => 'fa-solid fa-truck-fast', 'tone' => 'green', 'route' => route('shipment.report')],
        ['label' => 'Wallet Recharges', 'value' => '₹' . number_format($sellerRechargeAmount, 2), 'sub' => 'Total credited', 'icon' => 'fa-solid fa-wallet', 'tone' => 'amber', 'route' => Route::has('recharges') ? route('recharges') : '#'],
        ['label' => 'Cancelled Orders', 'value' => number_format($canceledOrder), 'sub' => 'All time', 'icon' => 'fa-solid fa-ban', 'tone' => 'red', 'route' => route('get.orders')],
        ['label' => 'Active Sellers', 'value' => number_format($activeUser), 'sub' => number_format($inactiveUser) . ' inactive', 'icon' => 'fa-solid fa-user-check', 'tone' => 'green', 'route' => route('seller-list', ['filter' => 'active'])],
        ['label' => 'KYC Pending', 'value' => number_format($kycPending), 'sub' => number_format($approveKyc) . ' approved', 'icon' => 'fa-solid fa-id-card', 'tone' => 'amber', 'route' => route('seller-list', ['filter' => 'kyc_pending'])],
        ['label' => 'New Sign-ups', 'value' => number_format($todayRegister), 'sub' => number_format($weekRegister) . ' this week · ' . number_format($allRegister) . ' total', 'icon' => 'fa-solid fa-user-plus', 'tone' => 'blue', 'route' => route('seller-list')],
        ['label' => 'Open Tickets', 'value' => number_format($openTicket), 'sub' => number_format($pendingTicket) . ' pending · ' . number_format($closeTicket) . ' closed', 'icon' => 'fa-solid fa-ticket', 'tone' => 'red', 'route' => Route::has('ticket') ? route('ticket') : '#'],
    ];

    $quick = [
        ['t' => 'Manage sellers', 'd' => 'Edit details, KYC, status, balances', 'i' => 'fa-solid fa-users', 'r' => route('seller-list')],
        ['t' => 'Courier & rates', 'd' => 'Assign couriers and rate slabs to sellers', 'i' => 'fa-solid fa-truck-fast', 'r' => route('courier.rate.manager')],
        ['t' => 'Zone pricing', 'd' => 'Zone-wise prices sellers see on their rate card', 'i' => 'fa-solid fa-map-location-dot', 'r' => route('zone.price.index')],
        ['t' => 'COD remittance', 'd' => 'Record COD payouts to sellers', 'i' => 'fa-solid fa-indian-rupee-sign', 'r' => route('codremittance.index')],
        ['t' => 'Weight disputes', 'd' => 'Apply weight discrepancy charges', 'i' => 'fa-solid fa-weight-scale', 'r' => route('weight.dispatching.index')],
        ['t' => 'Seller invoices', 'd' => 'Generate monthly seller invoices', 'i' => 'fa-solid fa-file-invoice', 'r' => route('invoices.add')],
    ];
@endphp

<div class="sx-page-head">
    <div>
        <h2>Dashboard</h2>
        <p>Everything you change here is reflected in each seller's panel.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('shipment.report') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-chart-line me-1"></i>Shipment report</a>
        <a href="{{ route('seller-list') }}" class="btn btn-primary"><i class="fa-solid fa-users me-1"></i>Sellers</a>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach ($kpis as $k)
        <div class="col-6 col-lg-3">
            <a href="{{ $k['route'] }}" class="sx-kpi">
                <div class="sx-kpi-top">
                    <span class="sx-kpi-label">{{ $k['label'] }}</span>
                    <span class="sx-kpi-icon sx-tone-{{ $k['tone'] }}"><i class="{{ $k['icon'] }}"></i></span>
                </div>
                <div class="sx-kpi-value">{{ $k['value'] }}</div>
                <div class="sx-kpi-sub">{{ $k['sub'] }}</div>
            </a>
        </div>
    @endforeach
</div>

{{-- ===== Shipments overview: totals, B2C vs B2B, tonnage, couriers, top B2B accounts ===== --}}
@php
    $t = \App\Support\AdminShipmentStats::class;
    $b2bUrl = Route::has('admin.bigship.index') ? route('admin.bigship.index') : '#';
    $overview = [
        ['label' => 'Total Orders', 'value' => number_format($stats['total_orders']), 'sub' => number_format($stats['b2c']['orders']) . ' B2C · ' . number_format($stats['b2b']['orders']) . ' B2B', 'icon' => 'fa-solid fa-boxes-stacked', 'tone' => 'blue', 'route' => route('shipment.report')],
        ['label' => 'Total Weight Shipped', 'value' => $t::tonnage($stats['total_kg']), 'sub' => number_format($stats['total_shipped']) . ' shipments · all time', 'icon' => 'fa-solid fa-weight-hanging', 'tone' => 'green', 'route' => route('shipment.report')],
        ['label' => 'Most Used Courier', 'value' => $stats['top_couriers'][0]['name'] ?? '—', 'sub' => isset($stats['top_couriers'][0]) ? number_format($stats['top_couriers'][0]['count']) . ' shipments' : 'No shipments yet', 'icon' => 'fa-solid fa-trophy', 'tone' => 'amber', 'route' => route('shipment.report')],
        ['label' => 'Top B2B Account', 'value' => $stats['top_b2b_accounts'][0]['name'] ?? '—', 'sub' => isset($stats['top_b2b_accounts'][0]) ? number_format($stats['top_b2b_accounts'][0]['orders']) . ' B2B orders · ' . $t::tonnage($stats['top_b2b_accounts'][0]['kg']) : 'No B2B orders yet', 'icon' => 'fa-solid fa-building', 'tone' => 'red', 'route' => $b2bUrl],
    ];
    $split = [
        'B2C' => ['d' => $stats['b2c'], 'route' => route('shipment.report'), 'icon' => 'fa-solid fa-box', 'tone' => 'blue'],
        'B2B' => ['d' => $stats['b2b'], 'route' => $b2bUrl, 'icon' => 'fa-solid fa-pallet', 'tone' => 'green'],
    ];
@endphp

<div class="d-flex align-items-center justify-content-between mb-2">
    <h5 class="mb-0">Shipments overview</h5>
</div>
<div class="row g-3 mb-3">
    @foreach ($overview as $k)
        <div class="col-6 col-lg-3">
            <a href="{{ $k['route'] }}" class="sx-kpi">
                <div class="sx-kpi-top">
                    <span class="sx-kpi-label">{{ $k['label'] }}</span>
                    <span class="sx-kpi-icon sx-tone-{{ $k['tone'] }}"><i class="{{ $k['icon'] }}"></i></span>
                </div>
                <div class="sx-kpi-value text-truncate" title="{{ $k['value'] }}">{{ $k['value'] }}</div>
                <div class="sx-kpi-sub">{{ $k['sub'] }}</div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    @foreach ($split as $label => $s)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><span class="sx-kpi-icon sx-tone-{{ $s['tone'] }} me-2"><i class="{{ $s['icon'] }}"></i></span>{{ $label }} orders</h5>
                    <a href="{{ $s['route'] }}" class="btn btn-sm btn-outline-secondary">Open</a>
                </div>
                <div class="card-body">
                    <div class="row g-2 text-center mb-3">
                        <div class="col-4">
                            <div class="sx-kpi-sub">Orders</div>
                            <div class="fs-4 fw-semibold">{{ number_format($s['d']['orders']) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="sx-kpi-sub">Shipped</div>
                            <div class="fs-4 fw-semibold">{{ number_format($s['d']['shipped']) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="sx-kpi-sub">Weight</div>
                            <div class="fs-4 fw-semibold">{{ $t::tonnage($s['d']['kg']) }}</div>
                        </div>
                    </div>
                    @if ($label === 'B2B')
                        <div class="sx-kpi-sub mb-3">
                            {{ number_format($s['d']['boxes']) }} boxes · ₹{{ number_format($s['d']['revenue'], 2) }} billed to sellers
                            @foreach (\App\Models\B2bOrder::STATUS_LABELS as $key => $lbl)
                                @if (!empty($s['d']['by_status'][$key]))
                                    · {{ $lbl }} {{ $s['d']['by_status'][$key] }}
                                @endif
                            @endforeach
                        </div>
                    @endif
                    <div class="sx-kpi-sub mb-1">Most used couriers</div>
                    @forelse ($s['d']['top_couriers'] as $c)
                        @php $max = max(1, $s['d']['top_couriers'][0]['count']); @endphp
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="text-truncate" style="width:45%" title="{{ $c['name'] }}">{{ $c['name'] }}</span>
                            <div class="progress flex-grow-1" style="height:8px">
                                <div class="progress-bar" style="width: {{ round($c['count'] / $max * 100) }}%"></div>
                            </div>
                            <span class="text-nowrap" style="width:3.5rem;text-align:right">{{ number_format($c['count']) }}</span>
                        </div>
                    @empty
                        <div class="text-muted small">No {{ $label }} shipments yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Accounts using B2B the most</h5>
        <a href="{{ $b2bUrl }}" class="btn btn-sm btn-outline-secondary">B2B / Bigship</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Seller</th>
                        <th class="text-end">B2B orders</th>
                        <th class="text-end">Boxes</th>
                        <th class="text-end">Weight</th>
                        <th class="text-end">Spend</th>
                        <th>Last booked</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stats['top_b2b_accounts'] as $i => $a)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="sx-seller-cell">
                                    <div class="sx-avatar">{{ strtoupper(mb_substr($a['name'] ?: 'S', 0, 1)) }}</div>
                                    <div>
                                        <div class="n">{{ $a['name'] }}</div>
                                        <div class="e">{{ $a['email'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">{{ number_format($a['orders']) }}</td>
                            <td class="text-end">{{ number_format($a['boxes']) }}</td>
                            <td class="text-end">{{ $t::tonnage($a['kg']) }}</td>
                            <td class="text-end">₹{{ number_format($a['spend'], 2) }}</td>
                            <td class="text-nowrap">{{ $a['last_at'] ? \Carbon\Carbon::parse($a['last_at'])->format('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No B2B orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Latest sellers</h5>
                <a href="{{ route('seller-list') }}" class="btn btn-sm btn-outline-secondary">View all</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Seller</th>
                                <th>Status</th>
                                <th>KYC</th>
                                <th>Joined</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSellers as $s)
                                <tr>
                                    <td>
                                        <div class="sx-seller-cell">
                                            <div class="sx-avatar">{{ strtoupper(mb_substr($s->name ?: 'S', 0, 1)) }}</div>
                                            <div>
                                                <div class="n">{{ $s->name ?: '—' }}</div>
                                                <div class="e">{{ $s->email }}{{ $s->phone_number ? ' · ' . $s->phone_number : '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($s->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($s->kyc_status == 1)
                                            <span class="badge bg-success">Verified</span>
                                        @else
                                            <span class="badge bg-warning">Pending</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ optional($s->created_at)->format('d M Y') }}</td>
                                    <td class="text-end">
                                        <div class="sx-actions justify-content-end">
                                            <a href="{{ route('seller-list.edit', $s->id) }}" class="btn btn-outline-secondary" title="Edit seller"><i class="fa-solid fa-pen"></i></a>
                                            <a href="{{ route('seller.impersonate', $s->id) }}" target="_blank" rel="noopener" class="btn btn-outline-primary" title="Open this seller's panel"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Panel</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No sellers yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h5>Quick actions</h5></div>
            <div class="card-body d-grid gap-2">
                @foreach ($quick as $q)
                    <a href="{{ $q['r'] }}" class="sx-quick">
                        <span class="sx-kpi-icon sx-tone-blue"><i class="{{ $q['i'] }}"></i></span>
                        <span>
                            <span class="t d-block">{{ $q['t'] }}</span>
                            <span class="d d-block">{{ $q['d'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
