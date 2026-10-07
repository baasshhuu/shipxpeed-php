@extends('layouts.app')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/summernote/summernote.min.css') }}">
<style>
    .form-section {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 20px;
        background-color: #f9f9f9;
    }
    .section-title {
        font-weight: bold;
        margin-bottom: 20px;
        color: #333;
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
    }
</style>
@endsection

@section('content')
<div class="card mb-3" style="padding: 15px 4px;">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Zone Price Setting :: Add/Update</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('zone.pricesetting.add') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-plus"></i> Add New
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <!-- Manual Entry Form -->
        <div class="form-section">
            <div class="section-title">Manual Entry Form</div>
            <form class="row" method="POST" action="{{ route('zone.pricesetting.save') }}" enctype='multipart/form-data'>
                @csrf
                
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="seller_id">Select Seller *</label>
                    <select class="form-control @error('seller_id') is-invalid @enderror" id="seller_id" name="seller_id" required>
                        <option value="">-- Select Seller --</option>
                        @foreach($SellerList as $seller)
                            <option value="{{ $seller->id }}" {{ old('seller_id', $selectedSeller?->id) == $seller->id ? 'selected' : '' }}>
                                {{ $seller->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('seller_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="zone">Zone *</label>
                    <input type="text" class="form-control @error('zone') is-invalid @enderror" 
                           id="zone" name="zone" value="{{ old('zone') }}" 
                           placeholder="Enter Zone (e.g., Zone A, Metro, etc.)" required>
                    @error('zone')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="LogisticProvider">Logistic Provider *</label>
                    <select class="form-control @error('LogisticProvider') is-invalid @enderror" 
                            id="LogisticProvider" name="LogisticProvider" required>
                        <option value="">-- Select Logistic Provider --</option>
                        @foreach($LogisticProviders as $provider)
                            <option value="{{ $provider }}" {{ old('LogisticProvider') == $provider ? 'selected' : '' }}>
                                {{ $provider }}
                            </option>
                        @endforeach
                    </select>
                    @error('LogisticProvider')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="cod_price">COD Price *</label>
                    <input type="number" step="0.01" class="form-control @error('cod_price') is-invalid @enderror" 
                           id="cod_price" name="cod_price" value="{{ old('cod_price') }}" 
                           placeholder="Enter COD Price" required>
                    @error('cod_price')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="cod_fix_price">COD Fixed Price *</label>
                    <input type="number" step="0.01" class="form-control @error('cod_fix_price') is-invalid @enderror" 
                           id="cod_fix_price" name="cod_fix_price" value="{{ old('cod_fix_price') }}" 
                           placeholder="Enter COD Fixed Price" required>
                    @error('cod_fix_price')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="prepaid_price">Prepaid Price *</label>
                    <input type="number" step="0.01" class="form-control @error('prepaid_price') is-invalid @enderror" 
                           id="prepaid_price" name="prepaid_price" value="{{ old('prepaid_price') }}" 
                           placeholder="Enter Prepaid Price" required>
                    @error('prepaid_price')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="prepaid_fix_price">Prepaid Fixed Price *</label>
                    <input type="number" step="0.01" class="form-control @error('prepaid_fix_price') is-invalid @enderror" 
                           id="prepaid_fix_price" name="prepaid_fix_price" value="{{ old('prepaid_fix_price') }}" 
                           placeholder="Enter Prepaid Fixed Price" required>
                    @error('prepaid_fix_price')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="cod_charge_parsent">COD Charge Percentage *</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control @error('cod_charge_parsent') is-invalid @enderror" 
                           id="cod_charge_parsent" name="cod_charge_parsent" value="{{ old('cod_charge_parsent') }}" 
                           placeholder="Enter COD Charge Percentage" required>
                    @error('cod_charge_parsent')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>



                          <div class="col-lg-6 mt-2">
                    <label class="form-label" for="rto_credit">RTO Credit *</label>
                    <input type="number" step="0.01" class="form-control @error('rto_credit') is-invalid @enderror" 
                           id="rto_credit" name="rto_credit" value="{{ old('rto_credit') }}" 
                           placeholder="Enter RTO Credit" required>
                    @error('rto_credit')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-12 mt-3 d-flex justify-content-start">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-save"></i> Save Zone Price Setting
                    </button>
                </div>
            </form>
        </div>

        <!-- Excel Upload Form -->
        <div class="form-section">
            <div class="section-title">Upload Excel File</div>
            <form class="row" method="POST" action="{{ route('zone.pricesetting.upload.excel') }}" enctype="multipart/form-data">
                @csrf
                
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="excel_seller_id">Select Seller for Excel Upload *</label>
                    <select class="form-control @error('seller_id') is-invalid @enderror" id="excel_seller_id" name="seller_id" required>
                        <option value="">-- Select Seller --</option>
                        @foreach($SellerList as $seller)
                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                        @endforeach
                    </select>
                    @error('seller_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="excel_file">Upload Excel File *</label>
                    <input type="file" class="form-control @error('excel_file') is-invalid @enderror" 
                           id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                    @error('excel_file')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">
                        Accepted formats: .xlsx, .xls, .csv (Max size: 2MB)
                    </small>
                </div>

                <div class="col-lg-12 mt-3">
                    <div class="alert alert-info">
                        <strong>Excel Format Requirements:</strong><br>
                        Headers: zone, logistic_provider, cod_price, cod_fix_price, prepaid_price, prepaid_fix_price, cod_charge_parsent<br>
                        <div class="mt-2">
                            <strong>📋 Template includes:</strong><br>
                            • <strong>5 Zones:</strong> A, B, C, D, E<br>
                            • <strong>{{ count($LogisticProviders) }} Logistic Providers:</strong> All available providers<br>
                            • <strong>Total Rows:</strong> {{ 5 * count($LogisticProviders) }} (5 zones × {{ count($LogisticProviders) }} providers)<br>
                            • <strong>Sample Pricing:</strong> Pre-filled with sample values that you can modify
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                The downloaded template will have all zones and logistic providers pre-populated. 
                                Simply modify the pricing values and upload the file.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 mt-3 d-flex justify-content-start">
                    <button class="btn btn-success me-2" type="submit">
                        <i class="fas fa-upload"></i> Upload Excel File
                    </button>
                    <a href="{{ route('zone.pricesetting.download.template') }}" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> Download Excel Template
                    </a>
                </div>
            </form>
        </div>

        <!-- Existing Data Display -->
        @if($selectedSeller && count($existingPrices) > 0)
        <div class="form-section">
            <div class="section-title">Existing Zone Price Settings for {{ $selectedSeller->name }}</div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Zone</th>
                            <th>Logistic Provider</th>
                            <th>COD Price</th>
                            <th>COD Fixed Price</th>
                            <th>Prepaid Price</th>
                            <th>Prepaid Fixed Price</th>
                            <th>COD Charge %</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($existingPrices as $price)
                        <tr>
                            <td>{{ $price->zone }}</td>
                            <td>{{ $price->Provider->name ?? 'N/A' }}</td>
                            <td>₹{{ number_format($price->cod_price, 2) }}</td>
                            <td>₹{{ number_format($price->cod_fix_price, 2) }}</td>
                            <td>₹{{ number_format($price->prepaid_price, 2) }}</td>
                            <td>₹{{ number_format($price->prepaid_fix_price, 2) }}</td>
                            <td>{{ $price->cod_charge_parsent }}%</td>
                            <td>
                                <a href="{{ route('zone.pricesetting.edit', $price->id) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete({{ $price->id }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    // Auto-load prices when seller is selected in manual form
    document.getElementById('seller_id').addEventListener('change', function() {
        if(this.value) {
            window.location.href = "{{ route('zone.pricesetting.add') }}?seller_id=" + this.value;
        }
    });

    function confirmDelete(id) {
        if(confirm('Are you sure you want to delete this zone price setting?')) {
            window.location.href = "{{ url('admin/zone-price-setting/delete') }}/" + id;
        }
    }
</script>
@endsection