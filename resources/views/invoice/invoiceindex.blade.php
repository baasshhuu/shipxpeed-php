

@extends('layouts.app')

@section('content')
<style>
    .card {
        border: none;
    }
    .table thead th {
        background-color: #343a40;
        color: white;
    }
    .seller-card {
        margin-bottom: 30px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
    }
    .seller-header {
        background-color: #f8f9fa;
        padding: 15px;
        border-bottom: 1px solid #dee2e6;
    }
    
    /* Custom button styling */
    .card-header .btn {
        font-size: 14px;
        padding: 8px 16px;
        border-radius: 5px;
        font-weight: 500;
    }
    
    .card-header .d-flex.gap-2 {
        gap: 10px !important;
    }
    
    /* Ensure buttons are properly aligned */
    .card-header .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Select all section styling */
    .bg-light {
        border: 1px solid #e9ecef;
    }
    
    .badge.bg-secondary {
        font-size: 12px;
    }
</style>

<div class="container my-5">
    <div class="card shadow-lg rounded-lg">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">{{ $month }} - All Sellers Monthly Statements</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('all.seller.invoice.bulk.download', ['month' => $month]) }}" 
                   class="btn btn-success">
                    <i class="bi bi-download"></i> Download All Invoices (ZIP)
                </a>
                <button type="button" id="downloadSelected" class="btn btn-warning" disabled>
                    <i class="bi bi-check-square"></i> Download Selected
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Select All Checkbox -->
            <div class="mb-4 p-3 bg-light rounded border">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-check-label d-flex align-items-center mb-0">
                        <input type="checkbox" id="selectAll" class="form-check-input me-2">
                        <strong>Select All (Current Page)</strong>
                    </label>
                    <div class="d-flex align-items-center gap-2">
                        <span id="selectedCount" class="badge bg-secondary">0 selected</span>
                        <button type="button" id="clearAllSelections" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x-circle"></i> Clear All
                        </button>
                    </div>
                </div>
            </div>
            @foreach($sellers as $seller)
            <div class="seller-card">
                <div class="seller-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">{{ $seller->name }} (ID: {{ $seller->id }})</h5>
                        <p class="mb-0 text-muted">Wallet Balance: ₹{{ number_format($seller->totalAmount, 2) }}</p>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input seller-checkbox" 
                               id="seller_{{ $seller->id }}" value="{{ $seller->id }}" 
                               data-name="{{ $seller->name }}">
                        <label class="form-check-label" for="seller_{{ $seller->id }}">
                            Select
                        </label>
                    </div>
                </div>
                <div class="seller-body p-3">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped text-center">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Total Deduction</th>
                                    <th>GST (18%)</th>
                                    <th>Amount After GST</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>₹{{ number_format($seller->monthlyAmount, 2) }}</td>
                                    <td>₹{{ number_format($seller->gst, 2) }}</td>
                                    <td>₹{{ number_format($seller->finalAmount, 2) }}</td>
                                    <td>
                                        <a href="{{ route('admin.seller.invoice.pdf', ['month' => $month, 'seller_id' => $seller->id]) }}" 
                                           class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach

               <div class="d-flex justify-content-center mt-4">
                {{ $sellers->links() }}
            </div>
            
            <!-- Additional Bulk Download Section -->
            <div class="text-center mt-4 p-3 bg-light rounded">
                <h5>Bulk Download Option</h5>
                <p class="text-muted">Download all seller invoices for {{ $month }} as a single ZIP file</p>
                <a href="{{ route('all.seller.invoice.bulk.download', ['month' => $month]) }}" 
                   class="btn btn-lg btn-success"
                   onclick="this.innerHTML='<i class=\'bi bi-spinner-border\'></i> Preparing ZIP File...'; this.disabled=true;">
                    <i class="bi bi-cloud-download"></i> Download All as ZIP
                </a>
                <br>
                <small class="text-muted mt-2 d-block">
                    <i class="bi bi-info-circle"></i> This will include invoices for all sellers shown above
                </small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const sellerCheckboxes = document.querySelectorAll('.seller-checkbox');
    const downloadSelectedBtn = document.getElementById('downloadSelected');
    const selectedCountBadge = document.getElementById('selectedCount');
    const clearAllBtn = document.getElementById('clearAllSelections');
    const month = '{{ $month }}';
    const storageKey = 'selected_sellers_' + month.replace(/\s+/g, '_');
    
    // Load selected sellers from localStorage
    let selectedSellers = JSON.parse(localStorage.getItem(storageKey) || '{}');
    
    // Update selected count and button state
    function updateSelection() {
        const currentPageSelected = document.querySelectorAll('.seller-checkbox:checked').length;
        const totalSelected = Object.keys(selectedSellers).length;
        
        selectedCountBadge.textContent = totalSelected + ' selected';
        downloadSelectedBtn.disabled = totalSelected === 0;
        
        // Update select all checkbox for current page only
        const currentPageCheckboxes = document.querySelectorAll('.seller-checkbox');
        const currentPageChecked = document.querySelectorAll('.seller-checkbox:checked');
        
        if (currentPageChecked.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (currentPageChecked.length === currentPageCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }
    
    // Initialize checkboxes based on stored selections
    function initializeCheckboxes() {
        sellerCheckboxes.forEach(checkbox => {
            const sellerId = checkbox.value;
            const sellerName = checkbox.getAttribute('data-name');
            
            if (selectedSellers[sellerId]) {
                checkbox.checked = true;
            }
        });
        updateSelection();
    }
    
    // Save/remove seller from localStorage
    function toggleSellerInStorage(sellerId, sellerName, isChecked) {
        if (isChecked) {
            selectedSellers[sellerId] = {
                id: sellerId,
                name: sellerName
            };
        } else {
            delete selectedSellers[sellerId];
        }
        localStorage.setItem(storageKey, JSON.stringify(selectedSellers));
    }
    
    // Select all functionality for current page
    selectAllCheckbox.addEventListener('change', function() {
        sellerCheckboxes.forEach(checkbox => {
            const wasChecked = checkbox.checked;
            checkbox.checked = this.checked;
            
            if (wasChecked !== this.checked) {
                const sellerId = checkbox.value;
                const sellerName = checkbox.getAttribute('data-name');
                toggleSellerInStorage(sellerId, sellerName, this.checked);
            }
        });
        updateSelection();
    });
    
    // Individual checkbox change
    sellerCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const sellerId = this.value;
            const sellerName = this.getAttribute('data-name');
            toggleSellerInStorage(sellerId, sellerName, this.checked);
            updateSelection();
        });
    });
    
    // Download selected functionality
    downloadSelectedBtn.addEventListener('click', function() {
        const selectedIds = Object.keys(selectedSellers);
        
        if (selectedIds.length === 0) {
            alert('Please select at least one seller.');
            return;
        }
        
        // Show loading state
        this.innerHTML = '<i class="bi bi-spinner-border"></i> Preparing ZIP...';
        this.disabled = true;
        
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("all.seller.invoice.bulk.selected", ["month" => $month]) }}';
        
        // Add CSRF token
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Add selected seller IDs
        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'seller_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
        
        // Clear localStorage after successful download
        localStorage.removeItem(storageKey);
        selectedSellers = {};
        
        // Reset button after a delay
        setTimeout(() => {
            this.innerHTML = '<i class="bi bi-check-square"></i> Download Selected';
            this.disabled = true;
            updateSelection();
        }, 3000);
    });
    
    // Clear all selections functionality
    clearAllBtn.addEventListener('click', function() {
        // Clear localStorage
        localStorage.removeItem(storageKey);
        selectedSellers = {};
        
        // Uncheck all current page checkboxes
        sellerCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
        
        updateSelection();
    });
    
    // Initialize everything
    initializeCheckboxes();
});
</script>
@endsection


