
@extends('layouts.sellerdash')

@section('content')

<style>
    .passbook-page {
        --pb-primary-gradient:linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        --pb-success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --pb-danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --pb-info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --pb-dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }
    .passbook-page .pb-dashboard-header {
        background: var(--pb-primary-gradient);
        color: white;
        padding: 0.8rem 0;
        margin-bottom: 1rem;
        border-radius: 0 0 15px 15px;
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
        border-radius: 7px;
        padding: 0.6rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
        margin-left:12px;
    }
    .passbook-page .pb-nav-tab {
        display: inline-flex;
        align-items: center;
        padding: 0.7rem 0.6rem;
        margin: 0 0.2rem;
        border-radius: 7px;
        text-decoration: none;
        color: #6c757d;
        /* font-weight: 500; */
        font-size: 0.82rem;
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
        /* background: var(--pb-dark-gradient); */
        color: white;
        padding: 1rem;
        text-align: center;
    }
    .passbook-page .pb-transactions-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
    }
    /* .passbook-page .pb-transactions-content {
        padding: 1.2rem;
    } */
    .passbook-page .pb-custom-table {
        border: none;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }
    .passbook-page .pb-custom-table thead th {
        background: #f8f9fa;
        border: none;
        font-weight: 600;
        color: #495057;
        padding: 0.8rem 0.7rem;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .passbook-page .pb-custom-table tbody td {
        border: none;
        padding: 0.8rem 0.7rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
        font-size: 0.85rem;
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
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }
    .passbook-page .pb-badge-debit {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
    }
    .passbook-page .pb-transaction-amount {
        font-weight: 600;
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
        <!-- Dashboard Header -->
        <!-- <div class="pb-dashboard-header text-center pb-fade-in">
            <div class="container">
                <h1 class="pb-dashboard-title">Shipping Charges Center</h1>
                <p class="pb-dashboard-subtitle">Track and manage your shipping charges effortlessly</p>
            </div>
        </div> -->
        <!-- Navigation Tabs -->
        <div class="pb-top-nav-tabs pb-slide-in">
            <div class="">
                <a href="{{ route('seller.passbook') }}" class="pb-nav-tab">
                    <i class="ti ti-dashboard"></i> Passbook
                </a>
                <a href="{{ route('seller.cod') }}" class="pb-nav-tab">
                    <i class="ti ti-shopping-cart"></i> COD Remittance
                </a>
                <a href="{{ route('seller.shippingcharge') }}" class="pb-nav-tab active">
                    <i class="ti ti-alert-circle"></i> Shipping Charges
                </a>
                <a href="{{ route('seller.recharge') }}" class="pb-nav-tab">
                    <i class="fa-regular fa-wallet"></i> All Recharges
                </a>
                <a href="{{ route('seller.invoice.add') }}" class="pb-nav-tab">
                    <i class="fa-regular fa-money-check-dollar"></i> Invoices
                </a>
            </div>
        </div>

      
        <!--
        <div class="pb-balance-cards pb-fade-in">
            <div class="pb-balance-card pb-wallet">
                <div class="pb-balance-icon">
                    <i class="ti ti-wallet"></i>
                </div>
                <h6 class="pb-balance-title">Current Wallet Balance</h6>
                <h2 class="pb-balance-amount">₹{{ number_format($walletBalance ?? 0, 2) }}</h2>
            </div>
            <div class="pb-balance-card pb-credit">
                <div class="pb-balance-icon">
                    <i class="ti ti-arrow-up-right"></i>
                </div>
                <h6 class="pb-balance-title">Total Recharge</h6>
                <h2 class="pb-balance-amount">₹{{ number_format($totalRecharge ?? 0, 2) }}</h2>
            </div>
            <div class="pb-balance-card pb-debit">
                <div class="pb-balance-icon">
                    <i class="ti ti-arrow-down-right"></i>
                </div>
                <h6 class="pb-balance-title">Total Shipping Charges</h6>
                <h2 class="pb-balance-amount">₹{{ number_format($totalShipping ?? 0, 2) }}</h2>
            </div>
        </div>
        -->
        <!-- Transactions Table -->
        <div class="pb-transactions-container pb-fade-in" style="margin-left: 13px;">
            <div class="pb-transactions-header">
                <h3 class="pb-transactions-title">
                    <i class="ti ti-history me-2"></i>
                    Shipping Charges History
                </h3>
            </div>
            <style>
                /* Table Header Premium Gradient */
                .pb-custom-table thead th {
                    background: linear-gradient(90deg, #3576e3 0%, #306cc9 100%) !important;
                    color: #fff !important;
                    border: none;
                    font-weight: 600;
                    font-size: 0.95rem;
                    letter-spacing: 0.5px;
                    text-transform: uppercase;
                    box-shadow: 0 1px 6px rgba(54, 118, 227, 0.07);
                }
                .pb-custom-table {
                    overflow: hidden;
                    border-radius: 14px;
                    background: #fff;
                }
                /* Row UX */
                .pb-custom-table tbody tr {
                    transition: box-shadow 0.22s, transform 0.2s;
                }
                .pb-custom-table tbody tr:hover {
                    background: #f1f7fe;
                    box-shadow: 0 2px 18px rgba(54, 118, 227, 0.06);
                    transform: translateY(-2px) scale(1.01);
                }
                /* Table Cell Styling */
                .pb-custom-table tbody td {
                    font-size: 1rem;
                    color: #19355f;
                    vertical-align: middle;
                }
                .shipping-order-id {
                    font-weight: 600;
                    color: #2c62c7;
                    letter-spacing: 0.03em;
                }
                .shipping-awb-badge {
                    display: inline-block;
                    padding: 2px 10px;
                    background: linear-gradient(90deg, #4c91ea 0%, #70a5fa 100%);;
                    color: #fff;
                    font-weight: 500;
                    border-radius: 16px;
                    font-size: 0.92rem;
                    letter-spacing: 0.03em;
                }
                .shipping-courier-text {
                    font-weight: 500;
                    color: #3158a5;
                }
                .shipping-amount-display {
                    color: #00947a;
                    background: #edfcf7;
                    /* font-weight: 700; */
                    padding: 3px 13px;
                    border-radius: 14px;
                    font-size: 0.8rem;
                    letter-spacing: 0.02em;
                }
                /* Empty State Premium Look */
                .pb-empty-state {
                    text-align: center;
                    color: #5b6c92;
                    background: #f3f6fb;
                    border-radius: 12px;
                    padding: 40px 16px 36px 16px;
                }
                .pb-empty-state i {
                    font-size: 2.1rem;
                    color: #3783f7;
                    opacity: 0.87;
                    margin-bottom: 0.2em;
                }
                .pb-empty-state h5 {
                    margin-top: 10px;
                    font-size: 1.12rem;
                    font-weight: 600;
                }
                .pb-empty-state p {
                    font-size: 0.99rem;
                    opacity: 0.86;
                    margin-bottom: 0;
                }
                /* Responsive Table UX */
                @media (max-width: 800px) {
                    .pb-custom-table thead {
                        display: none;
                    }
                    .pb-custom-table, .pb-custom-table tbody, .pb-custom-table tr, .pb-custom-table td {
                        display: block;
                        width: 100%;
                    }
                    .pb-custom-table tr {
                        margin-bottom: 1rem;
                        border-radius: 8px;
                        background: #f7fbff;
                        box-shadow: 0 1px 6px rgba(54, 118, 227, 0.05);
                        padding: 0.4rem 0.8rem;
                    }
                    .pb-custom-table td {
                        text-align: right;
                        position: relative;
                        padding-left: 52%;
                        margin-bottom: 0.6rem;
                    }
                    .pb-custom-table td:before {
                        content: attr(data-label);
                        position: absolute;
                        left: 14px;
                        top: 50%;
                        transform: translateY(-50%);
                        font-weight: bold;
                        color: #3365ad;
                        font-size: 0.97rem;
                        text-align: left;
                    }
                }
            </style>
            <div class="pb-transactions-content">
                <div class="table-responsive">
                    <table class="table pb-custom-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>AWB Number</th>
                                <th>Courier Name</th>
                                <th>Consignee Name</th>
                                <th>Recharge Amount</th>
                            </tr>
                        </thead>
                        <tbody style="text-align: center;">
                            @forelse ($orders as $order)
                                <tr class="pb-slide-in">
                                    <td data-label="Order ID">
                                        <span class="shipping-order-id">
                                            {{ $order->order_number }}
                                        </span>
                                    </td>
                                    <td data-label="AWB Number">
                                        @if($order->awb_number)
                                            <span>{{ $order->awb_number }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td data-label="Courier Name">
                                        <span class="shipping-courier-text">{{ $order->courier_id ?? 'N/A' }}</span>
                                    </td>
                                    <td data-label="Consignee Name">
                                        {{ $order->consignee['name'] ?? 'N/A' }}
                                    </td>
                                    <td data-label="Recharge Amount">
                                        <span class="shipping-amount-display">
                                            @if(isset($order->seller_amount_walate) && $order->seller_amount_walate !== '')
                                                <i class="fa-solid fa-indian-rupee-sign"></i>{{ $order->seller_amount_walate }}
                                            @else
                                                N/A
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="pb-empty-state">
                                        <i class="ti ti-database"></i>
                                        <h5>No Shipping Charges Found</h5>
                                        <p>Your shipping charges history will appear here once you start making transactions.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(method_exists($orders, 'links'))
                    <div class="d-flex justify-content-center mt-3">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
