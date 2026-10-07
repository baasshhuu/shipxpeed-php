
@extends('layouts.sellerdash')

@section('content')
<style>
    /* Page container (shared with other pages) */
    .pc-container {
        padding: 0 10px;
    }

    .invoice-container {
        width: 100%;
        margin: 0;
        padding: 1.5rem 0.8rem 1rem 0.8rem; /* compact page like create.blade */
        min-height: 100vh;
        position: relative;
        z-index: 10;
    }

    .invoice-card {
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        border-radius: 16px; /* smaller radius like modern-card */
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.2);
        overflow: hidden;
        animation: slideInUp 0.45s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .invoice-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 0.8rem 1rem; /* match create header compactness */
        margin: 0 0 1rem 0;
        color: white;
        position: relative;
        overflow: hidden;
        border-radius: 12px 12px 0 0;
    }

    .invoice-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.1;
    }

    .invoice-title {
        font-size: 1.4rem; /* compact title like create page */
        font-weight: 700;
        margin: 0;
        text-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        position: relative;
        z-index: 2;
    }

    .invoice-subtitle {
        font-size: 0.8rem;
        opacity: 0.9;
        margin: 0.25rem 0 0 0;
        position: relative;
        z-index: 2;
    }

    .invoice-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        position: relative;
        z-index: 2;
    }

    /* control icon font sizes so FontAwesome icons don't appear too large */
    .invoice-icon i {
        font-size: 1.1rem; /* ~18px */
    }

    .statement-section {
        background: white;
        border-radius: 12px;
        padding: 0.8rem; /* more compact */
        margin-bottom: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        border: 1px solid #f1f3f4;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
    }

    .section-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        color: white;
    }

    .section-icon i {
        font-size: 1rem; /* ~16px */
    }

    .modern-table {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border: none;
        margin-bottom: 0;
    }

    .modern-table thead th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        padding: 0.8rem 0.8rem; /* compact header cells */
        font-size: 0.75rem;
        position: relative;
    }

    .modern-table thead th::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .modern-table tbody td {
        padding: 0.6rem 0.6rem; /* compact rows */
        border: none;
        border-bottom: 1px solid #f1f3f4;
        font-size: 0.9rem;
        font-weight: 600;
        vertical-align: middle;
        position: relative;
    }

    .modern-table tbody tr {
        transition: all 0.3s ease;
    }

    .modern-table tbody tr:hover {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    /* Help small screens: allow table cells to wrap and avoid horizontal overflow */
    .modern-table th,
    .modern-table td {
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table-container {
        background: white;
        border-radius: 10px;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow-x: auto;
    }

    img, svg {
        max-width: 100%;
        height: auto;
    }

    .amount-cell {
        font-family: 'Courier New', monospace;
        font-size: 1.2rem;
        color: #2c3e50;
    }

    .total-amount {
        background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        color: white !important;
        font-weight: 700;
    }

    .gst-amount {
        background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        color: white !important;
    }

    .final-amount {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white !important;
        font-weight: 700;
        font-size: 1.3rem;
    }

    .download-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
        border: 1px dashed #dee2e6;
        transition: all 0.25s ease;
    }

    .download-section:hover {
        border-color: #667eea;
        background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    }

    .download-btn {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1.2rem;
        color: white;
        font-weight: 600;
        font-size: 0.95rem;
        text-decoration: none;
        transition: all 0.25s ease;
        display: inline-block;
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.18);
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .download-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(231, 76, 60, 0.4);
        color: white;
        text-decoration: none;
    }

    .download-btn:active {
        transform: translateY(-1px);
    }

    .download-text {
        font-size: 1.2rem;
        color: #6c757d;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }

    .download-text .fa-2x {
        font-size: 1.4rem; /* reduce the large download icon */
    }

    .info-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .info-card {
        background: white;
        border-radius: 10px;
        padding: 0.75rem; /* tighter */
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.03);
        border-left: 4px solid;
        transition: all 0.2s ease;
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .info-card.primary { border-left-color: #667eea; }
    .info-card.success { border-left-color: #27ae60; }
    .info-card.warning { border-left-color: #f39c12; }

    .info-label {
        font-size: 0.9rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .info-value {
        font-size: 1.1rem; /* more compact numeric display */
        font-weight: 700;
        color: #2c3e50;
        font-family: 'Courier New', monospace;
    }

    @media (max-width: 768px) {
        .invoice-container {
            padding: 3rem 1rem 1rem 1rem;
            margin-left: 0 !important;
            position: relative;
            z-index: 15;
            width: 100%;
            box-sizing: border-box;
        }
        
        .info-cards {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
            width: 100%;
        }
        
        .info-card {
            padding: 1.2rem;
            width: 100%;
            box-sizing: border-box;
        }
        
        .info-value {
            font-size: 1.5rem;
        }
        
        .invoice-title {
            font-size: 2.2rem;
        }
        
        .invoice-header {
            padding: 2rem 1.5rem;
        }

        /* slightly smaller icons on tablet */
        .invoice-icon {
            width: 44px;
            height: 44px;
            margin-right: 0.8rem;
        }

        .invoice-icon i { font-size: 1rem; }
        .section-icon { width: 32px; height: 32px; }
        .section-icon i { font-size: 0.95rem; }
        .download-text .fa-2x { font-size: 1.25rem; }

        /* Stack header content on small screens */
        .invoice-header .d-flex {
            flex-direction: column;
            text-align: center;
        }

        .invoice-icon {
            margin-right: 0;
            margin-bottom: 0.8rem;
        }
        
        .statement-section {
            padding: 1.5rem;
        }
        
        .modern-table tbody td,
        .modern-table thead th {
            padding: 1rem 0.5rem;
            font-size: 0.9rem;
        }
        
        .download-btn {
            padding: 0.8rem 2rem;
            font-size: 1rem;
        }
    }

    @media (max-width: 1024px) {
        .invoice-container {
            padding: 4rem 0.8rem 2rem 0.8rem;
            position: relative;
            z-index: 12;
        }
        
        .info-cards {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.2rem;
        }
    }

    @media (max-width: 480px) {
        .invoice-container {
            padding: 2rem 0.8rem 1rem 0.8rem;
            margin-left: 0 !important;
            z-index: 20;
        }
        
        .invoice-card {
            border-radius: 15px;
            margin: 0;
        }
        
        .invoice-header {
            padding: 1.5rem 1rem;
        }
        
        .invoice-title {
            font-size: 1.8rem;
        }
        
        .info-cards {
            gap: 0.8rem;
        }
        
        .info-card {
            padding: 1rem;
        }
        
        .info-value {
            font-size: 1.3rem;
        }

        /* mobile: make icons smaller so header fits better */
        .invoice-icon {
            width: 40px;
            height: 40px;
            margin-right: 0.6rem;
        }

        .invoice-icon i { font-size: 0.95rem; }
        .section-icon { width: 28px; height: 28px; }
        .section-icon i { font-size: 0.85rem; }
        .download-text .fa-2x { font-size: 1.05rem; }
    }

    @media (max-width: 380px) {
        .invoice-title {
            font-size: 1.4rem;
        }

        .modern-table thead th,
        .modern-table tbody td {
            padding: 0.5rem 0.4rem;
            font-size: 0.75rem;
        }

        .download-btn {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
    }
</style>

<div class="pc-container" style="padding: 0;">
    <div class="invoice-container">
        <div class="invoice-card">
        <!-- Header Section -->
        <div class="invoice-header">
            <div class="d-flex align-items-center">
                <div class="invoice-icon">
                    <i class="fas fa-file-invoice-dollar fa-2x"></i>
                </div>
                <div>
                    <h1 class="invoice-title">Monthly Invoice</h1>
                    <p class="invoice-subtitle">{{ $month }} Financial Statement</p>
                </div>
            </div>
        </div>

        <!-- Quick Info Cards -->
        @php
            $gst = $monthlySellerAmount * 0.18;
            $finalAmount = $monthlySellerAmount - $gst;
        @endphp
        
        <div class="info-cards">
            <div class="info-card primary">
                <div class="info-label">Total Deduction</div>
                <div class="info-value">₹{{ number_format($monthlySellerAmount, 2) }}</div>
            </div>
            <div class="info-card warning">
                <div class="info-label">GST (18%)</div>
                <div class="info-value">₹{{ number_format($gst, 2) }}</div>
            </div>
            <div class="info-card success">
                <div class="info-label">Final Amount</div>
                <div class="info-value">₹{{ number_format($finalAmount, 2) }}</div>
            </div>
        </div>

        <!-- Statement Section -->
        <div class="statement-section">
            <h2 class="section-title">
                <div class="section-icon">
                    <i class="fas fa-calculator"></i>
                </div>
                Detailed Breakdown
            </h2>
            
            <div class="table-responsive">
                <table class="table modern-table">
                    <thead>
                        <tr>
                            <th scope="col">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Total Deduction
                            </th>
                            <th scope="col">
                                <i class="fas fa-percentage me-2"></i>
                                GST (18%)
                            </th>
                            <th scope="col">
                                <i class="fas fa-coins me-2"></i>
                                Amount After GST
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="amount-cell total-amount">
                                <i class="fas fa-rupee-sign me-1"></i>
                                {{ number_format($monthlySellerAmount, 2) }}
                            </td>
                            <td class="amount-cell gst-amount">
                                <i class="fas fa-rupee-sign me-1"></i>
                                {{ number_format($gst, 2) }}
                            </td>
                            <td class="amount-cell final-amount">
                                <i class="fas fa-rupee-sign me-1"></i>
                                {{ number_format($finalAmount, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Download Section -->
        <div class="download-section">
            <div class="download-text">
                <i class="fas fa-download fa-2x mb-3 text-muted"></i>
                <div>Ready to download your invoice?</div>
            </div>
            <form method="GET" action="{{ route('seller.invoice.pdf') }}" class="d-inline-block">
                <input type="hidden" name="month" value="{{ $month }}">
                <button type="submit" class="download-btn">
                    <i class="fas fa-file-pdf me-2"></i>
                    Download PDF Invoice
                </button>
            </form>
        </div>
        </div>
    </div>
</div>
@endsection



