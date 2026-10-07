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
            <td><strong>Invoice Number:</strong> {{ $invoiceNumber }}</td>
            <td><strong>Invoice Date:</strong> {{ $invoiceDate }}</td>
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



{{-- <!DOCTYPE html>
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
     
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('1745560772_5971.png'))) }}" alt="Company Logo" class="logo">


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
            <td><strong>Invoice Month:</strong> {{ $month }}</td>
            <td><strong>Invoice Date:</strong> {{ \Carbon\Carbon::now()->format('d-m-Y') }}</td>
        </tr>
    </table>

    <div class="section-title">Customer Details:</div>
    <table class="info-table" width="100%">
        <tr><td><strong>Customer Name:</strong> {{ $seller->name }}</td></tr>
        <tr><td><strong>Address:</strong> {{ $sellerAddress->address_line ?? 'E-7, 3rd Floor, Office No. 302, Near Hira Sweets, Laxmi Nagar, Delhi – 110092' }}</td></tr>
        <tr><td><strong>Email:</strong> {{ $seller->email }}</td></tr>
        <tr><td><strong>Phone:</strong> {{ $seller->phone ?? '-' }}</td></tr>
    </table>

    <div class="section-title">Invoice Summary:</div>
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th>HSN/SAC</th>
                <th>Unit Cost</th>
                <th>Quantity</th>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Shipping Charges for the month of {{ $month }}</td>
                <td>996719</td>
                <td class="text-right">{{ number_format($monthlySellerAmount, 2) }}</td>
                <td class="text-right">1</td>
                <td class="text-right">{{ number_format($monthlySellerAmount, 2) }}</td>
            </tr>
        </tbody>
    </table> 

    <table class="info-table" width="100%" style="margin-top: 20px;">
        <tr><td><strong>Subtotal:</strong></td><td class="text-right">₹{{ number_format($monthlySellerAmount, 2) }}</td></tr>
        @if($sellerstatename === 'Delhi')
        <tr><td><strong>CGST (9%):</strong></td><td class="text-right">₹{{ number_format($cgst, 2) }}</td></tr>
        <tr><td><strong>SGST (9%):</strong></td><td class="text-right">₹{{ number_format($sgst, 2) }}</td></tr>
        @else
        <tr><td><strong>GST (18%):</strong></td><td class="text-right">₹{{ number_format($gst, 2) }}</td></tr>
        @endif
        <tr><td><strong>Total:</strong></td><td class="text-right">₹{{ number_format($monthlySellerAmount + $gst, 2) }}</td></tr>
        <tr><td><strong>Paid Amount:</strong></td><td class="text-right">₹0.00</td></tr>
        <tr><td><strong>Balance Amount:</strong></td><td class="text-right">₹{{ number_format($monthlySellerAmount + $gst, 2) }}</td></tr>
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
</html> --}}





