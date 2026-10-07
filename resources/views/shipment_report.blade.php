@extends('layouts.app')

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white border-bottom d-flex flex-column flex-md-row justify-content-between align-items-center">
        <h5 class="mb-2 mb-md-0 fw-bold text-primary">📦 Shipment Report</h5>

        <form method="GET" action="{{ route('shipment.report') }}" class="d-flex flex-wrap gap-2">
            <select name="seller_id" class="form-select form-select-sm" style="min-width: 180px;">
                <option value="">🔍 All Sellers</option>
                @foreach ($sellers as $seller)
                    <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                        {{ $seller->name }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="start_date" class="form-control form-control-sm"
                   value="{{ request('start_date') }}" placeholder="Start Date" />

            <input type="date" name="end_date" class="form-control form-control-sm"
                   value="{{ request('end_date') }}" placeholder="End Date" />

            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            
            <a href="{{ route('shipment.report.export', request()->query()) }}" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>

        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="table-primary text-center text-dark fw-semibold">
                    <tr>
                        <th>AWB Number</th>
                        <th>Seller Name</th>
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
                        <tr class="text-center">
                            <td>{{ $order->awb_number }}</td>
                            <td>{{ $order->seller->name ?? 'N/A' }}</td>
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
