
@extends('layouts.sellerdash')

@section('content')
<style>
    :root {
        --add-order-primary: #6366f1;
        --add-order-primary-light: #8b5cf6;
        --add-order-primary-dark: #4f46e5;
        --add-order-success: #10b981;
        --add-order-success-light: #34d399;
        --add-order-danger: #ef4444;
        --add-order-warning: #f59e0b;
        --add-order-info: #06b6d4;
        --add-order-dark: #1f2937;
        --add-order-light: #f8fafc;
        --add-order-gray: #6b7280;
        --add-order-border: #e5e7eb;
        --add-order-border-light: #f3f4f6;
        --add-order-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        --add-order-shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
        --add-order-shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.15);
        --add-order-radius: 12px;
        --add-order-radius-sm: 8px;
        --add-order-radius-lg: 16px;
        --add-order-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --add-order-font-primary: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Global Styling */
    body {
        font-family: var(--add-order-font-primary);
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        -webkit-font-smoothing: antialiased;
    }

    .pc-container {
        padding: 2rem !important;
        background: transparent !important;
    }

    .pc-content {
        background: white;
        border-radius: var(--add-order-radius-lg);
        box-shadow: var(--add-order-shadow-xl);
        padding: 2rem;
        border: 1px solid var(--add-order-border);
        backdrop-filter: blur(10px);
    }

    /* Compact Header Section */
    .header-section {
        background: linear-gradient(135deg, var(--add-order-primary) 0%, var(--add-order-primary-light) 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: var(--add-order-radius);
        margin-bottom: 1.5rem !important;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
        box-shadow: var(--add-order-shadow-lg);
    }

    .header-section::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 200px;
        height: 200px;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" opacity="0.1"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>') no-repeat;
        background-size: contain;
        opacity: 0.2;
    }

    .heading {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
    }

    .heading::before {
        content: "📦";
        margin-right: 0.75rem;
        font-size: 1.25rem;
    }

    /* Compact Button Styling */
    .export-btn, .export-btn-light {
        border-radius: var(--add-order-radius);
        font-weight: 600;
        padding: 0.5rem 1rem;
        transition: var(--add-order-transition);
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.8rem;
        position: relative;
        overflow: hidden;
    }

    .export-btn {
        background: linear-gradient(135deg, var(--add-order-success) 0%, var(--add-order-success-light) 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }

    .export-btn-light {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        backdrop-filter: blur(10px);
    }

    .export-btn-light:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 255, 255, 0.2);
    }

    /* Enhanced Accordion Styling */
    .accordion {
        --bs-accordion-border-radius: var(--add-order-radius-lg);
        --bs-accordion-border-color: var(--add-order-border);
        box-shadow: var(--add-order-shadow-lg);
    }

    .accordion-item {
        border: none !important;
        border-radius: var(--add-order-radius) !important;
        margin-bottom: 1.5rem;
        overflow: hidden;
        box-shadow: var(--add-order-shadow);
    }

    .accordion-header {
        border-radius: var(--add-order-radius) !important;
    }

    .accordion-button {
        background: linear-gradient(135deg, var(--add-order-light) 0%, white 100%);
        border: none !important;
        border-radius: var(--add-order-radius) !important;
        padding: 1.5rem 2rem;
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--add-order-dark);
        transition: var(--add-order-transition);
        box-shadow: none !important;
    }

    .accordion-button:not(.collapsed) {
        background: linear-gradient(135deg, var(--add-order-primary) 0%, var(--add-order-primary-light) 100%);
        color: white;
        box-shadow: none !important;
    }

    .accordion-button:hover {
        background: linear-gradient(135deg, var(--add-order-primary-light) 0%, var(--add-order-primary) 100%);
        color: white;
    }

    .accordion-button::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='currentColor'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
        transition: var(--add-order-transition);
    }

    .accordion-body {
        padding: 2rem;
        background: white;
        border-top: 2px solid var(--add-order-border-light);
    }

    /* Enhanced Form Controls */
    .form-label {
        font-weight: 600;
        color: var(--add-order-dark);
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
    }

    .form-control, .form-select {
        border: 2px solid var(--add-order-border);
        border-radius: var(--add-order-radius);
        padding: 0.875rem 1.25rem;
        transition: var(--add-order-transition);
        font-weight: 500;
        background: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--add-order-primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        transform: translateY(-1px);
        background: white;
    }

    .input-group-text {
        background: var(--add-order-light);
        border: 2px solid var(--add-order-border);
        border-right: none;
        font-weight: 600;
        color: var(--add-order-gray);
    }

    /* Enhanced Alert Styling */
    .alert {
        border-radius: var(--add-order-radius);
        border: none;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }

    .alert-success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.1) 100%);
        color: var(--add-order-success);
        border-left: 4px solid var(--add-order-success);
    }

    .alert-danger {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(248, 113, 113, 0.1) 100%);
        color: var(--add-order-danger);
        border-left: 4px solid var(--add-order-danger);
    }
