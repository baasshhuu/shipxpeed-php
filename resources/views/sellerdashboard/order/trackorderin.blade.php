@extends('layouts.sellerdash')

@section('content')
<main class="main">
    <!-- Hero Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <!-- Left -->
                <div class="col-md-6 mb-4 mb-md-0">
                    <h1 class="fw-bold">Track Your Order</h1>
                    <p class="text-muted">Enter your AWB Number or Order ID to get real-time updates.</p>
                </div>
                <!-- Right -->
                <div class="col-md-6 text-center">
                    <img src="{{ asset('assets/website/img/track.png') }}" alt="Track Image" class="img-fluid" style="max-height: 250px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Tracking Form -->
    <section class="py-4">
        <div class="container">
            <form action="{{ route('order.details') }}" method="GET" class="row justify-content-center">
                @csrf
                <div class="col-md-6 d-flex">
                    <input type="text" name="awb" class="form-control me-2" placeholder="Enter AWB Number" required>
                    <button type="submit" class="btn btn-primary">Track</button>
                </div>
            </form>

            @if(session('success'))
                <div class="alert alert-success mt-3 text-center">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mt-3 text-center">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <!-- Tracking Result -->
    @if(!empty($shipment))
        <section class="py-4">
            <div class="container">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Shipment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-2">
                            <div class="col-md-6"><strong>Order Number:</strong> {{ $shipment['order_number'] }}</div>
                            <div class="col-md-6"><strong>AWB Number:</strong> {{ $shipment['awb_number'] }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6"><strong>Status:</strong> 
                                <span class="badge bg-info text-dark">{{ ucfirst($shipment['status']) }}</span>
                            </div>
                            <div class="col-md-6"><strong>Order ID:</strong> {{ $shipment['order_id'] }}</div>
                        </div>
                        <div class="mb-3"><strong>Created:</strong> {{ $shipment['created'] }}</div>

                        @if(!empty($shipment['history']))
                            <h6 class="mt-4">Tracking History:</h6>
                            <ul class="list-group">
                                @foreach($shipment['history'] as $event)
                                    <li class="list-group-item">
                                        <strong>{{ $event['event_time'] }}</strong> - {{ $event['message'] }}
                                        <br><small class="text-muted">{{ $event['location'] }}</small>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mt-3">No tracking history available.</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif
</main>

<style>
    .main h1 {
        font-size: 2.25rem;
    }
    .form-control {
        border-radius: 0.375rem;
    }
    .btn-primary {
        border-radius: 0.375rem;
        padding: 0.5rem 1.25rem;
    }
</style>
@endsection
