@extends('layouts.app')

@section('content')
    <div class="pc-container">
    

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
                    <table class="table table-bordered table-hover text-center">
                        <thead class="table-dark">
                             <tr>
                                <th>Month</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                       <tbody>
                   @foreach($months as $month)
                    <tr>
                        <td>{{ $month }}</td>
                        <td>
                            <a href="{{ route('all.seller.monthly.report', ['month' => $month]) }}" class="btn btn-primary btn-sm">
                                View Details
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>

                    </table>

                 
                </div>

            </div>
        </div>
    </div>


@endsection


