@extends('layouts.sellerdash')

@section('content')
<style>
    .shopify-integration-page {
        --si-primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --si-shopify-green: #95bf47;
        --si-card-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .shopify-integration-page .si-header {
        background: var(--si-primary-gradient);
        color: white;
        padding: 0.8rem 0;
        margin-bottom: 1.5rem;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .shopify-integration-page .si-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
        animation: shimmer 3s infinite;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    
    .shopify-integration-page .si-title {
        font-size: 1.8rem;
        font-weight: 700;
        text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
        margin: 0;
        position: relative;
        z-index: 2;
    }
    
    .shopify-integration-page .si-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 1rem;
    }
    
    .shopify-integration-page .si-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: var(--si-card-shadow);
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .shopify-integration-page .si-instructions .si-header-section {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f3f4;
    }
    
    .shopify-integration-page .si-logo {
        width: 40px;
        height: 40px;
        background: var(--si-shopify-green);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        color: white;
        font-weight: bold;
        font-size: 1.2rem;
    }
    
    .shopify-integration-page .si-card-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }
    
    .shopify-integration-page .si-subtitle {
        color: var(--si-shopify-green);
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .shopify-integration-page .si-step {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 10px;
        border-left: 4px solid var(--si-shopify-green);
    }
    
    .shopify-integration-page .si-step-title {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .shopify-integration-page .si-step-text {
        color: #6c757d;
        font-size: 0.9rem;
        line-height: 1.5;
        margin: 0;
    }
    
    .shopify-integration-page .si-note {
        background: #e3f2fd;
        padding: 1rem;
        border-radius: 10px;
        border-left: 4px solid #2196f3;
        margin-top: 1.5rem;
    }
    
    .shopify-integration-page .si-note p {
        margin: 0;
        color: #1976d2;
        font-size: 0.9rem;
    }
    
    .shopify-integration-page .si-form {
        height: fit-content;
    }
    
    .shopify-integration-page .si-form-header {
        text-align: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f3f4;
    }
    
    .shopify-integration-page .si-form-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }
    
    .shopify-integration-page .si-required-text {
        color: #e74c3c;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .shopify-integration-page .si-form-group {
        margin-bottom: 1.5rem;
    }
    
    .shopify-integration-page .si-label {
        display: block;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }
    
    .shopify-integration-page .si-required {
        color: #e74c3c;
        margin-left: 2px;
    }
    
    .shopify-integration-page .si-input {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background: white;
        box-sizing: border-box;
    }
    
    .shopify-integration-page .si-input:focus {
        border-color: var(--si-shopify-green);
        box-shadow: 0 0 0 0.2rem rgba(149, 191, 71, 0.25);
        outline: none;
    }
    
    .shopify-integration-page .si-input::placeholder {
        color: #adb5bd;
        font-style: italic;
    }
    
    .shopify-integration-page .si-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }
    
    .shopify-integration-page .si-btn {
        flex: 1;
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .shopify-integration-page .si-btn-reset {
        background: #6c757d;
        color: white;
    }
    
    .shopify-integration-page .si-btn-reset:hover {
        background: #5a6268;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
    }
    
    .shopify-integration-page .si-btn-connect {
        background: #1e3a8a;
        color: white;
    }
    
    .shopify-integration-page .si-btn-connect:hover {
        background: #1e40af;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(30, 58, 138, 0.4);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .shopify-integration-page .si-container {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .shopify-integration-page .si-card {
            padding: 1.5rem;
        }
        
        .shopify-integration-page .si-title {
            font-size: 1.4rem;
        }
        
        .shopify-integration-page .si-buttons {
            flex-direction: column;
        }
    }
    
    @media (max-width: 576px) {
        .shopify-integration-page .si-card {
            padding: 1rem;
            margin: 0 0.5rem;
        }
        
        .shopify-integration-page .si-header {
            padding: 0.6rem 0;
        }
        
        .shopify-integration-page .si-title {
            font-size: 1.2rem;
        }
    }
    
    /* Animations */
    .fade-in {
        animation: fadeIn 0.8s ease-in-out;
    }
    
    .slide-up {
        animation: slideUp 0.6s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
</style>

<div class="pc-container shopify-integration-page">
    <div class="pc-content">
        <!-- Header -->
        <div class="si-header fade-in">
            <div class="container">
                <div class="text-center">
                    <h1 class="si-title">Shopify</h1>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container">
            <div class="si-container slide-up">
                <!-- Instructions Card -->
                <div class="si-card si-instructions">
                    <div class="si-header-section">
                        <div class="si-logo">S</div>
                        <h2 class="si-card-title">Shopify</h2>
                    </div>
                    
                    <h3 class="si-subtitle">Use the Following Instructions to Integrate Shopify</h3>
                    
                    <div class="si-step">
                        <div class="si-step-title">Step 1: Go to your Shopify account and navigate to "Settings".</div>
                        <p class="si-step-text">Click on "Domains", then copy the store URL and paste it in the Store URL section.</p>
                    </div>
                    
                    <div class="si-step">
                        <div class="si-step-title">Step 2: In your Shopify account, navigate to "Apps and Sales channels" in the settings screen.</div>
                        <p class="si-step-text">Click on "Develop apps", then copy the Admin API Access token and API key and paste it here.</p>
                    </div>
                    
                    <div class="si-step">
                        <div class="si-step-title">Step 3: Enter the Host Name required to connect your Shopify account with ShipXpeed.</div>
                    </div>
                    
                    <div class="si-step">
                        <div class="si-step-title">Step 4: Navigate to the Orders section in your ShipXpeed account.</div>
                        <p class="si-step-text">Click on "Sync Orders" and all your orders will be fetched.</p>
                    </div>
                    
                    <div class="si-note">
                        <p><strong>If you're having trouble with Shopify integration, troubleshoot it by following the steps mentioned</strong></p>
                    </div>
                </div>
                <!-- Form Card -->
                <div class="si-card si-form">
                    <div class="si-form-header">
                        <img src="https://logos-world.net/wp-content/uploads/2020/11/Shopify-Logo.png" alt="Shopify" style="width: 120px; height: auto; margin-bottom: 1rem;">
                        <h3 class="si-form-title">One Click Integration</h3>
                        <p class="si-required-text">*All Fields are Required</p>
                    </div>
                    
                    <form id="shopifyForm" method="POST" action="{{ route('shopify.connect') ?? '#' }}">
                        @csrf
                        
                        <div class="si-form-group">
                            <label for="store_url" class="si-label">Store Url <span class="si-required">*</span></label>
                            <input type="url" id="store_url" name="store_url" class="si-input" placeholder="Enter your store url" required>
                        </div>
                        
                        <div class="si-form-group">
                            <label for="api_key" class="si-label">Api Key <span class="si-required">*</span></label>
                            <input type="text" id="api_key" name="api_key" class="si-input" placeholder="Enter your api key" required>
                        </div>
                        
                        <div class="si-form-group">
                            <label for="admin_api_token" class="si-label">Admin Api Access Token <span class="si-required">*</span></label>
                            <input type="text" id="admin_api_token" name="admin_api_token" class="si-input" placeholder="Enter your admin api access token" required>
                        </div>
                        
                        <div class="si-form-group">
                            <label for="host_name" class="si-label">Host Name <span class="si-required">*</span></label>
                            <input type="text" id="host_name" name="host_name" class="si-input" placeholder="Enter your host name" required>
                        </div>
                        
                        <div class="si-form-group">
                            <label for="sync_start_date" class="si-label">Order Sync Start Date<span class="si-required">*</span></label>
                            <input type="date" id="sync_start_date" name="sync_start_date" class="si-input" required>
                        </div>
                        
                        <div class="si-buttons">
                            <button type="button" class="si-btn si-btn-reset" onclick="resetForm()">RESET</button>
                            <button type="submit" class="si-btn si-btn-connect">CONNECT TO SHOPIFY</button>
                        </div>
                    </form>

                    <!-- Sync Orders Section -->
                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #f1f3f4;">
                        <h3 class="si-form-title">Sync Orders</h3>
                        <p style="color: #6c757d; margin-bottom: 1rem; font-size: 0.9rem;">
                            After connecting your store, use this button to sync orders from Shopify to your dashboard.
                        </p>
                        <form action="{{ route('shopify.sync.orders') }}" method="POST">
                            @csrf
                            <button type="submit" class="si-btn si-btn-connect" style="width: 100%;">
                                SYNC ORDERS FROM SHOPIFY
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function resetForm() {
        document.getElementById('shopifyForm').reset();
    }
    
    // Animation setup
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.si-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.2}s`;
            card.classList.add('slide-up');
        });
    });
</script>
@endsection