</style>

<div class="pc-container">
    <div class="pc-content">
        <div class="header-section">
            <div class="heading">Add New Order</div>
            <div>
                <button class="export-btn-light" onclick="window.location.href='{{ route('seller.orderadd') }}'">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
            </div>
        </div>

        <div class="container-fluid">
            <!-- Single Column Layout (Removed Sidebar) -->
            <div class="row justify-content-center">
                <div class="col-12">
                    @if (session('success'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                        </div>
                    @endif

                        <form method="POST" action="{{ route('order.shipment') }}">
                            @csrf
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
                                                        placeholder="Enter name" value="" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Phone *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">+91</span>
                                                        <input type="text" name="consignee[phone]" class="form-control"
                                                            placeholder="Phone" value="" required maxlength="10" pattern="\d{10}" title="Please enter a valid 10-digit phone number">
                                                    </div>
                                                </div>


                                             <div class="col-md-6">
                                                    <label class="form-label">Buyer Email *</label>
                                                    <input type="email" name="consignee[email]" class="form-control"
                                                        placeholder="Enter email" value="" >
                                                </div>


                                                <div class="col-md-6">
                                                    <label class="form-label">Address Line 1 *</label>
                                                    <input type="text" name="consignee[address]" class="form-control"
                                                        placeholder="Address Line 1" value="" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Address Line 2 *</label>
                                                    <input type="text" name="consignee[address_2]" class="form-control"
                                                        placeholder="Address Line 2" value="" required>
                                                </div>






                                                
<div class="col-md-6">
    <label class="form-label">Pincode *</label>
    <input type="text" name="consignee[pincode]" class="form-control" id="BuyerPincode" placeholder="Pincode" required>
</div>

<div class="col-md-6">
    <label class="form-label">City *</label>
    <input type="text" name="consignee[city]" class="form-control" id="BuyerCity" placeholder="City" required >
</div>

<div class="col-md-6">
    <label class="form-label">State *</label>
    <input type="text" name="consignee[state]" class="form-control" id="BuyerState" placeholder="State" required >
</div>

<script>
document.getElementById('BuyerPincode').addEventListener('input', function () {
    let pincode = this.value.trim();

    // Only call API when exactly 6 digits entered
    if (pincode.length === 6 && /^\d{6}$/.test(pincode)) {
        // Show loading indicator
        const cityField = document.getElementById('BuyerCity');
        const stateField = document.getElementById('BuyerState');
        
        cityField.value = 'Loading...';
        stateField.value = 'Loading...';
        
        // Try primary API first
        fetch(`https://api.postalpincode.in/pincode/${pincode}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data && data.length > 0 && data[0].Status === "Success" && data[0].PostOffice && data[0].PostOffice.length > 0) {
                    cityField.value = data[0].PostOffice[0].District;
                    stateField.value = data[0].PostOffice[0].State;
                } else {
                    throw new Error("Invalid pincode");
                }
            })
            .catch(error => {
                console.error("Primary API failed, trying backup:", error);
                
                // Try backup API
                fetch(`https://api.zippopotam.us/in/${pincode}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Backup API error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.places && data.places.length > 0) {
                            cityField.value = data.places[0]['place name'];
                            stateField.value = data.places[0]['state'];
                        } else {
                            throw new Error("No data from backup API");
                        }
                    })
                    .catch(backupError => {
                        console.error("Both APIs failed:", backupError);
                        cityField.value = '';
                        stateField.value = '';
                        
                        // Show appropriate error message
                        if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                            alert("Network error: Please check your internet connection and try again");
                        } else if (error.message.includes('Invalid pincode')) {
                            alert("Invalid Pincode or No Data Found");
                        } else if (error.message.includes('HTTP error')) {
                            alert("Pincode service is temporarily unavailable. Please enter city and state manually");
                        } else {
                            alert("Unable to fetch pincode data. Please enter city and state manually");
                        }
                    });
            });
    } else {
        // Clear city/state if pincode is not valid
        document.getElementById('BuyerCity').value = '';
        document.getElementById('BuyerState').value = '';
    }
});
</script>

