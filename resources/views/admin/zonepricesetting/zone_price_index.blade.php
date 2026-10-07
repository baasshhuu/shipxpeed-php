@extends('layouts.app')

@section('content')
<div class="card mb-3" style="margin: 39px 3px;">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Zone Price Settings</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('zone.pricesetting.add') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Zone Price
                    </a>
                </div>
            </div>
        </div> 
    </div>
    <div class="card-body">
        <!-- Seller Filter Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0" style="background: linear-gradient(98deg,#f7fafc 60%,#e6f0fa 100%); border-radius: 14px;">
                    <div class="card-body py-3 px-4">
                        <form method="GET" action="{{ route('zone.price.index') }}" class="row g-2 align-items-end">
                            <div class="col-md-6 col-lg-8">
                                <label class="form-label fw-semibold mb-1" style="color:#1b2836;font-size:14px;" for="seller_id">
                                    <i class="fas fa-store-alt me-1 text-primary"></i> Filter by Seller
                                </label>
                                <select class="form-select form-select-sm border-1" id="seller_id" name="seller_id" style="border-radius: 7px; min-height: 34px; font-size: 14px; padding-top: 2px; padding-bottom: 2px;">
                                    <option value="" class="text-muted">-- All Sellers --</option>
                                    @foreach(\App\Models\SellerList::all() as $seller)
                                        <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                                            {{ $seller->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-4 d-flex gap-2 justify-content-end">
                                <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm" style="border-radius: 7px; min-height:32px; font-size:14px;">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                @if(request('seller_id'))
                                <a href="{{ route('zone.price.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" style="border-radius: 7px; min-height:32px; font-size:14px;">
                                    <i class="fas fa-times me-1"></i> Clear
                                </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if(request('seller_id'))
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info d-flex align-items-center shadow-sm" style="border-radius:9px;background: linear-gradient(90deg, #f4faff, #d9eefd 80%); border-left: 5px solid #57a3e8; min-height:36px; font-size:14px;">
                    <i class="fas fa-info-circle fs-6 me-2 text-primary"></i>
                    <div>
                        <span class="fw-semibold" style="color: #22587c;">
                            Showing zone prices for: 
                            <strong>{{ \App\Models\SellerList::find(request('seller_id'))->name ?? 'Unknown Seller' }}</strong>
                        </span>
                        <span class="ms-2 text-muted">
                            ({{ $zonePrices->total() }} record{{ $zonePrices->total()!=1 ? 's' : '' }} found)
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endif
   
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="zonePriceTable">
                <thead class="table-dark" style="text-align:center;">
                    <tr>
                        <th>ID</th>
                        <th>Seller</th>
                        <th>Zone</th>
                        <th>Logistic Provider</th>
                        <th>COD Price</th>
                        <th>COD Fixed</th>
                        <th>Prepaid Price</th>
                        <th>Prepaid Fixed</th>
                        <th>COD Charge %</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody style="text-align:center;">
                    @forelse($zonePrices as $price)
                    <tr>
                        <td>{{ $price->id }}</td>
                        <td>{{ $price->seller->name ?? 'N/A' }}</td>
                        <td>{{ $price->zone }}</td>
                        <td>{{ $price->LogisticProvider ?? 'N/A' }}</td>
                        <td>₹{{ number_format($price->cod_price, 2) }}</td>
                        <td>₹{{ number_format($price->cod_fix_price, 2) }}</td>
                        <td>₹{{ number_format($price->prepaid_price, 2) }}</td>
                        <td>₹{{ number_format($price->prepaid_fix_price, 2) }}</td>
                        <td>{{ $price->cod_charge_parsent }}%</td>
                        <td>
                            <span class="badge {{ $price->status ? 'bg-success' : 'bg-danger' }}">
                                {{ $price->status ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('zone.pricesetting.edit', $price->id) }}" 
                                   class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="confirmDelete({{ $price->id }})" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center">No zone price settings found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-center">
            {{ $zonePrices->links() }}
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        if(confirm('Are you sure you want to delete this zone price setting?')) {
            window.location.href = "{{ url('admin/zone-price-setting/delete') }}/" + id;
        }
    }
</script>
@endsection
