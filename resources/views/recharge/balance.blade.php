

@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="text-dark mb-1">Seller Wallet Balance</h2>
                        <p class="text-muted mb-0">Manage and monitor seller wallet balances</p>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-info fs-6 px-3 py-2">
                            Total Sellers: {{ $brands->total() ?? count($brands) }}
                        </span>
                        <a href="{{ route('recharge.export') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-download me-1"></i>Export Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light border-0 py-3">
                <h6 class="card-title mb-0 text-dark">
                    <i class="fas fa-filter me-2"></i>Filter Options
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('balance') }}" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">Search by Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   placeholder="Enter seller name..."
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">Select Seller</label>
                        <select name="seller_id" class="form-select">
                            <option value="">All Sellers</option>
                            @foreach($allSellers ?? [] as $seller)
                                <option value="{{ $seller->id }}" 
                                        {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                                    {{ $seller->name }} (ID: {{ $seller->seller_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">Balance Range</label>
                        <div class="d-flex gap-2">
                            <input type="number" name="min_balance" class="form-control" 
                                   placeholder="Min" value="{{ request('min_balance') }}" step="0.01">
                            <input type="number" name="max_balance" class="form-control" 
                                   placeholder="Max" value="{{ request('max_balance') }}" step="0.01">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Apply Filters
                            </button>
                            <a href="{{ route('balance') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
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
                                            <div class="avatar avatar-sm me-2">
                                                <div class="avatar-name rounded-circle bg-info text-white d-flex align-items-center justify-content-center">
                                                    {{ substr($brand->seller->name ?? 'N', 0, 1) }}
                                                </div>
                                            </div>
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
