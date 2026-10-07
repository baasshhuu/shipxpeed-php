@extends('layouts.sellerdash')

@section('content')

<!-- Add PDF library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    .shipmentprice-page {
        --sp-primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --sp-success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --sp-info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --sp-dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }
    .shipmentprice-page .sp-header {
        background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 0.65rem 0; /* slightly more compact */
        margin-bottom: 0.8rem;
        border-radius: 7px;
        box-shadow: 0 3px 12px rgba(102, 126, 234, 0.18);
        text-align: center;
    }
    .shipmentprice-page .sp-title {
        font-size: 1.3rem;
        font-weight: 700;
       
        margin-bottom: 0.2rem;
    }
    .shipmentprice-page .sp-subtitle {
        font-size: 0.8rem;
        opacity: 0.9;
    }
    .shipmentprice-page .sp-balance-card {
        background: white;
        border-radius: 10px;
        padding: 0.6rem; /* compact */
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
        min-height: 80px;
        margin-bottom: 1rem;
    }
    .shipmentprice-page .sp-balance-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--sp-success-gradient);
    }
    .shipmentprice-page .sp-balance-title {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 0.2rem;
        font-weight: 500;
    }
    .shipmentprice-page .sp-balance-amount {
        font-size: 1.2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
    }
    .shipmentprice-page .sp-download-btn {
        margin-top: 0.5rem;
    }
    .shipmentprice-page .sp-rate-card {
        background: #f6f8fc;
        border-radius: 12px; /* smaller radius */
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.08);
        margin-bottom: 1rem;
        overflow: hidden;
        border: 1px solid #e6eaf0;
        transition: box-shadow 0.2s;
    }
    .shipmentprice-page .sp-rate-card:hover {
        box-shadow: 0 16px 48px rgba(102, 126, 234, 0.18);
    }
    .shipmentprice-page .sp-rate-card-header {
        background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: #fff;
        padding: 0.7rem 1rem; /* compact header */
        font-weight: 700;
        font-size: 1rem;
        border-bottom: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        letter-spacing: 0.4px;
        box-shadow: 0 1px 6px rgba(102, 126, 234, 0.06);
    }
    .shipmentprice-page .sp-rate-card-header .badge {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        color: #2c3e50;
        font-size: 0.9rem;
        border-radius: 10px;
        padding: 0.4rem 1rem;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(168, 237, 234, 0.15);
    }
    .shipmentprice-page .sp-rate-card-table {
        padding: 0.8rem 1rem;
    }
    .shipmentprice-page .sp-custom-table {
        border: none;
        /* border-radius: 12px; */
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        width: 100%;
        background: #fff;
    }
    .shipmentprice-page .sp-custom-table thead th {
        background: linear-gradient(90deg, #3576e3 0, #306cc9 100%) !important;
        color: #fff;
        border: none;
        font-weight: 700;
        padding: 0.6rem 0.6rem; /* compact */
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.4px;
       
    }
    .shipmentprice-page .sp-custom-table tbody td {
        border: none;
        padding: 0.6rem 0.6rem; /* compact */
        vertical-align: middle;
        text-align:center;
        border-bottom: 1px solid #f1f3f4;
        font-size: 0.95rem;
        background: #fff;
        color: #2c3e50;
    }
    .shipmentprice-page .sp-custom-table tbody tr:last-child td {
        border-bottom: none;
    }
    .shipmentprice-page .sp-custom-table tbody tr:hover {
        background: #f8f9ff;
        transform: scale(1.01);
        transition: all 0.2s;
    }
    .shipmentprice-page .sp-note-card {
        background: #f8f9fa;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.06);
        padding: 0.8rem;
        margin-top: 1rem;
    }
    @media (max-width: 768px) {
        .shipmentprice-page .sp-title {
            font-size: 1rem;
        }
        .shipmentprice-page .sp-rate-card-header {
            font-size: 0.95rem;
            padding: 0.6rem 0.8rem;
        }
        .shipmentprice-page .sp-custom-table {
            font-size: 0.82rem;
        }
        .shipmentprice-page .sp-balance-card {
            min-height: 70px;
        }
    }
</style>

<div class="pc-container shipmentprice-page">
    <div class="pc-content" style="margin-left:12px;background:#646dff26;">
        <!-- Header -->
        <div class="sp-header">
            <div class="container">
                <h1 class="sp-title">Shipping Rate Card</h1>
                <p class="sp-subtitle">Check your wallet balance and download the latest rate card</p>
            </div>
        </div>

        <!-- Wallet Balance & Download -->
        <div class="sp-balance-card d-flex justify-content-between align-items-center">
            <div>
                <h6 class="sp-balance-title">Current Wallet Balance</h6>
                <h2 class="sp-balance-amount">₹{{ number_format($totalAmount, 2) }}</h2>
            </div>
            <div class="sp-download-btn d-flex flex-wrap gap-2 align-items-stretch" style="min-width:0;">
                @if($pdfLinks['pdf_10'])
                <a 
                    href="{{ $pdfLinks['pdf_10'] }}" 
                    class="btn btn-sm btn-outline-primary flex-fill d-flex align-items-center justify-content-center"
                    style="min-width: 0; min-height: 29px; height: 29px; font-size: 0.82rem; padding: 0.17rem 0.67rem;"
                >
                    <i class="fa fa-download me-1"></i> <span class="d-none d-sm-inline">Download Rate Card</span>
                    <span class="d-inline d-sm-none">Download</span>
                </a>
                @endif
                <button 
                    onclick="downloadPageAsPDFLibrary()" 
                    class="btn btn-sm btn-success flex-fill d-flex align-items-center justify-content-center"
                    style="min-width: 0; min-height: 29px; height: 29px; font-size: 0.82rem; padding: 0.17rem 0.67rem;"
                >
                    <i class="fa fa-file-pdf me-1"></i> 
                    <span class="d-none d-sm-inline">Download Page as PDF</span>
                    <span class="d-inline d-sm-none">PDF</span>
                </button>
                <button 
                    onclick="window.print()" 
                    class="btn btn-sm btn-info flex-fill d-flex align-items-center justify-content-center"
                    style="min-width: 0; min-height: 29px; height: 29px; font-size: 0.82rem; padding: 0.17rem 0.67rem;"
                >
                    <i class="fa fa-print me-1"></i> 
                    <span class="d-none d-sm-inline">Print/Save as PDF</span>
                    <span class="d-inline d-sm-none">Print</span>
                </button>
            </div>
            <style>
                @media (max-width: 576px) {
                    .sp-download-btn .btn {
                        font-size: 0.76rem !important;
                        padding: 0.08rem 0.32rem !important;
                        min-height: 25px !important;
                        height: 25px !important;
                    }
                    .sp-download-btn {
                        gap: 0.38rem !important;
                    }
                }
            </style>
        </div>

        <!-- Rate Card Display -->
        @foreach($rateData as $serviceName => $service)
            <div class="sp-rate-card">
                <div class="sp-rate-card-header">
                    <span>{{ $serviceName }}</span>
                    @if(isset($service['cod_charge']))
                    <span class="badge">COD: {{ $service['cod_charge'] }}</span>
                    @endif
                </div>
                <div class="sp-rate-card-table">
                    <div class="table-responsive">
                        <table class="sp-custom-table">
                            <thead>
                                <tr>
                                    @foreach($service['headers'] as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($service['rows'] as $row)
                                    <tr>
                                        @foreach($row as $cell)
                                            <td>
                                                @if(is_numeric($cell))
                                                    ₹{{ number_format($cell, 2) }}
                                                @else
                                                    {!! $cell !!}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Note Card -->
        <div class="sp-note-card">
            <p class="mb-1"><strong>Note:</strong></p>
            <ul class="mb-1 ps-3">
                <li>18% GST applicable (exclusive of base shipping rate)</li>
                <li>100% Forward Charges applicable on all RTO shipments</li>
                <li>COD Charges: ₹{{$priceSettingsXpressBees_cod_charge}} or {{$priceSettingsXpressBees_cod_charge_parsent}}% of order value, whichever is higher</li>
            </ul>
            <p class="mb-1"><strong>Claims on Lost/Damaged Shipments:</strong></p>
            <ul class="mb-0 ps-3">
                <li>Up to ₹2000: Fixed claim of ₹2000</li>
                <li>Above ₹2000: Certificate of Loss will be provided for further insurance or legal claim</li>
            </ul>
        </div>
    </div>
</div>

<style>
    /* Responsive adjustments */
    .rate-card-container .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    }
    .rate-card-container .table th {
        white-space: nowrap;
        background-color: #f8f9fa;
    }
    .rate-card-container .table td {
        vertical-align: middle;
    }
    
    @media (max-width: 1200px) {
        .pc-content {
            padding-left: 0 !important;
        }
        .mt-2 {
            padding-left: 15px !important;
            padding-right: 15px !important;
        }
    }
    @media (max-width: 768px) {
        .rate-card-container .card-header h5 {
            font-size: 1rem;
        }
        .rate-card-container .table {
            font-size: 0.85rem;
        }
    }
    .text-nowrap {
        white-space: nowrap;
    }
    
    /* PDF Download Button Styling */
    .btn-success {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        transition: all 0.3s ease;
    }
    .btn-success:hover {
        background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }
    
    /* Print-specific styles for PDF */
    @media print {
        .sp-download-btn, .btn, button {
            display: none !important;
            visibility: hidden !important;
        }
        .shipmentprice-page {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: none !important;
        }
        .pc-container {
            margin: 0 !important;
            padding: 5px !important;
            width: 100% !important;
            max-width: none !important;
        }
        .pc-content {
            padding: 0 !important;
            width: 100% !important;
        }
        .sp-header {
            margin-bottom: 15px !important;
            padding: 10px 0 !important;
        }
        .sp-rate-card {
            page-break-inside: avoid !important;
            margin-bottom: 15px !important;
            width: 100% !important;
        }
        .sp-custom-table {
            page-break-inside: avoid !important;
            width: 100% !important;
            font-size: 11px !important;
        }
        .sp-custom-table th,
        .sp-custom-table td {
            padding: 8px 4px !important;
            font-size: 10px !important;
        }
        .sp-balance-card {
            justify-content: flex-start !important;
            display: block !important;
            margin-bottom: 15px !important;
        }
        .sp-rate-card-header {
            font-size: 14px !important;
            padding: 10px !important;
        }
        .sp-note-card {
            margin-top: 10px !important;
            font-size: 11px !important;
        }
    }
    
    /* CSS class to hide elements during PDF generation */
    .pdf-generating .sp-download-btn,
    .pdf-generating .btn,
    .pdf-generating button {
        display: none !important;
        visibility: hidden !important;
    }
    
    .pdf-generating .sp-balance-card {
        justify-content: flex-start !important;
        display: block !important;
    }
    
    .pdf-generating .shipmentprice-page {
        width: 100% !important;
        max-width: none !important;
    }
    
    .pdf-generating .pc-container {
        width: 100% !important;
        max-width: none !important;
    }
</style>

<script>
// Wait for page to load
document.addEventListener('DOMContentLoaded', function() {
    
    // Method 1: Simple print-to-PDF (most reliable)
    window.downloadPageAsPDF = function() {
        const downloadBtns = document.querySelectorAll('.sp-download-btn button');
        downloadBtns.forEach(btn => btn.style.display = 'none');
        
        const originalTitle = document.title;
        document.title = 'Shipping_Rate_Card_' + new Date().toISOString().split('T')[0];
        
        setTimeout(() => {
            window.print();
            downloadBtns.forEach(btn => btn.style.display = 'inline-block');
            document.title = originalTitle;
        }, 500);
    }

    // Method 2: Using html2pdf library
    window.downloadPageAsPDFLibrary = function() {
        console.log('PDF download function called');
        
        // Add PDF generating class to hide buttons
        document.body.classList.add('pdf-generating');
        
        // Check if html2pdf is available
        if (typeof html2pdf === 'undefined') {
            console.log('html2pdf not loaded, using print method');
            document.body.classList.remove('pdf-generating');
            alert('PDF library not loaded. Using print method instead.');
            window.downloadPageAsPDF();
            return;
        }
        
        const element = document.querySelector('.shipmentprice-page');
        if (!element) {
            console.error('Page element not found');
            document.body.classList.remove('pdf-generating');
            alert('Page content not found');
            return;
        }
        
        const opt = {
            margin: [0.2, 0.2, 0.2, 0.2],
            filename: 'shipping-rate-card.pdf',
            image: { type: 'jpeg', quality: 1.0 },
            html2canvas: { 
                scale: 3,
                useCORS: true,
                allowTaint: true,
                width: element.scrollWidth,
                height: element.scrollHeight,
                scrollX: 0,
                scrollY: 0,
                onclone: function(clonedDoc) {
                    // Hide all buttons in the cloned document
                    const buttons = clonedDoc.querySelectorAll('.sp-download-btn, .btn, button');
                    buttons.forEach(btn => {
                        btn.style.display = 'none';
                        btn.style.visibility = 'hidden';
                    });
                    
                    // Adjust balance card layout
                    const balanceCard = clonedDoc.querySelector('.sp-balance-card');
                    if (balanceCard) {
                        balanceCard.style.justifyContent = 'flex-start';
                        balanceCard.style.display = 'block';
                    }
                    
                    // Make all content fit properly
                    const pageContent = clonedDoc.querySelector('.shipmentprice-page');
                    if (pageContent) {
                        pageContent.style.width = '100%';
                        pageContent.style.maxWidth = 'none';
                        pageContent.style.fontSize = '14px';
                    }
                    
                    // Adjust tables for better PDF display
                    const tables = clonedDoc.querySelectorAll('.sp-custom-table');
                    tables.forEach(table => {
                        table.style.width = '100%';
                        table.style.fontSize = '12px';
                    });
                    
                    // Adjust rate cards
                    const rateCards = clonedDoc.querySelectorAll('.sp-rate-card');
                    rateCards.forEach(card => {
                        card.style.marginBottom = '15px';
                        card.style.pageBreakInside = 'avoid';
                    });
                }
            },
            jsPDF: { 
                unit: 'mm', 
                format: 'a4', 
                orientation: 'portrait',
                compress: true
            },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
        };
        
        console.log('Starting PDF generation...');
        html2pdf().set(opt).from(element).save().then(() => {
            console.log('PDF generated successfully');
            document.body.classList.remove('pdf-generating');
        }).catch((error) => {
            console.error('PDF generation failed:', error);
            document.body.classList.remove('pdf-generating');
            alert('PDF generation failed. Using print method instead.');
            window.downloadPageAsPDF();
        });
    }

    // Method 3: Alternative backend method
    window.downloadPageAsPDFBackend = function() {
        const currentUrl = window.location.href;
        const pdfUrl = currentUrl.replace('/shipment-price', '/shipment-price-pdf');
        window.open(pdfUrl, '_blank');
    }
    
});
</script>
</script>
@endsection




















