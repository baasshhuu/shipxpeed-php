@extends('layouts.sellerdash')

@section('content')
@php use App\Support\Xpeed\Kit; @endphp
@include('sellerdashboard.xpeed._style')
<div class="pc-container xp">
<div class="pc-content">
    <div class="xp-head">
        <div>
            <h1><i class="ti ti-stack-2"></i> Bulk actions</h1>
            <p>Paste up to 500 order IDs, order numbers or AWBs (comma, space or one per line) and print labels, print a pickup manifest, cancel shipments or download them in one go.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="xp-card">
                <form method="POST" action="{{ route('xp.bulk.run') }}" id="xpBulkForm">
                    @csrf
                    <label class="form-label" for="refs">Order IDs / order numbers / AWBs</label>
                    <textarea id="refs" name="refs" rows="8" class="form-control mono" required placeholder="1234567890123&#10;SX-10021, SX-10022">{{ old('refs') }}</textarea>
                    <div class="small text-muted mt-1"><span id="xpCount">0</span> value(s) · max 500</div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button name="action" value="find" class="btn btn-outline-secondary btn-sm"><i class="ti ti-search"></i> Check</button>
                        <button name="action" value="labels" class="btn btn-xp btn-sm" formtarget="_blank"><i class="ti ti-printer"></i> Print labels</button>
                        <button name="action" value="manifest" class="btn btn-xp btn-sm" formtarget="_blank"><i class="ti ti-clipboard-list"></i> Print manifest</button>
                        <button name="action" value="export" class="btn btn-outline-primary btn-sm"><i class="ti ti-download"></i> Download CSV</button>
                        <button name="action" value="cancel" class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel all these shipments? Only shipments not yet picked up can be cancelled; charges are refunded as per your cancellation terms.')"><i class="ti ti-x"></i> Cancel shipments</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="xp-card">
                <h2>How it works</h2>
                <ul class="small mb-0 ps-3">
                    <li class="mb-1"><b>Check</b> shows which values matched your shipments and which did not.</li>
                    <li class="mb-1"><b>Print labels</b> uses your Custom Label settings (4×6 or A4) and opens in a new tab.</li>
                    <li class="mb-1"><b>Print manifest</b> lists shipments grouped by courier with a signature box for the pickup person.</li>
                    <li class="mb-1"><b>Cancel</b> works only for shipments that are not yet picked up.</li>
                    <li><b>Download CSV</b> gives the selected shipments with status, buyer and amounts.</li>
                </ul>
            </div>
        </div>
    </div>

    @if($found->isNotEmpty() || !empty($missing))
    <div class="xp-card">
        <h2>{{ $found->count() }} shipment(s) found</h2>
        @if(!empty($missing))<p class="xp-sub">Not found on your account: <span class="mono">{{ implode(', ', array_slice($missing, 0, 50)) }}{{ count($missing) > 50 ? '…' : '' }}</span></p>@endif
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Order</th><th>AWB</th><th>Courier</th><th>Status</th><th>Payment</th><th>Buyer</th></tr></thead>
                <tbody>
                @foreach($found as $o)
                    @php $b = Kit::bucket($o); $c = Kit::consignee($o); @endphp
                    <tr>
                        <td>#{{ Kit::ref($o) }}<div class="small text-muted">ID {{ $o->id }}</div></td>
                        <td class="mono">{{ $o->awb_number ?: '—' }}</td>
                        <td>{{ $o->awb_number ? Kit::courierLabel($o) : '—' }}</td>
                        <td><span class="pill {{ in_array($b, ['delivered']) ? 'ok' : (in_array($b, ['rto','ndr','cancelled']) ? 'bad' : 'info') }}">{{ Kit::BUCKET_LABELS[$b] ?? $b }}</span></td>
                        <td>{{ strtoupper($o->payment_type) }} · {{ Kit::money($o->order_amount) }}</td>
                        <td>{{ $c['name'] ?? '' }} <span class="text-muted small">{{ $c['pincode'] ?? '' }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
</div>
<script>
(function () {
    var t = document.getElementById('refs'), c = document.getElementById('xpCount');
    function upd() { c.textContent = (t.value.split(/[\s,;]+/).filter(Boolean)).length; }
    t.addEventListener('input', upd); upd();
})();
</script>
@endsection
