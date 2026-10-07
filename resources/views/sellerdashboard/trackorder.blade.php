@extends('layouts.sellerdash')

@section('content')
<style>
    .track-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 80px 0 100px 0;
        position: relative;
    }
    
    .track-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.1) 25%, transparent 25%), 
                    linear-gradient(-45deg, rgba(255,255,255,0.1) 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, rgba(255,255,255,0.1) 75%), 
                    linear-gradient(-45deg, transparent 75%, rgba(255,255,255,0.1) 75%);
        background-size: 20px 20px;
        background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
        opacity: 0.3;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        text-align: center;
        color: white;
    }
    
    .hero-content h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    .hero-content p {
        font-size: 1.3rem;
        opacity: 0.9;
        margin-bottom: 0;
        font-weight: 300;
    }
    
    .tracking-form-section {
        background: white;
        margin-top: -50px;
        position: relative;
        z-index: 10;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        padding: 50px 40px;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .form-title {
        text-align: center;
        margin-bottom: 30px;
        color: #2c3e50;
        font-weight: 600;
        font-size: 1.4rem;
    }
    
    .track-input-wrapper {
        position: relative;
        margin-bottom: 20px;
    }
    
    .track-input {
        width: 100%;
        padding: 18px 20px;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    
    .track-input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .track-btn {
        width: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 18px;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        border-radius: 12px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .track-btn:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }
    
    .tracking-results {
        max-width: 900px;
        margin: 50px auto 0;
        padding: 0 20px;
    }
    
    .tracking-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        border: 1px solid #f1f3f4;
    }
    
    .card-header {
        padding: 25px 30px;
        border-bottom: 1px solid #f1f3f4;
        background: #f8f9fa;
    }
    
    .card-header h5 {
        margin: 0;
        font-weight: 600;
        font-size: 1.3rem;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .courier-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .courier-badge.delhivery {
        background: #e3f2fd;
        color: #1565c0;
    }
    
    .courier-badge.shadowfax {
        background: #fff3e0;
        color: #ef6c00;
    }
    
    .courier-badge.xpressbees {
        background: #f3e5f5;
        color: #7b1fa2;
    }
    
    .card-body {
        padding: 30px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 35px;
    }
    
    .info-item {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }
    
    .info-item:hover {
        background: #f1f3f4;
        transform: translateY(-2px);
    }
    
    .info-label {
        color: #6c757d;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        display: block;
        margin-bottom: 8px;
    }
    
    .info-value {
        color: #2c3e50;
        font-size: 1.1rem;
        font-weight: 500;
        margin: 0;
    }
    
    .status-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
    }
    
    .timeline-section {
        margin-top: 20px;
    }
    
    .timeline-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 25px;
        color: #2c3e50;
        font-weight: 600;
        font-size: 1.2rem;
    }
    
    .timeline {
        position: relative;
        padding-left: 0;
    }
    
    .timeline-item {
        position: relative;
        padding: 20px 0 20px 50px;
        border-left: 2px solid #e9ecef;
    }
    
    .timeline-item:last-child {
        border-left: 2px solid transparent;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -7px;
        top: 25px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #667eea;
        border: 3px solid white;
        box-shadow: 0 0 0 2px #667eea;
    }
    
    .timeline-item:first-child::before {
        background: #28a745;
        box-shadow: 0 0 0 2px #28a745;
    }
    
    .timeline-content {
        background: white;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #f1f3f4;
        transition: all 0.3s ease;
    }
    
    .timeline-content:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    
    .timeline-time {
        color: #667eea;
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 8px;
    }
    
    .timeline-message {
        color: #2c3e50;
        font-size: 1rem;
        line-height: 1.5;
        margin-bottom: 5px;
    }
    
    .timeline-location {
        color: #6c757d;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .no-data {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }
    
    .no-data-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 20px;
        background: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #adb5bd;
    }
    
    .alert-custom {
        border: none;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
    
    @media (max-width: 768px) {
        .hero-content h1 {
            font-size: 2.5rem;
        }
        
        .hero-content p {
            font-size: 1.1rem;
        }
        
        .tracking-form-section {
            margin: -30px 20px 0;
            padding: 30px 25px;
        }
        
        .info-grid {
            grid-template-columns: 1fr;
        }
        
        .timeline-item {
            padding-left: 40px;
        }
    }
</style>

<main class="main">
    <!-- Hero Section -->
    <div class="track-hero">
        <div class="container">
            <div class="hero-content">
                <h1>Track Your Order</h1>
                <p>Enter your AWB number or Order ID to get real-time tracking information</p>
            </div>
        </div>
    </div>

    <!-- Tracking Form -->
    <div class="container">
        <div class="tracking-form-section">
            <h3 class="form-title">Enter Tracking Information</h3>
            
            <form action="{{ route('seller.order.details') }}" method="GET">
                @csrf
                <div class="track-input-wrapper">
                    <input type="text" 
                           name="awb" 
                           class="track-input" 
                           placeholder="Enter AWB Number or Order ID" 
                           required
                           value="{{ request('awb') }}">
                </div>
                <button type="submit" class="track-btn">
                    <i class="fas fa-search me-2"></i>Track Order
                </button>
            </form>

            <!-- Alert Messages -->
            @if(session('success'))
                <div class="alert-custom alert-success">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert-custom alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Tracking Results -->
    <div class="tracking-results">
        <!-- General Shipment Tracking -->
        @if(!empty($shipment))
            <div class="tracking-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-shipping-fast"></i>
                        Shipment Tracking Details
                        <span class="courier-badge xpressbees">XpressBees</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Order Number</span>
                            <p class="info-value">{{ $shipment['order_number'] ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item">
                            <span class="info-label">AWB Number</span>
                            <p class="info-value">{{ $shipment['awb_number'] ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="status-badge bg-info text-dark">{{ ucfirst($shipment['status'] ?? 'N/A') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Order ID</span>
                            <p class="info-value">{{ $shipment['order_id'] ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Created Date</span>
                            <p class="info-value">{{ $shipment['created'] ?? 'N/A' }}</p>
                        </div>
                    </div>

                    @if(!empty($shipment['history']))
                        <div class="timeline-section">
                            <div class="timeline-header">
                                <i class="fas fa-history"></i>
                                Tracking History
                            </div>
                            <div class="timeline">
                                @foreach($shipment['history'] as $event)
                                    <div class="timeline-item">
                                        <div class="timeline-content">
                                            <div class="timeline-time">{{ $event['event_time'] }}</div>
                                            <div class="timeline-message">{{ $event['message'] }}</div>
                                            <div class="timeline-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                {{ $event['location'] }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="no-data">
                            <div class="no-data-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <p>No tracking history available at the moment.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Shadowfax Tracking -->
        @if(!empty($Shadowfax))
            <div class="tracking-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-bolt"></i>
                        Shipment Tracking Details
                        <span class="courier-badge shadowfax">Shadowfax</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Client Order Number</span>
                            <p class="info-value">{{ $Shadowfax['client_order_number'] ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item">
                            <span class="info-label">AWB (Client Request ID)</span>
                            <p class="info-value">{{ $Shadowfax['client_request_id'] ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="status-badge bg-warning text-dark">{{ ucfirst($Shadowfax['status'] ?? 'N/A') }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Request Type</span>
                            <p class="info-value">{{ $Shadowfax['request_type'] ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pickup Type</span>
                            <p class="info-value">{{ $Shadowfax['pickup_type'] ?? 'N/A' }}</p>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Scheduled Date</span>
                            <p class="info-value">{{ isset($Shadowfax['scheduled_date']) ? \Carbon\Carbon::createFromTimestampMs($Shadowfax['scheduled_date'])->format('M d, Y H:i A') : 'N/A' }}</p>
                        </div>
                    </div>

                    @if(!empty($Shadowfax['pickup_request_state_histories']))
                        <div class="timeline-section">
                            <div class="timeline-header">
                                <i class="fas fa-history"></i>
                                Tracking History
                            </div>
                            <div class="timeline">
                                @foreach($Shadowfax['pickup_request_state_histories'] as $event)
                                    <div class="timeline-item">
                                        <div class="timeline-content">
                                            <div class="timeline-time">
                                                {{ isset($event['created_at']) ? \Carbon\Carbon::parse($event['created_at'])->format('M d, Y H:i A') : 'N/A' }}
                                            </div>
                                            <div class="timeline-message">
                                                <strong>Status:</strong> {{ $event['state'] ?? 'N/A' }}
                                            </div>
                                            <div class="timeline-location">
                                                <i class="fas fa-info-circle"></i>
                                                {{ $event['state_description'] ?? 'No details' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="no-data">
                            <div class="no-data-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <p>No tracking history available at the moment.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Delhivery B2C Tracking -->
        @if(!empty($trackWithDelhiveryB2C))
            <div class="tracking-card">
                <div class="card-header">
                    <h5>
                        <i class="fas fa-truck"></i>
                        Shipment Tracking Details
                        <span class="courier-badge delhivery">Delhivery</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Order Number</span>
                            <p class="info-value">{{ $trackWithDelhiveryB2C['order_number'] }}</p>
                        </div>
                        <div class="info-item">
                            <span class="info-label">AWB Number</span>
                            <p class="info-value">{{ $trackWithDelhiveryB2C['awb_number'] }}</p>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="status-badge bg-success">{{ ucfirst($trackWithDelhiveryB2C['status']) }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Order ID</span>
                            <p class="info-value">{{ $trackWithDelhiveryB2C['order_id'] }}</p>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Created Date</span>
                            <p class="info-value">{{ $trackWithDelhiveryB2C['created'] }}</p>
                        </div>
                    </div>

                    @if(!empty($trackWithDelhiveryB2C['history']))
                        <div class="timeline-section">
                            <div class="timeline-header">
                                <i class="fas fa-history"></i>
                                Tracking History
                            </div>
                            <div class="timeline">
                                @foreach($trackWithDelhiveryB2C['history'] as $event)
                                    <div class="timeline-item">
                                        <div class="timeline-content">
                                            <div class="timeline-time">{{ $event['event_time'] }}</div>
                                            <div class="timeline-message">{{ $event['message'] }}</div>
                                            <div class="timeline-location">
                                                <i class="fas fa-map-marker-alt"></i>
                                                {{ $event['location'] }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="no-data">
                            <div class="no-data-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <p>No tracking history available at the moment.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</main>

@endsection


