@extends('layouts.sellerdash')

@section('content')

<!-- Modern UI Dashboard CSS -->
<style>
    /* Base Styles and Font */
    :root {
        --primary-color: #4f46e5;
        --primary-light: #a5b4fc;
        --primary-dark: #4338ca;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --info-color: #3b82f6;
        --dark-color: #1f2937;
        --light-color: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-400: #9ca3af;
        --gray-500: #6b7280;
        --box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        --box-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --border-radius-sm: 0.375rem;
        --border-radius: 0.75rem;
        --border-radius-lg: 1.5rem;
    }
    
    body {
        background-color: var(--light-color);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: var(--dark-color);
    }
    
    .pc-container, .pc-content, .pc-header, .pc-sidebar {
        background-color: var(--light-color) !important;
        color: var(--dark-color) !important;
        transition: all 0.3s ease !important;

    }
    
    /* Modern Dashboard Container */
    .modern-dashboard {
        padding: 1.5rem;
        margin: 13px 0px 13px 13px;
        border-radius: 23px;
        background-color: #646dff26;
        min-height: 100vh;
    }
    
    /* Card Styles */
    .modern-card {
        background: #ffffff;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        border: none;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .modern-card:hover {
        box-shadow: var(--box-shadow-hover);
        transform: translateY(-5px);
    }
    
    /* Stat Cards */
    .stat-card {
        padding: 0.5rem;
        border-radius: var(--border-radius);
        background: #ffffff;
        box-shadow: var(--box-shadow);
        border: none;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--box-shadow-hover);
    }
    
    /* Icon Styles */
    .stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 47px;
        height: 47px;
        border-radius: 50%;
        margin-right: 1rem;
        background-color: var(--primary-light);
        color: var(--primary-color);
        font-size: 1.3rem;
    }
    
    /* Typography */
    .stat-title {
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--gray-500);
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    
    .stat-value {
        font-size: 1rem;
        font-weight: 700;
        color: var(--dark-color);
      
    }
    
    .trend-badge {
        font-size: 0.65rem;
        /* font-weight: 600; */
        padding: 0.20rem 0.3rem;
        border-radius: var(--border-radius-sm);
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    /* Status Cards */
    .status-card {
        padding: 1rem;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        background: #ffffff;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        height: 100%;
    }
    
    .status-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--box-shadow-hover);
    }
    
    .status-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--border-radius);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    /* Progress Bars */
    .modern-progress {
        height: 8px;
        background-color: var(--gray-100);
        border-radius: 4px;
        overflow: hidden;
        margin-top: 0.75rem;
    }
    
    .modern-progress-bar {
        height: 100%;
        border-radius: 4px;
    }
    
    /* Chart Cards */
    .chart-card {
        background: #ffffff;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .chart-card:hover {
        box-shadow: var(--box-shadow-hover);
    }
    
    /* Tab Navigation */
    .modern-tabs {
        border-bottom: 1px solid var(--gray-200);
        margin-bottom: 1.5rem;
    }
    
    .modern-tab {
        padding: 0.75rem 1.25rem;
        border-radius: var(--border-radius-sm) var(--border-radius-sm) 0 0;
        font-weight: 500;
        color: var(--gray-500);
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
    }
    
    .modern-tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
    
    .modern-tab:hover:not(.active) {
        color: var(--dark-color);
        background-color: var(--gray-100);
    }
    
    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease forwards;
    }
    
    .fade-in-delay-1 { animation-delay: 0.1s; }
    .fade-in-delay-2 { animation-delay: 0.2s; }
    .fade-in-delay-3 { animation-delay: 0.3s; }
    .fade-in-delay-4 { animation-delay: 0.4s; }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    /* Welcome Section */
    .welcome-section {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: white;
        padding: 2rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .welcome-section::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
        transform: rotate(30deg);
    }
    
    .welcome-title {
        font-size: 1.7rem;
        font-weight: 700;
       
        text-transform: uppercase;
        /* margin-bottom: 0.5rem; */
        position: relative;
    }
    
    .welcome-subtitle {
        font-size: 0.8rem;
        opacity: 0.9;
        margin-bottom: 1rem;
        position: relative;
    }
    
    .welcome-action {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        border-radius: var(--border-radius);
        padding: 1rem;
        margin-top: 1rem;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .welcome-action:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-3px);
    }
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Icon Circle Animations */
    .icon-circle {
        transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    
    .dashboard-stat-card:hover .icon-circle {
        transform: scale(1.1) rotate(5deg);
    }
    
    /* Status Icon Pulse Effect */
    .status-icon {
        position: relative;
    }
    
    .status-icon::after {
        content: '';
        position: absolute;
        inset: -4px;
        border-radius: inherit;
        padding: 4px;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        mask-composite: exclude;
        animation: borderRotate 3s linear infinite;
        opacity: 0;
    }
    
    .hover-lift:hover .status-icon::after {
        opacity: 1;
    }
    
    @keyframes borderRotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Enhanced Progress Bars */
    .progress {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .progress-bar {
        border-radius: 10px;
        position: relative;
        animation: progressFill 1.5s ease-out 0.5s both;
    }
    
    .progress-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: shimmer 2s infinite;
    }
    
    @keyframes progressFill {
        0% { width: 0%; }
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    /* Chart Container Enhancements */
    .chart-container {
        position: relative;
        border-radius: 12px;
        padding: 20px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    /* Legend Dots with Glow */
    .legend-dot {
        position: relative;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
    }
    
    .legend-dot::after {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: inherit;
        background: inherit;
        opacity: 0.3;
        filter: blur(4px);
        z-index: -1;
    }
    
    /* Stat Icon Animations */
    .stat-icon {
        position: relative;
        transition: all 0.3s ease;
    }
    
    .stat-icon::before {
        content: '';
        position: absolute;
        inset: -10px;
        border-radius: inherit;
        background: radial-gradient(circle, currentColor 0%, transparent 70%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .hover-lift:hover .stat-icon::before {
        opacity: 0.1;
    }
    
    /* Badge Enhancements */
    .badge {
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.025em;
        padding: 0.375rem 0.75rem;
        transition: all 0.3s ease;
    }
    
    /* Sales Breakdown Hover Effects */
    .sales-breakdown .row .col-12:hover > div {
        transform: translateX(4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    /* Dropdown Menu Enhancements */
    .dropdown-menu {
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        padding: 0.5rem;
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
    }
    
    .dropdown-item {
        border-radius: 8px;
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
        font-weight: 500;
    }
    
    .dropdown-item:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        transform: translateX(4px);
    }
    
    /* Number Counter Animation */
    .dashboard-stat-number {
        animation: countUp 1.5s ease-out;
        transform: perspective(1000px) rotateX(0deg);
        transition: transform 0.3s ease;
    }
    
    .dashboard-stat-card:hover .dashboard-stat-number {
        transform: perspective(1000px) rotateX(5deg);
    }
    
    @keyframes countUp {
        from {
            opacity: 0;
            transform: perspective(1000px) rotateX(-90deg);
        }
        to {
            opacity: 1;
            transform: perspective(1000px) rotateX(0deg);
        }
    }
    
    /* Responsive Enhancements */
    @media (max-width: 768px) {
        .welcome-banner {
            text-align: center;
        }
        
        .welcome-banner .col-lg-4 {
            margin-top: 1rem;
        }
        
        .status-icon {
            width: 50px !important;
            height: 50px !important;
        }
        
        .dashboard-stat-number {
            font-size: 1.8rem !important;
        }
    }
    
    /* Smooth Scrolling */
    .dashboard-unique-container {
        scroll-behavior: smooth;
    }
    
    /* Custom Scrollbar */
    .dashboard-unique-container::-webkit-scrollbar {
        width: 8px;
    }
    
    .dashboard-unique-container::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    .dashboard-unique-container::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 4px;
    }
    
    .dashboard-unique-container::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #5a67d8, #6b46c1);
    }
    
    /* Dashboard Animations and Enhancements */
    @keyframes dashboardFadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes dashboardBounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        60% {
            transform: translateY(-5px);
        }
    }
    
    @keyframes dashboardPulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
        100% {
            transform: scale(1);
        }
    }
    
    @keyframes dashboardCountUp {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    /* UNIQUE Card Animations */
    .dashboard-stat-card {
        animation: dashboardFadeInUp 0.6s ease-out !important;
        transition: all 0.3s ease !important;
        border-radius: 16px !important;
        overflow: hidden !important;
        position: relative !important;
    }
    
    .dashboard-stat-card:hover {
        transform: translateY(-8px) scale(1.02) !important;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2) !important;
        z-index: 10 !important;
    }
    
    .dashboard-stat-card:nth-child(1) { animation-delay: 0.1s !important; }
    .dashboard-stat-card:nth-child(2) { animation-delay: 0.2s !important; }
    .dashboard-stat-card:nth-child(3) { animation-delay: 0.3s !important; }
    .dashboard-stat-card:nth-child(4) { animation-delay: 0.4s !important; }
    
    /* GLOBAL BLACK TEXT OVERRIDE FOR DASHBOARD */
    .dashboard-unique-container * {
        color: #032693 !important;
    }
    
    .dashboard-unique-container .text-muted {
        color: #6b7280 !important;
    }
    
    .dashboard-unique-container .text-success {
        color: #10b981 !important;
    }
    
    .dashboard-unique-container .text-danger {
        color: #ef4444 !important;
    }
    
    .dashboard-unique-container .text-warning {
        color: #f59e0b !important;
    }
    
    .dashboard-unique-container .text-info {
        color: #06b6d4 !important;
    }
    
    .dashboard-unique-container .text-primary {
        color: #3b82f6 !important;
    }
    
    /* Enhanced card backgrounds for better readability */
    .dashboard-unique-container .card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
        border: 1px solid #e5e7eb !important;
    }
    
    /* UNIQUE Icon Animations */
    .dashboard-stat-icon i {
        animation: dashboardBounce 2s infinite !important;
        transition: all 0.3s ease !important;
        filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3)) !important;
    }
    
    .dashboard-stat-card:hover .dashboard-stat-icon i {
        animation: dashboardPulse 1s infinite !important;
        transform: scale(1.2) rotate(10deg) !important;
        filter: brightness(1.2) drop-shadow(3px 3px 6px rgba(0,0,0,0.4)) !important;
    }
    
    /* UNIQUE Number Counter Animation */
    .dashboard-stat-number {
        animation: dashboardCountUp 0.8s ease-out !important;
        animation-fill-mode: both !important;
        font-family: 'Inter', sans-serif !important;
        font-weight: 900 !important;
        font-size: 2.8rem !important;
        line-height: 1 !important;
        letter-spacing: -0.02em !important;
    }
    
    /* UNIQUE Social icons animation */
    .dashboard-social-icon {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
    }
    
    .dashboard-social-icon:hover {
        transform: scale(1.15) rotate(8deg) !important;
        box-shadow: 0 8px 25px rgba(0,0,0,0.2) !important;
    }
    
    /* UNIQUE Progress bar animations */
    .dashboard-progress-bar {
        animation: dashboardProgressFill 2s ease-out !important;
        transition: width 0.5s ease !important;
        border-radius: 8px !important;
    }
    
    @keyframes dashboardProgressFill {
        from { width: 0% !important; }
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    /* UNIQUE Chart container animations */
    .dashboard-chart-card {
        animation: dashboardFadeInUp 0.8s ease-out !important;
        transition: all 0.3s ease !important;
        border-radius: 12px !important;
    }
    
    .dashboard-chart-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    }
    
    /* UNIQUE Enhanced text visibility - BLACK COLORS */
    .dashboard-enhanced-text {
        color: #1f2937 !important;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
        font-weight: 700 !important;
        letter-spacing: 0.01em !important;
    }
    
    .dashboard-enhanced-number {
        color: #111827 !important;
        text-shadow: 0 2px 4px rgba(0,0,0,0.15) !important;
        font-weight: 900 !important;
        filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.1)) !important;
    }
    
    .dashboard-enhanced-icon {
        text-shadow: 0 2px 4px rgba(0,0,0,0.2) !important;
        filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.1)) !important;
    }
    
    /* UNIQUE Traffic stats styling */
    .dashboard-traffic-stat {
        background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(248,250,252,0.9)) !important;
        border-radius: 12px !important;
        padding: 16px !important;
        margin: 8px 0 !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
        border: 1px solid rgba(229,231,235,0.6) !important;
    }
    
    .dashboard-traffic-number {
        font-weight: 800 !important;
        font-size: 1.8rem !important;
        text-shadow: 0 2px 4px rgba(0,0,0,0.15) !important;
        letter-spacing: -0.01em !important;
    }
    
    .dashboard-traffic-label {
        font-weight: 600 !important;
        color: #4b5563 !important;
        font-size: 0.85rem !important;
    }
    
    /* UNIQUE Sales breakdown styling */
    .dashboard-sales-item {
        background: rgba(255,255,255,0.05) !important;
        border-radius: 8px !important;
        padding: 8px 12px !important;
        margin: 4px 0 !important;
        transition: all 0.3s ease !important;
    }
    
    .dashboard-sales-item:hover {
        background: rgba(255,255,255,0.1) !important;
        transform: translateX(5px) !important;
    }
    
    .dashboard-sales-amount {
        font-weight: 800 !important;
        font-size: 1.1rem !important;
        text-shadow: 0 1px 3px rgba(0,0,0,0.3) !important;
    }
    
    /* Custom Scrollbar for Courier Partner Distribution */
    .courier-partner-list {
        scrollbar-width: thin;
        scrollbar-color: #d1d5db #f9fafb;
    }
    
    .courier-partner-list::-webkit-scrollbar {
        width: 6px;
    }
    
    .courier-partner-list::-webkit-scrollbar-track {
        background: #f9fafb;
        border-radius: 3px;
    }
    
    .courier-partner-list::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 3px;
        transition: background 0.3s ease;
    }
    
    .courier-partner-list::-webkit-scrollbar-thumb:hover {
        background: #9ca3af;
    }
    
    /* Reduce chart canvas sizes */
    #trafficChart, #salesChart {
        max-height: 150px !important;
    }
</style>

<!-- Immediate light theme script -->
<script>
    // Apply light theme immediately before page renders
    (function() {
        document.documentElement.setAttribute('data-pc-theme', 'light');
        document.documentElement.classList.add('light-theme');
        document.body.setAttribute('data-pc-preset', 'preset-1');
    })();
</script>

<!-- Font Awesome Pro 6.0.0-alpha3 CSS -->
<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v6.0.0-alpha3/css/all.css">

<?php 

        $seller = Auth::guard('seller')->user();
       $user_type = $seller->user_type ?? 1;

