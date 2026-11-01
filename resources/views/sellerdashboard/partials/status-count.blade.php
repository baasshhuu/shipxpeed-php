{{-- Live order count pill for a "Filter by Status" tab. Usage: @include('sellerdashboard.partials.status-count', ['key' => 'ndr', 'tone' => 'warning']) --}}
@php $n = (int) (($orderCounts ?? [])[$key] ?? 0); @endphp
<span class="status-count status-count-{{ $tone ?? 'secondary' }}{{ $n === 0 ? ' is-zero' : '' }}">{{ number_format($n) }}</span>
@once
<style>
    .status-count {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 1.5em; height: 1.5em; padding: 0 .45em; margin-left: .45rem;
        border-radius: 999px; font-size: .78em; font-weight: 700; line-height: 1;
        background: rgba(100, 116, 139, .18); color: inherit;
        font-variant-numeric: tabular-nums;
    }
    .status-count-success { background: rgba(34, 197, 94, .18);  color: #16a34a; }
    .status-count-danger  { background: rgba(239, 68, 68, .18);  color: #dc2626; }
    .status-count-warning { background: rgba(245, 158, 11, .20); color: #d97706; }
    .status-count-info    { background: rgba(14, 165, 233, .18); color: #0284c7; }
    .status-count-primary { background: rgba(99, 118, 241, .18); color: #6376f1; }
    .status-count.is-zero { background: rgba(100, 116, 139, .12); color: inherit; opacity: .5; }
</style>
@endonce
