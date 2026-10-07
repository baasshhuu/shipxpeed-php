@extends('layouts.app')

@section('title', 'Send WhatsApp Messages')

@section('css')
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .btn-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
    }
    .btn-gradient:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        color: white;
    }
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">WhatsApp Marketing Messages</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                        <li class="breadcrumb-item active">WhatsApp Messages</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Add Number Section -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-plus-circle me-2"></i>Add Phone Number</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    <form action="{{ route('SendWhatsApp.store') }}" method="POST" id="addNumberForm">
                        @csrf
                        <div class="mb-3">
                            <label for="number" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="number" name="number" 
                                   placeholder="Enter phone number (e.g., 7357169546)" required>
                            <div class="form-text">Number will be automatically formatted with +91</div>
                        </div>
                        <button type="submit" class="btn btn-gradient w-100">
                            <i class="fas fa-plus me-2"></i>Add Number
                        </button>
                    </form>
                </div>
            </div>

            <!-- Upload Excel Section -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-file-excel me-2"></i>Upload Excel File</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <a href="{{ route('SendWhatsApp.download-sample') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-2"></i>Download Sample Excel
                        </a>
                        <small class="text-muted d-block mt-1">Download sample Excel file with correct format</small>
                    </div>
                    
                    <form action="{{ route('SendWhatsApp.upload-excel') }}" method="POST" enctype="multipart/form-data" id="uploadExcelForm">
                        @csrf
                        <div class="mb-3">
                            <label for="excel_file" class="form-label">Excel File</label>
                            <input type="file" class="form-control" id="excel_file" name="excel_file" 
                                   accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">Upload Excel file with phone numbers in first column</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-upload me-2"></i>Upload Excel
                        </button>
                    </form>
                </div>
            </div>

            <!-- Send Message Section -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-bullhorn me-2"></i>Send Marketing Message</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('SendWhatsApp.send-message') }}" method="POST" id="sendMessageForm">
                        @csrf
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="6" 
                                      placeholder="Enter your marketing message here..." required></textarea>
                            <div class="form-text">This message will be sent to all phone numbers in your list.</div>
                        </div>
                        <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Are you sure you want to send this message to all phone numbers?')">
                            <i class="fas fa-paper-plane me-2"></i>Send to All Numbers
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Numbers List Section -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Phone Numbers List</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-success btn-sm" onclick="refreshTable()">
                            <i class="fas fa-refresh me-1"></i>Refresh
                        </button>
                        <span class="badge bg-primary" id="totalCount">Total: 0</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="numbersTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Phone Number</th>
                                    <th>Added On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#numbersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('SendWhatsApp.get-numbers') }}",
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'number', name: 'number'},
            {
                data: 'created_at', 
                name: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']],
        drawCallback: function(settings) {
            $('#totalCount').text('Total: ' + settings.json.recordsTotal);
        }
    });

    // Remove the AJAX form submission code since we're using normal form action now
    // The form will submit normally to the controller

    // Upload Excel Form
    $('#uploadExcelForm').on('submit', function(e) {
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Uploading...');
    });

    // Send Message Form
    $('#sendMessageForm').on('submit', function(e) {
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Sending...');
    });

    // Delete Number
    $(document).on('click', '.delete-btn', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('SendWhatsApp.destroy', '') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', 'An error occurred while deleting.', 'error');
                    }
                });
            }
        });
    });
});

// Refresh Table Function
function refreshTable() {
    $('#numbersTable').DataTable().ajax.reload();
}
</script>
@endsection 
