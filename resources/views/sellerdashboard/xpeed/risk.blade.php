@extends('layouts.sellerdash')

@section('content')
@php use App\Support\Xpeed\Kit; @endphp
@include('sellerdashboard.xpeed._style')
<div class="pc-container xp">
<div class="pc-content">
    <div class="xp-head">
        <div>
            <h1><i class="ti ti-shield-x"></i> RTO risk check</h1>
            <p>Before you ship, see which new orders are likely to come back. The score uses your buyer's past deliveries and returns, the pincode's return rate, address quality and order value. Call or confirm high-risk COD orders on WhatsApp first, or ask for prepaid.</p>
        </div>
        <div class="d-flex gap-3">
            <div><div class="stat">{{ $rows->where('risk.level', 'high')->count() }}</div><div class="stat-l" style="color:#ffb4ad">High risk</div></div>
            <div><div class="stat">{{ $rows->where('risk.level', 'medium')->count() }}</div><div class="stat-l" style="color:#ffd79a">Medium</div></div>
            <div><div class="stat">{{ $rows->where('risk.level', 'low')->count() }}</div><div class="stat-l" style="color:#9be6b5">Low</div></div>
        </div>
    </div>

    <div class="xp-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div><h2>Unshipped orders · last 30 days</h2><p class="xp-sub mb-0">Highest risk first. Hover the score for the reasons.</p></div>
            <div class="btn-group btn-group-sm">
                <a href="{{ route('xp.risk', ['cod' => 1]) }}" class="btn {{ $codOnly ? 'btn-xp' : 'btn-outline-secondary' }}">COD only</a>
                <a href="{{ route('xp.risk', ['cod' => 0]) }}" class="btn {{ !$codOnly ? 'btn-xp' : 'btn-outline-secondary' }}">All orders</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Order</th><th>Buyer</th><th>Payment</th><th>Risk</th><th>Why</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse($rows as $r)
                    @php $o = $r['order']; $c = Kit::consignee($o); $ph = preg_replace('/\D/', '', (string) ($c['phone'] ?? '')); @endphp
                    <tr>
                        <td>#{{ Kit::ref($o) }}<div class="small text-muted">{{ optional($o->created_at)->format('d M, h:i A') }}</div></td>
                        <td>{{ $c['name'] ?? '' }}<div class="small text-muted">{{ $c['phone'] ?? '' }} · {{ $c['city'] ?? '' }} {{ $c['pincode'] ?? '' }}</div></td>
                        <td>{{ strtoupper($o->payment_type) }}<div class="small">{{ Kit::money($o->order_amount) }}</div></td>
                        <td><span class="pill risk-{{ $r['risk']['level'] }}" style="font-size:13px">{{ $r['risk']['score'] }} · {{ ucfirst($r['risk']['level']) }}</span></td>
                        <td class="small" style="max-width:380px">{{ implode(' · ', $r['risk']['reasons']) ?: 'No warning signs' }}</td>
                        <td class="text-end text-nowrap">
                            @if(strlen($ph) >= 10)
                                <a class="btn btn-sm btn-outline-success py-0" target="_blank" rel="noopener" href="https://wa.me/91{{ substr($ph, -10) }}?text={{ rawurlencode('Hi ' . ($c['name'] ?? '') . ', this is ' . ($seller->name ?? 'your seller') . '. Please confirm your order #' . Kit::ref($o) . ' of ' . Kit::money($o->order_amount) . ' so we can ship it today.') }}">Confirm on WhatsApp</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="xp-empty"><i class="ti ti-shield-check"></i>No unshipped orders to check.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
