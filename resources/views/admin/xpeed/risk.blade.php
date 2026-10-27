@extends('layouts.app')

@section('content')
@php use App\Support\Xpeed\Kit; @endphp
<div class="xa">
@include('admin.xpeed._style')
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div><h5 class="mb-0">Risky COD orders not yet shipped</h5><div class="small text-muted">All sellers, last 7 days. Score uses buyer history, pincode RTO rate, address quality and value.</div></div>
                <form><select name="min" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach([0 => 'All scores', 35 => 'Medium and high', 60 => 'High only'] as $v => $l)<option value="{{ $v }}" @selected($min === $v)>{{ $l }}</option>@endforeach
                </select></form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Seller</th><th>Order</th><th>Buyer</th><th>Value</th><th>Risk</th><th>Why</th></tr></thead>
                        <tbody>
                        @forelse($rows as $r)
                            @php $o = $r['order']; $c = Kit::consignee($o); @endphp
                            <tr>
                                <td>{{ $sellers[$o->seller_id] ?? '#' . $o->seller_id }}</td>
                                <td>#{{ Kit::ref($o) }}<div class="small text-muted">{{ optional($o->created_at)->format('d M') }}</div></td>
                                <td>{{ $c['name'] ?? '' }}<div class="small text-muted">{{ $c['phone'] ?? '' }} · {{ $c['pincode'] ?? '' }}</div></td>
                                <td>{{ Kit::money($o->order_amount) }}</td>
                                <td><span class="pill risk-{{ $r['risk']['level'] }}">{{ $r['risk']['score'] }}</span></td>
                                <td class="small">{{ implode(' · ', $r['risk']['reasons']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No risky orders.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Sellers by RTO rate</h5><div class="small text-muted">Last 60 days, sellers with 10+ closed shipments.</div></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Seller</th><th class="text-end">Closed</th><th class="text-end">RTO %</th></tr></thead>
                    <tbody>
                    @forelse($sellerRto as $sid => $v)
                        <tr><td>{{ $sellers[$sid] ?? '#' . $sid }}</td><td class="text-end">{{ $v['closed'] }}</td>
                            <td class="text-end"><span class="pill {{ $v['rate'] >= 25 ? 'bad' : ($v['rate'] >= 12 ? 'warn' : 'ok') }}">{{ $v['rate'] }}%</span></td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Not enough data yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
