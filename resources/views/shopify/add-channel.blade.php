
@extends('layouts.sellerdash')

@section('content')
<style>
    .add-channel-page {
        --ac-primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --ac-success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --ac-card-gradient: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
           function addChannel(channelType) {
        // Redirect to specific channel integration pages
        if (channelType === 'shopify') {
            window.location.href = '/seller/channels/seller.shopify.integration';
        } else if (channelType === 'woocommerce') {
            window.location.href = '/seller/channels/seller.woocommerce.integration';
        } else if (channelType === 'magento') {
            window.location.href = '/seller/channels/seller.magento.integration';
        } else if (channelType === 'bigcommerce') {
            window.location.href = '/seller/channels/seller.bigcommerce.integration';
        } else {
            // Fallback alert for other channels
            alert('Connecting to ' + channelType.charAt(0).toUpperCase() + channelType.slice(1) + '...');
        }
    }ient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    }
    
    .add-channel-page .ac-dashboard-header {
        background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 0.8rem 0;
        margin-bottom: 1.5rem;
        border-radius: 7px;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .add-channel-page .ac-dashboard-header::before {
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
    
    .add-channel-page .ac-dashboard-title {
        font-size: 1.8rem;
        font-weight: 700;
        text-shadow: 2px 2px 8px rgba(0,0,0,0.3);
        margin-bottom: 0;
        position: relative;
        z-index: 2;
    }
    
    .add-channel-page .ac-breadcrumb {
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border-radius: 10px;
        padding: 0.4rem 0.8rem;
        display: inline-block;
        position: relative;
        z-index: 2;
        font-size: 0.85rem;
    }
    
    .add-channel-page .ac-breadcrumb a {
        color: rgba(255,255,255,0.9);
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .add-channel-page .ac-breadcrumb a:hover {
        color: white;
        text-shadow: 0 0 10px rgba(255,255,255,0.5);
    }
    
    .add-channel-page .ac-main-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
        margin-bottom: 2rem;
    }
    
    .add-channel-page .ac-main-card:hover {
        box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .add-channel-page .ac-card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1.5rem;
        border-bottom: 2px solid #e9ecef;
    }
    
    .add-channel-page .ac-card-title {
        font-size: 1.4rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .add-channel-page .ac-card-subtitle {
        color: #6c757d;
        font-size: 0.95rem;
        margin: 0;
    }
    
    .add-channel-page .ac-card-body {
        padding: 2rem;
    }
    
    .add-channel-page .ac-channel-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .add-channel-page .ac-channel-card {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 1.2rem;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }
    
    .add-channel-page .ac-channel-card:hover {
        border-color: #667eea;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        transform: translateY(-5px);
    }
    
    .add-channel-page .ac-channel-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
        transition: all 0.5s ease;
    }
    
    .add-channel-page .ac-channel-card:hover::before {
        left: 100%;
    }
    
    .add-channel-page .ac-channel-logo {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem auto;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 2;
    }
    
    .add-channel-page .ac-channel-logo img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    
    .add-channel-page .ac-channel-name {
        font-size: 1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 1rem;
        position: relative;
        z-index: 2;
    }
    
    .add-channel-page .ac-add-btn {
        background: white;
        border: 2px solid #667eea;
        color: #667eea;
        border-radius: 20px;
        padding: 0.5rem 1.2rem;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        position: relative;
        z-index: 2;
        min-width: 100px;
    }
    
    .add-channel-page .ac-channel-card:hover .ac-add-btn {
        background: var(--ac-primary-gradient);
        border-color: transparent;
        color: white;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        transform: scale(1.05);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .add-channel-page .ac-dashboard-title {
            font-size: 1.3rem;
        }
        
        .add-channel-page .ac-breadcrumb {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
        
        .add-channel-page .ac-channel-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .add-channel-page .ac-card-header {
            padding: 1rem;
        }
        
        .add-channel-page .ac-card-body {
            padding: 1.5rem;
        }
        
        .add-channel-page .ac-card-title {
            font-size: 1.2rem;
        }
    }
    
    @media (max-width: 576px) {
        .add-channel-page .ac-dashboard-header {
            padding: 0.6rem 0;
            margin-bottom: 1rem;
        }
        
        .add-channel-page .ac-dashboard-title {
            font-size: 1.2rem;
        }
        
        .add-channel-page .ac-main-card {
            border-radius: 10px;
            margin: 0 0.5rem 1rem 0.5rem;
        }
        
        .add-channel-page .ac-channel-card {
            padding: 1.5rem;
        }
        
        .add-channel-page .ac-channel-logo {
            width: 60px;
            height: 60px;
            margin-bottom: 1rem;
        }
    }
    
    /* Animation Classes */
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

<div class="pc-container add-channel-page">
    <div class="pc-content" style="margin-left:12px;background:#646dff26;">
        <!-- Dashboard Header -->
        <div class="ac-dashboard-header fade-in">
            <div class="container">
                <div class="text-center">
                    <h1 class="ac-dashboard-title">Add Channel</h1>
                </div>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="container">
            <div class="ac-main-card slide-up">
                <div class="ac-card-header">
                    <h2 class="ac-card-title">Shopping Carts</h2>
                    <p class="ac-card-subtitle">Connect the shopping cart on which your online store/website is built</p>
                </div>
                <div class="ac-card-body">
                    <div class="ac-channel-grid">
                        <!-- Shopify Card -->
                        <div class="ac-channel-card" onclick="addChannel('shopify')">
                            <div class="ac-channel-logo">
                                <svg width="80" height="80" viewBox="0 0 256 292" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M223.774 57.34c-.201-1.46-1.48-2.268-2.537-2.357-1.055-.088-23.383-1.743-23.383-1.743s-15.507-15.395-17.209-17.099c-1.703-1.703-5.029-1.185-6.32-.805-.19.056-3.388 1.043-8.678 2.68-.5-5.033-2.478-10.924-5.474-16.509C153.85 10.337 144.778 4.919 135.016 5.009c-.524.018-1.048.044-1.567.093-.024-.04-.044-.08-.067-.122-2.623-4.755-6.143-7.606-11.104-7.606C114.264-2.726 108.682 1.614 103.56 8.109c-4.364 5.539-8.188 12.529-9.26 19.54-14.35 4.444-24.4 7.587-24.4 7.587-7.152 2.179-7.448 2.478-8.387 9.212-.694 4.95-19.402 149.617-19.402 149.617l164.717 30.739L223.774 57.34z" fill="#95BF47"/>
                                    <path d="M201.238 54.983c-1.055-.088-23.383-1.743-23.383-1.743s-15.507-15.395-17.209-17.099c-.852-.852-1.905-1.22-3.08-1.22-.442 0-.917.044-1.401.133-.19.056-3.388 1.043-8.678 2.68-.5-5.033-2.478-10.924-5.474-16.509C135.695 10.058 126.623 4.64 116.861 4.73c-.524.018-1.048.044-1.567.093-.024-.04-.044-.08-.067-.122-2.623-4.755-6.143-7.606-11.104-7.606-8.914 0-14.496 4.34-19.618 10.835-4.364 5.539-8.188 12.529-9.26 19.54-14.35 4.444-24.4 7.587-24.4 7.587-7.152 2.179-7.448 2.478-8.387 9.212-.694 4.95-19.402 149.617-19.402 149.617l164.717 30.739 17.945-177.587c-.201-1.46-1.48-2.268-2.537-2.357z" fill="#5E8E3E"/>
                                </svg>
                            </div>
                            <h3 class="ac-channel-name">Shopify</h3>
                            <button class="btn ac-add-btn">Add</button>
                        </div>

                        <!-- Add more channels as needed -->
                        <div class="ac-channel-card" onclick="addChannel('woocommerce')">
                            <div class="ac-channel-logo">
                                <svg width="80" height="80" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M128 0C57.307 0 0 57.307 0 128s57.307 128 128 128 128-57.307 128-128S198.693 0 128 0z" fill="#7854A8"/>
                                    <path d="M60.8 201.6c-4.8-9.6-8-20.8-8-32 0-36.8 29.6-66.4 66.4-66.4s66.4 29.6 66.4 66.4c0 11.2-3.2 22.4-8 32l-58.4-32 58.4-32c4.8 9.6 8 20.8 8 32 0 36.8-29.6 66.4-66.4 66.4s-66.4-29.6-66.4-66.4c0-11.2 3.2-22.4 8-32z" fill="#FFFFFF"/>
                                </svg>
                            </div>
                            <h3 class="ac-channel-name">WooCommerce</h3>
                            <button class="btn ac-add-btn">Add</button>
                        </div>

                        <div class="ac-channel-card" onclick="addChannel('magento')">
                            <div class="ac-channel-logo">
                                <svg width="80" height="80" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M128 0l-64 37.3v149.4L128 224l64-37.3V37.3L128 0z" fill="#EE672F"/>
                                    <path d="M128 32l-48 27.9v124.2L128 212l48-27.9V59.9L128 32z" fill="#FFFFFF"/>
                                </svg>
                            </div>
                            <h3 class="ac-channel-name">Magento</h3>
                            <button class="btn ac-add-btn">Add</button>
                        </div>

                        <div class="ac-channel-card" onclick="addChannel('bigcommerce')">
                            <div class="ac-channel-logo">
                                <svg width="80" height="80" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="128" cy="128" r="128" fill="#1B5E20"/>
                                    <path d="M96 96h64v64H96z" fill="#FFFFFF"/>
                                </svg>
                            </div>
                            <h3 class="ac-channel-name">BigCommerce</h3>
                            <button class="btn ac-add-btn">Add</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function addChannel(channelType) {
        // Redirect to specific channel integration pages
        if (channelType === 'shopify') {
            window.location.href = 'seller.shopify.integration';
        } else if (channelType === 'woocommerce') {
            window.location.href = 'seller.woocommerce.integration';
        } else if (channelType === 'magento') {
            window.location.href = 'seller.magento.integration';
        } else if (channelType === 'bigcommerce') {
            window.location.href = 'seller.bigcommerce.integration';
        } else {
            // Fallback alert for other channels
            alert('Connecting to ' + channelType.charAt(0).toUpperCase() + channelType.slice(1) + '...');
        }
    }
    
    // Add animation delays for cards
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.ac-channel-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
            card.classList.add('slide-up');
        });
    });
</script>
@endsection
