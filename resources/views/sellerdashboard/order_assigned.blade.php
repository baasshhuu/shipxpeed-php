@extends('layouts.sellerdash')

@section('content')

<style>
    :root {
        --shipxpeed-primary: #6366f1;
        --shipxpeed-primary-light: #8b5cf6;
        --shipxpeed-primary-dark: #4f46e5;
        --shipxpeed-success: #10b981;
        --shipxpeed-success-light: #34d399;
        --shipxpeed-danger: #ef4444;
        --shipxpeed-danger-light: #f87171;
        --shipxpeed-warning: #f59e0b;
        --shipxpeed-warning-light: #fbbf24;
        --shipxpeed-info: #06b6d4;
        --shipxpeed-info-light: #22d3ee;
        --shipxpeed-dark: #1f2937;
        --shipxpeed-light: #f8fafc;
        --shipxpeed-gray: #6b7280;
        --shipxpeed-border: #e5e7eb;
        --shipxpeed-border-light: #f3f4f6;
        --shipxpeed-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        --shipxpeed-shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
        --shipxpeed-shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.15);
        --shipxpeed-radius: 12px;
        --shipxpeed-radius-sm: 8px;
        --shipxpeed-radius-lg: 16px;
        --shipxpeed-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --shipxpeed-font-primary: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Global font improvements */
    body {
        font-family: var(--shipxpeed-font-primary);
        font-feature-settings: "liga" 1, "kern" 1;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

    /* Override default styles to match modern design */
    .pc-container {
        padding: 0.5rem 1rem !important;
        background: transparent;
        min-height: 100vh;
    }

    /* Remove any extra top gap before header */
    .order-dashboard-header {
        margin-top: 0 !important;
    }

    /* Enhanced Dashboard Header (Compact) */
    .order-dashboard-header {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        color: white;
        padding: 0.25rem 0; /* reduced vertical padding */
        margin-bottom: 0.5rem;
        border-radius: 0 0 calc(var(--shipxpeed-radius-lg) / 2) calc(var(--shipxpeed-radius-lg) / 2);
        box-shadow: var(--shipxpeed-shadow-lg);
        position: relative;
        overflow: hidden;
    }

    .order-dashboard-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.06) 0%, transparent 50%, rgba(255,255,255,0.06) 100%);
        pointer-events: none;
    }
    
    .order-dashboard-title {
        font-size: 1rem; /* smaller title */
        font-weight: 700;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.15);
        margin-bottom: 0.05rem;
        letter-spacing: -0.02em;
    }
    
    .order-dashboard-subtitle {
        font-size: 0.72rem; /* smaller subtitle */
        opacity: 0.95;
        font-weight: 400;
        color: rgba(255,255,255,0.95);
        margin-top: 0.15rem;
    }

    /* Enhanced Content Container */
    .pc-content {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: var(--shipxpeed-radius-lg);
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shipxpeed-shadow-lg);
        border: 1px solid var(--shipxpeed-border-light);
        transition: var(--shipxpeed-transition);
    }

    .pc-content:hover {
        box-shadow: var(--shipxpeed-shadow-xl);
        transform: translateY(-2px);
    }

    /* Enhanced Toggle Section */
    .toggle-button-section {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: var(--shipxpeed-radius-lg);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shipxpeed-shadow-lg);
        border: 1px solid var(--shipxpeed-border-light);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .toggle-button {
        display: flex;
        gap: 0.75rem;
        background: var(--shipxpeed-light);
        border-radius: var(--shipxpeed-radius);
        padding: 0.5rem;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }

    /* Enhanced Table Styles */
    .table tbody tr {
        cursor: pointer;
        transition: var(--shipxpeed-transition);
        border-bottom: 1px solid var(--shipxpeed-border-light);
    }

    .table tbody tr:hover {
        background: rgba(99, 102, 241, 0.05) !important;
        box-shadow: inset 4px 0 0 var(--shipxpeed-primary);
    }

    .table tbody tr.selected {
        background: rgba(99, 102, 241, 0.1) !important;
        border-left: 4px solid var(--shipxpeed-primary);
    }

    .table tbody tr.selected td {
        border-color: var(--shipxpeed-primary);
    }

    /* Enhanced Button Styles */
    .toggle-button .btn {
        border-radius: var(--shipxpeed-radius);
        font-weight: 600;
        background: transparent;
        color: var(--shipxpeed-gray);
        border: 2px solid transparent;
        padding: 0.75rem 1.5rem;
        transition: var(--shipxpeed-transition);
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.875rem;
    }

    .toggle-button .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        transition: var(--shipxpeed-transition);
        z-index: -1;
    }

    .toggle-button .btn.active,
    .toggle-button .btn:hover {
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .toggle-button .btn.active::before,
    .toggle-button .btn:hover::before {
        left: 0;
    }

    /* Enhanced Section Container */
    .section-container {
        background: white;
        border-radius: var(--shipxpeed-radius-lg);
        box-shadow: var(--shipxpeed-shadow-lg);
        padding: 0.9rem 1rem;
        margin-bottom: 1rem;
        transition: var(--shipxpeed-transition);
        position: relative;
        overflow: hidden;
        border: 1px solid var(--shipxpeed-border);
    }

    .section-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
    }

    .section-container:hover {
        box-shadow: var(--shipxpeed-shadow-xl);
        transform: translateY(-2px);
    }

    /* Enhanced Button Group Container */
    .btn-group-container {
        margin-bottom: 0.6rem;
    }

    .btn-group-container label {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--shipxpeed-dark);
        margin-bottom: 0.4rem;
        display: block;
        position: relative;
        padding-left: 1rem;
    }

    .btn-group-container label::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 100%;
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        border-radius: 2px;
    }

    /* Enhanced Button Group */
    .btn-group {
        gap: 0.75rem;
        display: flex;
        flex-wrap: wrap;
        border: none !important;
    }

    .btn-group .btn {
        border-radius: var(--shipxpeed-radius);
        background: var(--shipxpeed-light);
        color: var(--shipxpeed-gray);
        border: 2px solid var(--shipxpeed-border);
        font-size: 0.9rem;
        font-weight: 600;
        transition: var(--shipxpeed-transition);
        box-shadow: var(--shipxpeed-shadow);
        padding: 0.75rem 1.25rem;
        position: relative;
        overflow: hidden;
        text-decoration: none;
        margin-right: 0;
    }

    .btn-group .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        transition: var(--shipxpeed-transition);
        z-index: -1;
    }

    .btn-group .btn.active {
        color: white;
        border-color: var(--shipxpeed-primary);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        transform: translateY(-2px);
    }

    .btn-group .btn.active::before {
        left: 0;
    }

    .btn-group .btn:hover {
        color: white;
        border-color: var(--shipxpeed-primary);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        transform: translateY(-2px);
    }

    .btn-group .btn:hover::before {
        left: 0;
    }
    /* Enhanced Badge Styles */
    .badge {
        border-radius: var(--shipxpeed-radius-sm);
        font-size: 0.75rem;
        /* font-weight: 700; */
        padding: 0.5rem 0.75rem;
        margin-left: 0.5rem;
        /* box-shadow: var(--shipxpeed-shadow); */
        position: relative;
        overflow: hidden;
        /* min-width: 20px;
        height: auto; */
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        letter-spacing: 0.025em;
        text-transform: uppercase;
    }

    .badge::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: var(--shipxpeed-transition);
    }

    .badge:hover::before {
        left: 100%;
    }

    /* Compact overrides for status filter section */
    .section-container .d-flex {
        gap: 0.5rem !important;
    }

    .section-container .btn-group .btn,
    .section-container .btn {
        padding: 0.45rem 0.8rem !important;
        font-size: 0.9rem !important;
        border-radius: calc(var(--shipxpeed-radius) - 4px) !important;
        box-shadow: none !important;
    }

    .section-container .btn.position-relative .badge {
        position: absolute;
        top: -6px;
        right: -6px;
        font-size: 0.65rem;
        min-width: 18px;
        height: 18px;
        padding: 0;
        border-width: 2px;
    }

    .section-container h5 {
        margin-bottom: 0.5rem;
        font-size: 1rem;
    }

    /* Enhanced Table Container */
    .table-container {
        background: white;
        border-radius: var(--shipxpeed-radius-lg);
        overflow: hidden;
        box-shadow: var(--shipxpeed-shadow-xl);
        border: 1px solid var(--shipxpeed-border);
        backdrop-filter: blur(10px);
        margin-bottom: 2rem;
        margin-left:12px;
    }

    /* Enhanced Controls Section */
    .enhanced-controls-section {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: 0.7rem 0.7rem;
        border-bottom: 1px solid var(--shipxpeed-border-light);
    }

    .controls-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 2rem;
        flex-wrap: wrap;
    }

    /* Date Filter Styles */
    .date-filter-container {
        flex: 1;
        min-width: 300px;
    }

    .date-filter-form {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .date-inputs-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: var(--shipxpeed-light);
        /* padding: 0.3rem 0.5rem; */
        border-radius: 5px;
        border: 2px solid var(--shipxpeed-border);
        transition: var(--shipxpeed-transition);
    }

    .date-inputs-group:focus-within {
        border-color: var(--shipxpeed-primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .enhanced-date-input {
        border: none;
        background: transparent;
        padding: 0.5rem 0.75rem;
        border-radius: 4px;
        font-weight: 500;
        color: var(--shipxpeed-dark);
        width: 140px;
        transition: var(--shipxpeed-transition);
    }

    .enhanced-date-input:focus {
        outline: none;
        background: rgba(99, 102, 241, 0.05);
    }

    .date-separator {
        color: var(--shipxpeed-gray);
        font-weight: 600;
        font-size: 0.9rem;
    }

    .enhanced-filter-btn {
        background:linear-gradient(135deg, #9496e7 0%, #5147f3 100%);
        color: white;
        border: none;
        padding: 0.6rem 0.7rem;
        border-radius: 5px;
        /* font-weight: 600; */
        transition: var(--shipxpeed-transition);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    }

    .enhanced-filter-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        color: white;
    }

    .enhanced-clear-btn {
        background: transparent;
        color: var(--shipxpeed-danger);
        border: 2px solid var(--shipxpeed-danger);
        padding: 0.75rem 1.5rem;
        border-radius: var(--shipxpeed-radius);
        font-weight: 600;
        transition: var(--shipxpeed-transition);
        text-decoration: none;
    }

    .enhanced-clear-btn:hover {
        background: var(--shipxpeed-danger);
        color: white;
        transform: translateY(-1px);
    }

    .enhanced-export-btn {
        background: linear-gradient(135deg, #5acfa8 0%, #14b97d 100%);
        color: white;
        border: none;
        padding: 0.4rem 0.6rem;
        border-radius: 5px;
        text-decoration: none;
        /* font-weight: 600; */
        transition: var(--shipxpeed-transition);
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
    }

    .enhanced-export-btn:hover {
        background: linear-gradient(135deg, #059669 0%, var(--shipxpeed-success) 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        color: white;
    }

    /* Action Controls Styles */
    .action-controls-container {
        display: flex;
        align-items: center;
    }

    .action-controls-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .enhanced-control-btn {
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid var(--shipxpeed-border);
        color: var(--shipxpeed-dark);
        padding: 0.2rem 0.3rem;
        border-radius: var(--shipxpeed-radius);
        /* font-weight: 600; */
        transition: var(--shipxpeed-transition);
        min-width: 44px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .enhanced-control-btn:hover {
        background: var(--shipxpeed-primary);
        border-color: var(--shipxpeed-primary);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .refresh-btn {
        min-width: 44px;
    }

    .bulk-actions-btn {
        min-width: auto;
        white-space: nowrap;
    }

    .settings-btn {
        min-width: 44px;
    }

    .enhanced-dropdown {
        border: none;
        border-radius: var(--shipxpeed-radius);
        box-shadow: var(--shipxpeed-shadow-xl);
        padding: 0.5rem 0;
        margin-top: 0.5rem;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
    }

    /* Dropup specific styling */
    .dropup .enhanced-dropdown {
        margin-top: 0;
        margin-bottom: 0.5rem;
    }

    .enhanced-dropdown .dropdown-item {
        padding: 0.75rem 1.25rem;
        transition: var(--shipxpeed-transition);
        font-weight: 500;
    }

    .enhanced-dropdown .dropdown-item:hover {
        background: var(--shipxpeed-light);
        transform: translateX(4px);
    }

    /* Enhanced Table Header Design */
    .enhanced-table-head {
        background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
        position: relative;
    }

    .enhanced-table-head::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.05) 0%, transparent 50%, rgba(255,255,255,0.05) 100%);
        pointer-events: none;
    }

    .table-header-row {
        position: relative;
        z-index: 2;
    }

    .table-header-row th {
        border: none;
        padding: 1.25rem 1rem;
        vertical-align: middle;
        position: relative;
        color: white;
        font-weight: 700;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .table-header-row th:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 1px;
        height: 60%;
        background: rgba(255, 255, 255, 0.1);
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        position: relative;
    }

    .header-icon {
        font-size: 1rem;
        opacity: 0.9;
        color: rgba(255, 255, 255, 0.9);
    }

    .header-text {
        font-weight: 700;
        font-size: 0.8rem;
        color: white;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    }

    .header-hash {
        font-weight: 900;
        font-size: 1rem;
        color: rgba(255, 255, 255, 0.7);
        margin-left: -0.25rem;
    }

    /* Column specific styling */
    .checkbox-col {
        width: 50px;
        padding-left: 1.5rem !important;
    }

    .order-col {
        width: 120px;
    }

    .awb-col {
        width: 150px;
    }

    .product-col {
        width: 180px;
    }

    .payment-col {
        width: 120px;
    }

    .collectable-col {
        width: 140px;
    }

    .method-col {
        width: 100px;
    }

    .customer-col {
        width: 150px;
    }

    .zip-col {
        width: 100px;
    }

    .weight-col {
        width: 100px;
    }

    .download-col {
        width: 160px;
    }

    .action-col {
        width: 120px;
        padding-right: 1.5rem !important;
    }

    .enhanced-checkbox {
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.1);
        transition: var(--shipxpeed-transition);
    }

    .enhanced-checkbox:checked {
        background-color: var(--shipxpeed-success);
        border-color: var(--shipxpeed-success);
        transform: scale(1.1);
    }

    .enhanced-checkbox:focus {
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        border-color: var(--shipxpeed-success);
    }

    /* Hover effects for headers */
    .table-header-row th:hover .header-icon {
        color: var(--shipxpeed-primary-light);
        transform: scale(1.1);
        transition: var(--shipxpeed-transition);
    }

    .table-header-row th:hover .header-text {
        color: black;
        transition: var(--shipxpeed-transition);
    }

    /* Enhanced Table Header */
    .enhanced-table-header {
        background: linear-gradient(135deg, var(--shipxpeed-dark) 0%, #374151 100%);
        color: white;
        padding: 1.25rem 2rem;
        position: relative;
        overflow: hidden;
    }

    .enhanced-table-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.05) 0%, transparent 50%, rgba(255,255,255,0.05) 100%);
        pointer-events: none;
    }

    .table-header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1.5rem;
        position: relative;
        z-index: 2;
    }

    .table-title-section {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .table-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        color: white;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    }

    .orders-count-badge {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: var(--shipxpeed-radius);
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }

    .selection-counter-section .selection-counter {
        background: rgba(16, 185, 129, 0.9);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: var(--shipxpeed-radius);
        font-size: 0.85rem;
        font-weight: 600;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Responsive Design for Enhanced Components */
    @media (max-width: 768px) {
        .enhanced-controls-section {
            padding: 1rem;
        }

        .controls-row {
            flex-direction: column;
            gap: 1rem;
        }

        .date-filter-container {
            min-width: unset;
            width: 100%;
        }

        .date-filter-form {
            flex-direction: column;
            align-items: stretch;
        }

        .date-inputs-group {
            justify-content: center;
        }

        .table-header-content {
            flex-direction: column;
            gap: 1rem;
        }

        .table-title-section {
            flex-direction: column;
            text-align: center;
        }
    }

    .table-header {
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        padding: 1.5rem 2rem;
        color:black;
        position: relative;
    }

    .table-header::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    }

    .table-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .date-input {
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: var(--shipxpeed-radius);
        color: white;
        padding: 0.75rem 1rem;
        transition: var(--shipxpeed-transition);
    }

    .date-input::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }

    /* Enhanced Product Info Styling */
    .product-info {
        max-width: 200px;
    }

    .product-item {
        background: var(--shipxpeed-light);
        border-radius: var(--shipxpeed-radius-sm);
        padding: 0.5rem 0.75rem;
        border-left: 3px solid var(--shipxpeed-primary);
    }

    .product-name {
        font-size: 0.875rem;
        line-height: 1.4;
        margin-bottom: 0.25rem;
    }

    /* Enhanced Order Number Styling */
    .order-number strong {
        font-size: 1rem;
        font-weight: 700;
    }

    /* Enhanced AWB Container */
    .awb-container .badge {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
    }

    /* Enhanced Customer Info */
    .customer-info {
        max-width: 180px;
    }

    .customer-name {
        font-weight: 600;
        color: var(--shipxpeed-dark);
        /* margin-bottom: 0.25rem; */
    }

    .customer-details {
        font-size: 0.8rem;
        color: var(--shipxpeed-gray);
    }

    /* Enhanced Action Dropdown */
    .action-dropdown .dropdown-toggle {
        background: linear-gradient(135deg, #f2f2f3 0%, #f8f8fd 100%);;
        /* border: 1px solid; */
        border-radius: 6px;
        padding: 0.5rem 0.6rem;
        /* font-size: 0.8rem;
        font-weight: 600; */
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        transition: var(--shipxpeed-transition);
    }

    .action-dropdown .dropdown-toggle:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    }

    .action-dropdown .dropdown-menu {
        border: none;
        border-radius: var(--shipxpeed-radius);
        box-shadow: var(--shipxpeed-shadow-xl);
        padding: 0.5rem 0;
        margin-top: 0.5rem;
    }

    .action-dropdown .dropdown-item {
        padding: 0.75rem 1.25rem;
        transition: var(--shipxpeed-transition);
        font-weight: 500;
    }

    .action-dropdown .dropdown-item:hover {
        background: var(--shipxpeed-light);
        transform: translateX(4px);
    }

    /* Enhanced Form Controls */
    .form-control, .form-select {
        border: 2px solid var(--shipxpeed-border);
        border-radius: var(--shipxpeed-radius);
        padding: 0.75rem 1rem;
        transition: var(--shipxpeed-transition);
        font-weight: 500;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--shipxpeed-primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        transform: translateY(-1px);
    }

    /* Enhanced Checkboxes */
    .form-check-input {
        width: 1rem;
        height: 1rem;
        border: 2px solid var(--shipxpeed-border);
        border-radius: 4px;
        transition: var(--shipxpeed-transition);
    }

    .form-check-input:checked {
        background-color: var(--shipxpeed-primary);
        border-color: var(--shipxpeed-primary);
        transform: scale(1.1);
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    /* Enhanced Pagination */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        border: 2px solid var(--shipxpeed-border);
        color: var(--shipxpeed-primary);
        font-weight: 600;
        padding: 0.75rem 1rem;
        margin: 0 2px;
        border-radius: var(--shipxpeed-radius);
        transition: var(--shipxpeed-transition);
    }

    .page-link:hover {
        background: var(--shipxpeed-primary);
        border-color: var(--shipxpeed-primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .page-item.active .page-link {
        background: var(--shipxpeed-primary);
        border-color: var(--shipxpeed-primary);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    /* Enhanced Empty State */
    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
        background: linear-gradient(135deg, var(--shipxpeed-light) 0%, white 100%);
    }

    .empty-state i {
        font-size: 4rem;
        color: var(--shipxpeed-gray);
        margin-bottom: 1.5rem;
        opacity: 0.7;
    }

    .empty-state h5 {
        color: var(--shipxpeed-dark);
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .empty-state p {
        color: var(--shipxpeed-gray);
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }

    /* Loading Animation */
    .loading-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(99, 102, 241, 0.3);
        border-radius: 50%;
        border-top-color: var(--shipxpeed-primary);
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Enhanced Table Styles */
    .table {
        margin-bottom: 0;
        font-size: 0.9rem;
    }

    .table thead {
        background: linear-gradient(135deg, var(--shipxpeed-dark) 0%, #374151 100%);
        color: white;
    }

    .table thead th {
        background: linear-gradient(90deg, #3576e3 0, #306cc9 100%) !important;
        color:white;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.6rem 1rem;
        border: none;
        font-size: 0.7rem;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table tbody tr {
        transition: var(--shipxpeed-transition);
        border-bottom: 1px solid var(--shipxpeed-border-light);
    }

    .table tbody tr:hover {
        background: rgba(99, 102, 241, 0.05);
        box-shadow: inset 4px 0 0 var(--shipxpeed-primary);
    }

    .table tbody td {
        padding: 0.4rem 0.5rem;
        vertical-align: middle;
        border: none;
    }

    /* Enhanced AWB Link */
    .awb-link {
        text-decoration: none;
        color: var(--shipxpeed-primary);
        font-weight: 600;
        transition: var(--shipxpeed-transition);
        /* padding: 0.5rem 0.75rem; */
        border-radius: var(--shipxpeed-radius);
        position: relative;
        overflow: hidden;
        display: inline-block;
    }

    .awb-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(99,102,241,0.1), transparent);
        transition: var(--shipxpeed-transition);
    }

    .awb-link:hover {
        color: var(--shipxpeed-primary-dark);
        background: rgba(99,102,241,0.1);
        text-decoration: none;
        transform: scale(1.05);
    }

    .awb-link:hover::before {
        left: 100%;
    }

    /* Enhanced Order Details Container */
    .order-details-container {
        border-radius: var(--shipxpeed-radius-lg);
        box-shadow: var(--shipxpeed-shadow-lg);
        background: linear-gradient(135deg, var(--shipxpeed-light) 0%, white 100%);
        margin-top: 1rem;
        padding: 2rem;
        border: 2px solid var(--shipxpeed-border);
        position: relative;
        overflow: hidden;
    }

    .order-details-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, var(--shipxpeed-info) 0%, var(--shipxpeed-info-light) 100%);
    }

    .order-details-row td {
        border: none !important;
        padding: 0 !important;
    }

    .table-borderless td {
        border: none !important;
        padding: 0.75rem 1rem !important;
    }

    /* Enhanced Custom Label Button */
    .custom-label-btn {
        font-size: 0.8rem;
        padding: 0.5rem 0.5rem;
        border-radius: 6px;
        background: linear-gradient(135deg, var(--shipxpeed-success) 0%, var(--shipxpeed-success-light) 100%);
        color: white;
        border: none;
        transition: var(--shipxpeed-transition);
        /* font-weight: 600; */
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .custom-label-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: var(--shipxpeed-transition);
    }

    .custom-label-btn:hover {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        transform: translateY(-2px);
        color: white;
    }

    .custom-label-btn:hover::before {
        left: 100%;
    }

    /* Enhanced Dark Button */
    .btn-dark {
        background: linear-gradient(135deg, var(--shipxpeed-dark) 0%, #374151 100%);
        color: white;
        border-radius: var(--shipxpeed-radius-lg);
        border: none;
        transition: var(--shipxpeed-transition);
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(31, 41, 55, 0.3);
        position: relative;
        overflow: hidden;
    }

    .btn-dark::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: var(--shipxpeed-transition);
    }

    .btn-dark:hover {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        box-shadow: 0 6px 20px rgba(31, 41, 55, 0.4);
        transform: translateY(-2px);
        color: white;
    }

    .btn-dark:hover::before {
        left: 100%;
    }

    /* Enhanced Modal Styles */
    .modal-content {
        border-radius: var(--shipxpeed-radius-lg);
        box-shadow: var(--shipxpeed-shadow-xl);
        border: none;
        overflow: hidden;
    }

    .modal-header {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        color: white;
        border: none;
        padding: 1.5rem 2rem;
    }

    .modal-title {
        font-weight: 700;
        font-size: 1.25rem;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
    }

    .modal-body {
        padding: 2rem;
        background: var(--shipxpeed-light);
    }

    .modal-footer {
        border: none;
        padding: 1.5rem 2rem;
        background: white;
    }

    /* Enhanced Color System for Badges */
    .badge.bg-danger {
        background: linear-gradient(135deg, var(--shipxpeed-danger) 0%, var(--shipxpeed-danger-light) 100%) !important;
        color: white !important;
    }
    
    .badge.bg-success {
        background: linear-gradient(135deg, var(--shipxpeed-success) 0%, var(--shipxpeed-success-light) 100%) !important;
        color: white !important;
    }
    
    .badge.bg-warning {
        background: linear-gradient(135deg, var(--shipxpeed-warning) 0%, var(--shipxpeed-warning-light) 100%) !important;
        color: white !important;
    }
    
    .badge.bg-info {
        background: linear-gradient(135deg, var(--shipxpeed-info) 0%, var(--shipxpeed-info-light) 100%) !important;
        color: white !important;
    }
    
    .badge.bg-primary {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%) !important;
        color: white !important;
    }
    
    .badge.bg-secondary {
        background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%) !important;
        color: white !important;
    }
    
    .badge.bg-dark {
        background: linear-gradient(135deg, var(--shipxpeed-dark) 0%, #374151 100%) !important;
        color: white !important;
    }

    /* Badge positioning for buttons */
    .btn.position-relative .badge {
        position: absolute;
        top: -8px;
        right: -8px;
        font-size: 0.7rem;
        min-width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 1;
        font-weight: 700;
        border: 2px solid white;
        box-shadow: var(--shipxpeed-shadow-lg);
        z-index: 10;
        border-radius: 50%;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .pc-container {
            padding: 1rem !important;
        }
        
        .section-container,
        .pc-content {
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .btn-group {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .btn-group .btn {
            margin-right: 0;
            margin-bottom: 0.5rem;
        }
        
        .btn-group-container {
            margin-bottom: 1rem;
        }
        
        .table thead th,
        .table tbody td {
            padding: 0.5rem 0.4rem;
            font-size: 0.8rem;
        }
    }

    /* Enhanced Action Section Styles */
    .enhanced-action-section {
        padding: 1.5rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: var(--shipxpeed-radius-lg);
        border: 1px solid var(--shipxpeed-border-light);
    }

    .action-row {
        min-height: 120px;
    }

    .section-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--shipxpeed-gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.75rem;
    }

    .toggle-buttons-container,
    .order-actions-container {
        padding: 1rem;
        background: rgba(255, 255, 255, 0.8);
        border-radius: var(--shipxpeed-radius);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .enhanced-toggle-btn {
        position: relative;
        overflow: hidden;
        border-radius: var(--shipxpeed-radius) !important;
        transition: var(--shipxpeed-transition);
        min-width: 120px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
    }

    .enhanced-toggle-btn .btn-indicator {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, var(--shipxpeed-primary), var(--shipxpeed-primary-light));
        transform: scaleX(0);
        transition: var(--shipxpeed-transition);
    }

    .enhanced-toggle-btn.active .btn-indicator,
    .enhanced-toggle-btn:hover .btn-indicator {
        transform: scaleX(1);
    }

    .enhanced-action-btn {
        position: relative;
        overflow: hidden;
        border-radius: var(--shipxpeed-radius) !important;
        transition: var(--shipxpeed-transition);
        padding: 0.55rem 0.5rem;
     
    }

    .btn-shine {
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: var(--shipxpeed-transition);
    }

    .enhanced-action-btn:hover .btn-shine {
        left: 100%;
    }

    .enhanced-action-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shipxpeed-shadow-lg);
    }

    /* Enhanced Order Details Styles */
    .enhanced-order-details {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border-radius: var(--shipxpeed-radius-lg);
        box-shadow: var(--shipxpeed-shadow-xl);
        border: 1px solid var(--shipxpeed-border-light);
        overflow: hidden;
        margin: 1rem;
    }

    .details-header {
        background: linear-gradient(135deg, #b7b8ed 0%, #655deb 100%);
        color: white;
        padding: 0.5rem 2rem;
        border-bottom: 1px solid var(--shipxpeed-border);
    }

    .details-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
    }

    .details-close-btn {
        background: rgba(255, 255, 255, 0.1) !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        border-radius: 50% !important;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--shipxpeed-transition);
    }

    .details-close-btn:hover {
        background: rgba(255, 255, 255, 0.2) !important;
        transform: rotate(90deg);
    }

    .details-content {
        padding: 2rem;
    }

    .info-card {
        background: rgba(255, 255, 255, 0.9);
        border-radius: var(--shipxpeed-radius);
        box-shadow: var(--shipxpeed-shadow);
        border: 1px solid var(--shipxpeed-border-light);
        transition: var(--shipxpeed-transition);
        height: 100%;
    }

    .info-card:hover {
        box-shadow: var(--shipxpeed-shadow-lg);
        transform: translateY(-2px);
    }

    .info-card .card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-bottom: 1px solid var(--shipxpeed-border);
        padding: 1rem 1.25rem;
        border-radius: var(--shipxpeed-radius) var(--shipxpeed-radius) 0 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-card .card-header h6 {
        margin: 0;
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--shipxpeed-dark);
    }

    .info-card .card-header i {
        font-size: 1.1rem;
    }

    .info-card .card-body {
        padding: 1.25rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--shipxpeed-border-light);
        transition: var(--shipxpeed-transition);
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-item:hover {
        background: rgba(99, 102, 241, 0.05);
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        border-radius: 4px;
        margin: 0 -0.5rem;
    }

    .info-label {
        font-weight: 600;
        color: var(--shipxpeed-gray);
        font-size: 0.85rem;
        min-width: 120px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-weight: 500;
        color: var(--shipxpeed-dark);
        text-align: right;
        flex: 1;
        font-size: 0.9rem;
    }

    .awb-number {
        font-family: 'Courier New', monospace;
        background: var(--shipxpeed-light);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
    }

    .courier-name {
        color: var(--shipxpeed-primary);
        font-weight: 700;
    }

    .customer-name,
    .warehouse-name {
        color: var(--shipxpeed-success);
        font-weight: 600;
    }

    .details-actions {
        padding: 1.5rem 2rem;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border-top: 1px solid var(--shipxpeed-border);
    }

    .action-divider {
        position: relative;
        text-align: center;
        margin-bottom: 1rem;
    }

    .action-divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--shipxpeed-border), transparent);
    }

    .action-divider-text {
        background: white;
        padding: 0 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--shipxpeed-gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Responsive Design for Enhanced Components */
    @media (max-width: 768px) {
        .action-row {
            flex-direction: column;
            gap: 1rem !important;
            min-height: auto;
        }

        .section-label {
            text-align: center;
        }

        .toggle-buttons-container,
        .order-actions-container {
            text-align: center;
        }

        .details-header .d-flex {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .details-content {
            padding: 1rem;
        }

        .info-item {
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-start;
        }

        .info-value {
            text-align: left;
        }

        .details-actions .d-flex {
            flex-direction: column;
            gap: 0.75rem;
        }
    }

    /* Animation Classes */
    .fade-in {
        animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .slide-in {
        animation: slideIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Loading spinner */
    .loading-spinner {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid rgba(99, 102, 241, 0.3);
        border-radius: 50%;
        border-top-color: var(--shipxpeed-primary);
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }






</style>

<div class="pc-container fade-in" style="background:#646dff26;">
    
<!-- Enhanced Status Filter Buttons -->
<!-- <div class="section-container slide-in" style="margin-left:12px;">

    <h5 class="mb-3 text-dark fw-bold">
        <i class="ti ti-filter me-2 text-primary"></i>
        Filter Orders by Status
    </h5>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('seller.order') }}" class="btn btn-outline-primary position-relative" onclick="setActive(this)">
            <i class="ti ti-plus-circle me-2"></i>New Orders
            @if(isset($orderCounts['new']) && $orderCounts['new'] > 0)
                <span class="badge bg-danger ms-1">
                    {{ $orderCounts['new'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('seller.courier.Assigned') }}" class="btn btn-primary position-relative active" onclick="setActive(this)">
            <i class="ti ti-truck me-2"></i>Courier Assigned
            @if(isset($orderCounts['assigned']) && $orderCounts['assigned'] > 0)
                <span class="badge bg-success ms-1">
                    {{ $orderCounts['assigned'] }}
                </span>
            @endif
        </a>
        
        <a href="{{ route('seller.courier.Cancelled') }}" class="btn btn-outline-danger position-relative" onclick="setActive(this)">
            <i class="ti ti-x-circle me-2"></i>Cancelled
            @if(isset($orderCounts['cancelled']) && $orderCounts['cancelled'] > 0)
                <span class="badge bg-warning ms-1">
                    {{ $orderCounts['cancelled'] }}
                </span>
            @endif
        </a>

        <a href="{{ route('seller.courier.InTransit') }}" class="btn btn-outline-info position-relative" onclick="setActive(this)">
            <i class="ti ti-truck-loading me-2"></i>In Transit
            @if(isset($orderCounts['in_transit']) && $orderCounts['in_transit'] > 0)
                <span class="badge bg-info ms-1">
                    {{ $orderCounts['in_transit'] }}
                </span>
            @endif
        </a>

        <a href="{{ route('seller.courier.OutForDelivery') }}" class="btn btn-outline-warning position-relative" onclick="setActive(this)">
            <i class="ti ti-truck-delivery me-2"></i>Out For Delivery
            @if(isset($orderCounts['out_for_delivery']) && $orderCounts['out_for_delivery'] > 0)
                <span class="badge bg-warning ms-1">
                    {{ $orderCounts['out_for_delivery'] }}
                </span>
            @endif
        </a>

        <a href="{{ route('seller.courier.Delivered') }}" class="btn btn-outline-success position-relative" onclick="setActive(this)">
            <i class="ti ti-circle-check me-2"></i>Delivered
            @if(isset($orderCounts['delivered']) && $orderCounts['delivered'] > 0)
                <span class="badge bg-success ms-1">
                    {{ $orderCounts['delivered'] }}
                </span>
            @endif
        </a>

        <a href="{{ route('seller.courier.NDR') }}" class="btn btn-outline-warning position-relative" onclick="setActive(this)">
            <i class="ti ti-alert-triangle me-2"></i>NDR
            @if(isset($orderCounts['ndr']) && $orderCounts['ndr'] > 0)
                <span class="badge bg-warning ms-1">
                    {{ $orderCounts['ndr'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('seller.courier.RTO') }}" class="btn btn-outline-danger position-relative" onclick="setActive(this)">
            <i class="ti ti-arrow-back me-2"></i>RTO
            @if(isset($orderCounts['rto']) && $orderCounts['rto'] > 0)
                <span class="badge bg-danger ms-1">
                    {{ $orderCounts['rto'] }}
                </span>
            @endif
        </a>

        <a href="{{ route('seller.courier.all') }}" class="btn btn-outline-secondary position-relative" onclick="setActive(this)">
            <i class="ti ti-list me-2"></i>All Orders
            @if(isset($orderCounts['all']) && $orderCounts['all'] > 0)
                <span class="badge bg-secondary ms-1">
                    {{ $orderCounts['all'] }}
                </span>
            @endif
        </a>
        <a href="{{ route('seller.courier.other') }}" class="btn btn-outline-dark position-relative" onclick="setActive(this)">
            <i class="ti ti-search-off me-2"></i>Other
            @if(isset($orderCounts['other']) && $orderCounts['other'] > 0)
                <span class="badge bg-dark ms-1">
                    {{ $orderCounts['other'] }}
                </span>
            @endif
        </a>
    </div>
</div> -->

    <div class="section-container slide-in" style="margin-left:12px;">
        <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center mb-2">
            <h5 class="mb-0 text-dark fw-bold d-flex align-items-center" style="font-size: 1.1rem; padding-bottom: 0.5rem;">
                <i class="fas fa-filter me-2 text-primary opacity-75"></i>
                Filter by Status
            </h5>
        </div>

        <div class="status-btns-container w-100">
            <a href="{{ route('seller.order') }}" class="status-btn-premium btn " data-bs-toggle="tooltip" title="View new orders">
                <span class="status-btn-content"><i class="fas fa-list me-2"></i>New Orders</span>
            </a>
            <a href="{{ route('seller.courier.Assigned') }}" class="status-btn-premium btn active" data-bs-toggle="tooltip" onclick="setActive(this)" title="Orders assigned to courier">
                <span class="status-btn-content"><i class="fas fa-truck me-2 text-success"></i>Courier Assigned</span>
            </a>
            <a href="{{ route('seller.courier.InTransit') }}" class="status-btn-premium btn" data-bs-toggle="tooltip" title="Orders in transit">
                <span class="status-btn-content"><i class="fas fa-shipping-fast me-2 text-info"></i>In Transit</span>
            </a>
            <a href="{{ route('seller.courier.OutForDelivery') }}" class="status-btn-premium btn" data-bs-toggle="tooltip" title="Orders out for delivery">
                <span class="status-btn-content"><i class="fas fa-map-marker-alt me-2 text-primary"></i>Out For Delivery</span>
            </a>
            <a href="{{ route('seller.courier.Delivered') }}" class="status-btn-premium btn" data-bs-toggle="tooltip" title="Successfully delivered orders">
                <span class="status-btn-content"><i class="fas fa-check-circle me-2 text-success"></i>Delivered</span>
            </a>
            <a href="{{ route('seller.courier.Cancelled') }}" class="status-btn-premium btn" data-bs-toggle="tooltip" title="Cancelled orders">
                <span class="status-btn-content"><i class="fas fa-times-circle me-2 text-danger"></i>Cancelled</span>
            </a>
            <a href="{{ route('seller.courier.NDR') }}" class="status-btn-premium btn" data-bs-toggle="tooltip" title="Non-delivery report orders">
                <span class="status-btn-content"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>NDR</span>
            </a>
            <a href="{{ route('seller.courier.RTO') }}" class="status-btn-premium btn" data-bs-toggle="tooltip" title="Return to origin orders">
                <span class="status-btn-content"><i class="fas fa-undo me-2 text-danger"></i>RTO</span>
            </a>
            <a href="#" class="status-btn-premium btn" data-bs-toggle="tooltip" title="Other status orders">
                <span class="status-btn-content"><i class="fas fa-ellipsis-h me-2 text-secondary"></i>Other</span>
            </a>
        </div>
        <style>
            .status-btns-container {
                display: flex;
                flex-wrap: wrap;
                gap: 0.7rem;
                width: 100%;
                justify-content: flex-start;
            }
            .status-btn-premium {
                border-radius: 16px;
                border-width: 2px;
                border-style: solid;
                background: linear-gradient(117deg, #f8fafc 60%, #e9eaea 100%);
                color: #28304b;
                font-weight: 600;
                font-size: 0.98rem;
                box-shadow: 0 2px 10px -4px rgba(60,62,100,0.09);
                transition: 
                    background .18s,
                    box-shadow .16s,
                    color .14s,
                    border-color .17s,
                    transform .18s;
                position: relative;
                min-width: 0;
                flex: 1 1 0;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 0;
                height: auto;
                max-width: 100%;
                width: 100%;
            }
            .status-btn-content {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                height: 100%;
                padding: 0.7rem 1.1rem 0.7rem 0.9rem;
                font-size: inherit;
                font-weight: inherit;
            }
            .status-btn-premium:hover, 
            .status-btn-premium:focus {
                background: linear-gradient(117deg, #f3f6fa 0%, #dce7fa 100%);
                color: #223044;
                border-color: var(--shipxpeed-primary, #6376f1);
                box-shadow: 0 6px 19px -7px rgba(60,62,100,0.18);
                transform: translateY(-2px) scale(1.03);
            }
            .status-btn-premium .badge {
                font-size: 0.93em;
                font-weight: 600;
                letter-spacing: 0.01em;
                box-shadow: 0 3px 10px -2px rgba(60,62,100,0.14);
            }

            /* LAPTOP/DESKTOP VIEW */
            @media (min-width: 768px) {
                .status-btns-container {
                    display: flex;
                    flex-wrap: nowrap;
                    width: 100%;
                    gap: 0.7rem;
                    height: 36px;
                    align-items: stretch;
                }
                .status-btn-premium {
                    min-width: 0;
                    width: 100%;
                    flex: 1 1 0;
                    max-width: 100%;
                    border-radius: 6px !important;
                    font-size: 0.81rem !important;
                    height: 36px;
                    padding: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .status-btn-content {
                    min-width: 0;
                    width: 100%;
                    padding: 0 0.9rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 0.81rem !important;
                    height: 36px;
                    line-height: 1;
                }
                .status-btn-content i {
                    font-size: 1.08em;
                    line-height: 1;
                    align-self: center;
                }
            }

            @media (max-width: 991.98px) {
                .status-btn-premium {
                    font-size: 0.93rem;
                }
            }

            /* MOBILE VIEW: 3 in a row, grid display, padding 0.2rem 0.2rem on inside btn content*/
            @media (max-width: 767.98px) {
                .status-filter-row {
                    padding: 1.1rem 1rem !important;
                }
                .status-btns-container {
                    display: grid;
                    grid-template-columns: repeat(3, 1fr);
                    gap: 0.5rem 0.5rem;
                    height: auto;
                }
                .status-btn-premium {
                    font-size: 0.91rem;
                    border-radius: 16px !important;
                    padding: 0;
                    width: 100%;
                    max-width: 100%;
                    flex: none;
                    height: auto;
                }
                .status-btn-content {
                    padding: 0.2rem 0.2rem;
                    width: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: inherit;
                    height: 100%;
                }
                .status-btn-premium .badge {
                    min-width: 1.6rem !important;
                }
            }
            @media (max-width: 500px) {
                .status-btns-container {
                    grid-template-columns: repeat(2, 1fr);
                }
            }
        </style>
    </div>




    <!-- Enhanced Orders Table Section -->
    <div class="table-container mt-4">
        <!-- Enhanced Controls Section Above Table -->
        <div class="enhanced-controls-section">
            <div class="controls-row">
                <!-- Date Filter Section -->
                <div class="date-filter-container">
                    <form method="GET" action="{{ route('seller.courier.Assigned') }}" class="date-filter-form">
                        <div class="date-inputs-group">
                            <input type="date" name="date_from" class="enhanced-date-input" 
                                   value="{{ request('date_from') }}" placeholder="dd-mm-yyyy">
                            <span class="date-separator">to</span>
                            <input type="date" name="date_to" class="enhanced-date-input" 
                                   value="{{ request('date_to') }}" placeholder="dd-mm-yyyy">
                        </div>
                        <button type="submit" class="enhanced-filter-btn">
                            <i class="ti ti-search me-1"></i> Filter
                        </button>
                        @if(request('date_from') || request('date_to'))
                            <a href="{{ route('seller.courier.Assigned') }}" class="enhanced-clear-btn">
                                <i class="ti ti-x me-1"></i> Clear
                            </a>
                            <a href="{{ route('seller.orders.export-assigned-excel') }}?date_from={{ request('date_from') }}&date_to={{ request('date_to') }}" class="enhanced-export-btn">
                                <i class="ti ti-file-excel me-1"></i> Export Filtered
                            </a>
                        @else
                            <a href="{{ route('seller.orders.export-assigned-excel') }}" class="enhanced-export-btn">
                                <i class="ti ti-file-excel me-1"></i> Export Excel
                            </a>
                        @endif
                    </form>
                </div>

                <!-- Action Controls Section -->
                <div class="action-controls-container">
                    <div class="action-controls-group">
                        <!-- Selection Counter -->
                        <div class="selection-counter-section">
                            <span class="selection-counter" id="selectionCounter" style="display: none;">
                                <i class="ti ti-check-circle me-1"></i> 
                                <span id="selectionCount">0</span> selected
                            </span>
                        </div>

                        <!-- Refresh Button -->
                        <button class="enhanced-control-btn refresh-btn" title="Refresh" onclick="location.reload()">
                            <i class="ti ti-refresh"></i>
                        </button>

                        <!-- Settings Dropdown -->
                        <div class="dropup">
                            <button class="enhanced-control-btn settings-btn" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-settings"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end enhanced-dropdown">
                                <li><a class="dropdown-item" href="#"><i class="ti ti-user me-2"></i>Profile Settings</a></li>
                                <li><a class="dropdown-item" href="#"><i class="ti ti-table me-2"></i>Table Preferences</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#"><i class="ti ti-logout me-2"></i>Logout</a></li>
                            </ul>
                        </div>

                        <!-- Bulk Actions Dropdown -->
                        <div class="dropup">
                            <button class="enhanced-control-btn bulk-actions-btn" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-list me-1"></i>
                                Bulk Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end enhanced-dropdown">
                                <li><a class="dropdown-item" href="{{ route('seller.orders.export-assigned-excel') }}{{ request()->has('date_from') || request()->has('date_to') ? '?' . http_build_query(request()->only(['date_from', 'date_to'])) : '' }}">
                                    <i class="ti ti-file-excel me-2"></i>Export Orders to Excel
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" id="bulkCustomLabelBtn">
                                    <i class="ti ti-tags me-2"></i>Generate Custom Labels (Bulk)
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" id="clearSelectionsBtn">
                                    <i class="ti ti-x-circle me-2"></i>Clear All Selections
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Table -->
        <div class="table-responsive">
            <table class="table table-hover text-center mb-0">
                <thead class="enhanced-table-head">
                    <tr class="table-header-row">
                        <th class="checkbox-col"><input type="checkbox" id="select-all" class="form-check-input enhanced-checkbox"></th>
                        <th class="order-col">
                            <div class="header-content">
                                <i class="ti ti-hash header-icon"></i>
                                <span class="header-text">ORDER ID</span>
                                
                            </div>
                        </th>
                        <th class="order-col">
                            <div class="header-content">
                                <i class="ti ti-barcode"></i>
                                <span class="header-text">AWB NUMBER</span>
                                
                            </div>
                        </th>
                        <th class="product-col">
                            <div class="header-content">
                                <i class="ti ti-package header-icon"></i>
                                <span class="header-text">PRODUCT DETAILS</span>
                            </div>
                        </th>
                        <th class="payment-col">
                            <div class="header-content">
                                <i class="ti ti-currency-rupee header-icon"></i>
                                <span class="header-text">PAYMENT</span>
                            </div>
                        </th>
                        <th class="collectable-col">
                            <div class="header-content">
                                <i class="ti ti-wallet header-icon"></i>
                                <span class="header-text">COLLECTABLE</span>
                            </div>
                        </th>
                       
                        <th class="customer-col">
                            <div class="header-content">
                                <i class="ti ti-user header-icon"></i>
                                <span class="header-text">CUSTOMER</span>
                            </div>
                        </th>
                       
                        <th class="download-col">
                            <div class="header-content">
                                <i class="ti ti-download header-icon"></i>
                                <span class="header-text">DOWNLOAD LABEL</span>
                            </div>
                        </th>
                        <th class="action-col">
                            <div class="header-content">
                                <i class="ti ti-settings header-icon"></i>
                                <span class="header-text">ACTION</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                        @forelse ($orders as $order)
                        <tr class="order-row" data-order-id="{{ $order->id }}" id="order-row-{{ $order->id }}">
                            <td><input type="checkbox" class="order-checkbox form-check-input" value="{{ $order->id }}"></td>

                            <td style="font-size:0.82rem;">
                               
                                            @if($order->seller_id == 433)
                     
                            <span class="fw-bold text-primary">{{ $order->customer_order_id }}</span>
                            @else
                            <span class="fw-bold text-primary">{{ $order->order_number }}</span>
                            
                            @endif
                            </td>
                        
                            <td>
                                <a href="javascript:void(0)" class="awb-link fw-medium" data-order-id="{{ $order->id }}" 
                                   onclick="toggleOrderDetails(this)">
                                    {{ $order->awb_number ?? 'N/A' }}
                                </a>
                                <div>
                                    <span 
                                        class="badge"
                                        style="
                                            background-color: #e3f0ff;
                                            color: #2461a8;
                                            font-size: 11px;
                                            font-weight: 500;
                                            border-radius: 10px;
                                            padding: 2px 10px;
                                            letter-spacing: .02em;
                                        "
                                    >{{ $order->shipping_status ?? 'Pending' }}</span>
                                </div>
                            </td>

                            @php
                                $items = is_array($order->order_items) 
                                    ? $order->order_items 
                                    : json_decode($order->order_items, true);

                                $firstItem = $items[0] ?? null;
                                $allNames = collect($items)->map(function ($item) {
                                    return ($item['name'] ?? 'N/A') . ' (Qty: ' . ($item['quantity'] ?? '1') . ')';
                                })->implode(', ');

                                // Limit to 20 words for the first product name
                                if ($firstItem) {
                                    $words = explode(' ', $firstItem['name']);
                                    $limitedWords = implode(' ', array_slice($words, 0, 3));
                                    if (count($words) > 3) {
                                        $limitedWords .= '...';
                                    }
                                }
                            @endphp

                            <td class="align-middle" style=" min-width:140px; max-width:200px; padding:4px 6px;">
                                @if ($firstItem)
                                    <div class="d-flex align-items-center gap-2" 
                                        style="min-width:0;">
                                      
                                        <div class="flex-grow-1" style="min-width:0;">
                                            <span class=" d-inline-block text-truncate" title="{{ $allNames }}" style="max-width:110px;font-size:.83rem;">{{ $limitedWords }}</span>
                                            @if (count($items) > 1)
                                                <span class="badge bg-info-subtle text-info ms-1 px-1 py-0 small" 
                                                    style="font-size:.72rem;vertical-align:middle;">
                                                    +{{ count($items) - 1 }}
                                                </span>
                                            @endif
                                            <div class="d-flex flex-row align-items-center">
                                                <span class="badge bg-light text-dark px-2 py-0" style="font-size:.75rem;letter-spacing:.01em;">
                                                    Qty: <span class="fw-semibold">{{ $firstItem['qty'] ?? '1' }}</span>
                                                </span>
                                                <span class="badge bg-gradient px-2 py-0"
                                                    style="color:black;background:linear-gradient(90deg,#6366f1 45%,#06b6d4 100%);font-size:.75rem;border-radius:5px;">
                                                    weight : 
                                                    <span>{{ (float) $order->package_weight }}gms</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-muted py-1 px-2 small" style="font-style: italic; border-radius:7px;">No items</div>
                                @endif
                            </td>

                            <td class="align-middle" style="vertical-align:middle; min-width:150px; max-width:220px; padding:6px 8px;">
                                <div class="d-flex flex-column align-items-center justify-content-center w-100" style="min-width:0;">
                                    <span class="fw-bold text-dark text-center" style="font-size:0.82rem;">
                                        ₹{{ number_format($order->order_amount, 2) }}
                                    </span>
                                    @php
                                        $paymentType = strtolower($order->payment_type);
                                        $label = strtoupper($order->payment_type);
                                        $icon = $paymentType === 'cod' ? 'ti ti-cash' : 'ti ti-credit-card';
                                    @endphp
                                    @if ($label === 'COD')
                                        <small class="text-success d-flex align-items-center justify-content-center text-center" style="font-size:.85rem;">
                                            <i class="ti ti-cash me-1" style="font-size:1rem;"></i>COD
                                        </small>
                                    @else
                                        <small class="text-primary d-flex align-items-center justify-content-center text-center" style="font-size:.85rem;">
                                            <i class="ti ti-credit-card me-1" style="font-size:1rem;"></i>Prepaid
                                        </small>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <span class="badge" style="font-size: 0.79rem; border-radius:6px; padding: 0.37em 0.65em; background: transparent; border: 1px solid #bcbcbc; color: #198754;">
                                    ₹{{ number_format($order->collectable_amount, 2) }}
                                </span>
                            </td>

                            <!-- <td>
                                @php
                                $paymentType = strtolower($order->payment_type);
                                $badgeClass = $paymentType === 'cod' ? 'bg-warning' : 'bg-info';
                                $icon = $paymentType === 'cod' ? 'ti-cash' : 'ti-credit-card';
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    <i class="{{ $icon }} me-1"></i>{{ strtoupper($order->payment_type) }}
                                </span>
                            </td> -->

                            @php
                            $consignee = is_array($order->consignee) 
                                ? $order->consignee 
                                : json_decode($order->consignee, true);
                            @endphp
                            <td>
                                <div class="customer-info">
                                    <div class="customer-name text-wrap">{{ $consignee['name'] ?? 'N/A' }}</div>
                                    @if(isset($consignee['phone']))
                                        <small class="customer-details text-muted">{{ $consignee['phone'] }}</small>
                                    @endif
                                </div>
                            </td>

                            @php
                            $pickup = is_array($order->pickup) 
                                ? $order->pickup 
                                : json_decode($order->pickup, true);
                            @endphp
                            <!-- <td>
                                <span class="badge bg-secondary">{{ $pickup['pincode'] ?? 'N/A' }}</span>
                            </td> -->

                            <!-- <td>
                                <span class="badge bg-info">
                                    <i class="fas fa-weight me-1"></i>{{ $order->package_weight }}kg
                                </span>
                            </td> -->



                            @if($order->all_courier_name === "Amazon_0.5 KG" || $order->courier_id === "amazon_0_5kg" || $order->courier_id === "amazon_2kg" || $order->all_courier_name === "Amazon_2 KG" )
                      
                            <td>
                             <button class="btn btn-sm btn-dark tekipost-open-popup" data-tekipost-order-id="{{ $order->id }}">
                                    Download
                                </button>
                            </td>
                             @elseif($order->courier_id === "parcelx" && in_array($order->all_courier_name, ["Amazon 500gm", "Amazon 1kg", "Amazon 2kg","Amazon 500gm"]))

                            <td>
                             <button class="btn btn-sm btn-dark parcelx-open-popup" data-parcelx-order-id="{{ $order->id }}">
                                    Download
                                </button>
                            </td>
                            @else
                            <td>
                                <button class="custom-label-btn" data-order-id="{{ $order->id }}">
                                    <i class="fas fa-download me-1"></i>Download
                                </button>
                            </td>
                            @endif
                    

                            <td>
                                <div class="action-dropdown">
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cog "></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <form action="{{ route('courier.cancel.awb') }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                    <input type="hidden" name="awb" value="{{ $order->awb_number }}">
                                                    <input type="hidden" name="courier" value="{{ $order->courier_id }}">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash me-2"></i>Cancel AWB
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <!-- Clone Order -->
                                            <li>
                                                <form action="{{ route('seller.orders.clone') }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                    <button type="submit" class="dropdown-item text-warning">
                                                        <i class="fas fa-copy me-2"></i>Clone Order
                                                    </button>
                                                </form>
                                            </li>

                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="toggleOrderDetails(document.querySelector('[data-order-id=&quot;{{ $order->id }}&quot;]'))">
                                                    <i class="fas fa-eye me-2"></i>View Details
                                                </a>
                                            </li>
                                        </ul>

                                        <ul class="dropdown-menu">
    <!-- Cancel AWB -->
    <li>
        <form action="{{ route('courier.cancel.awb') }}" method="POST" style="display:inline;">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">
            <input type="hidden" name="awb" value="{{ $order->awb_number }}">
            <input type="hidden" name="courier" value="{{ $order->courier_id }}">
            <button type="submit" class="dropdown-item text-danger">
                <i class="fas fa-trash me-2"></i>Cancel AWB
            </button>
        </form>
    </li>

    <li><hr class="dropdown-divider"></li>

    <!-- Clone Order -->
    <li>
        <form action="{{ route('seller.orders.clone') }}" method="POST" style="display:inline;">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">
            <button type="submit" class="dropdown-item text-warning">
                <i class="fas fa-copy me-2"></i>Clone Order
            </button>
        </form>
    </li>

    <li><hr class="dropdown-divider"></li>

    <!-- View Details -->
    <li>
        <a class="dropdown-item" href="#"
           onclick="toggleOrderDetails(document.querySelector('[data-order-id=&quot;{{ $order->id }}&quot;]'))">
            <i class="fas fa-eye me-2"></i>View Details
        </a>
    </li>
</ul>

                                    </div>
                                </div>
                            </td>
                        </tr>






                        
<tr id="details-{{ $order->id }}" class="order-details-row" style="display: none;">
    <td colspan="12">
        <div class="enhanced-order-details">
            <div class="details-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="details-title">
                        <i class="ti ti-file-text me-2"></i>
                        Order Details - #{{ $order->order_number }}
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary details-close-btn" onclick="toggleOrderDetails(document.querySelector('[data-order-id=&quot;{{ $order->id }}&quot;]'))">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            </div>
            
            <div class="details-content">
                <div class="row g-4">
                    <!-- Order Information Card -->
                    <div class="col-md-6">
                        <div class="info-card order-info-card">
                            <div class="card-header">
                                <i class="ti ti-shopping-cart text-primary"></i>
                                <h6>Order Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <span class="info-label">Order Number:</span>
                                    <span class="info-value">{{ $order->order_number }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">AWB Number:</span>
                                    <span class="info-value awb-number">{{ $order->awb_number ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Courier Partner:</span>
                                    <span class="info-value courier-name">{{ $order->all_courier_name ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Payment Type:</span>
                                    <span class="info-value payment-badge">
                                        @php
                                        $paymentClass = strtolower($order->payment_type) === 'cod' ? 'bg-warning' : 'bg-info';
                                        @endphp
                                        <span class="badge {{ $paymentClass }}">{{ ucfirst($order->payment_type) }}</span>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Shipping Status:</span>
                                    <span class="info-value status-badge">
                                        <span class="badge bg-info">{{ $order->shipping_status ?? 'Pending' }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Package Information Card -->
                    <div class="col-md-6">
                        <div class="info-card package-info-card">
                            <div class="card-header">
                                <i class="ti ti-package text-success"></i>
                                <h6>Package Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="info-item">
                                    <span class="info-label">Weight:</span>
                                    <span class="info-value weight-value">{{ $order->package_weight }} gm</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Dimensions:</span>
                                    <span class="info-value dimensions">
                                        {{ $order->package_length ?? 'N/A' }} × 
                                        {{ $order->package_breadth ?? 'N/A' }} × 
                                        {{ $order->package_height ?? 'N/A' }} cm
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Volume:</span>
                                    <span class="info-value volume">
                                        @php
                                        $volume = ($order->package_length && $order->package_breadth && $order->package_height) 
                                            ? ($order->package_length * $order->package_breadth * $order->package_height) 
                                            : 'N/A';
                                        @endphp
                                        {{ $volume !== 'N/A' ? number_format($volume, 2) . ' cm³' : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-4 mt-2">
                    <!-- Consignee Details Card -->
                    <div class="col-md-6">
                        <div class="info-card consignee-info-card">
                            <div class="card-header">
                                <i class="ti ti-user text-info"></i>
                                <h6>Consignee Details</h6>
                            </div>
                            <div class="card-body">
                                @php
                                    $consigneeDetails = is_array($order->consignee) 
                                        ? $order->consignee 
                                        : json_decode($order->consignee, true);
                                @endphp
                                <div class="info-item">
                                    <span class="info-label">Name:</span>
                                    <span class="info-value customer-name">{{ $consigneeDetails['name'] ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone:</span>
                                    <span class="info-value customer-phone">
                                        <a href="tel:{{ $consigneeDetails['phone'] ?? '' }}" class="text-decoration-none">
                                            {{ $consigneeDetails['phone'] ?? 'N/A' }}
                                        </a>
                                    </span>
                                </div>
                                <!-- <div class="info-item">
                                    <span class="info-label">Address:</span>
                                    <span class="info-value customer-address">{{ $consigneeDetails['address'] ?? 'N/A' }}</span>
                                </div>
                                @if(isset($consigneeDetails['address_2']) && $consigneeDetails['address_2'])
                                <div class="info-item">
                                    <span class="info-label">Address 2:</span>
                                    <span class="info-value">{{ $consigneeDetails['address_2'] }}</span>
                                </div>
                                @endif -->

                                <div class="info-item">
                                    <span class="info-label">Address:</span>
                                    <span class="info-value customer-address" style="white-space: pre-line; word-break: break-word;">
                                        {{ $consigneeDetails['address'] ?? 'N/A' }}
                                    </span>
                                </div>
                                @if(isset($consigneeDetails['address_2']) && $consigneeDetails['address_2'])
                                <div class="info-item">
                                    <span class="info-label">Address 2:</span>
                                    <span class="info-value" style="white-space: pre-line; word-break: break-word;">
                                        {{ $consigneeDetails['address_2'] }}
                                    </span>
                                </div>
                                @endif



                                <div class="info-item">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value location-info">
                                        {{ $consigneeDetails['city'] ?? 'N/A' }}, 
                                        {{ $consigneeDetails['state'] ?? 'N/A' }} - 
                                        <span class="badge bg-secondary">{{ $consigneeDetails['pincode'] ?? 'N/A' }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pickup Details Card -->
                    <div class="col-md-6">
                        <div class="info-card pickup-info-card">
                            <div class="card-header">
                                <i class="ti ti-map-pin text-warning"></i>
                                <h6>Pickup Details</h6>
                            </div>
                            <div class="card-body">
                                @php
                                    $pickupDetails = is_array($order->pickup) 
                                        ? $order->pickup 
                                        : json_decode($order->pickup, true);
                                @endphp
                                <div class="info-item">
                                    <span class="info-label">Warehouse:</span>
                                    <span class="info-value warehouse-name">{{ $pickupDetails['warehouse_name'] ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Contact Person:</span>
                                    <span class="info-value pickup-contact">{{ $pickupDetails['name'] ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone:</span>
                                    <span class="info-value pickup-phone">
                                        <a href="tel:{{ $pickupDetails['phone'] ?? '' }}" class="text-decoration-none">
                                            {{ $pickupDetails['phone'] ?? 'N/A' }}
                                        </a>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Address:</span>
                                    <span class="info-value pickup-address" style="white-space: pre-line; word-break: break-word;">{{ $pickupDetails['address'] ?? 'N/A' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Location:</span>
                                    <span class="info-value pickup-location">
                                        {{ $pickupDetails['city'] ?? 'N/A' }}, 
                                        {{ $pickupDetails['state'] ?? 'N/A' }} - 
                                        <span class="badge bg-secondary">{{ $pickupDetails['pincode'] ?? 'N/A' }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Action Buttons Section -->
                <div class="details-actions mt-4">
                    <div class="">
                        <span class="action-divider-text">Quick Actions</span>
                    </div>
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <a href="{{ route('seller.order.track') }}" class="btn btn-primary enhanced-action-btn">
                            <i class="ti ti-map-pin me-2"></i>
                            <span>Track Order</span>
                            <div class="btn-shine"></div>
                        </a>
                        <button class="btn btn-outline-info enhanced-action-btn" onclick="window.print()">
                            <i class="ti ti-printer me-2"></i>
                            <span>Print Details</span>
                            <div class="btn-shine"></div>
                        </button>
                        <button class="btn btn-outline-warning enhanced-action-btn" onclick="navigator.share({title: 'Order {{ $order->order_number }}', text: 'Order details for {{ $order->order_number }}'})">
                            <i class="ti ti-share me-2"></i>
                            <span>Share</span>
                            <div class="btn-shine"></div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </td>
</tr>


                        @empty
                        <tr>
                            <td colspan="12" class="py-5">
                                <div class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox text-muted" style="font-size: 3rem; opacity: 0.5;"></i>
                                        <h5 class="mt-3 text-muted">No Orders Found</h5>
                                        <p class="text-muted">There are no assigned orders to display at the moment.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($orders->count())
                <div class="p-4 border-top bg-light">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="text-muted">
                            <strong>{{ $orders->count() }}</strong> orders displayed
                        </div>
                        <div>
                            {{ $orders->links() }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>







<!-- 

<div class="modal fade" id="tekipost-modal" tabindex="-1" aria-labelledby="tekipost-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Courier Label</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="d-flex justify-content-center gap-2">
                    <a href="#" class="btn btn-primary tekipost-download-label" style="display:none;">
                        Download Label
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.tekipost-open-popup').forEach(button => {
        button.addEventListener('click', function () {
            const orderId = this.getAttribute('data-tekipost-order-id');

            fetch("{{ route('tekipost.label') }}", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                const labelBtn = document.querySelector('.tekipost-download-label');
                if (data.label_url) {
                    labelBtn.style.display = 'inline-block';

                    // Remove old click listeners
                    const newLabelBtn = labelBtn.cloneNode(true);
                    labelBtn.parentNode.replaceChild(newLabelBtn, labelBtn);

                    newLabelBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        const a = document.createElement('a');
                        a.href = data.label_url;
                        a.setAttribute('download', `Order_${orderId}_Label.pdf`);
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    });

                } else {
                    labelBtn.style.display = 'none';
                }

                const modal = new bootstrap.Modal(document.getElementById('tekipost-modal'));
                modal.show();
            })
            .catch(error => {
                console.error("Tekipost fetch error:", error);
                alert("Something went wrong with Tekipost download.");
            });
        });
    });
</script>

 -->

 
<div class="modal fade" id="parcelx-modal" tabindex="-1" aria-labelledby="parcelx-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Courier Label</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="d-flex justify-content-center gap-2">
                    <a href="#" class="btn btn-primary parcelx-download-label" style="display:none;">
                        Download Label
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.parcelx-open-popup').forEach(button => {
        // alert('hello');
        button.addEventListener('click', function () {
            const orderId = this.getAttribute('data-parcelx-order-id');

            fetch("{{ route('parcelx.label') }}", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                const labelBtn = document.querySelector('.parcelx-download-label');
                if (data.label_url) {
                    labelBtn.style.display = 'inline-block';

                    // Remove old click listeners
                    const newLabelBtn = labelBtn.cloneNode(true);
                    labelBtn.parentNode.replaceChild(newLabelBtn, labelBtn);

                    newLabelBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        const a = document.createElement('a');
                        a.href = data.label_url;
                        a.setAttribute('download', `Order_${orderId}_Label.pdf`);
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    });

                } else {
                    labelBtn.style.display = 'none';
                }

                const modal = new bootstrap.Modal(document.getElementById('parcelx-modal'));
                modal.show();
            })
            .catch(error => {
                console.error("Parcelx fetch error:", error);
                alert("Something went wrong with Parcelx download.");
            });
        });
    });
</script>




<div class="modal fade" id="tekipost-modal" tabindex="-1" aria-labelledby="tekipost-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Courier Label</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="d-flex justify-content-center gap-2">
                    <a href="#" class="btn btn-primary tekipost-download-label" style="display:none;">
                        Download Label
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.tekipost-open-popup').forEach(button => {
        // alert('hello');
        button.addEventListener('click', function () {
            const orderId = this.getAttribute('data-tekipost-order-id');

            fetch("{{ route('tekipost.label') }}", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                const labelBtn = document.querySelector('.tekipost-download-label');
                if (data.label_url) {
                    labelBtn.style.display = 'inline-block';

                    // Remove old click listeners
                    const newLabelBtn = labelBtn.cloneNode(true);
                    labelBtn.parentNode.replaceChild(newLabelBtn, labelBtn);

                    newLabelBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        const a = document.createElement('a');
                        a.href = data.label_url;
                        a.setAttribute('download', `Order_${orderId}_Label.pdf`);
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    });

                } else {
                    labelBtn.style.display = 'none';
                }

                const modal = new bootstrap.Modal(document.getElementById('tekipost-modal'));
                modal.show();
            })
            .catch(error => {
                console.error("Tekipost fetch error:", error);
                alert("Something went wrong with Tekipost download.");
            });
        });
    });
</script>




<div class="modal fade" id="smartship-modal" tabindex="-1" aria-labelledby="smartship-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Smartship Courier Info</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Courier Name</th>
                            {{-- <th>Status</th> --}}
                        </tr>
                    </thead>
                    <tbody id="smartship-table-body">
                        <tr>
                            <td colspan="2">Loading...</td>
                        </tr>
                    </tbody>
                </table>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="#" class="btn btn-primary smartship-download-label" download="label.pdf">Download Label</a>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    document.querySelectorAll('.smartship-open-popup').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-smartship-order-id');

            fetch("{{ route('smartship.label') }}", {
                    method: "POST"
                    , headers: {
                        'Content-Type': 'application/json'
                        , 'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                    , body: JSON.stringify({
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    // Update modal table content
                    document.getElementById('smartship-table-body').innerHTML = `
                <tr>
                    <td>${data.courier}</td>
                </tr>
            `;

                    // Update label download button
                    const labelBtn = document.querySelector('.smartship-download-label');
                    if (data.label_url) {
                        labelBtn.href = data.label_url;
                        labelBtn.style.display = 'inline-block';
                    } else {
                        labelBtn.href = '#';
                        labelBtn.style.display = 'none';
                    }

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('smartship-modal'));
                    modal.show();
                })
                .catch(error => {
                    console.error("Smartship fetch error:", error);
                    alert("Something went wrong with Smartship download.");
                });
        });
    });

</script>

{{-- smartship --}}










<div class="modal fade" id="downloadModaldelhivery_b2c" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Delhivery Label</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p><strong>Courier:</strong> <span id="courier-name">Delhivery</span></p>
                <p><strong>Processed:</strong> <span id="processed-status">1</span></p>

                <!-- Label Preview Frame -->
                <iframe id="label-preview-frame" style="width:100%; height:400px; display: none;" frameborder="0">
                </iframe>

                <!-- Download Button -->
                <div class="mt-3">
                    <a href="#" id="download-label-btn" class="btn btn-primary" target="_blank" download="Delhivery_Label.pdf" style="display: none;">
                        Download Label PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.querySelectorAll('.open-popup-delhivery_b2c').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('delhivery_b2cdata-order-id');

            fetch("{{ route('get.courier.info.delhivery_b2c') }}", {
                    method: "POST"
                    , headers: {
                        'Content-Type': 'application/json'
                        , 'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                    , body: JSON.stringify({
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    // Set courier info
                    document.getElementById('courier-name').textContent = data.courier;
                    document.getElementById('processed-status').textContent = data.processed;

                    // Use direct PDF URL
                    if (data.pdf_base64) {
                        const pdfUrl = data.pdf_base64;

                        document.getElementById('label-preview-frame').src = pdfUrl;
                        document.getElementById('label-preview-frame').style.display = 'block';

                        const downloadBtn = document.getElementById('download-label-btn');
                        downloadBtn.href = pdfUrl;
                        downloadBtn.style.display = 'inline-block';
                    }

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('downloadModaldelhivery_b2c'));
                    modal.show();
                })
                .catch(error => {
                    console.error("Error fetching label:", error);
                    alert("Something went wrong.");
                });
        });
    });

</script>



<div class="modal fade" id="shadowfaxDownloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Shadowfax Label</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p><strong>Courier:</strong> <span id="courier-name"></span></p>
                <p><strong>Status:</strong> <span id="courier-status"></span></p>

                <a href="#" class="btn btn-primary shadowfaxdownload-label" target="_blank" download>Download Label</a>
            </div>
        </div>
    </div>
</div>


<script>
    document.querySelectorAll('.open-popup-shadowfax').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('shadowfaxdata-order-id');

            fetch("{{ route('get.courier.info.shadowfax') }}", {
                    method: "POST"
                    , headers: {
                        'Content-Type': 'application/json'
                        , 'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                    , body: JSON.stringify({
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    // Set courier info
                    document.getElementById('courier-name').textContent = data.courier || 'Shadowfax';
                    document.getElementById('courier-status').textContent = data.processed ? 'Processed' : 'Pending';

                    // Set label download
                    const labelBtn = document.querySelector('.shadowfaxdownload-label');
                    if (data.label_url) {
                        labelBtn.href = data.label_url;
                        labelBtn.style.display = 'inline-block';
                    } else {
                        labelBtn.href = '#';
                        labelBtn.style.display = 'none';
                    }

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('shadowfaxDownloadModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error("Error fetching label:", error);
                    alert("Something went wrong.");
                });
        });
    });

</script>

{{-- shadowfaxDownloadModal --}}


{{-- xpressbees --}}
<div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Courier List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Courier Name</th>
                            <th>Processed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Delhivery</td>
                            <td>1</td>
                        </tr>
                    </tbody>
                </table>
                <div class="d-flex justify-content-center gap-2 mt-3">

                    <a href="#" class="btn btn-primary download-manifest" target="_blank">Download Manifest</a>
                    <a href="#" class="btn btn-primary download-label" target="_blank">Download Label</a>

                </div>
            </div>
        </div>
    </div>
</div>



<script>
    document.querySelectorAll('.open-popup').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');

            fetch("{{ route('get.courier.info') }}", {
                    method: "POST"
                    , headers: {
                        'Content-Type': 'application/json'
                        , 'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                    , body: JSON.stringify({
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    document.querySelector('#downloadModal tbody').innerHTML = `
                <tr>
                    <td>${data.courier}</td>
                    <td>${data.processed}</td>
                </tr>
            `;

                    // Update links dynamically
                    const manifestBtn = document.querySelector('.download-manifest');
                    const labelBtn = document.querySelector('.download-label');

                    if (data.manifest_url) {
                        manifestBtn.href = data.manifest_url;
                        manifestBtn.style.display = 'inline-block';
                    } else {
                        manifestBtn.href = '#';
                        manifestBtn.style.display = 'none';
                    }

                    if (data.label_url) {
                        labelBtn.href = data.label_url;
                        labelBtn.style.display = 'inline-block';
                    } else {
                        labelBtn.href = '#';
                        labelBtn.style.display = 'none';
                    }

                    const modal = new bootstrap.Modal(document.getElementById('downloadModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error("Error fetching courier info:", error);
                    alert("Something went wrong.");
                });
        });
    });

</script>
{{-- xpressbees --}}



<script>
    document.getElementById('courier-form').addEventListener('submit', function(e) {

        let selected = Array.from(document.querySelectorAll('.order-checkbox:checked'))
            .map(checkbox => checkbox.value);
        if (selected.length === 0) {
            e.preventDefault();
            alert('Please select at least one order.');
            return;
        }

        document.getElementById('selected-orders').value = selected.join(',');
    });

    // Select all toggle
    document.getElementById('select-all').addEventListener('change', function() {
        let checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

</script>

<!-- Bulk Custom Label Generation Modal -->
<div class="modal fade" id="bulkCustomLabelModal" tabindex="-1" aria-labelledby="bulkCustomLabelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkCustomLabelModalLabel">Generate Bulk Custom Labels</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="alert alert-info">
                        <strong>Selected Orders:</strong> <span id="selectedOrdersCount">0</span>
                    </div>
                </div>
                <div id="bulkLabelContainer"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printBulkLabelsBtn">Print All Labels</button>
                <button type="button" class="btn btn-success" id="downloadBulkLabelsBtn">Download PDF</button>
            </div>
        </div>
    </div>
</div>











<!-- Bulk Cancel Form -->
<form id="bulkCancelForm" method="POST" action="{{ route('seller.order.bulk-cancel') }}" style="display: none;">
    @csrf
</form>

<!-- Bulk Label Download Form -->
<form id="bulkLabelForm" method="POST" action="{{ route('seller.order.bulk-label-download') }}" target="_blank" style="display: none;">
    @csrf
</form>





<script>
    // BULK CANCEL
    document.getElementById('bulkCancelBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const selected = document.querySelectorAll('.order-checkbox:checked');

        if (selected.length === 0) {
            alert('Please select at least one order to cancel.');
            return;
        }

        if (confirm(`Are you sure you want to cancel ${selected.length} orders?`)) {
            const form = document.getElementById('bulkCancelForm');
            form.innerHTML = '@csrf'; // Reset in case already submitted

            selected.forEach(el => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order_ids[]';
                input.value = el.value;
                form.appendChild(input);
            });

            form.submit();
        }
    });

    // BULK LABEL DOWNLOAD
    document.getElementById('bulkLabelDownloadBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const selected = document.querySelectorAll('.order-checkbox:checked');

        if (selected.length === 0) {
            alert('Please select at least one order to download labels.');
            return;
        }

        const form = document.getElementById('bulkLabelForm');
        form.innerHTML = '@csrf'; // Clear previous

        selected.forEach(el => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'order_ids[]';
            input.value = el.value;
            form.appendChild(input);
        });

        form.submit();
    });

