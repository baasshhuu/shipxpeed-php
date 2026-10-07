 @extends('layouts.sellerdash')

@section('content')
<style>
    .weight-discrepancy-page {
        --wd-primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --wd-info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --wd-dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }
    .weight-discrepancy-page .wd-header {
        background:linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 1rem 0;
        margin-bottom: 1.5rem;
        border-radius: 7px;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.3);
        text-align: center;
    }
    .weight-discrepancy-page .wd-title {
        font-size: 1.3rem;
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        margin-bottom: 0.2rem;
    }
    .weight-discrepancy-page .wd-subtitle {
        font-size: 0.8rem;
        opacity: 0.9;
    }
    .weight-discrepancy-page .wd-filter-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.08);
        padding: 0.7rem 0.8rem;
        margin-bottom: 1.2rem;
       
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .weight-discrepancy-page .wd-filter-form {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr auto;
        gap: 0.7rem;
        align-items: center;
        width: 100%;
    }
    .weight-discrepancy-page .wd-filter-form input[type="text"],
    .weight-discrepancy-page .wd-filter-form input[type="date"] {
        border-radius: 7px;
        border: 1px solid #e3e7ef;
        font-size: 0.82rem;
        padding: 0.75rem 0.7rem;
        background: #f6f8fc;
        transition: border-color 0.2s;
        min-width: 0;
    }
    .weight-discrepancy-page .wd-filter-form input[type="text"]:focus,
    .weight-discrepancy-page .wd-filter-form input[type="date"]:focus {
        border-color: #667eea;
        outline: none;
    }
    .weight-discrepancy-page .wd-filter-form .btn {
        border-radius: 8px;
        font-size: 0.98rem;
        padding: 0.55rem 1.1rem;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.08);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }
    .weight-discrepancy-page .wd-filter-form .btn-dark {
        background: #222;
        border: none;
        color: #fff;
        transition: background 0.2s;
    }
    .weight-discrepancy-page .wd-filter-form .btn-dark:hover {
        background: #444;
    }
    .weight-discrepancy-page .wd-table-card {
        background: #f6f8fc;
        border-radius: 7px;
        box-shadow: 0 8px 32px rgba(102, 126, 234, 0.10);
        /* padding: 1rem 1rem; */
        margin-bottom: 2rem;
        border: 1px solid #e3e7ef;
        overflow-x: auto;
    }
    .weight-discrepancy-page .wd-custom-table {
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        width: 100%;
        background: #fff;
    }
    .weight-discrepancy-page .wd-custom-table thead th {
        background: linear-gradient(90deg, #3576e3 0, #306cc9 100%) !important;
        color: #fff;
        border: none;
       
        padding: 0.7rem 0.7rem;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      
    }
    .weight-discrepancy-page .wd-custom-table tbody td {
        border: none;
        padding: 1rem 0.7rem;
        vertical-align: middle;
        text-align:center;
        border-bottom: 1px solid #f1f3f4;
        font-size: 0.9rem;
        background: #fff;
        color: #2c3e50;
    }
    .weight-discrepancy-page .wd-custom-table tbody tr:last-child td {
        border-bottom: none;
    }
    .weight-discrepancy-page .wd-custom-table tbody tr:hover {
        background: #f8f9ff;
        transform: scale(1.01);
        transition: all 0.2s;
    }
    .weight-discrepancy-page .wd-pagination {
        display: flex;
        justify-content: center;
        margin-top: 1.5rem;
    }
    .weight-discrepancy-page .wd-pagination .pagination {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.08);
    }
    .weight-discrepancy-page .wd-pagination .page-link {
        border: 1px solid #e3e7ef;
        color: #2c3e50;
        padding: 0.6rem 0.9rem;
        font-weight: 500;
        background: #fff;
        transition: all 0.2s;
    }
    .weight-discrepancy-page .wd-pagination .page-link:hover {
        background: #667eea;
        color: #fff;
        border-color: #667eea;
    }
    .weight-discrepancy-page .wd-pagination .page-item.active .page-link {
        background: #667eea;
        border-color: #667eea;
        color: #fff;
        font-weight: 600;
    }
    .weight-discrepancy-page .wd-pagination .page-item.disabled .page-link {
        color: #9ca3af;
        background: #f9fafb;
        border-color: #e3e7ef;
    }
    @media (max-width: 768px) {
        .weight-discrepancy-page .wd-header {
            font-size: 1.1rem;
        }
        .weight-discrepancy-page .wd-table-card {
            padding: 1rem;
        }
        .weight-discrepancy-page .wd-custom-table thead th,
        .weight-discrepancy-page .wd-custom-table tbody td {
            padding: 0.7rem 0.4rem;
            font-size: 0.85rem;
        }
        .weight-discrepancy-page .wd-filter-card {
            padding: 1rem;
        }
        .weight-discrepancy-page .wd-pagination .page-link {
            padding: 0.5rem 0.7rem;
            font-size: 0.85rem;
        }
    }
