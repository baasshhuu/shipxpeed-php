@extends('layouts.sellerdash')

@section('content')
<div class="pc-container" style="background:#646dff26;">
    <div class="pc-content" style="margin-left:12px;">
        <!-- Beautiful Header Section -->
        <div class="warehouse-edit-header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="form-title">
                            <i class="ti ti-edit me-3"></i>
                            Edit Warehouse
                        </h1>
                        <p class="form-subtitle">Update warehouse information and location details</p>
                        <div class="warehouse-info-chip">
                            <i class="ti ti-building me-2"></i>
                            Editing: <strong>{{ $warehouse->name }}</strong>
                        </div>
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
            {{-- Success Message --}}
            @if (session('success'))
                <div class="success-alert">
                    <div class="success-content">
                        <i class="ti ti-check-circle me-3"></i>
                        <div>
                            <strong>Success!</strong>
                            <p class="mb-0">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Modern Form Card -->
            <div class="warehouse-edit-card">
                <form method="POST" action="{{ route('seller.warehouse.update', $warehouse->id) }}" class="modern-form">
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
                                    <input name="name" class="form-control-modern" 
                                           value="{{ old('name', $warehouse->name) }}" required 
                                           placeholder="Enter warehouse name">
                                    <small class="form-hint">Unique identifier for this warehouse</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-file-text me-2"></i>Registered Name
                                    </label>
                                    <input name="registered_name" class="form-control-modern" 
                                           value="{{ old('registered_name', $warehouse->registered_name) }}" required 
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
                                    <input name="phone" class="form-control-modern" 
                                           value="{{ old('phone', $warehouse->phone) }}" required 
                                           maxlength="10" pattern="\d{10}" 
                                           title="Please enter a valid 10-digit phone number"
                                           placeholder="1234567890">
                                    <small class="form-hint">10-digit mobile number</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-world me-2"></i>Country
                                    </label>
                                    <input name="country" class="form-control-modern" 
                                           value="{{ old('country', $warehouse->country) }}" required 
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
                                              placeholder="Building number, street name, area">{{ old('address', $warehouse->address_title) }}</textarea>
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
                                              placeholder="Landmark, nearby location">{{ old('address_two', $warehouse->address_line2) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-mail me-2"></i>PIN Code
                                    </label>
                                    <input name="pin" id="main_pin" class="form-control-modern" 
                                           value="{{ old('pin', $warehouse->pincode) }}" required 
                                           placeholder="123456" maxlength="6" pattern="\d{6}">
                                    <small class="form-hint">6-digit postal code</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-building-skyscraper me-2"></i>City
                                    </label>
                                    <input name="city" id="main_city" class="form-control-modern" 
                                           value="{{ old('city', $warehouse->city) }}" required 
                                           placeholder="City name">
                                    <small class="form-hint auto-fill-hint">Auto-filled from PIN</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-flag me-2"></i>State
                                    </label>
                                    <input name="state" id="main_state" class="form-control-modern" 
                                           value="{{ old('state', $warehouse->state) }}" required 
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
                                              placeholder="Complete return address for customers">{{ old('return_address', $warehouse->return_address) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-mail me-2"></i>Return PIN Code
                                    </label>
                                    <input name="return_pin" id="return_pin" class="form-control-modern" 
                                           value="{{ old('return_pin', $warehouse->return_pin) }}" required 
                                           placeholder="123456" maxlength="6" pattern="\d{6}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-building-skyscraper me-2"></i>Return City
                                    </label>
                                    <input name="return_city" id="return_city" class="form-control-modern" 
                                           value="{{ old('return_city', $warehouse->return_city) }}" required 
                                           placeholder="City name">
                                    <small class="form-hint auto-fill-hint">Auto-filled from PIN</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group-modern">
                                    <label class="form-label">
                                        <i class="ti ti-flag me-2"></i>Return State
                                    </label>
                                    <input name="return_state" id="return_state" class="form-control-modern" 
                                           value="{{ old('return_state', $warehouse->return_state) }}" required 
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
                                <i class="ti ti-x me-2"></i>Cancel Changes
                            </a>
                            <div class="action-buttons">
                                <button type="reset" class="reset-btn">
                                    <i class="ti ti-refresh me-2"></i>Reset Form
                                </button>
                                <button type="submit" class="submit-btn">
                                    <i class="ti ti-device-floppy me-2"></i>Update Warehouse
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Header Styling */
    .warehouse-edit-header {
        background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 0.5rem 0.5rem;
        /* margin: -20px -20px 2rem -20px; */
        border-radius: 7px;
        box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
    }
    
    .form-title {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-subtitle {
        font-size: 0.9rem;
        opacity: 0.9;
        
    }
    
    .warehouse-info-chip {
        background: rgba(255, 255, 255, 0.15);
        padding: 8px 8px;
        border-radius: 7px;
        font-size: 0.8rem;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        display: inline-block;
    }
    
    .back-btn {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 500;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.3s ease;
    }
    
    .back-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }

    /* Success Alert */
    .success-alert {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border: 1px solid #c3e6cb;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 16px rgba(40, 167, 69, 0.1);
    }
    
    .success-content {
        display: flex;
        align-items: center;
        color: #155724;
    }
    
    .success-content i {
        font-size: 1.5rem;
        color: #28a745;
    }

    /* Form Card */
    .warehouse-edit-card {
        background: white;
        border-radius: 7px;
        margin-top:15px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
    }

    /* Form Sections */
    .form-section {
        padding: 1rem;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .form-section:last-child {
        border-bottom: none;
    }
    
    .section-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8f9fa;
    }
    
    .section-header h3 {
        font-size: 1.4rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
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
        border: 2px solid #e9ecef;
        border-radius: 7px;
        padding: 8px 8px;
        font-size: 0.8rem;
        transition: all 0.3s ease;
        background: #fafbfc;
        resize: vertical;
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
        font-size: 0.75rem;
        margin-top: 0.25rem;
        display: block;
    }
    
    .auto-fill-hint {
        color: #28a745;
        font-style: italic;
    }

    /* Form Actions */
    .form-actions {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        padding: 2rem;
        margin: 0;
    }
    
    .action-buttons {
        display: flex;
        gap: 1rem;
    }
    
    .cancel-btn {
        background: black;
        color: white;
        padding: 10px 12px;
        border-radius: 7px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
    }
    
    .cancel-btn:hover {
        background: #5a6268;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(108, 117, 125, 0.3);
    }
    
    .reset-btn {
        background: #ffc107;
        color: #212529;
        padding: 10px 12px;
        border-radius: 7px;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .reset-btn:hover {
        background: #e0a800;
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(255, 193, 7, 0.3);
    }
    
    .submit-btn {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 10px 12px;
        border-radius: 7px;
        border: none;
        /* font-weight: 600; */
        font-size: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(40, 167, 69, 0.3);
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 24px rgba(40, 167, 69, 0.4);
        background: linear-gradient(135deg, #20c997, #17a2b8);
    }
    
    .submit-btn:active {
        transform: translateY(0);
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

    /* Responsive Design */
    @media (max-width: 768px) {
        /* .warehouse-edit-header {
            padding: 2rem 0 1.5rem 0;
            margin: -15px -15px 1.5rem -15px;
            border-radius: 0 0 16px 16px;
        } */
        
        .form-title {
            font-size: 2rem;
        }
        
        .form-section {
            padding: 1.5rem 1rem;
        }
        
        .form-actions {
            padding: 1.5rem 1rem;
        }
        
        .form-actions .d-flex {
            flex-direction: column;
            gap: 1rem;
        }
        
        .action-buttons {
            flex-direction: column;
            width: 100%;
        }
        
        .cancel-btn, .reset-btn, .submit-btn {
            text-align: center;
            width: 100%;
        }
        
        .warehouse-info-chip {
            display: block;
            width: fit-content;
           
        }
    }

    /* Form Animations */
    .warehouse-edit-card {
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

    /* Enhanced Visual Effects */
    .form-control-modern.success {
        border-color: #28a745;
        background: #f8fff9;
    }
    
    .form-control-modern.error {
        border-color: #dc3545;
        background: #fff8f8;
    }
</style>

<script>
    function fetchPincodeDetails(pin, cityFieldId, stateFieldId) {
        const cityField = document.getElementById(cityFieldId);
        const stateField = document.getElementById(stateFieldId);
        
        if (pin.length === 6 && /^\d{6}$/.test(pin)) {
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
                        cityField.classList.add('success');
                        stateField.classList.add('success');
                        
                        setTimeout(() => {
                            cityField.classList.remove('success');
                            stateField.classList.remove('success');
                        }, 2000);
                    } else {
                        // Add error state
                        cityField.classList.add('error');
                        stateField.classList.add('error');
                        
                        setTimeout(() => {
                            cityField.classList.remove('error');
                            stateField.classList.remove('error');
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error fetching pincode:', error);
                    cityField.classList.add('error');
                    stateField.classList.add('error');
                    
                    setTimeout(() => {
                        cityField.classList.remove('error');
                        stateField.classList.remove('error');
                    }, 2000);
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
        submitBtn.innerHTML = '<i class="ti ti-loader-2 me-2 spinner"></i>Updating Warehouse...';
        submitBtn.disabled = true;
    });

    // Reset form functionality
    document.querySelector('.reset-btn').addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to reset all changes? This will restore the original values.')) {
            location.reload();
        }
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

    // Enhanced form validation
    document.querySelectorAll('.form-control-modern').forEach(input => {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.classList.remove('error');
            } else {
                this.classList.add('error');
            }
        });
    });
</script>

@endsection
