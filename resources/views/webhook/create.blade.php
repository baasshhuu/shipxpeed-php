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
        background:linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
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
        padding: 2rem;
    }
    .wb-form-group {
        margin-bottom: 1.5rem;
    }
    .wb-form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        display: block;
    }
    .wb-form-control {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 0.8rem;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    .wb-form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        outline: none;
    }
    .wb-btn-gradient {
        background: var(--wb-primary-gradient);
        border: none;
        color: white;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        text-decoration: none;
        display: inline-block;
    }
    .wb-btn-gradient:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }
    .wb-btn-secondary {
        background: black;
        border: none;
        color: white;
        padding: 0.6rem 0.7rem;
        border-radius: 7px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    .wb-btn-secondary:hover {
        background: #5a6268;
        color: white;
        text-decoration: none;
    }
    .wb-docs-card {
        background: var(--wb-info-gradient);
        border-radius: 15px;
        padding: 1.5rem;
        margin-top: 1.5rem;
        box-shadow: 0 5px 20px rgba(168, 237, 234, 0.3);
    }
    .wb-code-block {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
        overflow-x: auto;
    }
    @media (max-width: 768px) {
        .webhook-page .wb-content-body {
            padding: 1.5rem;
        }
        .webhook-page .wb-dashboard-title {
            font-size: 1.1rem;
        }
    }
</style>

<div class="pc-container webhook-page" style="background:#646dff26;">
    <div class="pc-content"  style="margin-left:12px;">
        <!-- Dashboard Header -->
        <!-- <div class="wb-dashboard-header text-center">
            <div class="container">
                <h1 class="wb-dashboard-title">Create New Webhook</h1>
                <p class="wb-dashboard-subtitle">Set up real-time order status notifications</p>
            </div>
        </div> -->
        
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="wb-content-container">
                        <div class="wb-content-header">
                            <h4 class="wb-content-title">
                                <i class="fas fa-plus me-2"></i>
                                Create New Webhook
                            </h4>
                            <a href="{{ route('seller.webhooks.index') }}" class="wb-btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Webhooks
                            </a>
                        </div>
                        <div class="wb-content-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                            <form action="{{ route('seller.webhooks.store') }}" method="POST">
                                @csrf
                                
                                <div class="wb-form-group">
                                    <label for="webhook_url" class="wb-form-label">
                                        Webhook URL <span class="text-danger">*</span>
                                    </label>
                                    <input type="url" 
                                           class="form-control wb-form-control @error('webhook_url') is-invalid @enderror" 
                                           id="webhook_url" 
                                           name="webhook_url" 
                                           value="{{ old('webhook_url') }}"
                                           placeholder="https://your-domain.com/webhook/endpoint"
                                           required>
                                    @error('webhook_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Enter the complete URL where you want to receive webhook notifications.
                                    </small>
                                </div>

                                <div class="wb-form-group">
                                    <label for="status" class="wb-form-label">
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control wb-form-control @error('status') is-invalid @enderror" 
                                            id="status" 
                                            name="status" 
                                            required>
                                        <option value="">Select Status</option>
                                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Active webhooks will receive notifications. Inactive webhooks are disabled.
                                    </small>
                                </div>

                                <div class="wb-form-group">
                                    <button type="submit" class="wb-btn-gradient">
                                        <i class="fas fa-save"></i> Create Webhook
                                    </button>
                                    <a href="{{ route('seller.webhooks.index') }}" class="wb-btn-secondary ml-3">
                                         Cancel
                                    </a>
                                </div>
                            </form>
                </div>
            </div>
        </div>
    </div>

            <!-- Webhook Documentation -->
            <div class="row">
                <div class="col-12">
                    <div class="wb-docs-card">
                        <h5><i class="fas fa-book me-2"></i>Webhook Documentation</h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6>Request Format:</h6>
                                <div class="wb-code-block">
                                    <code>{
  "event": "order_delivered",
  "timestamp": "2025-12-11T10:30:00Z",
  "order_id": 12345,
  "awb_number": "AWB123456789",
  "status": "delivered",
  "data": {
    "order_number": "ORD-2025-001",
    "customer_name": "John Doe",
    "customer_phone": "+91XXXXXXXXXX",
    "delivery_address": "...",
    "current_status": "delivered",
    "updated_at": "2025-12-11T10:30:00Z"
  }
}</code>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Headers:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Content-Type:</strong> application/json</li>
                                    <li><strong>X-Webhook-Source:</strong> Seller-Admin</li>
                                </ul>

                                <h6 class="mt-3">Event Types:</h6>
                                <ul class="small">
                                    <li><code>order_created</code> - New order created</li>
                                    <li><code>order_picked_up</code> - Order picked up</li>
                                    <li><code>order_in_transit</code> - Order in transit</li>
                                    <li><code>order_out_for_delivery</code> - Out for delivery</li>
                                    <li><code>order_delivered</code> - Order delivered</li>
                                    <li><code>order_cancelled</code> - Order cancelled/RTO</li>
                                    <li><code>test</code> - Test webhook</li>
                                </ul>

                                <h6 class="mt-3">Response Expected:</h6>
                                <p class="small">
                                    Your webhook endpoint should return a 2xx HTTP status code to confirm successful receipt.
                                    Timeout: 30 seconds.
                                </p>
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
        // Validate URL format on input
        $('#webhook_url').on('blur', function() {
            const url = $(this).val();
            if (url && !isValidUrl(url)) {
                $(this).addClass('is-invalid');
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">Please enter a valid URL starting with http:// or https://</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });

        function isValidUrl(string) {
            try {
                const url = new URL(string);
                return url.protocol === 'http:' || url.protocol === 'https:';
            } catch (_) {
                return false;
            }
        }
    });
</script>
@endpush
