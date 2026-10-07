@extends('layouts.sellerdash')

@section('content')
<div class="pc-container" style="background:#646dff26;">
    <div class="pc-content">
        <div class="d-flex align-items-center py-3" style="background: #fff; border-radius: 16px 16px 16px 16px; padding-left: 32px; padding-right: 32px; margin-left:12px;">
            <h2 class="fw-semibold mb-0" style="font-size: 1rem; letter-spacing: 0.01em;color:rgb(37 120 197) !important;">
                Rate Calculator
            </h2>
        </div>

        <div class="mt-4">
            <div class="card shadow-lg border-0 rounded-4" style="margin-left: 12px;">
                <div class="card-header text-white rounded-top-4 py-3" style="background:linear-gradient(135deg, #8192e1 0%, #83a8eb 100%);">
                    <h4 class="mb-0" style="font-size:1rem;">
                        <i class="ti ti-calculator me-2"></i>
                        Shipping Rate Calculator
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Alert for pincode status -->
                    <div id="pincode-alert" class="alert alert-info d-none" role="alert">
                        <i class="ti ti-info-circle me-2"></i>
                        <span id="alert-message">Enter a valid 6-digit pincode to get location details</span>
                    </div>
                    
                    <form id="rate-calculator-form" method="POST" action="{{ route('seller.check.rate') }}">
                        @csrf
                        
                        <div class="row g-4">
                            <!-- Origin Section -->
                            <div class="col-md-6">
                                <div class="form-section">
                                    <h6 class="text-primary mb-3">
                                        <i class="ti ti-map-pin me-2"></i>Origin Details
                                    </h6>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text bg-light">
                                            <i class="ti ti-location"></i>
                                        </span>
                                        <input type="text" name="origin" id="originPincode" 
                                               class="form-control form-control-lg" 
                                               placeholder="Origin Pincode" required>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="text" id="originCity" 
                                                   class="form-control bg-light" 
                                                   placeholder="City" readonly>
                                        </div>
                                        <div class="col-6">
                                            <input type="text" id="originState" 
                                                   class="form-control bg-light" 
                                                   placeholder="State" name="originState" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Destination Section -->
                            <div class="col-md-6">
                                <div class="form-section">
                                    <h6 class="text-success mb-3">
                                        <i class="ti ti-target me-2"></i>Destination Details
                                    </h6>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text bg-light">
                                            <i class="ti ti-location"></i>
                                        </span>
                                        <input type="text" name="destination" id="destinationPincode" 
                                               class="form-control form-control-lg" 
                                               placeholder="Destination Pincode" required>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="text" id="destinationCity" 
                                                   class="form-control bg-light" 
                                                   placeholder="City" readonly>
                                        </div>
                                        <div class="col-6">
                                            <input type="text" id="destinationState" 
                                                   class="form-control bg-light" 
                                                   placeholder="State" name="destinationState" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Package Details -->
                            <div class="col-12">
                                <div class="form-section">
                                    <h6 class="text-info mb-3">
                                        <i class="ti ti-package me-2"></i>Package Details
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">
                                                    <i class="ti ti-currency-rupee"></i>
                                                </span>
                                                <input type="number" name="order_amount" 
                                                       class="form-control form-control-lg" 
                                                       placeholder="Invoice Value" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">
                                                    <i class="ti ti-weight"></i>
                                                </span>
                                                <input type="number" name="weight" 
                                                       class="form-control form-control-lg" 
                                                       placeholder="Weight (grams)" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-3 mt-2">
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">L</span>
                                                <input type="number" name="length" 
                                                       class="form-control" 
                                                       placeholder="Length (cm)" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">W</span>
                                                <input type="number" name="breadth" 
                                                       class="form-control" 
                                                       placeholder="Width (cm)" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">H</span>
                                                <input type="number" name="height" 
                                                       class="form-control" 
                                                       placeholder="Height (cm)" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Type -->
                            <div class="col-md-6 mx-auto">
                                <div class="form-section text-center">
                                    <h6 class="text-warning mb-3">
                                        <i class="ti ti-credit-card me-2"></i>Payment Method
                                    </h6>
                                    <select name="payment_type" class="form-select form-select-lg" required style="font-size:14px;">
                                        <option value="">Select Payment Type</option>
                                        <option value="cod">💰 Cash on Delivery</option>
                                        <option value="prepaid">💳 Prepaid</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-5">
                            <button type="submit" class="btn btn-lg py-3" style="background:linear-gradient(135deg, #8192e1 0%, #83a8eb 100%);padding:8px 12px;">
                                <i class="ti ti-calculator me-2"></i>
                                Calculate Rates
                                <span class="spinner-border spinner-border-sm ms-2 d-none" id="loading-spinner"></span>
                            </button>
                        </div>
                    </form>

                @if (session('rate_data') && is_array(session('rate_data')))
                <div id="api-response" class="mt-5">
                    <div class="card border-0 shadow-lg rounded-4">
                        <div class="card-header text-white rounded-top-4 py-3" style="background: linear-gradient(135deg, #8192e1 0%, #83a8eb 100%);">
                            <h4 class="mb-0 text-center">
                                <i class="ti ti-truck-delivery me-2"></i>
                                Available Shipping Rates
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th><i class="ti ti-truck me-2"></i>Service Provider</th>
                                            <th class="text-center"><i class="ti ti-currency-rupee me-1"></i>Freight</th>
                                            <th class="text-center"><i class="ti ti-credit-card me-1"></i>COD</th>
                                            <th class="text-center"><i class="ti ti-receipt me-1"></i>Total</th>
                                            <th class="text-center">Min Weight</th>
                                            <th class="text-center">Chargeable Weight</th>
                                        </tr>
                                    </thead>

