<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Shipping Label</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .label-container {
            max-width: 4in;
            min-height: 6in;
            width: 100%;
            background: white;
            border: 2px solid #000;
            padding: 10px;
            margin: 0 auto;
            box-sizing: border-box;
        }
        
        /* Mobile First Responsive Design */
        @media screen and (max-width: 768px) {
            body {
                padding: 10px;
            }
            .label-container {
                max-width: 100%;
                min-height: auto;
                padding: 15px;
            }
        }
        
        @media screen and (max-width: 480px) {
            body {
                padding: 5px;
            }
            .label-container {
                padding: 10px;
            }
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .company-logo {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        /* Responsive header */
        @media screen and (max-width: 480px) {
            .company-logo {
                font-size: 16px;
            }
        }
        .barcode-section {
            text-align: center;
            margin: 10px 0;
        }
        .barcode {
            font-family: 'Courier New', monospace;
            font-size: 24px;
            letter-spacing: 2px;
            margin: 5px 0;
            word-break: break-all;
        }
        .awb-number {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
        }
        .service-info {
            font-size: 12px;
            margin: 5px 0;
        }
        
        /* Responsive barcode section */
        @media screen and (max-width: 480px) {
            .barcode {
                font-size: 18px;
                letter-spacing: 1px;
            }
            .awb-number {
                font-size: 13px;
            }
            .service-info {
                font-size: 11px;
            }
        }
        .weight-date {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin: 10px 0;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        /* Responsive weight-date section */
        @media screen and (max-width: 480px) {
            .weight-date {
                font-size: 11px;
                flex-direction: column;
                gap: 5px;
            }
        }
        .ship-to {
            border: 1px solid #000;
            padding: 8px;
            margin: 10px 0;
            position: relative;
        }
        .ship-to h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        .address {
            font-size: 12px;
            line-height: 1.3;
            word-wrap: break-word;
        }
        
        /* Responsive ship-to section */
        @media screen and (max-width: 480px) {
            .ship-to h3 {
                font-size: 13px;
            }
            .address {
                font-size: 11px;
            }
        }
        .cod-section {
            float: right;
            border: 2px solid #000;
            padding: 8px;
            text-align: center;
            width: 80px;
            margin-top: -50px;
            position: relative;
            z-index: 1;
        }
        .cod-title {
            font-weight: bold;
            font-size: 14px;
        }
        .cod-amount {
            font-size: 12px;
            margin: 5px 0;
        }
        .order-barcode {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            word-break: break-all;
        }
        
        /* Responsive COD section */
        @media screen and (max-width: 768px) {
            .cod-section {
                float: none;
                margin: 10px auto 0;
                width: 100px;
            }
        }
        
        @media screen and (max-width: 480px) {
            .cod-section {
                width: 90px;
                padding: 6px;
            }
            .cod-title {
                font-size: 12px;
            }
            .cod-amount {
                font-size: 11px;
            }
            .order-barcode {
                font-size: 9px;
            }
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin: 10px 0;
            table-layout: auto;
        }
        .product-table th,
        .product-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .product-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        /* Responsive product table */
        @media screen and (max-width: 768px) {
            .product-table {
                font-size: 9px;
            }
            .product-table th,
            .product-table td {
                padding: 3px;
            }
        }
        
        @media screen and (max-width: 480px) {
            .product-table {
                font-size: 8px;
            }
            .product-table th,
            .product-table td {
                padding: 2px;
            }
            
            /* Stack table on very small screens */
            .product-table thead {
                display: none;
            }
            .product-table tr {
                display: block;
                border: 1px solid #000;
                margin-bottom: 5px;
            }
            .product-table td {
                display: block;
                border: none;
                border-bottom: 1px solid #ccc;
                text-align: right;
                padding-left: 50%;
                position: relative;
            }
            .product-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                width: 45%;
                text-align: left;
                font-weight: bold;
            }
        }
            padding: 3px;
            text-align: left;
        }
        .total-section {
            text-align: right;
            font-size: 12px;
            margin: 10px 0;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
            border-top: 1px solid #000;
            padding-top: 5px;
            line-height: 1.4;
        }
        
        /* Additional responsive styles */
        @media screen and (max-width: 768px) {
            .total-section {
                font-size: 11px;
                text-align: center;
            }
            .footer {
                font-size: 9px;
            }
        }
        
        @media screen and (max-width: 480px) {
            .total-section {
                font-size: 10px;
            }
            .footer {
                font-size: 8px;
            }
        }
        
        /* Print styles to maintain original size for printing */
        @media print {
            body {
                padding: 0;
            }
            .label-container {
                width: 4in;
                height: 6in;
                max-width: 4in;
                min-height: 6in;
            }
        }
    </style>
</head>
<body>
    <div class="label-container">
        <div class="header">
            <div class="company-logo">📦 SHIPXPEED</div>
        </div>
        
        <div class="barcode-section">
            <div class="barcode">||||| |||| | ||| || |||||| ||</div>
            <div class="awb-number">AWB No 77945116616</div>
            <div class="service-info">
                Bluedart- Surface<br>
                DEL/EED/EED<br>
                NCR 110092
            </div>
        </div>
        
        <div class="weight-date">
            <span>Weight: 0.2kg</span>
            <span>Order Date: 2025-07-29</span>
        </div>
        
        <div class="ship-to">
            <h3>Ship To:</h3>
            <div class="address">
                <strong>test</strong><br>
                delhi<br>
                Delhi, NCR 110092<br>
                Ph: 7357169546
            </div>
        </div>
        
        <div class="cod-section">
            <div class="cod-title">COD</div>
            <div>Please collect</div>
            <div class="cod-amount"><strong>Rs.100.00</strong></div>
            <div class="order-barcode">||||| |||| |</div>
            <div style="font-size: 8px;">Order #SPX#725</div>
        </div>
        
        <table class="product-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td data-label="Product">test (SKU NUMBER : SPX#725)</td>
                    <td data-label="Qty">1</td>
                    <td data-label="Subtotal">100.00</td>
                </tr>
            </tbody>
        </table>
        
        <div class="total-section">
            <strong>Total (+ Discount/Shipping Charges): 100.00</strong>
        </div>
        
        <div class="footer">
            <div>Return To:</div>
            <div>S_X_D, delhi - delhi,Delhi,NCR,110092, Phone : 7357169546</div>
        </div>
    </div>
</body>
</html>