?>


    <div class="pc-container dashboard-unique-container">
        <div class="pc-content">
            @if ($seller->kyc_status == 0)
                <div class="kyc-profile">
                    <button type="button" class="btn btn-warning d-flex justify-content-center" data-bs-toggle="modal"
                        data-bs-target="#kycModal">
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>Notice!</strong> Please complete your KYC to proceed with your registration.
                        </div>
                    </button>
                </div>
                <div class="modal fade" id="kycModal" tabindex="-1" aria-labelledby="kycModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <!-- Modal Header -->
                        <div class="modal-header d-flex justify-content-between align-items-center">
                            <h5 class="modal-title" id="kycModalLabel">Complete Your KYC</h5>
                            <button type="button"
                                    class="border-0 bg-transparent fs-4"
                                    data-bs-dismiss="modal"
                                    aria-label="Close">
                                &times;
                            </button>
                            <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                        </div>

                        <!-- Modal Body -->
                        <form action="{{ route('seller.kyc.complete') }}" method="POST" id="kycForm">
                            @csrf

                                <input type="hidden" name="bank_name" id="bank_name">
                                <input type="hidden" name="bank_account_verified" id="bank_account_verified">
                                <input type="hidden" name="gst_verified_name" id="gst_verified_name">
                                <input type="hidden" name="pan_verified_name" id="pan_verified_name">
                            <div class="modal-body">
                                <meta name="csrf-token" content="{{ csrf_token() }}">

                                <!-- Bank Verification -->
                                <div class="mb-3">
                                    <label class="form-label">Bank Account Number</label>
                                    <input type="text" id="accountNumber" name="account_number" class="form-control"
                                        placeholder="Enter bank account number" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">IFSC Code</label>
                                    <input type="text" id="ifscCode" name="ifsc_code" class="form-control"
                                        placeholder="Enter IFSC (e.g. HDFC0000001)" required>
                                </div>
                                <button type="button" id="verifyBankBtn" class="btn btn-primary w-100 mb-2" onclick="verifyBank()">
                                    Verify Bank Account
                                </button>
                                <div id="bankStatus" class="mt-2"></div>

                                <hr class="my-3">

                                <!-- GST Verification -->
                                {{-- @if($user_type == 2) --}}
                                <div class="mb-3">
                                    <label for="gstNumber" class="form-label">Enter GSTIN</label>
                                    <input type="text" class="form-control" id="gstNumber" name="gst_number"
                                        maxlength="15" placeholder="Enter GST Number" required>
                                    <button type="button" class="btn btn-info mt-2 w-100" id="verifyGstBtn" onclick="verifyGst()">Verify GST</button>
                                    <div id="gstStatus" class="mt-2"></div>
                                </div>
                                <hr class="my-3">
                                {{-- @endif --}}

                                <!-- PAN Verification -->
                                <div class="mb-3" id="panSection">
                                    <label for="panNumber" class="form-label">PAN Card Number</label>
                                    <input type="text" class="form-control" id="panNumber" name="pan_number"
                                        maxlength="10" placeholder="Enter PAN" required>
                                    <button type="button" class="btn btn-warning mt-2 w-100" id="verifyPanBtn" onclick="verifyPan()">Verify PAN</button>
                                    <div id="panStatus" class="mt-2"></div>
                                </div>

                                <hr class="my-3">

                                <!-- Submit Button -->
                                <div class="col-md-6 mx-auto">
                                    <button type="submit" class="btn btn-primary w-100 mt-3">Complete Your KYC</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>




               


                <!-- JavaScript to toggle forms -->
                <script>
                    function showKycForm(type) {
                        document.querySelectorAll('.kyc-form').forEach(form => form.style.display = 'none');
                        if (type === 'domestic') {
                            document.getElementById('domesticKycForm').style.display = 'block';
                        } else {
                            document.getElementById('internationalKycForm').style.display = 'block';
                        }
                    }

                    function verifyBank() {
                        const account = document.getElementById('accountNumber').value.trim();
                        const ifsc = document.getElementById('ifscCode').value.trim();

                        if (!account || !ifsc) {
                            document.getElementById('bankStatus').innerHTML = '<span class="text-danger">Please enter account number & IFSC.</span>';
                            return;
                        }

                        const btn = document.getElementById('verifyBankBtn');
                        btn.disabled = true;
                        btn.innerText = 'Verifying...';

                        fetch("{{ route('bank.verify') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ account_number: account, ifsc: ifsc })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.ok) {
                                const d = data.data;
                                let bankName = d.name_at_bank ?? 'N/A';
                                let accountNum = d.bank_account ?? account;

                                document.getElementById('bankStatus').innerHTML = `
                                    <div class="text-success">
                                        <b>Verified!</b><br>
                                        Name @ Bank: ${bankName}<br>
                                        A/C: ${accountNum}<br>
                                        IFSC: ${d.ifsc ?? ifsc}
                                    </div>`;
                                
                                // Hidden inputs set
                                document.getElementById('bank_name').value = bankName;
                                document.getElementById('bank_account_verified').value = accountNum;
                            } else {
                                document.getElementById('bankStatus').innerHTML =
                                    `<span class="text-danger">Verification failed: ${data.error ?? 'Unknown error'}</span>`;
                            }
                        })
                        .catch(err => console.error(err))
                        .finally(() => {
                            btn.disabled = false;
                            btn.innerText = 'Verify Bank Account';
                        });
                    }

                    function verifyPan() {
                        const panNumber = document.getElementById('panNumber').value;
                        const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;

                        if (!panRegex.test(panNumber)) {
                            document.getElementById('panStatus').innerHTML = '<span class="text-danger">Invalid PAN format.</span>';
                            return;
                        }

                        fetch("{{ route('pan.verify') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ pan_number: panNumber })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.ok) {
                                let name = data.data.registered_name ?? 'N/A';
                                document.getElementById('panStatus').innerHTML = '<span class="text-success">PAN Verified: ' + name + '</span>';
                                document.getElementById('pan_verified_name').value = name;
                            } else {
                                document.getElementById('panStatus').innerHTML = '<span class="text-danger">Verification failed: ' + data.error + '</span>';
                            }
                        })
                        .catch(err => console.error(err));
                    }

                    function verifyGst() {
                        const gstNumber = document.getElementById('gstNumber').value;
                        // if (!gstRegex.test(gstNumber)) {
                        //     document.getElementById('gstStatus').innerHTML = '<span class="text-danger">Invalid GST format.</span>';
                        //     return;
                        // }

                        fetch("{{ route('gst.verify') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ gst_number: gstNumber })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.ok) {
                                let businessName = data.data.legal_name_of_business ?? 'N/A';
                                document.getElementById('gstStatus').innerHTML = '<span class="text-success">GST Verified: ' + businessName + '</span>';
                                document.getElementById('gst_verified_name').value = businessName;
                            } else {
                                document.getElementById('gstStatus').innerHTML = '<span class="text-danger">Verification failed: ' + data.error + '</span>';
                            }
                        })
                        .catch(err => console.error(err));
                    }
                </script>

            @endif


            
         
           
            @if ($seller->agreement_accepted == 0)
                <!-- Agreement Modal -->
                   <div class="modal fade" id="agreementModal" tabindex="-1" aria-labelledby="agreementModalLabel"
                    aria-hidden="false" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="agreementModalLabel">Client Service Agreement</h5>
                                <button type="button"
                                    class="border-0 bg-transparent fs-4"
                                    data-bs-dismiss="modal"
                                    aria-label="Close">
                                    &times;
                                </button>
                                <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                            </div>
                            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                                <!-- Agreement Content (from PDF) -->
                                <div class="client-service-agreement">
                                    <h4>CLIENT SERVICE AGREEMENT</h4>
                                    <p><strong>SHIPXPEED LOGISTICS LLP</strong><br>
                                        <strong>GST No.: 07AFPF52846112A</strong>
                                    </p>
                                    <p>This Client Service Agreement (“Agreement”) is made and entered into <span
                                            id="current-date">___ Date</span>
                                        and between:</p>
                                    <p><strong>Shipxpeed Logistics LLP</strong>, a limited liability partnership having its
                                        registered office at Grandthum, Tower B,10th floor,Office No-1032, Sector-Tech Zone IV, Greater Noida, Uttar Pradesh 201308, India (hereinafter referred to as “Shipxpeed” or “Company”), which
                                        expression shall unless it be repugnant to the context or meaning thereof be deemed
                                        to include its successors and permitted assigns,</p>
                                    <p>AND</p>
                                    <p>
                                        (hereinafter referred to as the “Client”), which expression shall unless it be
                                        repugnant to the context or meaning thereof be deemed to include its successors and
                                        permitted assigns.
                                    </p>

                                    <p><strong>WHEREAS:</strong></p>
                                    <ul>
                                        <li>The Client desires to engage Shipxpeed as its logistics service provider for
                                            specific and lawful business activities;</li>
                                        <li>Shipxpeed agrees to provide such logistics and platform-based services under the
                                            terms and conditions set forth herein;</li>
                                    </ul>

                                    <p><strong>NOW, THEREFORE, in consideration of the mutual covenants and promises herein
                                            contained, the parties agree as follows:</strong></p>

                                    <h5>1. DEFINITIONS AND INTERPRETATION</h5>
                                    <ul>
                                        <li>“Services” means logistics, order processing, courier aggregation, returns
                                            management, and related services provided through Shipxpeed’s technology
                                            platform.</li>
                                        <li>“COD” refers to Cash on Delivery.</li>
                                        <li>“RTO” refers to Return to Origin.</li>
                                        <li>“NDR” means Non-Delivery Report.</li>
                                        <li>“VAS” means Value Added Services including but not limited to influencer
                                            marketing, WhatsApp bots, and tracking support.</li>
                                    </ul>

                                    <h5>2. SCOPE OF SERVICES</h5>
                                    <ul>
                                        <li>Courier aggregation & tracking support</li>
                                        <li>API and panel-based order placement</li>
                                        <li>COD management & reconciliation</li>
                                        <li>Hyperlocal and national delivery</li>
                                        <li>NDR and RTO follow-ups</li>
                                        <li>Wallet-based billing for prepaid/postpaid services</li>
                                        <li>KYC verification and seller onboarding</li>
                                        <li>Value Added Services (VAS)</li>
                                    </ul>
                                    <p>Shipxpeed may update or change its services upon giving the Client a 15-day written
                                        notice.</p>

                                    <h5>3. ONBOARDING AND VERIFICATION</h5>
                                    <ul>
                                        <li>The Client shall complete onboarding by submitting KYC details including PAN,
                                            GST, Aadhaar (if applicable), and bank details.</li>
                                    
                                        <li>Upon successful verification of all mandatory fields, accounts will be
                                            auto-approved.</li>
                                    </ul>

                                    <h5>4. PAYMENT TERMS</h5>
                                    <ul>
                                        <li>All payment is prepaid by default. No credit shall be extended unless explicitly
                                            approved by Shipxpeed.</li>
                                        <li>If credit-based billing is approved in writing by Shipxpeed, the Client must
                                            clear all invoices within 7 calendar days from the date of issuance.</li>
                                        <li>Failure to pay within 7 days will result in:
                                            <ul>
                                                <li>An interest charge of 18% per annum on the overdue amount.</li>
                                                <li>Immediate hold on all order processing and service access until payment
                                                    is received in full.</li>
                                            </ul>
                                        </li>
                                        <li>Shipxpeed may also withhold COD remittance against any outstanding dues without
                                            further notice.</li>
                                    </ul>

                                    <h5>5. CLIENT OBLIGATIONS</h5>
                                    <ul>
                                        <li>The Client shall not misuse the platform for any illegal or prohibited activity.
                                        </li>
                                        <li>All shipments must be accurately invoiced and securely packed.</li>
                                        <li>The Client must comply with all applicable tax and transport laws.</li>
                                        <li>The Client shall cooperate on failed delivery or NDR escalations.</li>
                                    </ul>

                                    <h5>6. PROHIBITED PRODUCTS</h5>
                                    <p>The Client shall not use Shipxpeed for transporting or trading in:</p>
                                    <ul>
                                        <li>Narcotics or illegal drugs</li>
                                        <li>Alcohol, tobacco, or vape items</li>
                                        <li>Weapons, ammunition, explosives</li>
                                        <li>Currency, bullion, or gems</li>
                                        <li>Pornographic materials</li>
                                        <li>Live animals</li>
                                        <li>Counterfeit products</li>
                                        <li>Chemicals, biohazards, or hazardous items</li>
                                        <li>Any item restricted under Indian law</li>
                                    </ul>
                                    <p>Violation will lead to immediate suspension and legal action.</p>

                                    <h5>6A. ILLEGAL ACTIVITY & LIABILITY</h5>
                                    <p><strong>i. Dangerous Goods:</strong></p>
                                    <p>Strictly prohibited from being shipped via Shipxpeed services:</p>
                                    <ul>
                                        <li>Oil-based paints and thinners (flammable liquids)</li>
                                        <li>Industrial solvents</li>
                                        <li>Insecticides, garden chemicals</li>
                                        <li>Lithium batteries</li>
                                        <li>Magnetized materials</li>
                                        <li>Machinery with/containing fuel</li>
                                        <li>Camp stove fuel, torch fuel</li>
                                        <li>Automobile batteries</li>
                                        <li>Infectious substances</li>
                                        <li>Bleach</li>
                                        <li>Flammable adhesives</li>
                                        <li>Arms/ammunition (including air guns)</li>
                                        <li>Dry ice (Carbon Dioxide, Solid)</li>
                                        <li>Any flammable aerosols, liquids, powders</li>
                                    </ul>
                                    <p><strong>ii. Restricted Items:</strong></p>
                                    <ul>
                                        <li>Precious stones, gems, and jewellery</li>
                                        <li>Bearer drafts, cheques, currency, coins</li>
                                        <li>Poison</li>
                                        <li>Firearms, explosives, military equipment</li>
                                        <li>Hazardous and radioactive materials</li>
                                        <li>Foodstuff and liquor</li>
                                        <li>Pornographic content</li>
                                        <li>Hazardous chemicals</li>
                                    </ul>

                                    <h5>6B. DANGEROUS GOODS AND RESTRICTED ITEMS</h5>
                                    <ul>
                                        <li>Client is fully liable for misuse involving illegal trade or fraud.</li>
                                        <li>Shipxpeed holds no responsibility for penalties or losses from such actions.
                                        </li>
                                        <li>Legal notices received will be redirected to the Client.</li>
                                        <li>Shipxpeed may suspend/terminate the Client’s account immediately if violations
                                            are found.</li>
                                    </ul>

                                    <h5>7. WALLET SYSTEM</h5>
                                    <ul>
                                        <li>Wallet auto-debits/credits for orders, cancellations, or refunds.</li>
                                        <li>Wallet transactions can be viewed from the Client dashboard.</li>
                                    </ul>

                                    <h5>8. PLATFORM FEATURES</h5>
                                    <ul>
                                        <li>Seamless Order processing system via multiple couriers</li>
                                        <li>NDR Management</li>
                                        <li>Real-time shipment tracking via panel or API.</li>
                                        <li>Communication module</li>
                                        <li>AI-Based courier allocation system</li>
                                    </ul>

                                    <h5>9. CLAIMS AND LIABILITY</h5>
                                    <ul>
                                        <li>In case of any loss or liability case , the seller must escalate to the Ops & Support team within 24 hours.</li>
                                        <li>Max liability: ₹1000 or invoice value (whichever is lower)</li>
                                        <li>Valid POD and unboxing proof required for all claims.</li>
                                    </ul>

                                    <h5>10. NON-SOLICITATION</h5>
                                    <p>The Client will not solicit or engage Shipxpeed partners/staff/vendors directly for
                                        12 months post termination.</p>

                                    <h5>11. TERM AND TERMINATION</h5>
                                    <ul>
                                        <li>Agreement stays active until terminated.</li>
                                        <li>Either party can terminate with 30 days' written notice.</li>
                                        <li>Immediate termination applies in case of breach, illegality, or non-payment.
                                        </li>
                                    </ul>

                                    <h5>12. INDEMNIFICATION</h5>
                                    <p>Client will indemnify Shipxpeed for losses, penalties, or legal action due to:</p>
                                    <ul>
                                        <li>Breach of agreement</li>
                                        <li>Law violations</li>
                                        <li>Platform misuse</li>
                                    </ul>

                                    <h5>13. LIMITATION OF LIABILITY</h5>
                                    <p>Shipxpeed is not liable for indirect or consequential damages. Liability is limited
                                        to the specific shipment fee.</p>

                                    <h5>14. FORCE MAJEURE</h5>
                                    <p>Shipxpeed shall not be held liable for service failure due to unforeseen events
                                        beyond its control.</p>

                                    <h5>15. GOVERNING LAW & DISPUTES</h5>
                                    <p>Governing law: India. Jurisdiction: Delhi. Disputes resolved per Arbitration and
                                        Conciliation Act, 1996.</p>

                                    <h5>16. ENTIRE AGREEMENT</h5>

                                    <p>This document is the complete agreement and overrides any previous communications.
                                    </p>

                                    <h5>16. WEIGHT DISPUTES</h5>

                                    <p>In the event of any weight discrepancy or weight update relating to a shipment, the final determination shall rest solely with ShipXpeed. The Seller may request documentary proof supporting such determination. ShipXpeed shall use reasonable efforts to obtain and provide such proof to the Seller, subject to the courier partner making the relevant documentation or evidence available to ShipXpeed.
                                    </p>

                                   
                                    <h5>17. ACCEPTANCE</h5>
                                    <p>I, the undersigned Client, hereby declare that I have read, understood, and agreed to
                                        all the terms mentioned in this legally binding Agreement.</p>
                                </div>





                <form id="agreementForm" action="{{ route('seller.agreement.accept') }}" method="POST">
                    @csrf
                    <meta name="csrf-token" content="{{ csrf_token() }}">

                    <div class="mb-3">
                        <label for="aadhaarNumber" class="form-label">Aadhaar Number</label>
                        <input type="text" class="form-control" id="aadhaarNumber" name="aadhaar_number" maxlength="12" required>
                        <button type="button" class="btn btn-success mt-2" onclick="myFunction()" id="generateOtpBtn">Send OTP</button>
                    </div>

                    <div class="mb-3 d-none" id="otpSection">
                        <label for="aadhaarOtp" class="form-label">Enter OTP</label>
                        <input type="text" class="form-control" id="aadhaarOtp" maxlength="6">
                        <button type="button" class="btn btn-info mt-2" id="verifyOtpBtn" onclick="verifyOtp()">Verify OTP</button>
                        <div id="aadhaarStatus" class="mt-2"></div>
                    </div>

                    <!-- Aadhaar details (auto-fill after verification) -->
                    <div class="mb-3 d-none" id="aadhaarDetails">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="clientName" id="aadhaarName" readonly>

                        <label class="form-label">Care Of</label>
                        <input type="text" class="form-control" name="aadhaar_care_of" id="aadhaarCareOf" readonly>

                        <label class="form-label">DOB</label>
                        <input type="text" class="form-control" name="aadhaar_dob" id="aadhaarDob" readonly>

                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="clientAddress" id="aadhaarAddress" readonly></textarea>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">
                            I have read and agree to the terms of this agreement.
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary" id="finalSubmit" disabled>Submit</button>
                </form>



                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" id="submitAgreement">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
              
                
            @endif

            <!-- JavaScript to show Agreement Modal automatically -->
            @if ($seller->agreement_accepted == 0)
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Show the agreement modal automatically
                    var agreementModal = new bootstrap.Modal(document.getElementById('agreementModal'), {
                        backdrop: 'static',
                        keyboard: false
                    });
                    agreementModal.show();
                });
            </script>
            @endif
            
           
            <script>
                function myFunction() {
                    //alert('Generate OTP button clicked!');
                    // e.preventDefault();
                    // e.stopPropagation();
                    //console.log('=== GENERATE OTP CLICKED ===');
                    
                    const aadhaarNumber = $('#aadhaarNumber').val();
                    //console.log('Aadhaar Number:', aadhaarNumber);
                    

                    if (!aadhaarNumber) {
                        alert('Please enter Aadhaar number first!');
                        return;
                    }
                    
                    if (aadhaarNumber.length !== 12) {
                        alert('Please enter a valid 12-digit Aadhaar number.');
                        return;
                    }

                    // Show loading state
                    $(this).prop('disabled', true).text('Sending OTP...');
                    console.log('Button disabled, sending request...');
                    
                    $.ajax({
                        url: "{{ route('aadhaar.generate') }}",
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            aadhaar_number: aadhaarNumber
                        },
                        beforeSend: function() {
                            console.log('AJAX request starting...');
                        },
                        success: function(data) {
                            console.log('=== SUCCESS RESPONSE ===');
                            console.log('Response:', data);
                            
                            if (data.ok && data.data.status === "SUCCESS") {
                                refId = data.data.ref_id;
                                console.log('RefId set:', refId);
                                $('#otpSection').removeClass('d-none');
                                $('#aadhaarStatus').html(`<span class="text-success">${data.data.message}</span>`);
                                alert('OTP sent successfully!');
                            } else {
                                $('#aadhaarStatus').html(`<span class="text-danger">${data.error ? data.error.message : 'Something went wrong'}</span>`);
                                alert('Failed to send OTP: ' + (data.error ? data.error.message : 'Unknown error'));
                            }
                            $('#generateOtpBtn').prop('disabled', false).text('Send OTP');
                        },
                        error: function(xhr, status, error) {
                            console.log('=== ERROR RESPONSE ===');
                            console.error('AJAX Error:', error);
                            console.error('Status:', status);
                            console.error('Response:', xhr.responseText);
                            $('#aadhaarStatus').html(`<span class="text-danger">Error: ${error}</span>`);
                            $('#generateOtpBtn').prop('disabled', false).text('Send OTP');
                            alert('AJAX Error: ' + error);
                        }
                    });
                }


                function verifyOtp() {                    
                    alert('Test!');
                    const otp = $('#aadhaarOtp').val();
                    //console.log('OTP:', otp, 'RefId:', refId);
                    
                    if (!refId) {
                        alert('Please generate OTP first!');
                        return;
                    }
                    
                    if (!otp || otp.length !== 6) {
                        alert('Please enter a valid 6-digit OTP.');
                        return;
                    }

                    $(this).prop('disabled', true).text('Verifying...');

                    $.ajax({
                        url: "{{ route('aadhaar.verify') }}",
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            ref_id: refId,
                            otp: otp
                        },
                        success: function(data) {
                            console.log("=== VERIFY RESPONSE ===");
                            console.log("Response:", data);

                            if (data.ok && data.data.status === "VALID") {
                                const details = data.data;
                                $('#aadhaarName').val(details.name);
                                $('#aadhaarCareOf').val(details.care_of);
                                $('#aadhaarDob').val(details.dob);
                                $('#aadhaarAddress').val(details.address);
                                $('#aadhaarDetails').removeClass('d-none');
                                $('#aadhaarStatus').html(`<span class="text-success">Aadhaar verified successfully!</span>`);
                                $('#finalSubmit').prop('disabled', false);
                                alert('Aadhaar verified successfully!');
                            } else {
                                $('#aadhaarStatus').html(`<span class="text-danger">Verification failed</span>`);
                                alert('Verification failed!');
                            }
                            $('#verifyOtpBtn').prop('disabled', false).text('Verify OTP');
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', error);
                            $('#aadhaarStatus').html(`<span class="text-danger">Error: ${error}</span>`);
                            $('#verifyOtpBtn').prop('disabled', false).text('Verify OTP');
                            alert('Verification error: ' + error);
                        }
                    });
                }
            </script>
            <!-- Notice Section -->
            <div class="container-fluid mb-3 px-0" style="position:relative;z-index:1;    margin-left: 12px;">
                <div class="notice-marquee-wrapper position-relative" style="background:#fff6e5; border:1px solid #ffd27c; border-radius:7px;font-size:16px; overflow:hidden;">
                    <span class="notice-label position-absolute start-0 top-0 bottom-0 d-flex align-items-center px-3 fw-bold" style="background:#ffde92;color:#914f00;height:100%;letter-spacing:0.08em;z-index:2;border-radius:7px 0 0 7px;box-shadow:1px 0 6px rgba(0,0,0,0.02);">
                        NOTICE -
                    </span>
                    <div class="marquee-container" style="margin-left:110px; padding:0 12px; overflow:hidden; height:30px;">
                        <div class="notice-marquee-message d-flex align-items-center" id="noticeMarqueeMessage" style="white-space:nowrap; display:flex; font-weight:500; color:#9d6e1e;">
                            <!-- The repeated messages will be generated with JS -->
                        </div>
                    </div>
                </div>
                
                <style>
                    .notice-marquee-wrapper {
                        box-shadow: 0 1px 7px -3px #ffe7b7;
                    }
                    .notice-label {
                        font-size: 15px;
                        text-shadow: 0 1px 2px #ffeec8;
                    }
                    .marquee-container {
                        position:relative;
                        height:42px;
                        display:flex;
                        align-items:center;
                        overflow:hidden;
                    }
                    .notice-marquee-message {
                        animation: notice-marquee-left-right 18s linear infinite;
                    }
                    @keyframes notice-marquee-left-right {
                        0% { transform:translateX(0%);}
                        100% { transform:translateX(-50%);}
                    }
                </style>
                <script>
                    (function() {
                        var noticeText = "Due to rising fuel costs, a minimal fuel surcharge may be applicable on shipments from 16 April 2026.";
                        var container = document.getElementById("noticeMarqueeMessage");
                        if (!container) return;
                        // Create repeated texts to always fill (at least) twice the marquee container's width
                        var repeatCount = 12; // Enough to overflow for most screens
                        var nodes = [];
                        for (var i = 0; i < repeatCount; i++) {
                            var span = document.createElement("span");
                            span.style.marginRight = "42px";
                            span.textContent = noticeText;
                            nodes.push(span);
                        }
                        nodes.forEach(function(node){ container.appendChild(node); });
                        
                        // Recalculate animation duration based on total message length
                        function updateMarqueeAnimation() {
                            var marquee = container;
                            var containerWidth = marquee.parentElement.offsetWidth;
                            var messagesWidth = marquee.scrollWidth;
                            // No blank space: loop halfway (since content is repeated)
                            if (messagesWidth > 0) {
                                var duration = (messagesWidth / 80); // adjust 80: lower=faster
                                marquee.style.animationDuration = duration + "s";
                            }
                        }
                        window.addEventListener('resize', updateMarqueeAnimation);
                        updateMarqueeAnimation();
                    })();
                </script>
            </div>
            <!-- Dashboard Main Content -->
