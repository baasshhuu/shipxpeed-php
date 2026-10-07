@extends('layouts.sellerdash')

@section('content')
<div class="pc-container">
    <div class="pc-content" style="margin-left:15px;background:#646dff26;">
        <!-- Beautiful Header Section -->
        <div class="warehouse-form-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="form-title">
                            <i class="ti ti-building-warehouse me-3"></i>
                            Create New Warehouse
                        </h1>
                        <p class="form-subtitle">Add a new warehouse location to expand your distribution network</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="{{ route('seller.warehouse.index') }}" class="back-btn">
                            <i class="ti ti-arrow-left me-2"></i>Back to Warehouses
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            {{-- API Error Response --}}
            @if (session('api_response') && is_array(session('api_response')))
                <div class="api-error-card">
                    <div class="error-header">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <strong>API Response Details</strong>
                    </div>
                    <div class="error-body">
                        @php $api = session('api_response'); @endphp

                        @if (isset($api['error']['list-item']))
                            <div class="error-message">
                                <i class="ti ti-x-circle me-2"></i>
                                <strong>Error:</strong>
                                {{ is_array($api['error']['list-item']) ? implode(', ', $api['error']['list-item']) : $api['error']['list-item'] }}
                            </div>
                        @endif

                        <div class="api-details-table">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($api['data']))
                                        @foreach ($api['data'] as $key => $value)
                                            <tr>
                                                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                                                <td>
                                                    @if (is_array($value))
                                                        <pre class="json-display">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    @if (isset($api['success']))
                                        <tr><td>Success</td><td><span class="success-badge">{{ $api['success'] }}</span></td></tr>
                                    @endif
                                    @if (isset($api['error_code']['list-item']))
                                        <tr><td>Error Code</td><td><span class="error-code">{{ $api['error_code']['list-item'] }}</span></td></tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Modern Form Card -->
            <div class="warehouse-form-card">
                <form method="POST" action="{{ route('seller.warehouse.create') }}" class="modern-form">
                    @csrf

                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <div class="section-header">
                            <i class="ti ti-info-circle me-2"></i>
                            <h3>Basic Information</h3>
                            <p>Essential warehouse details and identification</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-building me-2"></i>Warehouse Name
                                    </label>
                                    <input name="name" class="form-control-modern" maxlength="15" required 
                                           placeholder="Enter warehouse name">
                                    <small class="form-hint">Maximum 15 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-file-text me-2"></i>Registered Name
                                    </label>
                                    <input name="registered_name" class="form-control-modern" maxlength="15" required 
                                           placeholder="Enter registered business name">
                                    <small class="form-hint">Official business registration name</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-phone me-2"></i>Phone Number
                                    </label>
                                    <input name="phone" class="form-control-modern" required maxlength="10" 
                                           pattern="\d{10}" title="Please enter a valid 10-digit phone number"
                                           placeholder="1234567890">
                                    <small class="form-hint">10-digit mobile number</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-world me-2"></i>Country
                                    </label>
                                    <input name="country" class="form-control-modern" required value="India" 
                                           placeholder="Enter country">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Primary Address Section -->
                    <div class="form-section">
                        <div class="section-header">
                            <i class="ti ti-map-pin me-2"></i>
                            <h3>Primary Address</h3>
                            <p>Main warehouse location and contact details</p>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-map-2 me-2"></i>Address Line 1
                                    </label>
                                    <textarea name="address" class="form-control-modern" rows="2" required 
                                              placeholder="Building number, street name, area"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-map-2 me-2"></i>Address Line 2
                                    </label>
                                    <textarea name="address_two" class="form-control-modern" rows="2" required 
                                              placeholder="Landmark, nearby location"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-mail me-2"></i>PIN Code
                                    </label>
                                    <input name="pin" id="main_pin" class="form-control-modern" required 
                                           placeholder="123456" maxlength="6" pattern="\d{6}">
                                    <small class="form-hint">6-digit postal code</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-building-skyscraper me-2"></i>City
                                    </label>
                                    <input name="city" id="main_city" class="form-control-modern" required 
                                           placeholder="City name">
                                    <small class="form-hint auto-fill-hint">Auto-filled from PIN</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-flag me-2"></i>State
                                    </label>
                                    <input name="state" id="main_state" class="form-control-modern" required 
                                           placeholder="State name">
                                    <small class="form-hint auto-fill-hint">Auto-filled from PIN</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Return Address Section -->
                    <div class="form-section">
                        <div class="section-header">
                            <i class="ti ti-package-return me-2"></i>
                            <h3>Return Address</h3>
                            <p>Address for product returns and exchanges</p>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-map-2 me-2"></i>Return Address
                                    </label>
                                    <textarea name="return_address" class="form-control-modern" rows="3" required 
                                              placeholder="Complete return address for customers"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-mail me-2"></i>Return PIN Code
                                    </label>
                                    <input name="return_pin" id="return_pin" class="form-control-modern" required 
                                           placeholder="123456" maxlength="6" pattern="\d{6}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-building-skyscraper me-2"></i>Return City
                                    </label>
                                    <input name="return_city" id="return_city" class="form-control-modern" required 
                                           placeholder="City name">
                                    <small class="form-hint auto-fill-hint">Auto-filled from PIN</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-flag me-2"></i>Return State
                                    </label>
                                    <input name="return_state" id="return_state" class="form-control-modern" required 
                                           placeholder="State name">
                                    <small class="form-hint auto-fill-hint">Auto-filled from PIN</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('seller.warehouse.index') }}" class="cancel-btn">
                                <i class="ti ti-x me-2"></i>Cancel
                            </a>
                            <button type="submit" class="submit-btn">
                                <i class="ti ti-check me-2"></i>Create Warehouse
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Header Styling */
    .warehouse-form-header {
        background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 12px 12px; /* tightened */
        margin-bottom: 15px;
        border-radius: 7px;
        box-shadow: 0 6px 18px rgba(102, 126, 234, 0.18);
    }
    
    .form-title {
        font-size: 1.35rem; /* compact */
        font-weight: 700;
        margin-bottom: 0.25rem;
        text-shadow: 0 1px 2px rgba(0,0,0,0.06);
    }
    
    .form-subtitle {
        font-size: 0.85rem;
        opacity: 0.9;
        margin-bottom: 0;
    }
    
    .back-btn {
        background: rgba(255, 255, 255, 0.12);
        color: white;
        padding: 8px 14px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 500;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.16);
        transition: all 0.18s ease;
        font-size: 0.9rem;
    }
    
    .back-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }

    /* API Error Card */
    .api-error-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
        border-left: 4px solid #ff6b6b;
        overflow: hidden;
    }
    
    .error-header {
        background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        color: white;
        padding: 1rem 1.5rem;
        font-weight: 600;
    }
    
    .error-body {
        padding: 1.5rem;
    }
    
    .error-message {
        background: #ffebee;
        border: 1px solid #ffcdd2;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        color: #c62828;
    }
    
    .api-details-table {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e0e0e0;
    }
    
    .api-details-table .table {
        margin-bottom: 0;
    }
    
    .api-details-table thead th {
        background: #f8f9fa;
        border: none;
        font-weight: 600;
        color: #495057;
    }
    
    .json-display {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 4px;
        padding: 8px;
        font-size: 0.85rem;
        margin: 0;
    }
    
    .success-badge {
        background: #d4edda;
        color: #155724;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.85rem;
    }
    
    .error-code {
        background: #f8d7da;
        color: #721c24;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-family: monospace;
    }

    /* Form Card */
    .warehouse-form-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.04);
    }

    /* Form Sections */
    .form-section {
        padding: 1rem 0.9rem; /* reduced */
        border-bottom: 1px solid #f1f3f4;
    }
    
    .form-section:last-child {
        border-bottom: none;
    }
    
    .section-header {
        margin-bottom: 1rem;
        padding-bottom: 0.6rem;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .section-header h3 {
        font-size: 1.05rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
    }
    
    .section-header p {
        color: #6c757d;
        margin-bottom: 0;
        font-size: 0.95rem;
    }

    /* Form Controls */
    .form-group-modern {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        font-size: 0.95rem;
    }
    
    .form-control-modern {
        border: 1.5px solid #e9ecef;
        border-radius: 10px;
        padding: 8px 10px; /* tighter */
        font-size: 0.95rem;
        transition: all 0.18s ease;
        background: #fafbfc;
    }
    
    .form-control-modern:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        background: white;
        outline: none;
    }
    
    .form-control-modern:hover {
        border-color: #d1d5db;
        background: white;
    }
    
    .form-hint {
        color: #6c757d;
        font-size: 0.8rem;
        margin-top: 0.25rem;
        display: block;
    }
    
    .auto-fill-hint {
        color: #28a745;
        font-style: italic;
    }

    /* Form Actions */
    .form-actions {
        background: #f8f9fa;
        padding: 1rem 0.8rem; /* reduced */
        margin: 0;
    }
    
    .cancel-btn {
        background: #6c757d;
        color: white;
        padding: 8px 14px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.18s ease;
        border: none;
        font-size: 0.95rem;
    }
    
    .cancel-btn:hover {
        background: #5a6268;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(108, 117, 125, 0.3);
    }
    
    .submit-btn {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 8px 18px;
        border-radius: 10px;
        border: none;
        font-weight: 600;
        font-size: 0.98rem;
        transition: all 0.18s ease;
        box-shadow: 0 3px 12px rgba(40, 167, 69, 0.24);
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 24px rgba(40, 167, 69, 0.4);
        background: linear-gradient(135deg, #20c997, #17a2b8);
    }
    
    .submit-btn:active {
        transform: translateY(0);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        /* .warehouse-form-header {
            padding: 1rem 0 0.8rem 0;
            margin: -10px -10px 1rem -10px;
            border-radius: 0 0 12px 12px;
        }
         */
        .form-title {
            font-size: 1.15rem;
        }
        
        .form-section {
            padding: 0.9rem 0.75rem;
        }
        
        .form-actions {
            padding: 0.9rem 0.75rem;
        }
        
        .form-actions .d-flex {
            flex-direction: column;
            gap: 0.6rem;
        }
        
        .cancel-btn, .submit-btn {
            text-align: center;
            width: 100%;
            padding: 8px 0;
            font-size: 0.95rem;
        }
    }

    /* Loading States */
    .form-control-modern.loading {
        background-image: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Form Animations */
    .warehouse-form-card {
        animation: slideInUp 0.6s ease;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .form-section {
        animation: fadeIn 0.8s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>

<script>
    function fetchPincodeDetails(pin, cityFieldId, stateFieldId) {
        const cityField = document.getElementById(cityFieldId);
        const stateField = document.getElementById(stateFieldId);
        
        if (pin.length === 6) {
            // Add loading state
            cityField.classList.add('loading');
            stateField.classList.add('loading');
            
            fetch(`https://api.postalpincode.in/pincode/${pin}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data[0] && data[0].Status === 'Success' && data[0].PostOffice) {
                        const postOffice = data[0].PostOffice[0];
                        cityField.value = postOffice.District || '';
                        stateField.value = postOffice.State || '';
                        
                        // Add success animation
                        cityField.style.borderColor = '#28a745';
                        stateField.style.borderColor = '#28a745';
                        
                        setTimeout(() => {
                            cityField.style.borderColor = '';
                            stateField.style.borderColor = '';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error fetching pincode:', error);
                    // Add error state
                    cityField.style.borderColor = '#dc3545';
                    stateField.style.borderColor = '#dc3545';
                })
                .finally(() => {
                    // Remove loading state
                    cityField.classList.remove('loading');
                    stateField.classList.remove('loading');
                });
        }
    }

    // Enhanced PIN code handling with debouncing
    let pinTimeout;
    
    function handlePinInput(inputElement, cityFieldId, stateFieldId) {
        clearTimeout(pinTimeout);
        pinTimeout = setTimeout(() => {
            const pin = inputElement.value.trim();
            if (pin.length === 6 && /^\d{6}$/.test(pin)) {
                fetchPincodeDetails(pin, cityFieldId, stateFieldId);
            }
        }, 500);
    }

    // For main PIN
    document.getElementById('main_pin').addEventListener('input', function () {
        handlePinInput(this, 'main_city', 'main_state');
    });

    // For return PIN
    document.getElementById('return_pin').addEventListener('input', function () {
        handlePinInput(this, 'return_city', 'return_state');
    });

    // Form validation enhancement
    document.querySelector('.modern-form').addEventListener('submit', function(e) {
        const submitBtn = document.querySelector('.submit-btn');
        submitBtn.innerHTML = '<i class="ti ti-loader-2 me-2 spinner"></i>Creating Warehouse...';
        submitBtn.disabled = true;
    });

    // Add spinner animation
    const spinnerStyle = document.createElement('style');
    spinnerStyle.textContent = `
        .spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(spinnerStyle);
</script>

@endsection







<?php /* ?>
@extends('layouts.sellerdash')

@section('content')
    <div class="pc-container">
        <div class="pc-content">
            <div class="container">
                <h2>Create Warehouse</h2>
                @if (session('api_response') && is_array(session('api_response')))
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <strong>Delhivery API Error Details</strong>
                        </div>
                        <div class="card-body">
                            @php
                                $api = session('api_response');
                            @endphp

                            {{-- Error Message --}}
                            @if (isset($api['error']['list-item']))
                                <div class="alert alert-danger">
                                    <strong>Error:</strong>
                                    {{ is_array($api['error']['list-item']) ? implode(', ', $api['error']['list-item']) : $api['error']['list-item'] }}
                                </div>
                            @endif

                            <table class="table table-bordered mt-3">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Field</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($api['data']))
                                        @foreach ($api['data'] as $key => $value)
                                            <tr>
                                                <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                                                <td>
                                                    @if (is_array($value))
                                                        <pre class="mb-0">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    @if (isset($api['success']))
                                        <tr>
                                            <td>Success</td>
                                            <td>{{ $api['success'] }}</td>
                                        </tr>
                                    @endif
                                    @if (isset($api['error_code']['list-item']))
                                        <tr>
                                            <td>Error Code</td>
                                            <td>{{ $api['error_code']['list-item'] }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif


                {{-- Warehouse creation form --}}
                <form method="POST" action="{{ route('seller.warehouse.create') }}">
                    @csrf

                  <div class="form-group">
                        <label>Warehouse Name</label>
                        <input name="name" class="form-control" required>
                    </div>

               <div class="form-group">
                        <label>Registered Name</label>
                        <input name="registered_name" class="form-control" required>
                    </div>
    
                    <div class="form-group">
                        <label>Address -1</label>
                        <textarea name="address" class="form-control" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Address -2</label>
                        <textarea name="address_two" class="form-control" required></textarea>
                    </div>


                     <div class="form-group">
                        <label>PIN</label>
                        <input name="pin" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>City</label>
                        <input name="city" class="form-control" required>
                    </div>
          

                    
                    <div class="form-group">
                        <label>State </label>
                        <input name="state" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input name="phone" class="form-control" required maxlength="10" pattern="\d{10}" 
                        title="Please enter a valid 10-digit phone number">
                    </div>



                    <div class="form-group">
                        <label>Country</label>
                        <input name="country" class="form-control" required>
                    </div>
              
                    <div class="form-group">
                        <label>Return Address</label>
                        <textarea name="return_address" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Return PIN</label>
                        <input name="return_pin" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Return City</label>
                        <input name="return_city" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Return State</label>
                        <input name="return_state" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Warehouse</button>
                </form>
            </div>
        </div>
    </div>
@endsection


<?php */ ?>