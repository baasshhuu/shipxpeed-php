@extends('layouts.sellerdash')
@section('content')
<div class="pc-container weight-discrepancy-page" style="background:#646dff26;">
    <div class="pc-content" style="margin-left:12px;">
        <div class="api-marquee-techs-container" style="width:100%;display:flex;flex-direction:column;align-items:center;margin-top:10px;margin-bottom:23px;">
            <div class="api-marquee-techs-track" style="overflow:hidden;width:100%;max-width:1650px;position:relative;height:38px;">
                <!-- Marquee Track: doubled content - seamless infinite, gapless -->
                <div class="api-marquee-content" style="display:flex;align-items:center;height:38px;">
                    <span class="api-marquee-rect api-rect-laravel">Laravel</span>
                    <span class="api-marquee-rect api-rect-php">PHP</span>
                    <span class="api-marquee-rect api-rect-genai">Gen AI</span>
                    <span class="api-marquee-rect api-rect-python">Python</span>
                    <span class="api-marquee-rect api-rect-aws">AWS</span>
                    <span class="api-marquee-rect api-rect-nodejs">NodeJS</span>
                    <span class="api-marquee-rect api-rect-php">Shopify</span>
                    <span class="api-marquee-rect api-rect-genai">WooCommerce</span>
                    <!-- Duplicate for infinite, seamless scrolling -->
                    <span class="api-marquee-rect api-rect-laravel">Laravel</span>
                    <span class="api-marquee-rect api-rect-php">PHP</span>
                    <span class="api-marquee-rect api-rect-genai">Gen AI</span>
                    <span class="api-marquee-rect api-rect-python">Python</span>
                    <span class="api-marquee-rect api-rect-aws">AWS</span>
                    <span class="api-marquee-rect api-rect-nodejs">NodeJS</span>
                    <span class="api-marquee-rect api-rect-php">Shopify</span>
                    <span class="api-marquee-rect api-rect-genai">WooCommerce</span>

                    <!-- third time for infinite, seamless scrolling -->
                    <span class="api-marquee-rect api-rect-laravel">Laravel</span>
                    <span class="api-marquee-rect api-rect-php">PHP</span>
                    <span class="api-marquee-rect api-rect-genai">Gen AI</span>
                    <span class="api-marquee-rect api-rect-python">Python</span>
                    <span class="api-marquee-rect api-rect-aws">AWS</span>
                    <span class="api-marquee-rect api-rect-nodejs">NodeJS</span>
                    <span class="api-marquee-rect api-rect-php">Shopify</span>
                    <span class="api-marquee-rect api-rect-genai">WooCommerce</span>
                    
                </div>
            </div>
        </div>
        <style>
            .api-marquee-techs-container {
                position: relative;
                z-index: 1;
                width: 100%;
            }
            .api-marquee-techs-track {
                background: #e6ecfa;
                border-radius: 12px;
                margin: 0 auto;
                min-height: 38px;
                width: 100vw;
                max-width: 1650px;
                border: none;
            }
            .api-marquee-content {
                display: flex;
                align-items: center;
                gap: 14px;
                width: max-content;
                animation: apiMarqueeScrollRect 36s linear infinite;
            }
            .api-marquee-rect {
                display: inline-flex;
                justify-content: center;
                align-items: center;
                min-width: 80px;
                height: 28px;
                border-radius: 9px;
                font-size: 0.93rem;
                font-weight: 600;
                margin: 0 2px;
                letter-spacing: 0.1px;
                background: #f3f8fe;
                padding: 0 15px;
                color: #fff;
                border: 1.2px solid transparent;
                box-shadow: 0 1px 7px rgba(0, 32, 64, 0.07);
                transition: box-shadow 0.14s, transform 0.2s;
                user-select: none;
            }
            .api-marquee-rect:hover {
                box-shadow: 0 6px 24px rgba(40,60,120,0.15);
                transform: scale(1.054);
            }
            .api-rect-laravel    { background: #ffeaea; color: #ee301c; border-color: #fdd3cf;}
            .api-rect-php        { background: #dee8fa; color: #335c81; border-color: #acc0e5;}
            .api-rect-genai      { background: #fff9ec; color: #ff9000; border-color: #f7ecd6;}
            .api-rect-python     { background: #f3f7eb; color: #2d7c4b; border-color: #d3e5c1;}
            .api-rect-aws        { background: #fff8ef; color: #ee9800; border-color: #fbe1bd;}
            .api-rect-nodejs     { background: #e9fcea; color: #278325; border-color: #bee0be;}
            @keyframes apiMarqueeScrollRect {
                0% { transform: translateX(0);}
                100% { transform: translateX(-50%);}
            }
            @media (max-width: 1200px) {
                .api-marquee-techs-track {
                    max-width: 99vw;
                }
                .api-marquee-content {
                    gap: 10px;
                }
            }
            @media (max-width: 800px) {
                .api-marquee-techs-track {
                    max-width: 99vw;
                    min-height: 26px;
                }
                .api-marquee-content {
                    gap: 5px;
                }
                .api-marquee-rect {
                    min-width:52px;
                    height:22px;
                    font-size:0.82rem;
                    margin:0 1px;
                    padding:0 7px;
                    border-radius: 7px;
                }
            }
        </style>
        <!-- Seamless, gapless infinite left-to-right marquee for tech stacks (Laravel, PHP, Gen AI, Python, AWS, NodeJS) with doubled content, slow speed, rectangular colored boxes and modern UI -->
        {{-- Enhanced Main API Documentation - Techy, Professional, Standardized Look (Contained Width) --}}
        <style>
            .api-doc-card {
                background: #181f2b;
                border-radius: 16px;
                box-shadow: 0 4px 32px #1017251f;
                border-top: 2.5px solid #2672ff;
                border-bottom: 2px solid #283259;
                color: #e9eef8;
                width: 100%;
                /* max-width: 980px;
                margin: 2rem auto 1.5rem auto; */
                font-family: "Inter", "Menlo", "Fira Mono", "Consolas", monospace;
                padding-left: 0;
                padding-right: 0;
            }
            .api-doc-card > .api-doc-container {
                /* max-width: 940px; */
                margin: 0 auto;
                padding: 2.2rem 2rem;
            }
            .api-doc-header {
                border-bottom: 1px solid #242c41;
                padding-bottom: 0.8rem;
                margin-bottom: 1.5rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }
            .api-doc-title {
                font-weight: 700;
                color: #61dafb;
                font-size: 2.1rem;
                letter-spacing: -.5px;
                margin-bottom: 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .api-doc-download {
                background: #222943;
                border: 1.7px solid #284c84;
                color: #61dafb;
                font-weight: 500;
                border-radius: 7px;
                font-size: 1rem;
                padding: 10px 38px 10px 22px;
                position: relative;
                transition: all .16s;
                cursor: pointer;
                box-shadow: 0 2px 14px #0c215044;
            }
            .api-doc-download i {
                color: #78ffe6;
            }
            .api-doc-download:hover, .api-doc-download:focus-visible {
                background: #243661;
                border-color: #61dafb;
                color: #fff;
            }
            .api-section-title {
                color: #c694f6;
                font-weight: 600;
                font-size: 1.12rem;
                margin: 1.2rem 0 .67rem;
                letter-spacing: 0.01em;
                display:flex;align-items:center;gap:8px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            .api-doc-label {
                font-size: .99em;
                font-weight: 600;
                color: #40cfc1;
                margin-bottom: 2px;
                display:inline-block;
            }
            .api-doc-url {
                background: #212a3d;
                border-radius: 6px;
                color: #fed776;
                padding: 1.5px 10px 1px 10px;
                font-family: "JetBrains Mono", "Fira Mono", monospace;
                font-size: 1.01em;
                font-weight: 500;
                letter-spacing: 0.02em;
            }
            .api-doc-instructions {
                color: #d4e0f7;
                font-size: 1.01rem;
            }
            .api-doc-instructions ul, .api-doc-instructions ol {
                margin-top: 7px;
                margin-bottom: 10px;
                padding-left: 1.26em;
            }
            .api-doc-instructions li {
                margin-bottom: 4px;
                line-height: 1.52;
            }
            .api-doc-block {
                background: #222943 !important;
                border: 1.2px solid #334168 !important;
                border-radius: 8px !important;
                padding: 13px 20px !important;
                color: #c5edfd !important;
                font-size: 1em !important;
                font-family: 'JetBrains Mono', 'Fira Mono', Consolas, 'Liberation Mono', monospace !important;
                margin-top: 9px;
                margin-bottom: 0;
                white-space: pre-wrap;
                word-break: break-all;
                line-height: 1.62 !important;
                overflow-x: auto;
            }
            .api-response-block {
                background: #182d24!important;
                border: 1.5px solid #284c36 !important;
                color: #b3ebc2 !important;
            }
            .api-highlight {
                color: #48fff3;
                font-weight: 700;
            }
            .api-doc-badge {
                background: #303a55;
                color: #fed776;
                border: 1px solid #272f44;
                font-size: 0.97em;
                padding: 2px 12px;
                border-radius: 6px;
                font-family: "JetBrains Mono", "Fira Mono", monospace;
            }
            .api-doc-block code, .api-doc-block .code, .api-doc-url code {
                color: #f0e397;
            }
            .api-doc-block .fw-bold {
                font-weight: 600!important;
                color: #f69bad;
            }
            @media (max-width: 1200px) {
                .api-doc-card, .api-doc-card > .api-doc-container {max-width:96vw;}
            }
            @media (max-width: 990px) {
                .api-doc-card, .api-doc-card > .api-doc-container {max-width:100vw;padding:0;}
                .api-doc-card > .api-doc-container {padding: 1.4rem 2vw;}
                .api-doc-card {font-size:0.98em;}
            }
            @media (max-width: 600px) {
                .api-doc-header {flex-direction:column;align-items:flex-start;}
                .api-doc-title {font-size:1rem;}
                .api-section-title {font-size:0.97rem;}
                .api-doc-block {font-size:0.86em;}
                .api-doc-card > .api-doc-container {padding:1rem 3vw;}
            }
        </style>

        {{-- Main API Documentation --}}
        <div class="api-doc-card mb-4">
            <div class="api-doc-container">
                <div class="api-doc-header">
                    <h2 class="api-doc-title mb-0">
                        <i class="fa-solid fa-cubes me-2" style="color:#c694f6"></i>SHIPXPEED API Documentation
                    </h2>
                    <a href="/shipxpeed_api_documentation_with_clean_instructions.pdf"
                        class="api-doc-download btn-download"
                        download
                        title="Download API Documentation PDF"
                    >
                        <i class="fa-solid fa-download me-2"></i>Download
                    </a>
                </div>
                <div>
                    <div class="api-section-title"><i class="fa-solid fa-truck-arrow-right"></i> 1. Create Shipment</div>
                    <span class="api-doc-label">URL:</span>
                    <span class="api-doc-url" style="word-break:break-all;overflow-wrap:anywhere;display:inline-block;max-width:100%;">https://shipxpeed.com/api/shipments/create</span>
                    <div class="api-doc-instructions mt-3">
                        <span class="api-doc-label">Instructions:</span>
                        <ul>
                            <li>Use this endpoint to <span class="api-highlight">create a new shipment order</span>.</li>
                            <li>Ensure all required fields are included and properly formatted.</li>
                            <li>Nested fields like <code>consignee</code>, <code>pickup</code>, and <code>rto</code> must contain accurate address details.</li>
                            <li>Set <code>unique_order_number</code> to <span class="api-highlight">"yes"</span> to prevent duplication.</li>
                            <li><code>seller_id</code> must be included as it identifies the merchant.</li>
                        </ul>
                    </div>
                    <div class="mt-3">
                        <span class="api-doc-label">Request Format (POST):</span>
                        <pre class="api-doc-block">
{
    "consignee": {
        "name": "John Doe",
        "phone": "9999999999",
        "address": "Consignee Address Street 1",
        "address_2": "Address Line 2",
        "pincode": "400001",
        "city": "Mumbai",
        "state": "Maharashtra"
    },
    "order_number": "#5588",
    "unique_order_number": "yes",
    "payment_type": "cod",
    "order_items": [
        {
            "name": "Sample T-shirt",
            "sku": "T123",
            "qty": 1,
            "price": "100"
        }
    ],
    "collectable_amount": "100",
    "pickup": {
        "warehouse_name": "My Warehouse",
        "name": "Sender Name",
        "address": "123, Pickup Address",
        "address_2": "Sublocality or Floor",
        "pincode": "400014",
        "city": "Mumbai",
        "state": "Maharashtra",
        "phone": "8888888888"
    },
    "package_weight": "100",
    "package_length": "10",
    "package_breadth": "10",
    "package_height": "10",
    "is_rto_different": "no",
    "rto": {
        "warehouse_name": "My Warehouse",
        "name": "Sender Name",
        "address": "123, Pickup Address",
        "city": "Mumbai",
        "state": "Maharashtra",
        "pincode": "400014",
        "phone": "8888888888"
    },
    "request_auto_pickup": "yes",
    "courier_id": null,
    "seller_id": "[your_seller_id]"
}
                        </pre>
                    </div>
                    <div class="mt-3">
                        <span class="api-doc-label">Response (success):</span>
                        <pre class="api-doc-block api-response-block">
{
    "success": true,
    "message": "Shipment created successfully",
    "order_id": 465
}
                        </pre>
                    </div>
                </div>
            </div>
        </div>

        {{-- Enhanced Track Order Section --}}
        <div class="api-doc-card mb-4">
            <div class="api-doc-container">
                <div>
                    <div class="api-section-title"><i class="fa-solid fa-location-dot"></i> 2. Track Order</div>
                    <span class="api-doc-label">URL:</span>
                    <span class="api-doc-url">https://shipxpeed.com/api/orders/track</span>
                    <div class="api-doc-instructions mt-2">
                        <span class="api-doc-label">Instructions:</span>
                        <ul>
                            <li>This endpoint allows tracking of a shipment using the <span class="api-highlight">AWB number</span>.</li>
                            <li>The response structure may vary depending on courier and shipment status.</li>
                            <li><b>Always ensure the AWB is correct and exists before calling.</b></li>
                        </ul>
                    </div>
                    <div class="mt-3">
                        <span class="api-doc-label">Request Format (GET):</span>
                        <pre class="api-doc-block">
awb: "38528410001923"
                        </pre>
                    </div>
                    <div class="mt-3">
                        <span class="api-doc-label">Multiple Possible Responses:</span>
                        <ol class="mb-1 api-doc-instructions">
                            <li>
                                <span class="fw-bold">Basic Shipment Info</span>
                                <div class="ms-1" style="color:#b3e5fc">
                                    Includes fields like: <code>id</code>, <code>order_id</code>, <code>order_number</code>, <code>created</code>, <code>awb_number</code>, <code>courier_id</code>, <code>warehouse_id</code>, <code>status</code>, etc.
                                </div>
                            </li>
                            <li class="mt-2">
                                <span class="fw-bold">Pickup Request Info</span>
                                <div class="ms-1" style="color:#ffc78c">
                                    Includes: <code>client_order_number</code>, <code>request_type</code>, <code>price</code>, <code>address</code>, <code>skus</code>, <code>seller</code>, <code>status updates</code>, <code>pickup_request_state_histories</code>, etc.
                                </div>
                            </li>
                            <li class="mt-2">
                                <span class="fw-bold">Detailed Shipment Info</span>
                                <div class="ms-1" style="color:#bff6e2">
                                    Includes: <code>AWB</code>, <code>CODAmount</code>, <code>Consignee</code>, <code>DeliveryDate</code>, <code>Origin</code>, <code>Destination</code>, <code>PickupLocation</code>, <code>PromisedDeliveryDate</code>, <code>Quantity</code>, <code>Scans</code>, <code>SenderName</code>, and Status details (<code>StatusCode</code>, <code>StatusType</code>, <code>DateTime</code>, <code>Location</code>, etc.)
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>



        <div class="tech-support-section card mt-4 mb-4 shadow-sm" style="width: 100%; border-radius: 12px; background: #f3f8fe;">
            <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between" style="gap: 18px;">
                <div>
                    <h4 class="fw-bold mb-2" style="color: #5056cc;">
                        <i class="ti ti-headset me-2" style="font-size: 1.4rem;"></i>Technical Support
                    </h4>
                    <div class="mb-1" style="font-size: 1.07rem;">
                        Need help with API integration or any technical issue? Our team is here for you:
                    </div>
                </div>
                <div class="text-md-end" style="min-width: 250px;">
                    <div class="mb-1">
                        <span class="fw-semibold" style="color: #3f4cc9;">Email:</span>
                        <a href="mailto:tech@shipxpeed.com" class="text-decoration-underline ms-1" style="color: #313494;">tech@shipxpeed.com</a>
                    </div>
                    <div>
                        <span class="fw-semibold" style="color: #3f4cc9;">Phone:</span>
                        <a href="tel:7357169546" class="text-decoration-underline ms-1" style="color: #313494;">7357169546</a>,
                        <a href="tel:7017417377" class="text-decoration-underline ms-1" style="color: #313494;">7017417377</a>
                    </div>
                </div>
            </div>
        </div>
        <style>
            @media (max-width: 768px) {
                .tech-support-section .card-body {
                    flex-direction: column !important;
                    align-items: flex-start !important;
                    text-align: left !important;
                    gap: 10px !important;
                }
                .tech-support-section .text-md-end {
                    text-align: left !important;
                }
            }
        </style>
    </div>
</div>
@endsection