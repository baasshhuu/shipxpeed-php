

@extends('layouts.app')

@section('content')
    <div class="container-fluid" style="padding: 19px 3px;">
        <!-- Header Section -->
        <div class="row mb-4" >
            <div class="col-12" style="margin-top: 12px;margin-left:12px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="text-dark mb-1" style="font-size:25px;">Seller Wallet Balance</h2>
                        <p class="text-muted mb-0">Manage and monitor seller wallet balances</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3" style="margin-right: 18px;">
                        <span class="badge fs-6 px-4 py-2 shadow-sm d-flex align-items-center"
                              style="background: linear-gradient(90deg, #e3f0ff 0%, #b3d8fd 100%); color: #1754a1; font-size: 1rem; height: 44px;">
                            <i class="fas fa-users me-2" style="color: #1754a1;"></i>
                            <span style="font-weight: 600;">Total Sellers: {{ $brands->total() ?? count($brands) }}</span>
                        </span>
                        <a href="{{ route('recharge.export') }}"
                           class="btn d-flex align-items-center px-4 py-2 shadow-sm"
                           style="background: linear-gradient(90deg, #e3f0ff 0%, #b3d8fd 100%); color: #1754a1; border: none; height: 44px; font-size: 1rem; font-weight: 500; border-radius: 7px;">
                            <i class="fas fa-download me-2"></i> Export Excel
                        </a>
                    </div>
               
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card shadow-lg rounded-4 mb-4 border-0" style="background: linear-gradient(105deg, #f3f8fd 85%, #ddebf8 100%);">
            <div class="card-header bg-white border-0 py-4 rounded-top-4 px-4 d-flex align-items-center" style="border-bottom: 2px solid #e3f1fa;">
                <h5 class="card-title mb-0 text-dark fw-semibold d-flex align-items-center gap-2" style="font-size: 1.28rem;">
                    <span><i class="fas fa-sliders-h fa-fw" style="color: #2563eb; font-size: 1.1em;"></i></span>
                    <span>Filter Wallets</span>
                </h5>
            </div>
            <div class="card-body px-4 pb-4 pt-4">
                <form method="GET" action="{{ route('balance') }}">
                    <div class="row g-2 align-items-end flex-nowrap flex-md-wrap">
                        <div class="col-lg-3 col-md-4 col-12 mb-2 mb-lg-0">
                            <label class="form-label text-secondary fw-medium mb-1">Search by Name</label>
                            <div class="input-group border rounded-3 shadow-sm overflow-hidden flex-nowrap">
                                <span class="input-group-text bg-white border-0">
                                    <i class="fas fa-search text-primary"></i>
                                </span>
                                <input 
                                    type="text"
                                    name="search"
                                    class="form-control border-0 bg-light"
                                    style="color: #32475b; font-weight: 500;"
                                    placeholder="Type seller name"
                                    value="{{ request('search') }}"
                                    autocomplete="off"
                                    aria-label="Search by seller name"
                                >
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-4 col-12 mb-2 mb-lg-0">
                            <label class="form-label text-secondary fw-medium mb-1">Select Seller</label>
                            <select 
                                name="seller_id"
                                class="form-select bg-light border-0 fw-semibold shadow-sm rounded-3"
                                style="color: #1754a1;"
                                aria-label="Seller select"
                            >
                                <option value="">All Sellers</option>
                                @foreach($allSellers ?? [] as $seller)
                                    <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                                        {{ $seller->name }} <small>(ID: {{ $seller->seller_id }})</small>
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-4 col-12 mb-2 mb-lg-0">
                            <label class="form-label text-secondary fw-medium mb-1">Balance Range</label>
                            <div class="input-group gap-2 flex-nowrap shadow-sm rounded-3">
                                <input 
                                    type="number"
                                    name="min_balance"
                                    class="form-control border-0 bg-light fw-semibold"
                                    placeholder="Min ₹"
                                    value="{{ request('min_balance') }}"
                                    min="0"
                                    step="0.01"
                                    style="max-width: 90px;"
                                >
                                <span class="input-group-text bg-white border-0 px-2 text-muted fs-6 fw-semibold">-</span>
                                <input 
                                    type="number"
                                    name="max_balance"
                                    class="form-control border-0 bg-light fw-semibold"
                                    placeholder="Max ₹"
                                    value="{{ request('max_balance') }}"
                                    min="0"
                                    step="0.01"
                                    style="max-width: 90px;"
                                >
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-12 col-12 d-flex align-items-end gap-2 justify-content-lg-end justify-content-md-start justify-content-start mt-2 mt-lg-0">
                            <button type="submit" class="btn btn-gradient-primary rounded-3 px-4 py-2 fw-semibold shadow-sm d-flex align-items-center"
                                    style="background: linear-gradient(90deg, #2563eb 0%, #1cb5e0 100%); border: none; color: #fff;">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                            <a href="{{ route('balance') }}" class="btn btn-outline-secondary rounded-3 px-4 py-2 fw-semibold shadow-sm d-flex align-items-center">
                                <i class="fas fa-broom me-2"></i>Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
   

        <!-- Data Table Card -->
        <div class="card shadow-sm">
            <div class="card-header bg-light border-0 py-3">
                <h6 class="card-title mb-0 text-dark">
                    <i class="fas fa-table me-2"></i>Seller Wallet Balances
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-center">#</th>
                                <th>Seller ID</th>
                                <th>Client Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th class="text-end">Wallet Balance</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($brands as $index => $brand)
                                <tr>
                                    <td class="text-center text-muted">
                                        {{ ($brands->currentPage() - 1) * $brands->perPage() + $index + 1 }}
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $brand->seller_id }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <!-- <div class="avatar avatar-sm me-2">
                                                <div class="avatar-name rounded-circle bg-info text-white d-flex align-items-center justify-content-center">
                                                    {{ substr($brand->seller->name ?? 'N', 0, 1) }}
                                                </div>
                                            </div> -->
                                            <span class="fw-semibold">{{ $brand->seller->name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($brand->seller->phone_number)
                                            <i class="fas fa-phone text-muted me-1"></i>
                                            {{ $brand->seller->phone_number }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($brand->seller->email)
                                            <i class="fas fa-envelope text-muted me-1"></i>
                                            {{ $brand->seller->email }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success fs-6">
                                            ${{ number_format($brand->wallet_balance, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-1"></i>View Details</a></li>
                                                <li><a class="dropdown-item" href="#"><i class="fas fa-plus me-1"></i>Add Balance</a></li>
                                                <li><a class="dropdown-item" href="#"><i class="fas fa-history me-1"></i>Transaction History</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-search fa-3x mb-3"></i>
                                            <h5>No sellers found</h5>
                                            <p>Try adjusting your search criteria or add some sellers.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            @if($brands instanceof \Illuminate\Pagination\LengthAwarePaginator && $brands->hasPages())
                <div class="card-footer bg-light border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $brands->firstItem() ?? 0 }} to {{ $brands->lastItem() ?? 0 }} 
                            of {{ $brands->total() }} results
                        </div>
                        <div>
                            {{ $brands->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Custom Styles -->
    <style>
        .avatar-sm {
            width: 32px;
            height: 32px;
        }
        
        .avatar-name {
            width: 32px;
            height: 32px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .dropdown-toggle::after {
            display: none;
        }
        
        .badge {
            font-size: 0.75rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .table th {
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.025em;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table td {
            vertical-align: middle;
            font-size: 0.875rem;
        }
        
        .btn {
            font-weight: 500;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-color: #ced4da;
        }
    </style>
@endsection
