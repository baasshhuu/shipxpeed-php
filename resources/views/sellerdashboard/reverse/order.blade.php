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
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: var(--shipxpeed-radius-lg);
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        box-shadow: var(--shipxpeed-shadow-xl);
        border: 1px solid rgba(255, 255, 255, 0.2);
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
    .file-upload-container {
        background: var(--shipxpeed-light);
        border: 2px dashed var(--shipxpeed-border);
        border-radius: var(--shipxpeed-radius);
        padding: 0.5rem;
        transition: var(--shipxpeed-transition);
    }

    .file-upload-container:hover {
        border-color: var(--shipxpeed-primary);
        background: rgba(99, 102, 241, 0.05);
    }

    .input-group .form-control {
        border-radius: var(--shipxpeed-radius) 0 0 var(--shipxpeed-radius);
        border-right: none;
        border: 2px solid var(--shipxpeed-border);
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
        background: white;
        border-radius: var(--shipxpeed-radius-lg);
        padding: 0.75rem;
        margin-bottom: 1rem;
        box-shadow: var(--shipxpeed-shadow-lg);
        border: 1px solid var(--shipxpeed-border);
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
        padding: 0.4rem 0.8rem;
        transition: var(--shipxpeed-transition);
        border: 2px solid transparent;
        position: relative;
        margin-bottom: 0.25rem;
        font-size: 0.92rem;
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
        box-shadow: var(--shipxpeed-shadow-xl);
        border: 1px solid var(--shipxpeed-border);
        backdrop-filter: blur(10px);
    }

    .table-header {
        background: linear-gradient(135deg, var(--shipxpeed-dark) 0%, #374151 100%);
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
        border-bottom: 1px solid var(--shipxpeed-border-light);
    }

    .table tbody tr:hover {
        background: rgba(99, 102, 241, 0.05);
        box-shadow: inset 4px 0 0 var(--shipxpeed-primary);
    }

    .table tbody td {
        padding: 0.6rem 0.5rem;
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
        border: 2px solid var(--shipxpeed-border);
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


    .table-header {
        background: linear-gradient(135deg, var(--shipxpeed-primary) 0%, var(--shipxpeed-primary-light) 100%);
        padding: 1.25rem 1.5rem;
        color: white;
    }

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
        border-bottom: 1px solid var(--shipxpeed-border);
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .table tbody tr.selected {
        background: rgba(99, 102, 241, 0.05);
        border-left: 3px solid var(--shipxpeed-primary);
    }

    .table tbody td {
        padding: 0.875rem 0.75rem;
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
    .badge.bg-warning { background-color: var(--shipxpeed-warning) !important; color: white !important; }
    .badge.bg-info { background-color: var(--shipxpeed-info) !important; }
    .badge.bg-primary { background-color: var(--shipxpeed-primary) !important; }
    .badge.bg-secondary { background-color: var(--shipxpeed-dark) !important; }

    /* Product Info Styling */
    .product-info .product-item {
        margin-bottom: 0.5rem;
    }

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

    /* Enhanced Color System */
    .bg-danger { 
        background: linear-gradient(135deg, var(--shipxpeed-danger) 0%, var(--shipxpeed-danger-light) 100%) !important; 
    }
    .bg-success { 
        background: linear-gradient(135deg, var(--shipxpeed-success) 0%, var(--shipxpeed-success-light) 100%) !important; 
    }
    .bg-warning { 
        background: linear-gradient(135deg, var(--shipxpeed-warning) 0%, var(--shipxpeed-warning-light) 100%) !important; 
        color: white !important; 
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
    <!-- Enhanced Action Buttons Section -->
    <!-- <div class="toggle-section slide-in">
        <div class="row g-3 align-items-center">
            <div class="col-lg-4 col-md-6 col-12 mb-2 mb-lg-0">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('seller.orders.download-template') }}" class="btn btn-success w-100" data-bs-toggle="tooltip" title="Download CSV template">
                        <i class="fas fa-download me-2"></i>Download Template
                    </a>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 col-12 mb-2 mb-lg-0">
                <div class="file-upload-container p-3 h-100 d-flex align-items-center justify-content-center">
                    <form action="{{ route('seller.orders.import') }}" method="POST" enctype="multipart/form-data" class="w-100 d-flex align-items-center gap-2">
                        @csrf
                        <div class="input-group" style="max-width: 320px;">
                            <input type="file" name="excel_file" accept=".csv, .xlsx" class="form-control" required>
                            <button type="submit" class="btn btn-dark" data-bs-toggle="tooltip" title="Upload orders file">
                                <i class="fas fa-upload me-1"></i> Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            

            <div class="col-lg-4 col-12">
                <div class="d-flex gap-2 justify-content-lg-end justify-content-start">
                    <a href="{{ route('seller.reverseorderadd') }}" class="btn btn-outline-success" data-bs-toggle="tooltip" title="Create new order">
                        <i class="fas fa-plus me-1"></i>Add Order
                    </a>
                </div>
            </div>
        </div>
    </div> -->




      <div class="toggle-section slide-in">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <!-- Primary Actions -->
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- <button class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Forward selected orders">
                    <i class="fas fa-arrow-right me-2"></i>Forward
                </button>
                <button class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Reverse selected orders">
                    <i class="fas fa-arrow-left me-2"></i>Reverse
                </button> -->
                
                <a href="{{ route('seller.orders.download-template') }}" class="btn btn-success" data-bs-toggle="tooltip" title="Download CSV template">
                    <i class="fas fa-download me-2"></i>Download Template
                </a>
            </div>

            <!-- Enhanced File Upload Section -->
            <div class="file-upload-container">
                <form action="{{ route('seller.orders.import') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                    @csrf
                    <div class="input-group" style="max-width: 320px;">
                        <input type="file" name="excel_file" accept=".csv, .xlsx" class="form-control" required>
                        <button type="submit" class="btn btn-dark" data-bs-toggle="tooltip" title="Upload orders file">
                            <i class="fas fa-upload me-1"></i> Upload
                        </button>
                    </div>
                </form>
            </div>

             <!-- <div class="file-upload-container">
                <form action="{{ route('seller.orders.import') }}" method="POST"  class="d-flex align-items-center gap-2">
                    @csrf
                    <div class="input-group" style="max-width: 320px;">
                        <input type="text" name="awb"  class="form-control" required>
                        <button type="submit" class="btn btn-dark" data-bs-toggle="tooltip" title="Upload orders file">
                            <i class="fas fa-upload me-1"></i> Submit
                        </button>
                    </div>
                </form>
            </div> -->
    <div class="file-upload-container">
        <form action="{{ route('seller.orders.import') }}" method="POST" class="d-flex align-items-center gap-2">
            @csrf

            <div class="input-group" style="max-width: 320px;">
                <input type="text" name="awb" class="form-control" required placeholder="Enter AWB Number">

                <button type="submit" class="btn btn-dark" data-bs-toggle="tooltip" title="Submit AWB">
                    <i class="fas fa-upload me-1"></i> Submit
                </button>
            </div>

        </form>
    </div>


            <!-- Secondary Actions -->
            <div class="d-flex gap-2">
                <!-- <button class="btn btn-outline-info" data-bs-toggle="tooltip" title="Sync orders from all channels">
                    <i class="fas fa-sync me-1"></i>Sync Orders
                </button>
                <button class="btn btn-outline-warning" data-bs-toggle="tooltip" title="Import multiple orders">
                    <i class="fas fa-file-import me-1"></i>Bulk Import
                </button> -->
                <a href="{{ route('seller.reverseorderadd') }}" class="btn btn-outline-success" data-bs-toggle="tooltip" title="Create new order">
                    <i class="fas fa-plus me-1"></i>Add Order
                </a>
            </div>
        </div>
    </div>


    <!-- Enhanced Status Filter Buttons -->
    <div class="status-filter-row slide-in">
        <h5 class="mb-3 text-dark fw-bold">
            <i class="fas fa-filter me-2 text-primary"></i>Filter by Status
        </h5>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('seller.order') }}" class="btn btn-outline-secondary position-relative" data-bs-toggle="tooltip" title="View all orders">
                <i class="fas fa-list me-1"></i>All Orders
                @if(isset($orderCounts['new']) && $orderCounts['new'] > 0)
                <span class="badge bg-secondary position-absolute top-0 start-100 translate-middle">
                    {{ $orderCounts['new'] }}
                </span>
                @endif
            </a>
            
            <a href="{{ route('seller.courier.Assigned') }}" class="btn btn-outline-success position-relative" data-bs-toggle="tooltip" title="Orders assigned to courier">
                <i class="fas fa-truck me-1"></i>Courier Assigned
                @if(isset($orderCounts['assigned']) && $orderCounts['assigned'] > 0)
                <span class="badge bg-success position-absolute top-0 start-100 translate-middle">
                    {{ $orderCounts['assigned'] }}
                </span>
                @endif
            </a>
            
            <a href="{{ route('seller.courier.InTransit') }}" class="btn btn-outline-info position-relative" data-bs-toggle="tooltip" title="Orders in transit">
                <i class="fas fa-shipping-fast me-1"></i>In Transit
                @if(isset($orderCounts['in_transit']) && $orderCounts['in_transit'] > 0)
                <span class="badge bg-info position-absolute top-0 start-100 translate-middle">
                    {{ $orderCounts['in_transit'] }}
                </span>
                @endif
            </a>
            
            <a href="{{ route('seller.courier.OutForDelivery') }}" class="btn btn-outline-primary position-relative" data-bs-toggle="tooltip" title="Orders out for delivery">
                <i class="fas fa-map-marker-alt me-1"></i>Out For Delivery
                @if(isset($orderCounts['out_for_delivery']) && $orderCounts['out_for_delivery'] > 0)
                <span class="badge bg-primary position-absolute top-0 start-100 translate-middle">
                    {{ $orderCounts['out_for_delivery'] }}
                </span>
                @endif
            </a>
            
            <a href="{{ route('seller.courier.Delivered') }}" class="btn btn-outline-success position-relative" data-bs-toggle="tooltip" title="Successfully delivered orders">
                <i class="fas fa-check-circle me-1"></i>Delivered
                @if(isset($orderCounts['delivered']) && $orderCounts['delivered'] > 0)
                <span class="badge bg-success position-absolute top-0 start-100 translate-middle">
                    {{ $orderCounts['delivered'] }}
                </span>
                @endif
            </a>
            
            <a href="{{ route('seller.courier.Cancelled') }}" class="btn btn-outline-danger position-relative" data-bs-toggle="tooltip" title="Cancelled orders">
                <i class="fas fa-times-circle me-1"></i>Cancelled
                @if(isset($orderCounts['cancelled']) && $orderCounts['cancelled'] > 0)
                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                    {{ $orderCounts['cancelled'] }}
                </span>
                @endif
            </a>
            
            <a href="{{ route('seller.courier.NDR') }}" class="btn btn-outline-warning position-relative" data-bs-toggle="tooltip" title="Non-delivery report orders">
                <i class="fas fa-exclamation-triangle me-1"></i>NDR
                @if(isset($orderCounts['ndr']) && $orderCounts['ndr'] > 0)
                <span class="badge bg-warning position-absolute top-0 start-100 translate-middle">
                    {{ $orderCounts['ndr'] }}
                </span>
                @endif
            </a>
            
            <a href="{{ route('seller.courier.RTO') }}" class="btn btn-outline-danger position-relative" data-bs-toggle="tooltip" title="Return to origin orders">
                <i class="fas fa-undo me-1"></i>RTO
                @if(isset($orderCounts['rto']) && $orderCounts['rto'] > 0)
                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                    {{ $orderCounts['rto'] }}
                </span>
                @endif
            </a>
            
            <a href="#" class="btn btn-outline-secondary position-relative" data-bs-toggle="tooltip" title="Other status orders">
                <i class="fas fa-ellipsis-h me-1"></i>Other
                @if(isset($orderCounts['other']) && $orderCounts['other'] > 0)
                <span class="badge bg-secondary position-absolute top-0 start-100 translate-middle">
                    {{ $orderCounts['other'] }}
                </span>
                @endif
            </a>
        </div>
    </div>

    <!-- Enhanced Orders Table Section -->
    <div class="table-container">
        <!-- Table Header with Controls -->
        <div class="table-header">
            <div class="table-controls">
                <div class="d-flex align-items-center gap-3">
                    <div class="input-group" style="max-width: 250px;">
                        <span class="input-group-text bg-transparent border-0 text-white">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <input type="text" id="daterange" class="date-input" placeholder="Select Date Range">
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="bulkActionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog me-2"></i>Bulk Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#warehouseModal">
                                    <i class="fas fa-warehouse me-2"></i>Update Warehouse
                                </a>
                            </li>
                            @php
                            $seller = Auth::guard('seller')->user();
                            @endphp

                            @if($seller && $seller->id == 14)
                            <li>
                                <a class="dropdown-item" href="#" id="bulkShipNowBtn">
                                    <i class="fas fa-truck me-2"></i>Ship Now (Bulk)
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                    
                    <button class="btn btn-light" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Orders Table -->
        <div class="table-responsive">
            <form id="bulkActionForm" action="{{ route('seller.orders.update-warehouse') }}" method="POST">
                @csrf
                <table class="table table-hover text-center mb-0">
                    <thead>
                        <tr>
                            <th width="50" class="text-start">
                                <div class="form-check">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </div>
                            </th>
                            <th width="100">
                                <i class="fas fa-cog me-1"></i>Actions
                            </th>
                            <th>
                                <i class="fas fa-hashtag me-1"></i>Order #
                            </th>
                            <th>
                                <i class="fas fa-barcode me-1"></i>AWB Number
                            </th>
                            <th>
                                <i class="fas fa-box me-1"></i>Product
                            </th>
                            <th>
                                <i class="fas fa-rupee-sign me-1"></i>Payment
                            </th>
                            <th>
                                <i class="fas fa-coins me-1"></i>Collectable
                            </th>
                            <th>
                                <i class="fas fa-credit-card me-1"></i>Method
                            </th>
                            <th>
                                <i class="fas fa-user me-1"></i>Customer
                            </th>
                            <th>
                                <i class="fas fa-map-pin me-1"></i>Zip Code
                            </th>
                            <th>
                                <i class="fas fa-weight me-1"></i>Weight
                            </th>
                            <th>
                                <i class="fas fa-warehouse me-1"></i>Warehouse
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                            @forelse ($orders as $order)
                            <tr class="order-row">
                                <td>
                                    <div class="form-check">
                                        <input type="checkbox" class="order-checkbox form-check-input" name="order_ids[]" value="{{ $order->id }}">
                                        <label class="form-check-label"></label>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-dropdown">
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots me-1"></i>Action
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('seller.reverse.courier', $order->id) }}">
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
                                <td>
                                    <div class="order-number">
                                        <strong class="text-primary">#{{ $order->order_number }}</strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="awb-container">
                                        @if($order->awb_number)
                                            <span class="badge bg-success">{{ $order->awb_number }}</span>
                                        @else
                                            <span class="badge bg-secondary">Not Assigned</span>
                                        @endif
                                    </div>
                                </td>

     <td>
    @php
        $items = is_array($order->order_items) ? $order->order_items : json_decode($order->order_items, true);
        $firstItem = $items[0] ?? null;
        $allNames = collect($items)->pluck('name')->implode(', ');
        
        // Limit to first 20 words for display
        if ($firstItem) {
            $words = explode(' ', $firstItem['name']);
            $limitedWords = implode(' ', array_slice($words, 0, 3));
            if (count($words) > 3) {
                $limitedWords .= '...';
            }
        }
    @endphp

    @if($firstItem)
        <div class="product-info">
            <div class="product-item" title="{{ $allNames }}">
                <div class="product-name text-dark fw-medium">
                    {{ $limitedWords }}
                    @if(count($items) > 1)
                        <span class="text-muted">(+{{ count($items) - 1 }} more)</span>
                    @endif
                </div>
                <small class="text-muted">
                    <i class="ti ti-package-export me-1"></i>
                    Qty: {{ $firstItem['quantity'] ?? '1' }}
                </small>
            </div>
        </div>
    @else
        <div class="text-muted">No items</div>
    @endif
