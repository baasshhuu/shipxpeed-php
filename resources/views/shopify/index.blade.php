
@extends('layouts.sellerdash')

@section('content')
<style>
    .channel-page {
        --ch-primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --ch-success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --ch-danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --ch-info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --ch-dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        --ch-secondary-gradient: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
    }
    
    .channel-page .ch-dashboard-header {
        background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 0.8rem 0;
        margin-bottom: 1.5rem;
        border-radius: 7px;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .channel-page .ch-dashboard-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        animation: shimmer 3s infinite;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    
    .channel-page .ch-dashboard-title {
        font-size: 1.5rem;
        font-weight: 700;
        /* text-shadow: 2px 2px 8px rgba(0,0,0,0.3); */
        margin-bottom: 0;
        position: relative;
        z-index: 2;
    }
    
    .channel-page .ch-breadcrumb {
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border-radius: 10px;
        padding: 0.5rem 1rem;
        display: inline-block;
        position: relative;
        z-index: 2;
    }
    
    .channel-page .ch-breadcrumb a {
        color: rgba(255,255,255,0.9);
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .channel-page .ch-breadcrumb a:hover {
        color: white;
        text-shadow: 0 0 10px rgba(255,255,255,0.5);
    }
    
    .channel-page .ch-main-card {
        background: white;
        border-radius: 7px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .channel-page .ch-main-card:hover {
        box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .channel-page .ch-search-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1rem;
        border-bottom: 2px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .channel-page .ch-search-container {
        position: relative;
        max-width: 350px;
        flex: 1;
        min-width: 250px;
    }
    
    .channel-page .ch-search-input {
        border-radius: 15px;
        border: 2px solid #e9ecef;
        padding: 0.8rem 3rem 0.8rem 1.2rem;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        width: 100%;
    }
    
    .channel-page .ch-search-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25), 0 5px 20px rgba(0,0,0,0.1);
        outline: none;
    }
    
    .channel-page .ch-search-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: var(--ch-primary-gradient);
        border-radius: 10px;
        padding: 0.6rem;
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }
    
    .channel-page .ch-search-btn:hover {
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        transform: translateY(-50%) scale(1.05);
    }
    
    .channel-page .ch-add-btn {
        background:linear-gradient(135deg, #82acd5 0%, #1388e3 100%);
    
        border: none;
        border-radius: 7px;
        padding: 0.5rem 0.5rem;
        color: white;
       
        font-size: 0.82rem;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(116, 185, 255, 0.3);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .channel-page .ch-add-btn:hover {
        box-shadow: 0 8px 30px rgba(116, 185, 255, 0.4);
        transform: translateY(-2px);
        background: var(--ch-primary-gradient);
    }

    .channel-page .ch-table-container {
        padding: 0;
        background: white;
    }
    
    .channel-page .ch-table {
        margin: 0;
        border: none;
    }
    
    .channel-page .ch-table thead th {
        background: var(--ch-dark-gradient);
        color: white;
        border: none;
        padding: 1rem 1.2rem;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        text-align: center;
    }
    
    .channel-page .ch-table thead th:first-child {
        border-radius: 0;
    }
    
    .channel-page .ch-table thead th:last-child {
        border-radius: 0;
    }
    
    .channel-page .ch-table tbody td {
        border: none;
        padding: 1.2rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        font-size: 0.9rem;
        text-align: center;
    }
    
    .channel-page .ch-table tbody tr {
        transition: all 0.3s ease;
        position: relative;
    }
    
    .channel-page .ch-table tbody tr:hover {
        background: linear-gradient(135deg, #f8f9ff 0%, #e6f3ff 100%);
        transform: scale(1.01);
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .channel-page .ch-empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #6c757d;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    }
    
    .channel-page .ch-empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.6;
        background: var(--ch-info-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .channel-page .ch-empty-state h5 {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #495057;
    }
    
    .channel-page .ch-empty-state p {
        font-size: 1rem;
        opacity: 0.8;
        margin: 0;
    }
    
    .channel-page .ch-action-btn {
        border-radius: 8px;
        padding: 0.4rem 0.8rem;
        margin: 0 0.2rem;
        font-size: 0.8rem;
        border: 2px solid;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .channel-page .ch-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .channel-page .ch-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .channel-page .ch-dashboard-title {
            font-size: 1.3rem;
        }
        
        .channel-page .ch-search-section {
            flex-direction: column;
            align-items: stretch;
        }
        
        .channel-page .ch-search-container {
            max-width: 100%;
            min-width: auto;
        }
        
        .channel-page .ch-add-btn {
            width: 100%;
            margin-top: 0.5rem;
        }
        
        .channel-page .ch-table thead th,
        .channel-page .ch-table tbody td {
            padding: 0.8rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .channel-page .ch-action-btn {
            padding: 0.3rem 0.5rem;
            margin: 0.1rem;
        }
        
        .channel-page .ch-table tbody tr:hover {
            transform: none;
        }
    }
    
    @media (max-width: 576px) {
        .channel-page .ch-dashboard-header {
            padding: 0.6rem 0;
            margin-bottom: 1rem;
        }
        
        .channel-page .ch-dashboard-title {
            font-size: 1.2rem;
        }
        
        .channel-page .ch-main-card {
            border-radius: 10px;
            margin: 0 0.5rem;
        }
        
        .channel-page .ch-search-section {
            padding: 1rem;
        }
        
        .channel-page .ch-empty-state {
            padding: 2rem 1rem;
        }
        
        .channel-page .ch-empty-state i {
            font-size: 3rem;
        }
        
        .channel-page .ch-empty-state h5 {
            font-size: 1.1rem;
        }
    }
    
    /* Animation Classes */
    .fade-in {
        animation: fadeIn 0.8s ease-in-out;
    }
    
    .slide-up {
        animation: slideUp 0.6s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .channel-page .sx-pill { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.75rem; font-weight:600; }
    .channel-page .sx-ok { background:#e6f7ee; color:#1c8a52; }
    .channel-page .sx-warn { background:#fff4e0; color:#a86a00; }
    .channel-page .sx-err { background:#fde8e8; color:#b42323; }
    .channel-page .sx-mut { background:#eef0f4; color:#5b6475; }
    .channel-page .sx-small { font-size:.78rem; color:#6b7385; }
    .channel-page .sx-errtext { font-size:.76rem; color:#b42323; max-width:320px; white-space:normal; }
    .channel-page .sx-section { padding:1.2rem 1.5rem; border-top:1px solid #eef0f4; }
    .channel-page .sx-section h6 { font-weight:700; margin-bottom:.8rem; }
    .channel-page .sx-log td { font-size:.8rem; padding:.45rem .6rem; }
</style>

<div class="pc-container channel-page">
    <div class="pc-content" style="margin-left:12px;background:#646dff26;">
        <div class="ch-dashboard-header text-center fade-in">
            <div class="container">
                <h1 class="ch-dashboard-title">Channel Management</h1>
            </div>
        </div>

        @if(session('success'))<div class="alert alert-success mx-3">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger mx-3">{{ session('error') }}</div>@endif

        <div class="ch-main-card slide-up">
            <div class="ch-search-section">
                <div class="ch-search-container">
                    <input type="text" class="form-control ch-search-input" id="chSearch" placeholder="Search by store...">
                    <button class="ch-search-btn" type="button"><i class="fas fa-search"></i></button>
                </div>
                <div class="d-flex gap-2">
                    @if($channels->where('status','connected')->count())
                    <form method="POST" action="{{ route('shopify.sync.orders') }}" class="m-0" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerHTML='<i class=\'fas fa-spinner fa-spin me-2\'></i>Syncing…'">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary"><i class="fas fa-sync me-2"></i>Sync all now</button>
                    </form>
                    @endif
                    <a href="{{ route('seller.channel.add') }}" class="btn ch-add-btn"><i class="fas fa-plus me-2"></i>Add Channel</a>
                </div>
            </div>

            <div class="table-responsive ch-table-container">
                <table class="table ch-table" id="chTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Store</th>
                            <th>Connection</th>
                            <th>Orders pulled</th>
                            <th>Status sent to Shopify</th>
                            <th>Settings</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($channels as $i => $ch)
                        @php
                            $pill = match($ch->status) {
                                'connected' => ['sx-ok', 'Connected'],
                                'pending_auth' => ['sx-warn', 'Waiting for Shopify approval'],
                                'auth_error' => ['sx-err', 'Token rejected — reconnect'],
                                'reconnect_required' => ['sx-warn', 'Reconnect required'],
                                default => ['sx-mut', ucfirst(str_replace('_',' ',$ch->status))],
                            };
                            $how = ['oauth' => 'App + approve', 'client_credentials' => 'Client ID + Secret', 'legacy_token' => 'Access token'][$ch->auth_type] ?? '—';
                        @endphp
                        <tr>
                            <td><strong>{{ $i + 1 }}</strong></td>
                            <td>
                                <strong class="text-primary">{{ $ch->shop_name ?: 'Shopify store' }}</strong><br>
                                <span class="sx-small">{{ $ch->shopify_shop_domain ?: $ch->formatted_store_url }}</span>
                            </td>
                            <td>
                                <span class="sx-pill {{ $pill[0] }}">{{ $pill[1] }}</span><br>
                                <span class="sx-small">{{ $how }}{{ $ch->status === 'connected' ? ($ch->webhooks_registered ? ' · instant webhooks' : ' · every 5 min') : '' }}</span>
                                @if($ch->last_error)<div class="sx-errtext mt-1">{{ \Illuminate\Support\Str::limit($ch->last_error, 220) }}</div>@endif
                            </td>
                            <td>
                                <strong>{{ (int) $ch->orders_synced_count }}</strong> imported<br>
                                <span class="sx-small">Last: {{ $ch->last_order_pull_at ? $ch->last_order_pull_at->timezone('Asia/Kolkata')->format('d M, h:i A') : 'Never' }}</span><br>
                                <span class="sx-small">From: {{ $ch->sync_start_date ? $ch->sync_start_date->format('d M Y') : '—' }}</span>
                            </td>
                            <td>
                                <span class="sx-small">Last: {{ $ch->last_status_push_at ? $ch->last_status_push_at->timezone('Asia/Kolkata')->format('d M, h:i A') : 'Never' }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('shopify.settings', $ch->id) }}" class="m-0">
                                    @csrf
                                    <label class="sx-small d-block"><input type="checkbox" name="auto_fulfill" value="1" {{ $ch->auto_fulfill ? 'checked' : '' }} onchange="this.form.submit()"> Mark fulfilled + send status</label>
                                    <label class="sx-small d-block"><input type="checkbox" name="notify_customer" value="1" {{ $ch->notify_customer ? 'checked' : '' }} onchange="this.form.submit()"> Notify buyer</label>
                                </form>
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($ch->status === 'connected')
                                    <form method="POST" action="{{ route('shopify.sync.orders') }}" class="m-0">
                                        @csrf <input type="hidden" name="channel_id" value="{{ $ch->id }}">
                                        <button class="btn btn-sm btn-outline-primary" title="Sync now"><i class="fas fa-sync"></i></button>
                                    </form>
                                    @endif
                                    @if($ch->status !== 'connected')
                                    <a href="{{ route('seller.shopify.integration', ['reconnect' => $ch->id]) }}" class="btn btn-sm btn-outline-warning" title="Reconnect"><i class="fas fa-plug"></i></a>
                                    @endif
                                    @if($ch->status !== 'disconnected')
                                    <form method="POST" action="{{ route('shopify.stores.disconnect', $ch->id) }}" class="m-0" onsubmit="return confirm('Disconnect this Shopify store? Imported orders stay in the panel.')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" title="Disconnect"><i class="fas fa-unlink"></i></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="ch-empty-state">
                                    <i class="fas fa-satellite-dish"></i>
                                    <h5>No Channels Found</h5>
                                    <p>Connect your Shopify store to pull orders automatically and send AWB + tracking back to Shopify.</p>
                                    <a href="{{ route('seller.shopify.integration') }}" class="btn ch-add-btn mt-2">Connect Shopify</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($orderErrors->count())
            <div class="sx-section">
                <h6>Orders that need attention</h6>
                <div class="table-responsive">
                    <table class="table table-sm sx-log">
                        <thead><tr><th>Order</th><th>Shopify #</th><th>AWB</th><th>Status</th><th>Problem</th></tr></thead>
                        <tbody>
                        @foreach($orderErrors as $o)
                            <tr>
                                <td>{{ $o->order_number }}</td>
                                <td>#{{ $o->shopify_order_number }}</td>
                                <td>{{ $o->awb_number ?: '—' }}</td>
                                <td>{{ $o->shipping_status ?: '—' }}</td>
                                <td class="sx-errtext">{{ $o->shopify_sync_error }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($logs->count())
            <div class="sx-section">
                <h6>Sync log <span class="sx-small">(latest 30)</span></h6>
                <div class="table-responsive">
                    <table class="table table-sm sx-log">
                        <thead><tr><th>Time</th><th>Type</th><th>Action</th><th>Result</th><th>Details</th></tr></thead>
                        <tbody>
                        @foreach($logs as $l)
                            <tr>
                                <td class="text-nowrap">{{ \Carbon\Carbon::parse($l->created_at)->timezone('Asia/Kolkata')->format('d M, h:i:s A') }}</td>
                                <td>{{ ucfirst($l->direction) }}</td>
                                <td>{{ str_replace('_', ' ', $l->action) }}</td>
                                <td><span class="sx-pill {{ $l->success ? 'sx-ok' : 'sx-err' }}">{{ $l->success ? 'OK' : 'Failed' }}</span></td>
                                <td class="sx-small" style="white-space:normal;max-width:420px">{{ $l->message }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.getElementById('chSearch')?.addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#chTable tbody tr').forEach(function (tr) {
        tr.style.display = tr.innerText.toLowerCase().indexOf(q) > -1 ? '' : 'none';
    });
});
</script>
@endsection
