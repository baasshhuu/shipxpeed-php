@extends('layouts.sellerdash')

@section('content')
@include('sellerdashboard.xpeed._style')
<div class="pc-container xp">
<div class="pc-content">
    <div class="xp-head">
        <div>
            <h1><i class="ti ti-file-spreadsheet"></i> Reports</h1>
            <p>Download any report as CSV (opens in Excel / Google Sheets), or get it e-mailed to you and your team every day, week or month.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="xp-card">
                <h2>Download now</h2>
                <p class="xp-sub">Up to one year at a time.</p>
                <form method="POST" action="{{ route('xp.reports.download') }}">
                    @csrf
                    <label class="form-label">Report</label>
                    <div class="mb-2">
                        @foreach($types as $k => [$t, $d])
                            <label class="d-flex gap-2 align-items-start p-2 rounded mb-1" style="border:1px solid rgba(120,130,150,.2);cursor:pointer">
                                <input type="radio" name="report" value="{{ $k }}" @checked($loop->first) class="mt-1">
                                <span><b>{{ $t }}</b><br><span class="small text-muted">{{ $d }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">From</label><input type="date" name="from" class="form-control form-control-sm" required value="{{ now()->subDays(29)->toDateString() }}" max="{{ now()->toDateString() }}"></div>
                        <div class="col-6"><label class="form-label">To</label><input type="date" name="to" class="form-control form-control-sm" required value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        @foreach([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $d => $t)
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0" onclick="xpRange({{ $d }})">{{ $t }}</button>
                        @endforeach
                    </div>
                    <button class="btn btn-xp mt-3 w-100"><i class="ti ti-download"></i> Download CSV</button>
                </form>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="xp-card">
                <h2>E-mail it to me automatically</h2>
                <p class="xp-sub">Sent in the morning (after 8 am). Up to 10 schedules, up to 10 e-mail addresses each.</p>
                <form method="POST" action="{{ route('xp.reports.schedule') }}" class="row g-2">
                    @csrf
                    <div class="col-md-6"><label class="form-label">Report</label>
                        <select name="report" class="form-select form-select-sm">@foreach($types as $k => [$t])<option value="{{ $k }}">{{ $t }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">How often</label>
                        <select name="frequency" class="form-select form-select-sm">@foreach($frequencies as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Send to</label>
                        <input name="emails" class="form-control form-control-sm" required value="{{ old('emails', $seller->email) }}" placeholder="you@brand.com, ops@brand.com"></div>
                    <div class="col-12"><button class="btn btn-xp btn-sm" @disabled(!$xpReady)>Schedule</button></div>
                </form>

                <div class="table-responsive mt-3">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Report</th><th>When</th><th>To</th><th>Last sent</th><th></th></tr></thead>
                        <tbody>
                        @forelse($schedules as $s)
                            <tr>
                                <td>{{ $types[$s->report][0] ?? $s->report }}</td>
                                <td>{{ ucfirst($s->frequency) }}</td>
                                <td class="small">{{ $s->emails }}</td>
                                <td class="small">{{ $s->last_sent_at ? \Carbon\Carbon::parse($s->last_sent_at)->format('d M, h:i A') : 'Not yet' }}
                                    @if($s->last_result)<div class="text-muted">{{ \Illuminate\Support\Str::limit($s->last_result, 60) }}</div>@endif</td>
                                <td class="text-end"><form method="POST" action="{{ route('xp.reports.schedule.delete', $s->id) }}" onsubmit="return confirm('Stop this e-mail?')">@csrf<button class="btn btn-sm btn-outline-danger py-0">Stop</button></form></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted small text-center py-3">No scheduled reports yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script>
function xpRange(d) {
    var t = new Date(), f = new Date(); f.setDate(t.getDate() - d + 1);
    var iso = x => new Date(x.getTime() - x.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    document.querySelector('[name=from]').value = iso(f); document.querySelector('[name=to]').value = iso(t);
}
</script>
@endsection
