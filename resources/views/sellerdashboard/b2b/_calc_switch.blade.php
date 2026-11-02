{{-- Rate calculator switch: B2C (parcel couriers) | B2B (heavy / multi-box). $calc = 'b2c' | 'b2b' --}}
@php($calc = $calc ?? 'b2c')
@if(Route::has('seller.b2b.rates'))
<style>
.rc-switch{display:flex;gap:6px;padding:6px;margin:0 0 16px;border-radius:12px;background:var(--sx-surface,#0f1626);border:1px solid var(--sx-line,rgba(196,206,235,.12));width:max-content;max-width:100%}
.rc-switch a{display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:9px;font-size:14px;font-weight:600;color:var(--sx-fg-2,#a3abc2);text-decoration:none;line-height:1.2}
.rc-switch a small{display:block;font-size:11px;font-weight:500;opacity:.75}
.rc-switch a:hover{color:var(--sx-fg,#e9ecf5);background:var(--sx-raised,#152036)}
.rc-switch a.on{background:var(--sx-blue,#3b7bff);color:#fff}
@media (max-width:540px){.rc-switch{width:100%}.rc-switch a{flex:1;justify-content:center;padding:9px 10px}}
</style>
<div class="rc-switch" role="tablist" aria-label="Rate calculator type">
    <a href="{{ Route::has('seller.ratecards') ? route('seller.ratecards') : url('/rate-cards') }}" class="{{ $calc === 'b2c' ? 'on' : '' }}" role="tab" aria-selected="{{ $calc === 'b2c' ? 'true' : 'false' }}">
        <i class="ti ti-package"></i><span>B2C<small>Parcels to customers</small></span>
    </a>
    <a href="{{ route('seller.b2b.rates') }}" class="{{ $calc === 'b2b' ? 'on' : '' }}" role="tab" aria-selected="{{ $calc === 'b2b' ? 'true' : 'false' }}">
        <i class="ti ti-truck-delivery"></i><span>B2B<small>Heavy &amp; multi-box</small></span>
    </a>
</div>
@endif
