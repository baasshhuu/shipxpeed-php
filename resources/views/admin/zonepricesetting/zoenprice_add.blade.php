@extends('layouts.app')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/summernote/summernote.min.css') }}">
<style>
    .courier-box {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
        background-color: #f9f9f9;
    }
    .courier-title {
        font-weight: bold;
        margin-bottom: 15px;
        color: #333;
    }
</style>
@endsection

@section('content')
<div class="card mb-3" style="padding: 15px 4px;">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Price Setting :: Price Add </h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('pricesetting.add') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form class="row" method="POST" action="{{ route('pricesetting.data.add') }}" enctype='multipart/form-data'>
            @csrf
            
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="seller_id">Name</label>
                <select class="form-control @error('seller_id') is-invalid @enderror" id="seller_id" name="seller_id" required>
                    <option value="">-- Select Seller --</option>
                    @foreach($SellerList as $seller)
                        <option value="{{ $seller->id }}" 
                            {{ old('seller_id', isset($selectedSeller) && $selectedSeller->id == $seller->id ? 'selected' : '') }}>
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





    {{-- <div class="col-lg-6 mt-2">
    <label class="form-label" for="fixed_price">Fixed Courier Price</label>
    <input type="number" step="0.01" class="form-control @error('fixed_price') is-invalid @enderror" 
           id="fixed_price" name="fixed_price" value="{{ old('fixed_price', $fixedPrice ?? '') }}" placeholder="Enter Fixed Courier Price" >

    @error('fixed_price')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div> --}}




            <!-- Courier Services Boxes -->
            <div class="col-12 mt-4">
                <h5>Courier Services/Partners</h5>
                
                @php
                    // Default values
                    $defaultShipping = 70;
                    $defaultCodRs = 32;
                    $defaultCodPercent = 2;
                    $defaultFixedPrice = 0;

                @endphp



                   <!-- Boxd_1750498184844 -->
                <div class="courier-box">
                    <div class="courier-title">Boxd Bluedart Air</div>
                    <input type="hidden" name="courier_service[]" value="Boxd_1750498184844">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1750498184844_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="boxd_1750498184844_shipping" name="boxd_1750498184844_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1750498184844']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1750498184844_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="boxd_1750498184844_cod" name="boxd_1750498184844_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1750498184844']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1750498184844_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="boxd_1750498184844_cod_percent" name="boxd_1750498184844_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1750498184844']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>

                          <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1750498184844_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="boxd_1750498184844_fixed_price" name="boxd_1750498184844_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1750498184844']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>
                   <!-- Boxd_1746709645240 -->
                <div class="courier-box">
                    <div class="courier-title">Boxd Bluedart Surface</div>
                    <input type="hidden" name="courier_service[]" value="Boxd_1746709645240">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1746709645240_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="boxd_1746709645240_shipping" name="boxd_1746709645240_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1746709645240']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1746709645240_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="boxd_1746709645240_cod" name="boxd_1746709645240_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1746709645240']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1746709645240_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="boxd_1746709645240_cod_percent" name="boxd_1746709645240_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1746709645240']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                          <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1746709645240_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="boxd_1746709645240_fixed_price" name="boxd_1746709645240_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1746709645240']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>


                </div>
                   <!-- Boxd_1753178335262 -->
                <div class="courier-box">
                    <div class="courier-title">Boxd Delhivery Air</div>
                    <input type="hidden" name="courier_service[]" value="Boxd_1753178335262">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1753178335262_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="boxd_1753178335262_shipping" name="boxd_1753178335262_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1753178335262']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1753178335262_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="boxd_1753178335262_cod" name="boxd_1753178335262_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1753178335262']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1753178335262_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="boxd_1753178335262_cod_percent" name="boxd_1753178335262_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1753178335262']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>

                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1753178335262_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="boxd_1753178335262_fixed_price" name="boxd_1753178335262_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1753178335262']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>



                    </div>
                </div>
                   <!-- Boxd_1753163038641 -->
                <div class="courier-box">
                    <div class="courier-title">Boxd Delhivery Surface</div>
                    <input type="hidden" name="courier_service[]" value="Boxd_1753163038641">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1753163038641_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="boxd_1753163038641_shipping" name="boxd_1753163038641_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1753163038641']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1753163038641_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="boxd_1753163038641_cod" name="boxd_1753163038641_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1753163038641']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1753163038641_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="boxd_1753163038641_cod_percent" name="boxd_1753163038641_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1753163038641']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>

                         <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_1753163038641_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="boxd_1753163038641_fixed_price" name="boxd_1753163038641_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Boxd_1753163038641']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>


                    </div>
                </div>



















                
                {{-- <!-- XpressBees -->
                <div class="courier-box">
                    <div class="courier-title">XpressBees</div>
                    <input type="hidden" name="courier_service[]" value="XpressBees">
                    <div class="row">
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="xpressbees_shipping" name="xpressbees_shipping" type="number" step="0.01" 
                                value="{{ $existingPrices['XpressBees']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="xpressbees_cod" name="xpressbees_cod" type="number" step="0.01"
                                value="{{ $existingPrices['XpressBees']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="xpressbees_cod_percent" name="xpressbees_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['XpressBees']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>
                    </div>
                </div>
                
                <!-- XpressBees Air -->
                <div class="courier-box">
                    <div class="courier-title">XpressBees Air</div>
                    <input type="hidden" name="courier_service[]" value="XpressBees Air">
                    <div class="row">
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_air_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="xpressbees_air_shipping" name="xpressbees_air_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['XpressBees Air']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_air_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="xpressbees_air_cod" name="xpressbees_air_cod" type="number" step="0.01"
                                value="{{ $existingPrices['XpressBees Air']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_air_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="xpressbees_air_cod_percent" name="xpressbees_air_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['XpressBees Air']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>
                    </div>
                </div> --}}
                
                <!-- Delhivery -->
                <div class="courier-box">
                    <div class="courier-title">Delhivery</div>
                    <input type="hidden" name="courier_service[]" value="Delhivery">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="delhivery_shipping" name="delhivery_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="delhivery_cod" name="delhivery_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="delhivery_cod_percent" name="delhivery_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                               <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="delhivery_fixed_price" name="delhivery_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>


                    </div>
                </div>
                
                <!-- Delhivery Air -->
                <div class="courier-box">
                    <div class="courier-title">Delhivery Air</div>
                    <input type="hidden" name="courier_service[]" value="Delhivery Air">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_air_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="delhivery_air_shipping" name="delhivery_air_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery Air']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_air_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="delhivery_air_cod" name="delhivery_air_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery Air']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_air_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="delhivery_air_cod_percent" name="delhivery_air_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery Air']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>

            <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_air_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="delhivery_air_fixed_price" name="delhivery_air_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery Air']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>


                    </div>
                </div>
                
                <!-- Blue Dart -->
                <div class="courier-box">
                    <div class="courier-title">Blue Dart</div>
                    <input type="hidden" name="courier_service[]" value="Blue Dart">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="bluedart_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="bluedart_shipping" name="bluedart_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="bluedart_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="bluedart_cod" name="bluedart_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="bluedart_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="bluedart_cod_percent" name="bluedart_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>

     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="bluedart_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="bluedart_fixed_price" name="bluedart_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>
                
                <!-- DTDC -->
                <div class="courier-box">
                    <div class="courier-title">DTDC</div>
                    <input type="hidden" name="courier_service[]" value="DTDC">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="dtdc_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="dtdc_shipping" name="dtdc_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['DTDC']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="dtdc_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="dtdc_cod" name="dtdc_cod" type="number" step="0.01"
                                value="{{ $existingPrices['DTDC']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="dtdc_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="dtdc_cod_percent" name="dtdc_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['DTDC']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="dtdc_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="dtdc_fixed_price" name="dtdc_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['DTDC']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>





    <!-- Shiprocket Xpressbee -->
                <div class="courier-box">
                    <div class="courier-title">Shiprocket Xpressbee</div>
                    <input type="hidden" name="courier_service[]" value="shiprocket_Xpressbee">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_xpressbee_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="shiprocket_xpressbee_shipping" name="shiprocket_xpressbee_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Xpressbee']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_xpressbee_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="shiprocket_xpressbee_cod" name="shiprocket_xpressbee_cod" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Xpressbee']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_xpressbee_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="shiprocket_xpressbee_cod_percent" name="shiprocket_xpressbee_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Xpressbee']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_xpressbee_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="shiprocket_xpressbee_fixed_price" name="shiprocket_xpressbee_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Xpressbee']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>




    <!-- Shiprocket Delhivery -->
                <div class="courier-box">
                    <div class="courier-title">Shiprocket Delhivery</div>
                    <input type="hidden" name="courier_service[]" value="shiprocket_Delhivery">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_delhivery_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="shiprocket_delhivery_shipping" name="shiprocket_delhivery_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Delhivery']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_delhivery_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="shiprocket_delhivery_cod" name="shiprocket_delhivery_cod" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Delhivery']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_delhivery_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="shiprocket_delhivery_cod_percent" name="shiprocket_delhivery_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Delhivery']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_delhivery_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="shiprocket_delhivery_fixed_price" name="shiprocket_delhivery_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Delhivery']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>





    <!-- Shiprocket Bluedart -->
                <div class="courier-box">
                    <div class="courier-title">Shiprocket Bluedart</div>
                    <input type="hidden" name="courier_service[]" value="shiprocket_Bluedart">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_bluedart_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="shiprocket_bluedart_shipping" name="shiprocket_bluedart_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Bluedart']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_bluedart_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="shiprocket_bluedart_cod" name="shiprocket_bluedart_cod" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Bluedart']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_bluedart_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="shiprocket_bluedart_cod_percent" name="shiprocket_bluedart_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Bluedart']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shiprocket_bluedart_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="shiprocket_bluedart_fixed_price" name="shiprocket_bluedart_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['shiprocket_Bluedart']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>















 <!-- Amazon_0.5 KG -->
                <div class="courier-box">
                    <div class="courier-title">Amazon_0.5 KG</div>
                    <input type="hidden" name="courier_service[]" value="Amazon_0.5 KG">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="amazon_0_5kg_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="amazon_0_5kg_shipping" name="amazon_0_5kg_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Amazon_0.5 KG']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="amazon_0_5kg_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="amazon_0_5kg_cod" name="amazon_0_5kg_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Amazon_0.5 KG']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="amazon_0_5kg_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="amazon_0_5kg_cod_percent" name="amazon_0_5kg_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Amazon_0.5 KG']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>



                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="amazon_0_5kg_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="amazon_0_5kg_fixed_price" name="amazon_0_5kg_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Amazon_0.5 KG']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>



 


 <!-- shadofex -->
                <div class="courier-box">
                    <div class="courier-title">Shadowfax</div>
                    <input type="hidden" name="courier_service[]" value="Shadowfax">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shadowfax_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="shadowfax_shipping" name="shadowfax_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Shadowfax']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shadowfax_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="shadowfax_cod" name="shadowfax_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Shadowfax']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shadowfax_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="shadowfax_cod_percent" name="shadowfax_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Shadowfax']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>



                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="shadowfax_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="shadowfax_fixed_price" name="shadowfax_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Shadowfax']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>







                 <!-- DTDC -->
                <div class="courier-box">
                    <div class="courier-title">Amazon_2 KG</div>
                    <input type="hidden" name="courier_service[]" value="Amazon_2 KG">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="amazon_2_kg_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="amazon_2_kg_shipping" name="amazon_2_kg_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Amazon_2 KG']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="amazon_2_kg_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="amazon_2_kg_cod" name="amazon_2_kg_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Amazon_2 KG']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="amazon_2_kg_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="amazon_2_kg_cod_percent" name="amazon_2_kg_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Amazon_2 KG']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>

   <div class="col-lg-3 mt-2">
                            <label class="form-label" for="amazon_2_kg_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="amazon_2_kg_fixed_price" name="amazon_2_kg_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Amazon_2 KG']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>


                    </div>
                </div>

                 <!-- DTDC -->
                <div class="courier-box">
                    <div class="courier-title">Blue Dart_0.5 KG</div>
                    <input type="hidden" name="courier_service[]" value="Blue Dart_0.5 KG">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="blue_dart_0_5_kg_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="blue_dart_0_5_kg_shipping" name="blue_dart_0_5_kg_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart_0.5 KG']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="blue_dart_0_5_kg_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="blue_dart_0_5_kg_cod" name="blue_dart_0_5_kg_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart_0.5 KG']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="blue_dart_0_5_kg_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="blue_dart_0_5_kg_cod_percent" name="blue_dart_0_5_kg_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart_0.5 KG']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>



                          <div class="col-lg-3 mt-2">
                            <label class="form-label" for="blue_dart_0_5_kg_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="blue_dart_0_5_kg_fixed_price" name="blue_dart_0_5_kg_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart_0.5 KG']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>
                    </div>
                </div>

                 <!-- Delhivery_5kg -->
                <div class="courier-box">
                    <div class="courier-title">Delhivery_5kg</div>
                    <input type="hidden" name="courier_service[]" value="Delhivery_5kg">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_5kg_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="delhivery_5kg_shipping" name="delhivery_5kg_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_5kg']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_5kg_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="delhivery_5kg_cod" name="delhivery_5kg_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_5kg']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_5kg_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="delhivery_5kg_cod_percent" name="delhivery_5kg_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_5kg']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>



                          <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_5kg_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="delhivery_5kg_fixed_price" name="delhivery_5kg_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_5kg']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>

                 <!-- Delhivery_10kg -->
                <div class="courier-box">
                    <div class="courier-title">Delhivery_10kg</div>
                    <input type="hidden" name="courier_service[]" value="Delhivery_10kg">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_10kg_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="delhivery_10kg_shipping" name="delhivery_10kg_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_10kg']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_10kg_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="delhivery_10kg_cod" name="delhivery_10kg_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_10kg']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_10kg_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="delhivery_10kg_cod_percent" name="delhivery_10kg_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_10kg']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


      <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_10kg_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="delhivery_10kg_fixed_price" name="delhivery_10kg_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_10kg']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>


                    </div>
                </div>




                <div class="courier-box">
                    <div class="courier-title">Delhivery_1 KG</div>
                    <input type="hidden" name="courier_service[]" value="Delhivery_1KG">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_1kg_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="delhivery_1kg_shipping" name="delhivery_1kg_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_1KG']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_1kg_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="delhivery_1kg_cod" name="delhivery_1kg_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_1KG']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_1kg_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="delhivery_1kg_cod_percent" name="delhivery_1kg_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_1KG']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>



      <div class="col-lg-3 mt-2">
                            <label class="form-label" for="delhivery_1kg_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="delhivery_1kg_fixed_price" name="delhivery_1kg_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery_1KG']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>







                
                <div class="courier-box">
                    <div class="courier-title">Ekart 2 KG</div>
                    <input type="hidden" name="courier_service[]" value="Ekart_2_KG_Fixed">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="Ekart_2kg_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="Ekart_2kg_shipping" name="Ekart_2kg_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Ekart_2_KG_Fixed']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="Ekart_2kg_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="Ekart_2kg_cod" name="Ekart_2kg_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Ekart_2_KG_Fixed']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="Ekart_2kg_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="Ekart_2kg_cod_percent" name="Ekart_2kg_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Ekart_2_KG_Fixed']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>



                            <div class="col-lg-3 mt-2">
                            <label class="form-label" for="Ekart_2kg_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="Ekart_2kg_fixed_price" name="Ekart_2kg_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Ekart_2_KG_Fixed']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>




   <!-- pARCAL X  -->
                <div class="courier-box">
                    <div class="courier-title">Parcel X Delhivery</div>
                    <input type="hidden" name="courier_service[]" value="parcel_x_Delhivery">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Delhivery_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="parcel_x_Delhivery_shipping" name="parcel_x_Delhivery_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Delhivery']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Delhivery_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="parcel_x_Delhivery_cod" name="parcel_x_Delhivery_cod" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Delhivery']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Delhivery_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="parcel_x_Delhivery_cod_percent" name="parcel_x_Delhivery_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Delhivery']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Delhivery_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="parcel_x_Delhivery_fixed_price" name="parcel_x_Delhivery_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Delhivery']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>





                          <div class="courier-box">
                    <div class="courier-title">Parcel X Amazon</div>
                    <input type="hidden" name="courier_service[]" value="parcel_x_Amazon">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="parcel_x_Amazon_shipping" name="parcel_x_Amazon_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="parcel_x_Amazon_cod" name="parcel_x_Amazon_cod" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="parcel_x_Amazon_cod_percent" name="parcel_x_Amazon_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="parcel_x_Amazon_fixed_price" name="parcel_x_Amazon_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>





                
                          <div class="courier-box">
                    <div class="courier-title">Parcel X Amazon 1 KG</div>
                    <input type="hidden" name="courier_service[]" value="parcel_x_Amazon_1kg">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_1kg_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="parcel_x_Amazon_1kg_shipping" name="parcel_x_Amazon_1kg_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon_1kg']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_1kg_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="parcel_x_Amazon_1kg_cod" name="parcel_x_Amazon_1kg_cod" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon_1kg']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_1kg_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="parcel_x_Amazon_1kg_cod_percent" name="parcel_x_Amazon_1kg_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon_1kg']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_1kg_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="parcel_x_Amazon_1kg_fixed_price" name="parcel_x_Amazon_1kg_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon_1kg']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>



                
                          <div class="courier-box">
                    <div class="courier-title">Parcel X Amazon 2 KG</div>
                    <input type="hidden" name="courier_service[]" value="parcel_x_Amazon_2kg">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_2kg_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="parcel_x_Amazon_2kg_shipping" name="parcel_x_Amazon_2kg_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon_2kg']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_2kg_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="parcel_x_Amazon_2kg_cod" name="parcel_x_Amazon_2kg_cod" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon_2kg']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_2kg_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="parcel_x_Amazon_2kg_cod_percent" name="parcel_x_Amazon_2kg_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon_2kg']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_2kg_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="parcel_x_Amazon_2kg_fixed_price" name="parcel_x_Amazon_2kg_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['parcel_x_Amazon_2kg']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>




                
                
                          <div class="courier-box">
                    <div class="courier-title">BOX D Ekart 2 KG</div>
                    <input type="hidden" name="courier_service[]" value="boxd_Ekart_2kg">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_Ekart_2kg_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="boxd_Ekart_2kg_shipping" name="boxd_Ekart_2kg_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['boxd_Ekart_2kg']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_Ekart_2kg_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="boxd_Ekart_2kg_cod" name="boxd_Ekart_2kg_cod" type="number" step="0.01"
                                value="{{ $existingPrices['boxd_Ekart_2kg']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_Ekart_2kg_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="boxd_Ekart_2kg_cod_percent" name="boxd_Ekart_2kg_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['boxd_Ekart_2kg']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="boxd_Ekart_2kg_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="boxd_Ekart_2kg_fixed_price" name="boxd_Ekart_2kg_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['boxd_Ekart_2kg']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>





                
            </div>

            <div class="col-lg-12 mt-3 d-flex justify-content-start">
                <button class="btn btn-secondary submitbtn" type="submit">
                    {{ isset($selectedSeller) ? 'Update' : 'Add' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-load prices when seller is selected
    document.getElementById('seller_id').addEventListener('change', function() {
        if(this.value) {
            window.location.href = "{{ route('pricesetting.add') }}?seller_id=" + this.value;
        }
    });
</script>
@endsection













{{-- @extends('layouts.app')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/summernote/summernote.min.css') }}">
<style>
    .courier-box {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
        background-color: #f9f9f9;
    }
    .courier-title {
        font-weight: bold;
        margin-bottom: 15px;
        color: #333;
    }
</style>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Price Setting :: Price Add </h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('pricesetting.add') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form class="row" method="POST" action="{{ route('pricesetting.data.add') }}" enctype='multipart/form-data'>
            @csrf
            
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="seller_id">Name</label>
                <select class="form-control @error('seller_id') is-invalid @enderror" id="seller_id" name="seller_id" required>
                    <option value="">-- Select Seller --</option>
                    @foreach($SellerList as $seller)
                        <option value="{{ $seller->id }}" 
                            {{ old('seller_id', isset($selectedSeller) && $selectedSeller->id == $seller->id ? 'selected' : '') }}>
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

            <!-- Courier Services Boxes -->
            <div class="col-12 mt-4">
                <h5>Courier Services/Partners</h5>
                
                @php
                    // Default values
                    $defaultShipping = 70;
                    $defaultCodRs = 32;
                    $defaultCodPercent = 2;
                @endphp
                
                <!-- XpressBees -->
                <div class="courier-box">
                    <div class="courier-title">XpressBees</div>
                    <input type="hidden" name="courier_service[]" value="XpressBees">
                    <div class="row">
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="xpressbees_shipping" name="xpressbees_shipping" type="number" step="0.01" 
                                value="{{ $existingPrices['XpressBees']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="xpressbees_cod" name="xpressbees_cod" type="number" step="0.01"
                                value="{{ $existingPrices['XpressBees']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="xpressbees_cod_percent" name="xpressbees_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['XpressBees']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>
                    </div>
                </div>
                
                <!-- XpressBees Air -->
                <div class="courier-box">
                    <div class="courier-title">XpressBees Air</div>
                    <input type="hidden" name="courier_service[]" value="XpressBees Air">
                    <div class="row">
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_air_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="xpressbees_air_shipping" name="xpressbees_air_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['XpressBees Air']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_air_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="xpressbees_air_cod" name="xpressbees_air_cod" type="number" step="0.01"
                                value="{{ $existingPrices['XpressBees Air']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="xpressbees_air_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="xpressbees_air_cod_percent" name="xpressbees_air_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['XpressBees Air']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>
                    </div>
                </div>
                
                <!-- Delhivery -->
                <div class="courier-box">
                    <div class="courier-title">Delhivery</div>
                    <input type="hidden" name="courier_service[]" value="Delhivery">
                    <div class="row">
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="delhivery_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="delhivery_shipping" name="delhivery_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="delhivery_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="delhivery_cod" name="delhivery_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="delhivery_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="delhivery_cod_percent" name="delhivery_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>
                    </div>
                </div>
                
                <!-- Delhivery Air -->
                <div class="courier-box">
                    <div class="courier-title">Delhivery Air</div>
                    <input type="hidden" name="courier_service[]" value="Delhivery Air">
                    <div class="row">
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="delhivery_air_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="delhivery_air_shipping" name="delhivery_air_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery Air']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="delhivery_air_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="delhivery_air_cod" name="delhivery_air_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery Air']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="delhivery_air_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="delhivery_air_cod_percent" name="delhivery_air_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Delhivery Air']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>
                    </div>
                </div>
                
                <!-- Blue Dart -->
                <div class="courier-box">
                    <div class="courier-title">Blue Dart</div>
                    <input type="hidden" name="courier_service[]" value="Blue Dart">
                    <div class="row">
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="bluedart_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="bluedart_shipping" name="bluedart_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="bluedart_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="bluedart_cod" name="bluedart_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="bluedart_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="bluedart_cod_percent" name="bluedart_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Blue Dart']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>
                    </div>
                </div>
                
                <!-- DTDC -->
                <div class="courier-box">
                    <div class="courier-title">DTDC</div>
                    <input type="hidden" name="courier_service[]" value="DTDC">
                    <div class="row">
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="dtdc_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="dtdc_shipping" name="dtdc_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['DTDC']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="dtdc_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="dtdc_cod" name="dtdc_cod" type="number" step="0.01"
                                value="{{ $existingPrices['DTDC']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-4 mt-2">
                            <label class="form-label" for="dtdc_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="dtdc_cod_percent" name="dtdc_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['DTDC']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>
                    </div>
                </div>
            </div>






   <!-- pARCAL X  -->
                <div class="courier-box">
                    <div class="courier-title">Parcel X Delhivery</div>
                    <input type="hidden" name="courier_service[]" value="Parcel X">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Delhivery_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="parcel_x_Delhivery_shipping" name="parcel_x_Delhivery_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Parcel X']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Delhivery_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="parcel_x_Delhivery_cod" name="parcel_x_Delhivery_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Parcel X']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Delhivery_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="parcel_x_Delhivery_cod_percent" name="parcel_x_Delhivery_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Parcel X']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Delhivery_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="parcel_x_Delhivery_fixed_price" name="parcel_x_Delhivery_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Parcel X']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>





                          <div class="courier-box">
                    <div class="courier-title">Parcel X Amazon</div>
                    <input type="hidden" name="courier_service[]" value="Parcel X">
                    <div class="row">
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_shipping">Shipping Charge (%)</label>
                            <input class="form-control" id="parcel_x_Amazon_shipping" name="parcel_x_Amazon_shipping" type="number" step="0.01"
                                value="{{ $existingPrices['Parcel X']['shipping_charge'] ?? $defaultShipping }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_cod">COD Charge (Rs)</label>
                            <input class="form-control" id="parcel_x_Amazon_cod" name="parcel_x_Amazon_cod" type="number" step="0.01"
                                value="{{ $existingPrices['Parcel X']['cod_charge'] ?? $defaultCodRs }}" required>
                        </div>
                        <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_cod_percent">COD Charge (%)</label>
                            <input class="form-control" id="parcel_x_Amazon_cod_percent" name="parcel_x_Amazon_cod_percent" type="number" step="0.01"
                                value="{{ $existingPrices['Parcel X']['cod_charge_percent'] ?? $defaultCodPercent }}" required>
                        </div>


                     <div class="col-lg-3 mt-2">
                            <label class="form-label" for="parcel_x_Amazon_fixed_price">Fixed Courier Price</label>
                            <input class="form-control" id="parcel_x_Amazon_fixed_price" name="parcel_x_Amazon_fixed_price" type="number" step="0.01"
                                value="{{ $existingPrices['Parcel X']['fixed_courier_price'] ?? $defaultFixedPrice }}" required>
                        </div>

                    </div>
                </div>





            <div class="col-lg-12 mt-3 d-flex justify-content-start">
                <button class="btn btn-secondary submitbtn" type="submit">
                    {{ isset($selectedSeller) ? 'Update' : 'Add' }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-load prices when seller is selected
    document.getElementById('seller_id').addEventListener('change', function() {
        if(this.value) {
            window.location.href = "{{ route('pricesetting.add') }}?seller_id=" + this.value;
        }
    });
</script>
@endsection --}}