<tbody>
    @foreach (session('rate_data') as $index => $item)
        <tr class="rate-row {{ $loop->even ? 'table-light' : '' }}">
            <td class="text-center fw-bold text-primary">{{ $index + 1 }}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="service-icon me-3">
                        @if(Str::contains($item['name'], 'Shadowfax'))
                            <div class="p-2">
                                <i class="ti ti-bolt"></i>
                            </div>
                        @elseif(Str::contains($item['name'], 'Delhivery'))
                            <div class="p-2">
                                <i class="ti ti-truck"></i>
                            </div>
                        @elseif(Str::contains($item['name'], 'XpressBees'))
                            <div class=" p-2">
                                <i class="ti ti-plane"></i>
                            </div>
                        @elseif(Str::contains($item['name'], 'Blue'))
                            <div class=" p-2">
                                <i class="ti ti-package"></i>
                            </div>
                        @else
                            <div class=" p-2">
                                <i class="ti ti-truck"></i>
                            </div>
                        @endif
                    </div>
                    <div>
                        <div class="fw-semibold">
                            @if(
                                Str::contains($item['name'], 'Shadowfax') ||
                                Str::contains($item['name'], 'Delhivery B2C (Express)') ||
                                Str::contains($item['name'], 'Delhivery B2C (Surface)') ||
                                Str::contains($item['name'], 'Bluedart- Surface') ||
                                Str::contains($item['name'], 'DTDC-SMART')
                            )
                                {{ $item['name'] }} <span class="badge bg-light text-dark ms-1">0.5Kg</span>
                            @else
                                {{ $item['name'] }}
                            @endif
                        </div>
                        @if(Str::contains($item['name'], 'Express'))
                            <small class="text-success">⚡ Fast Delivery</small>
                        @elseif(Str::contains($item['name'], 'Surface'))
                            <small class="text-info">🚛 Standard Delivery</small>
                        @elseif(Str::contains($item['name'], 'Air'))
                            <small class="text-primary">✈️ Air Service</small>
                        @endif
                    </div>
                </div>
            </td>
            <td class="text-center">
                <span class="fw-semibold text-dark">₹{{ number_format($item['freight_charges'], 2) }}</span>
            </td>
            <td class="text-center">
                @if($item['cod_charges'] > 0)
                    <span class="fw-semibold text-warning">₹{{ number_format($item['cod_charges'], 2) }}</span>
                @else
                    <span class="text-muted">-</span>
                @endif
            </td>
            <td class="text-center" style="color:black;">
                <span class="fs-6 px-3 py-2" style="color:black;">
                    ₹{{ number_format($item['total_charges'], 2) }}
                </span>
            </td>
            <td class="text-center text-muted">{{ $item['min_weight'] }}g</td>
            <td class="text-center text-muted">{{ $item['chargeable_weight'] }}g</td>
        </tr>
    @endforeach