</script>




<script>
    function toggleOrderDetails(element) {
        const orderId = element.getAttribute('data-order-id');
        const detailsRow = document.getElementById('details-' + orderId);
        
        if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
            // Hide all other open details first
            document.querySelectorAll('.order-details-row').forEach(row => {
                row.style.display = 'none';
            });
            
            // Show the clicked order details
            detailsRow.style.display = 'table-row';
            
            // Update AWB link appearance
            document.querySelectorAll('.awb-link').forEach(link => {
                link.classList.remove('text-primary', 'fw-bold');
            });
            element.classList.add('text-primary', 'fw-bold');
        } else {
            // Hide the details if already open
            detailsRow.style.display = 'none';
            element.classList.remove('text-primary', 'fw-bold');
        }
    }
</script>

<script>
    // Custom Label Generation Functions
    function generateCustomLabel(orderId) {
        // Directly download the label PDF
        window.location.href = `/seller/orders/${orderId}/custom-label/download`;
    }

    function generateBulkCustomLabels() {
        const selected = document.querySelectorAll('.order-checkbox:checked');
        
        if (selected.length === 0) {
            alert('Please select at least one order to generate labels.');
            return;
        }

        const orderIds = Array.from(selected).map(cb => cb.value);
        
        // Create a form to submit order IDs for direct download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/seller/orders/bulk-custom-labels/download';
        
        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);
        
        // Add order IDs
        orderIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'order_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        // Submit form to download
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function setupBulkLabelActions(orderIds) {
        // Print functionality for bulk
        document.getElementById('printBulkLabelsBtn').onclick = function() {
            const printContent = document.getElementById('bulkLabelContainer').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print Bulk Labels</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .label-container { max-width: 800px; margin: 0 auto; }
                        .page-break { page-break-before: always; }
                        @media print {
                            body { margin: 0; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="label-container">${printContent}</div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        };

        // Download PDF functionality for bulk
        document.getElementById('downloadBulkLabelsBtn').onclick = function() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/seller/orders/bulk-custom-labels/download';
            form.target = '_blank';
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);
            
            orderIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order_ids[]';
                input.value = id;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        };
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Individual custom label buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('custom-label-btn')) {
                const orderId = e.target.getAttribute('data-order-id');
                generateCustomLabel(orderId);
            }
        });

        // Bulk custom label button
        document.getElementById('bulkCustomLabelBtn').addEventListener('click', function(e) {
            e.preventDefault();
            generateBulkCustomLabels();
        });

        // Clear selections button (single-click)
        // NOTE: unified handler defined later to avoid duplicate listeners

        // Row selection functionality with pagination persistence
        let selectedOrders = JSON.parse(localStorage.getItem('selectedOrders') || '[]');

        // Function to save selected orders to localStorage
        function saveSelectedOrders() {
            localStorage.setItem('selectedOrders', JSON.stringify(selectedOrders));
            updateSelectionCounter();
        }

        // Function to update selection counter
        function updateSelectionCounter() {
            const counter = document.getElementById('selectionCounter');
            const countSpan = document.getElementById('selectionCount');
            
            if (counter && countSpan) {
                if (selectedOrders.length > 0) {
                    counter.style.display = 'inline-block';
                    countSpan.textContent = selectedOrders.length;
                } else {
                    counter.style.display = 'none';
                }
            }
        }

        // Function to restore selection state on page load
        function restoreSelectionState() {
            selectedOrders.forEach(orderId => {
                const row = document.getElementById('order-row-' + orderId);
                const checkbox = document.querySelector('.order-checkbox[value="' + orderId + '"]');
                
                if (row) {
                    row.classList.add('selected');
                }
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }

        // Function to add order to selection
        function addToSelection(orderId) {
            if (!selectedOrders.includes(orderId)) {
                selectedOrders.push(orderId);
                saveSelectedOrders();
            }
        }

        // Function to remove order from selection
        function removeFromSelection(orderId) {
            const index = selectedOrders.indexOf(orderId);
            if (index > -1) {
                selectedOrders.splice(index, 1);
                saveSelectedOrders();
            }
        }

        // Restore selection state when page loads
        restoreSelectionState();
        updateSelectionCounter();

        // Clear selections button handler (single click clears selections and closes any open dropdowns)
        const clearSelectionsBtn = document.getElementById('clearSelectionsBtn');
        if (clearSelectionsBtn) {
            clearSelectionsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                clearAllSelections();
                // close any visible dropdown menus
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                    if (menu.__origParent) {
                        try { menu.__origParent.appendChild(menu); } catch(err) {}
                        delete menu.__origParent;
                    }
                });
            });
        }

        // Row click handler
        document.addEventListener('click', function(e) {
            const row = e.target.closest('tr.order-row');
            if (row) {
                // Don't trigger row selection if clicked on checkbox, button, or link
                if (e.target.type === 'checkbox' || 
                    e.target.type === 'submit' || 
                    e.target.type === 'button' ||
                    e.target.tagName === 'BUTTON' ||
                    e.target.tagName === 'A' ||
                    e.target.closest('button') ||
                    e.target.closest('a')) {
                    return;
                }

                const orderId = row.getAttribute('data-order-id');
                const checkbox = row.querySelector('.order-checkbox');

                // Toggle selection
                if (row.classList.contains('selected')) {
                    row.classList.remove('selected');
                    if (checkbox) checkbox.checked = false;
                    removeFromSelection(orderId);
                } else {
                    row.classList.add('selected');
                    if (checkbox) checkbox.checked = true;
                    addToSelection(orderId);
                }
            }
        });

        // Checkbox change handler
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('order-checkbox')) {
                const row = e.target.closest('tr.order-row');
                const orderId = e.target.value;
                
                if (row) {
                    if (e.target.checked) {
                        row.classList.add('selected');
                        addToSelection(orderId);
                    } else {
                        row.classList.remove('selected');
                        removeFromSelection(orderId);
                    }
                }
            }
        });

        // Handle "Select All" checkbox
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.order-checkbox');
                const rows = document.querySelectorAll('.order-row');
                
                checkboxes.forEach((checkbox, index) => {
                    const row = rows[index];
                    const orderId = checkbox.value;
                    
                    if (this.checked) {
                        checkbox.checked = true;
                        if (row) row.classList.add('selected');
                        addToSelection(orderId);
                    } else {
                        checkbox.checked = false;
                        if (row) row.classList.remove('selected');
                        removeFromSelection(orderId);
                    }
                });
            });
        }

        // Add function to clear all selections (optional)
        window.clearAllSelections = function() {
            selectedOrders = [];
            localStorage.removeItem('selectedOrders');
            document.querySelectorAll('.order-row.selected').forEach(row => {
                row.classList.remove('selected');
            });
            document.querySelectorAll('.order-checkbox:checked').forEach(checkbox => {
                checkbox.checked = false;
            });
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            updateSelectionCounter();
        };

        // Add function to get selected order IDs (useful for bulk operations)
        window.getSelectedOrderIds = function() {
            return selectedOrders;
        };
    });

