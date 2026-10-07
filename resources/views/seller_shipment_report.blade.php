@extends('layouts.sellerdash')

@section('content')

<style>
    .shipment-report-page {
        --sr-primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --sr-info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --sr-dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }
    .shipment-report-page .sr-header {
        background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 0.45rem 0; /* further reduced header */
        margin-bottom: 0.6rem;
        border-radius: 7px;
        box-shadow: 0 1px 6px rgba(102, 126, 234, 0.12);
        text-align: center;
    }
    .shipment-report-page .sr-title {
        font-size: 1rem; /* smaller */
        font-weight: 700;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.18);
        margin-bottom: 0.1rem;
    }
    .shipment-report-page .sr-subtitle {
        font-size: 0.75rem;
        opacity: 0.9;
        margin: 0;
    }
    .shipment-report-page .sr-filter-card {
        background: white;
        border-radius: 7px;
        box-shadow: 0 3px 14px rgba(0, 0, 0, 0.04);
        padding: 0.5rem 0.6rem; /* tighter */
        margin-bottom: 0.8rem;
        /* max-width: 920px;
        margin-left: auto;
        margin-right: auto; */
        display: block;
    }
    .shipment-report-page .sr-filter-form {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto auto;
        gap: 0.4rem;
        align-items: center;
        width: 100%;
    }
    .shipment-report-page .sr-filter-form input[type="text"],
    .shipment-report-page .sr-filter-form input[type="date"] {
        border-radius: 6px;
        border: 1px solid #eef2f7;
        font-size: 0.9rem;
        padding: 0.35rem 0.6rem;
        background: #ffffff;
        transition: border-color 0.2s;
        min-width: 0;
    }
    .shipment-report-page .sr-filter-form input[type="text"]:focus,
    .shipment-report-page .sr-filter-form input[type="date"]:focus {
        border-color: #667eea;
        outline: none;
    }
    .shipment-report-page .sr-filter-form .btn {
        border-radius: 6px;
        font-size: 0.88rem;
        padding: 0.35rem 0.6rem;
        font-weight: 600;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        min-width: 0;
    }
    .shipment-report-page .sr-filter-form .btn-success {
        background: #44d62c;
        border: none;
        color: #fff;
        transition: background 0.2s;
    }
    .shipment-report-page .sr-filter-form .btn-success:hover {
        background: #2bb418;
    }
    .shipment-report-page .sr-filter-form .btn-dark {
        background: #222;
        border: none;
        color: #fff;
        transition: background 0.2s;
    }
    .shipment-report-page .sr-filter-form .btn-dark:hover {
        background: #444;
    }
    .shipment-report-page .sr-table-card {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
        padding: 0.6rem 0.6rem 0.5rem 0.6rem; /* compact */
        margin-bottom: 0.8rem;
        border: 1px solid #f1f4f8;
        overflow-x: auto;
    }
    .shipment-report-page .sr-custom-table {
        border: none;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        width: 100%;
        background: #fff;
    }
    .shipment-report-page .sr-custom-table thead th {
        background:linear-gradient(93deg, #8e9fd9 5%) !important;
        color: #fff;
        border: none;
        font-weight: 700;
        padding: 0.45rem 0.45rem; /* compact */
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.35px;
        /* border-top-left-radius: 6px;
        border-top-right-radius: 6px; */
    }
    .shipment-report-page .sr-custom-table tbody td {
        border: none;
        padding: 0.45rem 0.45rem; /* compact */
        vertical-align: middle;
        text-align:center;
        border-bottom: 1px solid #f8f9fb;
        font-size: 0.85rem;
        background: #fff;
        color: #2c3e50;
    }
    .shipment-report-page .sr-custom-table tbody tr:last-child td {
        border-bottom: none;
    }
    .shipment-report-page .sr-custom-table tbody tr:hover {
        background: #f8f9ff;
        transform: scale(1.01);
        transition: all 0.2s;
    }
    .shipment-report-page .sr-pagination {
        display: flex;
        justify-content: center;
        margin-top: 0.6rem;
    }
    @media (max-width: 768px) {
        .shipment-report-page .sr-header {
            font-size: 1.05rem;
        }
        .shipment-report-page .sr-table-card {
            padding: 0.8rem;
        }
        .shipment-report-page .sr-custom-table thead th,
        .shipment-report-page .sr-custom-table tbody td {
            padding: 0.5rem 0.4rem;
            font-size: 0.82rem;
        }
        .shipment-report-page .sr-filter-card {
            padding: 0.6rem;
        }
        .shipment-report-page .sr-filter-form {
            grid-template-columns: 1fr; /* stacked on mobile */
            gap: 0.5rem;
        }
        .shipment-report-page .sr-filter-form .btn,
        .shipment-report-page .sr-filter-form a.btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="pc-container shipment-report-page" style="background:#646dff26;">
    <div class="pc-content" style="margin-left:12px;">
        <!-- Header -->
        <div class="sr-header">
            <div class="container">
                <h1 class="sr-title">📦 Shipment Report</h1>
                <p class="sr-subtitle">View and filter your shipment transactions</p>
            </div>
        </div>

        <div class="sr-filter-card">
            <form method="GET" action="{{ route('seller.shipment.report') }}" class="sr-filter-form" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <div class="input-group sr-search-group" style="max-width:470px; width: 100%; flex:2;">
                    <span class="input-group-text" style="background:#f7f7fa;">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" name="awb" placeholder="Search AWB Number" value="{{ request('awb') }}" class="form-control" style="height: 44px;" />
                </div>
                <div class="sr-date-actions d-flex align-items-center" style="margin-left: auto; gap: 0.4rem;">
                    <input type="date" name="start_date" value="{{ request('start_date') }}" style="width:145px; min-width: 0; height: 44px;" />
                    <input type="date" name="end_date" value="{{ request('end_date') }}" style="width:145px; min-width: 0; height: 44px;" />
                    <button type="submit" class="btn btn-dark d-flex align-items-center justify-content-center" style="height: 44px;">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="{{ route('shipment.report.download', request()->query()) }}" class="btn btn-success d-flex align-items-center justify-content-center" style="height: 44px;">
                        <i class="fa-solid fa-download"></i>
                    </a>
                </div>
            </form>
            <style>
                @media (max-width: 600px) {
                    .sr-filter-form {
                        flex-direction: column !important;
                        align-items: stretch !important;
                        gap: 0.75rem !important;
                    }
                    .sr-filter-form .sr-search-group {
                        width: 100% !important;
                        max-width: 100% !important;
                    }
                    .sr-filter-form .sr-date-actions {
                        width: 100%;
                        gap: 0.38rem !important;
                        display: flex !important;
                        flex-direction: row !important;
                        margin-left: 0 !important;
                        justify-content: space-between;
                    }
                    .sr-filter-form .sr-date-actions input[type="date"] {
                        width: 100px !important;
                        min-width: 0 !important;
                        flex: 1 1 0;
                        height: 44px;
                    }
                    .sr-filter-form .sr-date-actions button,
                    .sr-filter-form .sr-date-actions a.btn {
                        min-width: 40px;
                        padding-left: 0.6rem;
                        padding-right: 0.6rem;
                        height: 44px;
                        flex: 0 0 40px;
                        font-size: 1rem;
                    }
                }
            </style>
        </div>

        <!-- Table Card -->
        <div class="sr-table-card">
            <div class="table-responsive">
                <table class="sr-custom-table">
                    <thead>
                        <tr>
                            <th>AWB Number</th>
                            <th>Order Number</th>
                            <th>Courier ID</th>
                            <th>Package Weight</th>
                            <th>Status</th>
                            <th>Shipment Amount (₹)</th>
                            <th>GST 18% (₹)</th>
                            <th>Total Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $order)
                            @php
                                // $amount = $order->seller_amount_walate ?? 0;
                                $amountIncludingGST = $order->seller_amount_walate ?? 0;
                                 $amount = round($amountIncludingGST / 1.18, 2); // ✅ GST removed amount

                                $gst = round($amount * 0.18, 2);
                                $total = $amount + $gst;
                            @endphp
                            <tr>
                                <td>{{ $order->awb_number ?? 'N/A' }}</td>
                                <td>#{{ $order->order_number ?? 'N/A' }}</td>
                                <td>
                                    @if($order->courier_id == 'smartship')
                                        {{ $order->smarship_courier_id ?? 'N/A' }}
                                    @else
                                        {{ $order->all_courier_name ?? 'N/A' }}
                                    @endif
                                </td>
                                <td>{{ $order->package_weight ?? 'N/A' }} GMS</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $order->shipping_status ? ucfirst($order->shipping_status) : 'N/A' }}</span>
                                </td>
                                <td>₹{{ number_format($amount, 2) }}</td>
                                <td>₹{{ number_format($gst, 2) }}</td>
                                <td>₹{{ number_format($total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted py-4">No shipment data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="sr-pagination">
                {{ $data->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection


<?php /* ?>
@extends('layouts.sellerdash')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex flex-column flex-md-row justify-content-between align-items-center"  style="padding-top: 100px;padding-right: 200px">
        <h5 class="mb-2 mb-md-0 fw-bold text-primary">📦 Shipment Report</h5>

        <form method="GET" action="{{ route('shipment.report') }}" class="d-flex flex-wrap gap-2">
            <input type="text" name="awb" class="form-control form-control-sm"
                   placeholder="Search AWB Number" value="{{ request('awb') }}" />

            <input type="date" name="start_date" class="form-control form-control-sm"
                   value="{{ request('start_date') }}" placeholder="Start Date" />

            <input type="date" name="end_date" class="form-control form-control-sm"
                   value="{{ request('end_date') }}" placeholder="End Date" />

            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-filter me-1"></i> Filter
            </button>

            <a href="{{ route('shipment.report.download', request()->query()) }}" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel me-1"></i> Download Excel
            </a>
        </form>
    </div>

    <div class="card-body p-0" >
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0" >
                <thead class="table-primary text-center text-dark fw-semibold" >
                    <tr>
                        <th>AWB Number</th>
                        {{-- <th>Seller Name</th> --}}
                        <th>Status</th>
                        <th>Shipment Amount (₹)</th>
                        <th>GST 18% (₹)</th>
                        <th>Total Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $order)
                        @php
                            $amount = $order->seller_amount_walate ?? 0;
                            $gst = round($amount * 0.18, 2);
                            $total = $amount + $gst;
                        @endphp
                        <tr class="text-center">
                            <td>{{ $order->awb_number }}</td>
                            {{-- <td>{{ $order->seller->name ?? 'N/A' }}</td> --}}
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $order->shipping_status ? ucfirst($order->shipping_status) : 'N/A' }}
                                </span>
                            </td>
                            <td>₹{{ number_format($amount, 2) }}</td>
                            <td>₹{{ number_format($gst, 2) }}</td>
                            <td>₹{{ number_format($total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No shipment data found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 px-3">
            {{ $data->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection


<?php */ ?>