<div class="modern-dashboard">
    <div class="container-fluid px-0">
        <!-- Welcome Section -->
        <div class="row align-items-center mb-3 flex-wrap">
            <div class="col-12 d-flex flex-row align-items-center justify-content-between flex-wrap gap-2 px-0" style="min-width:0;">
                <!-- Welcome -->
                <div class="welcome-container flex-grow-1 text-truncate" style="min-width:0;">
                    <h2 class="welcome-title mb-1" style="font-size:1.3rem;white-space:normal;">
                        Welcome, {{ Auth::guard('seller')->user()->name ?? Auth::guard('seller')->user()->business_name ?? 'Seller' }}!
                    </h2>
                    <p class="welcome-subtitle mb-0" style="font-size:1rem;white-space:normal;">{{ now()->format('l, F j, Y') }}</p>
                </div>
                <!-- Filter -->
                <div class="dashboard-filter-dropdown position-relative d-flex align-items-center flex-shrink-0 w-auto" style="min-width:120px;">
                    <button 
                        type="button"
                        id="dashboardFilterBtn"
                        class="btn btn-light d-flex align-items-center gap-1 px-3 py-2 fw-semibold"
                        style="background: rgba(246,248,250,0.8); border-radius: 8px; border: 1px solid #dde5ed; color: #1a2438; font-size: 15px; min-width:90px;"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        tabindex="0"
                    >
                        <i class="ti ti-filter d-inline-block" style="font-size: 18px;"></i>
                        <span class="d-inline" style="font-size: 15px;">Filter</span>
                        <i class="ti ti-chevron-down ms-1" style="font-size: 15px;"></i>
                    </button>
                    <!-- Dropdown Menu -->
                    <div 
                        id="dashboardFilterDropdownMenu"
                        class="dropdown-menu p-2 shadow"
                        style="min-width:180px;border-radius:9px;top:110%;right:0;left:auto;display:none;position:absolute;z-index:1200"
                        aria-labelledby="dashboardFilterBtn"
                        tabindex="-1"
                    >
                        <!-- <button type="button" class="dropdown-item w-100 text-start" data-value="1">Today</button> -->
                        <!-- <button type="button" class="dropdown-item w-100 text-start" data-value="7">Last 7 days</button> -->
                        <!-- <button type="button" class="dropdown-item w-100 text-start" data-value="15">Last 15 days</button> -->
                        <button type="button" class="dropdown-item w-100 text-start" data-value="30">Last 30 days</button>
                        <!-- <button type="button" class="dropdown-item w-100 text-start" data-value="31">Last Month</button> -->
                        <!-- <button type="button" class="dropdown-item w-100 text-start" data-value="custom">Custom</button> -->
                    </div>
                </div>
            </div>
            <style>
                .dashboard-filter-dropdown .btn:focus, .dashboard-filter-dropdown .btn:active {
                    box-shadow: none !important;
                    outline: none !important;
                }
                .welcome-title,
                .welcome-subtitle {
                    word-break: break-word;
                    white-space: normal !important;
                }
                .dashboard-filter-dropdown {
                    z-index: 1200;
                }
                @media (max-width: 992px) {
                    .welcome-title {
                        font-size: 1.1rem !important;
                    }
                    .welcome-subtitle {
                        font-size: 0.97rem !important;
                    }
                    .dashboard-filter-dropdown {
                        width: auto !important;
                        min-width: 0 !important;
                    }
                    #dashboardFilterBtn {
                        min-width: 90px !important;
                        font-size: 14px !important;
                    }
                }
                @media (max-width: 768px) {
                    .welcome-title,
                    .welcome-subtitle {
                        font-size: 1rem !important;
                    }
                    .dashboard-filter-dropdown {
                        padding-right: 0 !important;
                    }
                }
                @media (max-width: 576px) {
                    .col-12.d-flex.flex-row.align-items-center.justify-content-between.flex-wrap.gap-2.px-0 {
                        flex-wrap: wrap !important;
                        gap: 0.5rem !important;
                    }
                    .welcome-title,
                    .welcome-subtitle {
                        font-size: 0.95rem !important;
                        padding-right:0.2rem !important;
                    }
                    #dashboardFilterBtn,
                    #dashboardFilterDropdownMenu button {
                        font-size: 14px !important;
                        padding: 0.45rem 1.05rem !important;
                    }
                }
                @media (max-width: 400px) {
                    .welcome-title,
                    .welcome-subtitle {
                        font-size: 0.85rem !important;
                    }
                    #dashboardFilterBtn span {
                        font-size: 13px !important;
                    }
                }

                /*--- Filter dropdown positon: mobile below filter btn, desktop below filter btn but align right ---*/
                .dashboard-filter-dropdown {
                    position: relative;
                }
                #dashboardFilterDropdownMenu {
                    animation: fadein .2s;
                }
                @keyframes fadein {
                    from { opacity:0; transform: translateY(6px);}
                    to { opacity:1; transform: translateY(0);}
                }

                @media (max-width: 768px) {
                    #dashboardFilterDropdownMenu {
                        right: auto !important;
                        left: 0 !important;
                        top: calc(100% + 3px) !important;
                        width: 115px !important;
                        min-width: 0 !important;
                        max-width: 98vw !important;
                        border-radius: 10px 10px 12px 12px !important;
                        position: absolute !important;
                        /* Touch-friendly */
                        box-shadow: 0 12px 32px rgba(44,57,102,0.13);
                    }
                }
            </style>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const filterBtn = document.getElementById('dashboardFilterBtn');
                    const dropdown = document.getElementById('dashboardFilterDropdownMenu');
                    let open = false;
                    let lastFocusedEl = null;

                    function updateDropdownPosition() {
                        // On mobile, dropdown always aligns left below filterBtn and is full width
                        // On desktop, aligns to right=0 (default)
                        if (window.innerWidth <= 768) {
                            dropdown.style.position = "absolute";
                            dropdown.style.left = "0";
                            dropdown.style.right = "auto";
                            dropdown.style.width = "100vw";
                            dropdown.style.maxWidth = "98vw";
                            dropdown.style.top = "calc(100% + 3px)";
                            dropdown.style.borderRadius = "10px 10px 12px 12px";
                        } else {
                            dropdown.style.position = "absolute";
                            dropdown.style.right = "0";
                            dropdown.style.left = "auto";
                            dropdown.style.width = "180px";
                            dropdown.style.maxWidth = "260px";
                            dropdown.style.top = "110%";
                            dropdown.style.borderRadius = "9px";
                        }
                        dropdown.style.zIndex = "1200";
                    }

                    function showDropdown() {
                        updateDropdownPosition();
                        dropdown.style.display = 'block';
                        dropdown.setAttribute('aria-hidden', 'false');
                        // Focus for a11y
                        setTimeout(()=>{
                            const firstItem = dropdown.querySelector('.dropdown-item');
                            if(firstItem) firstItem.focus();
                        }, 0);
                        document.body.style.overflow = (window.innerWidth <= 768) ? 'hidden' : '';
                    }

                    function hideDropdown() {
                        dropdown.style.display = 'none';
                        dropdown.setAttribute('aria-hidden', 'true');
                        if (lastFocusedEl) lastFocusedEl.focus();
                        document.body.style.overflow = '';
                    }

                    function closeDropdown(e) {
                        if (dropdown && !dropdown.contains(e.target) && !filterBtn.contains(e.target)) {
                            hideDropdown();
                            filterBtn.setAttribute('aria-expanded', 'false');
                            document.removeEventListener('mousedown', closeDropdown, true);
                            open = false;
                        }
                    }

                    if (filterBtn) {
                        filterBtn.addEventListener('click', function(evt){
                            evt.preventDefault();
                            lastFocusedEl = filterBtn;
                            open = !open;
                            if (open) {
                                showDropdown();
                                filterBtn.setAttribute('aria-expanded', 'true');
                                setTimeout(() => document.addEventListener('mousedown', closeDropdown, true), 0);
                            } else {
                                hideDropdown();
                                filterBtn.setAttribute('aria-expanded', 'false');
                                document.removeEventListener('mousedown', closeDropdown, true);
                            }
                        });
                        filterBtn.addEventListener('keydown', function(e){
                            if ((e.key === 'Enter' || e.key === ' ') && !open) {
                                e.preventDefault();
                                lastFocusedEl = filterBtn;
                                open = true;
                                showDropdown();
                                filterBtn.setAttribute('aria-expanded', 'true');
                                setTimeout(() => document.addEventListener('mousedown', closeDropdown, true), 0);
                            }
                            if(open && (e.key === 'Escape' || e.key === 'Esc')) {
                                hideDropdown();
                                filterBtn.setAttribute('aria-expanded', 'false');
                                open = false;
                                document.removeEventListener('mousedown', closeDropdown, true);
                            }
                        });
                    }

                    // Reposition dropdown on resize if open
                    window.addEventListener('resize', function() {
                        if (open) {
                            updateDropdownPosition();
                        }
                    });

                    // Handle dropdown option click (except custom)
                    dropdown.querySelectorAll('.dropdown-item').forEach(function(item) {
                        item.addEventListener('click', function() {
                            const val = this.getAttribute('data-value');
                            if (val === 'custom') return;
                            hideDropdown();
                            filterBtn.setAttribute('aria-expanded', 'false');
                            open = false;
                            // Implement filtering logic here if needed
                            console.log('Filter selected:', val);
                        });
                        item.addEventListener('keydown',function(e){
                            if (e.key === 'Escape' || e.key === 'Esc') {
                                hideDropdown();
                                filterBtn.setAttribute('aria-expanded', 'false');
                                open = false;
                                document.removeEventListener('mousedown', closeDropdown, true);
                            }
                        });
                    });

                    // Escape closes dropdown
                    dropdown.addEventListener('keydown', function(e){
                        if(e.key === 'Escape' || e.key === 'Esc') {
                            hideDropdown();
                            filterBtn.setAttribute('aria-expanded', 'false');
                            open = false;
                            document.removeEventListener('mousedown', closeDropdown, true);
                        }
                    });

                    // Always allow scroll in the dropdown if overflow
                    dropdown.style.maxHeight = '65vh';
                    dropdown.style.overflowY = 'auto';

                    // Prevent scroll behind dropdown on mobile
                    dropdown.addEventListener('touchmove', function(e){e.stopPropagation();}, { passive:false });
                });
            </script>
        <!-- <form class="d-flex gap-2" id="dashboardFilterForm">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="dateFilter" id="filter7" value="7" disabled>
                        <label class="form-check-label" for="filter7">Last 7 days</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="dateFilter" id="filter15" value="15" disabled>
                        <label class="form-check-label" for="filter15">Last 15 days</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="dateFilter" id="filterMonth" value="month" disabled>
                        <label class="form-check-label" for="filterMonth">Last Month</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="dateFilter" id="filterCustom" value="custom" disabled>
                        <label class="form-check-label" for="filterCustom" style="cursor:pointer;" id="customLabel">
                            Custom
                            <span id="customDateText" class="text-primary"></span>
                        </label>
                    </div>
                </form> -->
        
        
                <!-- Modal for Custom Date Range -->
        <!-- <div class="modal fade" id="customDateModal" tabindex="-1" aria-labelledby="customDateModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="customDateModalLabel">Select Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label for="dateFrom" class="form-label">From:</label>
                  <input type="date" id="dateFrom" class="form-control">
                </div>
                <div class="mb-3">
                  <label for="dateTo" class="form-label">To:</label>
                  <input type="date" id="dateTo" class="form-control">
                </div>
              </div>
              <div class="modal-footer">
                <button id="applyCustomDateBtn" type="button" class="btn btn-primary">Apply</button>
              </div>
            </div>
          </div>
        </div> -->
        <script>
        // Enable filters after page load (simulate ready state for demo)
        document.addEventListener("DOMContentLoaded", function() {
            ['filter7', 'filter15', 'filterMonth', 'filterCustom'].forEach(function(id) {
                document.getElementById(id).disabled = false;
            });
        });

        // Custom modal trigger
        document.addEventListener('DOMContentLoaded', function() {
            var customInput = document.getElementById('filterCustom');
            var customLabel = document.getElementById('customLabel');
            var customModal = new bootstrap.Modal(document.getElementById('customDateModal'));
            var customDateText = document.getElementById('customDateText');

            // Open modal on clicking either radio or label
            customInput.addEventListener('change', function() {
                if (this.checked)
                    customModal.show();
            });
            customLabel.addEventListener('click', function(e) {
                if (customInput.checked) customModal.show();
            });

            // Apply button in modal
            document.getElementById('applyCustomDateBtn').addEventListener('click', function() {
                var from = document.getElementById('dateFrom').value;
                var to = document.getElementById('dateTo').value;
                if (from && to) {
                    customDateText.textContent = ` (${from} to ${to})`;
                    customModal.hide();
                } else {
                    customDateText.textContent = "";
                    alert("Please select both dates.");
                }
            });
        });
        </script>
        <!-- Stats Row -->
        <style>
            .stat-card {
                min-height: 74px;
                height: 74px;
                padding: 0.45rem !important;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .stat-icon {
                min-width: 32px !important;
                width: 32px !important;
                height: 32px !important;
                font-size: 1.3rem !important;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .stat-title,
            .stat-value {
                font-size: 0.8rem !important;
                line-height: 1.2;
            }
            .stat-value {
                font-weight: bold;
                margin-bottom: 0 !important;
            }
            .stat-card .stat-title {
                display: block;
                margin-bottom: 0.05rem;
            }
            @media (min-width: 576px) {
                .stat-card {
                    min-height: 80px;
                    height: 80px;
                }
            }
        </style>
        <div class="row g-2 g-sm-3 mb-3">
            <!-- Assigned Order Card -->
            <div class="col-6 col-md-2 fade-in fade-in-delay-1">
                <div class="stat-card h-100">
                    <div class="d-flex align-items-center mb-1 gap-2">
                        <div class="stat-icon"
                             style="background-color: rgba(79, 70, 229, 0.1); color: var(--primary-color);margin-top: -4px;">
                            <i class="ti ti-shopping-cart"></i>
                        </div>
                        <div>
                            <span class="stat-title">Assigned Order</span>
                            <span class="stat-value">{{ $orderCount }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- In-Transit Card -->
            <div class="col-6 col-md-2 fade-in fade-in-delay-1">
                <div class="stat-card h-100">
                    <div class="d-flex align-items-center mb-1 gap-2">
                        <div class="stat-icon"
                             style="background-color: rgba(79, 70, 229, 0.1); color: var(--primary-color);">
                            <i class="ti ti-clipboard-list"></i>
                        </div>
                        <div>
                            <span class="stat-title">In-Transit</span>
                            <span class="stat-value">{{ $transitdeliveredCount }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Out for Delivery Card -->
            <div class="col-6 col-md-2 fade-in fade-in-delay-2">
                <div class="stat-card h-100">
                    <div class="d-flex align-items-center mb-1 gap-2">
                        <div class="stat-icon"
                             style="background-color: rgba(79, 70, 229, 0.1); color: var(--primary-color);margin-top: -4px;">
                            <i class="ti ti-truck-delivery"></i>
                        </div>
                        <div>
                            <span class="stat-title">Out for Delivery</span>
                            <span class="stat-value">{{ $outForDeliveryCount }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Delivered Card -->
            <div class="col-6 col-md-2 fade-in fade-in-delay-3">
                <div class="stat-card h-100">
                    <div class="d-flex align-items-center mb-1 gap-2">
                        <div class="stat-icon"
                             style="background-color: rgba(79, 70, 229, 0.1); color: var(--primary-color);">
                            <i class="fa-regular fa-circle-check"></i>
                        </div>
                        <div>
                            <span class="stat-title">Delivered</span>
                            <span class="stat-value">{{ $deliveredCount }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- NDR Card -->
            <div class="col-6 col-md-2 fade-in fade-in-delay-1">
                <div class="stat-card h-100">
                    <div class="d-flex align-items-center mb-1 gap-2">
                        <div class="stat-icon"
                             style="background-color: rgba(79, 70, 229, 0.1); color: var(--primary-color);">
                            <img src="{{ asset('assets/website/img/ndr.png') }}" alt="NDR" style="height:28px; width:21px; display:block; margin:auto;">
                        </div>
                        <div>
                            <span class="stat-title">NDR</span>
                            <span class="stat-value">{{ $orderCount }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- RTO Card -->
            <div class="col-6 col-md-2 fade-in fade-in-delay-4">
                <div class="stat-card h-100">
                    <div class="d-flex align-items-center mb-1 gap-2">
                        <div class="stat-icon"
                             style="background-color: rgba(79, 70, 229, 0.1); color: var(--primary-color);">
                             <img src="{{ asset('assets/website/img/rto.png') }}" alt="RTO" style="height:35px; width:26px; display:block; margin:auto;">
                        </div>
                        <div>
                            <span class="stat-title">RTO</span>
                            @if($sellerId === 14)
                                <span class="stat-value">3020</span>
                            @else
                                <span class="stat-value">{{ $Allorderrto }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

      

        <!-- Charts Row -->
        <div class="row g-3 g-md-4 mb-4 dashboard-charts-row">
            <!-- Total Orders Quantity (Column Chart) -->
            <div class="col-12 col-lg-7 fade-in dashboard-chart-card">
                <div class="modern-card h-100">
                    <div class="card-body p-3 p-md-4 d-flex flex-column h-100">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 mb-sm-4 gap-2">
                            <div>
                                <h5 class="fw-bold mb-1 fs-6 fs-md-5">Total Orders Quantity</h5>
                                <p class="text-muted mb-0 small">Column chart of your total orders</p>
                            </div>
                            <div>
                                <select id="ordersRange" class="form-select form-select-sm border-0" style="background-color: var(--bs-gray-100); width: auto; min-width:110px;">
                                    <!-- <option value="7">Last 7 days</option>
                                    <option value="15">Last 15 days</option> -->
                                    <option value="30">Last 30 days</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex-grow-1 d-flex align-items-center chart-canvas-container orders-canvas-container" style="position:relative;">
                            <canvas id="ordersColumnChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
           
            <!-- Sales Distribution Chart -->
            <div class="col-12 col-lg-5 fade-in dashboard-chart-card">
                <div class="modern-card h-100 prepaid-cod-chart-card">
                    <div class="card-body p-3 p-md-4 d-flex flex-column h-100">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 mb-sm-4 gap-2">
                            <div>
                                <h5 class="fw-bold mb-1 fs-6 fs-md-5">Prepaid and COD Ratio</h5>
                                <p class="text-muted mb-0 small">Revenue breakdown by courier partners</p>
                            </div>
                            <div>
                                <button class="btn btn-sm" style=" border: none;">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                            </div>
                        </div>
                        <!-- Responsive flex row: chart on left, stats on right -->
                        <div class="d-flex flex-column flex-md-row align-items-stretch gap-3 gap-md-4 w-100 mb-2 prepaid-cod-flex-row">
                            <!-- Chart -->
                            <div class="prepaid-cod-canvas-container" style="position: relative; flex: 1 1 60%; display: flex; justify-content: center; align-items: center;">
                                <canvas id="prepaidCodPieChart" width="0px;"></canvas>
                            </div>
                            <!-- Stats -->
                            <div class="d-flex flex-row flex-md-column flex-wrap align-items-center align-items-md-start justify-content-between justify-content-md-start gap-2 flex-md-column prepaid-cod-breakup-stat" style="flex: 1 1 40%; min-width: 130px;">
                                <div class="p-1 rounded-3 w-100" style="background-color: #f5f5f5; min-width: 110px;">
                                    <div class="d-flex justify-content-between align-items-center" style="padding:10px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="d-inline-block" style="width: 8px; height: 8px; background: #4ADE80; border-radius: 50%;"></span>
                                            <span class="small fw-medium">COD</span>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-success" id="codPercent">0%</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-1 rounded-3 w-100" style="background-color: #f5f5f5; min-width: 110px;">
                                    <div class="d-flex justify-content-between align-items-center" style="padding:10px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="d-inline-block" style="width: 8px; height: 8px; background: #FEF9C3; border-radius: 50%;"></span>
                                            <span class="small fw-medium">Prepaid</span>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-success" id="prepaidPercent">0%</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> 
                    </div>
                </div>
            </div>


<script>
let ordersChart = null;
let pieChart = null;

function loadDashboard(days = 7) {
    fetch(`/dashboard-data`)
        .then(res => res.json())
        .then(data => {

            console.log("API DATA:", data); // 🔥 check in console

            // 🔹 Orders Chart
            if (ordersChart) {
                ordersChart.destroy();
            }

            const ctx1 = document.getElementById('ordersColumnChart');

            if (!ctx1) return;

            ordersChart = new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Total Orders',
                        data: data.orders,
                        backgroundColor: 'rgba(79, 70, 229, 0.7)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // 🔹 Pie Chart
            if (pieChart) {
                pieChart.destroy();
            }

            const ctx2 = document.getElementById('prepaidCodPieChart');

            if (!ctx2) return;

            pieChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['COD', 'Prepaid'],
                    datasets: [{
                        data: [data.cod, data.prepaid],
                        backgroundColor: ['#4ADE80', '#FEF9C3']
                    }]
                }
            });

            // 🔹 Percentage update
            const total = data.cod + data.prepaid;

            document.getElementById('codPercent').innerText =
                total ? ((data.cod / total) * 100).toFixed(1) + '%' : '0%';

            document.getElementById('prepaidPercent').innerText =
                total ? ((data.prepaid / total) * 100).toFixed(1) + '%' : '0%';
        })
        .catch(err => console.error("ERROR:", err));
}

// 🔥 Page load
document.addEventListener("DOMContentLoaded", function () {
    loadDashboard(7);

    document.getElementById('ordersRange').addEventListener('change', function () {
        loadDashboard(this.value);
    });
});
</script>

            <!-- Scripts go at the end to not break HTML structure on responsive stacking -->
            <!-- <script>
                const ordersData = {
                    days: ['1 Feb', '2 Feb', '3 Feb', '4 Feb', '5 Feb', '6 Feb', '7 Feb'],
                    totalOrders: [18, 26, 12, 24, 30, 27, 21],
                };

                function getOptions(labels) {
                    let chartFontSize =  window.innerWidth < 576 ? 10 : (window.innerWidth < 1200 ? 12 : 13);
                    let barPercentage = window.innerWidth < 1200 ? 0.65 : 0.80;
                    return {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            x: { 
                                stacked: false,
                                ticks: { font: { size: chartFontSize } },
                                barPercentage: barPercentage,
                                categoryPercentage: barPercentage
                            },
                            y: { 
                                beginAtZero: true,
                                stacked: false,
                                ticks: { precision:0, font: { size: chartFontSize } }
                            }
                        }
                    };
                }

                function renderOrdersColumn(days, orders) {
                    if(window.ordersColumnChartInstance) window.ordersColumnChartInstance.destroy();
                    const ctx = document.getElementById('ordersColumnChart').getContext('2d');
                    window.ordersColumnChartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: days,
                            datasets: [
                                {
                                    label: 'Total Orders',
                                    data: orders,
                                    backgroundColor: 'rgba(79, 70, 229, 0.7)'
                                }
                            ]
                        },
                        options: getOptions(days)
                    });
                }

                function updateChartResponsive() {
                    let range = document.getElementById('ordersRange').value;
                    let days, orders;
                    if(range == 7) {
                        days = ordersData.days;
                        orders = ordersData.totalOrders;
                    } else if(range == 15) {
                        days = Array.from({length: 15}, (_, i) => `Feb ${i+1}`);
                        orders = Array(15).fill().map(() => Math.floor(10 + Math.random() * 25));
                    } else {
                        days = Array.from({length: 30}, (_, i) => `Feb ${i+1}`);
                        orders = Array(30).fill().map(() => Math.floor(7 + Math.random() * 35));
                    }
                    renderOrdersColumn(days, orders);
                }

                document.addEventListener('DOMContentLoaded', function() {
                    renderOrdersColumn(ordersData.days, ordersData.totalOrders);
                    document.getElementById('ordersRange').addEventListener('change', updateChartResponsive);
                    
                    let resizeTimeout;
                    window.addEventListener('resize', function() {
                        clearTimeout(resizeTimeout);
                        resizeTimeout = setTimeout(updateChartResponsive, 200);
                    });

                    // Pie Chart
                    const ctx = document.getElementById('prepaidCodPieChart');
                    if (ctx) {
                        const data = {!! json_encode($prepaidCodData ?? [60, 40]) !!};
                        window.prepaidCodChartInstance = new Chart(ctx.getContext('2d'), {
                            type: 'pie',
                            data: {
                                labels: ['Prepaid', 'COD'],
                                datasets: [{
                                    data: data,
                                    backgroundColor: [
                                        '#4ADE80',
                                        '#FEF9C3'
                                    ],
                                    borderWidth: 0
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'bottom',
                                        labels: {
                                            color: '#374151',
                                            font: {
                                                size: window.innerWidth < 576 ? 12 : 13,
                                                weight: 'bold'
                                            }
                                        }
                                    },
                                    tooltip: {
                                        enabled: true,
                                        callbacks: {
                                            label: function(context) {
                                                const label = context.label || '';
                                                const value = context.parsed || 0;
                                                const total = context.dataset.data.reduce((a, b) => a + b, 0) || 1;
                                                const pct = ((value / total) * 100).toFixed(1);
                                                return label + ': ' + value + ' (' + pct + '%)';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Responsive update for pie chart labels and size
                    window.addEventListener('resize', function () {
                        if (window.prepaidCodChartInstance) {
                            window.prepaidCodChartInstance.options.plugins.legend.labels.font.size = window.innerWidth < 576 ? 12 : 13;
                            window.prepaidCodChartInstance.update();
                        }
                    });
                });
            </script> -->
            <!-- Make sure to include Chart.js library if not yet included -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        </div>
        <style>
        /* Responsive tweaks for chart/cards and row flexing */
        .dashboard-charts-row {
            display: flex;
            flex-wrap: wrap;
        }
        .dashboard-charts-row .dashboard-chart-card {
            display: flex;
            flex-direction: column;
        }

        @media (min-width: 992px) { /* Laptop and up: single row */
            .dashboard-charts-row {
                flex-wrap: nowrap;
            }
            .dashboard-charts-row .col-lg-7 {
                max-width: 60%;
                flex: 0 0 60%;
            }
            .dashboard-charts-row .col-lg-5 {
                max-width: 40%;
                flex: 0 0 40%;
            }
            .prepaid-cod-flex-row {
                flex-direction: row !important;
                align-items: stretch !important;
            }
            .prepaid-cod-canvas-container {
                min-width: 0;
                max-width: 62%;
                flex: 2 1 62%;
            }
            .prepaid-cod-breakup-stat {
                max-width: 38%;
                min-width: 140px;
                flex: 1 1 38%;
            }
        }
        @media (max-width: 991.98px) {
            .dashboard-charts-row {
                flex-wrap: wrap;
            }
            .dashboard-charts-row .col-lg-7,
            .dashboard-charts-row .col-lg-5 {
                max-width: 100%;
                flex: 0 0 100%;
            }
            .prepaid-cod-flex-row {
                flex-direction: row !important;
                align-items: stretch !important;
            }
            .prepaid-cod-breakup-stat {
                max-width: 48%;
                min-width: 120px;
                flex: 1 1 48%;
            }
        }
        @media (max-width: 767.98px) {
            .prepaid-cod-flex-row {
                flex-direction: column !important;
                gap: 0.8rem !important;
            }
            .prepaid-cod-breakup-stat {
                max-width: 100%;
                flex-direction: row !important;
                flex: none !important;
                min-width: unset;
            }
            .prepaid-cod-canvas-container {
                max-width: 100%;
                margin-bottom: 0.6rem;
            }
        }
        @media (max-width: 575.98px) {
            .modern-card .card-body { padding: .55rem !important; }
            .modern-card h5,
            .modern-card .fw-bold { font-size: .97rem !important; }
            .modern-card p { font-size: .93rem !important; }
            .prepaid-cod-flex-row {
                flex-direction: column !important;
                gap: 0.7rem !important;
            }
            .prepaid-cod-breakup-stat {
                max-width: 100%;
                flex-direction: row !important;
                flex: none !important;
            }
            .prepaid-cod-canvas-container {
                max-width: 100%;
                margin-bottom: 0.55rem;
            }
        }
        /* Chart canvas sizing */
        .chart-canvas-container {
            width: 100%;
            max-width: 100%;
        }
        /* COD/prepaid section base sizing */
        .prepaid-cod-canvas-container {
            min-height: 75px;
            height: 120px;
        }
        #prepaidCodPieChart {
            /* width: 100% !important;
            max-width: 100%; */
            /* height: 150px !important; */
            /* min-height: 60px !important; */
            display: block;
            border-radius: 12px;
        }
        @media (max-width: 991.98px) {
            .prepaid-cod-canvas-container {
                height: 125px !important;
                min-height: 88px !important;
            }
            #prepaidCodPieChart {
                height: 125px !important;
                min-height: 88px !important;
            }
        }
        @media (max-width: 767.98px) {
            /* Pie chart slightly bigger */
            .prepaid-cod-canvas-container {
                height: 145px !important;
                min-height: 120px !important;
            }
            #prepaidCodPieChart {
                height: 145px !important;
                min-height: 120px !important;
                width: 100% !important;
            }
        }
        @media (max-width: 575.98px) {
            .prepaid-cod-canvas-container {
                height: 170px !important;
                min-height: 135px !important;
                margin-bottom: .4rem !important;
            }
            #prepaidCodPieChart {
                height: 170px !important;
                min-height: 135px !important;
                width: 100% !important;
                border-radius: 20px !important;
                /* background: linear-gradient(120deg, #fffbe6 70%, #f1ffd6 100%); */
                box-shadow: 0 8px 16px 0 rgba(32,55,112,0.08);
                margin: 0 auto;
            }
            .prepaid-cod-chart-card .card-body {
                padding-top: 0.3rem !important;
                padding-bottom: 0.3rem !important;
                min-height: unset !important;
            }
        }
        @media (max-width: 500px) {
            .prepaid-cod-canvas-container {
                height: 150px !important;
                min-height: 120px !important;
            }
            #prepaidCodPieChart {
                height: 150px !important;
                min-height: 120px !important;
                width: 99% !important;
            }
        }
        </style>
        <style>
            /* Responsive flex layout for dashboard-equal-height section */
            .dashboard-equal-height {
                display: flex;
                flex-direction: column;
            }
            .dashboard-equal-height > [class^="col-"] {
                display: flex;
                flex-direction: column;
                margin-bottom: 1rem;
            }
            .dashboard-equal-card {
                height: 100%;
                display: flex;
                flex-direction: column;
            }
            .dashboard-equal-card .card-body, 
            .dashboard-equal-height .card-body {
                flex: 1 1 auto;
                display: flex;
                flex-direction: column;
            }

            /* On md and up, use row layout */
            @media (min-width: 768px) {
                .dashboard-equal-height {
                    flex-direction: row;
                }
                .dashboard-equal-height > [class^="col-"] {
                    margin-bottom: 0 !important;
                }
            }
            
            /* On small screens, make cards full width, stack vertically */
            @media (max-width: 991.98px) {
                .dashboard-equal-height > [class^="col-"] {
                    flex: 1 1 100%;
                    max-width: 100%;
                }
            }
        </style>
        <div class="row mb-2 dashboard-equal-height">
            <div class="col-12 col-md-6 fade-in d-flex flex-column">
                <div class="card shadow-sm border-0 dashboard-equal-card" style="border-radius: 20px; flex:1;">
                    <div class="card-body" style="background: linear-gradient(135deg, #fff, #f8fafc 90%); border-radius: 20px; flex: 1; display:flex; flex-direction:column;">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-2">
                            <div class="mb-2 mb-md-0">
                                <h6 class="fw-bold mb-1" style="color: #064e3b;">Revenue Overview</h6>
                                <span class="text-muted small">Total Revenue &nbsp;|&nbsp; <b id="totalRevenue">₹0</b></span>
                                
                            </div>
                            <div>
                                <select id="revenueRangeDropdown" class="form-select form-select-sm" style="min-width: 130px; box-shadow:none; border-radius: 10px;">
                                    <!-- <option value="year">Last Year</option> -->
                                    <option value="month" selected>Last Month</option>
                                    <!-- <option value="week">Last Week</option> -->
                                </select>
                            </div>
                        </div>
                        <div style="margin:auto; flex:1;">
                            <canvas id="revenueLineChart" height="74"></canvas>
                        </div>
                    </div>
                </div>
                <!-- <script>
                    function getStaticRevenueData(range) {
                        // Simple static demo data for now:
                        if (range === 'year') {
                            return {
                                labels: [
                                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'
                                ],
                                data: [54000, 57000, 59000, 68000, 72600, 77200, 70000, 62000, 81000, 85000, 89500, 82500]
                            };
                        } else if (range === 'week') {
                            return {
                                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                                data: [8900, 11500, 10000, 8400, 12000, 10900, 11600]
                            };
                        } // Default 'month'
                        return {
                            labels: [
                                '01', '05', '09', '13', '17', '21', '25', '29'
                            ],
                            data: [12100, 15800, 13900, 16400, 17700, 18200, 20500, 19700]
                        };
                    }

                    function renderRevenueChart(range) {
                        const ctx = document.getElementById('revenueLineChart').getContext('2d');
                        const { labels, data } = getStaticRevenueData(range);

                        if (window.revenueLineChartInstance) {
                            window.revenueLineChartInstance.data.labels = labels;
                            window.revenueLineChartInstance.data.datasets[0].data = data;
                            window.revenueLineChartInstance.update();
                            return;
                        }

                        window.revenueLineChartInstance = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Revenue',
                                    data: data,
                                    borderColor: '#22c55e',
                                    backgroundColor: 'rgba(34,197,94,0.12)',
                                    borderWidth: 3,
                                    pointBackgroundColor: '#22c55e',
                                    pointBorderColor: "#fff",
                                    pointHoverRadius: 7,
                                    pointRadius: 6,
                                    tension: 0.38,
                                    fill: true,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: "#fff",
                                        borderColor: "#22c55e",
                                        borderWidth: 1,
                                        titleColor: "#16a34a",
                                        bodyColor: "#334155",
                                        padding: 13,
                                        bodyFont: {weight: 600},
                                        callbacks: {
                                            label: function(context) {
                                                return `₹${context.parsed.y.toLocaleString()}`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        ticks: {
                                            color: "#7c8694",
                                            font: {weight:'bold'}
                                        },
                                        grid: {
                                            display: false,
                                            drawBorder: false
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            color: "#7c8694",
                                            font: {weight:'bold'},
                                            callback: function(value) {
                                                return '₹' + value/1000 + 'k';
                                            }
                                        },
                                        grid: {
                                            color: "rgba(34,197,94,0.07)"
                                        }
                                    }
                                }
                            }
                        });
                    }

                    document.addEventListener("DOMContentLoaded", function () {
                        // Inject Chart.js if not loaded
                        function ensureChartJs(cb) {
                            if (typeof window.Chart === "undefined") {
                                var script = document.createElement('script');
                                script.src = "https://cdn.jsdelivr.net/npm/chart.js";
                                script.onload = cb;
                                document.head.appendChild(script);
                            } else {
                                cb();
                            }
                        }

                        ensureChartJs(function() {
                            renderRevenueChart(document.getElementById('revenueRangeDropdown').value);
                        });

                        document.getElementById('revenueRangeDropdown').addEventListener('change', function() {
                            renderRevenueChart(this.value);
                        });
                    });
                </script> -->
            </div>
            <div class="col-12 col-md-3 d-flex flex-column">
                <!-- Zone Delivery Performance Section -->
                <div class="modern-card mb-4 p-4 dashboard-equal-card" style="flex:1;">
                    <div class="card-body" style="flex:1; display:flex; flex-direction:column;">
                        <h6 class="mb-3 fw-bold">Zone-wise Delivered Orders</h6>
                        @php
                            // Use soft, light color shades for each zone
                            $zones = [
                                [
                                    'zone' => 'A',
                                    'delivered' => $zoneStats['Local']['delivered'] ?? 0,
                                    'total' => $zoneStats['Local']['total'] ?? 0,
                                    'badge' => '#e0e7ff',     // Indigo-50
                                    'progress' => '#a5b4fc',  // Indigo-300
                                    'text' => '#3730a3'       // Indigo-800
                                ],
                                [
                                    'zone' => 'B',
                                    'delivered' => $zoneStats['Zonal']['delivered'] ?? 0,
                                    'total' => $zoneStats['Zonal']['total'] ?? 0,
                                    'badge' => '#d1fae5',     // Emerald-100
                                    'progress' => '#6ee7b7',  // Emerald-300
                                    'text' => '#065f46'       // Emerald-800
                                ],
                                [
                                    'zone' => 'C',
                                    'delivered' => $zoneStats['National']['delivered'] ?? 0,
                                    'total' => $zoneStats['National']['total'] ?? 0,
                                    'badge' => '#fef9c3',     // Amber-100
                                    'progress' => '#fde68a',  // Amber-300
                                    'text' => '#92400e'       // Amber-800
                                ],
                                [
                                    'zone' => 'D',
                                    'delivered' => $zoneStats['Remote']['delivered'] ?? 0,
                                    'total' => $zoneStats['Remote']['total'] ?? 0,
                                    'badge' => '#fee2e2',     // Red-100
                                    'progress' => '#fca5a5',  // Red-300
                                    'text' => '#991b1b'       // Red-800
                                ],
                            ];
                        @endphp

                        <div style="flex:1;">

    <!-- A Zone -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="fw-semibold" style="color:#3730a3">A Zone</span>
        <span id="zone-A" class="badge badge-modern" style="background:#e0e7ff;color:#3730a3;">
            0%
        </span>
    </div>
    <div class="modern-progress mb-3" style="background:#e0e7ff;">
        <div id="zone-A-bar" class="modern-progress-bar" style="width:0%; background:#a5b4fc;"></div>
    </div>

    <!-- B Zone -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="fw-semibold" style="color:#065f46">B Zone</span>
        <span id="zone-B" class="badge badge-modern" style="background:#d1fae5;color:#065f46;">
            0%
        </span>
    </div>
    <div class="modern-progress mb-3" style="background:#d1fae5;">
        <div id="zone-B-bar" class="modern-progress-bar" style="width:0%; background:#6ee7b7;"></div>
    </div>

    <!-- C Zone -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="fw-semibold" style="color:#92400e">C Zone</span>
        <span id="zone-C" class="badge badge-modern" style="background:#fef9c3;color:#92400e;">
            0%
        </span>
    </div>
    <div class="modern-progress mb-3" style="background:#fef9c3;">
        <div id="zone-C-bar" class="modern-progress-bar" style="width:0%; background:#fde68a;"></div>
    </div>

    <!-- D Zone -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="fw-semibold" style="color:#991b1b">D Zone</span>
        <span id="zone-D" class="badge badge-modern" style="background:#fee2e2;color:#991b1b;">
            0%
        </span>
    </div>
    <div class="modern-progress mb-3" style="background:#fee2e2;">
        <div id="zone-D-bar" class="modern-progress-bar" style="width:0%; background:#fca5a5;"></div>
    </div>

</div>
                        <!-- <div style="flex:1;">
                        @foreach($zones as $zone)
                            @php
                                $percent = $zone['total'] > 0 ? round(($zone['delivered']/$zone['total'])*100,1) : 0;
                            @endphp
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold" style="color: {{ $zone['text'] }}">{{ $zone['zone'] }} Zone</span>
                                <span class="badge badge-modern" style="background:{{ $zone['badge'] }};color:{{ $zone['text'] }};">
                                    {{ $percent }}%
                                </span>
                            </div>
                            <div class="modern-progress mb-3" style="background:{{ $zone['badge'] }};">
                                <div class="modern-progress-bar" style="width: {{ $percent }}%; background: {{ $zone['progress'] }};"></div>
                            </div>
                        @endforeach
                        </div> -->
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3 d-flex flex-column">
                <!-- Courier Priority Section: Premium UI/UX -->
                <div class="modern-card shadow-sm border-0 mb-4 p-4 dashboard-equal-card" style="border-radius: 20px; background: linear-gradient(135deg, #f4f7fa, #e0efff 95%); flex:1;">
                    <div class="card-body" style="border-radius: 20px; flex: 1; display: flex; flex-direction:column;">
                        <div class="d-flex align-items-center mb-3">
                            <i class="ti ti-bolt text-warning" style="font-size: 1.7rem; margin-right: 12px;"></i>
                            <div>
                                <h6 class="fw-bold mb-0" style="color:#1e293b;">Courier Priority</h6>
                                <small class="text-muted">Preferred shipping partners</small>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-0 mt-3" style="flex:1;" id="courierList">
                            <li class="d-flex align-items-center mb-3">
                                <div class="flex-fill">
                                    <span class="fw-semibold" style="color:#0f172a;">Bluedart</span>
                                </div>
                                <span class="text-success fw-medium ms-auto" style="font-size:13px;">99.2%</span>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <div class="flex-fill">
                                    <span class="fw-semibold" style="color:#14532d;">Delhivery</span>
                                </div>
                                <span class="text-success fw-medium ms-auto" style="font-size:13px;">97.5%</span>
                            </li>
                            <li class="d-flex align-items-center mb-1">
                                <div class="flex-fill">
                                    <span class="fw-semibold" style="color:#7c2d12;">DTDC</span>
                                </div>
                                <span class="text-success fw-medium ms-auto" style="font-size:13px;">93.8%</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>


        <script>
let revenueChart = null;

function loadRevenueSection(range = 'month') {

    console.log("CALLING API...");

    fetch(`/revenue-dashboard-data`)
        .then(res => res.json())
        .then(data => {

            console.log("API DATA:", data);

            // 🔹 Chart update
            if (revenueChart) revenueChart.destroy();

            const ctx = document.getElementById('revenueLineChart');

            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data.data,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34,197,94,0.12)',
                        fill: true
                    }]
                }
            });


                let total = data.data.reduce((sum, val) => sum + val, 0);

    let totalEl = document.getElementById('totalRevenue');
    if (totalEl) {
        totalEl.innerText = '₹' + total.toLocaleString();
    }
            // 🔹 Zones update
            ['A','B','C','D'].forEach(zone => {
                let val = data.zones[zone] ?? 0;

                let el = document.getElementById(`zone-${zone}`);
                let bar = document.getElementById(`zone-${zone}-bar`);

                if (el) el.innerText = val + '%';
                if (bar) bar.style.width = val + '%';
            });

            // 🔹 Courier update
            let list = document.getElementById('courierList');
            if (list) {
                list.innerHTML = '';

                data.couriers.forEach(c => {
                    list.innerHTML += `
                        <li class="d-flex align-items-center mb-2">
                            <span>${c.name}</span>
                            <span class="ms-auto text-success">${c.percent}%</span>
                        </li>
                    `;
                });
            }

        });
}

