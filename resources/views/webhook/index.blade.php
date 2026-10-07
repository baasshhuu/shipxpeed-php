@extends('layouts.sellerdash')

@section('content')
<style>
    .webhook-page {
        --wb-primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --wb-success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --wb-danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --wb-info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --wb-dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }
    .webhook-page .wb-dashboard-header {
        background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 0.8rem 0;
        margin-bottom: 1rem;
        border-radius: 7px;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.3);
    }
    .webhook-page .wb-dashboard-title {
        font-size: 1.3rem;
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        margin-bottom: 0.2rem;
    }
    .webhook-page .wb-dashboard-subtitle {
        font-size: 0.8rem;
        opacity: 0.9;
    }
    .webhook-page .wb-content-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .webhook-page .wb-content-header {
        background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 1.2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .webhook-page .wb-content-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
    }
    .webhook-page .wb-content-body {
        padding: 1.2rem;
    }
    .webhook-page .wb-custom-table {
        border: none;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }
    .webhook-page .wb-custom-table thead th {
        background-color: linear-gradient(90deg, #3576e3 0, #306cc9 100%) !important;;
        border: none;
        font-weight: 600;
        /* color: #495057; */
        padding: 0.8rem 0.7rem;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .webhook-page .wb-custom-table tbody td {
        border: none;
        text-align: center;
        padding: 0.8rem 0.7rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
        font-size: 0.85rem;
    }
    .webhook-page .wb-custom-table tbody tr {
        transition: all 0.3s ease;
    }
    .webhook-page .wb-custom-table tbody tr:hover {
        background-color: #f8f9ff;
        transform: scale(1.005);
    }
    .wb-empty-state {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }
    .wb-empty-state i {
        font-size: 2.5rem;
        margin-bottom: 0.8rem;
        opacity: 0.6;
        background: var(--wb-info-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .wb-btn-gradient {
        background: linear-gradient(135deg, #dbdeeb 0%, #fdfcff 100%);
        border: none;
        color: black;
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
    }
    .wb-btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: black;
    }
    .wb-info-card {
        background: var(--wb-info-gradient);
        border-radius: 15px;
        padding: 1.5rem;
        color: #333;
        margin-top: 1.5rem;
        box-shadow: 0 5px 20px rgba(168, 237, 234, 0.3);
    }
    .wb-action-btn {
        border: none;
        padding: 0.4rem 0.8rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        transition: all 0.3s ease;
        margin: 0 0.2rem;
    }
    .wb-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.2);
    }
    @media (max-width: 768px) {
        .webhook-page .wb-dashboard-title {
            font-size: 1.1rem;
        }
        .webhook-page .wb-dashboard-subtitle {
            font-size: 0.75rem;
        }
        .wb-action-btn {
            padding: 0.3rem 0.6rem;
            font-size: 0.7rem;
        }
    }
</style>

<div class="pc-container webhook-page"  style="background:#646dff26;">
    <div class="pc-content" style="margin-left:12px;">
        <!-- Dashboard Header -->
       
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="wb-content-container">
                        <div class="wb-content-header">
                            <h4 class="wb-content-title">
                                <i class="fas fa-webhook me-2"></i>
                                Webhooks Management
                            </h4>
                            <a href="{{ route('seller.webhooks.create') }}" class="wb-btn-gradient">
                                <i class="fas fa-plus"></i> Add New Webhook
                            </a>
                        </div>
                        <div class="wb-content-body">
                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    {{ session('success') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif

                            @if($webhooks->count() > 0)
                                <div class="table-responsive">
                                    <table class="table wb-custom-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Webhook URL</th>
                                                <th>Status</th>
                                                <th>Created At</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($webhooks as $index => $webhook)
                                                <tr>
                                                    <td>{{ $webhooks->firstItem() + $index }}</td>
                                                    <td>
                                                        <span class="text-truncate d-inline-block" style="max-width: 300px;" 
                                                            title="{{ $webhook->webhook_url }}">
                                                            {{ $webhook->webhook_url }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($webhook->status === 'active')
                                                            <span class="badge badge-success">Active</span>
                                                        @else
                                                            <span class="badge badge-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $webhook->created_at->format('d M Y, h:i A') }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('seller.webhooks.edit', $webhook->id) }}" 
                                                            class="wb-action-btn btn btn-outline-primary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            
                                                            <a href="{{ route('seller.webhooks.test', $webhook->id) }}" 
                                                            class="wb-action-btn btn btn-outline-info" title="Test Webhook"
                                                            onclick="return confirm('Are you sure you want to send a test webhook?')">
                                                                <i class="fas fa-flask"></i>
                                                            </a>
                                                            
                                                            <a href="{{ route('seller.webhooks.toggle', $webhook->id) }}" 
                                                            class="wb-action-btn btn btn-outline-warning" 
                                                            title="{{ $webhook->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                                                <i class="fas fa-{{ $webhook->status === 'active' ? 'pause' : 'play' }}"></i>
                                                            </a>
                                                            
                                                            <form action="{{ route('seller.webhooks.destroy', $webhook->id) }}" 
                                                                method="POST" class="d-inline"
                                                                onsubmit="return confirm('Are you sure you want to delete this webhook?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="wb-action-btn btn btn-outline-danger" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div class="d-flex justify-content-center">
                                    {{ $webhooks->links() }}
                                </div>
                            @else
                                <div class="wb-empty-state">
                                    <i class="fas fa-webhook"></i>
                                    <h5>No Webhooks Found</h5>
                                    <p class="text-muted">You haven't created any webhooks yet.</p>
                                    <a href="{{ route('seller.webhooks.create') }}" class="wb-btn-gradient">
                                        <i class="fas fa-plus"></i> Create Your First Webhook
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="wb-info-card">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle me-2"></i>What are Webhooks?</h6>
                                <p class="small">
                                    Webhooks allow you to receive real-time notifications when order statuses change. 
                                    When an event occurs (like order delivery or pickup), we'll send a POST request to your specified URL.
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-bell me-2"></i>Webhook Events:</h6>
                                <ul class="small">
                                    <li>Order Created</li>
                                    <li>Order Picked Up</li>
                                    <li>Order In Transit</li>
                                    <li>Order Out for Delivery</li>
                                    <li>Order Delivered</li>
                                    <li>Order Cancelled/RTO</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>
@endpush
