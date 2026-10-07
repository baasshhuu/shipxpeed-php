@extends('frontend.website.layout.index')

@section('main_contant')
<main class="main">
    <div 
        class="rate-calculator-hero border-bottom"
        data-aos="fade-up"
        style="
            background: url('{{ asset('assets/website/img/herosection.png') }}') center center/cover no-repeat; 
            min-height: 340px;
            padding: 56px 20px 48px 20px;
            box-shadow: 0 8px 28px 0 rgba(24,131,187,0.08);">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <!-- Left: Content -->
                <div class="col-md-6">
                    <div class="text-left" style="max-width: 520px; margin: auto;">
                        <h1 style="font-size:2.4rem; font-weight:700; color:#fff; letter-spacing:-1px; margin-bottom: 18px;">
                            <span class="highlight" style="color:#FFCE49;">Calculate</span>
                            <span style="color:#fff;"> Shipping Rates Instantly</span>
                        </h1>
                        <p class="mb-4" style="color: #e7eaf3; font-size: 1.18rem;">
                            Get real insights into shipping costs within a few seconds
                        </p>
                        <a href="#rate-calc-form" class="cta-btn btn btn-primary shadow-sm px-5 py-2" 
                        style="font-weight:600; border-radius: 7px; background: #FFCE49; color:#16436c; border: none; font-size:1.1rem; letter-spacing:0.3px;">
                            Calculate Now
                        </a>
                    </div>
                </div>
                <!-- Right: Image -->
                <div class="col-md-6 d-flex justify-content-center">
                    <img 
                        src="{{ asset('assets/website/img/calculator.png') }}" 
                        alt="Rate Calculator" 
                        style="max-width: 380px; width:100%; border-radius: 14px;
                        ">
                </div>
            </div>
        </div>
    </div>

    <div class="rate-calculator-box row">
        <!-- Left Form Section -->
        <div class="col-md-7 form-container" data-aos="fade-right">
            <h2 class="fw-bold mb-4">Shipping Rate Calculator</h2>

            <!-- Alerts -->
            @if(session('success'))
                <div class="alert alert-success mt-3">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mt-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Professional Two-Column Form -->
            <form method="POST" action="{{ route('seller.check.rate') }}" class="rate-calc-form">
                @csrf

                <div class="row g-3">
                    <div class="col-12">
                        <div class="form-row d-flex">
                            <label for="origin" class="col-md-4 col-form-label fw-semibold">Origin Pincode</label>
                            <div class="col-md-8">
                                <input type="text" id="origin" name="origin" class="form-control" placeholder="Enter origin pincode" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-row d-flex align-items-center">
                            <label for="destination" class="col-md-4 col-form-label fw-semibold">Destination Pincode</label>
                            <div class="col-md-8">
                                <input type="text" id="destination" name="destination" class="form-control" placeholder="Enter destination pincode" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-row d-flex align-items-center">
                            <label for="order_amount" class="col-md-4 col-form-label fw-semibold">Invoice Value</label>
                            <div class="col-md-8">
                                <input type="text" id="order_amount" name="order_amount" class="form-control" placeholder="Enter invoice value" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-row d-flex align-items-center">
                            <label for="weight" class="col-md-4 col-form-label fw-semibold">Weight (in grams)</label>
                            <div class="col-md-8">
                                <input type="text" id="weight" name="weight" class="form-control" placeholder="Enter weight in grams" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-row d-flex align-items-center">
                            <label for="length" class="col-md-4 col-form-label fw-semibold">Length (in cm)</label>
                            <div class="col-md-8">
                                <input type="text" id="length" name="length" class="form-control" placeholder="Enter length" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-row d-flex align-items-center">
                            <label for="breadth" class="col-md-4 col-form-label fw-semibold">Breadth (in cm)</label>
                            <div class="col-md-8">
                                <input type="text" id="breadth" name="breadth" class="form-control" placeholder="Enter breadth" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-row d-flex align-items-center">
                            <label for="height" class="col-md-4 col-form-label fw-semibold">Height (in cm)</label>
                            <div class="col-md-8">
                                <input type="text" id="height" name="height" class="form-control" placeholder="Enter height" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-row d-flex align-items-center">
                            <label for="payment_type" class="col-md-4 col-form-label fw-semibold">Payment Type</label>
                            <div class="col-md-8">
                                <select id="payment_type" name="payment_type" class="form-select" required>
                                    <option value="cod">Cash on Delivery</option>
                                    <option value="prepaid">Prepaid</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4 py-2" style="font-weight:600; border-radius:6px;">
                                Submit
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Response Table -->
            @if(session('rate_data') && is_array(session('rate_data')))
                <div id="api-response" class="mt-5 table-responsive">
                    <h4>Rate Details:</h4>
                    <table class="table table-bordered table-striped text-center align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Service Name</th>
                                <th>Freight Charges</th>
                                <th>COD Charges</th>
                                <th>Total Charges</th>
                                <th>Min Weight (g)</th>
                                <th>Chargeable Weight (g)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('rate_data') as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item['name'] }}</td>
                                    <td>₹{{ $item['freight_charges'] }}</td>
                                    <td>₹{{ $item['cod_charges'] }}</td>
                                    <td><span class="badge bg-success">₹{{ $item['total_charges'] }}</span></td>
                                    <td>{{ $item['min_weight'] }}</td>
                                    <td>{{ $item['chargeable_weight'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Right Image Section -->
        <div class="col-md-5 right-section d-flex align-items-center justify-content-center" data-aos="fade-left" data-aos-delay="100">
            <img src="{{ asset('assets/website/img/shipCalculator.png') }}" alt="Calculator" class="img-fluid" style="max-width: 440px;height:400px;">
        </div>
    </div>

    <!-- Enhanced UI/UX Form Styles -->
    <style>
     
        .rate-calc-form label {
            color: #174361;
            font-size: 1.09rem;
            font-weight: 600;
            margin-bottom: 0;
        }
        .rate-calc-form .form-control, 
        .rate-calc-form .form-select {
            border-radius: 6px;
            border: 1px solid #cfd8e3;
            box-shadow: none;
            transition: border-color 0.2s;
            font-size: 1rem;
            background: #fbfcfd;
        }
        .rate-calc-form .form-control:focus, 
        .rate-calc-form .form-select:focus {
            border-color: #1976d2;
            box-shadow: 0 2px 8px 0 rgba(25,118,210,0.08);
            background: #fff;
        }
        .rate-calc-form button.btn-primary {
            background: linear-gradient(92deg, #1976d2 0%, #21a1db 80%);
            color: #fff;
            border: none;
            transition: background 0.2s;
        }
        .rate-calc-form button.btn-primary:hover {
            background: linear-gradient(92deg, #21a1db 0%, #1976d2 100%);
            color: #fff;
        }
        @media (max-width: 575.98px) {
            .rate-calc-form .form-row {
                flex-direction: column;
                align-items: flex-start !important;
            }
            .rate-calc-form label,
            .rate-calc-form .col-md-4,
            .rate-calc-form .col-md-8 {
                width: 100%;
                text-align: left !important;
            }
            .rate-calc-form .text-end {
                text-align: left !important;
                width: 100%;
                margin-top: 8px;
            }
        }
    </style>
</main>

<!-- Optional: Additional Style -->
<style>
    #api-response table {
        font-size: 0.95rem;
    }

    #api-response .badge {
        padding: 0.5em 0.75em;
        font-size: 1em;
    }

    #api-response .table th, #api-response .table td {
        vertical-align: middle;
    }
</style>
@endsection