// Init
document.addEventListener("DOMContentLoaded", function () {
    loadRevenueSection('month');

    document.getElementById('revenueRangeDropdown')
        .addEventListener('change', function () {
            loadRevenueSection(this.value);
        });
        
});
</script>
<!--         
<script>
let revenueChart = null;

function loadRevenueSection(range = 'month') {

    fetch(`/api/revenue-dashboard-data?range=${range}`)
        .then(res => res.json())
        .then(data => {

            console.log("API DATA:", data);

            // 🔹 Chart Update
            if (revenueChart) revenueChart.destroy();

            const ctx = document.getElementById('revenueLineChart');

            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data.data,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34,197,94,0.12)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // 🔹 Zones Update (IMPORTANT)
            ['A','B','C','D'].forEach(zone => {
                let percent = data.zones[zone] ?? 0;

                let el = document.getElementById(`zone-${zone}`);
                if (el) el.innerText = percent + '%';
            });

            // 🔹 Courier Update
            const list = document.getElementById('courierList');
            if (list) {
                list.innerHTML = '';

                data.couriers.forEach(c => {
                    list.innerHTML += `
                        <li class="d-flex align-items-center mb-2">
                            <span class="fw-semibold">${c.name}</span>
                            <span class="text-success ms-auto">${c.percent}%</span>
                        </li>
                    `;
                });
            }

        })
        .catch(err => console.error(err));
}

