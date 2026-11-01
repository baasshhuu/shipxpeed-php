@extends('layouts.sellerdash')

@section('content')
@php
    $c = $cfg ?? \App\Support\Label\LabelRenderer::DEFAULTS;
    $chk = fn($k) => !empty($c[$k]) ? 'checked' : '';
@endphp
<div class="container-fluid slc-wrap">
<style>
    .slc-wrap{padding:80px 16px 32px 86px;}
    @media (max-width:768px){.slc-wrap{padding:72px 12px 24px 12px;}}
    .slc-title{font-weight:700;font-size:22px;margin:0;}
    .slc-sub{color:#6b7280;margin:4px 0 18px;}
    .slc-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:20px;}
    .slc-sec{padding:18px 0;border-bottom:1px solid #eef0f4;}
    .slc-sec:first-child{padding-top:0}.slc-sec:last-child{border-bottom:0;padding-bottom:0}
    .slc-h{font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#111827;margin:0 0 4px;}
    .slc-hint{font-size:13px;color:#6b7280;margin:0 0 12px;}
    .slc-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 18px;}
    @media (max-width:576px){.slc-grid{grid-template-columns:1fr;}}
    .slc-chk{display:flex;align-items:center;gap:10px;font-weight:500;cursor:pointer;margin:0;}
    .slc-chk input{width:18px;height:18px;accent-color:#4f5bff;}
    .slc-size{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .slc-size label{border:1.5px solid #e5e7eb;border-radius:12px;padding:14px;display:flex;gap:12px;align-items:center;cursor:pointer;margin:0;}
    .slc-size input{display:none;}
    .slc-size input:checked + .slc-opt{color:#3b44e6;}
    .slc-size label:has(input:checked){border-color:#4f5bff;background:#f5f6ff;}
    .slc-ico{width:40px;height:40px;border-radius:10px;background:#eef0ff;display:flex;align-items:center;justify-content:center;font-size:18px;color:#4f5bff;flex:0 0 40px;}
    .slc-opt b{display:block;color:#111827}.slc-opt small{color:#6b7280}
    .slc-box{background:#f8f9fb;border-radius:12px;padding:14px;margin-top:10px;}
    .slc-sw{display:flex;align-items:center;gap:10px;font-weight:600;margin:0;cursor:pointer;}
    .slc-sw input{appearance:none;width:40px;height:22px;border-radius:22px;background:#cfd3dc;position:relative;cursor:pointer;transition:.2s;flex:0 0 40px;}
    .slc-sw input::after{content:"";position:absolute;width:18px;height:18px;border-radius:50%;background:#fff;top:2px;left:2px;transition:.2s;}
    .slc-sw input:checked{background:#4f5bff;}.slc-sw input:checked::after{left:20px;}
    .slc-sub-row{display:flex;align-items:center;gap:8px;margin:10px 0 0 50px;font-size:14px;flex-wrap:wrap;}
    .slc-sub-row input{width:70px;text-align:center;border:1px solid #d6d9e0;border-radius:8px;padding:5px;}
    .slc-logo{border:1.5px dashed #cfd3dc;border-radius:12px;padding:14px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-top:10px;}
    .slc-logo img{max-height:48px;max-width:180px;object-fit:contain;}
    .slc-prev{position:sticky;top:80px;}
    .slc-prev iframe{width:100%;height:640px;border:1px solid #e5e7eb;border-radius:12px;background:#f3f4f6;}
    @media (max-width:991px){.slc-prev{position:static;margin-top:16px}.slc-prev iframe{height:520px}}
    .slc-save{background:#4f5bff;color:#fff;border:0;border-radius:10px;padding:11px 22px;font-weight:600;}
    .slc-save:hover{background:#3b44e6;color:#fff}
    .slc-ghost{border:1px solid #d6d9e0;background:#fff;border-radius:10px;padding:11px 18px;font-weight:600;color:#111827;text-decoration:none;display:inline-block;}
</style>

<h1 class="slc-title">Shipping Label Config</h1>
<p class="slc-sub">Configure label size and what shows on your shipping label. Changes preview live on the right.</p>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-3">
<div class="col-lg-7">
<form id="slcForm" action="{{ route('seller.custom-label.store') }}" method="POST" enctype="multipart/form-data" class="slc-card">
    @csrf

    <div class="slc-sec">
        <p class="slc-h">Label size</p>
        <div class="slc-size">
            <label><input type="radio" name="label_type" value="thermal" {{ $c['label_type']==='thermal'?'checked':'' }}>
                <span class="slc-ico">&#9636;</span><span class="slc-opt"><b>Thermal Printer</b><small>4 in x 6 in</small></span></label>
            <label><input type="radio" name="label_type" value="standard" {{ $c['label_type']!=='thermal'?'checked':'' }}>
                <span class="slc-ico">&#128196;</span><span class="slc-opt"><b>Desktop Printer</b><small>A4 (8 in x 11 in)</small></span></label>
        </div>
    </div>

    <div class="slc-sec">
        <p class="slc-h">Company details</p>
        <label class="slc-chk"><input type="checkbox" name="show_logo" value="1" {{ $chk('show_logo') }}> Company Logo</label>
        <div class="slc-logo">
            @if(!empty($settings?->logo_path))
                <img src="{{ asset($settings->logo_path) }}" alt="Logo">
                <label class="slc-chk" style="font-size:13px"><input type="checkbox" name="remove_logo" value="1"> Remove logo</label>
            @else
                <span class="text-muted" style="font-size:13px">No logo uploaded — your brand name is printed instead.</span>
            @endif
            <input type="file" name="logo" accept="image/png,image/jpeg" class="form-control" style="max-width:260px">
        </div>
        <small class="text-muted">PNG or JPG, up to 2 MB. Applies after you save.</small>
    </div>

    <div class="slc-sec">
        <p class="slc-h">Payment details</p>
        <div class="slc-grid">
            <label class="slc-chk"><input type="checkbox" name="show_invoice_value_prepaid" value="1" {{ $chk('show_invoice_value_prepaid') }}> Show invoice value for prepaid orders</label>
            <label class="slc-chk"><input type="checkbox" name="show_invoice_number" value="1" {{ $chk('show_invoice_number') }}> Show invoice number</label>
        </div>
    </div>

    <div class="slc-sec">
        <p class="slc-h">Seller &amp; return details</p>
        <p class="slc-hint">If a seller address is not given while creating the order, the pickup location name and address are printed.</p>
        <div class="slc-grid">
            <label class="slc-chk"><input type="checkbox" name="show_seller_name" value="1" {{ $chk('show_seller_name') }}> Seller Name</label>
            <label class="slc-chk"><input type="checkbox" name="show_seller_address" value="1" {{ $chk('show_seller_address') }}> Seller Address</label>
            <label class="slc-chk"><input type="checkbox" name="show_seller_gst" value="1" {{ $chk('show_seller_gst') }}> Seller GST</label>
            <label class="slc-chk"><input type="checkbox" name="show_return_address" value="1" {{ $chk('show_return_address') }}> Return Address</label>
            <label class="slc-chk"><input type="checkbox" name="show_customer_mobile" value="1" {{ $chk('show_customer_mobile') }}> Customer Mobile</label>
            <label class="slc-chk"><input type="checkbox" name="show_support_contact" value="1" {{ $chk('show_support_contact') }}> Seller Support Number</label>
        </div>
    </div>

    <div class="slc-sec">
        <p class="slc-h">Product details</p>
        <div class="slc-grid">
            <label class="slc-chk"><input type="checkbox" name="show_qty" value="1" {{ $chk('show_qty') }}> Show Quantity</label>
            <label class="slc-chk"><input type="checkbox" name="show_item_price" value="1" {{ $chk('show_item_price') }}> Show Item Price</label>
            <label class="slc-chk"><input type="checkbox" name="show_taxes" value="1" {{ $chk('show_taxes') }}> Show Taxes &amp; Taxable Amount</label>
            <label class="slc-chk" style="font-weight:400">GST rate (if not on item)
                <input type="number" name="tax_rate" value="{{ $c['tax_rate'] }}" min="0" max="28" step="0.5" style="width:70px;border:1px solid #d6d9e0;border-radius:8px;padding:4px;text-align:center"> %</label>
        </div>

        <div class="slc-box">
            <label class="slc-sw"><input type="checkbox" name="show_product_name" value="1" {{ $chk('show_product_name') }}> Show Product Name</label>
            <div class="slc-sub-row">Trim product name up to <input type="number" name="product_name_trim" value="{{ $c['product_name_trim'] }}" min="10" max="250"> characters</div>
        </div>
        <div class="slc-box">
            <label class="slc-sw"><input type="checkbox" name="show_sku" value="1" {{ $chk('show_sku') }}> Show SKU Code</label>
            <div class="slc-sub-row">Trim SKU code up to <input type="number" name="sku_trim" value="{{ $c['sku_trim'] }}" min="4" max="64"> characters</div>
        </div>
        <div class="slc-box">
            <label class="slc-sw"><input type="checkbox" name="multi_page" value="1" {{ $chk('multi_page') }}> Print multiple pages</label>
            <div class="slc-sub-row">Show up to <input type="number" name="max_products" value="{{ $c['max_products'] }}" min="1" max="20"> products</div>
            <div class="slc-sub-row" style="color:#6b7280;font-size:13px">The first products are listed on the label; the rest show as "+xx more SKUs", or continue on extra pages when multiple pages is on. Thermal labels fit up to 3 products on page 1.</div>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap mt-3">
        <button type="submit" class="slc-save">Save settings</button>
        <a id="slcOpen" class="slc-ghost" href="{{ route('seller.custom-label.preview') }}" target="_blank" rel="noopener">Open preview PDF</a>
    </div>
</form>
</div>

<div class="col-lg-5">
    <div class="slc-card slc-prev">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <b>Preview</b><small class="text-muted">Sample order</small>
        </div>
        <iframe id="slcFrame" title="Label preview" src="{{ route('seller.custom-label.preview', ['format' => 'html']) }}"></iframe>
    </div>
</div>
</div>
</div>

<script>
(function(){
    const form = document.getElementById('slcForm');
    const frame = document.getElementById('slcFrame');
    const open = document.getElementById('slcOpen');
    const base = @json(route('seller.custom-label.preview'));
    const bools = ['show_logo','show_invoice_value_prepaid','show_invoice_number','show_seller_name','show_seller_address','show_seller_gst','show_return_address','show_customer_mobile','show_support_contact','show_qty','show_item_price','show_taxes','show_product_name','show_sku','multi_page'];
    let t;
    function url(){
        const p = new URLSearchParams();
        const fd = new FormData(form);
        p.set('label_type', fd.get('label_type') || 'thermal');
        bools.forEach(k => p.set(k, form.elements[k] && form.elements[k].checked ? 1 : 0));
        ['product_name_trim','sku_trim','max_products','tax_rate'].forEach(k => { if (fd.get(k)) p.set(k, fd.get(k)); });
        return base + '?' + p.toString();
    }
    function refresh(){
        clearTimeout(t);
        t = setTimeout(() => { const u = url(); frame.src = u + '&format=html'; open.href = u; }, 350);
    }
    form.addEventListener('change', refresh);
    form.addEventListener('input', e => { if (e.target.type === 'number') refresh(); });
})();
</script>
@endsection
