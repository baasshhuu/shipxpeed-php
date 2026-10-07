@extends('layouts.sellerdash')

@section('content')
    <div class="pc-container">
        <div class="pc-content">
            <div class="card">
                <div class="card-header">
                    <h4>Create Manifest</h4>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('api_response'))
                        <div class="card mt-4">
                            <div class="card-header">API Response</div>
                            <div class="card-body">
                                <pre>{{ json_encode(session('api_response'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </div>
                    @endif

                    @if (session('api_error'))
                        <div class="card mt-4">
                            <div class="card-header text-danger">API Error</div>
                            <div class="card-body">
                                <pre>{{ session('api_error') }}</pre>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('seller.manifest.create') }}">
                        @csrf

                        <h5 class="mb-3">Pickup Location</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Address</label>
                                <input type="text" name="pickup_location[add]" class="form-control"
                                    value="Unit No 7, Plot No 71E to T, GOVERNMENT INDUSTRIAL ESTATE, Behind Garuda Petrol Pump, Charkop, KANDIVALI WEST,Mumbai, Maharashtra,">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">City</label>
                                <input type="text" name="pickup_location[city]" class="form-control" value="Mumbai">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">State</label>
                                <input type="text" name="pickup_location[state]" class="form-control" value="Maharastra">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">PIN</label>
                                <input type="text" name="pickup_location[pin]" class="form-control" value="400067">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Country</label>
                                <input type="text" name="pickup_location[country]" class="form-control" value="India">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="pickup_location[phone]" class="form-control" value="7774855283">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="pickup_location[name]" class="form-control"
                                    value="BRILLARE SURFACE">
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Shipment Details</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Receiver Address</label>
                                <input type="text" name="shipments[0][add]" class="form-control"
                                    value="MRH- C 113, Ward no - 18. Below Sumi Church, Merhulietsa School Road">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">City</label>
                                <input type="text" name="shipments[0][city]" class="form-control" value="Kohima">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">State</label>
                                <input type="text" name="shipments[0][state]" class="form-control" value="Nagaland">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">PIN</label>
                                <input type="text" name="shipments[0][pin]" class="form-control" value="797001">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Country</label>
                                <input type="text" name="shipments[0][country]" class="form-control" value="India">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="shipments[0][phone]" class="form-control" value="9603304294">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="shipments[0][name]" class="form-control" value="Asen Jamir">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Order ID</label>
                                <input type="text" name="shipments[0][order]" class="form-control" value="528323">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Payment Mode</label>
                                <input type="text" name="shipments[0][payment_mode]" class="form-control"
                                    value="Prepaid">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Quantity</label>
                                <input type="text" name="shipments[0][quantity]" class="form-control" value="1">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Total Amount</label>
                                <input type="text" name="shipments[0][total_amount]" class="form-control"
                                    value="750">
                            </div>
                        </div>

                        <h5 class="mt-4 mb-3">Return Details</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Return Name</label>
                                <input type="text" name="shipments[0][return_name]" class="form-control"
                                    value="Unit No 7,GOVERNMENT INDUSTRIAL ESTATE">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Return Address</label>
                                <input type="text" name="shipments[0][return_add]" class="form-control"
                                    value="Unit No 7, Plot No 71E to T, GOVERNMENT INDUSTRIAL ESTATE, Behind Garuda Petrol Pump, Charkop, KANDIVALI WEST,Mumbai, Maharashtra, ">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Return PIN</label>
                                <input type="text" name="shipments[0][return_pin]" class="form-control"
                                    value="400067">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Return City</label>
                                <input type="text" name="shipments[0][return_city]" class="form-control"
                                    value="Mumbai">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Return State</label>
                                <input type="text" name="shipments[0][return_state]" class="form-control"
                                    value="Maharastra">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Return Country</label>
                                <input type="text" name="shipments[0][return_country]" class="form-control"
                                    value="India">
                            </div>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Return Phone</label>
                                <input type="text" name="shipments[0][return_phone]" class="form-control"
                                    value="7774855283">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Create Manifest</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