</tbody>

                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light text-center py-3">
                            <small class="text-muted">
                                <i class="ti ti-info-circle me-1"></i>
                                All prices are inclusive of GST. Choose the best option for your shipment.
                            </small>
                        </div>
                    </div>
                </div>
                @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        --hover-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    .bg-gradient-primary {
        background: var(--primary-gradient) !important;
    }

    .bg-gradient-success {
        background: var(--success-gradient) !important;
    }

    .card {
        border: none;
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: var(--hover-shadow);
    }

    .shadow-lg {
        box-shadow: var(--card-shadow) !important;
    }

    .rounded-4 {
        border-radius: 1rem !important;
    }

    .rounded-top-4 {
        border-radius: 1rem 1rem 0 0 !important;
    }

    .form-section {
        background: rgba(248, 249, 250, 0.5);
        border-radius: 0.75rem;
        padding: 1.5rem;
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .form-section:hover {
        background: rgba(248, 249, 250, 0.8);
        transform: translateY(-1px);
    }

    .form-control, .form-select {
        border: 2px solid #e9ecef;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        transform: translateY(-1px);
    }

    .form-control-lg {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }

    .input-group-text {
        border: 2px solid #e9ecef;
        border-right: none;
        background: #f8f9fa;
        color: #6c757d;
        font-weight: 500;
    }

    .btn-primary {
        background: var(--primary-gradient);
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }

    .table {
        font-size: 0.95rem;
    }

    .table th {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.85rem;
        padding: 1rem 0.75rem;
    }

    .table td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
    }

    .rate-row {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .rate-row:hover {
        background: rgba(102, 126, 234, 0.05) !important;
        transform: translateX(3px);
    }

    .service-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 1.2rem;
    }

    .badge {
        font-size: 0.9rem;
        font-weight: 500;
    }

    .top-toggler {
        background: var(--primary-gradient);
        border-radius: 0.75rem;
        padding: 0.75rem 1.5rem;
        color: white;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .top-toggler:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        color: white;
    }

    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    /* Loading Animation */
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .form-section {
            padding: 1rem;
        }
        
        .card-body {
            padding: 2rem 1.5rem !important;
        }
        
        .service-icon {
            width: 35px;
            height: 35px;
            font-size: 1rem;
        }
        
        .table {
            font-size: 0.85rem;
        }
        
        .table th, .table td {
            padding: 0.75rem 0.5rem;
        }
    }

    /* Custom Scrollbar */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(90deg, #764ba2, #667eea);
    }
</style>



<script>
// Multiple API endpoints for fallback
const PINCODE_APIS = [
    {
        name: 'IndiaPost',
        url: (pincode) => `https://api.postalpincode.in/pincode/${pincode}`,
        parser: (data) => {
            if (data && data[0] && data[0].Status === 'Success' && data[0].PostOffice && data[0].PostOffice.length > 0) {
                const postOffice = data[0].PostOffice[0];
                return {
                    city: postOffice.District,
                    state: postOffice.State,
                    success: true
                };
            }
            return { success: false };
        }
    },
    {
        name: 'ZipCodeAPI',
        url: (pincode) => `https://api.zippopotam.us/in/${pincode}`,
        parser: (data) => {
            if (data && data.places && data.places.length > 0) {
                return {
                    city: data.places[0]['place name'],
                    state: data.places[0].state,
                    success: true
                };
            }
            return { success: false };
        }
    }
];

