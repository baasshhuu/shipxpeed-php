@extends('layouts.app')

@section('css')
<style>
.crm-card{background:#fff;border-radius:10px;padding:20px;margin-bottom:20px;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.crm-section-title{font-size:16px;font-weight:600;margin-bottom:15px}
.zone-rate-row{display:grid;grid-template-columns:60px repeat(6,1fr);gap:8px;align-items:center;margin-bottom:8px}
.zone-rate-row input{padding:6px 8px;border:1px solid #ddd;border-radius:4px;font-size:13px;width:100%}
.zone-rate-header{font-weight:600;font-size:12px;color:#666}
.crm-account-card{border:1px solid #e2e2e2;border-radius:8px;padding:12px 15px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center}
.crm-account-card.active{border-color:#28a745;background:#f4fff6}
.crm-slab-card{border:1px solid #e2e2e2;border-radius:8px;padding:10px 15px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center}
.crm-slab-card.active{border-color:#007bff;background:#f4f8ff}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="crm-card">
        <h4>Courier &amp; Rate Manager</h4>
        <p class="text-muted" style="margin-bottom:0">Assign courier accounts to sellers and manage zone-wise rate slabs.</p>
    </div>

    <div class="crm-card">
        <div class="crm-section-title">Step 1: Select Seller</div>
        <select id="crm-seller" class="form-control">
            <option value="">-- Search Seller --</option>
            @foreach($sellers as $seller)
                <option value="{{ $seller->id }}">{{ $seller->name }} ({{ $seller->email }})</option>
            @endforeach
        </select>
    </div>

    <div class="crm-card" id="crm-courier-block" style="display:none">
        <div class="crm-section-title">Step 2: Select Courier</div>
        <select id="crm-courier" class="form-control">
            <option value="">-- Select Courier --</option>
            @foreach($couriers as $courier)
                <option value="{{ $courier->id }}">{{ $courier->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="crm-card" id="crm-account-block" style="display:none">
        <div class="crm-section-title">Step 3: Select Courier Account</div>
        <div id="crm-account-list">Loading...</div>
    </div>

    <div class="crm-card" id="crm-slab-block" style="display:none">
        <div class="crm-section-title">Step 4: Slabs for <span id="crm-selected-account-name"></span></div>
        <div id="crm-slab-list"></div>
        <hr>
        <h6>Create New Slab</h6>
        <input type="text" id="crm-slab-name" class="form-control" placeholder="Slab name (e.g. Zapdeal Standard)" style="max-width:320px;margin-bottom:15px">
        <div class="zone-rate-row zone-rate-header">
            <div>Zone</div><div>COD Price</div><div>COD Fixed</div><div>Prepaid Price</div><div>Prepaid Fixed</div><div>COD %</div><div>RTO Credit</div>
        </div>
        @foreach(['A','B','C','D','E'] as $zone)
        <div class="zone-rate-row" data-zone="{{ $zone }}">
            <div><strong>{{ $zone }}</strong></div>
            <input type="number" step="0.01" class="rate-cod-price" value="0">
            <input type="number" step="0.01" class="rate-cod-fix" value="0">
            <input type="number" step="0.01" class="rate-prepaid-price" value="0">
            <input type="number" step="0.01" class="rate-prepaid-fix" value="0">
            <input type="number" step="0.01" class="rate-cod-percent" value="0">
            <input type="number" step="0.01" class="rate-rto-credit" value="0">
        </div>
        @endforeach
        <button id="crm-save-slab" class="btn btn-primary mt-3">Save Slab</button>
        <div id="crm-msg" style="margin-top:10px"></div>
    </div>
</div>
<input type="hidden" id="crm-csrf" value="{{ csrf_token() }}">
<script>
window.addEventListener('load', function(){
jQuery(function($){
    var selectedSellerId = null;
    var selectedCourierId = null;
    var selectedAccountId = null;
    var csrf = $('#crm-csrf').val();

    $('#crm-seller').on('change', function(){
        selectedSellerId = $(this).val();
        $('#crm-courier-block').toggle(!!selectedSellerId);
        $('#crm-account-block').hide();
        $('#crm-slab-block').hide();
        $('#crm-courier').val('');
    });

    $('#crm-courier').on('change', function(){
        selectedCourierId = $(this).val();
        selectedAccountId = null;
        $('#crm-slab-block').hide();
        if(!selectedCourierId){ $('#crm-account-block').hide(); return; }
        $('#crm-account-block').show();
        $('#crm-account-list').html('Loading...');
        $.get('/courier-rate-manager/accounts', {courier_id: selectedCourierId}, function(res){
            renderAccounts(res.data);
        });
    });

    function renderAccounts(accounts){
        $.get('/courier-rate-manager/seller-assignment', {seller_id: selectedSellerId, courier_id: selectedCourierId}, function(assignRes){
            var activeAccountId = assignRes.data ? assignRes.data.courier_account_id : null;
            var html = '';
            accounts.forEach(function(acc){
                var isActive = (activeAccountId == acc.id);
                html += '<div class="crm-account-card' + (isActive ? ' active' : '') + '" data-id="' + acc.id + '" data-name="' + acc.name + '">';
                html += '<div><strong>' + acc.name + '</strong> <span class="badge badge-secondary">' + acc.courier_type + '</span> <span class="badge badge-info">' + acc.mode_type + '</span></div>';
                html += '<button class="btn btn-sm ' + (isActive ? 'btn-success' : 'btn-outline-primary') + ' crm-assign-btn" data-id="' + acc.id + '">' + (isActive ? 'Active' : 'Assign to Seller') + '</button>';
                html += '</div>';
            });
            $('#crm-account-list').html(html || '<em>No accounts found for this courier.</em>');
            if(activeAccountId){
                selectedAccountId = activeAccountId;
                var name = accounts.find(a => a.id == activeAccountId);
                loadSlabs(name ? name.name : '');
            }
        });
    }

    $(document).on('click', '.crm-assign-btn', function(){
        var accId = $(this).data('id');
        var accName = $(this).closest('.crm-account-card').data('name');
        $.post('/courier-rate-manager/assign-seller', {
            _token: csrf, seller_id: selectedSellerId, courier_account_id: accId
        }, function(res){
            if(res.success){
                selectedAccountId = accId;
                $('.crm-account-card').removeClass('active').find('.crm-assign-btn').removeClass('btn-success').addClass('btn-outline-primary').text('Assign to Seller');
                var card = $('.crm-account-card[data-id="'+accId+'"]');
                card.addClass('active').find('.crm-assign-btn').addClass('btn-success').removeClass('btn-outline-primary').text('Active');
                loadSlabs(accName);
            } else {
                alert(res.message || 'Failed to assign');
            }
        });
    });

    function loadSlabs(accountName){
        $('#crm-selected-account-name').text(accountName);
        $('#crm-slab-block').show();
        $.get('/courier-rate-manager/slabs', {courier_account_id: selectedAccountId}, function(res){
            var html = '';
            res.data.forEach(function(slab){
                html += '<div class="crm-slab-card"><div>' + slab.name + '</div></div>';
            });
            $('#crm-slab-list').html(html || '<em>No slabs yet. Create one below.</em>');
        });
    }

    $('#crm-save-slab').on('click', function(){
        var name = $('#crm-slab-name').val();
        if(!name){ alert('Please enter a slab name'); return; }
        if(!selectedAccountId){ alert('Please assign a courier account to the seller first'); return; }
        var rates = {};
        $('.zone-rate-row[data-zone]').each(function(){
            var zone = $(this).data('zone');
            rates[zone] = {
                cod_price: $(this).find('.rate-cod-price').val(),
                cod_fix_price: $(this).find('.rate-cod-fix').val(),
                prepaid_price: $(this).find('.rate-prepaid-price').val(),
                prepaid_fix_price: $(this).find('.rate-prepaid-fix').val(),
                cod_charge_percent: $(this).find('.rate-cod-percent').val(),
                rto_credit: $(this).find('.rate-rto-credit').val()
            };
        });
        $.post('/courier-rate-manager/save-slab', {
            _token: csrf, courier_account_id: selectedAccountId, name: name, rates: rates
        }, function(res){
            $('#crm-msg').html(res.success ? '<span class="text-success">Slab saved successfully.</span>' : '<span class="text-danger">'+res.message+'</span>');
            if(res.success){ loadSlabs($('#crm-selected-account-name').text()); }
        });
    });
});
});
</script>
@endsection
