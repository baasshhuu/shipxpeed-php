@extends('layouts.app')

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0" data-anchor="data-anchor">Negative Balance Sellers</h5>
                </div>
                <div class="col-auto ms-auto">
                    <!-- Individual row actions only -->
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('negative-balance.index') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Seller</label>
                        <select name="seller" class="form-select">
                            <option value="">All Sellers</option>
                            @foreach($allSellers as $id => $name)
                                <option value="{{ $id }}" {{ request('seller') == $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="{{ route('negative-balance.index') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="card-body border-bottom">
            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>Total Collectable Amount</h5>
                            <h3>₹{{ number_format($totalCollectable, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5>Total Negative Balance</h5>
                            <h3>₹{{ number_format($totalNegativeAmount, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Data Table -->
        <div class="card-body table-padding">
            <div class="table-responsive scrollbar">
                <table class="table custom-table table-striped dt-table-hover fs--1 mb-0" style="width:100%">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th>Seller Name</th>
                            <th>Seller Email</th>
                            <th>COD Orders Count</th>
                            <th>Collectable Amount</th>
                            <th>Current Wallet Balance</th>
                            <th>Negative Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sellerData as $data)
                            <tr>
                                <td>
                                    <strong>{{ $data['seller']->name ?? 'N/A' }}</strong>
                                </td>
                                <td>{{ $data['seller']->email ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $data['orders_count'] }}</span>
                                </td>
                                <td>
                                    <strong class="text-success">
                                        ₹{{ number_format($data['collectable_amount'], 2) }}
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge {{ $data['wallet_balance'] < 0 ? 'bg-danger' : 'bg-success' }}">
                                        ₹{{ number_format($data['wallet_balance'], 2) }}
                                    </span>
                                </td>
                                <td>
                                    @if($data['negative_amount'] > 0)
                                        <strong class="text-danger">
                                            ₹{{ number_format($data['negative_amount'], 2) }}
                                        </strong>
                                    @else
                                        <span class="text-muted">₹0.00</span>
                                    @endif
                                </td>
                                <td>
                                    @if($data['seller']->status == '1')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <!-- Add Money Button -->
                                        <a href="{{ route('negative-balance.add-money') }}?seller_id={{ $data['seller']->id }}" 
                                           class="btn btn-outline-primary btn-sm"
                                           title="Add Money to Wallet">
                                            <i class="fa fa-plus me-1"></i> Add Money
                                        </a>
                                        
                                        <!-- Export Orders Button -->
                                        <a href="{{ route('negative-balance.export-orders') }}?seller_id={{ $data['seller']->id }}" 
                                           class="btn btn-outline-success btn-sm export-direct-btn" 
                                           data-seller-id="{{ $data['seller']->id }}"
                                           data-seller-name="{{ $data['seller']->name }}"
                                           title="Export Orders for {{ $data['seller']->name }}"
                                           {{ $data['orders_count'] == 0 ? 'style=pointer-events:none;opacity:0.5;' : '' }}
                                           target="_blank">
                                            <i class="fa fa-download me-1"></i> Export
                                        </a>
                                        
                                        <!-- Upload Payment File Button -->
                                        <button type="button" class="btn btn-outline-warning btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#uploadModal"
                                                title="Upload Payment File">
                                            <i class="fa fa-upload me-1"></i> Upload
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-search fa-2x mb-3"></i>
                                        <p>No sellers found with negative balance matching the criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($sellerData) && $sellerData->count() > 0)
                <div class="mt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                Showing {{ $sellerData->count() }} sellers with negative balance
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Net Outstanding: ₹{{ number_format($totalCollectable - $totalNegativeAmount, 2) }}
                            </small>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Additional Details Card -->
    @if(isset($sellerData) && $sellerData->count() > 0)
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Summary Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="border-end pe-3">
                            <h6 class="text-muted">Sellers in Negative</h6>
                            <h4 class="text-danger">{{ $sellerData->where('negative_amount', '>', 0)->count() }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border-end pe-3">
                            <h6 class="text-muted">Average Collectable</h6>
                            <h4 class="text-primary">₹{{ number_format($sellerData->avg('collectable_amount'), 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted">Average Negative Amount</h6>
                        <h4 class="text-warning">₹{{ number_format($sellerData->where('negative_amount', '>', 0)->avg('negative_amount'), 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Seller Orders</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('negative-balance.export-orders') }}" method="GET" id="exportForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Seller</label>
                            <select name="seller_id" class="form-select" required id="exportSellerId">
                                <option value="">Choose a seller...</option>
                                @foreach($allSellers as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                        </div>
                        <div id="orderPreview" class="mt-3" style="display: none;">
                            <hr>
                            <h6>Preview:</h6>
                            <div id="previewContent"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="exportSubmitBtn">
                            <i class="fa fa-download me-1"></i> Export Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Payment File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('negative-balance.upload-excel') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Excel File</label>
                            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls" required>
                            <small class="text-muted">Upload an Excel file with AWB numbers in the first column</small>
                        </div>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            <strong>Instructions:</strong>
                            <ul class="mb-0">
                                <li>The Excel file should have AWB numbers in the first column</li>
                                <li>Orders matching the AWB numbers will be marked as "Paid"</li>
                                <li>Only COD delivered orders will be processed</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Upload & Process</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .card-body .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .table td, .table th {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 0.8em;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Direct export functionality
    document.querySelectorAll('.export-direct-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const sellerId = this.dataset.sellerId;
            const sellerName = this.dataset.sellerName;
            
            console.log('🔄 Starting direct export for:', sellerName, 'ID:', sellerId);
            
            // Show loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Exporting...';
            this.style.pointerEvents = 'none';
            
            // Reset after 3 seconds
            setTimeout(() => {
                this.innerHTML = originalText;
                this.style.pointerEvents = '';
            }, 3000);
        });
    });

    // Export orders button click (modal version - backup)
    document.querySelectorAll('.export-orders-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const sellerId = this.dataset.sellerId;
            const sellerName = this.dataset.sellerName;
            
            console.log('Export button clicked for seller:', sellerId, sellerName);
            
            // Pre-select the seller in the export modal
            document.getElementById('exportSellerId').value = sellerId;
            
            // Trigger the preview for the selected seller
            document.getElementById('exportSellerId').dispatchEvent(new Event('change'));
            
            // Show export modal
            const exportModal = new bootstrap.Modal(document.getElementById('exportModal'));
            exportModal.show();
        });
    });

    // Handle form submission
    document.getElementById('exportForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission for debugging
        
        const sellerId = document.getElementById('exportSellerId').value;
        
        if (!sellerId) {
            alert('Please select a seller to export orders.');
            return false;
        }
        
        console.log('Attempting to export orders for seller ID:', sellerId);
        
        // Get form data
        const formData = new FormData(this);
        const params = new URLSearchParams(formData);
        
        // Build export URL
        const exportUrl = '{{ route("negative-balance.export-orders") }}?' + params.toString();
        console.log('Export URL:', exportUrl);
        
        // Change button text to show loading
        const submitBtn = document.getElementById('exportSubmitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Exporting...';
        submitBtn.disabled = true;
        
        // Test the route with detailed debugging
        console.log('=== EXPORT DEBUG START ===');
        console.log('Form action attribute:', this.action);
        console.log('Current page URL:', window.location.href);
        console.log('Export URL constructed:', exportUrl);
        
        // First, let's test if the route exists
        fetch(exportUrl, { 
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);
            
            if (response.status === 404) {
                console.error('❌ Route not found (404)');
                alert('Export route not found. Please check if the route "negative-balance.export-orders" is defined in your routes file.');
                return;
            }
            
            if (response.status === 405) {
                console.error('❌ Method not allowed (405)');
                alert('Export route exists but doesn\'t accept GET requests. Check your route definition.');
                return;
            }
            
            if (response.status === 403) {
                console.error('❌ Access forbidden (403)');
                alert('Access denied to export route. Check your middleware permissions.');
                return;
            }
            
            if (response.status === 500) {
                console.error('❌ Server error (500)');
                response.text().then(text => {
                    console.log('Error response:', text);
                    alert('Server error during export. Check browser console and server logs for details.');
                });
                return;
            }
            
            if (response.ok) {
                console.log('✅ Route responded successfully');
                // Check if response is actually an Excel file
                const contentType = response.headers.get('content-type');
                console.log('Content-Type:', contentType);
                
                if (contentType && contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')) {
                    console.log('✅ Response is Excel file, triggering download');
                    // Create a temporary link to trigger download
                    const link = document.createElement('a');
                    link.href = exportUrl;
                    link.download = `negative-balance-orders-${sellerId}-${Date.now()}.xlsx`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    console.log('⚠️ Response is not Excel file, trying window.location');
                    window.location.href = exportUrl;
                }
            } else {
                console.error(`❌ Unexpected response status: ${response.status}`);
                alert(`Export failed with status ${response.status}. Check console for details.`);
            }
        })
        .catch(error => {
            console.error('❌ Network or fetch error:', error);
            console.log('Error name:', error.name);
            console.log('Error message:', error.message);
            
            if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                alert('Network error: Unable to reach the export URL. Check if the server is running and the URL is correct.');
            } else {
                alert(`Export request failed: ${error.message}`);
            }
        })
        .finally(() => {
            console.log('=== EXPORT DEBUG END ===');
        });
        
        // Re-enable button after 3 seconds
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
    });

    // Preview orders when seller is selected
    document.getElementById('exportSellerId').addEventListener('change', function() {
        const sellerId = this.value;
        console.log('Seller changed to:', sellerId);
        
        if (!sellerId) {
            document.getElementById('orderPreview').style.display = 'none';
            return;
        }

        // Show loading state
        document.getElementById('previewContent').innerHTML = `
            <div class="alert alert-light">
                <i class="fa fa-spinner fa-spin"></i> Loading order preview...
            </div>
        `;
        document.getElementById('orderPreview').style.display = 'block';

        // Get date filters from the main form
        const startDate = document.querySelector('input[name="start_date"]').value;
        const endDate = document.querySelector('input[name="end_date"]').value;

        // Construct the URL properly
        const url = '{{ route("negative-balance.get-seller-orders") }}';
        const params = new URLSearchParams({
            seller_id: sellerId,
            ...(startDate && { start_date: startDate }),
            ...(endDate && { end_date: endDate })
        });

        console.log('Fetching preview from:', `${url}?${params}`);

        // Fetch order preview
        fetch(`${url}?${params}`)
            .then(response => {
                console.log('Preview response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Preview data received:', data);
                
                if (data.error) {
                    document.getElementById('previewContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle"></i> ${data.error}
                        </div>
                    `;
                    return;
                }

                const previewHtml = `
                    <div class="alert alert-success">
                        <strong><i class="fa fa-check-circle"></i> Found:</strong> ${data.total_orders} orders<br>
                        <strong>Total Amount:</strong> ₹${parseFloat(data.total_amount || 0).toLocaleString('en-IN')}
                    </div>
                `;
                
                document.getElementById('previewContent').innerHTML = previewHtml;
            })
            .catch(error => {
                console.error('Error fetching preview:', error);
                document.getElementById('previewContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-triangle"></i> Error loading preview: ${error.message}
                    </div>
                `;
            });
    });

    // Clear modal when closed
    document.getElementById('exportModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('exportSellerId').value = '';
        document.getElementById('orderPreview').style.display = 'none';
        document.getElementById('previewContent').innerHTML = '';
        
        // Reset submit button
        const submitBtn = document.getElementById('exportSubmitBtn');
        submitBtn.innerHTML = '<i class="fa fa-download me-1"></i> Export Excel';
        submitBtn.disabled = false;
    });
});
</script>
@endpush
