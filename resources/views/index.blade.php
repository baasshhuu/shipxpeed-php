@extends('layouts.app')

@section('title', 'Order Status Upload')

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<style>
    /* Custom - Modern, Premium and Responsive Enhancements */
    .premium-card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 6px 28px 0 rgba(60, 72, 88, 0.12), 0 1.5px 7px 0 rgba(60,72,88,0.03);
        background: #fff;
        overflow: hidden;
        margin-bottom: 28px;
        transition: box-shadow .2s;
    }
    .premium-card:hover {
        box-shadow: 0 12px 32px 0 rgba(60, 72, 88, 0.21), 0 4px 12px 0 rgba(60,72,88,0.10);
    }
    .premium-card-header {
        padding: 24px 2.2rem 16px 2.2rem;
        background: linear-gradient(89deg, #3638A3 0%, #5E5EFF 100%);
        color: #fff;
        border-bottom: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    .premium-card-title {
        font-size: 1.4rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 12px;
        letter-spacing: 0.01em;
    }
    .premium-card-body {
        padding: 2.2rem;
        background: #f8fafd;
        border-radius: 0 0 18px 18px;
    }
    .premium-alert-instructions {
        background: linear-gradient(79deg,#e1ebfd,#eaf5f7 98%);
        border: 0;
        color: #12213A;
        font-size: 1rem;
        padding: 1.2rem 1.5rem 1rem 1.3rem;
        border-radius: 1rem;
        margin-bottom: 2.2rem;
        box-shadow: 0 2.5px 16px 0 rgba(89,135,255,.07);
    }
    /* Form Modernization */
    .form-label {
        font-weight: 500;
        letter-spacing: .01rem;
        color: #2f365f;
        margin-bottom: 6px;
    }
    .premium-file-input {
        border: 2px dashed #a3a8cf;
        border-radius: 16px;
        background: #fff;
        padding: 32px 26px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
        margin-bottom: 0;
    }
    .premium-file-input:hover, .premium-file-input:focus-within {
        border-color: #4257ef;
        background: #f2f5fc;
    }
    .premium-upload-btn {
        min-width: 155px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1.02rem;
        padding: 10px 18px;
    }
    .premium-btn-download {
        background: #fff;
        color: #3049e6;
        border: 1.5px solid #3049e6;
        margin-left: 8px;
        font-weight: 500;
        border-radius: 8px;
        transition: background .15s, color .15s;
    }
    .premium-btn-download:hover {
        background: #3049e6;
        color: #fff;
    }
    .progress {
        height: 16px;
        border-radius: 8px;
        background: #eef4fb;
    }
    .progress-bar {
        font-size: 0.93rem;
        font-weight: 500;
        line-height: 1;
        border-radius: 8px;
    }
    /* Search enhancements */
    .premium-search-group {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 4px #d8e0f2;
        padding: 24px 2.2rem 18px 2.2rem;
        margin-bottom: 0;
    }
    .premium-search-btn {
        border-radius: 8px;
        font-weight: 600;
        font-size: 1.03rem;
        padding: 9px 20px;
        min-width: 110px;
    }
    /* Table Modernization */
    .premium-table th, .premium-table td {
        vertical-align: middle;
        border-top: none;
    }
    .premium-table th {
        background: #f8fafc;
        color: #15367A;
        font-weight: 600;
    }
    .premium-table tbody tr {
        background: #fff;
        transition: background 0.15s;
    }
    .premium-table tbody tr:hover {
        background: #f3f5ff;
    }
    @media (max-width: 992px) {
        .premium-card-body,
        .premium-search-group {
            padding: 1.1rem;
        }
    }
    @media (max-width: 767.98px) {
        .premium-card-header,
        .premium-search-group {
            flex-direction: column;
            align-items: flex-start;
            padding: 1.3rem 1.1rem 1rem 1.2rem;
        }
        .premium-card-body {
            padding: 1.1rem;
        }
        .premium-file-input {
            padding: 20px 8px;
        }
        .premium-card-title {
            font-size: 1.07rem;
        }
    }
    @media (max-width: 480px) {
        .premium-card-header, .premium-card-body, .premium-search-group {
            padding: 0.7rem 0.65rem 0.8rem 0.65rem;
        }
    }
</style>
<div class="container-fluid px-1 px-sm-2 px-md-4" style="padding-top: 24px;">
    <!-- Upload Excel Section -->
    <div class="row">
        <div class="col-12">
            <div class="premium-card mb-4">
                <div class="premium-card-header">
                    <span class="premium-card-title">
                        <i class="fas fa-upload"></i>
                        Upload Order Status Excel
                    </span>
                    <a href="{{ route('order.status.template') }}" class="premium-btn-download btn btn-sm" title="Download Excel Template">
                        <i class="fas fa-download me-1"></i> Template
                    </a>
                </div>
                <div class="premium-card-body">
                    <div class="premium-alert-instructions">
                        <strong><i class="icon fas fa-lightbulb"></i> Quick Instructions:</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            <li>First, download the <b>template</b> (button above).</li>
                            <li>Fill AWB Numbers & correct statuses. Possible Statuses: 
                                <code>READY_TO_SHIP</code>, <code>READY_FOR_PICKUP</code>, <code>SHIPPED</code>,
                                <code>OUT_FOR_DELIVERY</code>, <code>DELIVERED</code>, 
                                <code>RETURNING_TO_ORIGIN</code>, <code>CANCELLED</code>
                            </li>
                            <li>If delivered, you may enter <b>Delivered date</b> (YYYY-MM-DD).</li>
                            <li>Max File: <b>10MB</b> | Types: <span class="ms-1"><code>.xlsx, .xls, .csv</code></span></li>
                        </ul>
                    </div>
                    <form id="uploadForm" action="{{ route('order.status.upload') }}" method="POST" enctype="multipart/form-data" novalidate autocomplete="off">
                        @csrf
                        <div class="row align-items-center gy-3">
                            <div class="col-lg-9 col-md-12">
                                <div>
                                    <label for="excel_file" class="form-label mb-2">Select Excel File:</label>
                                    <label class="premium-file-input w-100">
                                        <span class="text-secondary d-block mb-2">
                                            <i class="fas fa-file-excel fa-lg text-success me-2"></i>
                                            Drag and drop, or click to browse...
                                        </span>
                                        <input type="file" class="form-control d-none" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                                        <span id="fileName" class="text-muted" style="display:none;"></span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-12 text-lg-end text-md-end text-start pt-2 pt-lg-0">
                                <button type="submit" class="btn btn-primary premium-upload-btn w-100" id="uploadBtn">
                                    <i class="fas fa-upload me-2"></i>
                                    Upload & Update
                                </button>
                            </div>
                        </div>
                    </form>
                    <!-- Progress Bar -->
                    <div id="progressContainer" class="d-none my-3">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    <!-- Results -->
                    <div id="resultsContainer" class="d-none mt-3">
                        <div class="premium-card" style="box-shadow:none;">
                            <div class="premium-card-header py-2" style="background: #f4f7fe; color: #2f365f;">
                                <span class="premium-card-title fs-6 mb-0">
                                    <i class="fas fa-list-check me-2 text-primary"></i>
                                    Upload Results
                                </span>
                            </div>
                            <div class="premium-card-body pt-2 pb-2" id="resultsContent" style="background: #fff;">
                                <!-- Results injected here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Search Section -->
    <div class="row mb-5 mt-4">
        <div class="col-12">
            <div class="premium-card">
                <div class="premium-card-header">
                    <span class="premium-card-title">
                        <i class="fas fa-search"></i>
                        Search Orders
                    </span>
                </div>
                <div class="premium-card-body">
                    <div class="premium-search-group">
                        <form class="row align-items-end gx-3 gy-2" onsubmit="return false;">
                            <div class="col-md-8 col-12 mb-2 mb-md-0">
                                <label for="searchInput" class="form-label">AWB or Order Number:</label>
                                <input type="text" class="form-control" id="searchInput" maxlength="40"
                                       placeholder="Type AWB number or Order number">
                            </div>
                            <div class="col-md-4 col-12 text-md-end text-start pt-2 pt-md-0">
                                <button type="button" class="btn btn-info premium-search-btn w-100" id="searchBtn">
                                    <i class="fas fa-search me-2"></i>
                                    Search
                                </button>
                            </div>
                        </form>
                    </div>
                    <!-- Search Results -->
                    <div id="searchResults" class="d-none mt-2">
                        <div class="table-responsive">
                            <table class="table premium-table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Order Number</th>
                                        <th>AWB Number</th>
                                        <th>Current Status</th>
                                        <th>Delivered Date</th>
                                        <th>Created Date</th>
                                    </tr>
                                </thead>
                                <tbody id="searchResultsBody">
                                    <!-- Search results will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Enhance the file input for better UX display
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('excel_file');
        const fileNameSpan = document.getElementById('fileName');
        if(fileInput) {
            fileInput.addEventListener('change', function(){
                const name = this.files && this.files.length ? this.files[0].name : '';
                if(name) {
                    fileNameSpan.style.display = "inline";
                    fileNameSpan.textContent = name;
                } else {
                    fileNameSpan.style.display = "none";
                    fileNameSpan.textContent = '';
                }
            });
            // Also allow label trigger for browse
            document.querySelector('.premium-file-input').onclick = () => fileInput.click();
        }
    });
</script>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // File input change event
    $('#excel_file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Form submission with loading state
    $('#uploadForm').on('submit', function() {
        var file = $('#excel_file')[0].files[0];
        if (!file) {
            alert('Please select a file first!');
            return false;
        }
        
        // Show loading state
        $('#uploadBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Processing...');
        
        return true; // Allow normal form submission
    });

    // Search functionality
    $('#searchBtn, #searchInput').on('click keypress', function(e) {
        if (e.type === 'click' || e.which === 13) {
            performSearch();
        }
    });

    function performSearch() {
        var searchTerm = $('#searchInput').val().trim();
        if (searchTerm.length < 3) {
            alert('Please enter at least 3 characters to search');
            return;
        }

        $.ajax({
            url: '{{ route("order.search") }}',
            type: 'GET',
            data: { search: searchTerm },
            success: function(response) {
                displaySearchResults(response);
            },
            error: function() {
                alert('An error occurred while searching');
            }
        });
    }

    function displaySearchResults(orders) {
        var tbody = $('#searchResultsBody');
        tbody.empty();

        if (orders.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="5" class="text-center">No orders found</td>
                </tr>
            `);
        } else {
            orders.forEach(function(order) {
                var statusBadge = getStatusBadge(order.shipping_status);
                var deliveredDate = order.delivered_date || '-';
                var createdDate = new Date(order.created_at).toLocaleDateString();
                
                tbody.append(`
                    <tr>
                        <td>${order.order_number}</td>
                        <td><code>${order.awb_number}</code></td>
                        <td>${statusBadge}</td>
                        <td>${deliveredDate}</td>
                        <td>${createdDate}</td>
                    </tr>
                `);
            });
        }

        $('#searchResults').removeClass('d-none');
    }

    function getStatusBadge(status) {
        var badgeClass = 'secondary';
        switch (status) {
            case 'delivered':
                badgeClass = 'success';
                break;
            case 'transit':
            case 'out for delivery':
                badgeClass = 'info';
                break;
            case 'rto':
            case 'cancelled':
                badgeClass = 'danger';
                break;
            case 'pending':
                badgeClass = 'warning';
                break;
        }
        return `<span class="badge badge-${badgeClass}">${status || 'unknown'}</span>`;
    }
});
</script>

@endsection