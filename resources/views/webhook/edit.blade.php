@extends('layouts.sellerdash')

@section('content')
<style>
    .webhook-page {
        --wb-primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --wb-success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --wb-danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --wb-info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --wb-dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        --wb-warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .webhook-page .wb-dashboard-header {
        background: var(--wb-primary-gradient);
        color: white;
        padding: 0.8rem 0;
        margin-bottom: 1rem;
        border-radius: 0 0 15px 15px;
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
        background: var(--wb-dark-gradient);
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
        background: #6c757d;
        border: none;
        color: white;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
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
    .wb-btn-info {
        background: var(--wb-success-gradient);
        border: none;
        color: white;
        padding: 0.6rem 1.2rem;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        font-size: 0.85rem;
    }
    .wb-btn-info:hover {
        transform: translateY(-1px);
        color: white;
        text-decoration: none;
    }
    .wb-btn-warning {
        background: var(--wb-warning-gradient);
        border: none;
        color: white;
        padding: 0.6rem 1.2rem;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        font-size: 0.85rem;
    }
    .wb-btn-warning:hover {
        transform: translateY(-1px);
        color: white;
        text-decoration: none;
    }
    .wb-btn-danger {
        background: var(--wb-danger-gradient);
        border: none;
        color: white;
        padding: 0.6rem 1.2rem;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
        font-size: 0.85rem;
        width: 100%;
    }
    .wb-btn-danger:hover {
        transform: translateY(-1px);
        color: white;
    }
    .wb-info-cards {
        display: flex;
        gap: 1rem;
    }
    .wb-info-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        flex: 1;
    }
    .wb-actions-card {
        background: var(--wb-info-gradient);
    }
    @media (max-width: 768px) {
        .wb-info-cards {
            flex-direction: column;
        }
        .webhook-page .wb-content-body {
            padding: 1.5rem;
        }
        .webhook-page .wb-dashboard-title {
            font-size: 1.1rem;
        }
    }
</style>

<div class="pc-container webhook-page">
    <div class="pc-content">
        <!-- Dashboard Header -->
        <div class="wb-dashboard-header text-center">
            <div class="container">
                <h1 class="wb-dashboard-title">Edit Webhook</h1>
                <p class="wb-dashboard-subtitle">Modify your webhook configuration</p>
            </div>
        </div>
        
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="wb-content-container">
                        <div class="wb-content-header">
                            <h4 class="wb-content-title">
                                <i class="fas fa-edit me-2"></i>
                                Edit Webhook
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

                            <form action="{{ route('seller.webhooks.update', $webhook->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="wb-form-group">
                                    <label for="webhook_url" class="wb-form-label">
                                        Webhook URL <span class="text-danger">*</span>
                                    </label>
                                    <input type="url" 
                                           class="form-control wb-form-control @error('webhook_url') is-invalid @enderror" 
                                           id="webhook_url" 
                                           name="webhook_url" 
                                           value="{{ old('webhook_url', $webhook->webhook_url) }}"
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
                                        <option value="active" {{ old('status', $webhook->status) === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('status', $webhook->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                                        <i class="fas fa-save"></i> Update Webhook
                                    </button>
                                    <a href="{{ route('seller.webhooks.test', $webhook->id) }}" 
                                       class="wb-btn-info ml-3"
                                       onclick="return confirm('Are you sure you want to send a test webhook?')">
                                        <i class="fas fa-flask"></i> Test Webhook
                                    </a>
                                    <a href="{{ route('seller.webhooks.index') }}" class="wb-btn-secondary ml-3">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Webhook Info Cards -->
            <div class="row">
                <div class="col-md-6">
                    <div class="wb-info-card">
                        <h6><i class="fas fa-info-circle me-2 text-primary"></i>Webhook Details</h6>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td>{{ $webhook->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Updated:</strong></td>
                                <td>{{ $webhook->updated_at->format('d M Y, h:i A') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    @if($webhook->status === 'active')
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="wb-info-card wb-actions-card">
                        <h6><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                        <div class="d-grid gap-2 mt-3">
                            <a href="{{ route('seller.webhooks.test', $webhook->id) }}" 
                               class="wb-btn-info"
                               onclick="return confirm('Are you sure you want to send a test webhook?')">
                                <i class="fas fa-flask"></i> Test This Webhook
                            </a>
                            
                            <a href="{{ route('seller.webhooks.toggle', $webhook->id) }}" 
                               class="wb-btn-warning">
                                <i class="fas fa-{{ $webhook->status === 'active' ? 'pause' : 'play' }}"></i> 
                                {{ $webhook->status === 'active' ? 'Deactivate' : 'Activate' }} Webhook
                            </a>
                            
                            <form action="{{ route('seller.webhooks.destroy', $webhook->id) }}" 
                                  method="POST" 
                                  onsubmit="return confirm('Are you sure you want to delete this webhook? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="wb-btn-danger">
                                    <i class="fas fa-trash"></i> Delete Webhook
                                </button>
                            </form>
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