{{-- <!DOCTYPE html>
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
            max-height: 50px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
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
            <img src="{{ asset('1745560772_5971.png') }}" alt="Company Logo" class="logo">
        </div>
        <div class="company-name">
            Shipxpeed Limited <br>
            <span style="font-weight: normal;">(Previously Known as shipxpeed Pvt. Ltd)</span>
            <br><a href="https://shipxpeed.com/" target="_blank">https://shipxpeed.com</a>
        </div>
    </div>

    <h2 style="margin-top: 15px;">TAX Invoice</h2>

    <table class="info-table" width="100%">
        <tr>
            <td><strong>PAN:</strong> AAPCS9575E</td>
            <td><strong>CIN:</strong> L63090DL2011PLC221234</td>
            <td><strong>Original for Recipient</strong></td>
        </tr>
    </table>

    <div class="section-title">Customer Details:</div>
    <table class="info-table" width="100%">
        <tr><td><strong>Customer Name:</strong> ShipXpeed</td></tr>
        <tr><td><strong>Address:</strong> E-7, 3rd floor, Office No - 302, NA Delhi - 110092</td></tr>
        <tr><td><strong>IRN#:</strong> null</td></tr>
    </table>

    <div class="section-title">Delhivery Address:</div>
    <p>
        Khasra No 57//23 Min 631/3 8 9 10/1 11-23<br>
        641/17 80/15 81/1 2/1 2/2 3/1 Village Patheri,<br>
        Gurgaon, Haryana 123413
    </p>

    <table class="info-table" width="100%">
        <tr>
            <td><strong>Invoice Number:</strong> BPE2572056</td>
            <td><strong>Invoice Date:</strong> 31-05-2025</td>
        </tr>
    </table>

    <div class="section-title">Invoice Summary:</div>
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th>HSN/SAC</th>
                <th>Unit Cost</th>
                <th>Quantity</th>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>B2B Services for 16 May'25 to 31 May'25 - SHIPXPEED 3645 B2B - DELHI</td>
                <td>996719</td>
                <td class="text-right">17092.05</td>
                <td class="text-right">1</td>
                <td class="text-right">17092.05</td>
            </tr>
        </tbody>
    </table>

    <table class="info-table" width="100%" style="margin-top: 20px;">
        <tr><td><strong>Subtotal:</strong></td><td class="text-right">₹17,092.05</td></tr>
        <tr><td><strong>IGST @18%:</strong></td><td class="text-right">₹3,076.57</td></tr>
        <tr><td><strong>Total (INR):</strong></td><td class="text-right">₹20,168.62</td></tr>
        <tr><td><strong>Paid Amount:</strong></td><td class="text-right">₹0.00</td></tr>
        <tr><td><strong>Balance Amount:</strong></td><td class="text-right">₹20,168.62</td></tr>
    </table>

    <div class="section-title">Terms:</div>
    <p>Please make all cheques/DD payable to M/s Delhivery Limited (Previously Known as Delhivery Pvt. Ltd).</p>

    <div class="bank-details">
        <strong>Remittance to be made to:</strong><br>
        A/C Holder Name: M/s Delhivery Limited<br>
        A/C Number: 12022320000801<br>
        IFSC Code: HDFC0001202
    </div>

    <div class="footer">
        This is a computer-generated invoice and does not require any stamp or signature.
    </div>

    <div class="bank-details" style="margin-top: 10px;">
        Service for ShipXpeed to GST ID: null from Delhivery GST ID: 06AAPCS9575E1ZR<br>
        SAC Code: 996719, STATE CODE: 7, PLACE OF SUPPLY: DELHI
    </div>

</body>
</html>
 --}}









{{-- <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $month }} Seller Invoice</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 0; }
        .info-box { margin-bottom: 20px; }
        table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        th, td {
            padding: 10px; border: 1px solid #ddd; text-align: left;
        }
        .total { font-weight: bold; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

<div class="header">
    <h2>Monthly Invoice</h2>
    <p>{{ $month }}</p>
</div>

<div class="info-box">
    <strong>Seller Name:</strong> {{ $seller->name ?? '-' }}<br>
    <strong>Email:</strong> {{ $seller->email ?? '-' }}<br>
    <strong>Phone:</strong> {{ $seller->phone ?? '-' }}<br>
</div>

<table>
    <thead>
        <tr>
            <th>Description</th>
            <th class="text-right">Amount (₹)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Total Deduction</td>
            <td class="text-right">{{ number_format($monthlySellerAmount, 2) }}</td>
        </tr>
        <tr>
            <td>GST (18%)</td>
            <td class="text-right">{{ number_format($gst, 2) }}</td>
        </tr>
        <tr class="total">
            <td>Amount After GST</td>
            <td class="text-right">{{ number_format($finalAmount, 2) }}</td>
        </tr>
    </tbody>
</table>

<p style="margin-top: 50px;">Thank you for using our service!</p>

</body>
</html> --}}