{{-- 
<div class="col-md-6">
    <label class="form-label">Pincode *</label>
    <input type="text" name="consignee[pincode]" class="form-control" id="BuyerPincode" placeholder="Pincode" required>
</div>

<div class="col-md-6">
    <label class="form-label">City *</label>
    <input type="text" name="consignee[city]" class="form-control" id="BuyerCity" placeholder="City" required readonly>
</div>

<div class="col-md-6">
    <label class="form-label">State *</label>
    <input type="text" name="consignee[state]" class="form-control" id="BuyerState" placeholder="State" required readonly>
</div>

<script>
document.getElementById('BuyerPincode').addEventListener('input', function () {
    let pincode = this.value.trim();

    // Only call API when exactly 6 digits entered
    if (pincode.length === 6 && /^\d{6}$/.test(pincode)) {
        fetch(`https://api.postalpincode.in/pincode/${pincode}`)
            .then(response => response.json())
            .then(data => {
                if (data[0].Status === "Success" && data[0].PostOffice && data[0].PostOffice.length > 0) {
                    document.getElementById('BuyerCity').value = data[0].PostOffice[0].District;
                    document.getElementById('BuyerState').value = data[0].PostOffice[0].State;
                } else {
                    alert("Invalid Pincode or No Data Found");
                    document.getElementById('BuyerCity').value = '';
                    document.getElementById('BuyerState').value = '';
                }
            })
            .catch(error => {
                console.error("Error fetching pincode data:", error);
                alert("Failed to fetch data");
                document.getElementById('BuyerCity').value = '';
                document.getElementById('BuyerState').value = '';
            });
    } else {
        // Clear city/state if pincode is not valid
        document.getElementById('BuyerCity').value = '';
        document.getElementById('BuyerState').value = '';
    }
});
</script> --}}



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
                                                <!-- Order ID -->



                                                <div class="col-md-5">
                                                    <label class="form-label">Order ID *</label>
                                                    <div class="input-group">
                                                        <input type="text" id="orderNumber" name="order_number" class="form-control" placeholder="Order ID"  required>
                                                        <button type="button" class="btn btn-secondary" onclick="generateOrderId()">Generate</button>
                                                    </div>
                                                </div>

                                                <script>
                                                function generateOrderId() {
                                                    const randomId = Math.floor(1000 + Math.random() * 9000); // 100 to 999
                                                    document.getElementById("orderNumber").value = "#" + randomId;
                                                }
                                    </script>

                                                <!-- Unique Order Number -->
                                                <div class="col-md-3">
                                                    <label class="form-label">Unique Order Number *</label>
                                                    <select class="form-control" name="unique_order_number">
                                                        <option value="yes" selected>Yes</option>
                                                        <option value="no">No</option>
                                                    </select>
                                                </div>

                                                <!-- Order Type -->
                                                <div class="col-md-3">
                                                    <label class="form-label">Payment Type *</label>
                                                    <select class="form-control" name="payment_type" required>
                                                        <option value="prepaid">Prepaid</option>
                                                        <option value="cod" selected>COD</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Product Details Section -->
                                            <div class="mt-4 p-3 border rounded" id="productSection">
                                                <h5 class="mb-3">Product Details</h5>
                      <input type="hidden" name="reverse" value="reverse">

                                                <div class="product-items">
                                                    <div class="product-item row g-3 align-items-center mb-3">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Product Name *</label>
                                                            <input type="text" name="order_items[0][name]"
                                                                class="form-control" placeholder="Enter product name"
                                                                value="" required>
                                                        </div>
 
                                                        <div class="col-md-3">
                                                            <label class="form-label">SKU *</label>
                                                            <input type="text" name="order_items[0][sku]"
                                                                class="form-control" placeholder="SKU" value="" required>
                                                        </div>


                                                                                                <!-- Quantity Input -->
                                            <div class="col-md-2">
                                                <label class="form-label">Quantity *</label>
                                                <input type="number" name="order_items[0][qty]" class="form-control text-center qty qtyrr" value="" required>
                                            </div>

                                            <!-- Unit Price Input -->
                                            <div class="col-md-3">
                                                <label class="form-label">Unit Price *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₹</span>
                                                    <input type="number" name="order_items[0][price]" class="form-control price pricerr" placeholder="Unit Price" step="0.01" value="" required>
                                                </div>
                                            </div>
                                                  
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Payment & Shipping -->
                                            <div class="row g-3 mt-4">
                                               

                                <div class="col-md-4">
                                    <label class="form-label">Collectable Amount *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" name="collectable_amount" class="form-control collectable_amounttt" placeholder="Collectable Amount" step="0.01" value="" required>
                                    </div>
                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <!-- Warehouse/Pickup Address -->
