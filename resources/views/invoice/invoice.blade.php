<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #000;
            padding: 40px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .logo {
            max-height: 60px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
        }

        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .invoice-table th, .invoice-table td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 13px;
        }

        .text-right {
            text-align: right;
        }

        .bank-details, .footer {
            margin-top: 30px;
            font-size: 12px;
        }

        .footer {
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="header">
        <div>
            @php
                $logoPath = public_path('1745560772_5971.png'); // make sure image exists here
                $logoBase64 = base64_encode(file_get_contents($logoPath));
            @endphp
            <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Company Logo" class="logo">
        </div>
        <div class="company-name">
            SHIPXPEED LOGISTICS LLP<br>
            GSTIN: 07AFPFS2846L1Z<br>
            PAN: AFPFS2846L<br>
            support@shipxpeed.com<br>
            +91-9871670388
        </div>
    </div>

    <h2 style="margin-top: 15px;">TAX Invoice</h2>

    <table class="info-table" width="100%">
        <tr>
             <td><strong>Invoice Number:</strong> {{ 'SXP-INV-' . $invoiceDate . '/000' . $invoiceNumber }}</td>

            <td><strong>Invoice Date:</strong> {{ $invoiceDatelast1 }}</td>
        </tr>
        <tr>
            <td><strong>Invoice Month:</strong> {{ $month }}</td>
            {{-- <td><strong>Customer State:</strong> {{ $sellerstatename }}</td> --}}
        </tr>
    </table>

    <div class="section-title">Customer Details:</div>
    <table class="info-table" width="100%">
        <tr><td><strong>Customer Name:</strong> {{ $seller->name }}</td></tr>
        <tr><td><strong>Address:</strong> {{ $sellerAddress->address_line ?? 'N/A' }}</td></tr>
        <tr><td><strong>Email:</strong> {{ $seller->email }}</td></tr>
        <tr><td><strong>Phone:</strong> {{ $seller->phone_number ?? '-' }}</td></tr>
      <tr><td><strong>GST:</strong> {{ $seller->gst_no ?? '-' }}</td></tr>

    </table>

    <div class="section-title">Invoice Summary:</div>
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th>HSN/SAC</th>
                <th>Unit Cost</th>
                {{-- <th>Quantity</th> --}}
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Shipping Charges for the month of {{ $month }}</td>
                <td>996719</td>
                <td class="text-right">{{ number_format($monthlySellerAmount, 2) }}</td>
                {{-- <td class="text-right">1</td> --}}
                <td class="text-right">{{ number_format($monthlySellerAmount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="info-table" width="100%" style="margin-top: 20px;">
        <tr><td><strong>Subtotal:</strong></td><td class="text-right">{{ number_format($monthlySellerAmount, 2) }}</td></tr>

        @php $totalTax = 0; @endphp

        @if($sellerstatename === 'Delhi')
            <tr><td><strong>CGST (9%):</strong></td><td class="text-right">{{ number_format($cgst, 2) }}</td></tr>
            <tr><td><strong>SGST (9%):</strong></td><td class="text-right">{{ number_format($sgst, 2) }}</td></tr>
            @php $totalTax = $cgst + $sgst; @endphp
        @else
            <tr><td><strong>IGST (18%):</strong></td><td class="text-right">{{ number_format($gst, 2) }}</td></tr>
            @php $totalTax = $gst; @endphp
        @endif

        <tr><td><strong>Total:</strong></td><td class="text-right">{{ number_format($monthlySellerAmount + $totalTax, 2) }}</td></tr>
        <tr><td><strong>Payable Amount:</strong></td><td class="text-right">{{ number_format($paidAmount, 2) }}</td></tr>
        {{-- <tr><td><strong>Balance Amount:</strong></td><td class="text-right">₹{{ number_format($monthlySellerAmount + $totalTax, 2) }}</td></tr> --}}
    </table>

    <div class="section-title">Bank Details for Payment:</div>
    <div class="bank-details">
        <strong>Account Name:</strong> SHIPXPEED LOGISTICS LLP<br>
        <strong>Account Number:</strong> 2502244266753903<br>
        <strong>Bank:</strong> AU SMALL FINANCE BANK LIMITED<br>
        <strong>Branch:</strong> DELHI YAMUNA VIHAR<br>
        <strong>Account Type:</strong> Current Account<br>
        <strong>IFSC Code:</strong> AUBL0002442
    </div>

    <div class="footer">
        This is a computer-generated invoice and does not require any stamp or signature.
    </div>

</body>
</html>








