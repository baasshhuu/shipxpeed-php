@extends('layouts.sellerdash')

@section('content')
<div class="pc-container">
    <div class="pc-content">
        <div class="top-toggler-container">
            <div class="top-toggler shadow-sm">
                <a href="{{ route('seller.ratecards') }}" class="active text-decoration-none"><i class="ti ti-dashboard"></i> Rate Calculator</a>
                <a href="{{ route('seller.shipmentpricelist') }}" class="text-decoration-none"><i class="ti ti-shopping-cart"></i> Shipment Price List</a>
                <a href="{{ route('seller.activitylog') }}" class="text-decoration-none"><i class="ti ti-alert-circle"></i> Activity Logs</a>
                <a href="#" class="text-decoration-none"><i class="ti ti-wallet"></i> Courier Manage</a>
                <a href="#" class="text-decoration-none"><i class="ti ti-settings"></i> Reports Download</a>
            </div>
        </div>

        <div class="mt-4">
            <div class="card p-4 shadow-sm">
                <form id="rate-calculator-form" method="POST" action="{{ route('seller.check.rate') }}">
                    @csrf
                    <input type="text" name="origin" class="form-control mt-4" placeholder="Origin Pincode" required>
                    <input type="text" name="destination" class="form-control mt-4" placeholder="Destination Pincode" required>
                    <input type="text" name="order_amount" class="form-control mt-4" placeholder="Invoice Value" required>
                    <input type="text" name="weight" class="form-control mt-4" placeholder="Weight (in grams)" required>
                    <input type="text" name="length" class="form-control mt-4" placeholder="Length" required>
                    <input type="text" name="breadth" class="form-control mt-4" placeholder="Breadth" required>
                    <input type="text" name="height" class="form-control mt-4" placeholder="Height" required>

                    <select name="payment_type" class="form-select mt-4" required>
                        <option value="cod">Cash on Delivery</option>
                        <option value="prepaid">Prepaid</option>
                    </select>

                    <button type="submit" class="btn btn-primary mt-4">Submit</button>
                </form>

                @if (session('rate_data') && is_array(session('rate_data')))
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
                            @foreach (session('rate_data') as $index => $item)
                                <tr @if($item['name'] === 'Shadowfax' || $item['name'] === 'Delhivery B2C') class="table-info" @endif>
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
        </div>
    </div>
</div>

<style>
    #api-response table {
        font-size: 0.95rem;
    }
    #api-response .badge {
        padding: 0.5em 0.75em;
        font-size: 1em;
    }
    #api-response .table th,
    #api-response .table td {
        vertical-align: middle;
    }
    .table-info {
        background-color: #e8f4ff !important;
    }
</style>
@endsection


{{-- @extends('layouts.sellerdash')

@section('content')
    <div class="pc-container">
        <div class="pc-content">
            <div class="top-toggler-container">
                <div class="top-toggler shadow-sm">
                    <a href="{{ route('seller.ratecards') }}" class="active text-decoration-none"><i class="ti ti-dashboard"></i> Rate Calculator</a>
                    <a href="{{ route('seller.shipmentpricelist') }}" class="text-decoration-none"><i class="ti ti-shopping-cart"></i> Shipment Price List</a>
                    <a href="{{ route('seller.activitylog') }}" class="text-decoration-none"><i class="ti ti-alert-circle"></i> Activity Logs</a>
                    <a href="courier-manage.html" class="text-decoration-none"><i class="ti ti-wallet"></i> Courier Manage</a>
                    <a href="report-download.html" class="text-decoration-none"><i class="ti ti-settings"></i> Reports Download</a>
                </div>
            </div>

            <div class="mt-4">
                <div class="card p-4 shadow-sm">
                    <div class="toggle-button-section mb-3">
                        <div class="toggle-button">
                            <a href="#" class="toggle-option active flex-fill text-center p-2" data-type="domestic">Domestic</a>
                            <a href="#" class="toggle-option flex-fill text-center p-2" data-type="international">International</a>
                        </div>
                    </div>

                    <form id="rate-calculator-form" method="POST" action="{{ route('seller.check.rate') }}">
                        @csrf
                        <input type="text" name="origin" class="form-control mt-4" placeholder="Origin Pincode" required>
                        <input type="text" name="destination" class="form-control mt-4" placeholder="Destination Pincode" required>
                        <input type="text" name="order_amount" class="form-control mt-4" placeholder="Invoice Value" required>
                        <input type="text" name="weight" class="form-control mt-4" placeholder="Weight (in grams)" required>
                        <input type="text" name="length" class="form-control mt-4" placeholder="Length" required>
                        <input type="text" name="breadth" class="form-control mt-4" placeholder="Breadth" required>
                        <input type="text" name="height" class="form-control mt-4" placeholder="Height" required>

                        <select name="payment_type" class="form-select mt-4" required>
                            <option value="cod">Cash on Delivery</option>
                            <option value="prepaid">Prepaid</option>
                        </select>

                        <button type="submit" class="btn btn-primary mt-4">Submit</button>
                    </form>




                    @if (session('rate_data') && is_array(session('rate_data')))
    <div id="api-response" class="mt-5 table-responsive">
        <h4>Rate Details:</h4>
        <table class="table table-bordered table-striped text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Service Name</th>
                    {{-- <th>Freight Charges<br><small>(with seller % and GST)</small></th> --}}
                     <th>Freight Charges</th>

                    <th>COD Charges</th>
                    <th>Total Charges</th>
                    <th>Min Weight (g)</th>
                    <th>Chargeable Weight (g)</th>
                </tr>
            </thead>
            <tbody>
                @foreach (session('rate_data') as $index => $item)
                    <tr @if($item['name'] === 'Shadowfax') class="table-info" @endif>
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
            </div>
        </div>
    </div>

    <style>
        #api-response table {
            font-size: 0.95rem;
        }

        #api-response .badge {
            padding: 0.5em 0.75em;
            font-size: 1em;
        }

        #api-response .table th,
        #api-response .table td {
            vertical-align: middle;
        }

        .table-info {
            background-color: #e8f4ff !important;
        }
    </style>
