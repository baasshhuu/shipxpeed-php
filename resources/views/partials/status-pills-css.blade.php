{{-- status-pills-v3: shared Filter-by-Status pill style for every seller page (loaded from layouts.sellerdash after page content). --}}
<style>
    /* status-pills-v2: one consistent pill row on every order page - pills size to their text and
       wrap to the next line instead of squeezing (icon, label and count never overlap). */
    html body .status-filter-row .status-btns-container,
    html body .status-filter-row > .d-flex.flex-wrap {
        display: flex !important; flex-wrap: wrap !important; gap: 8px !important;
        height: auto !important; grid-template-columns: none !important;
    }
    html body .status-filter-row .status-btns-container > .status-btn-premium.btn,
    html body .status-filter-row > .d-flex.flex-wrap > .btn {
        flex: 0 0 auto !important; width: auto !important; max-width: none !important; min-width: 0 !important;
        height: 34px !important; padding: 0 !important; margin: 0 !important;
        display: inline-flex !important; align-items: center !important;
        border: 1px solid var(--sx-line-2, rgba(196,206,235,.16)) !important; border-radius: 999px !important;
        background: var(--sx-raised, #152036) !important; color: var(--sx-fg-2, #a3abc2) !important;
        font-size: 13px !important; font-weight: 500 !important; line-height: 1 !important;
        box-shadow: none !important; transform: none !important; white-space: nowrap !important;
    }
    html body .status-filter-row > .d-flex.flex-wrap > .btn { padding: 0 14px !important; gap: 7px; }
    html body .status-filter-row .status-btns-container > .status-btn-premium.btn:hover,
    html body .status-filter-row > .d-flex.flex-wrap > .btn:hover {
        background: var(--sx-hover, #1a2742) !important; color: var(--sx-fg, #e9ecf5) !important;
        border-color: var(--sx-blue, #3b7bff) !important;
    }
    html body .status-filter-row .status-btns-container > .status-btn-premium.btn.active {
        background: var(--sx-blue-bg, rgba(59,123,255,.14)) !important; color: var(--sx-blue-2, #6b9cff) !important;
        border-color: var(--sx-blue, #3b7bff) !important;
    }
    html body .status-filter-row .status-btn-content {
        display: inline-flex !important; align-items: center !important; gap: 7px !important;
        height: 34px !important; width: auto !important; padding: 0 14px !important;
        font-size: 13px !important; white-space: nowrap !important; line-height: 1 !important;
    }
    html body .status-filter-row .status-btn-content > i,
    html body .status-filter-row > .d-flex.flex-wrap > .btn > i {
        position: static !important; margin: 0 !important; font-size: 12px !important; flex: 0 0 auto;
    }
    html body .status-filter-row .status-count {
        position: static !important; margin-left: 0 !important; flex: 0 0 auto;
        min-width: 20px !important; height: 18px !important; padding: 0 6px !important; font-size: 11px !important;
    }
    @media (max-width: 575.98px) {
        html body .status-filter-row .status-btns-container > .status-btn-premium.btn,
        html body .status-filter-row > .d-flex.flex-wrap > .btn { height: 32px !important; }
        html body .status-filter-row .status-btn-content { height: 32px !important; padding: 0 11px !important; font-size: 12.5px !important; }
    }
</style>