// Init
document.addEventListener("DOMContentLoaded", function () {

    loadRevenueSection('month');

    document.getElementById('revenueRangeDropdown')
        .addEventListener('change', function () {
            loadRevenueSection(this.value);
        });
});
</script> -->
<!-- 
<script>
let revenueChartInstance = null;

function loadRevenueSection(range = 'month') {
    fetch(`/api/revenue-dashboard-data?range=${range}`)
        .then(res => res.json())
        .then(data => {

            console.log("Revenue Section:", data);

            // 🔹 Chart Update
            if (revenueChartInstance) {
                revenueChartInstance.destroy();
            }

            const ctx = document.getElementById('revenueLineChart');

            revenueChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data.data,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34,197,94,0.12)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // 🔹 Zone Update
            ['A','B','C','D'].forEach(zone => {
                const el = document.getElementById('zone-' + zone);
                if (el) {
                    el.innerText = data.zones[zone] ?? 0;
                }
            });

            // 🔹 Courier Update
            const list = document.getElementById('courierList');

            if (list) {
                list.innerHTML = '';

                data.couriers.forEach(c => {
                    list.innerHTML += `
                        <li class="d-flex align-items-center mb-2">
                            <span class="fw-semibold">${c.name}</span>
                            <span class="text-success ms-auto">${c.percent}%</span>
                        </li>
                    `;
                });
            }
        });
}

