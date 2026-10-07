
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
        border-radius: 7px;
        padding: 0.7rem 2rem 0.7rem 1.2rem;
        font-size: 0.85rem;
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
        padding: 0.4rem 0.4rem;
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
        background: linear-gradient(90deg, #3576e3 0, #306cc9 100%) !important;
        color: white;
        border: none;
        padding: 0.7rem 1rem;
        /* font-weight: 600; */
        font-size: 0.82rem;
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
</style>

<div class="pc-container channel-page">
    <div class="pc-content" style="margin-left:12px;background:#646dff26;">
        <!-- Dashboard Header -->
        <div class="ch-dashboard-header text-center fade-in">
            <div class="container">
                <h1 class="ch-dashboard-title">Channel Management</h1>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="ch-main-card slide-up">
            <!-- Search and Add Channel Section -->
            <div class="ch-search-section">
                <div class="ch-search-container">
                    <input type="text" class="form-control ch-search-input" placeholder="Search by Channel Name...">
                    <button class="ch-search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <button type="button" 
        class="btn ch-add-btn" 
        onclick="window.location.href='{{ route('seller.channel.add') }}'">
    <i class="fas fa-plus me-2"></i>Add Channel
</button>

                {{-- <button class="btn ch-add-btn">
                    <i class="fas fa-plus me-2"></i>Add Channel
                </button> --}}
            </div>

            <!-- Table Section -->
            <div class="table-responsive ch-table-container">
                <table class="table ch-table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Channel Id & Name</th>
                            <th>Last Order Sync</th>
                            {{-- <th>Last Inventory Sync</th> --}}
                            <th>Connection Status</th>
                            <th>Last Connection Sync</th>
                            <th>Connection Response</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($channels->isEmpty())
                        <!-- No Records Message -->
                        <tr>
                            <td colspan="9">
                                <div class="ch-empty-state">
                                    <i class="fas fa-satellite-dish"></i>
                                    <h5>No Channels Found</h5>
                                    <p>Start by adding your first channel to manage your sales platforms</p>
                                </div>
                            </td>
                        </tr>
                        @else
                        <!-- Display Channels Data -->
                        @foreach($channels as $index => $channel)
                        <tr class="fade-in">
                            <td><strong>{{ $index + 1 }}</strong></td>
                            <td>
                                <div>
                                    <strong class="text-primary">{{ $channel->store_url ?? $channel->name }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $channel->store_name ?? 'Shopify Store' }}</small>
                                </div>
                            </td>
                            <td>
                                <small class="text-muted">{{ $channel->last_order_sync ?? 'Never' }}</small>
                            </td>
                            {{-- <td>
                                <small class="text-muted">{{ $channel->last_inventory_sync ?? 'Never' }}</small>
                            </td> --}}
                            <td>
                                <span class="badge ch-badge {{ $channel->status == 'connected' ? 'bg-success' : 'bg-danger' }}">
                                    {{ ucfirst($channel->status) }}
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $channel->updated_at ? $channel->updated_at->format('M d, Y H:i') : 'Never' }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ $channel->connection_response ?? 'Connected Successfully' }}</small>
                            </td>
                            <td>
                                <span class="badge ch-badge {{ $channel->status == 'connected' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $channel->status == 'connected' ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    {{-- <button type="button" class="btn btn-sm btn-outline-primary ch-action-btn" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button> --}}
                                    <button type="button" class="btn btn-sm btn-outline-danger ch-action-btn" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    {{-- <button type="button" class="btn btn-sm btn-outline-info ch-action-btn" title="Sync" onclick="syncChannel({{ $channel->id }})">
                                        <i class="fas fa-sync"></i>
                                    </button> --}}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        @endif 
                       
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function syncChannel(channelId) {
    if (confirm('Are you sure you want to sync this channel?')) {
        // Show loading state
        const syncBtn = event.target.closest('button');
        const originalHTML = syncBtn.innerHTML;
        syncBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        syncBtn.disabled = true;
        
        // Make AJAX request to sync orders
        fetch('{{ route("shopify.sync.orders") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                channel_id: channelId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Channel synced successfully!');
                location.reload();
            } else {
                alert('Failed to sync channel: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to sync channel. Please try again.');
        })
        .finally(() => {
            // Reset button state
            syncBtn.innerHTML = originalHTML;
            syncBtn.disabled = false;
        });
    }
}
</script>
@endsection