async function fetchPincodeDetails(pincode, cityFieldId, stateFieldId) {
    const alertDiv = document.getElementById('pincode-alert');
    const alertMessage = document.getElementById('alert-message');
    
    if (pincode.length === 6 && /^\d{6}$/.test(pincode)) {
        // Show loading state
        document.getElementById(cityFieldId).value = 'Loading...';
        document.getElementById(stateFieldId).value = 'Loading...';
        
        // Show loading alert
        alertDiv.className = 'alert alert-info';
        alertDiv.classList.remove('d-none');
        alertMessage.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Fetching location details...';
        
        // Try each API in sequence until one works
        for (let i = 0; i < PINCODE_APIS.length; i++) {
            const api = PINCODE_APIS[i];
            
            try {
                console.log(`Trying ${api.name} API for pincode ${pincode}`);
                
                const response = await fetch(api.url(pincode), {
                    headers: {
                        "Accept": "application/json",
                        "User-Agent": "Mozilla/5.0"
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                const data = await response.json();
                const result = api.parser(data);
                
                if (result.success) {
                    document.getElementById(cityFieldId).value = result.city;
                    document.getElementById(stateFieldId).value = result.state;
                    
                    // Add success styling
                    document.getElementById(cityFieldId).style.borderColor = '#28a745';
                    document.getElementById(stateFieldId).style.borderColor = '#28a745';
                    
                    // Show success alert
                    alertDiv.className = 'alert alert-success';
                    alertMessage.innerHTML = `<i class="ti ti-check-circle me-2"></i>Location found: ${result.city}, ${result.state}`;
                    
                    setTimeout(() => {
                        alertDiv.classList.add('d-none');
                    }, 3000);
                    
                    console.log(`Successfully fetched data from ${api.name}`);
                    return; // Success, exit the loop
                }
            } catch (error) {
                console.warn(`${api.name} API failed:`, error);
                // Continue to next API
            }
        }
        
        // If primary APIs failed, try one more reliable backup
        try {
            const fallbackResponse = await Promise.race([
                fetch(`https://api.postalpincode.in/pincode/${pincode}`),
                new Promise((_, reject) => setTimeout(() => reject(new Error('Timeout')), 5000))
            ]);
            
            if (fallbackResponse.ok) {
                const fallbackData = await fallbackResponse.json();
                if (fallbackData && fallbackData[0] && fallbackData[0].Status === 'Success' && fallbackData[0].PostOffice) {
                    const postOffice = fallbackData[0].PostOffice[0];
                    document.getElementById(cityFieldId).value = postOffice.District;
                    document.getElementById(stateFieldId).value = postOffice.State;
                    document.getElementById(cityFieldId).style.borderColor = '#28a745';
                    document.getElementById(stateFieldId).style.borderColor = '#28a745';
                    
                    // Show success alert
                    alertDiv.className = 'alert alert-success';
                    alertMessage.innerHTML = `<i class="ti ti-check-circle me-2"></i>Location found: ${postOffice.District}, ${postOffice.State}`;
                    
                    setTimeout(() => {
                        alertDiv.classList.add('d-none');
                    }, 3000);
                    
                    console.log('Successfully fetched data from fallback API');
                    return;
                }
            }
        } catch (error) {
            console.warn("Fallback API also failed:", error);
        }
        
        // If all APIs failed
        console.error("All pincode APIs failed");
        document.getElementById(cityFieldId).value = 'Invalid Pincode';
        document.getElementById(stateFieldId).value = 'Invalid Pincode';
        
        // Add error styling
        document.getElementById(cityFieldId).style.borderColor = '#dc3545';
        document.getElementById(stateFieldId).style.borderColor = '#dc3545';
        
        // Show error alert
        alertDiv.className = 'alert alert-danger';
        alertMessage.innerHTML = '<i class="ti ti-alert-circle me-2"></i>Invalid pincode or service unavailable. Please check the pincode and try again.';
        
    } else if (pincode.length > 0) {
        document.getElementById(cityFieldId).value = '';
        document.getElementById(stateFieldId).value = '';
        
        // Reset styling
        document.getElementById(cityFieldId).style.borderColor = '#e9ecef';
        document.getElementById(stateFieldId).style.borderColor = '#e9ecef';
        
        // Show warning for invalid format
        alertDiv.className = 'alert alert-warning';
        alertDiv.classList.remove('d-none');
        alertMessage.innerHTML = '<i class="ti ti-alert-triangle me-2"></i>Please enter a valid 6-digit pincode';
        
    } else {
        document.getElementById(cityFieldId).value = '';
        document.getElementById(stateFieldId).value = '';
        
        // Reset styling
        document.getElementById(cityFieldId).style.borderColor = '#e9ecef';
        document.getElementById(stateFieldId).style.borderColor = '#e9ecef';
        
        // Hide alert
        alertDiv.classList.add('d-none');
    }
}

document.getElementById('originPincode').addEventListener('input', function () {
    fetchPincodeDetails(this.value, 'originCity', 'originState');
});

document.getElementById('destinationPincode').addEventListener('input', function () {
    fetchPincodeDetails(this.value, 'destinationCity', 'destinationState');
});

// Form submission with loading state
document.getElementById('rate-calculator-form').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    const spinner = document.getElementById('loading-spinner');
    
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    submitBtn.innerHTML = '<i class="ti ti-loader me-2"></i>Calculating Rates... <span class="spinner-border spinner-border-sm ms-2"></span>';
});

// Smooth scroll to results
window.addEventListener('load', function() {
    const apiResponse = document.getElementById('api-response');
    if (apiResponse) {
        setTimeout(() => {
            apiResponse.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }, 500);
    }
});

// Add click to copy functionality for rates
document.querySelectorAll('.rate-row').forEach(row => {
    row.addEventListener('click', function() {
        const serviceName = this.querySelector('td:nth-child(2)').textContent.trim();
        const totalPrice = this.querySelector('.badge').textContent.trim();
        
        const textToCopy = `Service: ${serviceName}\nTotal Cost: ${totalPrice}`;
        
        navigator.clipboard.writeText(textToCopy).then(() => {
            // Show temporary success message
            const originalBg = this.style.backgroundColor;
            this.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
            this.style.borderLeft = '4px solid #28a745';
            
            setTimeout(() => {
                this.style.backgroundColor = originalBg;
                this.style.borderLeft = 'none';
            }, 1000);
        }).catch(err => {
            console.error('Failed to copy: ', err);
        });
    });
});
</script>




@endsection

