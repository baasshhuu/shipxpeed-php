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
    }

    /* Override default styles to match modern design */
    .pc-container {
        padding: 0.75rem !important;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
        min-height: auto;
    }

    /* Enhanced Toggle Section with glassmorphism */
    .toggle-section {
       

        border-radius: var(--shipxpeed-radius-lg);
        padding: 0.75rem;
        background-color: #646dff26;
        
        margin-left: 15px;
        margin-bottom: 0.75rem;
        box-shadow: var(--shipxpeed-shadow-xl);
        /* border: 1px solid rgba(255, 255, 255, 0.2); */
        position: relative;
    }

    .toggle-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--shipxpeed-primary), transparent);
        opacity: 0.5;
    }

    .toggle-button-section {
        gap: 1rem;
    }

    .toggle-button-section .btn {
        border-radius: var(--shipxpeed-radius);
        font-weight: 600;
        padding: 0.5rem 1rem;
        transition: var(--shipxpeed-transition);
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-size: 0.85rem;
    }

    .toggle-button-section .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: var(--shipxpeed-transition);
    }

    .toggle-button-section .btn:hover::before {
        left: 100%;
    }

    .toggle-button-section .btn-outline-primary {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-dark) 100%);
        border-color: var(--shipxpeed-primary);
        color: white;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .toggle-button-section .btn-outline-primary:hover {
        background: linear-gradient(135deg, var(--shipxpeed-primary-dark) 0%, var(--shipxpeed-primary) 100%);
        border-color: var(--shipxpeed-primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
    }

    .toggle-button-section .btn-success {
        background: linear-gradient(135deg, var(--shipxpeed-success) 0%, var(--shipxpeed-success-light) 100%);
        border-color: var(--shipxpeed-success);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .toggle-button-section .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }

    .toggle-button-section .btn-outline-secondary {
        background: white;
        border-color: var(--shipxpeed-border);
        color: var(--shipxpeed-gray);
    }

    .toggle-button-section .btn-outline-secondary:hover {
        background: var(--shipxpeed-light);
        transform: translateY(-2px);
        box-shadow: var(--shipxpeed-shadow-lg);
    }

    /* Enhanced file upload styling */
    /* .file-upload-container {
        background: var(--shipxpeed-light);
        border: 2px dashed var(--shipxpeed-border);
        border-radius: var(--shipxpeed-radius);
        padding: 0.5rem;
        transition: var(--shipxpeed-transition);
    }

    .file-upload-container:hover {
        border-color: var(--shipxpeed-primary);
        background: rgba(99, 102, 241, 0.05);
    } */

    .input-group .form-control {
        border-radius: var(--shipxpeed-radius) 0 0 var(--shipxpeed-radius);
        /* border-right: none; */
        /* border: 2px solid var(--shipxpeed-border); */
        transition: var(--shipxpeed-transition);
    }

    .input-group .form-control:focus {
        border-color: var(--shipxpeed-primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .input-group .btn {
        border-radius: 0 var(--shipxpeed-radius) var(--shipxpeed-radius) 0;
        border: 2px solid var(--shipxpeed-dark);
        border-left: none;
    }

    /* Enhanced Status Filters */
    .status-filter-row {
        background: #646dff26;
        border-radius: var(--shipxpeed-radius-lg);
        padding: 0.65rem;
        margin-bottom: 1rem;
        margin-left: 1rem;
        box-shadow: var(--shipxpeed-shadow-lg);
        /* border: 1px solid var(--shipxpeed-border); */
    }

    .status-filter-row h5 {
        margin-bottom: 0.4rem;
        font-size: 1rem;
    }

    .status-filter-row .d-flex {
        gap: 0.5rem;
        align-items: center;
    }

    .status-filter-row .btn {
        border-radius: var(--shipxpeed-radius);
        font-weight: 500;
        padding: 0.4rem 0.4rem;
        transition: var(--shipxpeed-transition);
        border: 2px solid transparent;
        position: relative;
        margin-bottom: 0.25rem;
        font-size: 0.82rem;
    }

    .status-filter-row .btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shipxpeed-shadow-lg);
    }

    .status-filter-row .btn .badge {
        font-size: 0.65rem;
        border-radius: 50%;
        min-width: 14px;
        height: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: pulse 2s infinite;
        transform: translate(6px,-6px);
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    /* Enhanced Table Container */
    .table-container {
        background: white;
        border-radius: var(--shipxpeed-radius-lg);
        overflow: hidden;
        margin-left: 15px;
        box-shadow: var(--shipxpeed-shadow-xl);
        backdrop-filter: blur(10px);
    }

    .table-header {
        background: #646dff26;
        padding: 0.75rem 1rem;
        color: white;
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

    .date-input:focus {
        border-color: var(--shipxpeed-primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        background: rgba(255, 255, 255, 0.15);
    }

    /* Enhanced Table Styling */
    .table {
        margin-bottom: 0;
        font-size: 0.9rem;
    }

    .table thead th {
        background: var(--shipxpeed-light);
        color: var(--shipxpeed-dark);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 0.6rem 0.5rem;
        border: none;
        font-size: 0.78rem;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table tbody tr {
        transition: var(--shipxpeed-transition);
        /* border-bottom: 1px solid var(--shipxpeed-border-light); */
    }

    .table tbody tr:hover {
        background: rgba(99, 102, 241, 0.05);
        box-shadow: inset 4px 0 0 var(--shipxpeed-primary);
    }

    .table tbody td {
        padding: 0.475rem 0.45rem;
        vertical-align: middle;
        border: none;
    }

    /* Enhanced Action Buttons */
    .action-dropdown .dropdown-toggle {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-dark) 100%);
        border: none;
        border-radius: var(--shipxpeed-radius);
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
        font-weight: 600;
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

    /* Enhanced Badges */
    .badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.5rem 0.75rem;
        border-radius: var(--shipxpeed-radius-sm);
        letter-spacing: 0.025em;
        text-transform: uppercase;
    }

    /* Enhanced Form Controls */
    .form-control, .form-select {
        /* border: 2px solid var(--shipxpeed-border); */
        border-radius: var(--shipxpeed-radius);
        padding: 0.5rem 0.75rem;
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
        width: 1.25rem;
        height: 1.25rem;
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


    /* .table-header {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        padding: 1.25rem 1.5rem;
        color: white;
    } */

    .table-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .date-input {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: var(--shipxpeed-radius);
        color: white;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }

    .date-input::placeholder {
        color: rgba(255,255,255,0.8);
    }

    .date-input:focus {
        background: rgba(255,255,255,0.25);
        border-color: rgba(255,255,255,0.5);
        outline: none;
        box-shadow: 0 0 0 3px rgba(255,255,255,0.1);
    }

    /* Table Styling */
    .table {
        margin: 0;
        font-size: 0.9rem;
    }

    .table thead th {
        background: var(--shipxpeed-dark);
        color: white;
        font-weight: 600;
        padding: 1rem 0.75rem;
        border: none;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table tbody tr {
        transition: var(--shipxpeed-transition);
        border-bottom: 1px solid #ebeef3;
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .table tbody tr.selected {
        background: rgba(99, 102, 241, 0.05);
        border-left: 3px solid var(--shipxpeed-primary);
    }

    .table tbody td {
        padding: 0.475rem 0.45rem;
        border: none;
        vertical-align: middle;
    }

    /* Checkbox Styling */
    .form-check-input {
        width: 1.1rem;
        height: 1.1rem;
        border-radius: 3px;
        border: 2px solid var(--shipxpeed-border);
    }

    .form-check-input:checked {
        background-color: var(--shipxpeed-primary);
        border-color: var(--shipxpeed-primary);
    }

    /* Action Button Styling */
    .action-dropdown .btn {
        background: var(--shipxpeed-primary);
        border: none;
        border-radius: var(--shipxpeed-radius);
        font-size: 0.85rem;
        padding: 0.5rem 1rem;
        font-weight: 500;
    }

    .action-dropdown .btn:hover {
        background: var(--shipxpeed-primary-light);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    /* Badge Enhancements */
    .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
        border-radius: var(--shipxpeed-radius);
        font-weight: 500;
    }

    .badge.bg-success { background-color: var(--shipxpeed-success) !important; }
    .badge.bg-danger { background-color: var(--shipxpeed-danger) !important; }
    .badge.bg-warning { background: linear-gradient(90deg, #e9f2fb 0%, #ebeef6 80%);; color: #2b3d6e !important; }
    .badge.bg-info { background-color: var(--shipxpeed-info) !important; }
    .badge.bg-primary { background-color: var(--shipxpeed-primary) !important; }
    .badge.bg-secondary { background-color: var(--shipxpeed-dark) !important; }

    /* Product Info Styling */
    /* .product-info .product-item {
        margin-bottom: 0.5rem;
    } */

    .product-name {
        font-size: 0.9rem;
        line-height: 1.3;
    }

    /* Customer Info Styling */
    .customer-info {
        text-align: left;
    }

    .customer-info .fw-medium {
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    /* Dropdown Menu Enhancement */
    .dropdown-menu {
        border-radius: var(--shipxpeed-radius);
        box-shadow: var(--shipxpeed-shadow-lg);
        border: 1px solid var(--shipxpeed-border);
        padding: 0.5rem;
        min-width: 160px;
    }

    .dropdown-item {
        border-radius: calc(var(--shipxpeed-radius) - 2px);
        margin-bottom: 0.125rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        transition: var(--shipxpeed-transition);
    }

    .dropdown-item:hover {
        background: var(--shipxpeed-light);
    }

    .dropdown-item.text-danger:hover {
        background: rgba(239, 68, 68, 0.1);
        color: var(--shipxpeed-danger) !important;
    }

    /* Modal Enhancements */
    .modal-content {
        border-radius: var(--shipxpeed-radius);
        border: none;
        box-shadow: var(--shipxpeed-shadow-lg);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        color: white;
        border-bottom: none;
        border-radius: var(--shipxpeed-radius) var(--shipxpeed-radius) 0 0;
        padding: 1.25rem 1.5rem;
    }

    .modal-title {
        font-weight: 600;
        font-size: 1.1rem;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid var(--shipxpeed-border);
    }

    /* Form Enhancements */
    .form-select {
        border-radius: var(--shipxpeed-radius);
        border: 1px solid var(--shipxpeed-border);
        padding: 0.75rem 1rem;
    }

    .form-select:focus {
        border-color: var(--shipxpeed-primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    /* Enhanced Modals */
    .modal-content {
        border: none;
        border-radius: var(--shipxpeed-radius-lg);
        box-shadow: var(--shipxpeed-shadow-xl);
        backdrop-filter: blur(10px);
    }

    .modal-header {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        color: white;
        border-radius: var(--shipxpeed-radius-lg) var(--shipxpeed-radius-lg) 0 0;
        padding: 1.5rem 2rem;
        border: none;
    }

    .modal-title {
        font-weight: 700;
        font-size: 1.25rem;
    }

    .modal-body {
        padding: 2rem;
    }

    .modal-footer {
        padding: 1.5rem 2rem;
        background: var(--shipxpeed-light);
        border-radius: 0 0 var(--shipxpeed-radius-lg) var(--shipxpeed-radius-lg);
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

    /* Enhanced Pagination */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        border: 1px solid var(--shipxpeed-border);
        color: var(--shipxpeed-primary);
        /* font-weight: 600; */
        padding: 0.45rem 0.7rem;
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

    /* Enhanced Color System */
    .bg-danger { 
        background: linear-gradient(135deg, var(--shipxpeed-danger) 0%, var(--shipxpeed-danger-light) 100%) !important; 
    }
    .bg-success { 
        background: linear-gradient(135deg, var(--shipxpeed-success) 0%, var(--shipxpeed-success-light) 100%) !important; 
    }
    .bg-warning { 
       
        color: #2b3d6e !important; 
        background: linear-gradient(90deg, #e9f2fb 0%, #ebeef6 80%);
    }
    .bg-info { 
        background: linear-gradient(135deg, var(--shipxpeed-info) 0%, var(--shipxpeed-info-light) 100%) !important; 
    }
    .bg-primary { 
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%) !important; 
    }
    .bg-secondary { 
        background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%) !important; 
    }
    .bg-dark { 
        background: linear-gradient(135deg, var(--shipxpeed-dark) 0%, #374151 100%) !important; 
    }

    /* Enhanced Product Info Styling */
    .product-info {
        max-width: 200px;
    }

    .product-item {
        background: #f6f9fd;
        border-radius: var(--shipxpeed-radius-sm);
        padding: 0.3rem 0.55rem;
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
        margin-bottom: 0.25rem;
    }

    .customer-details {
        font-size: 0.8rem;
        color: var(--shipxpeed-gray);
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

    /* Responsive Design */
    @media (max-width: 1200px) {
        .pc-container {
            padding: 1rem !important;
        }
        
        .page-header {
            padding: 1.5rem;
        }
        
        .page-header h1 {
            font-size: 2rem;
        }
        
        .toggle-section {
            padding: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .pc-container {
            padding: 0.5rem !important;
        }
        
        .page-header {
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
        }
        
        .toggle-section {
            padding: 1rem;
        }
        
        .toggle-button-section {
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .status-filter-row {
            padding: 1rem;
        }
        
        .status-filter-row .d-flex {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .table-controls {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }
        
        .table-responsive {
            font-size: 0.8rem;
        }
        
        .table thead th {
            padding: 0.75rem 0.5rem;
            font-size: 0.7rem;
        }
        
        .table tbody td {
            padding: 0.75rem 0.5rem;
        }
        
        .product-info,
        .customer-info {
            max-width: 150px;
        }
        
        .action-dropdown .dropdown-toggle {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }
    }

    /* Animation Classes */
    .fade-in {
        animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes fadeIn {
        from { 
            opacity: 0; 
            transform: translateY(30px); 
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
            transform: translateX(-30px); 
        }
        to { 
            opacity: 1; 
            transform: translateX(0); 
        }
    }

    /* Loading State */
    .loading {
        opacity: 0.6;
        pointer-events: none;
        position: relative;
    }

    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 2rem;
        height: 2rem;
        margin: -1rem 0 0 -1rem;
        border: 3px solid rgba(99, 102, 241, 0.3);
        border-radius: 50%;
        border-top-color: var(--shipxpeed-primary);
        animation: spin 1s ease-in-out infinite;
    }

    /* Enhanced Focus States */
    *:focus {
        outline: none;
    }

    .btn:focus,
    .form-control:focus,
    .form-select:focus {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    /* Dark mode support (optional) */
    @media (prefers-color-scheme: dark) {
        :root {
            --shipxpeed-light: #1f2937;
            --shipxpeed-border: #374151;
            --shipxpeed-dark: #f9fafb;
        }
    }
</style>

<!-- Main Content Container -->
<div class="pc-container fade-in">
    <!-- Enhanced Action, Search & Upload Section (Responsive) -->
    <div class="toggle-section slide-in">
        <div class="action-upload-flexbar d-flex flex-wrap justify-content-between align-items-start gap-3 w-100">
            <!-- Action Bar: Always horizontal in mobile, shrunk buttons, left in mobile -->
            <div class="action-bar-premium d-flex flex-wrap gap-3 align-items-center p-3 rounded-4 premium-light-bg-action-bar" style="min-width: 310px;">
                <a href="{{ route('seller.orders.download-template') }}"
                   class="btn btn-sm d-flex align-items-center px-3 py-2 fs-6 btn-premium-light"
                   data-bs-toggle="tooltip"
                   title="Download CSV">
                    <i class="fas fa-download fs-5"></i>
                </a>
                <a href="{{ route('seller.orders.export-excel') }}"
                   class="btn btn-sm d-flex align-items-center px-3 py-2 fs-6 btn-premium-light"
                   data-bs-toggle="tooltip"
                   title="Export orders to Excel">
                    <i class="fas fa-file-excel fs-5"></i>
                </a>
                <button class="btn btn-sm d-flex align-items-center px-3 py-2 fs-6 btn-premium-light"
                        data-bs-toggle="tooltip"
                        title="Sync orders from all channels">
                    <i class="fas fa-sync"></i>
                </button>
                <a href="{{ route('seller.orderadd') }}"
                   class="btn btn-sm d-flex align-items-center px-3 py-2 fs-6 btn-premium-light"
                   data-bs-toggle="tooltip"
                   title="Create new order">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
            <!-- Search Bar: Hide on mobile -->
            <div class="search-bar-premium search-bar-responsive" style="margin-top: 15px; max-width: 380px; width: 100%;">
                <div class="input-group shadow-sm"
                    style="
                        background: rgb(255 255 255 / 94%);
                        border-radius: 9px;
                        overflow: hidden;
                        transition: box-shadow 0.22s;
                    ">
                    <span class="input-group-text border-0 bg-transparent px-3" style="pointer-events: none;">
                        <i class="fas fa-search" style="color: #6366f1; opacity: 0.80;"></i>
                    </span>
                    <input
                        type="text"
                        class="form-control border-0 bg-transparent px-2"
                        placeholder="Search orders by ID, Product, Customer name…"
                        aria-label="Search orders"
                        disabled
                        style="
                            background: transparent;
                            color: #7b8591;
                            font-weight: 500;
                            letter-spacing: 0.01em;
                            font-size: 0.9rem;
                            min-height: 40px;
                        "
                    >
                </div>
            </div>
            <!-- File Upload: Always visible, at right in mobile and desktop -->
            <div class="mt-3 upload-section" style="max-width: 430px;">
                <form action="{{ route('seller.orders.import') }}" method="POST" enctype="multipart/form-data" class="d-flex flex-nowrap gap-2">
                    @csrf
                    <div class="flex-grow-1">
                        <input 
                            type="file"
                            name="excel_file"
                            id="excel_file"
                            accept=".csv, .xlsx"
                            class="form-control"
                            required
                            style="min-width: 150px;"
                        >
                    </div>
                    <button type="submit" class="btn btn-gradient-dark fw-semibold d-flex" data-bs-toggle="tooltip" title="Upload orders file">
                        <i class="fas fa-upload" style="font-size: 14px;"></i>
                        <!-- <span class="d-none d-sm-inline">Upload</span> -->
                    </button>
                </form>
            </div>
            <style>
                /* Gradient-light & transparent color buttons */
                .btn-premium-light {
                    background: linear-gradient(90deg, rgba(226, 232, 240, 0.28) 0%, rgba(199, 210, 254,0.23) 100%);
                    color: #374151 !important;
                    border: 1.5px solid #d1d5db !important;
                    border-radius: 13px;
                    box-shadow: none;
                    opacity: 1;
                    font-weight: 500;
                    transition:
                        background 0.18s,
                        color 0.18s,
                        border 0.18s,
                        font-weight 0.13s;
                }

                .btn-premium-light i {
                    font-weight: 500;
                    transition: font-weight 0.14s;
                }

                .btn-premium-light:hover,
                .btn-premium-light:focus {
                    background: 
                        linear-gradient(90deg, #c3e8fc 0%, #c7d2fe 60%, #e0e7ff 100%);
                    font-weight: 700 !important;
                    color: #22223b !important;
                    border: 1.5px solid #6366f1 !important;
                    box-shadow: 0 2px 12px 0 rgba(99, 102, 241, 0.10);
                }
                .btn-premium-light:hover i,
                .btn-premium-light:focus i {
                    font-weight: 700 !important;
                }

                .btn-premium-light .fa-download      { color: #0076ab  !important; }
                .btn-premium-light .fa-file-excel    { color: #059669  !important; }
                .btn-premium-light .fa-sync          { color: #6366f1  !important; }
                .btn-premium-light .fa-plus          { color: #f59e42  !important; }

                .btn-premium-light:hover .fa-download      { color: #0369a1 !important; }
                .btn-premium-light:hover .fa-file-excel    { color: #047857 !important; }
                .btn-premium-light:hover .fa-sync          { color: #3730a3 !important; }
                .btn-premium-light:hover .fa-plus          { color: #f97316 !important; }

                .btn-gradient-dark {
                    background: linear-gradient(90deg, #22223b 0, #374151 100%);
                    border: none;
                    color: #fff;
                }
                .btn-gradient-dark:hover {
                    background: linear-gradient(90deg, #374151 0, #22223b 100%);
                    box-shadow: 0 2px 12px 0 rgba(34, 34, 59, 0.18);
                }
                .file-upload-container input[type="file"] {
                    cursor: pointer;
                }
                .file-upload-container form {
                    min-width: 0;
                    max-width: 100%;
                }
                /* Responsive adjustments */
                @media (max-width: 600px) {
                    .action-upload-flexbar {
                        flex-wrap: nowrap !important;
                        flex-direction: row !important;
                        align-items: center !important;
                        gap: 0.6rem !important;
                        width: 100%;
                    }
                    .action-bar-premium,
                    .premium-light-bg-action-bar {
                        min-width: 0 !important;
                        max-width: 100% !important;
                        padding: 0.33rem 0.1rem !important;
                        flex: 1 1 50%;
                        flex-direction: row !important;
                        gap: 0.16rem !important;
                    }
                    .action-bar-premium {
                        justify-content: flex-start !important;
                        align-items: center !important;
                        width: 50% !important;
                    }
                    .action-bar-premium .btn-premium-light {
                        min-width: 32px !important;
                        max-width: 36px !important;
                        width: 32px !important;
                        height: 32px !important;
                        padding: 0 !important;
                        font-size: 1rem !important;
                        border-radius: 8px !important;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .action-bar-premium .btn-premium-light i {
                        font-size: 1.07rem !important;
                    }
                    /* Search bar: Hide ONLY on mobile */
                    .search-bar-responsive {
                        display: none !important;
                    }
                    /* File upload: to right, line up */
                    .upload-section {
                        margin-top: 0 !important;
                        max-width: 100% !important;
                        min-width: 0 !important;
                        width: 50% !important;
                        flex: 1 1 50%;
                        display: flex !important;
                        justify-content: flex-end !important;
                        align-items: center !important;
                    }
                    .upload-section form {
                        width: 100% !important;
                        flex-wrap: nowrap !important;
                        flex-direction: row !important;
                        justify-content: flex-end !important;
                        align-items: center !important;
                        gap: 0.5rem !important;
                    }
                    .upload-section input[type="file"] {
                        min-width: 0 !important;
                        width: 100% !important;
                    }
                }
                /* For laptop/desktop view, restore previous layout */
                @media (min-width: 601px) {
                    .action-upload-flexbar {
                        flex-wrap: wrap !important;
                        flex-direction: row !important;
                        align-items: start !important;
                        justify-content: space-between !important;
                        gap: 1rem !important;
                    }
                    .action-bar-premium,
                    .premium-light-bg-action-bar {
                        min-width: 310px !important;
                        max-width: 100% !important;
                        padding: 1rem !important;
                        flex: none !important;
                        flex-direction: row !important;
                        gap: 1rem !important;
                        width: auto !important;
                    }
                    .action-bar-premium {
                        justify-content: flex-start !important;
                        align-items: center !important;
                        width: auto !important;
                    }
                    .upload-section {
                        margin-top: 1rem !important;
                        max-width: 430px !important;
                        width: 100% !important;
                        min-width: 0 !important;
                        flex: none !important;
                        display: block !important;
                    }
                }
            </style>
        </div>
    </div>

    <!-- Enhanced Status Filter Buttons -->
    <div class="status-filter-row slide-in p-4">
        <div class="d-flex justify-content-between flex-wrap gap-2 align-items-center mb-2">
            <h5 class="mb-0 text-dark fw-bold d-flex align-items-center" style="font-size: 1.1rem; padding-bottom: 0.5rem;">
                <i class="fas fa-filter me-2 text-primary opacity-75"></i>
                Filter by Status
            </h5>
        </div>

        <div class="status-btns-container w-100">
            <a href="{{ route('seller.order') }}" class="status-btn-premium btn" data-bs-toggle="tooltip" title="View new orders">
                <span class="status-btn-content"><i class="fas fa-list me-2"></i>New Orders</span>
            </a>
            <a href="{{ route('seller.courier.Assigned') }}" class="status-btn-premium btn" data-bs-toggle="tooltip" title="Orders assigned to courier">
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
                    /* height: 36px; */
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
                    font-size: 0.89rem;
                    border-radius: 8px !important;
                    padding: 0;
                    width: 100%;
                    max-width: 100%;
                    flex: none;
                    height: 44px;
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
    <div class="table-container">
        <!-- Table Header with Controls -->
        <div class="table-header">
            <div class="table-controls">
                <div class="d-flex flex-wrap align-items-center gap-2">

                    <!-- Date Filter Div -->
                    <div class="filter-block bg-white rounded-3 shadow-sm border px-3 py-2 d-flex align-items-center date-filter-responsive">
                        <form method="GET" action="{{ route('seller.order') }}" class="filter-form-premium d-flex flex-wrap align-items-center gap-2 m-0 w-100">
                            <div class="date-group-premium d-flex align-items-center gap-2 flex-wrap flex-sm-nowrap mb-0 w-100">
                                <label for="start_date" class="form-label mb-0 fw-semibold text-secondary" style="font-size:.85rem;">
                                   From
                                </label>
                                <input 
                                    type="date" 
                                    id="start_date"
                                    name="start_date" 
                                    class="form-control form-control-sm custom-date-input-premium"
                                    value="{{ request('start_date') }}" 
                                    placeholder="dd-mm-yyyy"
                                    style="padding:0.3rem 0.55rem;font-size:11px;border-radius:5px;min-width: 100px;"
                                >
                                <span class="mx-1 text-muted" style="font-weight:500; font-size:.85rem;">to</span>
                                <label for="end_date" class="form-label mb-0 fw-semibold text-secondary visually-hidden">To</label>
                                <input 
                                    type="date" 
                                    id="end_date"
                                    name="end_date" 
                                    class="form-control form-control-sm custom-date-input-premium"
                                    value="{{ request('end_date') }}" 
                                    placeholder="dd-mm-yyyy"
                                    style="padding:0.3rem 0.55rem;font-size:11px;border-radius:5px;min-width: 100px;"
                                >
                                @if(request('start_date') || request('end_date'))
                                <a href="{{ route('seller.orders.export-excel') }}?start_date={{ request('start_date') }}&end_date={{ request('end_date') }}"
                                    class="btn btn-gradient-success fw-semibold rounded-pill px-3 ms-1 shadow-sm export-btn-responsive"
                                    style="font-size:.85rem;">
                                    <i class="fas fa-file-excel me-1"></i>Export Filtered
                                </a>
                                @endif
                            </div>
                        </form>
                        <style>
                        @media (max-width: 767.98px) {
                            .date-filter-responsive {
                                flex-direction: row !important;
                                flex-wrap: nowrap !important;
                                align-items: center !important;
                                padding: 0.7rem 0.2rem !important;
                            }
                            .filter-form-premium {
                                flex-direction: row !important;
                                flex-wrap: nowrap !important;
                                gap: 0.25rem !important;
                                width: 100% !important;
                                align-items: center !important;
                                margin: 0 !important;
                            }
                            .date-group-premium {
                                flex-direction: row !important;
                                flex-wrap: nowrap !important;
                                align-items: center !important;
                                gap: 0.3rem !important;
                                margin-bottom: 0 !important;
                                width: 100% !important;
                            }
                            .date-group-premium > label,
                            .date-group-premium > span {
                                font-size: 0.75rem !important;
                                margin-bottom: 0 !important;
                                margin-top: 0 !important;
                            }
                            .date-group-premium > input[type="date"] {
                                width: 132px !important;
                                /* min-width: 80px !important; */
                                font-size: 0.81rem !important;
                                padding: 0.12rem 0.11rem !important;
                                margin-bottom: 0 !important;
                            }
                            .export-btn-responsive {
                                font-size: 0.83rem !important;
                                padding-left: 1.2rem !important;
                                padding-right: 1.2rem !important;
                                text-align: center;
                                white-space: nowrap;
                                margin: 0 0 0 0.3rem !important;
                                width: auto !important;
                            }
                        }
                        </style>
                    </div>

                    <!-- Search Bar Div -->
                    <div class="filter-block d-flex align-items-center">
                        <form class="w-100 d-flex align-items-center gap-2 m-0">
                            <div class="search-ref-id-group w-100 position-relative d-flex align-items-center shadow-sm  gap-1 " >
                                <span class="search-icon position-absolute" style="left: 10px; z-index: 3; color:#a1a7ba; font-size: 0.8rem;">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input 
                                    type="text" 
                                    id="ref_id"
                                    name="ref_id"
                                    class="form-control form-control-sm pl-4"
                                    value="{{ request('ref_id') }}"
                                    placeholder="Search Order ID"
                                    style="font-size:0.82rem;padding-left:2.1rem;">
                            </div>
                        </form>
                    </div>
                    <style>
                        @media (max-width: 767.98px) {
                            .search-ref-id-group {
                                min-width: 120px !important;
                                max-width: 100% !important;
                                box-shadow: 0 1px 5px 0 rgba(99,102,241,0.13) !important;
                            }
                            .search-ref-id-group input.form-control {
                                max-width: 100% !important;
                                font-size: 0.84rem !important;
                            }
                        }
                    </style>

                    <!-- Dropdown Div -->
                    <div class="filter-block d-flex align-items-center">
                        <div class="dropdown w-100" >
                            <button class="btn dropdown-toggle d-flex shadow-sm justify-content-between align-items-center w-100" 
                                type="button" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false" 
                                style="font-size: .85rem; font-weight: 100; background: white; border-radius: .65rem; box-shadow: 0 2px 8px 0 rgba(99,102,241,.10); color: #222; transition: color 0.15s;">
                                <span style="color: #222;">Select Payment Mode</span>
                                <span class="ms-2" style="font-size:1em; color: #222;">
                                    <i class="fas fa-chevron-down" style="font-size:10px; color: #222;"></i>
                                </span>
                            </button>
                            <style>
                            .dropdown-toggle:focus, 
                            .dropdown-toggle:hover {
                                color: #222 !important;
                            }
                            .dropdown-toggle:hover span,
                            .dropdown-toggle:hover .fas {
                                color: #222 !important;
                            }
                            </style>
                            <ul class="dropdown-menu dropdown-menu-end w-100">
                                <li>
                                    <a class="dropdown-item" href="#">
                                        COD
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#">
                                        Prepaid
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
                <style>
                    .filter-block {
                        border-radius: 1.05rem;
                        height: 38px;
                        transition: box-shadow 0.18s, background 0.2s;
                    }
                    .search-ref-id-group input.form-control {
                        background: white;
                        border-radius: 0.44rem;
                        border: none;
                        font-size: .92rem;
                        color: #58688e;
                        box-shadow: none;
                    }
                   
                    .search-ref-id-group .search-icon {
                        left: 10px;
                        font-size: 1rem;
                        color: #a1a7ba;
                        pointer-events: none;
                    }

                    .dropdown .btn.dropdown-toggle {
                        border-radius: 0.35rem !important;
                        /* background: white !important; */
                        border: none;
                        font-weight: 500;
                        /* color: #445686; */
                        box-shadow: none;
                        /* padding-right: 2rem; */
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                    }
                    .dropdown .btn.dropdown-toggle:after {
                        display: none !important;
                    }
                    .dropdown-menu {
                        min-width: 150px;
                        border-radius: 0.8rem;
                        box-shadow: 0 6px 24px -8px #6376f117;
                        font-size: 0.94rem;
                    }
                    .dropdown-menu .dropdown-item {
                        border-radius: 0.45rem;
                        font-size: .97rem;
                        color: #4a5875;
                    }
                    .dropdown-menu .dropdown-item:hover,
                    .dropdown-menu .dropdown-item:focus {
                        background: #eaf0fb;
                        color: #223044;
                    }
                    @media (max-width: 991.98px) {
                        .filter-block {
                            padding: 0.85rem 0.5rem !important;
                            gap: 0.2rem;
                        }
                        .filter-form-premium {
                            gap: 0.37rem;
                        }
                    }
                    @media (max-width: 767.98px) {
                        .d-flex.flex-wrap.align-items-center.gap-3.p-2.p-md-3.mb-2 {
                            flex-direction: column !important;
                            gap: 0.95rem !important;
                        }
                        .filter-block {
                            width: 100% !important;
                            min-width: 0 !important;
                            margin-bottom: 0.3rem;
                        }
                    }
                </style>
               
                <div class="d-flex align-items-center gap-2">
                   
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="bulkActionDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="height:30px;font-size:15px;">
                            <i class="fas fa-cog"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#warehouseModal">
                                    <i class="fas fa-warehouse me-2"></i>Update Warehouse
                                </a>
                            </li>
                         
                            <li>
                                <a class="dropdown-item" href="#" id="bulkShipNowBtn">
                                    <i class="fas fa-truck me-2"></i>Ship Now (Bulk)
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <button class="btn btn-light" onclick="location.reload()" style="height:30px;font-size:15px;">
                        <i class="fas fa-sync-alt" style="font-size: 15px;"></i>
                    </button>
                </div>
            </div>
        </div>
   
        <style>
            /* Premium Professional Table Styles */
            .premium-table-wrapper {
                background: #f7fafd;
                /* border-radius: 16px; */
                box-shadow: 0 4px 24px rgba(97,110,141,0.06);
                /* padding: 1.2rem .75rem .5rem .75rem; */
                margin-bottom: 12px;
            }
            .premium-table {
                border-radius: 9px;
                background: #fcfcfd;
                font-size: 0.98rem;
                color: #283146;
            }
            .premium-table th {
                background: linear-gradient(90deg,#f3f4f8 0 60%, #eef2fa 100%);
                color: #191f28 !important;
                font-weight: 600;
                border-bottom: 2px solid #e4e7f0;
                padding: 8px 6px !important;
                font-size: .965rem;
                letter-spacing: 0.01em;
                min-height: 22px;
                vertical-align: middle;
                white-space: nowrap;
            }
            .premium-table tr.premium-header-row th {
                /* background: linear-gradient(90deg,#eaf0fa 0,#f2f4f7 100%) !important; */
                background: linear-gradient(90deg, #3576e3 0, #306cc9 100%) !important;
                color: white !important;
                border-top: none;
            }
            .premium-table td {
                background: #fff;
                border-bottom: 1px solid #ecf0f6;
                vertical-align: middle;
                color: #273556;
                padding: 7px 6px;
                font-size: 0.95rem;
                min-height: 18px;
                transition: background 0.12s;
            }
            /* Alternate row coloring for better distinction */
            .premium-table tbody tr:nth-of-type(odd) td {
                background: #f7fafd;
            }
            .premium-table tbody tr:nth-of-type(even) td {
                background: #fff;
            }
            .premium-table tr.order-row:hover td {
                background: #f1f5fc !important;
                color: #161e33;
            }
            .premium-table .form-check-input[type="checkbox"] {
                width: 1em;
                height: 1em;
                border-radius: 5px;
                border: 1.1px solid #d4dae6;
                accent-color: #4266d9;
            }
            .table-action-btn {
                background: #e6e8f7 !important;
                color: #30407c !important;
                border: none;
                font-weight: 600;
                min-width: 32px;
                border-radius: 6px;
                font-size: .995em;
                box-shadow: 0 2px 8px rgba(85,108,214,.04);
                min-height: 28px;
            }
            .badge-premium {
                background: linear-gradient(90deg,#e9f2fb 0%,#ebeef6 80%);
                color: #2b3d6e;
                font-weight: 600;
                border-radius: 5px;
                font-size: .96em;
                min-height: 20px;
            }
            .badge-premium-dark {
                background: #e6eaf8;
                color: #384984;
            }
            .premium-table .dropdown-menu {
                border-radius: 8px;
                box-shadow: 0 4px 22px #f0f3fa;
                min-width: 150px;
                font-size: 0.99em;
            }
            .premium-table-wrapper::-webkit-scrollbar { height: 6px;}
            .premium-table-wrapper::-webkit-scrollbar-thumb { background: #e5eeff; border-radius: 4px;}
            @media (max-width: 991.98px) {
                .premium-table th, .premium-table td {
                    font-size: .89rem;
                }
            }
            @media (max-width: 576.98px) {
                .premium-table-wrapper {
                    padding-left: .18rem !important;
                    padding-right: .18rem !important;
                }
                .premium-table th, .premium-table td {
                    padding: 4px 2vw;
                    font-size: .84rem;
                }
            }
        </style>
        <div class="table-responsive premium-table-wrapper">
            <form id="bulkActionForm" action="{{ route('seller.orders.update-warehouse') }}" method="POST">
                @csrf
                <table class="table table-hover table-borderless text-center align-middle premium-table mb-0">
                    <thead>
                        <tr class="premium-header-row">
                            <th class="align-middle text-center" style="vertical-align: middle;">
                                <div class="d-flex justify-content-center align-items-center m-0" style="height: 100%;">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </div>
                            </th>
                            <th>Order ID</th>
                            <th>Product Details</th>
                            <th>Payment</th>
                            <th>Collectable</th>
                            <!-- <th>Method</th> -->
                            <th>Shipping Details</th>
                            <!-- <th>Weight</th> -->
                            <th>Warehouse</th>
                            <th width="95">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                        <tr class="order-row">
                            <td>
                                <div class="form-check m-0 d-flex justify-content-center">
                                    <input type="checkbox" class="order-checkbox form-check-input" name="order_ids[]" value="{{ $order->id }}">
                                </div>
                            </td>
                            <td>
                                @if($order->seller_id == 433)
                                    <strong class="text-primary" style="color: #2a4cb8 !important;font-size: 14px;">#{{ $order->customer_order_id }}</strong>
                                @else
                                    <strong class="text-primary" style="color: #2a4cb8 !important;font-size: 14px;">#{{ $order->order_number }}</strong>
                                @endif
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
                            <td class="align-middle" style="min-width:110px;max-width:170px;">
                                @php
                                    $items = is_array($order->order_items) ? $order->order_items : json_decode($order->order_items, true);
                                    $firstItem = $items[0] ?? null;
                                    $limitedWords = '';
                                    $qty = $firstItem['qty'] ?? $firstItem['quantity'] ?? 1;
                                    if ($firstItem) {
                                        $words = preg_split('/\s+/', $firstItem['name']);
                                        $limitedWords = implode(' ', array_slice($words, 0, 3)) . (count($words) > 3 ? '…' : '');
                                    }
                                @endphp
                                @if($firstItem)
                                    <div 
                                        class="d-flex flex-column gap-1 justify-content-center align-items-start premium-product-card"
                                        style="
                                            background: linear-gradient(107deg, #f7fafc 0%, #e8f0fa 100%);
                                            border-left: 3px solid #3051a0;
                                            border-radius: 8px 0 0 8px;
                                            padding: 8px 16px 6px 12px;
                                            min-width: 0; max-width: 190px; line-height: 1.16;
                                            box-shadow: 0 2px 10px 0 rgba(65,112,221,0.04);
                                        "
                                    >
                                        <span 
                                            title="{{ collect($items)->pluck('name')->implode(', ') }}"
                                            class="text-truncate fw-semibold"
                                            style="
                                                font-size: .93em;
                                                color: #23345c;
                                                letter-spacing: 0.01em;
                                                max-width: 118px;
                                                display: inline-block;
                                                line-height: 1.18;
                                            ">
                                            {{ $limitedWords }}
                                            @if(count($items) > 1)
                                                <span class="text-premium ms-1" style="font-size:.75em; color:#818ea3;font-weight:500;">+{{ count($items) - 1 }}</span>
                                            @endif
                                        </span>
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <span class="text-muted" style="font-size:.81em;letter-spacing: 0.02em;">
                                                QTY: <span class="fw-semibold text-dark" style="font-size:.96em;">{{ $qty }}</span>
                                            </span>
                                            <span class="badge" style="font-size:.60em; background:#edf2fa;color:#3c4765;padding: .19em .59em;">
                                                Weight: {{ $order->package_weight ?? '0' }}gms
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted" style="font-size:.84em;color:#a5afc7;">No items</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 0.19rem;">
                                    <span style="font-size: .82em;">Invoice: ₹{{ number_format($order->order_amount, 2) }}</span>
                                    @php
                                        $paymentType = strtolower(trim($order->payment_type ?? ''));
                                        $isCOD = in_array($paymentType, ['cod', 'cash on delivery', 'cashondelivery', 'c.o.d']);
                                        $icon = $isCOD ? 'ti-cash' : 'ti-credit-card';
                                        $displayText = $isCOD ? 'COD' : 'PREPAID';
                                        // Slightly different green for prepaid, yellow-green for cod for visibility
                                        $bgColor = $isCOD 
                                            ? 'linear-gradient(93deg, #eafddb 0%, #e2f5bb 100%)' 
                                            : 'linear-gradient(90deg, #d9fbe8 0%, #c6edd8 100%)';
                                        $txtColor = '#168d56';
                                    @endphp
                                    <span 
                                        class="fw-semibold d-inline-flex align-items-center"
                                        style="font-size: .68em;
                                            color: #168d56;
                                            background: linear-gradient(93deg, #e8e9e8 0%, #eff7de 100%);
                                            border-radius: 5px;
                                            padding: 1px 5px 1.5px 5px;
                                        ">
                                        <i class="ti {{ $icon }} me-1" style="font-size:1em;"></i>
                                        <span>{{ $displayText }}</span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="" style="font-size:.82em;">₹{{ number_format($order->collectable_amount, 2) }}</span>
                            </td>
                           
                            <td>
                                <div class="customer-info">
                                    @php
                                        $consignee = is_array($order->consignee) ? $order->consignee : json_decode($order->consignee, true);
                                    @endphp
                                    <div class="text-truncate" style="font-size:13px; color:#223254;">{{ $consignee['name'] ?? 'N/A' }}</div>
                                    @if(isset($consignee['phone']))
                                    <small class="text-muted" style="color: #8793aa !important;">
                                        <i class="ti ti-phone me-1"></i>{{ $consignee['phone'] }}
                                    </small>
                                    <small class="text-muted" style="color: #8793aa !important;">
                                        <i class="fa-regular fa-address-book me-1"></i>Address
                                    </small>
                                    @endif
                                </div>
                            </td>
                            <!-- <td>
                                <span class="badge bg-light text-dark border border-info-emphasis" style="font-size: .91em; color: #3c4765;">
                                    <i class="ti ti-weight me-1"></i>{{ $order->package_weight }}kg
                                </span>
                            </td> -->
                            <td style="max-width: 140px; min-width: 110px;">
                                @php
                                    $pickup = is_array($order->pickup) ? $order->pickup : json_decode($order->pickup, true);
                                    $warehouseName = isset($pickup['warehouse_name']) && trim($pickup['warehouse_name']) !== '' ? $pickup['warehouse_name'] : null;
                                @endphp
                                <span class="badge d-flex align-items-center justify-content-center px-2 py-1 w-100 text-truncate"
                                    style="
                                        font-size: .82rem;
                                        min-height: 22px;
                                       
                                        color: #2c3856;
                                        box-shadow: none;
                                    "
                                    title="{{ $warehouseName ?? 'Not Assigned' }}">
                                   
                                    <span class="flex-grow-1 text-truncate">
                                        {{ $warehouseName ?? 'Not Assigned' }}
                                    </span>
                                </span>
                            </td>
                            <td>
                                <div class="action-dropdown">
                                    <div class="dropdown">
                                        <button class="btn table-action-btn shadow-sm px-2 py-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ti ti-dots me-1"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('seller.courier', $order->id) }}">
                                                    <i class="ti ti-truck me-2 text-primary"></i>Ship Now
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('seller.orderedit', $order->id) }}">
                                                    <i class="ti ti-edit me-2 text-info"></i>Edit Order
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="{{ route('orderdelete', $order->id) }}" onclick="return confirm('⚠️ Are you sure you want to delete this order?')">
                                                    <i class="ti ti-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="py-5">
                                <div class="empty-state text-center p-4">
                                    <i class="fas fa-box-open" style="font-size: 2.6rem; color: #e5e9f3; margin-bottom: .7rem;"></i>
                                    <h4 class="text-muted mb-2" style="color:#92a1b3;">No orders found</h4>
                                    <p class="text-muted mb-3" style="color:#90a0ad;">You haven't created any orders yet, or none match your current filters.</p>
                                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                                        <a href="{{ route('seller.orderadd') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Create Your First Order
                                        </a>
                                        <button class="btn btn-outline-secondary" onclick="location.reload()">
                                            <i class="fas fa-sync me-2"></i>Refresh
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
            @if ($orders->count())
            <div class="p-3 border-top bg-white" style="border-radius:0 0 14px 14px; min-height:36px;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <!-- <div class="text-muted d-flex align-items-center" style="font-size:.97em;color:#627299;">
                        <i class="fas fa-info-circle me-2"></i>
                        Showing <strong>{{ $orders->firstItem() ?? 0 }}</strong> to <strong>{{ $orders->lastItem() ?? 0 }}</strong> 
                        of <strong>{{ $orders->total() ?? 0 }}</strong> orders
                    </div> -->
                    <div>
                        {{ $orders->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>

<!-- Enhanced Warehouse Update Modal -->
<div class="modal fade" id="warehouseModal" tabindex="-1" aria-labelledby="warehouseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="warehouseModalLabel">
                    <i class="fas fa-warehouse me-2"></i>Update Warehouse Assignment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info d-flex align-items-center mb-4">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong>Bulk Warehouse Update</strong><br>
                        <small>Select a warehouse to assign to all selected orders</small>
                    </div>
                </div>
                
                <form id="warehouseUpdateForm">
                    <div class="mb-4">
                        <label for="warehouse_id" class="form-label fw-semibold d-flex align-items-center">
                            <i class="fas fa-building me-2 text-primary"></i>Select Warehouse
                        </label>
                        <select class="form-select form-select-lg" id="warehouse_id" name="warehouse_id" required>
                            <option value="">🏭 Choose a warehouse...</option>
                            @foreach($Warehouse as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            <i class="fas fa-lightbulb me-1"></i>This warehouse will be assigned to all selected orders
                        </div>
                    </div>
                    
                    <div class="selected-orders-preview">
                        <p class="fw-semibold mb-2">
                            <i class="fas fa-check-circle me-2 text-success"></i>Selected Orders: 
                            <span id="selectedOrdersPreview" class="badge bg-primary">0</span>
                        </p>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="updateWarehouseBtn">
                    <i class="fas fa-save me-2"></i>Update Warehouse
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Bulk Shipping Confirmation Modal OPEN FROM TOP (20px) -->
<style>
    /* Custom modal positioning: from top 20px for this modal only */
    #bulkShipModal .modal-dialog {
        margin-top: 20px !important;
        margin-bottom: auto !important;
        /* Remove vertical centering if present */
        align-items: flex-start !important;
    }
    /* Optional -- better responsive for mobile */
    @media (max-width: 576.98px) {
        #bulkShipModal .modal-dialog {
            margin-top: 10px !important;
        }
    }
</style>
<div class="modal" id="bulkShipModal" tabindex="-1" aria-labelledby="bulkShipModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true" style="opacity:1 !important;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="bulkShipModalLabel">
                    <i class="fas fa-shipping-fast me-2"></i>Confirm Bulk Shipping
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="cleanupModal()"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-4">
                    <i class="fas fa-truck" style="font-size: 4rem; color: var(--shipxpeed-success);"></i>
                </div>
                <h4 class="mb-3 text-dark">Ready to ship orders?</h4>
                <p class="text-muted mb-4 lead">
                    You're about to ship <strong class="text-success"><span id="selectedOrdersCount">0</span> orders</strong>.
                    Please review before confirming.
                </p>
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div class="text-start">
                        <strong>Important:</strong> This action cannot be undone.<br>
                        <small>Make sure all order details are correct before shipping.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <button type="button" class="btn btn-light btn-lg me-3" data-bs-dismiss="modal" onclick="cleanupModal()">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <form id="bulkShipForm" method="POST" action="{{ route('seller.courier.bulk') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="order_ids" id="bulkOrderIdsInput">
                    <button type="submit" class="btn btn-success btn-lg" onclick="handleBulkShipSubmit(event)">
                        <i class="fas fa-check me-2"></i>Confirm Shipping
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced JavaScript Section -->
<script>
   
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Enhanced Select All functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const bulkActionForm = document.getElementById('bulkActionForm');
    const updateWarehouseBtn = document.getElementById('updateWarehouseBtn');
    const bulkShipNowBtn = document.getElementById('bulkShipNowBtn');

    // Select all functionality with visual feedback
    selectAllCheckbox?.addEventListener('change', function() {
        const isChecked = this.checked;
        orderCheckboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
            checkbox.closest('tr').classList.toggle('table-active', isChecked);
        });
        updateBulkActionStates();
    });

    // Individual checkbox functionality
    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.closest('tr').classList.toggle('table-active', this.checked);
            
            // Update select all checkbox state
            const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
            selectAllCheckbox.checked = checkedCount === orderCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < orderCheckboxes.length;
            
            updateBulkActionStates();
        });
    });

    function updateBulkActionStates() {
        const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
        const count = checkedBoxes.length;
        
        // Update UI elements
        document.getElementById('selectedOrdersCount').textContent = count;
        document.getElementById('selectedOrdersPreview').textContent = count;
        
        // Enable/disable bulk action buttons
        if (updateWarehouseBtn) {
            updateWarehouseBtn.disabled = count === 0;
        }
        if (bulkShipNowBtn) {
            bulkShipNowBtn.disabled = count === 0;
            bulkShipNowBtn.textContent = count > 0 ? `Ship ${count} Orders` : 'Ship Now (Bulk)';
        }
    }

    // Warehouse update functionality
    updateWarehouseBtn?.addEventListener('click', function() {
        const warehouseId = document.getElementById('warehouse_id').value;
        const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
        
        if (!warehouseId) {
            alert('⚠️ Please select a warehouse');
            return;
        }
        
        if (checkedBoxes.length === 0) {
            alert('⚠️ Please select at least one order');
            return;
        }
        
        // Add warehouse_id to form
        const warehouseInput = document.createElement('input');
        warehouseInput.type = 'hidden';
        warehouseInput.name = 'warehouse_id';
        warehouseInput.value = warehouseId;
        bulkActionForm.appendChild(warehouseInput);
        
        // Show loading state
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
        this.disabled = true;
        
        // Submit form
        bulkActionForm.submit();
    });

    // Enhanced bulk shipping functionality with proper cleanup
    bulkShipNowBtn?.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            showToast('⚠️ Please select at least one order to ship', 'warning');
            return;
        }
        
        const orderIds = Array.from(checkedBoxes).map(cb => cb.value);
        document.getElementById('bulkOrderIdsInput').value = orderIds.join(',');
        document.getElementById('selectedOrdersCount').textContent = checkedBoxes.length;
        
        // Clean up any existing modal instances
        cleanupModal();
        
        // Show modal with proper initialization; remove fade class if present and ensure opacity:1
        const modalElement = document.getElementById('bulkShipModal');
        modalElement.classList.remove('fade');
        modalElement.style.opacity = '1';

        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });
        
        // Add event listener for proper cleanup when modal is hidden
        modalElement.addEventListener('hidden.bs.modal', function() {
            cleanupModal();
        }, { once: true });

        modal.show();
    });

    // Enhanced loading states for action buttons
    document.querySelectorAll('.action-dropdown .dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (this.href && !this.href.includes('#')) {
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            }
        });
    });

    // Auto-refresh functionality (optional)
    const autoRefreshCheckbox = document.getElementById('auto-refresh');
    let refreshInterval;
    
    if (autoRefreshCheckbox) {
        autoRefreshCheckbox.addEventListener('change', function() {
            if (this.checked) {
                refreshInterval = setInterval(() => {
                    location.reload();
                }, 30000); // Refresh every 30 seconds
            } else {
                clearInterval(refreshInterval);
            }
        });
    }

    // Enhanced search functionality (if search input exists)
    const searchInput = document.getElementById('order-search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Implement search functionality here
                console.log('Searching for:', this.value);
            }, 500);
        });
    }

    // Initialize date range picker (requires external library)
    const dateRangeInput = document.getElementById('daterange');
    if (dateRangeInput && typeof daterangepicker !== 'undefined') {
        $(dateRangeInput).daterangepicker({
            opens: 'left',
            locale: {
                format: 'DD/MM/YYYY'
            }
        });
    }

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + A to select all
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !e.target.matches('input, textarea')) {
            e.preventDefault();
            selectAllCheckbox.checked = !selectAllCheckbox.checked;
            selectAllCheckbox.dispatchEvent(new Event('change'));
        }
        
        // Escape to deselect all
        if (e.key === 'Escape') {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.dispatchEvent(new Event('change'));
        }
    });

    // Initialize animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    });

    document.querySelectorAll('.table-container, .toggle-section, .status-filter-row').forEach(el => {
        observer.observe(el);
    });

    console.log('📦 Order Management System Loaded Successfully!');
});

// Enhanced modal cleanup function
function cleanupModal() {
    // Remove all modal backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Remove modal-open class from body
    document.body.classList.remove('modal-open');
    
    // Reset body styles
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // Hide the modal properly
    const modalElement = document.getElementById('bulkShipModal');
    if (modalElement) {
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.removeAttribute('aria-modal');
        modalElement.removeAttribute('role');
        modalElement.style.opacity = '1'; // Make sure opacity stays 1 for further use
    }
    
    // Re-enable scrolling
    document.documentElement.style.overflow = '';
    
    // Clear any modal-related timeouts
    clearTimeout(window.modalTimeout);
}

// Enhanced bulk ship form submit handler
function handleBulkShipSubmit(event) {
    const form = event.target.closest('form');
    const button = event.target;
    
    // Add loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    button.disabled = true;
    
    // Add a small delay to show loading state
    setTimeout(() => {
        form.submit();
    }, 500);
}

// Additional cleanup on page unload
window.addEventListener('beforeunload', function() {
    cleanupModal();
});

// Emergency cleanup function (can be called from console if needed)
window.emergencyCleanup = function() {
    cleanupModal();
    // Remove any stuck overlays
    const overlays = document.querySelectorAll('.modal, .modal-backdrop, .fade');
    overlays.forEach(overlay => {
        if (overlay.classList.contains('modal-backdrop') || overlay.classList.contains('modal')) {
            overlay.remove();
        }
    });
    
    // Reset document state
    document.body.className = document.body.className.replace(/modal-[a-z-]+/g, '');
    document.body.style.cssText = '';
    
    console.log('🧹 Emergency cleanup completed');
};
</script>

                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Enhanced JavaScript with modern functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Add fade-in animation to elements
        document.querySelector('.pc-container').classList.add('fade-in');

        // Enhanced Select All Checkboxes with visual feedback
        const selectAllCheckbox = document.getElementById('select-all');
        const orderCheckboxes = document.querySelectorAll('.order-checkbox');

        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            orderCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                // Add visual feedback
                const row = checkbox.closest('tr');
                if (isChecked) {
                    row.style.background = 'rgba(99, 102, 241, 0.1)';
                    row.style.borderLeft = '4px solid var(--shipxpeed-primary)';
                } else {
                    row.style.background = '';
                    row.style.borderLeft = '';
                }
            });
            
            // Update bulk action button state
            updateBulkActionState();
        });

        // Individual checkbox handling
        orderCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const row = this.closest('tr');
                if (this.checked) {
                    row.style.background = 'rgba(99, 102, 241, 0.1)';
                    row.style.borderLeft = '4px solid var(--shipxpeed-primary)';
                } else {
                    row.style.background = '';
                    row.style.borderLeft = '';
                }
                
                // Update select all checkbox state
                const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
                selectAllCheckbox.checked = checkedBoxes.length === orderCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < orderCheckboxes.length;
                
                updateBulkActionState();
            });
        });

        // Update bulk action button state
        function updateBulkActionState() {
            const selectedCount = document.querySelectorAll('.order-checkbox:checked').length;
            const bulkActionBtn = document.getElementById('bulkActionDropdown');
            
            if (selectedCount > 0) {
                bulkActionBtn.classList.remove('btn-light');
                bulkActionBtn.classList.add('btn-success');
                bulkActionBtn.innerHTML = `<i class="ti ti-settings me-2"></i>Bulk Actions (${selectedCount})`;
            } else {
                bulkActionBtn.classList.remove('btn-success');
                bulkActionBtn.classList.add('btn-light');
                bulkActionBtn.innerHTML = '<i class="ti ti-settings me-2"></i>Bulk Actions';
            }
        }

        // Enhanced Bulk Shipping Handler with validation
        const bulkShipBtn = document.getElementById('bulkShipNowBtn');
        if (bulkShipBtn) {
            bulkShipBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const selected = document.querySelectorAll('.order-checkbox:checked');

                if (selected.length === 0) {
                    // Show toast notification instead of alert
                    showToast('Please select at least one order.', 'warning');
                    return;
                }

                // Update modal display with animation
                const countElement = document.getElementById('selectedOrdersCount');
                countElement.textContent = selected.length;
                countElement.style.color = 'var(--shipxpeed-primary)';
                countElement.style.fontWeight = 'bold';

                // Remove old inputs if any
                document.querySelectorAll('#bulkShipForm input[name="order_ids[]"]').forEach(el => el.remove());

                // Add new hidden inputs for each selected order ID
                Array.from(selected).forEach(el => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'order_ids[]';
                    hiddenInput.value = el.value;
                    document.getElementById('bulkShipForm').appendChild(hiddenInput);
                });

                // Show modal WITHOUT fade or opacity
                const modalElement = document.getElementById('bulkShipModal');
                modalElement.classList.remove('fade');
                modalElement.style.opacity = '1';
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            });
        }

        // Enhanced Warehouse Update Handler
        document.getElementById('updateWarehouseBtn').addEventListener('click', function() {
            const warehouseId = document.getElementById('warehouse_id').value;
            const selectedOrders = document.querySelectorAll('.order-checkbox:checked').length;
            
            if (!warehouseId) {
                showToast('Please select a warehouse.', 'warning');
                return;
            }
            
            if (selectedOrders === 0) {
                showToast('Please select at least one order.', 'warning');
                return;
            }

            // Add loading state
            this.innerHTML = '<i class="ti ti-loader ti-spin me-2"></i>Updating...';
            this.disabled = true;

            // Submit form
            setTimeout(() => {
                document.getElementById('bulkActionForm').submit();
            }, 500);
        });

        // Toast notification function
        function showToast(message, type = 'info') {
            const toastContainer = getOrCreateToastContainer();
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type} border-0 show`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="ti ti-${type === 'warning' ? 'alert-triangle' : type === 'success' ? 'check' : 'info-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            toastContainer.appendChild(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }

        function getOrCreateToastContainer() {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container position-fixed top-0 end-0 p-3';
                container.style.zIndex = '1055';
                document.body.appendChild(container);
            }
            return container;
        }

        // Add smooth scrolling to table
        const tableContainer = document.querySelector('.table-responsive');
        if (tableContainer) {
            tableContainer.style.scrollBehavior = 'smooth';
        }

        // Add loading states to action buttons
        document.querySelectorAll('.dropdown-item').forEach(item => {
            if (item.href && !item.href.includes('#')) {
                item.addEventListener('click', function() {
                    this.innerHTML = '<i class="ti ti-loader ti-spin me-2"></i>' + this.textContent.trim();
                });
            }
        });

        // Initialize date range picker if available
        const dateInput = document.getElementById('daterange');
        if (dateInput && typeof flatpickr !== 'undefined') {
            flatpickr(dateInput, {
                mode: "range",
                dateFormat: "Y-m-d",
                theme: "material_blue"
            });
        }
    });

    // Set active state for status buttons
    function setActive(element) {
        // Remove active class from all buttons in the same group
        const group = element.closest('.status-buttons');
        if (group) {
            group.querySelectorAll('.status-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            element.classList.add('active');
        }
    }

    // Add click handlers for status buttons
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                setActive(this);
            });
        });
    });
</script>
@endsection
