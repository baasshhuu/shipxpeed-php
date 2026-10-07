@extends('layouts.sellerdash')

@section('content')
    <div class="pc-container">
        <div class="pc-content">
            <div class="container mt-4 bg-white p-4 shadow rounded" style="max-width: 900px;">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4>Zylker Electronics Hub</h4>
                        <p>
                            14B, Northern Street<br>
                            Greater South Avenue<br>
                            New York, NY 10001<br>
                            U.S.A
                        </p>
                    </div>
                    <div class="text-end">
                        <h1 class="text-primary">INVOICE</h1>
                        <p><strong>Invoice#:</strong> INV-000001</p>
                        <p><strong>Invoice Date:</strong> {{ now()->format('d M Y') }}</p>
                        <p><strong>Due Date:</strong> {{ now()->format('d M Y') }}</p>
                    </div>
                </div>
                <hr>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6><strong>Bill To</strong></h6>
                        <p>
                            Ms. Mary D. Dunton<br>
                            1324 Hinkle Lake Road<br>
                            Needham, 02192 Maine
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6><strong>Ship To</strong></h6>
                        <p>
                            1324 Hinkle Lake Road<br>
                            Needham, 02192 Maine
                        </p>
                    </div>
                </div>

                @if (!empty($charges))
                    <table class="table table-bordered">
                        <thead class="table-primary">
                            <tr>
                                <th>#</th>
                                <th>Item & Description</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Rate</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Charge RTO<br><small>Return to origin cost</small></td>
                                <td class="text-end">1</td>
                                <td class="text-end">₹{{ number_format($charges['charge_RTO'] ?? 0, 2) }}</td>
                                <td class="text-end">₹{{ number_format($charges['charge_RTO'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Delivery Charge<br><small>Charge DL</small></td>
                                <td class="text-end">1</td>
                                <td class="text-end">₹{{ number_format($charges['charge_DL'] ?? 0, 2) }}</td>
                                <td class="text-end">₹{{ number_format($charges['charge_DL'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>DPH Charge<br><small>Delivery Process Handling</small></td>
                                <td class="text-end">1</td>
                                <td class="text-end">₹{{ number_format($charges['charge_DPH'] ?? 0, 2) }}</td>
                                <td class="text-end">₹{{ number_format($charges['charge_DPH'] ?? 0, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Gross Amount</th>
                                <th class="text-end">₹{{ number_format($charges['gross_amount'] ?? 0, 2) }}</th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">CGST</th>
                                <th class="text-end">₹{{ number_format($charges['tax_data']['CGST'] ?? 0, 2) }}</th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">SGST</th>
                                <th class="text-end">₹{{ number_format($charges['tax_data']['SGST'] ?? 0, 2) }}</th>
                            </tr>
                            <tr class="table-primary">
                                <th colspan="4" class="text-end">Total Amount</th>
                                <th class="text-end">
                                    <strong>₹{{ number_format($charges['total_amount'] ?? 0, 2) }}</strong></th>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="mt-4">
                        <p><strong>Zone:</strong> {{ $charges['zone'] ?? '-' }}</p>
                        <p><strong>Charged Weight:</strong> {{ $charges['charged_weight'] ?? '-' }} gms</p>
                    </div>
                @else
                    <p class="text-danger">No charge data found.</p>
                @endif

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Thanks for shopping with us.</strong></p>
                        <p><strong>Terms & Conditions:</strong><br>
                            Full payment is due upon receipt. Late payments may incur additional charges or interest.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
