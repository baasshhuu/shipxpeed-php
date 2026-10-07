@extends('layouts.sellerdash')

@section('content')
    <div class="pc-container">
        <div class="pc-content">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="mb-4">Pincode Serviceability Checker</h2>

                    <form method="GET" action="{{ route('pincode.check') }}" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="pincode" class="form-control" placeholder="Enter Pincode" required>
                            <button class="btn btn-primary">Check</button>
                        </div>
                    </form>

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @isset($deliveryCodes)
                        @foreach ($deliveryCodes as $delivery)
                            @php $postal = $delivery['postal_code']; @endphp
                            <div class="card mb-3 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title">Pincode: {{ $postal['pin'] }} - {{ $postal['city'] }}</h5>
                                    <p class="card-text">
                                        <strong>State:</strong> {{ $postal['state_code'] }}<br>
                                        <strong>District:</strong> {{ $postal['district'] }}<br>
                                        <strong>COD Available:</strong> {{ $postal['cod'] == 'Y' ? 'Yes' : 'No' }}<br>
                                        <strong>Prepaid Available:</strong> {{ $postal['pre_paid'] == 'Y' ? 'Yes' : 'No' }}<br>
                                        <strong>Pickup Available:</strong> {{ $postal['pickup'] == 'Y' ? 'Yes' : 'No' }}<br>
                                        <strong>ODA:</strong> {{ $postal['is_oda'] == 'Y' ? 'Yes' : 'No' }}<br>
                                        <strong>Inc:</strong> {{ $postal['inc'] }}
                                    </p>

                                    <h6>Centers:</h6>
                                    <ul class="list-group list-group-flush">
                                        @foreach ($postal['center'] as $center)
                                            <li class="list-group-item">
                                                <strong>Code:</strong> {{ $center['code'] ?? 'N/A' }}<br>
                                                <strong>Name:</strong> {{ $center['cn'] ?? 'N/A' }}<br>
                                                <strong>Updated By:</strong> {{ $center['u'] ?? 'N/A' }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    @endisset
                </div>

            </div>
        </div>
    </div>

@endsection
