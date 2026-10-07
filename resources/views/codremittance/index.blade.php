

@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">COD Remittance List</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon d-flex gap-2">
                    @if (Helper::userCan(104, 'can_add'))
                        <a href="{{ route('codremittance.create') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-plus me-1"></i> Add COD Remittance
                        </a>
                    @endif

                    @if ($codOrders->count())
                        <a href="{{ route('codremittance.export', request()->all()) }}" class="btn btn-outline-success">
                            <i class="fa fa-file-excel-o me-1"></i> Download Excel
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-body table-padding">

        <!-- 🔍 Filter Form -->
        <form method="GET" action="{{ route('codremittance.index') }}" class="mb-3">
            <div class="row g-3 align-items-end">

                <!-- Seller Dropdown -->
                <div class="col-md-4">
                    <label for="seller" class="form-label">Seller</label>
                    <select class="form-select" name="seller" id="seller">
                        <option value="">All Sellers</option>
                        @foreach($sellers as $id => $name)
                            <option value="{{ $id }}" {{ request('seller') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Start Date -->
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>

                <!-- End Date -->
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>

                <!-- Submit -->
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Filter</button>
                </div>

            </div>
        </form>

        <!-- 📄 Table -->
        <div class="table-responsive scrollbar mt-3">
            <table class="table custom-table table-striped dt-table-hover fs--1 mb-0 table-datatable" style="width:100%">
                <thead class="bg-200 text-900">
                    <tr>
                        <th>Order Number</th>
                        <th>Status</th>
                        <th>Collectable Amount</th>
                        <th>Courier</th>
                        <th>AWB Number</th>
                        <th>Payment Status</th>
                        <th>Delivered Date</th>
                        <th>Remittance Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($codOrders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ ucfirst($order->shipping_status) }}</td>
                            <td>{{ $order->collectable_amount }}</td>
                            <td>{{ $order->courier_id }}</td>
                            <td>{{ $order->awb_number }}</td>
                            <td>{{ $order->payment_status ?? 'N/A' }}</td>
                            <td>{{ $order->delivered_date ?? 'N/A' }}</td>
                            <td>
                                @if($order->delivered_date)
                                    {{ \Carbon\Carbon::parse($order->delivered_date)->addDays(7)->format('Y-m-d') }}
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No COD orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 📄 Pagination -->
        <div class="mt-3 d-flex justify-content-center">
            {{ $codOrders->appends(request()->all())->links() }}
        </div>
    </div>
</div>
@endsection