// 🔥 Init
document.addEventListener("DOMContentLoaded", function () {

    loadRevenueSection('month');

    document.getElementById('revenueRangeDropdown')
        .addEventListener('change', function () {
            loadRevenueSection(this.value);
        });
});
</script>
 -->





        <!-- Data Tables Section -->
        <!-- <div class="row mb-4"> -->
            <!-- <div class="col-12 fade-in">
                <div class="modern-card"> -->
                    <!-- <div class="card-body p-0">

                        <ul class="nav modern-tabs mb-0" id="dashboardTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="modern-tab active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions-pane" type="button" role="tab">
                                    <i class="ti ti-credit-card me-2"></i>Transaction History
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="modern-tab" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders-pane" type="button" role="tab">
                                    <i class="ti ti-package me-2"></i>Recent Orders
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="modern-tab" id="rto-tab" data-bs-toggle="tab" data-bs-target="#rto-pane" type="button" role="tab">
                                    <i class="ti ti-truck-return me-2"></i>RTO Orders
                                    @if($rtoOrders->count() > 0)
                                        <span class="badge text-bg-danger ms-2">{{ $rtoOrders->count() }}</span>
                                    @endif
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content p-4" id="dashboardTabContent">
                                        
                                        <div class="tab-pane fade show active" id="transactions-pane" role="tabpanel">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">Your Recent Transactions</h6>
                                            </div>
                                    @if ($transactions->isEmpty())
                                        <div class="text-center py-5">
                                            <div class="mb-3">
                                                <i class="ti ti-database-off" style="font-size: 3rem; color: #ccc;"></i>
                                            </div>
                                            <h6 class="text-muted">No Transaction Data Available</h6>
                                            <p class="text-muted small mb-0">Start making transactions to see data here</p>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="fw-bold">#</th>
                                                        <th class="fw-bold">Amount</th>
                                                        <th class="fw-bold">Date</th>
                                                        <th class="fw-bold">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($transactions as $key => $transaction)
                                                        <tr>
                                                            <td class="fw-semibold">{{ $key + 1 }}</td>
                                                            <td>
                                                                <span class="badge bg-success-subtle text-success fw-semibold px-3 py-2">
                                                                    {{ $transaction->amount ? '₹' . number_format($transaction->amount, 2) : 'N/A' }}
                                                                </span>
                                                            </td>
                                                            <td class="text-muted">
                                                                {{ $transaction->created_at ? $transaction->created_at->format('d M Y, h:i A') : 'N/A' }}
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                                    data-bs-target="#rechargeModal{{ $transaction->id }}">
                                                                    <i class="ti ti-eye me-1"></i> View
                                                                </button>

                                                                <div class="modal fade"
                                                                    id="rechargeModal{{ $transaction->id }}" tabindex="-1"
                                                                    aria-labelledby="rechargeModalLabel{{ $transaction->id }}"
                                                                    aria-hidden="true">
                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                        <div class="modal-content border-0 shadow">
                                                                            <div class="modal-header border-0">
                                                                                <h5 class="modal-title fw-bold"
                                                                                    id="rechargeModalLabel{{ $transaction->id }}">
                                                                                    <i class="ti ti-receipt me-2"></i>Transaction Details
                                                                                </h5>
                                                                                <button type="button" class="btn-close"
                                                                                    data-bs-dismiss="modal"
                                                                                    aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <div class="row">
                                                                                    <div class="col-6">
                                                                                        <p class="mb-2"><strong>Amount:</strong></p>
                                                                                        <span class="badge bg-success fs-6">
                                                                                            {{ $transaction->amount ? '₹' . number_format($transaction->amount, 2) : 'N/A' }}
                                                                                        </span>
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <p class="mb-2"><strong>Date:</strong></p>
                                                                                        <p class="text-muted mb-0">
                                                                                            {{ $transaction->created_at ? $transaction->created_at->format('d M Y, h:i A') : 'N/A' }}
                                                                                        </p>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                               
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                        </div>
                                        
                                        <div class="tab-pane fade" id="orders-pane" role="tabpanel">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">Your Recent Orders</h6>
                                            </div>
                                    @if ($latestOrders->isEmpty())
                                        <div class="text-center py-5">
                                            <div class="mb-3">
                                                <i class="ti ti-package-off" style="font-size: 3rem; color: #ccc;"></i>
                                            </div>
                                            <h6 class="text-muted">No Orders Found</h6>
                                            <p class="text-muted small mb-0">New orders will appear here</p>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="fw-bold">#</th>
                                                        <th class="fw-bold">Order Number</th>
                                                        <th class="fw-bold">AWB Number</th>
                                                        <th class="fw-bold">Courier ID</th>
                                                        <th class="fw-bold">Amount</th>
                                                        <th class="fw-bold">Customer</th>
                                                        <th class="fw-bold">Date</th>
                                                        <th class="fw-bold">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($latestOrders as $key => $order)
                                                        <tr>
                                                            <td class="fw-semibold">{{ $key + 1 }}</td>
                                                            <td>
                                                                <span class="badge bg-primary-subtle text-primary">
                                                                    {{ $order->order_number ?? 'N/A' }}
                                                                </span>
                                                            </td>
                                                            <td class="text-muted">{{ $order->awb_number ?? 'N/A' }}</td>
                                                            <td class="text-muted">{{ $order->all_courier_name ?? 'N/A' }}</td>
                                                            <td>
                                                                <span class="fw-semibold text-success">
                                                                    {{ $order->collectable_amount ? '₹' . number_format($order->collectable_amount, 2) : 'N/A' }}
                                                                </span>
                                                            </td>
                                                            <td class="fw-semibold">{{ $order->consignee['name'] ?? 'N/A' }}</td>
                                                            <td class="text-muted">{{ $order->created_at ? $order->created_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                                            <td>
                                                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                                    data-bs-target="#orderModal{{ $order->id }}">
                                                                    <i class="ti ti-eye me-1"></i> View
                                                                </button>

                                                                <div class="modal fade" id="orderModal{{ $order->id }}" tabindex="-1"
                                                                    aria-labelledby="orderModalLabel{{ $order->id }}" aria-hidden="true">
                                                                    <div class="modal-dialog modal-dialog-centered">
                                                                        <div class="modal-content border-0 shadow">
                                                                            <div class="modal-header border-0">
                                                                                <h5 class="modal-title fw-bold" id="orderModalLabel{{ $order->id }}">
                                                                                    <i class="ti ti-package me-2"></i>Order Details
                                                                                </h5>
                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                                    aria-label="Close"></button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <div class="row g-3">
                                                                                    <div class="col-6">
                                                                                        <p class="mb-1"><strong>Order Number:</strong></p>
                                                                                        <span class="badge bg-primary">{{ $order->order_number }}</span>
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <p class="mb-1"><strong>AWB Number:</strong></p>
                                                                                        <p class="mb-0 text-muted">{{ $order->awb_number }}</p>
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <p class="mb-1"><strong>Courier ID:</strong></p>
                                                                                        <p class="mb-0 text-muted">{{ $order->all_courier_name }}</p>
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <p class="mb-1"><strong>Amount:</strong></p>
                                                                                        <span class="badge bg-success fs-6">₹{{ number_format($order->collectable_amount, 2) }}</span>
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <p class="mb-1"><strong>Customer Name:</strong></p>
                                                                                        <p class="mb-0 fw-semibold">{{ $order->consignee['name'] ?? 'N/A' }}</p>
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <p class="mb-1"><strong>Date:</strong></p>
                                                                                        <p class="mb-0 text-muted">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                               
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                        </div>
                                      
                                        <div class="tab-pane fade" id="rto-pane" role="tabpanel">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">RTO Orders</h6>
                                                <div>
                                                    <span class="text-muted me-3">Total RTO Amount: <strong class="text-danger">₹{{ number_format($totalRtoAmount, 2) }}</strong></span>
                                                    <a href="{{ route('seller.rto.download-excel') }}" class="btn btn-success btn-sm">
                                                        <i class="ti ti-download me-1"></i>Download Excel
                                                    </a>
                                                </div>
                                            </div>
                                            
                                            @if($rtoOrders->count() > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Order Number</th>
                                                                <th>AWB Number</th>
                                                                <th>Customer</th>
                                                                <th>RTO Amount</th>
                                                                <th>Order Amount</th>
                                                                <th>Date</th>
                                                                <th>Courier</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($rtoOrders as $rtoOrder)
                                                                @php
                                                                    $consignee = is_array($rtoOrder->consignee) ? $rtoOrder->consignee : json_decode($rtoOrder->consignee, true);
                                                                @endphp
                                                                <tr>
                                                                    <td>
                                                                        <div class="fw-bold">{{ $rtoOrder->order_number ?? 'N/A' }}</div>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-warning">{{ $rtoOrder->awb_number ?? 'N/A' }}</span>
                                                                    </td>
                                                                    <td>
                                                                        <div>
                                                                            <div class="fw-semibold">{{ $consignee['name'] ?? 'N/A' }}</div>
                                                                            <small class="text-muted">{{ $consignee['phone'] ?? 'N/A' }}</small>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <span class="text-danger fw-bold">-₹{{ number_format($rtoOrder->seller_amount_walate ?? 0, 2) }}</span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="fw-semibold">₹{{ number_format($rtoOrder->order_amount ?? 0, 2) }}</span>
                                                                    </td>
                                                                    <td>
                                                                        <div>{{ $rtoOrder->created_at ? $rtoOrder->created_at->format('d M Y') : 'N/A' }}</div>
                                                                        <small class="text-muted">{{ $rtoOrder->created_at ? $rtoOrder->created_at->format('h:i A') : '' }}</small>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge bg-info">{{ $rtoOrder->courier_name ?? 'N/A' }}</span>
                                                                    </td>
                                                                    <td>
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#rtoModal{{ $rtoOrder->id }}">
                                                                            <i class="ti ti-eye"></i>
                                                                        </button>
                                                                        <div class="modal fade" id="rtoModal{{ $rtoOrder->id }}" tabindex="-1" aria-hidden="true">
                                                                            <div class="modal-dialog">
                                                                                <div class="modal-content">
                                                                                    <div class="modal-header">
                                                                                        <h5 class="modal-title">RTO Order Details</h5>
                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                                    </div>
                                                                                    <div class="modal-body">
                                                                                        <div class="row">
                                                                                            <div class="col-6">
                                                                                                <p class="mb-1"><strong>Order Number:</strong></p>
                                                                                                <p class="mb-0 fw-semibold">{{ $rtoOrder->order_number ?? 'N/A' }}</p>
                                                                                            </div>
                                                                                            <div class="col-6">
                                                                                                <p class="mb-1"><strong>AWB Number:</strong></p>
                                                                                                <p class="mb-0 fw-semibold">{{ $rtoOrder->awb_number ?? 'N/A' }}</p>
                                                                                            </div>
                                                                                        </div>
                                                                                        <hr>
                                                                                        <div class="row">
                                                                                            <div class="col-6">
                                                                                                <p class="mb-1"><strong>Customer:</strong></p>
                                                                                                <p class="mb-0 fw-semibold">{{ $consignee['name'] ?? 'N/A' }}</p>
                                                                                            </div>
                                                                                            <div class="col-6">
                                                                                                <p class="mb-1"><strong>Phone:</strong></p>
                                                                                                <p class="mb-0 text-muted">{{ $consignee['phone'] ?? 'N/A' }}</p>
                                                                                            </div>
                                                                                        </div>
                                                                                        <hr>
                                                                                        <div class="row">
                                                                                            <div class="col-6">
                                                                                                <p class="mb-1"><strong>RTO Amount Deducted:</strong></p>
                                                                                                <p class="mb-0 text-danger fw-bold">₹{{ number_format($rtoOrder->seller_amount_walate ?? 0, 2) }}</p>
                                                                                            </div>
                                                                                            <div class="col-6">
                                                                                                <p class="mb-1"><strong>Order Amount:</strong></p>
                                                                                                <p class="mb-0 fw-semibold">₹{{ number_format($rtoOrder->order_amount ?? 0, 2) }}</p>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="text-center py-5">
                                                    <i class="ti ti-package-off" style="font-size: 3rem; color: #ccc;"></i>
                                                    <h6 class="mt-3 text-muted">No RTO Orders Found</h6>
                                                    <p class="text-muted small">No orders have been returned to origin yet.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <!-- COD and NDR Overview Section -->
                    <div class="row g-4 mb-2">
                        <!-- Earned COD Overview -->
                        <div class="col-lg-6 fade-in">
                            <div class="modern-card h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <h5 class="fw-bold mb-1">
                                                <i class="ti ti-cash me-2 text-primary"></i>COD Overview
                                            </h5>
                                            <p class="text-muted mb-0 small">Last 30 days</p>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary px-3 py-2">
                                            <i class="ti ti-calendar me-1"></i> Monthly
                                        </span>
                                    </div>
                                    
                                    <div class="row g-4">
                                        <!-- COD amount earned -->
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3" style="background-color: var(--gray-100);">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon me-3" style="background-color: rgba(79, 70, 229, 0.1); color: var(--primary-color); width: 48px; height: 48px;">
                                                        <i class="ti ti-wallet"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0 fw-bold">₹0</h5>
                                                        <span class="text-muted small">COD Amount Earned</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Earned COD remitted -->
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3" style="background-color: var(--gray-100);">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon me-3" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981; width: 48px; height: 48px;">
                                                        <i class="ti ti-credit-card"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0 fw-bold">₹0</h5>
                                                        <span class="text-muted small">COD Remitted</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- COD amount available -->
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3" style="background-color: var(--gray-100);">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon me-3" style="background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; width: 48px; height: 48px;">
                                                        <i class="ti ti-wallet"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0 fw-bold">₹0</h5>
                                                        <span class="text-muted small">COD Available</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Last remitted amount -->
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3" style="background-color: var(--gray-100);">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon me-3" style="background-color: rgba(79, 70, 229, 0.1); color: var(--primary-color); width: 48px; height: 48px;">
                                                        <i class="ti ti-receipt"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0 fw-bold">₹0</h5>
                                                        <span class="text-muted small">Last Remitted</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Progress bar for COD completion -->
                                    <!-- <div class="mt-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small text-muted">COD Collection Rate</span>
                                            <span class="fw-bold">80%</span>
                                        </div>
                                        <div class="modern-progress">
                                            <div class="modern-progress-bar" style="width: 80%; background: linear-gradient(90deg, #f59e0b, #d97706);"></div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>

                        <!-- NDR Overview -->
                        <div class="col-lg-6 fade-in">
                            <div class="modern-card h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <h5 class="fw-bold mb-1">
                                                <i class="ti ti-alert-triangle me-2 text-danger"></i>NDR Overview
                                            </h5>
                                            <p class="text-muted mb-0 small">Last 30 days</p>
                                        </div>
                                        <span class="badge bg-danger-subtle text-danger px-3 py-2">
                                            <i class="ti ti-calendar me-1"></i> Monthly
                                        </span>
                                    </div>
                                    
                                    <div class="row g-4">
                                        <!-- Total NDR raised -->
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3" style="background-color: var(--gray-100);">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon me-3" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; width: 48px; height: 48px;">
                                                        <i class="ti ti-clipboard-list"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0 fw-bold">0</h5>
                                                        <span class="text-muted small">Total NDR Raised</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Taken -->
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3" style="background-color: var(--gray-100);">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon me-3" style="background-color: rgba(79, 70, 229, 0.1); color: var(--primary-color); width: 48px; height: 48px;">
                                                        <i class="ti ti-check-circle"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0 fw-bold">0</h5>
                                                        <span class="text-muted small">Action Taken</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Pending for action -->
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3" style="background-color: var(--gray-100);">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon me-3" style="background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; width: 48px; height: 48px;">
                                                        <i class="ti ti-clock"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0 fw-bold">0</h5>
                                                        <span class="text-muted small">Pending Action</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- NDR shipments delivered -->
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-3" style="background-color: var(--gray-100);">
                                                <div class="d-flex align-items-center">
                                                    <div class="stat-icon me-3" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981; width: 48px; height: 48px;">
                                                        <i class="ti ti-truck-delivery"></i>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0 fw-bold">0</h5>
                                                        <span class="text-muted small">NDR Delivered</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Progress bar for NDR resolution -->
                                    <!-- <div class="mt-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small text-muted">NDR Resolution Rate</span>
                                            <span class="fw-bold">{{ $Cancelled > 0 ? round((max(0, ($Cancelled ?? 0) - 2) / $Cancelled) * 100) : 0 }}%</span>
                                        </div>
                                        <div class="modern-progress">
                                            <div class="modern-progress-bar" style="width: {{ $Cancelled > 0 ? round((max(0, ($Cancelled ?? 0) - 2) / $Cancelled) * 100) : 0 }}%; background: linear-gradient(90deg, #10b981, #059669);"></div>
                                        </div>
                                    </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                <!-- </div>
            </div> -->
        <!-- </div> -->
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <script>
        // Global chart instances
        let trafficChart = null;
        let salesChart = null;

        // Ensure modal close functionality works properly
        $(document).ready(function() {
            // Handle modal close button clicks
            $('.btn-close').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var modal = $(this).closest('.modal');
                if (modal.length) {
                    var bsModal = bootstrap.Modal.getInstance(modal[0]);
                    if (bsModal) {
                        bsModal.hide();
                    } else {
                        modal.modal('hide');
                    }
                }
            });
            
            // Handle backdrop click to close modal
            $('.modal').on('click', function(e) {
                if (e.target === this) {
                    var bsModal = bootstrap.Modal.getInstance(this);
                    if (bsModal) {
                        bsModal.hide();
                    } else {
                        $(this).modal('hide');
                    }
                }
            });
            
            // Handle escape key to close modal
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // ESC key
                    $('.modal.show').each(function() {
                        var bsModal = bootstrap.Modal.getInstance(this);
                        if (bsModal) {
                            bsModal.hide();
                        }
                    });
                }
            });

            // Initialize daterangepicker if the element exists
            if ($('#daterangepicker').length) {
                try {
                    $('#daterangepicker').daterangepicker({
                        opens: 'left',
                        startDate: moment().subtract(29, 'days'),
                        endDate: moment(),
                        locale: {
                            format: 'YYYY-MM-DD'
                        }
                    });
                } catch(e) {
                    console.warn('Daterangepicker initialization failed:', e.message);
                }
            }

            // Initialize charts if canvas elements exist
            if (document.getElementById('trafficChart')) {
                initTrafficChart();
            }
            if (document.getElementById('salesChart')) {
                initSalesChart();
            }
        });

        // Safe function to handle box container changes
        function change_box_container(element) {
            try {
                if (element && element.classList) {
                    // Add your box container logic here if needed
                    console.log('Box container changed');
                }
            } catch(e) {
                console.warn('Box container change failed:', e.message);
            }
        }

        // Chart initialization functions
        function initTrafficChart() {
            try {
                const canvas = document.getElementById('trafficChart');
                if (!canvas) return;

                // Destroy existing chart if it exists
                if (trafficChart) {
                    trafficChart.destroy();
                    trafficChart = null;
                }

                // Get dynamic data from PHP
                const trafficLabels = {!! json_encode($trafficLabels ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']) !!};
                const newVisitors = {!! json_encode($newVisitorsData ?? [30, 40, 35, 50, 45, 60]) !!};
                const returningVisitors = {!! json_encode($returningVisitorsData ?? [20, 30, 25, 40, 35, 50]) !!};

                const ctx = canvas.getContext('2d');
                trafficChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: trafficLabels,
                        datasets: [{
                            label: 'New Visitors',
                            data: newVisitors,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Old Visitors',
                            data: returningVisitors,
                            borderColor: '#764ba2',
                            backgroundColor: 'rgba(118, 75, 162, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#e4e6ea'
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#e4e6ea'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#e4e6ea'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            }
                        }
                    }
                });
            } catch(e) {
                console.warn('Traffic chart initialization failed:', e.message);
            }
        }

        function initSalesChart() {
            try {
                const canvas = document.getElementById('salesChart');
                if (!canvas) return;

                // Destroy existing chart if it exists
                if (salesChart) {
                    salesChart.destroy();
                    salesChart = null;
                }

                // Get dynamic courier data from PHP
                const salesLabels = {!! json_encode($salesLabels ?? ['Direct', 'Affiliate', 'Email', 'Other']) !!};
                const salesData = {!! json_encode($salesValues ?? [45, 25, 15, 15]) !!};
                const salesColors = ['#032693', '#032893', '#FBBF24', '#EF4444', '#8B5CF6'];

                const ctx = canvas.getContext('2d');
                salesChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: salesLabels,
                        datasets: [{
                            data: salesData,
                            backgroundColor: salesColors.slice(0, salesData.length),
                            borderWidth: 2,
                            borderColor: 'rgba(255, 255, 255, 0.1)'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            } catch(e) {
                console.warn('Sales chart initialization failed:', e.message);
            }
        }

        // Handle page visibility change to prevent chart issues
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden, destroy charts to prevent issues
                if (trafficChart) {
                    trafficChart.destroy();
                    trafficChart = null;
                }
                if (salesChart) {
                    salesChart.destroy();
                    salesChart = null;
                }
            } else {
                // Page is visible, reinitialize charts
                setTimeout(function() {
                    if (document.getElementById('trafficChart') && !trafficChart) {
                        initTrafficChart();
                    }
                    if (document.getElementById('salesChart') && !salesChart) {
                        initSalesChart();
                    }
                }, 100);
            }
        });

        // Clean up charts before page unload
        window.addEventListener('beforeunload', function() {
            if (trafficChart) {
                trafficChart.destroy();
                trafficChart = null;
            }
            if (salesChart) {
                salesChart.destroy();
                salesChart = null;
            }
        });
    </script>

    <style>
        /* Dark Theme Base */
        /* body {
            background-color: #0a0e27 !important;
            color: #e4e6ea !important;
        } */
