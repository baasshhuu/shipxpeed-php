@extends('layouts.app')

@section('content')
<div class="card mb-3">
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
            <div class="col-md-12">
                <form method="GET" action="{{ route('zone.price.index') }}" class="d-flex align-items-end gap-3">
                    <div class="flex-grow-1">
                        <label class="form-label" for="seller_id">Filter by Seller</label>
                        <select class="form-control" id="seller_id" name="seller_id">
                            <option value="">-- All Sellers --</option>
                            @foreach(\App\Models\SellerList::all() as $seller)
                                <option value="{{ $seller->id }}" {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                                    {{ $seller->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        @if(request('seller_id'))
                        <a href="{{ route('zone.price.index') }}" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        
        @if(request('seller_id'))
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            Showing zone prices for: <strong>{{ \App\Models\SellerList::find(request('seller_id'))->name ?? 'Unknown Seller' }}</strong>
            ({{ $zonePrices->total() }} records found)
        </div>
        @endif
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="zonePriceTable">
                <thead class="table-dark">
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
                <tbody>
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
