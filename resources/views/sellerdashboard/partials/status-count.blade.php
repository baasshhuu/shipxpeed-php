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
