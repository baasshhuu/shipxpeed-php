@extends('layouts.sellerdash')

@section('content')
<div class="pc-container">
    <div class="pc-content">
        <div class="container my-4">
            <!-- Header Section -->
            <div class="page-header mb-4">
                <h4 class="mb-1">
                    <a href="#" class="text-decoration-none text-primary me-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    Select Courier Partner
                </h4>
                <p class="text-muted mb-0">Choose the best shipping option for your order</p>
            </div>

            <!-- Insurance Notice -->
            <div class="alert alert-warning d-flex align-items-center mb-4">
                <i class="fas fa-shield-alt me-2"></i>
                <span><strong>Insurance Notice:</strong> In case of lost or damaged shipments, the insured amount is limited to ₹2000 only.</span>
            </div>

            <form id="courier-form" method="POST" action="{{ route('assign.Courier') }}">
                @csrf
                <input type="hidden" name="order_id" value="{{ $firstOrderId }}">
                <input type="hidden" name="courier_id" id="courier_id">
                <input type="hidden" name="courier_charge" id="courier_charge">
                <input type="hidden" name="freight_charges" id="freight_charges">
                <input type="hidden" name="cod_charges" id="cod_charges">
                <input type="hidden" name="gst_charges" id="gst_charges">
                <input type="hidden" name="serviceability_id" id="serviceability_id">
                <input type="hidden" name="provider_name" id="provider_name">


            {{-- {{dd($logisticProviders)}} --}}
                {{-- Dynamic Courier Options --}}
                <div class="courier-options-container">
                    @foreach ($logisticProviders as $index => $provider)
                        <div class="courier-card {{ $index === 0 ? 'recommended' : '' }}">
                            @if($index === 0)
                                <div class="recommended-badge">
                                    <i class="fas fa-star"></i> Recommended
                                </div>
                            @endif
                            
                            <div class="courier-option-content">
                                <div class="courier-selection">
                                    <input type="radio" name="courier"
                                        id="courier_{{ $index }}"
                                        value="{{ $provider['courierId'] }}"
                                        class="courier-radio"
                                        data-charge="{{ $provider['courierCharge'] }}"
                                        data-freight="{{ $provider['freightCharges'] ?? 0 }}"
                                        data-cod="{{ $provider['codCharge'] ?? 0 }}"
                                        data-serviceability="{{ $provider['serviceabilityId'] ?? 0 }}"
                                        data-gst="{{ $provider['gst_amount'] ?? 0 }}">
                                    <label for="courier_{{ $index }}" class="courier-label"></label>
                                </div>

                                <div class="courier-info">
                                    <div class="courier-logo-container">
                                        <img src="{{ asset($provider['provider_logo'] ?? 'images/default-logo.png') }}" 
                                             alt="{{ $provider['provider_name'] }}" 
                                             class="courier-logo">
                                    </div>

                                    <div class="courier-details">
                                        <h6 class="courier-name mb-1">
                                            {{ !empty($provider['provider_name']) ? $provider['provider_name'] : ($provider['courierName'] ?? 'Unknown') }}
                                        </h6>
                                        <div class="courier-specs">
                                            <span class="spec-item">
                                                <i class="fas fa-weight-hanging"></i>
                                                Min. Weight: {{ ($provider['minWeight'] ?? 500) / 1000 }} KG
                                            </span>
                                            @if(isset($provider['estimatedDays']))
                                                <span class="spec-item">
                                                    <i class="fas fa-clock"></i>
                                                    {{ $provider['estimatedDays'] }} days
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="courier-pricing">
                                    <div class="main-price">
                                        ₹{{ number_format($provider['courierCharge'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="submit-section mt-4">
                    <button type="submit" class="btn btn-primary btn-lg w-100" id="submit-button" disabled>
                        <i class="fas fa-shipping-fast me-2"></i>
                        Assign Selected Courier
                    </button>
                    <p class="text-center text-muted mt-2 mb-0">
                        <small>Please select a courier partner to continue</small>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Page Header Styles */
    .page-header {
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 1rem;
    }

    /* Courier Options Container */
    .courier-options-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* Individual Courier Card */
    .courier-card {
        position: relative;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        background: #ffffff;
        transition: all 0.3s ease;
        overflow: hidden;
        cursor: pointer;
    }

    .courier-card:hover {
        border-color: #007bff;
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
        transform: translateY(-2px);
    }

    .courier-card.selected {
        border-color: #007bff;
        background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
        box-shadow: 0 4px 20px rgba(0, 123, 255, 0.15);
    }

    /* Recommended Badge */
    .recommended-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-bottom-left-radius: 8px;
        z-index: 2;
    }

    .recommended-badge i {
        margin-right: 0.25rem;
    }

    /* Courier Option Content */
    .courier-option-content {
        display: flex;
        align-items: center;
        padding: 1.25rem;
        gap: 1rem;
    }

    /* Custom Radio Button */
    .courier-selection {
        position: relative;
    }

    .courier-radio {
        appearance: none;
        width: 20px;
        height: 20px;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        background: white;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .courier-radio:checked {
        border-color: #007bff;
        background: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }

    .courier-radio:checked::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 8px;
        height: 8px;
        background: white;
        border-radius: 50%;
    }

    /* Courier Logo Container */
    .courier-logo-container {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 80px;
        height: 80px;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 0.5rem;
    }

    .courier-logo {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    /* Courier Info Section */
    .courier-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex: 1;
    }

    .courier-details {
        flex: 1;
    }

    .courier-name {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .courier-specs {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .spec-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.85rem;
        color: #6c757d;
    }

    .spec-item i {
        font-size: 0.75rem;
        color: #007bff;
    }

    /* Pricing Section */
    .courier-pricing {
        text-align: right;
        min-width: 120px;
    }

    .main-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        line-height: 1.2;
    }

    .price-breakdown {
        margin-top: 0.25rem;
        font-size: 0.75rem;
    }

    /* Submit Section */
    .submit-section {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        border: 1px dashed #dee2e6;
    }

    .btn-primary {
        background: linear-gradient(135deg, #007bff, #0056b3);
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover:not(:disabled) {
        background: linear-gradient(135deg, #0056b3, #004085);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    .btn-primary:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    /* Alert Styles */
    .alert-warning {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        border: 1px solid #ffeaa7;
        border-radius: 8px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .courier-option-content {
            flex-direction: column;
            text-align: center;
            gap: 0.75rem;
        }

        .courier-info {
            flex-direction: column;
            text-align: center;
        }

        .courier-specs {
            justify-content: center;
        }

        .courier-pricing {
            text-align: center;
            min-width: auto;
        }

        .main-price {
            font-size: 1.25rem;
        }
    }

    /* Animation for selection */
    @keyframes selectPulse {
        0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.4); }
        70% { box-shadow: 0 0 0 6px rgba(0, 123, 255, 0); }
        100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
    }

    .courier-card.selected {
        animation: selectPulse 0.6s ease-out;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const courierRadios = document.querySelectorAll('input[name="courier"]');
        const submitButton = document.getElementById('submit-button');
        const submitText = submitButton.querySelector('small');

        courierRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                // Remove selected class from all cards
                document.querySelectorAll('.courier-card').forEach(card => {
                    card.classList.remove('selected');
                });

                // Add selected class to current card
                this.closest('.courier-card').classList.add('selected');

                // Update hidden form fields
                document.getElementById('courier_id').value = this.value;
                document.getElementById('courier_charge').value = this.dataset.charge || 0;
                document.getElementById('freight_charges').value = this.dataset.freight || 0;
                document.getElementById('cod_charges').value = this.dataset.cod || 0;
                document.getElementById('gst_charges').value = this.dataset.gst || 0;
                document.getElementById('serviceability_id').value = this.dataset.serviceability || 0;

                // Get provider name from the card
                const providerName = this.closest('.courier-card').querySelector('.courier-name').innerText;
                document.getElementById('provider_name').value = providerName;

                // Enable submit button and update text
                submitButton.disabled = false;
                if (submitText) {
                    submitText.textContent = `${providerName} selected - Ready to assign`;
                }

                // Add visual feedback
                submitButton.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    submitButton.style.transform = 'scale(1)';
                }, 150);
            });

            // Add click handler to the entire card
            const card = radio.closest('.courier-card');
            if (card) {
                card.addEventListener('click', function(e) {
                    if (e.target.type !== 'radio') {
                        radio.click();
                    }
                });
            }
        });

        // Add hover effects
        document.querySelectorAll('.courier-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                if (!this.classList.contains('selected')) {
                    this.style.transform = 'translateY(-2px)';
                }
            });

            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('selected')) {
                    this.style.transform = 'translateY(0)';
                }
            });
        });

        // Form submission with loading state
        document.getElementById('courier-form').addEventListener('submit', function(e) {
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            submitButton.disabled = true;
        });
    });
</script>
@endsection