<!-- Replace the Warehouse/Pickup Address accordion section with this: -->


<!-- Warehouse/Pickup Address Section -->
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
                <!-- Search Box with Anime Style -->
                <div class="col-md-12 mb-3">
                    <div class="search-box-anime position-relative">
                        <input type="text" id="warehouseSearch" class="form-control search-input-anime" 
                               placeholder="Search by Warehouse Name/City Name/Pincode">
                        <i class="fas fa-search search-icon-anime"></i>
                        <div class="search-border-anime"></div>
                    </div>
                </div>
                
                <!-- Warehouse Cards with Anime Style -->
                <div class="col-md-12">
                    <div class="row g-3" id="warehouseCardsContainer">
                        @foreach($warehouses as $warehouse)
                        <div class="col-md-6 warehouse-card-col">
                            <div class="card warehouse-card-anime" 
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
                <input type="hidden" id="selectedWarehouseId" name="selected_warehouse_id" value="">
                <input type="hidden" name="pickup[warehouse_name]" id="warehouseName" value="">
                <input type="hidden" name="pickup[name]" id="contactName" value="">
                <input type="hidden" name="pickup[address]" id="addressLine1" value="">
                <input type="hidden" name="pickup[address_2]" id="addressLine2" value="">
                <input type="hidden" name="pickup[pincode]" id="pincode" value="">
                <input type="hidden" name="pickup[city]" id="city" value="">
                <input type="hidden" name="pickup[state]" id="state" value="">
                <input type="hidden" name="pickup[phone]" id="phone" value="">
                
                <!-- Selected Warehouse Display (Anime Style) -->
                <div class="col-md-12 mt-3" id="selectedWarehouseDisplay" style="display: none;">
                    <div class="alert alert-info-anime">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle me-2"></i>
                            <div>
                                <strong class="d-block">Selected Warehouse:</strong>
                                <span id="displayWarehouseName" class="d-block"></span>
                                <small id="displayWarehouseAddress" class="d-block"></small>
                                <small id="displayWarehouseCityState" class="d-block"></small>
                                <small id="displayWarehousePincode"></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Anime Style Elements */
