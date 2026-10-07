@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Edit Zone Price Setting</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('zone.price.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form class="row" method="POST" action="{{ route('zone.pricesetting.update', $zonePriceSetting->id) }}">
            @csrf
            
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="seller_id">Select Seller *</label>
                <select class="form-control @error('seller_id') is-invalid @enderror" id="seller_id" name="seller_id" required>
                    <option value="">-- Select Seller --</option>
                    @foreach($SellerList as $seller)
                        <option value="{{ $seller->id }}" {{ $zonePriceSetting->seller_id == $seller->id ? 'selected' : '' }}>
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
                       id="zone" name="zone" value="{{ old('zone', $zonePriceSetting->zone) }}" 
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
                        <option value="{{ $provider }}" {{ $zonePriceSetting->LogisticProvider == $provider ? 'selected' : '' }}>
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
                       id="cod_price" name="cod_price" value="{{ old('cod_price', $zonePriceSetting->cod_price) }}" 
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
                       id="cod_fix_price" name="cod_fix_price" value="{{ old('cod_fix_price', $zonePriceSetting->cod_fix_price) }}" 
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
                       id="prepaid_price" name="prepaid_price" value="{{ old('prepaid_price', $zonePriceSetting->prepaid_price) }}" 
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
                       id="prepaid_fix_price" name="prepaid_fix_price" value="{{ old('prepaid_fix_price', $zonePriceSetting->prepaid_fix_price) }}" 
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
                       id="cod_charge_parsent" name="cod_charge_parsent" value="{{ old('cod_charge_parsent', $zonePriceSetting->cod_charge_parsent) }}" 
                       placeholder="Enter COD Charge Percentage" required>
                @error('cod_charge_parsent')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-lg-12 mt-3 d-flex justify-content-start">
                <button class="btn btn-primary me-2" type="submit">
                    <i class="fas fa-save"></i> Update Zone Price Setting
                </button>
                <a href="{{ route('zone.price.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
