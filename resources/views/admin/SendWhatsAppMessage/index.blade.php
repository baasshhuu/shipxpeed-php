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
<div class="container-fluid" style="padding-top:27px; padding-left:1.6rem; padding-right:1.4rem;">
    <!-- <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">WhatsApp Marketing System</h4>
               
            </div>
        </div>
    </div> -->

    <div class="row enhanced-wa-section shadow-sm" style="background: linear-gradient(105deg, #f5fcff 0%, #f7f8fa 100%); border-radius: 7px; margin: 0; padding: 24px 10px 10px 10px; border: 1.5px solid #e8eafc; box-shadow: 0 8px 24px 0 rgba(170,180,235,0.08);">
        <div class="col-12 d-flex align-items-center flex-wrap justify-content-between">
            <div class="premium-section-header mb-4 px-2 flex-grow-1">
                <h2 class="fw-bold mb-2" style="letter-spacing: .5px; color: #4954a4; font-weight: 700; text-shadow: 0 2px 8px rgba(229,233,248,0.19);">
                    <span class="icon-circle" style="background: #e5fbe0; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; width: 38px; height: 38px; margin-right: 10px; vertical-align: middle; box-shadow: 0 2px 6px 0 rgba(63,195,128,0.09);">
                      <i class="fab fa-whatsapp text-success" style="font-size: 1.58rem;"></i>
                    </span>
                    WhatsApp Marketing Manager
                </h2>
                <p class="text-muted fs-6 mb-2" style="color: #687492 !important; font-size: 1.02rem;">
                    Seamlessly manage, upload, and send marketing messages in bulk with a smooth, modern workflow.
                </p>
                <span class="d-inline-block mt-1 px-2 py-1 rounded-pill border-0" style="font-weight: 500; font-size: .96rem; background: #eaf6ff; color: #2d66a3;">
                    Add numbers, upload Excel, or send to thousands in one go.
                </span>
            </div>
            <div class="d-flex flex-lg-column flex-row align-items-stretch gap-2 gap-lg-2 wa-action-btn-row justify-content-end" style="margin-left: auto;">
                <a href="{{ route('SendWhatsApp.create') }}" class="btn wa-action-btn wa-action-start shadow-sm py-2 px-3 mb-0 text-center wa-fixed-btn-width">
                    <i class="fas fa-plus-circle me-2 text-success"></i>
                    <span class="d-none d-sm-inline">Start Messaging</span>
                    <span class="d-inline d-sm-none">Start</span>
                </a>
                <a href="{{ route('SendWhatsApp.create') }}" class="btn wa-action-btn wa-action-upload shadow-sm py-2 px-3 mb-0 text-center wa-fixed-btn-width">
                    <i class="fas fa-file-excel me-2 text-primary"></i>
                    <span class="d-none d-sm-inline">Upload Excel</span>
                    <span class="d-inline d-sm-none">Upload</span>
                </a>
                <a href="{{ route('SendWhatsApp.download-sample') }}" class="btn wa-action-btn wa-action-download shadow-sm py-2 px-3 mb-0 text-center wa-fixed-btn-width">
                    <i class="fas fa-download me-2 text-info"></i>
                    <span class="d-none d-sm-inline">Download Sample</span>
                    <span class="d-inline d-sm-none">Sample</span>
                </a>
            </div>
        </div>
    </div>
    <style>
        .premium-section-header h2 {
            font-size: 1.48rem;
            font-weight: 700;
        }
        .premium-section-header p {
            font-size: 1.02rem;
            margin-bottom: 4px;
        }
        .wa-action-btn-row {
            align-items: stretch !important;
            min-width: 150px;
            justify-content: flex-end !important;
        }
        /* Fixed width for all action buttons */
        .wa-fixed-btn-width {
            width: 210px;
            min-width: 210px;
            max-width: 210px;
            display: inline-block;
        }
        .wa-action-btn {
            border-radius: 12px;
            font-size: 0.98rem;
            font-weight: 500;
            letter-spacing: .01em;
            border: 1.2px solid #e7eafe;
            min-width: 0;
            box-shadow: 0 2px 9px 0 rgba(108, 128, 221, 0.045);
            transition:
                transform 0.15s cubic-bezier(.22,.55,.31,1),
                box-shadow 0.11s cubic-bezier(.22,.55,.31,1),
                color 0.18s;
            background: linear-gradient(105deg, #fafcff 60%, #edeffe 100%);
            color: #285786;
            padding: 0.45rem 0.86rem;
            height: fit-content !important;
            line-height: 1.2;
        }
        .wa-action-btn i {
            font-size: 1.00rem !important;
            opacity: 0.88;
        }
        .wa-action-start {
            background: linear-gradient(99deg, #eafaf0 0%, #e4fff6 100%);
            color: #1c8f4c;
            border: 1.2px solid #b8f8c8;
        }
        .wa-action-upload {
            background: linear-gradient(103deg, #f8f8fd 0%, #fdf6ff 100%);
            color: #86419d;
            border: 1.2px solid #f1d7fe;
        }
        .wa-action-download {
            background: linear-gradient(97deg, #e9f3fd 0%, #f3fdfa 100%);
            color: #2b84ba;
            border: 1.2px solid #cfebfc;
        }
        .wa-action-btn:focus,
        .wa-action-btn:hover {
            transform: translateY(-2px) scale(1.028);
            box-shadow: 0 10px 14px 0 rgba(89, 111, 203, 0.12);
            opacity: 0.995;
            color: #222 !important;
            text-decoration: none;
            border-color: #bdd6ff;
        }
        .icon-circle {
            box-shadow: 0 2px 8px 0 rgba(25, 187, 78, 0.06);
        }
        .premium-section-header span.d-inline-block {
            background: #eaf6ff;
            color: #2d66a3;
            font-size: .96rem;
            font-weight: 500;
            letter-spacing: .01em;
            padding: 0.41rem 0.9rem;
        }
        /* Responsive styles */
        @media (max-width: 1199.98px) {
            .premium-section-header h2 {
                font-size: 1.27rem;
            }
        }
        @media (max-width: 991.98px) {
            .premium-section-header h2 {
                font-size: 1.07rem;
            }
            .premium-section-header p {
                font-size: .93rem;
            }
        }
        @media (max-width: 767.98px) {
            .enhanced-wa-section {
                padding: 10px 3px 7px 6px !important;
            }
            .premium-section-header {
                padding-left: 2px;
                padding-right: 2px;
            }
            .premium-section-header h2 {
                font-size: .98rem;
            }
            .premium-section-header p {
                font-size: .85rem;
            }
            .premium-section-header span.d-inline-block {
                font-size: .91rem;
                padding: 0.34rem 0.7rem;
            }
            .wa-action-btn-row {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 0.5rem !important;
                justify-content: flex-end !important;
            }
            .wa-fixed-btn-width {
                width: 100%;
                min-width: 0;
                max-width: 100%;
            }
            .wa-action-btn {
                font-size: .85rem;
                padding: .5rem .6rem;
                border-radius: 10px;
            }
        }
        @media (max-width: 575.98px) {
            .premium-section-header h2,
            .premium-section-header p {
                font-size: .75rem !important;
            }
            .premium-section-header span.d-inline-block {
                font-size: .82rem;
                padding: 0.24rem 0.5rem;
            }
            .wa-fixed-btn-width {
                width: 100%;
                min-width: 0;
                max-width: 100%;
            }
            .wa-action-btn {
                font-size: 0.75rem;
                border-radius: 7px;
                padding: 0.38rem 0.36rem;
            }
        }
    </style>

    <div class="row mt-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100 animate__animated animate__fadeInLeft">
                <div class="card-header bg-gradient-primary text-white d-flex align-items-center" style="background: linear-gradient(87deg, #4158D0 0%, #5561b1 100%) !important;">
                    <span class="me-2 position-relative" style="font-size: 1.6rem;">
                        <i class="fas fa-info-circle"></i>
                    </span>
                    <h5 class="card-title mb-0 fw-semibold flex-grow-1" style="color:white;">How to Use</h5>
                </div>
                <div class="card-body py-4">
                    <ul class="list-group mb-0">
                        <li class="list-group-item border-0 px-0 d-flex align-items-baseline mb-1 bg-transparent">
                            <i class="fa-solid fa-comments mb-0 me-2 text-primary"></i>
                            <span>Click <span class="fw-bold text-primary">Start Messaging</span> to access the bulk messaging interface.</span>
                        </li>
                        <li class="list-group-item border-0 px-0 d-flex align-items-baseline mb-1 bg-transparent">
                            <i class="fa-solid fa-plus-circle mb-0 me-2 text-success"></i>
                            <span>Add numbers individually using the form.</span>
                        </li>
                        <li class="list-group-item border-0 px-0 d-flex align-items-baseline mb-1 bg-transparent">
                            <i class="fa-solid fa-upload mb-0 me-2 text-warning"></i>
                            <span>Or upload an <span class="fw-bold text-success">Excel file</span> with numbers in the first column.</span>
                        </li>
                        <li class="list-group-item border-0 px-0 d-flex align-items-baseline mb-1 bg-transparent">
                            <i class="fa-solid fa-pen mb-0 me-2 text-info"></i>
                            <span>Write your message and fill optional details.</span>
                        </li>
                        <li class="list-group-item border-0 px-0 d-flex align-items-baseline mb-0 bg-transparent">
                            <i class="fa-solid fa-paper-plane mb-0 me-2 text-danger"></i>
                            <span>Click <span class="fw-bold text-danger">Send to All Numbers</span> to send WhatsApp messages.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100 animate__animated animate__fadeInRight">
                <div class="card-header bg-gradient-success text-white d-flex align-items-center" style="background: linear-gradient(87deg, #23b66f 0%, #27AE60 100%) !important;">
                    <span class="me-2 position-relative" style="font-size: 1.6rem;">
                        <i class="fas fa-file-excel"></i>
                    </span>
                    <h5 class="card-title mb-0 fw-semibold flex-grow-1" style="color:white;">Excel Format</h5>
                </div>
                <div class="card-body py-4">
                    <p class="mb-2"><strong>Required Excel Format:</strong></p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-2" style="background: #f4f6fa;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:60px; text-align:center;">A</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold text-secondary">Phone Number</td>
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
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <i class="fa-solid fa-info-circle text-info"></i>
                        <small class="text-muted">
                            Put phone numbers in column <span class="fw-semibold">A</span>. Numbers will be auto-formatted with <span class="fw-bold text-success">+91</span>.
                        </small>
                    </div>
                    <a href="{{ asset('sample_whatsapp.xlsx') }}" class="btn btn-outline-success btn-sm rounded-pill mt-3" download>
                        <i class="fa-solid fa-download me-1"></i> Download Sample Excel
                    </a>
                </div>
            </div>
        </div>

        <style>
            /* Card Gradient header for animation demo, adjust if not using animate.css */
            .bg-gradient-primary {
                background: linear-gradient(87deg, #4158D0 0%, #768dfd 100%) !important
            }
            .bg-gradient-success {
                background: linear-gradient(90deg, #23b66f 0%, #27AE60 100%) !important;
            }
            @media (max-width: 991.98px) {
                .col-lg-6.mb-4 {
                    margin-bottom: 2rem !important;
                }
            }
            @media (max-width: 767.98px) {
                .col-lg-6.mb-4 {
                    margin-bottom: 1.5rem !important;
                }
            }
            @media (max-width: 575.98px) {
                .card-header h5 {
                    font-size: .98rem !important;
                }
                .card-body {
                    font-size: .95rem;
                }
            }
        </style>
        <!-- Optionally include animate.css for fadeIn effects. If not included, remove animate__ classes above. -->
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