@extends('layouts.sellerdash')

@section('content')
    <div class="pc-container">
        <div class="pc-content">
            <div class="header-section mb-3">
                <div class="heading">Edit Order #{{ $order->order_number }}</div>
                <div>
                    <button class="export-btn-light" onclick="window.location.href='{{ route('seller.order') }}'"> 
                        Cancel
                    </button>
                </div>
            </div>
            <div class="container mt-4">
                <div class="row">
                    <!-- Right Section (Wider) -->
                    <div class="col-md-9">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <form method="POST" action="{{ route('seller.orderupdate', $order->id) }}">
                            @csrf
                            {{-- @method('PUT') --}}
                            <meta name="csrf-token" content="{{ csrf_token() }}">
                            <div class="accordion" id="formAccordion">
                                <!-- Buyer/Receiver Details -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#buyerDetails">
                                            <i class="fas fa-user me-2"></i> Buyer/Receiver Details
                                        </button>
                                    </h2>
                                    <div id="buyerDetails" class="accordion-collapse collapse show"
                                        data-bs-parent="#formAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Buyer Name *</label>
                                                    <input type="text" name="consignee[name]" class="form-control"
                                                        placeholder="Enter name" value="{{ $order->consignee['name'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Phone *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">+91</span>
                                                        <input type="text" name="consignee[phone]" class="form-control"
                                                            placeholder="Phone" value="{{ $order->consignee['phone'] ?? '' }}" required maxlength="10" pattern="\d{10}" title="Please enter a valid 10-digit phone number">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Address Line 1 *</label>
                                                    <input type="text" name="consignee[address]" class="form-control"
                                                        placeholder="Address Line 1" value="{{ $order->consignee['address'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Address Line 2 *</label>
                                                    <input type="text" name="consignee[address_2]" class="form-control"
                                                        placeholder="Address Line 2" value="{{ $order->consignee['address_2'] ?? '' }}" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Pincode *</label>
                                                    <input type="text" name="consignee[pincode]" class="form-control" id="BuyerPincode" 
                                                        placeholder="Pincode" value="{{ $order->consignee['pincode'] ?? '' }}" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">City *</label>
                                                    <input type="text" name="consignee[city]" class="form-control" id="BuyerCity" 
                                                        placeholder="City" value="{{ $order->consignee['city'] ?? '' }}" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">State *</label>
                                                    <input type="text" name="consignee[state]" class="form-control" id="BuyerState" 
                                                        placeholder="State" value="{{ $order->consignee['state'] ?? '' }}" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Details -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#orderDetails">
                                            <i class="fas fa-box me-2"></i> Order Details
                                        </button>
                                    </h2>
                                    <div id="orderDetails" class="accordion-collapse collapse"
                                        data-bs-parent="#formAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-md-5">
                                                    <label class="form-label">Order ID *</label>
                                                    <input type="text" id="orderNumber" name="order_number" class="form-control" 
                                                        value="{{ $order->order_number }}" readonly required>
                                                </div>

                                                <div class="col-md-3">
                                                    <label class="form-label">Unique Order Number *</label>
                                                    <select class="form-control" name="unique_order_number">
                                                        <option value="yes" {{ ($order->encode_data['unique_order_number'] ?? 'yes') == 'yes' ? 'selected' : '' }}>Yes</option>
                                                        <option value="no" {{ ($order->encode_data['unique_order_number'] ?? 'yes') == 'no' ? 'selected' : '' }}>No</option>
                                                    </select>
                                                </div>

                                                @php
                                        $paymentType = strtolower(trim($order->payment_type ?? ''));
                                    @endphp

                                    <div class="col-md-3">
                                        <label class="form-label">Payment Type *</label>
                                        <select class="form-control" name="payment_type" required>
                                            <option value="prepaid" {{ in_array($paymentType, ['prepaid', 'pre-paid', 'pre paid']) ? 'selected' : '' }}>Prepaid</option>
                                            <option value="cod" {{ in_array($paymentType, ['cod', 'cash on delivery', 'cashondelivery']) ? 'selected' : '' }}>COD</option>
                                        </select>
                                    </div>


                                                <!-- <div class="col-md-3">
                                                    <label class="form-label">Payment Type *</label>
                                                    <select class="form-control" name="payment_type" required>
                                                        <option value="prepaid" {{ $order->payment_type == 'prepaid' ? 'selected' : '' }}>Prepaid</option>
                                                        <option value="cod" {{ $order->payment_type == 'cod' ? 'selected' : '' }}>COD</option>
                                                    </select>
                                                </div> -->
                                            </div>

                                            <!-- Product Details Section -->
                                            <div class="mt-4 p-3 border rounded" id="productSection">
                                                <h5 class="mb-3">Product Details</h5>

                                                <div class="product-items">
                                                    @foreach($order->order_items as $index => $item)
                                                    <div class="product-item row g-3 align-items-center mb-3">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Product Name *</label>
                                                            <input type="text" name="order_items[{{ $index }}][name]"
                                                                class="form-control" placeholder="Enter product name"
                                                                value="{{ $item['name'] ?? '' }}" required>
                                                        </div>
 
                                                        <div class="col-md-3">
                                                            <label class="form-label">SKU *</label>
                                                            <input type="text" name="order_items[{{ $index }}][sku]"
                                                                class="form-control" placeholder="SKU" value="{{ $item['sku'] ?? '' }}" required>
                                                        </div>

                                                        <div class="col-md-2">
                                                            <label class="form-label">Quantity *</label>
                                                            <input type="number" name="order_items[{{ $index }}][qty]" 
                                                                class="form-control text-center qty qtyrr" value="{{ $item['qty'] ?? '' }}" required>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <label class="form-label">Unit Price *</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text">₹</span>
                                                                <input type="number" name="order_items[{{ $index }}][price]" 
                                                                    class="form-control price pricerr" placeholder="Unit Price" step="0.01" 
                                                                    value="{{ $item['price'] ?? '' }}" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <!-- Payment & Shipping -->
                                            <div class="row g-3 mt-4">
                                                <div class="col-md-4">
                                                    <label class="form-label">Collectable Amount *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" name="collectable_amount" class="form-control collectable_amounttt" 
                                                            placeholder="Collectable Amount" step="0.01" value="{{ $order->collectable_amount }}" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Warehouse/Pickup Address -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#warehouseAddress">
                                            <i class="fas fa-warehouse me-2"></i> Pickup Location
                                        </button>
                                    </h2>
                                    <div id="warehouseAddress" class="accordion-collapse collapse"
                                        data-bs-parent="#formAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-md-12 mb-3">
                                                    <div class="search-box-anime position-relative">
                                                        <input type="text" id="warehouseSearch" class="form-control search-input-anime" 
                                                            placeholder="Search by Warehouse Name/City Name/Pincode">
                                                        <i class="fas fa-search search-icon-anime"></i>
                                                        <div class="search-border-anime"></div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-12">
                                                    <div class="row g-3" id="warehouseCardsContainer">
                                                        @foreach($warehouses as $warehouse)
                                                        <div class="col-md-6 warehouse-card-col">
                                                            <div class="card warehouse-card-anime {{ (($order->pickup['warehouse_name'] ?? '') == $warehouse->address_title) ? 'selected' : '' }}" 
                                                                data-warehouse-id="{{ $warehouse->id }}"
                                                                data-warehouse-name="{{ $warehouse->address_title }}"
                                                                data-name="{{ $warehouse->name }}"
                                                                data-address="{{ $warehouse->address_line1 }}"
                                                                data-address2="{{ $warehouse->address_line2 }}"
                                                                data-pincode="{{ $warehouse->pincode }}"
                                                                data-city="{{ $warehouse->city }}"
                                                                data-state="{{ $warehouse->state }}"
                                                                data-phone="{{ $warehouse->phone }}">
                                                                <div class="card-body">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <h5 class="card-title mb-1 warehouse-title-anime">
                                                                            <i class="fas fa-store-alt me-1"></i> {{ $warehouse->address_title }}
                                                                        </h5>
                                                                        <span class="badge bg-primary-anime">{{ $warehouse->code }}</span>
                                                                    </div>
                                                                    <p class="card-text text-muted mb-1 warehouse-text-anime">
                                                                        <i class="fas fa-map-marker-alt me-1"></i> 
                                                                        {{ $warehouse->city }}, {{ $warehouse->state }}
                                                                    </p>
                                                                    <p class="card-text mb-1 warehouse-text-anime">
                                                                        <i class="fas fa-home me-1"></i> {{ $warehouse->address_line1 }}
                                                                        @if($warehouse->address_line2)
                                                                            , {{ $warehouse->address_line2 }}
                                                                        @endif
                                                                    </p>
                                                                    <p class="card-text warehouse-text-anime">
                                                                        <i class="fas fa-map-pin me-1"></i> {{ $warehouse->pincode }}
                                                                    </p>
                                                                </div>
                                                                <div class="card-footer-anime"></div>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                
                                                <!-- Hidden Input Fields -->
                                                <input type="hidden" id="selectedWarehouseId" name="selected_warehouse_id" 
                                                    value="{{ $order->pickup['warehouse_id'] ?? '' }}">
                                                <input type="hidden" name="pickup[warehouse_name]" id="warehouseName" 
                                                    value="{{ $order->pickup['warehouse_name'] ?? '' }}">
                                                <input type="hidden" name="pickup[name]" id="contactName" 
                                                    value="{{ $order->pickup['name'] ?? '' }}">
                                                <input type="hidden" name="pickup[address]" id="addressLine1" 
                                                    value="{{ $order->pickup['address'] ?? '' }}">
                                                <input type="hidden" name="pickup[address_2]" id="addressLine2" 
                                                    value="{{ $order->pickup['address_2'] ?? '' }}">
                                                <input type="hidden" name="pickup[pincode]" id="pincode" 
                                                    value="{{ $order->pickup['pincode'] ?? '' }}">
                                                <input type="hidden" name="pickup[city]" id="city" 
                                                    value="{{ $order->pickup['city'] ?? '' }}">
                                                <input type="hidden" name="pickup[state]" id="state" 
                                                    value="{{ $order->pickup['state'] ?? '' }}">
                                                <input type="hidden" name="pickup[phone]" id="phone" 
                                                    value="{{ $order->pickup['phone'] ?? '' }}">
                                                
                                                <!-- Selected Warehouse Display -->
                                                <div class="col-md-12 mt-3" id="selectedWarehouseDisplay" 
                                                    style="{{ empty($order->pickup['warehouse_name'] ?? null) ? 'display: none;' : '' }}">
                                                    <div class="alert alert-info-anime">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-check-circle me-2"></i>
                                                            <div>
                                                                <strong class="d-block">Selected Warehouse:</strong>
                                                                <span id="displayWarehouseName" class="d-block">{{ $order->pickup['warehouse_name'] ?? '' }}</span>
                                                                <small id="displayWarehouseAddress" class="d-block">
                                                                    {{ $order->pickup['address'] ?? '' }}{{ !empty($order->pickup['address_2']) ? ', ' . $order->pickup['address_2'] : '' }}
                                                                </small>
                                                                <small id="displayWarehouseCityState" class="d-block">
                                                                    {{ $order->pickup['city'] ?? '' }}, {{ $order->pickup['state'] ?? '' }}
                                                                </small>
                                                                <small id="displayWarehousePincode">
                                                                    Pincode: {{ $order->pickup['pincode'] ?? '' }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Weight & Dimensions -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#weightDimensions">
                                            <i class="fas fa-weight me-2"></i> Weight & Dimensions
                                        </button>
                                    </h2>
                                    <div id="weightDimensions" class="accordion-collapse collapse"
                                        data-bs-parent="#formAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Total Weight (g) *</label>
                                                    <input type="number" name="package_weight" class="form-control"
                                                        placeholder="Total Weight" step="0.01" value="{{ $order->package_weight }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Length (cm) *</label>
                                                    <input type="number" name="package_length" class="form-control"
                                                        placeholder="Length" step="0.01" value="{{ $order->package_length }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Width (cm) *</label>
                                                    <input type="number" name="package_breadth" class="form-control"
                                                        placeholder="Width" step="0.01" value="{{ $order->package_breadth }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Height (cm) *</label>
                                                    <input type="number" name="package_height" class="form-control"
                                                        placeholder="Height" step="0.01" value="{{ $order->package_height }}" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- RTO Details -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#rtoDetails">
                                            <i class="fas fa-exchange-alt me-2"></i> RTO Details
                                        </button>
                                    </h2>
                                    <div id="rtoDetails" class="accordion-collapse collapse"
                                        data-bs-parent="#formAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Different RTO Address?</label>
                                                    <select class="form-control" name="is_rto_different" id="isRtoDifferent">
                                                        <option value="no" {{ ($order->encode_data['is_rto_different'] ?? 'no') == 'no' ? 'selected' : '' }}>No</option>
                                                        <option value="yes" {{ ($order->encode_data['is_rto_different'] ?? 'no') == 'yes' ? 'selected' : '' }}>Yes</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="row g-3 mt-3" id="rtoAddressFields">
                                                <div class="col-md-6">
                                                    <label class="form-label">RTO Warehouse Name *</label>
                                                    <input type="text" name="rto[warehouse_name]" id="rtoWarehouseName" class="form-control"
                                                        placeholder="RTO Warehouse Name" value="{{ $order->rto['warehouse_name'] ?? '' }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Contact Name *</label>
                                                    <input type="text" name="rto[name]" id="rtoContactName" class="form-control"
                                                        placeholder="Contact Name" value="{{ $order->rto['name'] ?? '' }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Address Line 1 *</label>
                                                    <input type="text" name="rto[address]" id="rtoAddress" class="form-control"
                                                        placeholder="Address Line 1" value="{{ $order->rto['address'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">City *</label>
                                                    <input type="text" name="rto[city]" id="rtoCity" class="form-control"
                                                        placeholder="City" value="{{ $order->rto['city'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">State *</label>
                                                    <input type="text" name="rto[state]" id="rtoState" class="form-control"
                                                        placeholder="State" value="{{ $order->rto['state'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Pincode *</label>
                                                    <input type="text" name="rto[pincode]" id="rtoPincode" class="form-control"
                                                        placeholder="Pincode" value="{{ $order->rto['pincode'] ?? '' }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Phone *</label>
                                                    <input type="text" name="rto[phone]" id="rtoPhone" class="form-control"
                                                        placeholder="Phone" value="{{ $order->rto['phone'] ?? '' }}" required maxlength="10" pattern="\d{10}" title="Please enter a valid 10-digit phone number">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Other Details -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#otherDetails">
                                            <i class="fas fa-list-alt me-2"></i> Other Details
                                        </button>
                                    </h2>
                                    <div id="otherDetails" class="accordion-collapse collapse"
                                        data-bs-parent="#formAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Request Auto Pickup</label>
                                                    <select class="form-control" name="request_auto_pickup">
                                                        <option value="yes" {{ ($order->encode_data['request_auto_pickup'] ?? 'yes') == 'yes' ? 'selected' : '' }}>Yes</option>
                                                        <option value="no" {{ ($order->encode_data['request_auto_pickup'] ?? 'yes') == 'no' ? 'selected' : '' }}>No</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Courier ID</label>
                                                    <input type="text" name="courier_id" class="form-control"
                                                        placeholder="Courier ID" value="{{ $order->courier_id }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="export-btn" id="submitOrderForm">
                                <i class="fas fa-save me-2"></i> <span id="submitText">Update Order</span>
                                <span id="loadingSpinner" class="spinner-border spinner-border-sm d-none" role="status"
                                    aria-hidden="true"></span>
                            </button>
                        </form>
                    </div>

                    <!-- Right Sidebar -->
                    <div class="col-md-3">
                        <div class="card border-0">
                            <div class="card-body">
                                <h6 class="fw-bold">Package Weight</h6>
                                <p><strong>Volumetric Weight -</strong> <br> Length * Width * Height / 5000</p>
                                <p><strong>Actual Weight -</strong> Actual weight is real size of package</p>
                                <p>Shipping weight will depend on which weight is more than (Volumetric or actual weight)
                                </p>

                                <hr>

                                <h6 class="fw-bold mt-3">Order Summary</h6>
                                <div id="orderSummary">
                                    @foreach($order->order_items as $item)
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>{{ $item['name'] }} ({{ $item['qty'] }} × ₹{{ $item['price'] }})</span>
                                        <span>₹{{ $item['qty'] * $item['price'] }}</span>
                                    </div>
                                    @endforeach
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total</span>
                                        <span>₹{{ $order->collectable_amount }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>



<script>
$(document).ready(function() {
    // alert('sssss')
    // ✅ Buyer Pincode Auto-fill City & State
    $('#BuyerPincode').on('input', function () {
        let pincode = $(this).val().trim();
        if (pincode.length === 6 && /^\d{6}$/.test(pincode)) {
            // Show loading state
            $('#BuyerCity').val('Loading...');
            $('#BuyerState').val('Loading...');
            
            // Use a more reliable approach with proper error handling
            fetchPincodeData(pincode);
        } else {
            $('#BuyerCity').val('');
            $('#BuyerState').val('');
        }
    });

    // Main function to fetch pincode data
    async function fetchPincodeData(pincode) {
        try {
            // Try API 1: PostalPincode (most reliable)
            const response1 = await fetch(`https://api.postalpincode.in/pincode/${pincode}`);
            if (response1.ok) {
                const data1 = await response1.json();
                console.log('API 1 Response:', data1);
                
                if (Array.isArray(data1) && data1[0] && data1[0].Status === 'Success' && data1[0].PostOffice && data1[0].PostOffice.length > 0) {
                    const postOffice = data1[0].PostOffice[0];
                    const city = postOffice.District || postOffice.Name;
                    const state = postOffice.State;
                    
                    if (city && state) {
                        $('#BuyerCity').val(city);
                        $('#BuyerState').val(state);
                        return; // Success, exit function
                    }
                }
            }
        } catch (error) {
            console.log('API 1 failed:', error);
        }

        try {
            // Try API 2: Zippopotam
            const response2 = await fetch(`https://api.zippopotam.us/in/${pincode}`);
            if (response2.ok) {
                const data2 = await response2.json();
                console.log('API 2 Response:', data2);
                
                if (data2 && data2.places && data2.places.length > 0) {
                    const place = data2.places[0];
                    const city = place['place name'];
                    const state = place.state;
                    
                    if (city && state) {
                        $('#BuyerCity').val(city);
                        $('#BuyerState').val(state);
                        return; // Success, exit function
                    }
                }
            }
        } catch (error) {
            console.log('API 2 failed:', error);
        }

        try {
            // Try API 3: Alternative API
            const response3 = await fetch(`https://api.pincode.org.in/pincode/${pincode}`);
            if (response3.ok) {
                const data3 = await response3.json();
                console.log('API 3 Response:', data3);
                
                if (data3 && data3.length > 0 && data3[0].office) {
                    const office = data3[0].office;
                    const city = office.district;
                    const state = office.state;
                    
                    if (city && state) {
                        $('#BuyerCity').val(city);
                        $('#BuyerState').val(state);
                        return; // Success, exit function
                    }
                }
            }
        } catch (error) {
            console.log('API 3 failed:', error);
        }

        // If all APIs fail, silently enable manual entry without annoying alert
        console.log('All APIs failed for pincode:', pincode);
        $('#BuyerCity').val('');
        $('#BuyerState').val('');
        
        // Just focus on city field for manual entry - NO ALERT!
        setTimeout(() => {
            $('#BuyerCity').focus();
        }, 100);
    }

    // ✅ Warehouse Card Click to Fill Hidden Fields
    $('.warehouse-card-anime').on('click', function() {
        $('.warehouse-card-anime').removeClass('selected');
        $(this).addClass('selected');

        $('#selectedWarehouseId').val($(this).data('warehouse-id'));
        $('#warehouseName').val($(this).data('warehouse-name'));
        $('#contactName').val($(this).data('name'));
        $('#addressLine1').val($(this).data('address'));
        $('#addressLine2').val($(this).data('address2'));
        $('#pincode').val($(this).data('pincode'));
        $('#city').val($(this).data('city'));
        $('#state').val($(this).data('state'));
        $('#phone').val($(this).data('phone'));

        $('#displayWarehouseName').text($(this).data('warehouse-name'));
        $('#displayWarehouseAddress').text($(this).data('address') + ($(this).data('address2') ? ', ' + $(this).data('address2') : ''));
        $('#displayWarehouseCityState').text(`${$(this).data('city')}, ${$(this).data('state')}`);
        $('#displayWarehousePincode').text(`Pincode: ${$(this).data('pincode')}`);

        $('#selectedWarehouseDisplay').show();
    });

    // ✅ Search Filter on Warehouses
    $('#warehouseSearch').on('input', function() {
        let searchTerm = $(this).val().toLowerCase();
        $('#warehouseCardsContainer .warehouse-card-col').each(function() {
            let text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });

    // ✅ RTO Toggle Logic
    $('#isRtoDifferent').on('change', function() {
        const readonly = $(this).val() === 'no';
        const rtoFields = $('#rtoAddressFields input');

        if (readonly) {
            $('#rtoWarehouseName').val($('#warehouseName').val());
            $('#rtoContactName').val($('#contactName').val());
            $('#rtoAddress').val($('#addressLine1').val());
            $('#rtoCity').val($('#city').val());
            $('#rtoState').val($('#state').val());
            $('#rtoPincode').val($('#pincode').val());
            $('#rtoPhone').val($('#phone').val());
        } else {
            rtoFields.val('');
        }

        rtoFields.prop('readonly', readonly);
        rtoFields.toggleClass('bg-light', readonly);
    }).trigger('change'); // initialize on page load
});
</script>






{{-- 
<script>
$(document).ready(function() {
    // alert('sssss')
    // ✅ Buyer Pincode Auto-fill City & State
    $('#BuyerPincode').on('input', function () {
        let pincode = $(this).val().trim();
        if (pincode.length === 6 && /^\d{6}$/.test(pincode)) {
            // Show loading state
            $('#BuyerCity').val('Loading...');
            $('#BuyerState').val('Loading...');
            
            // Try multiple APIs for better reliability
            Promise.race([
                // API 1: pinlookup.in
                fetch(`https://pinlookup.in/api/pincode?pincode=${pincode}`, {
                    headers: { "Accept": "application/json" }
                }).then(res => res.json()),
                
                // API 2: Postal PIN Code API (backup)
                fetch(`https://api.postalpincode.in/pincode/${pincode}`)
                .then(res => res.json())
            ])
            .then(data => {
                console.log('API Response:', data); // Debug log
                
                let city = '';
                let state = '';
                
                // Handle pinlookup.in response
                if (data.data && data.data.district_name && data.data.state_name) {
                    city = data.data.district_name;
                    state = data.data.state_name;
                }
                // Handle postalpincode.in response
                else if (Array.isArray(data) && data[0] && data[0].Status === 'Success' && data[0].PostOffice) {
                    city = data[0].PostOffice[0].District;
                    state = data[0].PostOffice[0].State;
                }
                
                if (city && state) {
                    $('#BuyerCity').val(city);
                    $('#BuyerState').val(state);
                } else {
                    alert("Invalid Pincode or No Data Found");
                    $('#BuyerCity').val('');
                    $('#BuyerState').val('');
                }
            })
            .catch(error => {
                console.error('API Error:', error); // Debug log
                alert("Failed to fetch pincode data. Please enter city and state manually.");
                $('#BuyerCity').val('');
                $('#BuyerState').val('');
            });
        } else {
            $('#BuyerCity').val('');
            $('#BuyerState').val('');
        }
    });

    // ✅ Warehouse Card Click to Fill Hidden Fields
    $('.warehouse-card-anime').on('click', function() {
        $('.warehouse-card-anime').removeClass('selected');
        $(this).addClass('selected');

        $('#selectedWarehouseId').val($(this).data('warehouse-id'));
        $('#warehouseName').val($(this).data('warehouse-name'));
        $('#contactName').val($(this).data('name'));
        $('#addressLine1').val($(this).data('address'));
        $('#addressLine2').val($(this).data('address2'));
        $('#pincode').val($(this).data('pincode'));
        $('#city').val($(this).data('city'));
        $('#state').val($(this).data('state'));
        $('#phone').val($(this).data('phone'));

        $('#displayWarehouseName').text($(this).data('warehouse-name'));
        $('#displayWarehouseAddress').text($(this).data('address') + ($(this).data('address2') ? ', ' + $(this).data('address2') : ''));
        $('#displayWarehouseCityState').text(`${$(this).data('city')}, ${$(this).data('state')}`);
        $('#displayWarehousePincode').text(`Pincode: ${$(this).data('pincode')}`);

        $('#selectedWarehouseDisplay').show();
    });

    // ✅ Search Filter on Warehouses
    $('#warehouseSearch').on('input', function() {
        let searchTerm = $(this).val().toLowerCase();
        $('#warehouseCardsContainer .warehouse-card-col').each(function() {
            let text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });

    // ✅ RTO Toggle Logic
    $('#isRtoDifferent').on('change', function() {
        const readonly = $(this).val() === 'no';
        const rtoFields = $('#rtoAddressFields input');

        if (readonly) {
            $('#rtoWarehouseName').val($('#warehouseName').val());
            $('#rtoContactName').val($('#contactName').val());
            $('#rtoAddress').val($('#addressLine1').val());
            $('#rtoCity').val($('#city').val());
            $('#rtoState').val($('#state').val());
            $('#rtoPincode').val($('#pincode').val());
            $('#rtoPhone').val($('#phone').val());
        } else {
            rtoFields.val('');
        }

        rtoFields.prop('readonly', readonly);
        rtoFields.toggleClass('bg-light', readonly);
    }).trigger('change'); // initialize on page load
});
</script> --}}