</style>

<div class="pc-container weight-discrepancy-page" style="background:#646dff26;">
    <div class="pc-content" style="margin-left:12px;">
        <!-- Header -->
        <div class="wd-header">
            <div class="container">
                <h1 class="wd-title">Weight Discrepancy Report</h1>
                <p class="wd-subtitle">View and filter your weight discrepancy transactions</p>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="wd-filter-card" style="width:100%; min-height: 55px; display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.7rem;">
            <form method="GET" action="{{ route('seller.weight.discrepancy') }}" class="wd-filter-form wd-filter-form-row" style="display: flex; flex-wrap: nowrap; gap: 0.7rem; align-items: center; flex:1;">
                <div class="filter-date-group" style="display:flex; gap:0.5rem; flex:2 1 0%">
                    <input type="date" name="start_date" value="{{ request('start_date') }}" placeholder="Start Date" style="flex:1 1 0%; min-width: 100px; width: 100%; max-width: 160px;" />
                    <input type="date" name="end_date" value="{{ request('end_date') }}" placeholder="End Date" style="flex:1 1 0%; min-width: 100px; width: 100%; max-width: 160px;" />
                    <button class="btn filter-mobile-shorten" type="submit" 
                        style="flex:0 0 auto; white-space:nowrap; background:#fff; color:#222; box-shadow: 0 4px 20px rgba(102,126,234,0.18); border: none;">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="{{ route('seller.weight.discrepancy.excel', request()->all()) }}" class="btn filter-mobile-shorten" 
                        style="flex:0 0 auto; white-space:nowrap; background:#fff; color:#43b244; box-shadow: 0 4px 20px rgba(102,126,234,0.18); border: none;">
                        <i class="fa-solid fa-download"></i>
                    </a>
                </div>
                <input type="text" name="awb" value="{{ request('awb') }}" placeholder="Enter AWB Number" style="flex:2 1 0%; min-width: 130px; width: 100%; max-width:200px;" />
               
                <div class="wd-actions-group" style="display:flex; align-items:center; gap:0.5rem;">
                    
                    <div class="wd-discrepancy-card" style="display:flex; align-items:center; justify-content:center; flex-shrink:0; height:38px;">
                        <div class="card text-center shadow-sm" style="min-width: 82px; max-width: 118px; height:38px;padding:0;border-radius:7px; margin-bottom:0;">
                            <div class="card-body bg-light" style="padding:0.27rem 0.5rem; height:38px; display:flex; flex-direction:column; justify-content:center;">
                                <span style="font-size:0.73rem; margin-bottom: 0.06rem;white-space:nowrap;">Discrepancy</span>
                                <span style="font-size:0.7rem;color:#222; margin:0;">{{ $totalDiscrepancies }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <style>
                @media (max-width:600px) {
                    .wd-filter-card {
                        flex-direction: column !important;
                        align-items: stretch !important;
                        gap: 0.45rem !important;
                        min-height: unset !important;
                        padding: 0.7rem 0.45rem !important;
                    }
                    .wd-filter-form-row {
                        flex-direction: column !important;
                        gap: 0.35rem !important;
                        width: 100%;
                        align-items: stretch !important;
                        flex-wrap: nowrap !important;
                    }
                    /* Row for date, filter and download */
                    .filter-date-group {
                        display: flex !important;
                        flex-direction: row !important;
                        gap: 0.16rem !important;
                        width: 100% !important;
                        align-items: center !important;
                        justify-content: space-between !important;
                        margin-bottom: 0.2rem !important;
                    }
                    .filter-date-group input[type="date"] {
                        width: 27vw !important;
                        min-width: 65px !important;
                        font-size: 0.98rem;
                        max-width: 110px;
                    }
                    /* Shrink and center filter & download btns in their divs */
                    .filter-mobile-shorten {
                        width: 33px !important;
                        min-width: 30px !important;
                        max-width: 36px !important;
                        height: 32px !important;
                        padding: 0 !important;
                        font-size: 1.13rem !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        margin: 0 2px !important;
                        border-radius: 6px !important;
                    }
                    .filter-date-group .btn,
                    .filter-date-group a.btn {
                        display: flex !important;
                        align-items: center;
                        justify-content: center;
                    }
                    /* AWB input takes full width under date/buttons row */
                    .wd-filter-form-row input[type="text"] {
                        width: 100% !important;
                        min-width: 0 !important;
                        max-width: 100vw !important;
                        flex-basis: 100% !important;
                        margin: 0.18rem 0 0.12rem 0 !important;
                        font-size: 0.97rem !important;
                    }
                    /* Discrepancy card remains below in mobile */
                    .wd-actions-group {
                        width: 100% !important;
                        flex-direction: row !important;
                        gap: 0.38rem !important;
                        justify-content: space-between !important;
                        align-items: center !important;
                        margin-top: 0.09rem !important;
                    }
                    .wd-discrepancy-card .card {
                        width: 100% !important;
                        min-width: 0 !important;
                        max-width: 100vw !important;
                        font-size: 0.93rem !important;
                    }
                }
            </style>
        </div>

        <!-- Table Card -->
        <div class="wd-table-card">
            <div class="table-responsive">
                <form method="POST" action="#">
                    @csrf
                    <table class="wd-custom-table">
                        <thead>
                            <tr>
                                <th>AWB</th>
                                <th>Courier</th>
                                <th>Mentioned Weight</th>
                                <th>Charged Weight</th>
                                <th>Weight Mismatch</th>
                                <th>Dispute Charges</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $index => $row)
                                <tr>
                                    <td>{{ $row['awb'] }}</td>
                                    <td>{{ $row['courier'] }}</td>
                                    <td>{{ $row['mentionedweight'] ?? 'N/A' }}</td>
                                    <td>{{ $row['chargedweight'] ?? 'N/A' }}</td>
                                    <td>{{ $row['weightmissmatched'] ?? 'N/A' }}</td>
                                    <td>{{ $row['weightdisputecharges'] ?? '0' }}</td>
                                    <input type="hidden" name="sellers[{{ $index }}][seller_id]" value="{{ $row['seller_id'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][amount]" value="{{ $row['weightdisputecharges'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_id]" value="{{ $row['id'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_awb]" value="{{ $row['awb'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_courier]" value="{{ $row['courier'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_mentionedweight]" value="{{ $row['mentionedweight'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_chargedweight]" value="{{ $row['chargedweight'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_weightmissmatched]" value="{{ $row['weightmissmatched'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_seller_name]" value="{{ $row['seller_name'] }}">
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No weight discrepancies found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
            </div>
            
            <!-- Pagination -->
            <div class="wd-pagination">
                {{ $disputes->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>
@endsection


{{-- @extends('layouts.sellerdash')

@section('content')
<div class="container-fluid px-4">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Weight Discrepancy Report</h5>
        </div>
        <div class="card-body">

            <form method="GET" action="{{ route('seller.weight.discrepancy') }}" class="mb-4" style="padding-left: 200px;">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">AWB Number</label>
                        <input type="text" name="awb" value="{{ request('awb') }}" class="form-control" placeholder="Enter AWB Number">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-dark w-100">Filter</button>
                    </div>
                </div>
            </form>

            <div class="row mb-4" style="padding-left: 190px;">
                <div class="col-md-12">
                    <div class="card text-center shadow-sm">
                        <div class="card-body bg-light">
                            <h6>Total Weight Discrepancies</h6>
                            <h4>{{ $totalDiscrepancies }}</h4>
                        </div>
                    </div>
                </div>
              
            </div>

            <div class="mb-3 d-flex gap-2">
                <a href="#" class="btn btn-success">
                    <i class="fa fa-file-excel"></i> Download Excel
                </a>
                <a href="{{ route('seller.weight.discrepancy') }}" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
            </div>

            <form method="POST" action="#">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>AWB</th>
                                <th>Courier</th>
                                <th>Mentioned Weight</th>
                                <th>Charged Weight</th>
                                <th>Weight Mismatch</th>
                                <th>Dispute Charges</th>
                           
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $index => $row)
                                <tr>
                                    <td>{{ $row['awb'] }}</td>
                                    <td>{{ $row['courier'] }}</td>
                                    <td>{{ $row['mentionedweight'] ?? 'N/A' }}</td>
                                    <td>{{ $row['chargedweight'] ?? 'N/A' }}</td>
                                    <td>{{ $row['weightmissmatched'] ?? 'N/A' }}</td>
                                    <td>{{ $row['weightdisputecharges'] ?? '0' }}</td>
                            

                                    <input type="hidden" name="sellers[{{ $index }}][seller_id]" value="{{ $row['seller_id'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][amount]" value="{{ $row['weightdisputecharges'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_id]" value="{{ $row['id'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_awb]" value="{{ $row['awb'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_courier]" value="{{ $row['courier'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_mentionedweight]" value="{{ $row['mentionedweight'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_chargedweight]" value="{{ $row['chargedweight'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_weightmissmatched]" value="{{ $row['weightmissmatched'] }}">
                                    <input type="hidden" name="sellers[{{ $index }}][weight_seller_name]" value="{{ $row['seller_name'] }}">
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No weight discrepancies found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="text-end mt-3">
                </div>
            </form>
        </div>
    </div>
</div>
@endsection --}}












