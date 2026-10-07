@extends('layouts.app')

@section('title', 'Order Status Upload')

@section('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-upload mr-2"></i>
                        Upload Excel File to Update Order Status
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('order.status.template') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-download mr-1"></i>
                            Download Template
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Instructions -->
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Instructions:</h5>
                        <ul class="mb-0">
                            <li>Download the template file first</li>
                            <li>Fill in the AWB numbers and corresponding statuses</li>
                            <li>Excel statuses: <code>READY_TO_SHIP</code>, <code>READY_FOR_PICKUP</code>, <code>SHIPPED</code>, <code>OUT_FOR_DELIVERY</code>, <code>DELIVERED</code>, <code>RETURNING_TO_ORIGIN</code>, <code>CANCELLED</code></li>
                            <li>For delivered orders, optionally provide delivery date in YYYY-MM-DD format</li>
                            <li>Maximum file size: 10MB</li>
                            <li>Supported formats: .xlsx, .xls, .csv</li>
                        </ul>
                    </div>

                    <!-- Upload Form -->
                    <form id="uploadForm" action="{{ route('order.status.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="excel_file">Select Excel File:</label>
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="excel_file" name="excel_file" 
                                                   accept=".xlsx,.xls,.csv" required>
                                            <label class="custom-file-label" for="excel_file">Choose file...</label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary" id="uploadBtn">
                                                <i class="fas fa-upload mr-1"></i>
                                                Upload & Update
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Progress Bar -->
                    <div id="progressContainer" class="d-none">
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Results Container -->
                    <div id="resultsContainer" class="d-none">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Upload Results</h5>
                            </div>
                            <div class="card-body" id="resultsContent">
                                <!-- Results will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Search Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-search mr-2"></i>
                        Search Orders
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="searchInput">Search by AWB Number or Order Number:</label>
                                <input type="text" class="form-control" id="searchInput" 
                                       placeholder="Enter AWB or Order number...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-info btn-block" id="searchBtn">
                                <i class="fas fa-search mr-1"></i>
                                Search
                            </button>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div id="searchResults" class="d-none">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
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