.search-box-anime {
    position: relative;
    margin-bottom: 20px;
}
.search-input-anime {
    border-radius: 20px;
    padding-left: 40px;
    border: 2px solid #e0e0e0;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.search-input-anime:focus {
    border-color: #ff6b9d;
    box-shadow: 0 0 0 3px rgba(255, 107, 157, 0.2);
}
.search-icon-anime {
    position: absolute;
    top: 50%;
    left: 15px;
    transform: translateY(-50%);
    color: #ff6b9d;
    transition: all 0.3s ease;
}
.search-border-anime {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #ff6b9d, #ff8e53);
    transition: all 0.3s ease;
}
.search-input-anime:focus ~ .search-border-anime {
    width: 100%;
}

/* Anime Card Styles */
.warehouse-card-anime {
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    margin-bottom: 15px;
    height: 180px; /* Smaller card size */
}
.warehouse-card-anime:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.warehouse-card-anime.selected {
    border: 2px solid #7e5bef;
    background-color: rgba(126, 91, 239, 0.05);
}
.warehouse-title-anime {
    font-size: 0.9rem;
    font-weight: 600;
    color: #333;
}
.warehouse-text-anime {
    font-size: 0.8rem;
    color: #666;
}
.card-footer-anime {
    height: 4px;
    background: linear-gradient(90deg, #7e5bef, #a38bff);
    width: 0;
    transition: all 0.3s ease;
}
.warehouse-card-anime:hover .card-footer-anime {
    width: 100%;
}
.warehouse-card-anime.selected .card-footer-anime {
    width: 100%;
    background: linear-gradient(90deg, #ff6b9d, #ff8e53);
}

/* Anime Badge */
.bg-primary-anime {
    background-color: #7e5bef !important;
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 10px;
}

/* Anime Alert */
.alert-info-anime {
    background-color: #f8f9fa;
    border-left: 4px solid #7e5bef;
    border-radius: 5px;
    color: #333;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const warehouseCards = document.querySelectorAll('.warehouse-card-anime');
    const searchInput = document.getElementById('warehouseSearch');
    const warehouseContainer = document.getElementById('warehouseCardsContainer');
    
    // Warehouse card selection
    warehouseCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove selected class from all cards
            warehouseCards.forEach(c => c.classList.remove('selected'));
            
            // Add selected class to clicked card
            this.classList.add('selected');
            
            // Set the hidden input values
            document.getElementById('selectedWarehouseId').value = this.dataset.warehouseId;
            document.getElementById('warehouseName').value = this.dataset.warehouseName;
            document.getElementById('contactName').value = this.dataset.name;
            document.getElementById('addressLine1').value = this.dataset.address;
            document.getElementById('addressLine2').value = this.dataset.address2;
            document.getElementById('pincode').value = this.dataset.pincode;
            document.getElementById('city').value = this.dataset.city;
            document.getElementById('state').value = this.dataset.state;
            document.getElementById('phone').value = this.dataset.phone;
            
            // Update the selected warehouse display
            document.getElementById('displayWarehouseName').textContent = this.dataset.warehouseName;
            document.getElementById('displayWarehouseAddress').textContent = this.dataset.address + (this.dataset.address2 ? ', ' + this.dataset.address2 : '');
            document.getElementById('displayWarehouseCityState').textContent = `${this.dataset.city}, ${this.dataset.state}`;
            document.getElementById('displayWarehousePincode').textContent = `Pincode: ${this.dataset.pincode}`;
            
            // Show the selected warehouse display
            document.getElementById('selectedWarehouseDisplay').style.display = 'block';
        });
    });
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = warehouseContainer.querySelectorAll('.warehouse-card-col');
        
        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
