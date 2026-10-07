@extends('frontend.website.layout.index')

@section('main_contant')
<main class="main">
    <div class="track-hero">
        <div class="container">
            <div class="row align-items-center">
                <!-- Left Content -->
                <div class="col-md-6 hero-text">
                    <h1>Cancel Your Order Anytime &amp; Anywhere</h1>
                    <p>Just enter your AWB Number or Order ID and cancel it instantly!</p>
                </div>

                <!-- Right Image with Decorative Circles -->
                <div class="col-md-6 position-relative hero-image">
                    <img src="{{ asset('assets/website/img/track.png') }}" alt="Package Delivery"
                        class="img-fluid fade-in">
                    <div class="circle one"></div>
                    <div class="circle two"></div>
                </div>
            </div>
        </div>
    </div>
  {{-- Show success or error messages --}}
  <div class="container mt-3">
    @if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
    <!-- Section 2: Cancel Shipment Form -->
    <div class="tracking-form mt-4">
        <div class="container d-flex justify-content-center align-items-center">
            <form action="{{ route('cancel.shipment') }}" method="POST" class="input-group w-75 w-md-50">
                @csrf
                <input type="text" name="awb" class="form-control" placeholder="Enter AWB Number" required>
                <button type="submit" class="btn track-btn">Cancel Shipment</button>
            </form>
        </div>



        </div>
    </div>
</main>
@endsection
