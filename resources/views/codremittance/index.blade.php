
@extends('layouts.app')

@section('content')
    <style>
        @media (max-width: 576px) {
            .cod-header-flex {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                width: 100% !important;
                gap: 0;
            }
            .cod-header-title {
                flex-shrink: 1;
                font-size: 1.1rem;
                margin-bottom: 0 !important;
                white-space: nowrap;
            }
            .cod-actions-buttons {
                display: flex !important;
                flex-direction: row;
                gap: 0.5rem !important;
                margin-left: 0.7rem;
                flex-shrink: 0;
            }
            .cod-actions-buttons .btn {
                font-size: 1.1rem !important;
                padding: 0.30rem 0.65rem !important;
                min-width: 38px;
                min-height: 34px;
            }
            .cod-actions-buttons .btn span {
                display: none !important;
            }
            .cod-actions-buttons .btn i {
                margin-right: 0 !important;
                font-size: 1.2em !important;
            }
        }
        @media (min-width: 577px) {
            .cod-header-flex {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 0;
            }
            .cod-header-title {
                font-size: 1.25rem;
                margin-bottom: 0 !important;
            }
            .cod-actions-buttons {
                display: flex !important;
                flex-direction: row;
                gap: 0.6rem;
            }
            .cod-actions-buttons .btn span {
                display: inline !important;
            }
        }
    </style>
    <div class="card mb-3" style="padding: 19px 6px;">
        <div class="card-header">
            <div class="cod-header-flex">
                <div class="cod-header-title">
                    <h5 class="mb-0" style="font-size:inherit;">COD Remittance List</h5>
                </div>
                <div class="cod-actions-buttons">
                    @if (Helper::userCan(104, 'can_add'))
                        <a href="{{ route('codremittance.create') }}" class="btn btn-outline-secondary" title="Add COD Remittance">
                            <i class="fa fa-plus"></i> <span>Add COD Remittance</span>
                        </a>
                    @endif

                    @if ($codOrders->count())
                        <a href="{{ route('codremittance.export', request()->all()) }}" class="btn btn-outline-success" title="Download Excel">
                            <i class="fa-solid fa-download"></i> <span>Download Excel</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body table-padding">

            <!-- 🔍 Filter Form -->
            <form method="GET" action="{{ route('codremittance.index') }}" class="mb-3">
                <div class="row g-3 align-items-end" style="margin: 12px;">

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
                        <button style="height: 45px;" class="btn btn-primary w-100" type="submit">Filter</button>
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