</script>



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
                                                        placeholder="Total Weight" step="0.01" value="" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Length (cm) *</label>
                                                    <input type="number" name="package_length" class="form-control"
                                                        placeholder="Length" step="0.01" value="" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Width (cm) *</label>
                                                    <input type="number" name="package_breadth" class="form-control"
                                                        placeholder="Width" step="0.01" value="" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Height (cm) *</label>
                                                    <input type="number" name="package_height" class="form-control"
                                                        placeholder="Height" step="0.01" value="" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>




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
                        <option value="no">No</option>
                        <option value="yes" selected>Yes</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-3" id="rtoAddressFields">
                <div class="col-md-6">
                    <label class="form-label">RTO Warehouse Name *</label>
                    <input type="text" name="rto[warehouse_name]" id="rtoWarehouseName" class="form-control"
                        placeholder="RTO Warehouse Name" value="">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Name *</label>
                    <input type="text" name="rto[name]" id="rtoContactName" class="form-control"
                        placeholder="Contact Name" value="">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Address Line 1 *</label>
                    <input type="text" name="rto[address]" id="rtoAddress" class="form-control"
                        placeholder="Address Line 1" value="" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">City *</label>
                    <input type="text" name="rto[city]" id="rtoCity" class="form-control"
                        placeholder="City" value="" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">State *</label>
                    <input type="text" name="rto[state]" id="rtoState" class="form-control"
                        placeholder="State" value="" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Pincode *</label>
                    <input type="text" name="rto[pincode]" id="rtoPincode" class="form-control"
                        placeholder="Pincode" value="" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone *</label>
                    <input type="text" name="rto[phone]" id="rtoPhone" class="form-control"
                        placeholder="Phone" value="" required maxlength="10" pattern="\d{10}" title="Please enter a valid 10-digit phone number">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rtoToggle = document.getElementById('isRtoDifferent');
    const rtoFields = document.getElementById('rtoAddressFields').querySelectorAll('input');
    
    rtoToggle.addEventListener('change', function() {
        if (this.value === 'no') {
            // Copy pickup address to RTO fields
            document.getElementById('rtoWarehouseName').value = document.getElementById('warehouseName').value;
            document.getElementById('rtoContactName').value = document.getElementById('contactName').value;
            document.getElementById('rtoAddress').value = document.getElementById('addressLine1').value;
            document.getElementById('rtoCity').value = document.getElementById('city').value;
            document.getElementById('rtoState').value = document.getElementById('state').value;
            document.getElementById('rtoPincode').value = document.getElementById('pincode').value;
            document.getElementById('rtoPhone').value = document.getElementById('phone').value;
            
            // Make fields read-only
            rtoFields.forEach(field => {
                field.readOnly = true;
                field.classList.add('bg-light');
            });
        } else {
            // Clear fields and make editable
            rtoFields.forEach(field => {
                field.value = '';
                field.readOnly = false;
                field.classList.remove('bg-light');
            });
        }
    });

    // Initialize on page load if "No" is selected
    if (rtoToggle.value === 'no') {
        rtoToggle.dispatchEvent(new Event('change'));
    }
});
</script>

<style>
.bg-light {
    background-color: #f8f9fa !important;
}
</style>



                               

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
                                                        <option value="yes" selected>Yes</option>
                                                        <option value="no">No</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Courier ID</label>
                                                    <input type="text" name="courier_id" class="form-control"
                                                        placeholder="Courier ID" value="">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="export-btn" id="submitOrderForm">
                                <i class="fas fa-save me-2"></i> 
                                <span id="submitText">Save Order</span>
                                <span id="loadingSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