</script>

<script>
// Enhanced Order Management System
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Enhanced Select All functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const selectionCounter = document.getElementById('selectionCounter');
    const selectionCount = document.getElementById('selectionCount');

    // Track selected orders with persistence
    let selectedOrders = JSON.parse(localStorage.getItem('selectedOrders') || '[]');

    // Initialize UI state
    updateSelectionCounter();
    restoreSelections();

    function updateSelectionCounter() {
        const count = selectedOrders.length;
        if (count > 0) {
            selectionCounter.style.display = 'inline-block';
            selectionCount.textContent = count;
        } else {
            selectionCounter.style.display = 'none';
        }
    }

    function addToSelection(orderId) {
        if (!selectedOrders.includes(orderId)) {
            selectedOrders.push(orderId);
            localStorage.setItem('selectedOrders', JSON.stringify(selectedOrders));
            updateSelectionCounter();
        }
    }

    function removeFromSelection(orderId) {
        selectedOrders = selectedOrders.filter(id => id !== orderId);
        localStorage.setItem('selectedOrders', JSON.stringify(selectedOrders));
        updateSelectionCounter();
    }

    function restoreSelections() {
        selectedOrders.forEach(orderId => {
            const checkbox = document.querySelector(`.order-checkbox[value="${orderId}"]`);
            const row = document.getElementById(`order-row-${orderId}`);
            if (checkbox) {
                checkbox.checked = true;
                if (row) row.classList.add('selected');
            }
        });
    }

    // Select all functionality with visual feedback
    selectAllCheckbox?.addEventListener('change', function() {
        const isChecked = this.checked;
        orderCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
            const row = checkbox.closest('tr');
            if (row) {
                row.classList.toggle('selected', isChecked);
                const orderId = checkbox.value;
                if (isChecked) {
                    addToSelection(orderId);
                } else {
                    removeFromSelection(orderId);
                }
            }
        });
    });

    // Individual checkbox functionality
    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('tr');
            const orderId = this.value;
            
            if (this.checked) {
                if (row) row.classList.add('selected');
                addToSelection(orderId);
            } else {
                if (row) row.classList.remove('selected');
                removeFromSelection(orderId);
                selectAllCheckbox.checked = false;
            }
        });
    });

    // (clear selections handled elsewhere)

    // Enhanced loading states for action buttons
    document.querySelectorAll('.custom-label-btn, .tekipost-open-popup').forEach(button => {
        button.addEventListener('click', function() {
            const originalContent = this.innerHTML;
            this.innerHTML = '<span class="loading-spinner"></span> Processing...';
            this.disabled = true;
            
            // Re-enable after 3 seconds (adjust as needed)
            setTimeout(() => {
                this.innerHTML = originalContent;
                this.disabled = false;
            }, 3000);
        });
    });

    // Add animation classes to elements
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    });

    document.querySelectorAll('.table-container, .section-container').forEach(el => {
        observer.observe(el);
    });

    // Global functions
    window.getSelectedOrderIds = function() {
        return selectedOrders;
    };

    // keep existing clearAllSelections implementation earlier

    console.log('📦 Enhanced Courier Assigned Orders System Loaded Successfully!');
});

