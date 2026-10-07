@extends('layouts.app')

@section('content')
    <div class="card mb-3" style="padding: 19px 3px;">
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
        <div class="card-body border-bottom" style="background-color: #f8fafc;">
            <form method="GET" action="{{ route('negative-balance.index') }}">
                <div class="d-flex flex-wrap align-items-end gap-3 justify-content-between">

                    <!-- Filter Fields (seller, start & end date, filter/clear) -->
                    <div class="d-flex flex-wrap align-items-end gap-3">
                        <div style="min-width: 190px;">
                            <label class="form-label mb-1">Seller</label>
                            <select name="seller" class="form-select form-select-sm" style="background-color:#f3f6fa;">
                                <option value="">All Sellers</option>
                                @foreach($allSellers as $id => $name)
                                    <option value="{{ $id }}" {{ request('seller') == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="min-width: 150px;">
                            <label class="form-label mb-1">Start Date</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}" style="background-color:#f3f6fa;">
                        </div>
                        <div style="min-width: 150px;">
                            <label class="form-label mb-1">End Date</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}" style="background-color:#f3f6fa;">
                        </div>
                        <div class="d-flex gap-2 align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm" style="background: #4F8CFF; border: none;">
                                <i class="fa fa-filter me-1"></i> Filter
                            </button>
                            <a href="{{ route('negative-balance.index') }}" class="btn btn-light btn-sm border" style="background: #f3f6fa;">
                                Clear
                            </a>
                        </div>
                    </div>

                    <!-- Summary Cards (total collectable + negative) -->
                    <!-- Desktop Normal View -->
                    <div class="d-none d-sm-flex flex-wrap gap-3 align-items-end">
                        <div class="px-4 py-2 rounded" style="background: #e8f0fe; min-width:175px;">
                            <div class="fw-semibold text-primary small mb-1" style="color:#3176d3!important;">
                                Total Collectable Amount
                            </div>
                            <div class="fs-5" style="color: #1864ab;">
                                ₹{{ number_format($totalCollectable, 2) }}
                            </div>
                        </div>
                        <div class="px-4 py-2 rounded" style="background: #ffeaea; min-width:175px;">
                            <div class="fw-semibold text-danger small mb-1" style="color:#cb2232!important;">
                                Total Negative Balance
                            </div>
                            <div class="fs-5" style="color: #cb2232;">
                                ₹{{ number_format($totalNegativeAmount, 2) }}
                            </div>
                        </div>
                    </div>
                    <!-- Mobile summary: in row, smaller font/size for total & collectable -->
                    <div class="d-flex d-sm-none w-100 align-items-center justify-content-between mt-2 mb-1 gap-2">
                        <div class="flex-fill d-flex flex-column align-items-center justify-content-center" style="background: #e8f0fe; border-radius:6px; min-width:0; min-height:36px; padding: 6px 4px;">
                            <div class="mobile-summary-label small fw-semibold text-primary" style="font-size:10px;line-height:1.1;color:#3176d3!important;">Total Collectable</div>
                            <div class="mobile-summary-value" style="color: #1864ab; font-size:12px; font-weight:600; line-height:1;">
                                ₹{{ number_format($totalCollectable, 2) }}
                            </div>
                        </div>
                        <div class="px-1"></div>
                        <div class="flex-fill d-flex flex-column align-items-center justify-content-center" style="background: #ffeaea; border-radius:6px; min-width:0; min-height:36px; padding: 6px 4px;">
                            <div class="mobile-summary-label small fw-semibold text-danger" style="font-size:10px;line-height:1.1;color:#cb2232!important;">Total Negative</div>
                            <div class="mobile-summary-value" style="color: #cb2232; font-size:12px; font-weight:600; line-height:1;">
                                ₹{{ number_format($totalNegativeAmount, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
   
        <!-- Data Table -->
        <style>
            .premium-table thead th {
                font-weight: 600;
                font-size: 14px;
                letter-spacing: 0.01em;
                background: #f5f7fa !important;
                border-bottom: 2px solid #e9ecef !important;
                padding-top: 7px !important;
                padding-bottom: 7px !important;
            }
            .premium-table tbody tr {
                transition: background 0.15s;
                height: 38px;
            }
            .premium-table tr:not(:last-child) {
                border-bottom: 1px solid #edf1f5 !important;
            }
            .premium-table tbody tr:hover {
                background: #f3f6fa !important;
            }
            .premium-table td {
                vertical-align: middle !important;
                font-size: 13px;
                padding-top: 5px !important;
                padding-bottom: 5px !important;
                border-color: #f1f3f7 !important;
            }
            .premium-table .badge, .premium-table strong, .premium-table span {
                font-size: 13px;
            }
            .premium-table .btn-sm {
                padding: 2px 9px;
                font-size: 12px;
                border-radius: 5px;
            }
            .premium-table .btn-outline-primary { border-color: #d0e3ff;}
            .premium-table .btn-outline-primary:hover { background: #eaf1fa; }
            .premium-table .btn-outline-success { border-color: #bee5c3;}
            .premium-table .btn-outline-success:hover { background: #e8faea; }
            .premium-table .btn-outline-warning { border-color: #ffe2b2;}
            .premium-table .btn-outline-warning:hover { background: #fff7e8; }
            .premium-table .text-success { color: #27ae60 !important; }
            .premium-table .text-danger { color: #cb2232 !important; }
            .email-wrap-cell {
                max-width: 180px;
                white-space: normal !important;
                word-break: break-all;
                /* For premium look, let table layout automatic for soft wrap */
            }
            /* Style for mobile summary row (make smaller font for total collectable/negative) */
            @media (max-width: 576px) {
                .mobile-summary-label {
                    font-size:10px!important;
                    line-height:1!important;
                }
                .mobile-summary-value {
                    font-size:12px!important;
                }
            }
        </style>

        <div class="card-body table-padding" style="padding:0;">
            <div class="table-responsive scrollbar" style="padding: 0px 6px;">
                <table class="table premium-table table-hover mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>Seller</th>
                            <th>Email</th>
                            <th class="text-center">Orders</th>
                            <th class="text-end">Collectable</th>
                            <th class="text-end">Wallet Balance</th>
                            <th class="text-end">Negative Amt</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sellerData as $data)
                            <tr>
                                <td class="fw-semibold" style="max-width:160px;">
                                    <span title="{{ $data['seller']->name }}">{{ Str::limit($data['seller']->name ?? 'N/A', 24) }}</span>
                                </td>
                                <td class="email-wrap-cell" style="color:#57606a;" title="{{ $data['seller']->email }}">
                                    {{ $data['seller']->email ?? 'N/A' }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info" style="min-width:38px;">{{ $data['orders_count'] }}</span>
                                </td>
                                <td class="text-end">
                                    <strong class="text-success" style="font-weight:500;">
                                        ₹{{ number_format($data['collectable_amount'], 2) }}
                                    </strong>
                                </td>
                                <td class="text-end">
                                    <span class="badge {{ $data['wallet_balance'] < 0 ? 'bg-danger' : 'bg-success' }}" style="font-weight:500;">
                                        ₹{{ number_format($data['wallet_balance'], 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($data['negative_amount'] > 0)
                                        <strong class="text-danger" style="font-weight:500;">
                                            ₹{{ number_format($data['negative_amount'], 2) }}
                                        </strong>
                                    @else
                                        <span class="text-muted">₹0.00</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($data['seller']->status == '1')
                                        <span class="badge bg-success" style="opacity:0.92;">Active</span>
                                    @else
                                        <span class="badge bg-secondary" style="opacity:0.85;">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end" style="min-width:210px;">
                                    <div class="d-flex gap-1 flex-nowrap justify-content-end">
                                        <a href="{{ route('negative-balance.add-money') }}?seller_id={{ $data['seller']->id }}" 
                                           class="btn btn-outline-primary btn-sm"
                                           title="Add Money to Wallet">
                                            <i class="fa fa-plus me-1"></i> Add
                                        </a>
                                        <a href="{{ route('negative-balance.export-orders') }}?seller_id={{ $data['seller']->id }}" 
                                           class="btn btn-outline-success btn-sm export-direct-btn" 
                                           data-seller-id="{{ $data['seller']->id }}"
                                           data-seller-name="{{ $data['seller']->name }}"
                                           title="Export Orders for {{ $data['seller']->name }}"
                                           {{ $data['orders_count'] == 0 ? 'style=pointer-events:none;opacity:0.5;' : '' }}
                                           target="_blank">
                                            <i class="fa fa-download me-1"></i> Export
                                        </a>
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
                                <td colspan="8" class="text-center py-4" style="height:54px;">
                                    <div class="text-muted">
                                        <i class="fas fa-search fa-2x mb-3"></i>
                                        <p style="margin-bottom:0;">No sellers found with negative balance matching the criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($sellerData) && $sellerData->count() > 0)
                <div class="mt-2" style="padding:0 4px;">
                    <div class="row g-0">
                        <div class="col-md-6">
                            <small class="text-muted">
                                Showing {{ $sellerData->count() }} sellers with negative balance
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Net Outstanding: <span style="color:#007aff;font-weight:600;">
                                 ₹{{ number_format($totalCollectable - $totalNegativeAmount, 2) }}
                                </span>
                            </small>
                        </div>
                    </div>
                </div>
            @endif
        </div>
  
    </div>

    <!-- Enhanced Summary Card: Negative Balance Sellers Metrics -->
    @if(isset($sellerData) && $sellerData->count() > 0)
        <div class="card shadow-sm border-0 my-4" style="background: linear-gradient(100deg, #f8fafc 90%, #f3f6fa 100%);">
            <div class="card-header border-0 bg-white pb-0 d-flex align-items-center">
                <i class="fas fa-chart-bar text-primary me-2" style="font-size: 1.4rem;"></i>
                <h6 class="mb-0 fw-bold text-dark" style="letter-spacing:0.02em;">Summary Information</h6>
            </div>
            <div class="card-body py-3">
                <div class="row g-4">
                    <div class="col-12 col-md-4">
                        <div class="h-100 p-3 rounded-3 bg-white d-flex flex-column align-items-start justify-content-center border shadow-sm hover-shadow transition"
                             style="min-height:100px;">
                            <div class="mb-1 small fw-semibold text-muted d-flex align-items-center">
                                <i class="fas fa-user-times text-danger me-1"></i>
                                Sellers in Negative
                            </div>
                            <div class=" text-danger" style="font-size: 17px;">
                                {{ $sellerData->where('negative_amount', '>', 0)->count() }}
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="h-100 p-3 rounded-3 bg-white d-flex flex-column align-items-start justify-content-center border shadow-sm hover-shadow transition"
                             style="min-height:100px;">
                            <div class="mb-1 small fw-semibold text-muted d-flex align-items-center">
                                <i class="fas fa-wallet text-primary me-1"></i>
                                Average Collectable
                            </div>
                            <div style="color:#3176d3;font-size: 17px;">
                                ₹{{ number_format($sellerData->avg('collectable_amount'), 2) }}
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="h-100 p-3 rounded-3 bg-white d-flex flex-column align-items-start justify-content-center border shadow-sm hover-shadow transition"
                             style="min-height:100px;">
                            <div class="mb-1 small fw-semibold text-muted d-flex align-items-center">
                                <i class="fas fa-arrow-down text-warning me-1"></i>
                                Average Negative Amount
                            </div>
                            <div style="color:#cb2232;font-size: 17px;">
                                ₹{{ number_format($sellerData->where('negative_amount', '>', 0)->avg('negative_amount'), 2) }}
                            </div>
                        </div>
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
