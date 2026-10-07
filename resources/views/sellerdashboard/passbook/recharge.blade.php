@extends('layouts.sellerdash')

@section('content')
    <style>
        /* Modern COD Dashboard Design */
        .cod-page {
            ba        /* Navigation Tabs */
        .nav-tabs-modern {
            background: white;
            padding: 0.8rem;
            margin-left:11px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.04);
            margin-bottom: 1rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            border: 1px solid #e5e7eb;
        }near-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Page Header */
        .compact-hero-section {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%) !important;
            color: white !important;
            padding: 0.5rem 0.8rem !important;
            margin-bottom: 0.5rem !important;
            border-radius: 8px !important;
            text-align: center !important;
            position: relative !important;
            overflow: hidden !important;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.15) !important;
        }

        .page-hero::before {
        }

        .hero-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
            font-weight: 400;
        }  .cod-page {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Page Header */
        .page-hero {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            color: white;
            padding: 1rem 0.8rem;
            margin-bottom: 0.8rem;
            border-radius: 10px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
        }
            margin-bottom: 1.5rem;
            border-radius: 16px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.2);
        }

        .compact-hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: heroFloat 25s infinite linear;
        }

        @keyframes heroFloat {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .compact-hero-title {
            font-size: 1.5rem !important;
            font-weight: 800 !important;
            margin-bottom: 0.2rem !important;
            position: relative !important;
            z-index: 2 !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3) !important;
        }

        .compact-hero-subtitle {
            font-size: 0.8rem !important;
            opacity: 0.9 !important;
            position: relative !important;
            z-index: 2 !important;
            font-weight: 400 !important;
        }  <style>
        /* Modern COD Dashboard Design */
        .cod-page {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Page Header */
        .page-hero {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            color: white;
            padding: 2rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 16px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.2);
        }

        .page-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: heroFloat 25s infinite linear;
        }

        @keyframes heroFloat {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
            font-weight: 400;
        }

        /* Navigation Tabs */
        .nav-tabs-modern {
            background: white;
            padding: 1rem;
            margin-left:11px;
            border-radius: 12px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.06);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
            border: 1px solid #e5e7eb;
        }

        .nav-tab-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.2rem 0.4rem;
            border-radius: 6px;
            text-decoration: none;
            color: #6b7280;
            /* font-weight: 600; */
            font-size: 0.8rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
        }

        .nav-tab-item:hover {
            background: #f9fafb;
            color: #374151;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .nav-tab-item.active {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-color: #6366f1;
            box-shadow: 0 12px 28px rgba(99, 102, 241, 0.35);
        }

        .nav-tab-item i {
            font-size: 1.2rem;
        }

        /* Statistics Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.8rem;
            margin-bottom: 1.2rem;
        }

        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
        }

        .stat-card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }

        .stat-content h5 {
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 0.5rem;
        }

        .balance-value {
            font-size: 1.6rem;
            font-weight: 900;
            color: #1f2937;
            line-height: 1;
        }

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.06);
            overflow: hidden;
            border: 1px solid #e5e7eb;
            margin-left:12px;
        }

        .table-header {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .table-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.2rem;
        }

        .table-subtitle {
            color: #6b7280;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .date-filter-wrapper {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.2rem 0.5rem;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .date-filter-wrapper:focus-within {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            transform: scale(1.02);
        }

        .date-filter-wrapper i {
            color: #6b7280;
            font-size: 1.1rem;
        }

        .date-input {
            border: none !important;
            outline: none !important;
            background: transparent !important;
            font-size: 0.9rem;
            color: #1f2937;
            font-weight: 500;
            width: 100%;
            box-shadow: none !important;
        }

        /* Enhanced Table */
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .modern-table thead th {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            padding: 1rem 1rem;
            text-align: left;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border: none;
            white-space: nowrap;
        }

        .modern-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
        }

        .modern-table tbody tr:hover {
            background: linear-gradient(135deg, #fafafb 0%, #f4f5f7 100%);
            transform: scale(1.001);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .modern-table tbody td {
            padding: 1rem 1rem;
            color: #374151;
            font-weight: 500;
            border: none;
            vertical-align: middle;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid;
        }

        .status-delivered {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border-color: #22c55e;
        }

        .status-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-color: #f59e0b;
        }

        .status-shipped {
            background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%);
            color: #1e40af;
            border-color: #3b82f6;
        }

        /* Order Number Styling */
        .order-number {
            
            color: #6366f1;
            font-size: 0.9rem;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Amount Styling */
        .amount-value {
            /* font-weight: 800; */
            color: #059669;
            font-size: 0.9rem;
        }

        /* Courier Info */
        .courier-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-weight: 600;
        }

        /* AWB Code */
        .awb-code {
            background: #f1f5f9;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        /* Date Info */
        .date-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.85rem;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.4;
            color: #d1d5db;
        }

        .empty-state h4 {
            color: #6b7280;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .empty-state p {
            color: #9ca3af;
            font-size: 0.9rem;
        }

        /* Pagination */
        .pagination-wrapper {
            background: #f9fafb;
            padding: 2rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: center;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-tabs-modern {
                flex-direction: column;
            }
            
            .hero-title {
                font-size: 2.2rem;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 2rem;
            }
            
            .stat-icon {
                margin-bottom: 1rem;
            }
            
            .table-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            
            .date-filter-wrapper {
                min-width: auto;
            }
        }

        /* Loading Animation */
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

        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>

    <div class="cod-page" >
        <div class="pc-container" style="background:#646dff26;">
            <div class="pc-content">
                <!-- Hero Section -->
                <!-- <div class="compact-hero-section animate-fade-in">
                    <h1 class="compact-hero-title">COD Remittance Center</h1>
                    <p class="compact-hero-subtitle">Track and manage your Cash on Delivery payments effortlessly</p>
                </div> -->

                <!-- Modern Navigation -->
                <div class="nav-tabs-modern animate-fade-in">
                    <a href="{{ route('seller.passbook') }}" class="nav-tab-item text-decoration-none">
                        <i class="ti ti-dashboard"></i>
                        <span>Passbook</span>
                    </a>
                    <a href="{{ route('seller.cod') }}" class="nav-tab-item  text-decoration-none">
                        <i class="ti ti-shopping-cart"></i>
                        <span>COD Remittance</span>
                    </a>
                    <a href="{{ route('seller.shippingcharge') }}" class="nav-tab-item text-decoration-none">
                        <i class="ti ti-truck"></i>
                        <span>Shipping Charges</span>
                    </a>
                    <a href="{{ route('seller.recharge') }}" class="nav-tab-item  text-decoration-none active">
                        <i class="fa-regular fa-wallet"></i> All Recharges
                    </a>
                    <a href="{{ route('seller.invoice.add') }}" class="nav-tab-item  text-decoration-none">
                        <i class="fa-regular fa-money-check-dollar"></i> Invoices
                    </a>
                </div>

               

                <!-- Enhanced Table Section -->
                <div class="table-container animate-fade-in" style="animation-delay: 0.4s;">
                    <div class="table-header">
                        
                        <div class="date-filter-wrapper">
                            <i class="ti ti-calendar"></i>
                            <input type="text" id="daterange" class="date-input" placeholder="Select Date Range">
                        </div>
                        <div>
                            <button class="enhanced-control-btn refresh-btn" title="Refresh" onclick="location.reload()">
                                <i class="ti ti-refresh"></i>
                            </button>
                            <a href="{{ route('seller.orders.download-template') }}"
                                class="btn btn-sm align-items-center px-3 py-2 fs-6"
                                data-bs-toggle="tooltip"
                                title="Download CSV">
                                <i class="fas fa-download fs-5"></i>
                            </a>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <style>
                            /* Enhanced Table Header Styling */
                            .modern-table thead tr {
                                background: linear-gradient(90deg, #3576e3 0, #306cc9 100%) !important;
                            }
                            .modern-table thead th {
                                color: #fff !important;
                                font-weight: 700;
                                border: none;
                                padding: 1rem 0.8rem;
                                font-size: 0.83rem !important;
                                letter-spacing: 0.02em;
                                transition: background 0.3s;
                                background: transparent;
                                box-shadow: none !important;
                                vertical-align: middle;
                            }
                            .modern-table thead th i {
                                color: #e0e7ef;
                                transition: color 0.3s;
                                font-size: 1.01rem;
                                vertical-align: middle;
                            }
                            /* Table row and hover effects for better UI/UX */
                            .modern-table tbody tr {
                                transition: background 0.23s cubic-bezier(.4,0,.2,1);
                            }
                            .modern-table tbody tr:hover {
                                background: #e1eaff;
                            }
                        </style>
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th><i class="ti ti-hash" style="margin-right: 0.5rem;"></i>Order ID</th>
                                    <th><i class="ti ti-truck" style="margin-right: 0.5rem;"></i>Status</th>
                                    <th><i class="ti ti-currency-rupee" style="margin-right: 0.5rem;"></i>Amount</th>
                                    <th><i class="ti ti-package" style="margin-right: 0.5rem;"></i>Courier</th>
                                    <th><i class="ti ti-barcode" style="margin-right: 0.5rem;"></i>AWB Number</th>
                                    <th><i class="ti ti-credit-card" style="margin-right: 0.5rem;"></i>Payment Status</th>
                                    <th><i class="ti ti-calendar" style="margin-right: 0.5rem;"></i>Created Date</th>
                                    <th><i class="ti ti-calendar-event" style="margin-right: 0.5rem;"></i>Remittance Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($codOrders as $order)
                                    <tr>
                                        <td>
                                            <span class="order-number">#{{ $order->order_number }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $statusClass = 'status-pending';
                                                switch(strtolower($order->shipping_status)) {
                                                    case 'delivered':
                                                        $statusClass = 'status-delivered';
                                                        break;
                                                    case 'shipped':
                                                        $statusClass = 'status-shipped';
                                                        break;
                                                }
                                            @endphp
                                            <span class="status-badge {{ $statusClass }}">
                                                {{ ucfirst($order->shipping_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="amount-value">₹{{ number_format($order->collectable_amount, 2) }}</span>
                                        </td>
                                        <td>
                                            <div class="courier-badge">
                                                <i class="ti ti-truck-delivery"></i>
                                                <span>{{ $order->all_courier_name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="awb-code">{{ $order->awb_number }}</span>
                                        </td>
                                        <td>
                                            @if($order->payment_status)
                                                <span class="status-badge status-delivered">{{ $order->payment_status }}</span>
                                            @else
                                                <span class="status-badge status-pending">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="date-display">
                                                <i class="ti ti-clock"></i>
                                                <span>{{ $order->created_at ? $order->created_at->format('M d, Y') : 'N/A' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-display">
                                                <i class="ti ti-calendar-plus"></i>
                                                <span>
                                                    @if($order->delivered_date)
                                                        {{ \Carbon\Carbon::parse($order->delivered_date)->addDays(7)->format('M d, Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="empty-state">
                                            <i class="ti ti-shopping-cart-off"></i>
                                            <h4>No COD Orders Found</h4>
                                            <p>There are no cash on delivery orders to display at the moment.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    @if(isset($codOrders) && $codOrders->hasPages())
                        <div class="pagination-wrapper">
                            {{ $codOrders->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced stat card animations
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                // Initial state
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                // Animate in
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
                
                // Hover effects
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-12px) scale(1.03)';
                    this.style.boxShadow = '0 30px 70px rgba(0,0,0,0.2)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '0 8px 32px rgba(0,0,0,0.06)';
                });
            });

            // Enhanced date input interactions
            const dateInput = document.getElementById('daterange');
            if (dateInput) {
                const wrapper = dateInput.closest('.date-filter-wrapper');
                
                dateInput.addEventListener('focus', function() {
                    wrapper.style.borderColor = '#6366f1';
                    wrapper.style.boxShadow = '0 0 0 4px rgba(99, 102, 241, 0.1)';
                    wrapper.style.transform = 'scale(1.02)';
                });
                
                dateInput.addEventListener('blur', function() {
                    wrapper.style.borderColor = '#e5e7eb';
                    wrapper.style.boxShadow = 'none';
                    wrapper.style.transform = 'scale(1)';
                });
            }

            // Table row animations
            const tableRows = document.querySelectorAll('.modern-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    row.style.transition = 'all 0.4s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, 500 + (index * 50));
                
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.002) translateX(5px)';
                    this.style.boxShadow = '0 4px 16px rgba(0,0,0,0.12)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1) translateX(0)';
                    this.style.boxShadow = 'none';
                });
            });

            // Add smooth scroll behavior
            document.documentElement.style.scrollBehavior = 'smooth';
        });
    </script>
@endsection
