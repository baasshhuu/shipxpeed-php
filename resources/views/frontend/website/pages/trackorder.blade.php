@extends('frontend.website.layout.index')

@section('main_contant')
<main class="main">
    <!-- Hero Section -->
    <div class="track-hero" style="background: url('{{ asset('assets/website/img/herosection.PNG') }}') no-repeat center center; background-size: cover;">
        <div class="container">
            <div class="row align-items-center">
                
                <!-- Left Content -->
                <div class="col-md-6 hero-text">
                    <h1 style="color:white;">Track Your Order Anytime &amp; Anywhere</h1>
                    <p style="color:white;">Just enter your AWB Number or Order ID and track on the go!</p>
                
                </div>

                <!-- Right Image -->
                <div class="col-md-6 position-relative hero-image">
                    <img src="{{ asset('assets/website/img/track.png') }}" alt="Package Delivery"
                        class="img-fluid fade-in">
                    <div class="circle one"></div>
                    <div class="circle two"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tracking Form -->
    <div class="tracking-form mt-4">
        <div class="container d-flex justify-content-center align-items-center">
            <form action="{{ route('order.details') }}" method="GET" class="input-group w-75 w-md-50">
                @csrf
                <input type="text" name="awb" class="form-control" placeholder="Enter AWB Number" required>
                <button type="submit" class="btn track-btn">Track</button>
            </form>
        </div>

        <div class="container mt-3">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Tracking Result -->
    @if(!empty($shipment))
        <div class="container mt-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5>Shipment Tracking Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Order Number:</strong> {{ $shipment['order_number'] }}</p>
                    <p><strong>AWB Number:</strong> {{ $shipment['awb_number'] }}</p>
                    <p><strong>Status:</strong>
                        <span class="badge bg-info text-dark">{{ ucfirst($shipment['status']) }}</span>
                    </p>
                    <p><strong>Order ID:</strong> {{ $shipment['order_id'] }}</p>
                    <p><strong>Created:</strong> {{ $shipment['created'] }}</p>

                    @if(!empty($shipment['history']))
                        <h6 class="mt-4">Tracking History:</h6>
                        <ul class="list-group">
                            @foreach($shipment['history'] as $event)
                                <li class="list-group-item">
                                    <strong>{{ $event['event_time'] }}</strong> - {{ $event['message'] }}<br>
                                    <small class="text-muted">{{ $event['location'] }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-muted">No tracking history available.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif



@if(!empty($Shadowfax))
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5>Shadowfax Shipment Tracking Details</h5>
            </div>
            <div class="card-body">
                <p><strong>Client Order Number:</strong> {{ $Shadowfax['client_order_number'] ?? 'N/A' }}</p>
                <p><strong>AWB (Client Request ID):</strong> {{ $Shadowfax['client_request_id'] ?? 'N/A' }}</p>
                <p><strong>Status:</strong>
                    <span class="badge bg-info text-dark">{{ ucfirst($Shadowfax['status'] ?? 'N/A') }}</span>
                </p>
                <p><strong>Request Type:</strong> {{ $Shadowfax['request_type'] ?? 'N/A' }}</p>
                <p><strong>Pickup Type:</strong> {{ $Shadowfax['pickup_type'] ?? 'N/A' }}</p>
                <p><strong>Scheduled Date:</strong>
                    {{ isset($Shadowfax['scheduled_date']) ? \Carbon\Carbon::createFromTimestampMs($Shadowfax['scheduled_date'])->toDayDateTimeString() : 'N/A' }}
                </p>
                <p><strong>Date Created:</strong>
                    {{ isset($Shadowfax['date_created']) ? \Carbon\Carbon::createFromTimestampMs($Shadowfax['date_created'])->toDayDateTimeString() : 'N/A' }}
                </p>
                <p><strong>Last Status Updated:</strong>
                    {{ \Carbon\Carbon::parse($Shadowfax['status_last_updated_at'] ?? '')->toDayDateTimeString() ?? 'N/A' }}
                </p>

                <h6 class="mt-4">Tracking History:</h6>
                @if(!empty($Shadowfax['pickup_request_state_histories']))
                    <ul class="list-group">
                        @foreach($Shadowfax['pickup_request_state_histories'] as $event)
                            <li class="list-group-item">
                                <strong>Status:</strong> {{ $event['state'] ?? 'N/A' }} <br>
                                <strong>Time:</strong>
                                {{ isset($event['created_at']) ? \Carbon\Carbon::parse($event['created_at'])->toDayDateTimeString() : 'N/A' }} <br>
                                <small class="text-muted">Description: {{ $event['state_description'] ?? 'No details' }}</small>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 text-muted">No tracking history available.</p>
                @endif
            </div>
        </div>
    </div>
@endif

@if(!empty($trackWithDelhiveryB2C))
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5>Shipment Tracking Details</h5>
            </div>
            <div class="card-body">
                <p><strong>Order Number:</strong> {{ $trackWithDelhiveryB2C['order_number'] }}</p>
                <p><strong>AWB Number:</strong> {{ $trackWithDelhiveryB2C['awb_number'] }}</p>
                <p><strong>Status:</strong>
                    <span class="badge bg-info text-dark">{{ ucfirst($trackWithDelhiveryB2C['status']) }}</span>
                </p>
                <p><strong>Order ID:</strong> {{ $trackWithDelhiveryB2C['order_id'] }}</p>
                <p><strong>Created:</strong> {{ $trackWithDelhiveryB2C['created'] }}</p>

                @if(!empty($trackWithDelhiveryB2C['history']))
                    <h6 class="mt-4">Tracking History:</h6>
                    <ul class="list-group">
                        @foreach($trackWithDelhiveryB2C['history'] as $event)
                            <li class="list-group-item">
                                <strong>{{ $event['event_time'] }}</strong> - {{ $event['message'] }}<br>
                                <small class="text-muted">{{ $event['location'] }}</small>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 text-muted">No tracking history available.</p>
                @endif
            </div>
        </div>
    </div>
@endif




</main>
@endsection