@endsection --}}


{{-- @extends('layouts.sellerdash')

@section('content')
    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <div class="top-toggler-container">
                <div class="top-toggler shadow-sm">
                    <a href="{{ route('seller.ratecards') }}" class="active text-decoration-none"><i
                            class="ti ti-dashboard"></i> Rate Calculator</a>
                    <a href="{{ route('seller.shipmentpricelist') }}" class="text-decoration-none"><i
                            class="ti ti-shopping-cart"></i> Shipment Price List</a>
                    <a href="{{ route('seller.activitylog') }}" class="text-decoration-none"><i
                            class="ti ti-alert-circle"></i> Activity Logs</a>
                    <a href="courier-manage.html" class="text-decoration-none"><i class="ti ti-wallet"></i> Courier
                        Manage</a>
                    <a href="report-download.html" class="text-decoration-none"><i class="ti ti-settings"></i> Reports
                        Download</a>
                </div>
            </div>

            <div class="mt-4">
                <div class="card p-4 shadow-sm">
                    <!-- Toggle Button for Domestic/International -->
                    <div class="toggle-button-section mb-3">
                        <div class="toggle-button">
                            <a href="#" class="toggle-option active flex-fill text-center p-2"
                                data-type="domestic">Domestic</a>
                            <a href="#" class="toggle-option flex-fill text-center p-2"
                                data-type="international">International</a>
                        </div>
                    </div>

                    <!-- Form -->
                    <form id="rate-calculator-form" method="POST" action="{{ route('seller.check.rate') }}">
                        @csrf
                        <input type="text" name="origin" class="form-control mt-4" placeholder="Origin Pincode" required>
                        <input type="text" name="destination" class="form-control mt-4" placeholder="Destination Pincode" required>
                        <input type="text" name="order_amount" class="form-control mt-4" placeholder="Invoice Value" required>
                        <input type="text" name="weight" class="form-control mt-4" placeholder="Weight (in grams)" required>
                        <input type="text" name="length" class="form-control mt-4" placeholder="Length" required>
                        <input type="text" name="breadth" class="form-control mt-4" placeholder="Breadth" required>
                        <input type="text" name="height" class="form-control mt-4" placeholder="Height" required>

                        <select name="payment_type" class="form-select mt-4" required>
                            <option value="cod">Cash on Delivery</option>
                            <option value="prepaid">Prepaid</option>
                        </select>

                        <button type="submit" class="btn btn-primary mt-4">Submit</button>
                    </form>

                    <!-- Response Table -->
                    @if (session('rate_data') && is_array(session('rate_data')))
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
                                    @foreach (session('rate_data') as $index => $item)
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
                    <div id="api-response" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>

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
@endsection --}}
