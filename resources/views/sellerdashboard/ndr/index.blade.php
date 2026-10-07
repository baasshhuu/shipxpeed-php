@extends('layouts.sellerdash')

@section('content')
    <div class="pc-container">
        <div class="pc-content">
            <div class="section-container">
                <!-- Section 1 (NDR Actions) -->
                <div class="btn-group-container">
                    <div class="btn-group border" role="group">
                        <button type="button" class="btn active" onclick="setActive(this)">NDR</button>
                        <button type="button" class="btn" onclick="setActive(this)">NDR(Wrong Address/Phone)
                        </button>
                        <button type="button" class="btn" onclick="setActive(this)">NDR Delivered</button>
                        <button type="button" class="btn " onclick="setActive(this)">RTO</button>

                        <button type="button" class="btn " onclick="setActive(this)">RTO Delivered</button>

                    </div>
                </div>

            </div>

            <div class=" mt-4">
                <div class="bg-white rounded shadow-sm p-3">

                    <!-- Top Bar -->
                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <!-- Left: Filter Icon & Date Input -->
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-filter filter-icon" data-bs-toggle="offcanvas" href="#offcanvasExample"></i>
                            <input type="text" id="daterange" class="form-control date-input"
                                placeholder="Select Date Range">
                        </div>

                        <!-- Right: Icons (Settings, Refresh, Download, Width Setting) -->
                        <div class="d-flex align-items-center gap-3">
                            <!-- Refresh Icon -->
                            <i class="fas fa-sync-alt refresh-icon" title="Refresh"></i>

                            <!-- Download Icon -->
                            <i class="fas fa-download download-icon" title="Download"></i>



                            <!-- Settings Dropdown -->
                            <div class="dropdown">
                                <i class="fas fa-cog settings-icon" data-bs-toggle="dropdown"></i>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#">Profile Settings</a></li>
                                    <li><a class="dropdown-item" href="#">Table Preferences</a></li>
                                    <li><a class="dropdown-item" href="#">Logout</a></li>
                                </ul>
                            </div>
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
                            <ul class="dropdown-menu w-100">
                                <li><a class="dropdown-item" href="#">Option 1</a></li>
                                <li><a class="dropdown-item" href="#">Option 2</a></li>
                                <li><a class="dropdown-item" href="#">Option 3</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>AWB Number</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>Updated At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (isset($ndrData['data']) && is_array($ndrData['data']) && count($ndrData['data']) > 0)
                                    @foreach ($ndrData['data'] as $ndr)
                                        <tr>
                                            <td>{{ $ndr['awb_number'] ?? 'N/A' }}</td>
                                            <td>{{ $ndr['ndr_status'] ?? 'N/A' }}</td>
                                            <td>{{ $ndr['reason'] ?? 'N/A' }}</td>
                                            <td>{{ $ndr['updated_at'] ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4">
                                            {{ $ndrData['message'] ?? 'No Record Foundss' }}
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>




                    <!-- Pagination -->
                    <nav class="d-flex justify-content-end mt-3">
                        <ul class="pagination">
                            <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">Next</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
@endsection