body {
    background-color: #F9FAFB !important; /* Soft white-gray */
    color: #1E1E1E !important; /* Dark gray for text */
}
        .pc-container, .pc-content {
            background-color: #f8f9fa !important;
        }

        .dashboard-main {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 50%, #e9ecef 100%) !important;
            min-height: 100vh;
            position: relative;
        }

        .dashboard-main::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 70%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container-fluid {
            position: relative;
            z-index: 1;
        }

        /* Stat Cards with Enhanced Light Theme */
        .stat-card {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.4s ease;
            /* border-radius: 20px; */
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(20px);
            /* background: var(--gray-100); */
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.8) !important;
        }

        .stat-card .card-body {
            position: relative;
            overflow: hidden;
        }

        .stat-card .card-body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: rotate(45deg);
        }

        /* COD and NDR Overview Cards */
        .card[style*="667eea"], .card[style*="f093fb"] {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card[style*="667eea"]:hover, .card[style*="f093fb"]:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 30px 60px rgba(0, 0, 0, 0.4),
                0 0 40px rgba(102, 126, 234, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .card[style*="f093fb"]:hover {
            box-shadow: 
                0 30px 60px rgba(0, 0, 0, 0.4),
                0 0 40px rgba(240, 147, 251, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        /* Stat Icon Small */
        .stat-icon-small {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-icon-small::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: rotate(45deg);
            transition: all 0.3s ease;
            opacity: 0;
        }

        .stat-icon-small:hover::before {
            opacity: 1;
            animation: shimmer 1s ease-in-out;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }

        /* Enhanced Progress Bars */
        .progress {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar {
            border-radius: 10px;
            position: relative;
            overflow: hidden;
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: progressShine 2s infinite;
        }

        @keyframes progressShine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Card Header Enhancements */
        .card-header.bg-transparent {
            position: relative;
            z-index: 2;
        }

        .card-header.bg-transparent::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 20px;
            right: 20px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        }

        /* Text Glow Effects */
        .card[style*="667eea"] h3, .card[style*="f093fb"] h3 {
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .card[style*="667eea"] .small, .card[style*="f093fb"] .small {
            font-weight: 500;
            letter-spacing: 0.5px;
        }
            transition: transform 0.6s;
            pointer-events: none;
        }

        .stat-card:hover .card-body::before {
            transform: rotate(45deg) translate(100%, 100%);
        }

        .stat-icon {
            opacity: 0.9;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }

        /* Cards */
        .card {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            border-radius: 20px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(20px);
            color: #374151 !important;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
            border-color: rgba(0, 0, 0, 0.15) !important;
        }

        .card-header {
            background: rgba(248, 249, 250, 0.8) !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
            color: #374151 !important;
        }

        .card-header h5 {
            color: #1f2937 !important;
        }

        .card-body {
            color: #374151 !important;
        }

        /* Tables */
        .table {
            background: transparent !important;
            color: #374151 !important;
        }

        .table th {
            background: rgba(248, 249, 250, 0.8) !important;
            border-bottom: 2px solid rgba(0, 0, 0, 0.1) !important;
            color: #1f2937 !important;
            font-weight: 600;
        }

        .table td {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
            color: #374151 !important;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(248, 249, 250, 0.5) !important;
        }

        .table-light {
            background: rgba(248, 249, 250, 0.8) !important;
        }

        /* Badges */
        .badge {
            font-size: 0.875rem;
            padding: 0.rem 1rem;
            border-radius: 10px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .badge.bg-primary, .badge.bg-primary-subtle {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
            color: white !important;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge.bg-success, .badge.bg-success-subtle {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            color: white !important;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge.bg-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            color: white !important;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge.bg-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
            color: white !important;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Buttons */
        .btn {
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
            border: none !important;
            color: white !important;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #3b82f6) !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-outline-primary {
            border: 2px solid #3b82f6 !important;
            color: #3b82f6 !important;
            background: transparent !important;
        }

        .btn-outline-primary:hover {
            background: #3b82f6 !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
            border: none !important;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            border: none !important;
        }

        .btn-info {
            background: linear-gradient(135deg, #06b6d4, #0891b2) !important;
            border: none !important;
        }

        /* Social Icons */
        .social-icon {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .social-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }

        .social-icon:hover::before {
            left: 100%;
        }

        .social-icon:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        /* Modals */
        .modal-content {
            background: rgba(255, 255, 255, 0.98) !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            border-radius: 20px;
            backdrop-filter: blur(20px);
            color: #374151 !important;
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
            color: #374151 !important;
        }

        .modal-header .modal-title {
            color: #1f2937 !important;
            font-weight: 600;
        }

        .modal-body {
            color: #374151 !important;
        }

        .modal-body * {
            color: #374151 !important;
        }

        .modal-body h4, .modal-body h5, .modal-body h6 {
            color: #1f2937 !important;
            font-weight: 600;
        }

        .modal-body p, .modal-body li, .modal-body span {
            color: #4b5563 !important;
        }

        .modal-body strong {
            color: #1f2937 !important;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.1) !important;
        }

        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5) !important;
        }

/* Enhanced Close Button Fix */
.btn-close {
    background: rgba(0, 0, 0, 0.1) !important;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    opacity: 1 !important;
    transition: all 0.3s ease;
    position: relative;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: #374151;
}

.btn-close:hover {
    background: rgba(0, 0, 0, 0.2) !important;
    transform: scale(1.1);
}

.btn-close::before, 
.btn-close::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 2px;
    background: currentColor;
}

.btn-close::before {
    transform: rotate(45deg);
}

.btn-close::after {
    transform: rotate(-45deg);
}

/* Remove default Bootstrap close button background image */
.btn-close {
    background-image: none !important;
}        /* Form Elements */
        .form-control {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(0, 0, 0, 0.15) !important;
            color: #374151 !important;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 1) !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
            color: #374151 !important;
        }

        .form-control::placeholder {
            color: rgba(107, 114, 128, 0.6) !important;
        }

        .form-label {
            color: #1f2937 !important;
            font-weight: 500;
        }

        .form-check-input {
            background-color: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid rgba(0, 0, 0, 0.2) !important;
        }

        .form-check-input:checked {
            background-color: #3b82f6 !important;
            border-color: #3b82f6 !important;
        }

        .form-check-label {
            color: #374151 !important;
        }

        /* Alerts */
        .alert {
            border: none !important;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .alert-warning {
            background: rgba(255, 193, 7, 0.2) !important;
            color: #ffc107 !important;
            border: 1px solid rgba(255, 193, 7, 0.3) !important;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2) !important;
            color: #28a745 !important;
            border: 1px solid rgba(40, 167, 69, 0.3) !important;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2) !important;
            color: #dc3545 !important;
            border: 1px solid rgba(220, 53, 69, 0.3) !important;
        }

        /* Custom scrollbar */
        .table-responsive::-webkit-scrollbar {
            height: 12px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, 0.9);
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
        }

        /* Global scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 6px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
        }

        /* Chart container styling */
        #trafficChart, #salesChart {
            max-height: 200px;
            filter: brightness(1.1) contrast(1.2);
        }

        /* Text Colors */
        .text-muted {
            color: rgba(107, 114, 128, 0.8) !important;
        }

        .text-success {
            color: #10b981 !important;
        }

        .text-danger {
            color: #ef4444 !important;
        }

        .text-warning {
            color: #f59e0b !important;
        }

        .text-info {
            color: #06b6d4 !important;
        }

        .text-primary {
            color: #3b82f6 !important;
        }

        /* Dropdown */
        .dropdown-menu {
            background: rgba(255, 255, 255, 0.98) !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            backdrop-filter: blur(20px);
            border-radius: 12px;
        }

        .dropdown-item {
            color: #374151 !important;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #3b82f6 !important;
        }

        /* KYC Profile Enhancement */
        .kyc-profile {
            margin-bottom: 2rem;
        }

        .kyc-profile .alert {
            margin-bottom: 0;
        }

        /* Enhanced Hover Effects */
        .card, .stat-card {
            position: relative;
            overflow: hidden;
        }

        .card::before, .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s;
            z-index: 1;
            pointer-events: none;
        }

        .card:hover::before, .stat-card:hover::before {
            left: 100%;
        }

        /* Loading Animation */
        @keyframes shimmer {
            0% {
                background-position: -468px 0;
            }
            100% {
                background-position: 468px 0;
            }
        }

        .loading-shimmer {
            animation: shimmer 2s infinite linear;
            background: linear-gradient(to right, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0.1) 100%);
            background-size: 800px 104px;
        }

        /* Responsive Enhancements */
        @media (max-width: 768px) {
            .stat-card:hover {
                transform: translateY(-4px) scale(1.01);
            }
            
            .card:hover {
                transform: translateY(-3px);
            }
        }

        /* Enhanced Focus States */
        .btn:focus {
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5) !important;
        }

        /* Bootstrap Close Button Fix */
        .btn-close {
            background: rgba(0, 0, 0, 0.1) !important;
            border: 1px solid rgba(0, 0, 0, 0.2) !important;
            border-radius: 50% !important;
            width: 32px !important;
            height: 32px !important;
            opacity: 1 !important;
            position: relative !important;
            transition: all 0.3s ease !important;
        }

        .btn-close::before {
            content: '×' !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            font-size: 18px !important;
            font-weight: bold !important;
            color: #374151 !important;
            line-height: 1 !important;
        }

        .btn-close:hover {
            background: rgba(0, 0, 0, 0.2) !important;
            border-color: rgba(0, 0, 0, 0.3) !important;
            transform: scale(1.1) !important;
        }

        .btn-close:focus {
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.5) !important;
            outline: none !important;
        }

        /* Ensure modal backdrop allows closing */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.8) !important;
        }

        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out !important;
        }

        /* Modal title styling */
        .modal-title {
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        /* Subtle Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 30px, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }

        .card, .stat-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .row .col-lg-3:nth-child(1) .stat-card { animation-delay: 0.1s; }
        .row .col-lg-3:nth-child(2) .stat-card { animation-delay: 0.2s; }
        .row .col-lg-3:nth-child(3) .stat-card { animation-delay: 0.3s; }
        .row .col-lg-3:nth-child(4) .stat-card { animation-delay: 0.4s; }

    </style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Comprehensive modal close fix
    function initializeModalCloseButtons() {
        // Fix all modal close buttons
        document.querySelectorAll('.modal .btn-close, .modal [data-bs-dismiss="modal"], .modal .close').forEach(function(btn) {
            // Remove existing event listeners to avoid duplicates
            btn.replaceWith(btn.cloneNode(true));
        });
        
        // Re-add event listeners to fresh buttons
        document.querySelectorAll('.modal .btn-close, .modal [data-bs-dismiss="modal"], .modal .close').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const modal = this.closest('.modal');
                if (modal) {
                    // Try Bootstrap 5 method first
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        // Create new instance and hide
                        const newModalInstance = new bootstrap.Modal(modal);
                        newModalInstance.hide();
                    }
                    
                    // Fallback: jQuery method
                    if (typeof $ !== 'undefined') {
                        $(modal).modal('hide');
                    }
                    
                    // Final fallback: manual hide
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    
                    // Remove backdrop
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                }
            });
        });
        
        // Fix backdrop clicks
        document.querySelectorAll('.modal').forEach(function(modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    const modalInstance = bootstrap.Modal.getInstance(this);
                    if (modalInstance) {
                        modalInstance.hide();
                    } else {
                        this.classList.remove('show');
                        this.style.display = 'none';
                        document.body.classList.remove('modal-open');
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) backdrop.remove();
                    }
                }
            });
        });
    }
    
    // Initialize immediately
    initializeModalCloseButtons();
    
    // Reinitialize after tab switches (for dynamically loaded content)
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tabTrigger) {
        tabTrigger.addEventListener('shown.bs.tab', function() {
            setTimeout(initializeModalCloseButtons, 100);
        });
    });
    
    // Reinitialize when new content is loaded
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                setTimeout(initializeModalCloseButtons, 100);
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Initialize Charts with modern styling
    if (document.getElementById('trafficChart')) {
        const trafficCtx = document.getElementById('trafficChart').getContext('2d');
        const trafficChart = new Chart(trafficCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [
                    {
                        label: 'New Visitors',
                        data: [3500, 4100, 3800, 5200, 4800, 5800, 6000, 6500, 6300, 7000],
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#4f46e5',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Returning Visitors',
                        data: [2500, 2800, 3200, 3600, 3300, 3900, 4200, 4500, 4800, 5100],
                        borderColor: '#0891b2',
                        backgroundColor: 'rgba(8, 145, 178, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#0891b2',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1f2937',
                        bodyColor: '#4b5563',
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6b7280'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6b7280',
                            callback: function(value) {
                                return value >= 1000 ? (value / 1000) + 'k' : value;
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }
    
    // Sales Chart - Doughnut Chart
    if (document.getElementById('salesChart')) {
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Direct', 'Affiliate', 'Email', 'Other'],
                datasets: [{
                    data: [{{ $directSales * 100 }}, {{ $affiliateSales * 100 }}, {{ $emailSales * 100 }}, {{ $otherSales * 100 }}],
                    backgroundColor: [
                        '#4f46e5', 
                        '#10b981', 
                        '#f59e0b', 
                        '#ef4444'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1f2937',
                        bodyColor: '#4b5563',
                        borderColor: 'rgba(0, 0, 0, 0.1)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const label = context.label || '';
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ₹${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                },
                elements: {
                    arc: {
                        borderWidth: 0
                    }
                },
                layout: {
                    padding: 10
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
    }
    
    // Show agreement modal if needed
    const agreementModal = document.getElementById('agreementModal');
    if (agreementModal) {
        const modal = new bootstrap.Modal(agreementModal);
        modal.show();
    }
    
    // Initialize date in agreement
    const currentDateElement = document.getElementById('current-date');
    if (currentDateElement) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        currentDateElement.textContent = new Date().toLocaleDateString('en-US', options);
    }

    // Initialize Charts
    const trafficCtx = document.getElementById('trafficChart');
    if (trafficCtx) {
        const trafficChart = new Chart(trafficCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
                datasets: [
                    {
                        label: 'New Visitor',
                        data: [2, 4, 8, 6, 12, 10, 8, 11, 9, 14],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Old Visitor',
                        data: [1, 3, 5, 4, 8, 7, 6, 8, 7, 10],
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        beginAtZero: true
                    }
                },
                elements: {
                    point: {
                        radius: 3,
                        hoverRadius: 5
                    }
                }
            }
        });
    }

    // Sales Chart (Doughnut Chart)
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        const salesChart = new Chart(salesCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Direct', 'Affiliate', 'E-mail', 'Other'],
                datasets: [{
                    data: [45, 25, 15, 15],
                    backgroundColor: [
                        '#667eea',
                        '#28a745',
                        '#ffc107',
                        '#dc3545'
                    ],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Universal Modal Close Functionality
    document.addEventListener('click', function(e) {
        // Close button functionality
        if (e.target.matches('[data-bs-dismiss="modal"]') || e.target.closest('[data-bs-dismiss="modal"]')) {
            const modal = e.target.closest('.modal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                modalInstance.hide();
            }
        }
        
        // Backdrop click to close
        if (e.target.matches('.modal')) {
            const modalInstance = bootstrap.Modal.getInstance(e.target) || new bootstrap.Modal(e.target);
            modalInstance.hide();
        }
    });

    // Escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            });
        }
    });

    // Fix for any modal close buttons that might not be working
    document.querySelectorAll('.modal').forEach(modal => {
        const closeButtons = modal.querySelectorAll('.btn-close, [data-bs-dismiss="modal"]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const modalInstance = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                modalInstance.hide();
            });
        });
    });
});
</script>

<!-- KYC Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const panBtn = document.getElementById('verifyPanBtn');
    const gstBtn = document.getElementById('verifyGstBtn');
    const bankBtn = document.getElementById('verifyBankBtn');

    if (panBtn) panBtn.addEventListener('click', verifyPan);
    if (gstBtn) gstBtn.addEventListener('click', verifyGst);
    if (bankBtn) bankBtn.addEventListener('click', verifyBank);
});

function verifyPan() {
    const panNumber = document.getElementById('panNumber').value;
    const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;

    if (!panRegex.test(panNumber)) {
        document.getElementById('panStatus').innerHTML = '<span class="text-danger">Invalid PAN format.</span>';
        return;
    }

    fetch("{{ route('pan.verify') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ pan_number: panNumber })
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            let name = data.data.registered_name ?? 'N/A';
            document.getElementById('panStatus').innerHTML = '<span class="text-success">PAN Verified: ' + name + '</span>';
            document.getElementById('pan_verified_name').value = name;
        } else {
            document.getElementById('panStatus').innerHTML = '<span class="text-danger">Verification failed: ' + data.error + '</span>';
        }
    })
    .catch(err => console.error(err));
}




</script>



<script>
// Global variables for debugging
window.debugAadhaar = true;
let refId = null;

console.log('=== AADHAAR VERIFICATION SCRIPT STARTING ===');

$(document).ready(function() {
    console.log('Document ready!');
    console.log('jQuery version:', $.fn.jquery);
    
    // Force show modal for testing (remove this later)
    setTimeout(function() {
        console.log('=== MODAL DEBUG INFO ===');
        console.log('Agreement Modal exists:', $('#agreementModal').length);
        console.log('Modal is visible:', $('#agreementModal').is(':visible'));
        console.log('Modal has show class:', $('#agreementModal').hasClass('show'));
        
        // Force show modal if not visible
        if ($('#agreementModal').length > 0 && !$('#agreementModal').hasClass('show')) {
            console.log('Forcing modal to show...');
            $('#agreementModal').modal('show');
        }
        
        // Check elements after modal is shown
        setTimeout(function() {
            console.log('=== ELEMENTS CHECK ===');
            console.log('Generate OTP Button:', $('#generateOtpBtn').length);
            console.log('Aadhaar Number Input:', $('#aadhaarNumber').length);
            console.log('Verify OTP Button:', $('#verifyOtpBtn').length);
            console.log('OTP Section:', $('#otpSection').length);
            console.log('Aadhaar Status:', $('#aadhaarStatus').length);
            
            // Add visual indicators to buttons for testing
            if ($('#generateOtpBtn').length > 0) {
                $('#generateOtpBtn').css('border', '3px solid red');
                console.log('Added red border to Generate OTP button');
            }
            
            if ($('#verifyOtpBtn').length > 0) {
                $('#verifyOtpBtn').css('border', '3px solid blue');
                console.log('Added blue border to Verify OTP button');
            }
        }, 1000);
    }, 500);
    
    //Generate Aadhaar OTP - Using multiple event bindings for testing
    // $(document).on('click', '#generateOtpBtn', function(e) {
    //     alert('Generate OTP button clicked!');
    //     e.preventDefault();
    //     e.stopPropagation();
    //     console.log('=== GENERATE OTP CLICKED ===');
        
    //     const aadhaarNumber = $('#aadhaarNumber').val();
    //     console.log('Aadhaar Number:', aadhaarNumber);
        

    //     if (!aadhaarNumber) {
    //         alert('Please enter Aadhaar number first!');
    //         return;
    //     }
        
    //     if (aadhaarNumber.length !== 12) {
    //         alert('Please enter a valid 12-digit Aadhaar number.');
    //         return;
    //     }

    //     // Show loading state
    //     $(this).prop('disabled', true).text('Sending OTP...');
    //     console.log('Button disabled, sending request...');
        
    //     $.ajax({
    //         url: "{{ route('aadhaar.generate') }}",
    //         method: 'POST',
    //         headers: {
    //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //         },
    //         data: {
    //             aadhaar_number: aadhaarNumber
    //         },
    //         beforeSend: function() {
    //             console.log('AJAX request starting...');
    //         },
    //         success: function(data) {
    //             console.log('=== SUCCESS RESPONSE ===');
    //             console.log('Response:', data);
                
    //             if (data.ok && data.data.status === "SUCCESS") {
    //                 refId = data.data.ref_id;
    //                 console.log('RefId set:', refId);
    //                 $('#otpSection').removeClass('d-none');
    //                 $('#aadhaarStatus').html(`<span class="text-success">${data.data.message}</span>`);
    //                 alert('OTP sent successfully!');
    //             } else {
    //                 $('#aadhaarStatus').html(`<span class="text-danger">${data.error ? data.error.message : 'Something went wrong'}</span>`);
    //                 alert('Failed to send OTP: ' + (data.error ? data.error.message : 'Unknown error'));
    //             }
    //             $('#generateOtpBtn').prop('disabled', false).text('Send OTP');
    //         },
    //         error: function(xhr, status, error) {
    //             console.log('=== ERROR RESPONSE ===');
    //             console.error('AJAX Error:', error);
    //             console.error('Status:', status);
    //             console.error('Response:', xhr.responseText);
    //             $('#aadhaarStatus').html(`<span class="text-danger">Error: ${error}</span>`);
    //             $('#generateOtpBtn').prop('disabled', false).text('Send OTP');
    //             alert('AJAX Error: ' + error);
    //         }
    //     });
    // });

    // //Also bind directly for testing
    // $('#generateOtpBtn').on('click', function(e) {
    //     console.log('Direct click handler triggered!');
    // });

    // Verify Aadhaar OTP
    $(document).on('click', '#verifyOtpBtn', function(e) {
        e.preventDefault();
        console.log('=== VERIFY OTP CLICKED ===');
        
        const otp = $('#aadhaarOtp').val();
        console.log('OTP:', otp, 'RefId:', refId);
        
        if (!refId) {
            alert('Please generate OTP first!');
            return;
        }
        
        if (!otp || otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP.');
            return;
        }

        $(this).prop('disabled', true).text('Verifying...');

        $.ajax({
            url: "{{ route('aadhaar.verify') }}",
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                ref_id: refId,
                otp: otp
            },
            success: function(data) {
                console.log("=== VERIFY RESPONSE ===");
                console.log("Response:", data);

                if (data.ok && data.data.status === "VALID") {
                    const details = data.data;
                    $('#aadhaarName').val(details.name);
                    $('#aadhaarCareOf').val(details.care_of);
                    $('#aadhaarDob').val(details.dob);
                    $('#aadhaarAddress').val(details.address);
                    $('#aadhaarDetails').removeClass('d-none');
                    $('#aadhaarStatus').html(`<span class="text-success">Aadhaar verified successfully!</span>`);
                    $('#finalSubmit').prop('disabled', false);
                    alert('Aadhaar verified successfully!');
                } else {
                    $('#aadhaarStatus').html(`<span class="text-danger">Verification failed</span>`);
                    alert('Verification failed!');
                }
                $('#verifyOtpBtn').prop('disabled', false).text('Verify OTP');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#aadhaarStatus').html(`<span class="text-danger">Error: ${error}</span>`);
                $('#verifyOtpBtn').prop('disabled', false).text('Verify OTP');
                alert('Verification error: ' + error);
            }
        });
    });

    // Test function - click this in console: testButtonClick()
    window.testButtonClick = function() {
        console.log('=== MANUAL TEST CLICK ===');
        $('#generateOtpBtn').trigger('click');
    };
    
    // Add click test for any button in modal
    $(document).on('click', '.modal button', function() {
        console.log('Modal button clicked:', this.id, this.textContent);
    });
    
    console.log('=== SCRIPT SETUP COMPLETE ===');
    console.log('Test command available: testButtonClick()');
});
</script>




    <script>
        $(document).ready(function() {
            $.ajax({
                url: '/get-current-date',
                type: 'GET',
                success: function(response) {
                    $('#current-date').text(response.date);
                },
                error: function() {
                    $('#current-date').text('Error fetching date');
                }
            });
        });
    </script>

   
    <script>

        
        $(document).ready(function() {
            @if ($seller->agreement_accepted == 0)
                $('#agreementModal').modal('show');
            @endif

            $('#submitAgreement').click(function(e) {
                e.preventDefault();

                if ($('#agreeTerms').is(':checked')) {
                    $.ajax({
                        url: "{{ route('seller.agreement.accept') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            clientName: $('#clientName').val(),
                            clientAddress: $('#clientAddress').val(),
                            clientPAN: $('#clientPAN').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message
                                }).then(() => {
                                    $('#agreementModal').modal('hide');
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            let response = xhr.responseJSON;
                            if (response && response.errors) {
                                let errorMessages = Object.values(response.errors).join("\n");
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validation Error',
                                    text: errorMessages
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Error submitting agreement. Please try again.'
                                });
                            }
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required',
                        text: 'You must agree to the terms before submitting.'
                    });
                }
            });
        });
        
        // Enhanced Dashboard Animations with Unique Classes
        document.addEventListener('DOMContentLoaded', function() {
            // Animate numbers with counting effect for dashboard
            function animateDashboardNumber(element, finalValue) {
                const startValue = 0;
                const duration = 2000;
                const startTime = performance.now();
                
                function updateNumber(currentTime) {
                    const elapsedTime = currentTime - startTime;
                    const progress = Math.min(elapsedTime / duration, 1);
                    const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                    
                    const currentValue = Math.floor(startValue + (finalValue - startValue) * easeOutQuart);
                    element.textContent = element.textContent.replace(/\d+/, currentValue);
                    
                    if (progress < 1) {
                        requestAnimationFrame(updateNumber);
                    }
                }
                
                requestAnimationFrame(updateNumber);
            }
            
            // Apply counting animation to dashboard stat numbers
            setTimeout(() => {
                document.querySelectorAll('.dashboard-stat-number').forEach(element => {
                    const text = element.textContent;
                    const number = parseInt(text.replace(/[^\d]/g, '')) || 0;
                    if (number > 0) {
                        animateDashboardNumber(element, number);
                    }
                });
            }, 500);
            
            // Add enhanced hover effects to dashboard cards
            document.querySelectorAll('.dashboard-stat-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-12px) scale(1.04) !important';
                    this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.25) !important';
                    this.style.zIndex = '20';
                    const icon = this.querySelector('.dashboard-stat-icon i');
                    if (icon) {
                        icon.style.transform = 'scale(1.4) rotate(15deg)';
                        icon.style.filter = 'brightness(1.3) drop-shadow(4px 4px 8px rgba(0,0,0,0.5))';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '';
                    this.style.zIndex = '';
                    const icon = this.querySelector('.dashboard-stat-icon i');
                    if (icon) {
                        icon.style.transform = 'scale(1) rotate(0deg)';
                        icon.style.filter = 'brightness(1)';
                    }
                });
            });
            
            // Animate progress bars with dashboard-specific class
            setTimeout(() => {
                document.querySelectorAll('.dashboard-progress-bar, .progress-bar').forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                        bar.style.transition = 'width 1.8s cubic-bezier(0.4, 0, 0.2, 1)';
                    }, 300);
                });
            }, 1400);
            
            // Enhanced dashboard social icon animations
            document.querySelectorAll('.dashboard-social-icon').forEach(icon => {
                icon.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.25) rotate(20deg) !important';
                    this.style.boxShadow = '0 12px 30px rgba(0,0,0,0.3) !important';
                    this.style.filter = 'brightness(1.1)';
                });
                
                icon.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1) rotate(0deg)';
                    this.style.boxShadow = '';
                    this.style.filter = 'brightness(1)';
                });
            });
            
            // Dashboard chart card hover effects
            document.querySelectorAll('.dashboard-chart-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.01)';
                    this.style.boxShadow = '0 15px 30px rgba(0,0,0,0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '';
                });
            });
            
            // Add ripple effect to dashboard stat cards
            document.querySelectorAll('.dashboard-stat-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    const ripple = document.createElement('div');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(255,255,255,0.3)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple 0.6s linear';
                    ripple.style.pointerEvents = 'none';
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });

    // Agreement Modal and Aadhaar Verification JavaScript
    @if ($seller->agreement_accepted == 0)
    // Generate OTP for Aadhaar
    // document.getElementById('generateOtpBtn').addEventListener('click', function() {
    //     const aadhaarNumber = document.getElementById('aadhaarNumber').value;
    //     if (aadhaarNumber.length !== 12) {
    //         alert('Please enter a valid 12-digit Aadhaar number');
    //         return;
    //     }

    //     this.innerHTML = 'Sending OTP...';
    //     this.disabled = true;

    //     // Simulate OTP generation (replace with actual API call)
    //     fetch('/seller/aadhaar/generate-otp', {
    //         method: 'POST',
    //         headers: {
    //             'Content-Type': 'application/json',
    //             'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    //         },
    //         body: JSON.stringify({ aadhaar_number: aadhaarNumber })
    //     })
    //     .then(response => response.json())
    //     .then(data => {
    //         if (data.success) {
    //             document.getElementById('otpSection').classList.remove('d-none');
    //             this.innerHTML = 'OTP Sent!';
    //             this.classList.add('btn-success');
    //             setTimeout(() => {
    //                 this.innerHTML = 'Resend OTP';
    //                 this.disabled = false;
    //                 this.classList.remove('btn-success');
    //             }, 30000);
    //         } else {
    //             alert('Failed to send OTP. Please try again.');
    //             this.innerHTML = 'Send OTP';
    //             this.disabled = false;
    //         }
    //     })
    //     .catch(error => {
    //         console.error('Error:', error);
    //         alert('An error occurred. Please try again.');
    //         this.innerHTML = 'Send OTP';
    //         this.disabled = false;
    //     });
    // });

    // Verify OTP for Aadhaar
    document.getElementById('verifyOtpBtn').addEventListener('click', function() {
        const aadhaarNumber = document.getElementById('aadhaarNumber').value;
        const otp = document.getElementById('aadhaarOtp').value;
        
        if (otp.length !== 6) {
            alert('Please enter a valid 6-digit OTP');
            return;
        }

        this.innerHTML = 'Verifying...';
        this.disabled = true;

        // Verify OTP (replace with actual API call)
        fetch('/seller/aadhaar/verify-otp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ 
                aadhaar_number: aadhaarNumber,
                otp: otp 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show Aadhaar details
                document.getElementById('aadhaarDetails').classList.remove('d-none');
                document.getElementById('aadhaarName').value = data.data.name || '';
                document.getElementById('aadhaarCareOf').value = data.data.care_of || '';
                document.getElementById('aadhaarDob').value = data.data.dob || '';
                document.getElementById('aadhaarAddress').value = data.data.address || '';
                
                document.getElementById('aadhaarStatus').innerHTML = '<div class="alert alert-success">Aadhaar verified successfully!</div>';
                this.innerHTML = 'Verified';
                this.classList.add('btn-success');
                this.disabled = true;
            } else {
                document.getElementById('aadhaarStatus').innerHTML = '<div class="alert alert-danger">Invalid OTP. Please try again.</div>';
                this.innerHTML = 'Verify OTP';
                this.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('aadhaarStatus').innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            this.innerHTML = 'Verify OTP';
            this.disabled = false;
        });
    });

    // Enable submit button when terms are agreed
    document.getElementById('agreeTerms').addEventListener('change', function() {
        document.getElementById('finalSubmit').disabled = !this.checked;
    });

    // Handle agreement form submission
    document.getElementById('agreementForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!document.getElementById('agreeTerms').checked) {
            alert('Please agree to the terms and conditions');
            return;
        }

        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Agreement accepted successfully!');
                // Close modal and reload page
                const modal = bootstrap.Modal.getInstance(document.getElementById('agreementModal'));
                modal.hide();
                location.reload();
            } else {
                alert('Failed to accept agreement. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
    @endif

    
     </script>
@endsection

@section('css')
<!-- Tabler Icons CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons@2.44.0/tabler-icons.min.css">

<!-- Additional Dashboard Styling -->
<style>
    /* Modern UI Enhancements */
    .modern-dashboard .modern-card {
        border-radius: var(--border-radius);
        transition: all 0.3s ease;
        box-shadow: var(--box-shadow);
        border: none;
    }
    
    .modern-dashboard .modern-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--box-shadow-hover);
    }
    
    /* Enhanced Animation Effects */
    .fade-in {
        opacity: 0;
        animation: fadeInEffect 0.8s ease forwards;
    }
    
    @keyframes fadeInEffect {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .fade-in-delay-1 { animation-delay: 0.1s; }
    .fade-in-delay-2 { animation-delay: 0.2s; }
    .fade-in-delay-3 { animation-delay: 0.3s; }
    .fade-in-delay-4 { animation-delay: 0.4s; }
    
    /* Beautiful Button Styles */
    .btn-modern {
        padding: 0.6rem 1.5rem;
        border-radius: 10px;
        transition: all 0.3s ease;
        font-weight: 500;
        border: none;
    }
    
    .btn-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    
    .btn-modern.btn-primary {
        background: linear-gradient(135deg, #4f46e5, #4338ca);
        color: white;
    }
    
    .btn-modern.btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    
    /* Custom Badge Styles */
    .badge-modern {
        padding: 0.5em 1em;
        border-radius: 8px;
        font-weight: 500;
        letter-spacing: 0.02em;
    }
    
    /* Custom Progress Bar */
    .modern-progress {
        height: 8px;
        background-color: var(--gray-100);
        border-radius: 4px;
        overflow: hidden;
    }
    
    .modern-progress-bar {
        height: 100%;
        border-radius: 4px;
        transition: width 1s ease;
    }
    
    /* Card Hover Effects */
    .hover-card-effect {
        transition: all 0.3s ease;
    }
    
    .hover-card-effect:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    
    /* Custom Font Weights */
    .fw-medium {
        font-weight: 500;
    }
    
    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    
    ::-webkit-scrollbar-track {
        background: var(--gray-100);
        border-radius: 5px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 5px;
        border: 2px solid var(--gray-100);
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }
    
    /* Fix for modal close buttons */
    .modal .btn-close {
        opacity: 1;
        position: absolute;
        top: 1rem;
        right: 1rem;
        z-index: 2000;
        background: rgba(255, 255, 255, 0.3) !important;
        backdrop-filter: blur(4px);
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .modal .btn-close:hover {
        background: rgba(255, 255, 255, 0.5) !important;
        transform: rotate(90deg);
    }
    
    /* Welcome Section Enhancement */
    .welcome-section {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        background-size: 200% 200%;
        animation: gradientAnimation 15s ease infinite;
    }
    
    @keyframes gradientAnimation {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    /* Stat Cards Enhancement */
    .stat-card {
        border: none;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
        pointer-events: none;
    }
    
    /* Table Enhancements */
    .table-modern {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table-modern th {
        background-color: var(--gray-100);
        color: var(--dark-color);
        font-weight: 600;
        padding: 1rem;
        border: none;
    }
    
    .table-modern td {
        padding: 1rem;
        border-top: 1px solid var(--gray-200);
        vertical-align: middle;
    }
    
    .table-modern tr:hover td {
        background-color: var(--gray-100);
    }
    
    /* Make sure modals close properly */
    .modal {
        z-index: 1050;
    }
    
    .modal-backdrop {
        z-index: 1040;
    }
</style>



@endsection