@push('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const orderForm = document.getElementById('orderForm');
            if (orderForm) {
                orderForm.addEventListener('submit', async function(event) {
                    event.preventDefault();

                    const submitText = document.getElementById('submitText');
                    const loadingSpinner = document.getElementById('loadingSpinner');
                    const submitButton = document.getElementById('submitOrderForm');

                    submitText.textContent = 'Processing...';
                    loadingSpinner.classList.remove('d-none');
                    submitButton.disabled = true;

                    try {
                        const formData = new FormData(orderForm);

                        const jsonData = {
                            order_number: formData.get('order_number'),
                            unique_order_number: formData.get('unique_order_number'),
                            shipping_charges: parseFloat(formData.get('shipping_charges')) || 0,
                            discount: parseFloat(formData.get('discount')) || 0,
                            cod_charges: parseFloat(formData.get('cod_charges')) || 0,
                            payment_type: formData.get('payment_type'),
                            order_amount: parseFloat(formData.get('order_amount')) || 0,
                            package_weight: parseFloat(formData.get('package_weight')) || 0,
                            package_length: parseFloat(formData.get('package_length')) || 0,
                            package_breadth: parseFloat(formData.get('package_breadth')) || 0,
                            package_height: parseFloat(formData.get('package_height')) || 0,
                            request_auto_pickup: formData.get('request_auto_pickup'),
                            is_rto_different: formData.get('is_rto_different'),
                            courier_id: formData.get('courier_id'),
                            collectable_amount: parseFloat(formData.get('collectable_amount')) || 0,
                            consignee: {
                                name: formData.get('consignee[name]'),
                                address: formData.get('consignee[address]'),
                                address_2: formData.get('consignee[address_2]') || '',
                                city: formData.get('consignee[city]'),
                                state: formData.get('consignee[state]'),
                                pincode: formData.get('consignee[pincode]'),
                                phone: formData.get('consignee[phone]'),
                                email: formData.get('consignee[email]') || ''
                            },
                            pickup: {
                                warehouse_name: formData.get('pickup[warehouse_name]') || '',
                                name: formData.get('pickup[name]'),
                                address: formData.get('pickup[address]'),
                                address_2: formData.get('pickup[address_2]') || '',
                                city: formData.get('pickup[city]'),
                                state: formData.get('pickup[state]'),
                                pincode: formData.get('pickup[pincode]'),
                                phone: formData.get('pickup[phone]')
                            },
                            rto: {
                                warehouse_name: formData.get('rto[warehouse_name]') || '',
                                name: formData.get('rto[name]') || '',
                                address: formData.get('rto[address]') || '',
                                city: formData.get('rto[city]') || '',
                                state: formData.get('rto[state]') || '',
                                pincode: formData.get('rto[pincode]') || '',
                                phone: formData.get('rto[phone]') || ''
                            },
                            order_items: [{
                                name: formData.get('order_items[0][name]'),
                                qty: parseInt(formData.get('order_items[0][qty]')) || 1,
                                price: parseFloat(formData.get('order_items[0][price]')) ||
                                    0,
                                sku: formData.get('order_items[0][sku]') || ''
                            }]
                        };

                        const laravelResponse = await fetch('{{ route('seller.orderadd') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(jsonData)
                        });

                        const laravelResult = await laravelResponse.json();

                        if (!laravelResponse.ok) {
                            throw new Error(laravelResult.message || 'Failed to save order locally');
                        }

                        const thirdPartyResponse = await fetch(
                            'https://shipment.xpressbees.com/api/shipments2', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'Authorization': 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpYXQiOjE3NDYyNTAxODksImp0aSI6IlJlZVBVSm54VUVzTWtSTWhBNmJMajYwSDh3MEtIRkYrVnVnVlN0a2FOKzQ9IiwibmJmIjoxNzQ2MjUwMTg5LCJleHAiOjE3NDYyNjA5ODksImRhdGEiOnsidXNlcl9pZCI6IjEzNDgxMSIsInBhcmVudF9pZCI6IjAiLCJlbWFpbCI6IlNoaXB4cGVlZEBnbWFpbC5jb20ifX0.8enVDL2T9_4y0wTA3sHxFmiry3LyOErB7-RtxDxSGg_NFt6MxNtld0h2cU1Of9eBQ3_kQzOQjiuL1GL2wq1_Ew'
                                },
                                body: JSON.stringify(jsonData)
                            });

                        const thirdPartyResult = await thirdPartyResponse.json();

                        if (!thirdPartyResponse.ok) {
                            throw new Error(thirdPartyResult.message || 'XpressBees API error');
                        }

                        alert('Order created successfully with XpressBees!');
                        if (thirdPartyResult.tracking_number) {
                            alert(`Tracking Number: ${thirdPartyResult.tracking_number}`);
                        }

                        window.location.href = '{{ route('seller.order') }}';

                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error: ' + error.message);
                    } finally {
                        submitText.textContent = 'Save';
                        loadingSpinner.classList.add('d-none');
                        submitButton.disabled = false;
                    }
                });
            }
        });
    </script>
@endpush


