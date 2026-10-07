@extends('layouts.sellerdash')

@section('content')
<div class="container-fluid custom-label-container" style="padding-top: 80px; padding-left: 86px; margin-right: 12px;background:#646dff26;">
    <style>
        @media (max-width: 576px) {
            .custom-label-container {
                padding-left: 14px !important;
            }
        }
    </style>
    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('seller.custom-label.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <!-- Hide Product Details Section -->
                <div class="card mb-4">
                    <div class="card-header text-white" style="background:linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;">
                        <h6 class="mb-1">Hide Product Details</h6>
                        <small>Select the box to hide product details on labels</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="hide_sku" id="hide_sku" 
                                           value="1" {{ isset($settings) && $settings->hide_sku ? 'checked' : '' }}>
                                    <label class="form-check-label" for="hide_sku">
                                        Hide SKU
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="hide_product" id="hide_product" 
                                           value="1" {{ isset($settings) && $settings->hide_product ? 'checked' : '' }}>
                                    <label class="form-check-label" for="hide_product">
                                        Hide Product
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="hide_discount" id="hide_discount" 
                                           value="1" {{ isset($settings) && $settings->hide_discount ? 'checked' : '' }}>
                                    <label class="form-check-label" for="hide_discount">
                                        Hide Discount
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="hide_qty" id="hide_qty" 
                                           value="1" {{ isset($settings) && $settings->hide_qty ? 'checked' : '' }}>
                                    <label class="form-check-label" for="hide_qty">
                                        Hide Qty
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="hide_amount" id="hide_amount" 
                                           value="1" {{ isset($settings) && $settings->hide_amount ? 'checked' : '' }}>
                                    <label class="form-check-label" for="hide_amount">
                                        Hide Amount
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Label Setting Section -->
                <div class="card mb-4">
                    <div class="card-header  text-white" style="background:linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;">
                        <h6 class="mb-0">Label Setting</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="label_type" id="standard_desktop" 
                                           value="standard" {{ (!isset($settings) || $settings->label_type == 'standard') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="standard_desktop">
                                        <strong>Standard Desktop Printers</strong><br>
                                        <small class="text-muted">Size A4 (8"X11")</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="label_type" id="thermal_label" 
                                           value="thermal" {{ isset($settings) && $settings->label_type == 'thermal' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="thermal_label">
                                        <strong>Thermal Label Printers</strong><br>
                                        <small class="text-muted">Size (4"X6")</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warehouse Setting Section -->
                <div class="card mb-4">
                    <div class="card-header text-white" style="background:linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;">
                        <h6 class="mb-1">Warehouse Setting</h6>
                        <small>Select the box to hide confidential warehouse details on labels</small>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="hide_return_address_line_1" id="hide_return_address_line_1" 
                                   value="1" {{ isset($settings) && $settings->hide_return_address_line_1 ? 'checked' : '' }}>
                            <label class="form-check-label" for="hide_return_address_line_1">
                                Hide Return Address Line 1
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="hide_return_address_line_2" id="hide_return_address_line_2" 
                                   value="1" {{ isset($settings) && $settings->hide_return_address_line_2 ? 'checked' : '' }}>
                            <label class="form-check-label" for="hide_return_address_line_2">
                                Hide Return Address Line 2
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="hide_return_city_state_pincode" id="hide_return_city_state_pincode" 
                                   value="1" {{ isset($settings) && $settings->hide_return_city_state_pincode ? 'checked' : '' }}>
                            <label class="form-check-label" for="hide_return_city_state_pincode">
                                Hide Return City/State/Pincode
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="hide_return_mobile_number" id="hide_return_mobile_number" 
                                   value="1" {{ isset($settings) && $settings->hide_return_mobile_number ? 'checked' : '' }}>
                            <label class="form-check-label" for="hide_return_mobile_number">
                                Hide Return Mobile Number
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="hide_return_contact_name" id="hide_return_contact_name" 
                                   value="1" {{ isset($settings) && $settings->hide_return_contact_name ? 'checked' : '' }}>
                            <label class="form-check-label" for="hide_return_contact_name">
                                Hide Return Contact Name
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Common Settings Section -->
                <div class="card mb-4">
                    <div class="card-header text-white" style="background:linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;">
                        <h6 class="mb-0">Common Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="show_logo" id="show_logo" 
                                   value="1" {{ isset($settings) && $settings->show_logo ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_logo">
                                <strong>Show Logo on Label</strong><br>
                                <small class="text-muted">Select the box to display your logo image</small>
                            </label>
                        </div>

                        <div class="mb-3">
                            <label for="logo" class="form-label">Upload Logo</label>
                            @if(isset($settings) && $settings->logo_path)
                                <p class="text-success small mb-2">Logo uploaded.</p>
                            @else
                                <p class="text-muted small mb-2">No logo uploaded yet.</p>
                            @endif
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <small class="text-muted">Supported formats: PNG, JPG, JPEG, SVG (Max size: 2MB)</small>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="show_support_contact" id="show_support_contact" 
                                   value="1" {{ isset($settings) && $settings->show_support_contact ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_support_contact">
                                <strong>Show Support No/Mobile No/Brand Name</strong><br>
                                <small class="text-muted">Select the box to showcase support contact info on labels</small>
                            </label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="hide_prepaid_amount" id="hide_prepaid_amount" 
                                   value="1" {{ isset($settings) && $settings->hide_prepaid_amount ? 'checked' : '' }}>
                            <label class="form-check-label" for="hide_prepaid_amount">
                                <strong>Hide Amount In Prepaid Order</strong><br>
                                <small class="text-muted">Select the box to hide the amount in prepaid orders</small>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="hide_customer_mobile" id="hide_customer_mobile" 
                                   value="1" {{ isset($settings) && $settings->hide_customer_mobile ? 'checked' : '' }}>
                            <label class="form-check-label" for="hide_customer_mobile">
                                <strong>Hide Customer Mobile Number</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-gradient-primary px-4 py-2 fw-semibold shadow-sm d-flex align-items-center gap-2" style="background:linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%); border: none; border-radius: .4rem; font-size:1.07rem; letter-spacing:.01em;">
                        <span class="d-inline-flex align-items-center justify-content-center" style="height: 1.4em; width: 1.4em; background: rgba(255,255,255,0.13); border-radius: 50%;">
                            <i class="fas fa-save" style="color: #fff;"></i>
                        </span>
                        <span style="color: #fff;">Save Settings</span>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        🔄 Reset to Default
                    </button>
                    <button type="button" class="btn btn-info" onclick="previewLabel()">
                        👁️ Preview Label
                    </button>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="" style="top: 20px;">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">Label Preview</h6>
                    </div>
                    <div class="card-body" style="min-height: 500px; background-color: #f8f9fa;">
                        <!-- Label Preview Container -->
                        <div id="labelPreview" class="label-preview-container p-3 bg-white border" style="min-height: 450px; border-radius: 8px;">
                            <!-- Logo Section -->
                            <div id="logoSection" class="logo-section mb-3 text-center" style="display: none;">
                                <div class="logo-placeholder p-2 border-dashed" style="border: 2px dashed #dee2e6; border-radius: 4px;">
                                    <i class="fas fa-image text-muted"></i>
                                    <small class="d-block text-muted">Company Logo</small>
                                </div>
                            </div>

                            <!-- Shipping Label Header -->
                            <div class="text-center mb-3">
                                <h6 class="mb-0 fw-bold">SHIPPING LABEL</h6>
                                <small class="text-muted">Order #12345</small>
                            </div>

                            <!-- From Address (Return Address) -->
                            <div class="from-section mb-3 p-2 bg-light rounded">
                                <div class="fw-bold text-uppercase small mb-1">FROM:</div>
                                <div id="returnContactName" class="return-field">John Doe (Seller)</div>
                                <div id="returnAddressLine1" class="return-field">123 Business Street</div>
                                <div id="returnAddressLine2" class="return-field">Suite 100</div>
                                <div id="returnCityStatePincode" class="return-field">Mumbai, MH 400001</div>
                                <div id="returnMobileNumber" class="return-field">+91 98765 43210</div>
                            </div>

                            <!-- To Address -->
                            <div class="to-section mb-3 p-2 bg-light rounded">
                                <div class="fw-bold text-uppercase small mb-1">TO:</div>
                                <div>Customer Name</div>
                                <div>456 Delivery Street</div>
                                <div>Apt 202</div>
                                <div>Delhi, DL 110001</div>
                                <div id="customerMobile" class="customer-field">+91 87654 32109</div>
                            </div>

                            <!-- Product Details -->
                            <div class="product-section mb-3 p-2 border rounded">
                                <div class="fw-bold text-uppercase small mb-2">PRODUCT DETAILS:</div>
                                <div class="row g-2 small">
                                    <div class="col-6" id="skuField">
                                        <strong>SKU:</strong> ABC123
                                    </div>
                                    <div class="col-6" id="qtyField">
                                        <strong>Qty:</strong> 2
                                    </div>
                                    <div class="col-12" id="productField">
                                        <strong>Product:</strong> Sample Product Name
                                    </div>
                                    <div class="col-6" id="discountField">
                                        <strong>Discount:</strong> ₹50
                                    </div>
                                    <div class="col-6" id="amountField">
                                        <strong>Amount:</strong> ₹1,450
                                    </div>
                                </div>
                            </div>

                            <!-- Support Contact -->
                            <div id="supportSection" class="support-section text-center p-2 bg-info bg-opacity-10 rounded" style="display: none;">
                                <small class="fw-bold">Support:</small>
                                <small>+91 98765 43210 | support@company.com</small>
                            </div>

                            <!-- Barcode/Tracking -->
                            <div class="text-center mt-3 pt-2 border-top">
                                <div class="barcode-placeholder p-2">
                                    <div style="height: 30px; background: repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px); margin: 10px 0;"></div>
                                    <small class="text-muted">Tracking: TRK123456789</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resetForm() {
    if(confirm('Are you sure you want to reset all settings to default?')) {
        document.querySelector('form').reset();
        // Uncheck all checkboxes
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        // Set standard printer as default
        document.getElementById('standard_desktop').checked = true;
        // Update preview
        updatePreview();
    }
}

function previewLabel() {
    alert('Live preview is already displayed on the right side!');
}

// Function to update the label preview based on form selections
function updatePreview() {
    // Hide/Show Product Details
    const hideSku = document.getElementById('hide_sku').checked;
    const hideProduct = document.getElementById('hide_product').checked;
    const hideDiscount = document.getElementById('hide_discount').checked;
    const hideQty = document.getElementById('hide_qty').checked;
    const hideAmount = document.getElementById('hide_amount').checked;
    
    // Hide/Show Warehouse/Return Address Details
    const hideReturnAddressLine1 = document.getElementById('hide_return_address_line_1').checked;
    const hideReturnAddressLine2 = document.getElementById('hide_return_address_line_2').checked;
    const hideReturnCityStatePincode = document.getElementById('hide_return_city_state_pincode').checked;
    const hideReturnMobileNumber = document.getElementById('hide_return_mobile_number').checked;
    const hideReturnContactName = document.getElementById('hide_return_contact_name').checked;
    
    // Common Settings
    const showLogo = document.getElementById('show_logo').checked;
    const showSupport = document.getElementById('show_support_contact').checked;
    const hideCustomerMobile = document.getElementById('hide_customer_mobile').checked;
    const hidePrepaidAmount = document.getElementById('hide_prepaid_amount').checked;
    
    // Apply visibility changes
    document.getElementById('skuField').style.display = hideSku ? 'none' : 'block';
    document.getElementById('productField').style.display = hideProduct ? 'none' : 'block';
    document.getElementById('discountField').style.display = hideDiscount ? 'none' : 'block';
    document.getElementById('qtyField').style.display = hideQty ? 'none' : 'block';
    document.getElementById('amountField').style.display = hideAmount ? 'none' : 'block';
    
    // Return address fields
    document.getElementById('returnContactName').style.display = hideReturnContactName ? 'none' : 'block';
    document.getElementById('returnAddressLine1').style.display = hideReturnAddressLine1 ? 'none' : 'block';
    document.getElementById('returnAddressLine2').style.display = hideReturnAddressLine2 ? 'none' : 'block';
    document.getElementById('returnCityStatePincode').style.display = hideReturnCityStatePincode ? 'none' : 'block';
    document.getElementById('returnMobileNumber').style.display = hideReturnMobileNumber ? 'none' : 'block';
    
    // Logo section
    document.getElementById('logoSection').style.display = showLogo ? 'block' : 'none';
    
    // Support section
    document.getElementById('supportSection').style.display = showSupport ? 'block' : 'none';
    
    // Customer mobile
    document.getElementById('customerMobile').style.display = hideCustomerMobile ? 'none' : 'block';
    
    // Handle prepaid amount hiding (modify amount field based on order type)
    const amountField = document.getElementById('amountField');
    if (hidePrepaidAmount && amountField.style.display !== 'none') {
        amountField.innerHTML = '<strong>Amount:</strong> <span class="text-muted">[Hidden for Prepaid]</span>';
    } else if (!hideAmount) {
        amountField.innerHTML = '<strong>Amount:</strong> ₹1,450';
    }
    
    // Update label type styling
    const isStandard = document.getElementById('standard_desktop').checked;
    const labelPreview = document.getElementById('labelPreview');
    
    if (isStandard) {
        labelPreview.style.fontSize = '12px';
        labelPreview.style.padding = '20px';
    } else {
        labelPreview.style.fontSize = '10px';
        labelPreview.style.padding = '15px';
    }
}

// Add event listeners to all form inputs
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[type="checkbox"], input[type="radio"]');
    inputs.forEach(input => {
        input.addEventListener('change', updatePreview);
    });
    
    // Initial preview update
    updatePreview();
});
</script>

<style>
.card {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1rem;
}

.card-header {
    padding: 0.75rem 1.25rem;
    margin-bottom: 0;
    background-color:linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: calc(0.5rem - 1px) calc(0.5rem - 1px) 0 0;
}

.card-body {
    flex: 1 1 auto;
    padding: 1.25rem;
}

.form-check {
    display: block;
    min-height: 1.5rem;
    padding-left: 1.5em;
    margin-bottom: 0.125rem;
}

.form-check-input {
    width: 1em;
    height: 1em;
    margin-top: 0.25em;
    vertical-align: top;
    background-color: #fff;
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
    border: 1px solid rgba(0, 0, 0, 0.25);
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    border-radius: 0.25em;
    float: left;
    margin-left: -1.5em;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.form-check-label {
    display: inline-block;
    margin-bottom: 0;
    cursor: pointer;
}

.bg-primary {
    background-color: #0d6efd !important;
}

.bg-success {
    background-color: #198754 !important;
}

.btn {
    display: inline-block;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    text-align: center;
    text-decoration: none;
    vertical-align: middle;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    user-select: none;
    background-color: transparent;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    border-radius: 0.375rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.btn-primary {
    color: #fff;
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.btn-secondary {
    color: #fff;
    background-color: #6c757d;
    border-color: #6c757d;
}

.btn-info {
    color: #000;
    background-color: #0dcaf0;
    border-color: #0dcaf0;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 1020;
}

.d-flex {
    display: flex !important;
}

.gap-2 {
    gap: 0.5rem !important;
}

@media (max-width: 991.98px) {
    .col-lg-4 {
        margin-top: 2rem;
    }
    
    .sticky-top {
        position: relative !important;
        top: auto !important;
    }
}

/* Label Preview Specific Styles */
.label-preview-container {
    font-family: 'Arial', sans-serif;
    font-size: 12px;
    line-height: 1.4;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.logo-section .logo-placeholder {
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.from-section, .to-section {
    border-left: 3px solid #0d6efd;
}

.product-section {
    background-color: #f8f9fa;
}

.return-field, .customer-field {
    margin-bottom: 2px;
}

.barcode-placeholder {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background-color: #fff;
}

.border-dashed {
    border-style: dashed !important;
}

/* Thermal Label Adjustments */
.thermal-label {
    max-width: 300px;
    margin: 0 auto;
}

/* Animation for field changes */
.return-field, .customer-field, #skuField, #qtyField, #productField, #discountField, #amountField {
    transition: opacity 0.3s ease, height 0.3s ease;
}

.field-hidden {
    opacity: 0.3;
    text-decoration: line-through;
}
</style>
@endsection
