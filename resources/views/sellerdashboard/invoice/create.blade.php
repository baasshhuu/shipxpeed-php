@extends('layouts.sellerdash')

@section('content')
    <style>
        .modern-card {
            /* background: linear-gradient(145deg, #ffffff, #f8f9fa); */
            background:#646dff26;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            width: 100%;
            margin: 0;
        }

        .header-section {
            /* background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #4f46e5 100%); */
            padding: 0.8rem 1rem;
            margin: 0 0 1rem 0;
            color: black;
            border-radius: 7px;
            position: relative;
            margin-left: 10px;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .page-title {
            font-size: 0.8rem;
            font-weight: 600;
            margin: 0;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
        }

        .page-subtitle {
            font-size: 0.75rem;
            opacity: 0.85;
            margin: 0.2rem 0 0 0;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }

        .date-input {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .date-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            transform: translateY(-1px);
        }

        .modern-table {
            background: white;
            border-radius: 7px;
            /* margin-left:12px; */
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .modern-table thead th {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 0.8rem 1rem;
            font-size: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .modern-table tbody tr {
            transition: all 0.3s ease;
            border: none;
        }

        .modern-table tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .modern-table tbody td {
            padding: 0.8rem 1rem;
            border: none;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .month-cell {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .action-btn {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            border-radius: 8px;
            padding: 0.4rem 1rem;
            color: white;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
            font-size: 0.8rem;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }

        .action-btn:active {
            transform: translateY(0);
        }

        .icon-wrapper {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.6rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }

        .filter-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }

        .stats-card {
            background: linear-gradient(145deg, #ffffff, #fafafa);
            border-radius: 7px;
            padding: 0.6rem 0.8rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(79, 70, 229, 0.1);
            margin-bottom: 1rem;
            margin-left: 10px;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
        }

        .stats-card i {
            font-size: 0.9rem;
            color: #4f46e5;
        }

        .stats-card h6 {
            font-size: 0.65rem;
            margin-bottom: 0.1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-card h4 {
            font-size: 1.1rem;
            margin: 0;
            font-weight: 600;
            color: #1f2937;
        }

        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .table-container {
            background: white;
            border-radius: 7px;
            margin-left:12px;
            padding: 0;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
            overflow-x: auto;
        }

        /* Enhanced Responsive Design */
        @media (max-width: 1200px) {
            .pc-container {
                padding: 0 10px;
            }
        }

        @media (max-width: 992px) {
            .pc-container {
                padding: 0 15px;
            }
            
            .modern-card {
                border-radius: 12px;
                margin: 0;
            }
            
            .header-section {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.3rem;
            }
            
            .filter-section {
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .pc-container {
                padding: 0 10px;
            }
            
            .modern-card {
                border-radius: 8px;
                margin: 0;
                width: 100%;
            }
            
            .page-title {
                font-size: 1.1rem;
            }
            
            .page-subtitle {
                font-size: 0.7rem;
            }
            
            .header-section {
                padding: 0.8rem;
                margin-bottom: 1rem;
            }
            
            .icon-wrapper {
                width: 28px;
                height: 28px;
                margin-right: 0.5rem;
            }
            
            .stats-card {
                padding: 0.7rem;
                margin-bottom: 1rem;
            }
            
            .stats-card h4 {
                font-size: 1rem;
            }
            
            .stats-card h6 {
                font-size: 0.7rem;
            }
            
            .modern-table thead th {
                padding: 0.6rem 0.8rem;
                font-size: 0.7rem;
            }
            
            .modern-table tbody td {
                padding: 0.6rem 0.8rem;
                font-size: 0.8rem;
            }
            
            .action-btn {
                padding: 0.35rem 0.8rem;
                font-size: 0.75rem;
            }
            
            .month-cell {
                font-size: 0.9rem;
            }
            
            .table-responsive {
                border-radius: 8px;
            }
        }

        @media (max-width: 576px) {
            .pc-container {
                padding: 0 8px;
            }
            
            .modern-card {
                border-radius: 6px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }
            
            .page-title {
                font-size: 1rem;
            }
            
            .page-subtitle {
                font-size: 0.65rem;
            }
            
            .header-section {
                padding: 0.6rem;
                border-radius: 6px 6px 0 0;
            }
            
            .icon-wrapper {
                width: 24px;
                height: 24px;
                margin-right: 0.4rem;
            }
            
            .icon-wrapper i {
                font-size: 0.7rem;
            }
            
            .stats-card {
                padding: 0.6rem;
                border-radius: 6px;
            }
            
            .stats-card h4 {
                font-size: 0.9rem;
            }
            
            .stats-card h6 {
                font-size: 0.65rem;
            }
            
            .modern-table thead th {
                padding: 0.5rem 0.6rem;
                font-size: 0.65rem;
            }
            
            .modern-table tbody td {
                padding: 0.5rem 0.6rem;
                font-size: 0.75rem;
            }
            
            .action-btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.7rem;
                border-radius: 6px;
            }
            
            .month-cell {
                font-size: 0.8rem;
            }
            
            .table-container {
                border-radius: 6px;
                box-shadow: 0 1px 8px rgba(0, 0, 0, 0.04);
            }
            
            /* Stack table content vertically on very small screens */
            .modern-table {
                font-size: 0.75rem;
            }
            
            /* Ensure table doesn't break layout */
            .table-responsive {
                -webkit-overflow-scrolling: touch;
            }
        }

        @media (max-width: 480px) {
            .header-section .d-flex {
                flex-direction: column;
                text-align: center;
            }
            
            .icon-wrapper {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
            
            .stats-card .d-flex {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-card i {
                margin-bottom: 0.5rem;
                margin-right: 0 !important;
            }
        }

        @media (max-width: 380px) {
            .modern-card .p-3 {
                padding: 0.8rem !important;
            }
            
            .page-title {
                font-size: 0.9rem;
            }
            
            .action-btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.65rem;
            }
            
            .modern-table thead th,
            .modern-table tbody td {
                padding: 0.4rem 0.5rem;
            }
        }

        /* Landscape orientation adjustments for tablets */
        @media (orientation: landscape) and (max-width: 1024px) {
            .header-section {
                padding: 0.7rem 1rem;
            }
            
            .page-title {
                font-size: 1.1rem;
            }
        }
        .pb-top-nav-tabs {
            background: white;
            border-radius: 10px;
            padding: 0.8rem;
            /* box-shadow: 0 5px 20px rgba(0,0,0,0.1); */
            margin-bottom: 1rem;
            margin-left: 10px;
        }
        .pb-nav-tab {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.7rem;
            margin: 0 0.2rem;
            border-radius: 10px;
            text-decoration: none;
            color: #6c757d;
            font-weight: 500;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .pb-nav-tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .pb-nav-tab.active,
        .pb-nav-tab:hover {
            color: white;
            backgorund:linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            transform: translateY(-1px);
            box-shadow: 0 3px 15px rgba(102, 126, 234, 0.4);
        }

        .pb-nav-tab.active::before,
        .pb-nav-tab:hover::before {
            left: 0;
        }
        .pb-nav-tab i {
            margin-right: 0.4rem;
            font-size: 1rem;
        }
    </style>

    <div class="pc-container" style="padding: 0;">
        <div class="mt-2">
            <div class="modern-card p-3 fade-in">
                <!-- Header Section -->
                <!-- <div class="header-section">
                    <div class="d-flex align-items-center justify-content-center text-center flex-wrap">
                        <div class="icon-wrapper">
                            <i class="fas fa-chart-line fa-lg"></i>
                        </div>
                        <div class="">
                            <h1 class="page-title">Monthly Reports</h1>
                            <p class="page-subtitle">View and analyze your monthly performance data</p>
                        </div>
                    </div>
                </div> -->
                <div class="pb-top-nav-tabs pb-slide-in">
                    <div class="">
                        <a href="{{ route('seller.passbook') }}" class="pb-nav-tab">
                            <i class="ti ti-dashboard"></i> Passbook
                        </a>
                        <a href="{{ route('seller.cod') }}" class="pb-nav-tab">
                            <i class="ti ti-shopping-cart"></i> COD Remittance
                        </a>
                        <a href="{{ route('seller.shippingcharge') }}" class="pb-nav-tab">
                            <i class="ti ti-truck"></i> Shipping Charges
                        </a>
                        <a href="{{ route('seller.recharge') }}" class="pb-nav-tab">
                            <i class="fa-regular fa-wallet"></i> All Recharges
                        </a>
                        <a href="{{ route('seller.invoice.add') }}" class="pb-nav-tab active">
                            <i class="fa-regular fa-money-check-dollar"></i> Invoices
                        </a>

                        
                    </div>
                </div>
                <div class="header-section">
                    <div class="d-flex align-items-center justify-content-center text-center flex-wrap">
                        <div class="icon-wrapper">
                            <i class="fas fa-chart-line fa-lg"></i>
                        </div>
                        <div class="">
                            <h1 class="page-title">Monthly Reports</h1>
                            <p class="page-subtitle">View and analyze your monthly performance data</p>
                        </div>
                    </div>
                </div>
                <!-- Stats Card -->
                <div class="stats-card">
                    <div class="d-flex align-items-center flex-wrap justify-content-center justify-content-md-start">
                        <i class="fas fa-database me-3" style="font-size: 0.9rem; color: #4f46e5;"></i>
                        <div class="text-center text-md-start">
                            <h6 class="mb-1 text-muted">Total Reports Available</h6>
                            <h4 class="mb-0 text-primary">{{ count($months) }} Months</h4>
                        </div>
                    </div>
                </div>

                <!-- Table Container -->
                <div class="table-container">
                    <div class="table-responsive">
                        <table class="table modern-table mb-0">
                            <thead>
                                <tr>
                                    <th>
                                        
                                        Invoice
                                    </th>
                                    <th>
                                        
                                        Invoice Date
                                    </th>
                                    <th>
                                        
                                        Amount
                                    </th>
                                    <th>
                                        
                                        Shipment Count
                                    </th>
                                    <th>
                                        
                                        Settlements
                                    </th>
                                    <th>
                                       
                                        Status
                                    </th>
                                    <th>
                                        
                                        Payment Date
                                    </th>
                                    <th>
                                        
                                        Ageing
                                    </th>
                                    <th>
                                        
                                        Payment Transaction ID
                                    </th>
                                    <th>
                                       
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($months as $month)
                                <tr>
                                    <td></td>
                                    <td><i class="far fa-calendar-alt me-2 text-muted"></i>{{ $month }}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="month-cell"></td>
                                    <td>
                                        <a href="{{ route('seller.monthly.report', ['month' => $month]) }}" 
                                           class="action-btn">
                                            <i class="fas fa-eye me-2"></i>
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Empty State (if no data) -->
                @if(count($months) === 0)
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Monthly Reports Available</h5>
                    <p class="text-muted">Reports will appear here as they become available.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection










