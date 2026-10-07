@extends('layouts.sellerdash')

@section('content')
<style>
    .passbook-page {
        --pb-primary-gradient: linear-gradient(135deg, #6c84ed 0%, #4b5bcf 100%);
        --pb-success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --pb-danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --pb-info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --pb-dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }

    .passbook-page .pb-dashboard-header {
        background: #646dff26;;
        color: black;
        padding: 0.8rem 0;
        margin-bottom: 1rem;
        margin-left: 0.8rem;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.3);
    }

    .passbook-page .pb-dashboard-title {
        font-size: 1.3rem;
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        margin-bottom: 0.2rem;
    }

    .passbook-page .pb-dashboard-subtitle {
        font-size: 0.8rem;
        opacity: 0.9;
    }

    .passbook-page .pb-top-nav-tabs {
        background: white;
        border-radius: 10px;
        padding: 0.8rem;
        /* box-shadow: 0 5px 20px rgba(0,0,0,0.1); */
        margin-bottom: 1.5rem;
        margin-left: 10px;
    }

    .passbook-page .pb-nav-tab {
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

    .passbook-page .pb-nav-tab::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: var(--pb-primary-gradient);
        transition: left 0.3s ease;
        z-index: -1;
    }

    .passbook-page .pb-nav-tab.active,
    .passbook-page .pb-nav-tab:hover {
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.4);
    }

    .passbook-page .pb-nav-tab.active::before,
    .passbook-page .pb-nav-tab:hover::before {
        left: 0;
    }

    .passbook-page .pb-nav-tab i {
        margin-right: 0.4rem;
        font-size: 1rem;
    }

    .passbook-page .pb-balance-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.8rem;
        margin-bottom: 1.5rem;
    }

    .passbook-page .pb-balance-card {
        background: white;
        border-radius: 12px;
        padding: 0.8rem;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        min-height: 100px;
    }

    .passbook-page .pb-balance-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--pb-primary-gradient);
    }

    .passbook-page .pb-balance-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 35px rgba(0,0,0,0.15);
    }

    .passbook-page .pb-balance-card.pb-wallet::before {
        background: var(--pb-success-gradient);
    }

    .passbook-page .pb-balance-card.pb-credit::before {
        background: var(--pb-info-gradient);
    }

    .passbook-page .pb-balance-card.pb-debit::before {
        background: var(--pb-danger-gradient);
    }

    .passbook-page .pb-balance-icon {
        width: 35px;
        height: 35px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: white;
        margin-bottom: 0.6rem;
    }

    .passbook-page .pb-balance-card.pb-wallet .pb-balance-icon {
        background: var(--pb-success-gradient);
    }

    .passbook-page .pb-balance-card.pb-credit .pb-balance-icon {
        background: var(--pb-info-gradient);
    }

    .passbook-page .pb-balance-card.pb-debit .pb-balance-icon {
        background: var(--pb-danger-gradient);
    }

    .passbook-page .pb-balance-title {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 0.2rem;
        font-weight: 500;
    }

    .passbook-page .pb-balance-amount {
        font-size: 1.2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }

    .passbook-page .pb-transactions-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .passbook-page .pb-transactions-header {
        background: white;
        color: white;
        padding: 1.2rem;
        text-align: center;
    }

    .passbook-page .pb-transactions-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
    }

    .passbook-page .pb-transactions-content {
        /* padding: 1.2rem; */
    }

    .passbook-page .pb-custom-table {
        border: none;
        /* border-radius: 8px; */
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }

    .passbook-page .pb-custom-table thead th {
        background:#b8d4f1 !important;
        border: none;
        font-weight: 600;
        color: #495057;
        padding: 0.8rem 0.7rem;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .passbook-page .pb-custom-table tbody td {
        border: none;
        padding: 0.8rem 0.7rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
        font-size: 0.82rem;
        text-align: center;
    }

    .passbook-page .pb-custom-table tbody tr {
        transition: all 0.3s ease;
    }

    .passbook-page .pb-custom-table tbody tr:hover {
        background-color: #f8f9ff;
        transform: scale(1.005);
    }

    .passbook-page .pb-transaction-date {
        font-weight: 500;
        color: #495057;
    }

    .passbook-page .pb-transaction-badge {
        padding: 0.3rem 0.8rem;
        border-radius: 15px;
        font-weight: 500;
        font-size: 0.75rem;
        border: none;
    }

    .passbook-page .pb-badge-credit {
        background: linear-gradient(135deg, #21d180 0%, #2db91c 100%);
        color: white;
    }

    .passbook-page .pb-badge-debit {
        background: linear-gradient(135deg, #f79b92 0%, #f71600 100%);
        color: white;
    }

    .passbook-page .pb-transaction-amount {
        /* font-weight: 600; */
        font-size: 0.9rem;
        color: #2c3e50;
    }

    .passbook-page .pb-empty-state {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }

    .passbook-page .pb-empty-state i {
        font-size: 3rem;
        margin-bottom: 0.8rem;
        opacity: 0.5;
    }

    .passbook-page .pb-fade-in {
        animation: pbFadeIn 0.6s ease-in;
        margin-left: 10px;
    }

    @keyframes pbFadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .passbook-page .pb-slide-in {
        animation: pbSlideIn 0.8s ease-out;
    }

    @keyframes pbSlideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .passbook-page .pagination {
        justify-content: center;
        /* margin-top: 1.5rem; */
    }

    .passbook-page .pagination .page-link {
        border-radius: 8px;
        margin: 0 0.1rem;
        border: none;
        color: #667eea;
        font-weight: 500;
        font-size: 0.85rem;
    }

    .passbook-page .pagination .page-item.active .page-link {
        background: var(--pb-primary-gradient);
        border: none;
    }

    @media (max-width: 768px) {
        .passbook-page .pb-dashboard-title {
            font-size: 1.1rem;
        }
        
        .passbook-page .pb-dashboard-subtitle {
            font-size: 0.75rem;
        }
        
        .passbook-page .pb-balance-cards {
            grid-template-columns: 1fr;
            gap: 0.8rem;
        }
        
        .passbook-page .pb-nav-tab {
            padding: 0.6rem 0.8rem;
            font-size: 0.8rem;
        }

        .passbook-page .pb-balance-card {
            min-height: 85px;
        }
    }
</style>

<div class="pc-container passbook-page" style="background:#646dff26;">
    <div class="pc-content">
        

        <!-- Navigation Tabs -->
        <div class="pb-top-nav-tabs pb-slide-in">
            <div class="">
                <a href="{{ route('seller.passbook') }}" class="pb-nav-tab active">
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
                <a href="{{ route('seller.invoice.add') }}" class="pb-nav-tab">
                    <i class="fa-regular fa-money-check-dollar"></i> Invoices
                </a>

                
            </div>
        </div>

        <!-- Enhanced Balance Overview Cards: Premium & Professional, Info on Right -->
        <div class="pb-balance-cards pb-fade-in"
            style="display: flex; flex-wrap: wrap; gap: 1rem; ">
            
            <!-- Wallet Balance -->
            <div class="pb-balance-card pb-wallet d-flex align-items-center justify-content-center"
                style="background: linear-gradient(97deg, #f7faff 60%, #edf1fa 100%); color: #313558;box-shadow: 0 4px 18px -9px #a5b5d436; border: 1px solid #e3eaf4; padding: 1rem .95rem; flex: 1 1 0; max-width: 100%; transition: box-shadow .23s, border .19s;">
                <div class="pb-balance-icon flex-shrink-0 d-flex align-items-center justify-content-center me-2"
                    style="background: #e6edfb; border-radius: 50%; width: 30px; height: 30px; min-width:30px; display:flex; align-items:center; justify-content:center;">
                    <i class="ti ti-wallet" style="font-size: 1.12rem; color: #6366f1;"></i>
                </div>
                <div class="pb-balance-details flex-grow-1 text-end" style="min-width:0;">
                    <span class="pb-balance-title mb-1 d-block"
                        style="font-size: .78rem; font-weight: 600; opacity:0.78; letter-spacing:.01em;">
                        Current Wallet Balance
                    </span>
                    <span class="pb-balance-amount mb-0 d-block"
                        style="font-size: .98rem; font-weight: 700; letter-spacing: .04em;">
                        ₹{{ number_format($totalAmount, 2) }}
                    </span>
                </div>
            </div>
            
            <!-- Total Credit -->
            <div class="pb-balance-card pb-credit d-flex align-items-center justify-content-center"
                style="background: linear-gradient(97deg,#f5fdf7 70%,#eafae8 100%); color: #206054; border-radius: 13px; min-height: 80px; box-shadow: 0 4px 18px -9px #ddeee954; border: 1px solid #ddeee9; padding: .95rem .85rem; flex: 1 1 0; max-width: 100%; transition: box-shadow .23s, border .19s;">
                <div class="pb-balance-icon flex-shrink-0 d-flex align-items-center justify-content-center me-2"
                    style="background: #d8f7e2; border-radius: 50%; width: 30px; height: 30px; min-width:30px; display:flex; align-items:center; justify-content:center;">
                    <i class="ti ti-arrow-up-right" style="font-size: 1.12rem; color: #10b981;"></i>
                </div>
                <div class="pb-balance-details flex-grow-1 text-end" style="min-width:0;">
                    <span class="pb-balance-title mb-1 d-block"
                        style="font-size: .78rem; font-weight: 600; opacity:0.78; letter-spacing:.01em;">
                        Total Credits
                    </span>
                    <span class="pb-balance-amount mb-0 d-block"
                        style="font-size: .98rem; font-weight: 700; letter-spacing: .04em;">
                        ₹{{ number_format($totalCredit, 2) }}
                    </span>
                </div>
            </div>
            
            <!-- Total Debit -->
            <div class="pb-balance-card pb-debit d-flex align-items-center justify-content-center"
                style="background: linear-gradient(97deg,#fff7fb 68%,#faeef8 100%); color: #6d375a; border-radius: 13px; min-height: 80px; box-shadow: 0 4px 18px -9px #eacdea38; border: 1px solid #eddcea; padding: .95rem .85rem; flex: 1 1 0; max-width: 100%; transition: box-shadow .23s, border .19s;">
                <div class="pb-balance-icon flex-shrink-0 d-flex align-items-center justify-content-center me-2"
                    style="background: #f7e2ed; border-radius: 50%; width: 30px; height: 30px; min-width:30px; display:flex; align-items:center; justify-content:center;">
                    <i class="ti ti-arrow-down-right" style="font-size: 1.12rem; color: #ca4a96;"></i>
                </div>
                <div class="pb-balance-details flex-grow-1 text-end" style="min-width:0;">
                    <span class="pb-balance-title mb-1 d-block"
                        style="font-size: .78rem; font-weight: 600; opacity:0.78; letter-spacing:.01em;">
                        Total Debits
                    </span>
                    <span class="pb-balance-amount mb-0 d-block"
                        style="font-size: .98rem; font-weight: 700; letter-spacing: .04em;">
                        ₹{{ number_format($totalDebit, 2) }}
                    </span>
                </div>
            </div>
        </div>
        <style>
            .pb-balance-cards {
                display: flex;
                align-items: stretch;
                flex-wrap: wrap;
                gap: 1rem;
            }
            .pb-balance-card {
                flex: 1 1 0;
                min-width: 0;
                min-height: 80px;
                max-width: 100%;
                border-radius: 13px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #fff;
                margin: 0;
                padding: .95rem .85rem;
                box-shadow: 0 4px 18px -9px rgba(99, 102, 241, 0.08);
                font-size: .98rem;
                transition: box-shadow 0.18s, border 0.16s, transform .15s;
            }
            .pb-balance-card:hover {
                box-shadow: 0 8px 28px -7px #6366f133;
                border-color: #adb4d055;
                transform: translateY(-2px) scale(1.016);
                z-index: 2;
            }
            .pb-balance-title {
                font-size: .78rem;
                font-weight: 600;
                opacity:0.78;
                letter-spacing:.01em;
                margin-bottom: .18rem;
            }
            .pb-balance-amount {
                font-size: .98rem;
                font-weight: 700;
                letter-spacing: .04em;
            }
            .pb-balance-icon {
                width: 30px;
                height: 30px;
                min-width: 30px;
                background: #eef0f3;
                border-radius: 50%;
                font-size: 1.1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 0.65rem;
            }

            @media (max-width: 991px) {
                .pb-balance-cards {
                    gap: 0.7rem !important;
                }
                .pb-balance-card {
                    min-height: 64px !important;
                    padding: .7rem .45rem !important;
                    font-size: .93rem !important;
                }
                .pb-balance-title {
                    font-size: .72rem !important;
                }
                .pb-balance-amount {
                    font-size: .91rem !important;
                }
                .pb-balance-icon {
                    width: 25px !important;
                    height: 25px !important;
                    font-size: 0.92rem !important;
                }
            }
            @media (max-width: 769px) {
                .pb-balance-cards {
                    flex-direction: row !important;
                    flex-wrap: nowrap !important;
                    gap: 0.45rem !important;
                }
                .pb-balance-card {
                    flex: 1 1 0 !important;
                    max-width: 100% !important;
                    min-width: 0 !important;
                    min-height: 56px !important;
                    border-radius: 8px !important;
                    padding: .66rem .66rem !important;
                    font-size: .82rem !important;
                }
                .pb-balance-title,
                .pb-balance-amount {
                    font-size: .61rem !important;
                }
                .pb-balance-icon {
                    width: 20px !important;
                    height: 20px !important;
                    min-width:20px !important;
                    border-radius: 50% !important;
                    font-size: 0.77rem !important;
                    margin-right: 0.38rem !important;
                    background: inherit !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    background: var(--pb-icon-bg, #eef0f3) !important;
                }
                .pb-balance-card.pb-wallet .pb-balance-icon { --pb-icon-bg: #e6edfb; }
                .pb-balance-card.pb-credit .pb-balance-icon { --pb-icon-bg: #d8f7e2; }
                .pb-balance-card.pb-debit .pb-balance-icon { --pb-icon-bg: #f7e2ed; }
            }
            @media (max-width: 500px) {
                .pb-balance-cards {
                    flex-direction: row !important;
                    flex-wrap: nowrap !important;
                    gap: 0.30rem !important;
                }
                .pb-balance-card {
                    padding: .37rem .37rem !important;
                    min-height: 46px !important;
                    border-radius: 8px !important;
                    font-size: .70rem !important;
                }
                .pb-balance-title,
                .pb-balance-amount {
                    font-size: .47rem !important;
                }
                .pb-balance-icon {
                    width: 14px !important;
                    height: 14px !important;
                    min-width:14px !important;
                    font-size: 0.56rem !important;
                    margin-right: 0.18rem !important;
                    background: inherit !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                }
            }
        </style>

        <!-- Transactions Table -->
        <div class="pb-transactions-container pb-fade-in">
            <div class="pb-transactions-header">
                <!-- For desktop: Keep all in one row, left and right aligned -->
                <div class="d-none d-md-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <!-- Date Filter -->
                        <div class="filter-block bg-white rounded-3 shadow-sm border px-3 py-2 d-flex align-items-center">
                            <form method="GET" action="{{ route('seller.order') }}" class="filter-form-premium d-flex flex-wrap align-items-center gap-2 m-0">
                                <div class="date-group-premium d-flex align-items-center gap-2 flex-wrap flex-sm-nowrap mb-0">
                                    <label for="start_date" class="form-label mb-0 fw-semibold text-secondary" style="font-size:.75rem;">
                                        From
                                    </label>
                                    <input 
                                        type="date" 
                                        id="start_date"
                                        name="start_date" 
                                        class="form-control form-control-sm custom-date-input-premium"
                                        value="{{ request('start_date') }}" 
                                        placeholder="dd-mm-yyyy" style="padding:0.3rem 0.55rem;font-size:10px;border-radius:5px;"
                                    >
                                    <span class="mx-1 text-muted" style="font-weight:500; font-size:.75rem;">to</span>
                                    <label for="end_date" class="form-label mb-0 fw-semibold text-secondary visually-hidden">To</label>
                                    <input 
                                        type="date" 
                                        id="end_date"
                                        name="end_date" 
                                        class="form-control form-control-sm custom-date-input-premium"
                                        value="{{ request('end_date') }}" 
                                        placeholder="dd-mm-yyyy" style="padding:0.3rem 0.55rem;font-size:10px;border-radius:5px;"
                                    >
                                </div>
                                @if(request('start_date') || request('end_date'))
                                <a href="{{ route('seller.orders.export-excel') }}?start_date={{ request('start_date') }}&end_date={{ request('end_date') }}"
                                    class="btn btn-gradient-success fw-semibold rounded-pill px-3 ms-1 shadow-sm"
                                    style="font-size:.85rem;">
                                    <i class="fas fa-file-excel me-1"></i>Export Filtered
                                </a>
                                @endif
                            </form>
                        </div>
                        <!-- Search Order ID -->
                        <div class="filter-block d-flex align-items-center">
                            <form class="w-100 d-flex align-items-center gap-2 m-0">
                                <div class="search-ref-id-group w-100 position-relative d-flex align-items-center shadow-sm gap-1">
                                    <input 
                                        type="text" 
                                        id="ref_id"
                                        name="ref_id"
                                        class="form-control form-control-sm pl-4"
                                        value="{{ request('ref_id') }}"
                                        placeholder="Search By Order ID"
                                        style="font-size:0.82rem;padding-left:2.1rem;">
                                </div>
                            </form>
                        </div>
                        <!-- AWB Search -->
                        <div class="filter-block d-flex align-items-center" style="border-radius: 0.44rem;">
                            <input 
                                type="text" 
                                id="awb_id"
                                name="awb_id"
                                class="form-control form-control-sm pl-4"
                                value="{{ request('awb_id') }}"
                                placeholder="Search by awb"
                                style="font-size:0.82rem;padding-left:2.1rem;">
                        </div>
                    </div>
                    <!-- Right side: Dropdown and Refresh -->
                    <div class="d-flex align-items-center gap-2">
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" id="bulkActionDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:15px;">
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
                        <button class="btn btn-light" onclick="location.reload()" style="font-size:15px;">
                            <i class="fas fa-sync-alt" style="font-size: 15px;"></i>
                        </button>
                    </div>
                </div>

                <!-- For mobile: Date in one row, OrderID in one row, then (AWB+dropdown+refresh) in separate row -->
                <div class="d-block d-md-none">
                    <!-- Date Filter Row - Single Row -->
                    <div class="mb-2">
                        <div class="filter-block bg-white rounded-3 shadow-sm border px-3 py-2 d-flex align-items-center w-100 mb-1" style="overflow-x:auto;">
                            <form method="GET" action="{{ route('seller.order') }}" class="filter-form-premium d-flex flex-nowrap align-items-center gap-2 m-0 w-100">
                                <div class="date-group-premium d-flex align-items-center gap-2 flex-nowrap mb-0 w-100">
                                    <label for="start_date" class="form-label mb-0 fw-semibold text-secondary" style="font-size:.75rem;">
                                        From
                                    </label>
                                    <input 
                                        type="date" 
                                        id="start_date"
                                        name="start_date" 
                                        class="form-control form-control-sm custom-date-input-premium"
                                        value="{{ request('start_date') }}" 
                                        placeholder="dd-mm-yyyy" style="padding:0.3rem 0.55rem;font-size:8px;border-radius:5px;"
                                    >
                                    <span class="mx-1 text-muted" style="font-weight:500; font-size:.75rem;">to</span>
                                    <label for="end_date" class="form-label mb-0 fw-semibold text-secondary visually-hidden">To</label>
                                    <input 
                                        type="date" 
                                        id="end_date"
                                        name="end_date" 
                                        class="form-control form-control-sm custom-date-input-premium"
                                        value="{{ request('end_date') }}" 
                                        placeholder="dd-mm-yyyy" style="padding:0.3rem 0.55rem;font-size:8px;border-radius:5px;"
                                    >
                                    @if(request('start_date') || request('end_date'))
                                    <a href="{{ route('seller.orders.export-excel') }}?start_date={{ request('start_date') }}&end_date={{ request('end_date') }}"
                                        class="btn btn-gradient-success fw-semibold rounded-pill px-3 ms-1 shadow-sm"
                                        style="font-size:.80rem;">
                                        <i class="fas fa-file-excel me-1"></i>Export
                                    </a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Order ID Search Row -->
                    <div class="mb-2">
                        <div class="filter-block d-flex align-items-center w-100">
                            <form class="w-100 d-flex align-items-center gap-2 m-0">
                                <div class="search-ref-id-group w-100 position-relative d-flex align-items-center gap-1 shadow-sm">
                                    <input 
                                        type="text" 
                                        id="ref_id"
                                        name="ref_id"
                                        class="form-control form-control-sm pl-4"
                                        value="{{ request('ref_id') }}"
                                        placeholder="Search By Order ID"
                                        style="font-size:0.82rem;padding-left:2.1rem;">
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- AWB & Actions Row -->
                    <div class="d-flex gap-1 mb-2 align-items-stretch">
                        <div class="filter-block d-flex align-items-center flex-grow-1" style="border-radius: 0.44rem;">
                            <input 
                                type="text" 
                                id="awb_id"
                                name="awb_id"
                                class="form-control form-control-sm pl-4"
                                value="{{ request('awb_id') }}"
                                placeholder="Search by awb"
                                style="font-size:0.82rem;padding-left:2.1rem;">
                        </div>
                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                            <div class="dropdown">
                                <button class="btn btn-light dropdown-toggle" type="button" id="bulkActionDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false" style="height:36px;font-size:15px;">
                                    <i class="fas fa-cog"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#warehouseModal">
                                            <i class="fas fa-warehouse me-2"></i>Update Warehouse
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" id="bulkShipNowBtnMobile">
                                            <i class="fas fa-truck me-2"></i>Ship Now (Bulk)
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <button class="btn btn-light" onclick="location.reload()" style="height:36px;font-size:15px;">
                                <i class="fas fa-sync-alt" style="font-size: 15px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <style>
                    .filter-block {
                        border-radius: 1.05rem;
                        height: 38px;
                        transition: box-shadow 0.18s, background 0.2s;
                    }
                    .search-awb-id-group input.form-control {
                        background: white;
                        border-radius: 0.44rem;
                        border: none;
                        font-size: .92rem;
                        color: #58688e;
                        box-shadow: none;
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
                        .filter-block {
                            min-width: 0 !important;
                            width: 100% !important;
                            margin-bottom: 0.3rem;
                        }
                        .filter-form-premium,
                        .date-group-premium {
                            flex-wrap: nowrap !important;
                            gap: 0.4rem !important;
                        }
                        .filter-form-premium input[type="date"] {
                            font-size: 10px !important;
                            min-width: 90px !important;
                            max-width: 110px !important;
                        }
                        .btn-gradient-success {
                            font-size: .76rem !important;
                            padding-left: 0.95rem !important;
                            padding-right: 0.95rem !important;
                            min-width: fit-content;
                            white-space: nowrap !important;
                        }
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
            </div>
            <div class="pb-transactions-content">
                <div class="table-responsive">
                    <table class="table pb-custom-table">
                        <thead>
                            <tr>
                                
                                <th><i class="ti ti-tag me-1"></i>Transaction Type</th>
                                <th><i class="ti ti-currency-rupee me-1"></i>Amount</th>
                                 <th><i class="ti ti-currency-rupee me-1"></i>Description</th>

                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $txn)
                                <tr class="pb-slide-in">
                                   
                                    <td>
                                        <span class="pb-transaction-badge {{ $txn->type == 'Credit' ? 'pb-badge-credit' : 'pb-badge-debit' }}">
                                            <i class="ti {{ $txn->type == 'Credit' ? 'ti-arrow-up' : 'ti-arrow-down' }} me-1"></i>
                                            {{ ucfirst($txn->type) }}
                                        </span>
                                    </td>
                                    <td class="pb-transaction-amount">₹{{ number_format($txn->amount, 2) }}</td>
                                    <td class="pb-transaction-amount">{{ $txn->description ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="pb-empty-state">
                                        <i class="ti ti-database"></i>
                                        <h5>No Transactions Found</h5>
                                        <p>Your transaction history will appear here once you start making transactions.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
