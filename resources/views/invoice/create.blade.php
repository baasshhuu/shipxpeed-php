@extends('layouts.app')

@section('content')
    <div class="pc-container" style="padding: 15px 20px;">
        <div class=" mt-4">
            <div class="bg-white rounded shadow-sm p-3">

                <!-- Top Bar -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <!-- Left: Filter Icon & Date Input -->
                    <div class="d-flex align-items-center gap-2">
                        {{-- <i class="fas fa-filter filter-icon" data-bs-toggle="offcanvas" href="#offcanvasExample"></i> --}}
                        <input type="text" id="daterange" class="form-control date-input"
                            placeholder="Select Date Range">
                    </div>

                    <!-- Right: Icons (Settings, Refresh, Download, Width Setting) -->
                    <div class="d-flex align-items-center gap-3">
                        <!-- Refresh Icon -->
                    </div>
                </div>

                <!-- Offcanvas Sidebar (Filters) -->
                <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasExample">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title">Filters</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
                    </div>
                    <div class="offcanvas-body">
                        <p>Use filters to refine your results.</p>
                        <button class="btn btn-secondary dropdown-toggle w-100" data-bs-toggle="dropdown">Select
                            Option</button>
                        <ul class="dropdown-menu sw-100">
                            <li><a class="dropdown-item" href="#">Option 1</a></li>
                            <li><a class="dropdown-item" href="#">Option 2</a></li>
                            <li><a class="dropdown-item" href="#">Option 3</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Responsive Table -->
                <div class="table-responsive">
                    <div class="card border-0 shadow-sm mb-4" style="overflow: hidden; background: #f9fafb;">
                        <div class="card-header text-center"
                             style="background: linear-gradient(90deg, #e0e7ef 0%, #f1f5fb 100%); border-bottom: 1px solid #eef1f4;">
                            <h5 class="mb-0" style="font-weight: 600; letter-spacing: .5px; color: #2563eb;">Available Invoice Months</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0"
                                style="background: #fff; min-width:300px;">
                                <thead style="background: #f1f5fb;">
                                    <tr>
                                        <th style="min-width:120px; color: #4b5563; font-weight: 500;">Month</th>
                                        <th style="min-width:120px; color: #4b5563; font-weight: 500;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($months as $month)
                                        <tr class="invoice-row">
                                            <td>
                                                <span class="fw-semibold" style="font-size: 1rem; color: #374151;">
                                                    <i class="far fa-calendar-alt me-2 text-primary"></i> {{ $month }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('all.seller.monthly.report', ['month' => $month]) }}"
                                                   class="btn view-details-btn px-4 py-2 fw-semibold"
                                                   style="
                                                      background: linear-gradient(90deg, #e0e7ef 0%, #93c5fd 100%);
                                                      border: none;
                                                      color: #1d4ed8;
                                                      transition: box-shadow .2s, background .2s, color .2s;
                                                      box-shadow: 0 2px 8px 0 rgba(80, 140, 255, .06);
                                                    ">
                                                    <i class="fas fa-eye me-1"></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-muted text-center py-4">
                                                <i class="far fa-info-circle me-1"></i> No months available for invoice generation.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <style>
                    .card {
                        border-radius: 1.2rem!important;
                    }
                    .card-header {
                        border-radius: 1.2rem 1.2rem 0 0 !important;
                    }
                    .table > :not(:first-child) {
                        border-top: none;
                    }
                    .btn[style*="linear-gradient"] {
                        background: linear-gradient(90deg, #e0e7ef 0%, #93c5fd 100%)!important;
                        color: #2563eb!important;
                        font-weight: 600;
                    }
                    .btn[style*="linear-gradient"]:hover {
                        background: #e0e7ef!important;
                        color: #1e40af!important;
                        box-shadow: 0 4px 16px 0 rgba(59,130,246,.13)!important;
                    }
                    /* Mobile view adjustments for "View Details" */
                    @media (max-width: 575.98px) {
                        .card-header h5 { font-size: 1rem; }
                        .btn[style*="linear-gradient"] { font-size: .63rem!important; padding: .38rem .35rem!important; }
                        table th, table td { font-size: .92rem!important; }
                        /* Remove pill, remove gradient and keep single row for View Details on mobile */
                        .view-details-btn {
                            display: inline-block !important;
                            width: auto;
                            min-width: 80px;
                            text-align: center;
                            padding: .45rem 1rem !important;
                            border-radius: 6px !important;
                            background: #e0e7ef !important;
                            color: #1d4ed8 !important;
                            box-shadow: none !important;
                            white-space: nowrap;
                        }
                        .view-details-btn:hover, .view-details-btn:focus {
                            background: #dbeafe !important;
                            color: #1e40af !important;
                        }
                        /* Ensure both td are in a single row, don't stack month and button vertically */
                        .invoice-row {
                            display: table-row;
                        }
                        .invoice-row > td {
                            /* No mobile stacking, standard cell layout */
                            display: table-cell;
                            width: auto;
                            vertical-align: middle;
                        }
                    }
                    @media (max-width: 991.98px) {
                        .card { border-radius: .75rem!important; }
                    }
                </style>
            </div>
        </div>
    </div>
@endsection