// Toggle order details function
function toggleOrderDetails(element) {
    const orderId = element.getAttribute('data-order-id');
    const detailsRow = document.getElementById('details-' + orderId);
    
    if (detailsRow) {
        if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
            detailsRow.style.display = 'table-row';
            element.classList.add('text-success');
        } else {
            detailsRow.style.display = 'none';
            element.classList.remove('text-success');
        }
    }
}

// Ensure dropup dropdowns render above all content by moving menus to body and positioning fixed
document.addEventListener('DOMContentLoaded', function() {
    function hideMenu(menu) {
        if (!menu) return;
        menu.classList.remove('show');
        menu.style.position = '';
        menu.style.left = '';
        menu.style.top = '';
        menu.style.zIndex = '';
        if (menu.__origParent) {
            try { menu.__origParent.appendChild(menu); } catch(e){}
            delete menu.__origParent;
        }
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.dropup [data-bs-toggle="dropdown"]');
        // If clicked a dropup toggle
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const dropup = btn.closest('.dropup');
            if (!dropup) return;
            const menu = dropup.querySelector('.dropdown-menu');
            if (!menu) return;

            // If already open, close it
            if (menu.classList.contains('show')) {
                hideMenu(menu);
                return;
            }

            // Close any other open menus
            document.querySelectorAll('.dropdown-menu.show').forEach(m => hideMenu(m));

            // Remember original parent so we can restore later
            if (!menu.__origParent) menu.__origParent = menu.parentNode;

            // Append to body to escape overflow and stacking contexts
            document.body.appendChild(menu);

            // Make visible to measure
            menu.classList.add('show');
            menu.style.position = 'fixed';
            menu.style.zIndex = '99999';

            // Calculate position above the button
            const btnRect = btn.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();
            const offset = 8; // small gap
            let left = btnRect.right - menuRect.width;
            // keep within viewport
            left = Math.max(8, Math.min(left, window.innerWidth - menuRect.width - 8));
            let top = btnRect.top - menuRect.height - offset;
            // if not enough space above, place below as fallback
            if (top < 8) top = btnRect.bottom + offset;

            menu.style.left = left + 'px';
            menu.style.top = top + 'px';

            // Close on ESC
            function onKey(e) {
                if (e.key === 'Escape') hideMenu(menu);
            }
            document.addEventListener('keydown', onKey);

            // Close on outside click
            setTimeout(() => {
                document.addEventListener('click', function onDocClick(evt) {
                    if (!evt.target.closest('.dropdown-menu') && !evt.target.closest('[data-bs-toggle="dropdown"]')) {
                        hideMenu(menu);
                        document.removeEventListener('click', onDocClick);
                        document.removeEventListener('keydown', onKey);
                    }
                });
            }, 0);
            return;
        }

        // If click elsewhere, hide any body-appended menus
        if (!e.target.closest('.dropdown-menu')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(m => {
                if (m.__origParent) hideMenu(m);
            });
        }
    });
});

</script>

@endsection





