@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Add Money to Seller Wallet</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('negative-balance.index') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form class="row" method="POST" action="{{ route('negative-balance.store-money') }}">
            @csrf
            
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="seller_id">Select Seller <span class="text-danger">*</span></label>
                <select class="form-select @error('seller_id') is-invalid @enderror" id="seller_id" name="seller_id" required>
                    <option value="">Choose a seller...</option>
                    @foreach($sellers as $seller)
                        <option value="{{ $seller->id }}" {{ old('seller_id', request('seller_id')) == $seller->id ? 'selected' : '' }}>
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
                <label class="form-label" for="amount">Amount <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" type="number" step="0.01" min="1" value="{{ old('amount') }}" required />
                </div>
                @error('amount')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="col-lg-12 mt-2">
                <label class="form-label" for="description">Description (Optional)</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Enter reason for adding money...">{{ old('description') }}</textarea>
                @error('description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div id="sellerInfo" class="col-lg-12 mt-3" style="display: none;">
                <div class="alert alert-info">
                    <h6>Seller Information:</h6>
                    <div id="sellerDetails"></div>
                </div>
            </div>

            <div class="col-lg-12 mt-3 d-flex justify-content-start">
                <button class="btn btn-success submitbtn" type="submit">
                    <i class="fa fa-plus me-1"></i> Add Money
                </button>
                <a href="{{ route('negative-balance.index') }}" class="btn btn-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sellerSelect = document.getElementById('seller_id');
    const sellerInfo = document.getElementById('sellerInfo');
    const sellerDetails = document.getElementById('sellerDetails');

    sellerSelect.addEventListener('change', function() {
        const sellerId = this.value;
        
        if (!sellerId) {
            sellerInfo.style.display = 'none';
            return;
        }

        // Show seller information
        const selectedOption = this.options[this.selectedIndex];
        sellerDetails.innerHTML = `
            <strong>Selected Seller:</strong> ${selectedOption.text}<br>
            <small class="text-muted">Money will be added to this seller's wallet as credit.</small>
        `;
        
        sellerInfo.style.display = 'block';
    });
});
</script>
@endpush