</td>

                                <!-- <td>
                                    <div class="product-info">
                                        @php
                                        $items = is_array($order->order_items) ? $order->order_items : json_decode($order->order_items, true);
                                        @endphp
                                        @foreach ($items as $item)
                                        <div class="product-item mb-1">

                                               @php
                                                $words = explode(' ', $item['name'] ?? 'N/A');
                                                $limitedWords = implode(' ', array_slice($words, 0, 2));
                                                if(count($words) > 2) {
                                                    $limitedWords .= '...';
                                                }
                                            @endphp

                                            <div class="product-name text-dark fw-medium">
                                                {{ $limitedWords }}
                                            </div>

                                            {{-- <div class="product-name text-dark fw-medium">{{ $item['name'] ?? 'N/A' }}</div> --}}
                                            <small class="text-muted">
                                                <i class="ti ti-package-export me-1"></i>Qty: {{ $item['quantity'] ?? '1' }}
                                            </small>
                                        </div>
                                        @endforeach
                                    </div>
                                </td> -->
                                <td>
                                    <span class="badge bg-primary fs-6">₹{{ number_format($order->order_amount, 2) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-success fs-6">₹{{ number_format($order->collectable_amount, 2) }}</span>
                                </td>
                                <td>
                                    @php
                                    $paymentType = strtolower($order->payment_type);
                                    $badgeClass = $paymentType === 'cod' ? 'bg-warning' : 'bg-info';
                                    $icon = $paymentType === 'cod' ? 'ti-cash' : 'ti-credit-card';
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">
                                        <i class="ti {{ $icon }} me-1"></i>{{ strtoupper($order->payment_type) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        @php
                                        $consignee = is_array($order->consignee) ? $order->consignee : json_decode($order->consignee, true);
                                        @endphp
                                        <div class="fw-medium text-dark">{{ $consignee['name'] ?? 'N/A' }}</div>
                                        @if(isset($consignee['phone']))
                                        <small class="text-muted">
                                            <i class="ti ti-phone me-1"></i>{{ $consignee['phone'] }}
                                        </small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @php
                                    $pickup = is_array($order->pickup) ? $order->pickup : json_decode($order->pickup, true);
                                    @endphp
                                    <span class="badge bg-secondary">{{ $pickup['pincode'] ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <i class="ti ti-weight me-1"></i>{{ $order->package_weight }}kg
                                    </span>
                                </td>
                                <td>
                                    @php
                                    $pickup = is_array($order->pickup) ? $order->pickup : json_decode($order->pickup, true);
                                    $warehouseName = $pickup['warehouse_name'] ?? 'Not assigned';
                                    @endphp
                                    @if($warehouseName !== 'Not assigned')
                                        <span class="badge bg-primary">
                                            <i class="fas fa-warehouse me-1"></i>{{ $warehouseName }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-exclamation-circle me-1"></i>Not Assigned
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-box-open" style="font-size: 4rem; color: var(--shipxpeed-gray); margin-bottom: 1.5rem;"></i>
                                        <h4 class="text-muted mb-3">No orders found</h4>
                                        <p class="text-muted mb-4">You haven't created any orders yet, or none match your current filters.</p>
                                        <div class="d-flex justify-content-center gap-2">
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
                <div class="p-4 border-top bg-light">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="text-muted d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            Showing <strong>{{ $orders->firstItem() ?? 0 }}</strong> to <strong>{{ $orders->lastItem() ?? 0 }}</strong> 
                            of <strong>{{ $orders->total() ?? 0 }}</strong> orders
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

<!-- Enhanced Bulk Shipping Confirmation Modal -->
<div class="modal fade" id="bulkShipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-shipping-fast me-2"></i>Confirm Bulk Shipping
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                <button type="button" class="btn btn-light btn-lg me-3" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <form id="bulkShipForm" method="POST" action="{{ route('seller.courier.bulk') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="order_ids" id="bulkOrderIdsInput">
                    <button type="submit" class="btn btn-success btn-lg">
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

    // Bulk shipping functionality
    bulkShipNowBtn?.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            alert('⚠️ Please select at least one order to ship');
            return;
        }
        
        const orderIds = Array.from(checkedBoxes).map(cb => cb.value);
        document.getElementById('bulkOrderIdsInput').value = orderIds.join(',');
        
        // Show modal
        new bootstrap.Modal(document.getElementById('bulkShipModal')).show();
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

                // Show modal with animation
                const modal = new bootstrap.Modal(document.getElementById('bulkShipModal'));
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

