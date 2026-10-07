@extends('layouts.app')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/summernote/summernote.min.css') }}">
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Price Setting :: Price Edit</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('pricesetting') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form class="row" method="post" action="{{ route('pricesetting.price.update', $priceSetting->id) }}" enctype="multipart/form-data">
            @csrf
            {{-- @method('PUT') --}}

            <div class="col-lg-6 mt-2">
                <label class="form-label" for="seller_id">Name</label>
                <select class="form-control @error('seller_id') is-invalid @enderror" id="seller_id" name="seller_id">
                    <option value="">-- Select Seller --</option>
                    @foreach($SellerList as $seller)
                        <option value="{{ $seller->id }}" {{ $seller->id == $priceSetting->seller_id ? 'selected' : '' }}>
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
                        <label class="form-label" for="LogisticProvider">Courier Services/Partners</label>
                        <select class="form-control @error('LogisticProvider') is-invalid @enderror" id="LogisticProvider" name="LogisticProvider">
                            <option value="">-- Select Services --</option>
                            @foreach($LogisticProvider as $Provider)
                              <option value="{{ $Provider->id }}" {{ $Provider->id == $priceSetting->LogisticProvider ? 'selected' : '' }}>
                            {{ $Provider->name }}
                        </option>                            @endforeach
                        </select>
                        @error('LogisticProvider')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>


            <div class="col-lg-6 mt-2">
                <label class="form-label" for="shipping_charge">Shipping Charge (%)</label>
                <input class="form-control @error('shipping_charge') is-invalid @enderror" id="shipping_charge" name="shipping_charge" type="text" value="{{ old('shipping_charge', $priceSetting->shipping_charge) }}" />
                @error('shipping_charge')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

          
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="cod_charge">COD Charge (Rs)</label>
                <input class="form-control" id="cod_charge" name="cod_charge" type="text" value="{{ old('cod_charge', $priceSetting->cod_charge) }}" />
                @error('cod_charge')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>


            <div class="col-lg-6 mt-2">
                <label class="form-label" for="cod_charge_parsent">COD Charge (%)</label>
                <input class="form-control" id="cod_charge_parsent" name="cod_charge_parsent" type="text" value="{{ old('cod_charge_parsent', $priceSetting->cod_charge_parsent) }}" />
                @error('cod_charge_parsent')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>







            <div class="col-lg-12 mt-3 d-flex justify-content-start">
                <button class="btn btn-secondary submitbtn" type="submit">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection
