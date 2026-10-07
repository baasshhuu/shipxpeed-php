@extends('layouts.app')

@section('title', 'WhatsApp Messages')

@section('css')
<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px 20px;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .stats-card i {
        font-size: 3rem;
        opacity: 0.8;
    }
    .stats-number {
        font-size: 2.5rem;
        font-weight: bold;
        margin: 15px 0 5px 0;
    }
    .stats-label {
        font-size: 1rem;
        opacity: 0.9;
    }
    .action-btn {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        border: none;
        color: white;
        padding: 15px 30px;
        border-radius: 50px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        color: white;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">WhatsApp Marketing System</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                        <li class="breadcrumb-item active">WhatsApp Messages</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
<!-- 
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <i class="fas fa-phone"></i>
                <div class="stats-number" id="totalNumbers">0</div>
                <div class="stats-label">Total Numbers</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-paper-plane"></i>
                <div class="stats-number">0</div>
                <div class="stats-label">Messages Sent Today</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-check-circle"></i>
                <div class="stats-number">0</div>
                <div class="stats-label">Success Rate</div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-rupee-sign"></i>
                <div class="stats-number">₹0</div>
                <div class="stats-label">Total Cost</div>
            </div>
        </div>
    </div> -->

    <div class="row mt-4">
        <div class="col-12 text-center">
            <h3 class="mb-4">Manage WhatsApp Marketing Messages</h3>
            <p class="text-muted mb-4">Add phone numbers manually or upload Excel file, then send bulk marketing messages via WhatsApp</p>
            
            <a href="{{ route('SendWhatsApp.create') }}" class="action-btn me-3">
                <i class="fas fa-plus-circle me-2"></i>Start Messaging
            </a>
            
            <a href="{{ route('SendWhatsApp.create') }}" class="action-btn me-3" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <i class="fas fa-file-excel me-2"></i>Upload Excel
            </a>
            
            <a href="{{ route('SendWhatsApp.download-sample') }}" class="action-btn" style="background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);">
                <i class="fas fa-download me-2"></i>Download Sample
            </a>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>How to Use</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2">Click on "Start Messaging" to access the bulk messaging interface</li>
                        <li class="mb-2">Add phone numbers individually using the form</li>
                        <li class="mb-2">Or upload an Excel file with phone numbers in the first column</li>
                        <li class="mb-2">Write your message and fill optional details</li>
                        <li class="mb-0">Click "Send to All Numbers" to send bulk WhatsApp messages</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0"><i class="fas fa-file-excel me-2"></i>Excel Format</h5>
                </div>
                <div class="card-body">
                    <p><strong>Required Excel Format:</strong></p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>A</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Phone Number</td>
                                </tr>
                                <tr>
                                    <td>7357169546</td>
                                </tr>
                                <tr>
                                    <td>9876543210</td>
                                </tr>
                                <tr>
                                    <td>8765432109</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted">Put phone numbers in column A. Numbers will be auto-formatted with +91</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    // Load stats
    loadStats();
});

function loadStats() {
    $.ajax({
        url: "{{ route('SendWhatsApp.get-numbers') }}",
        type: 'GET',
        success: function(response) {
            if (response.recordsTotal) {
                $('#totalNumbers').text(response.recordsTotal);
            }
        }
    });
}
</script>
@endsection