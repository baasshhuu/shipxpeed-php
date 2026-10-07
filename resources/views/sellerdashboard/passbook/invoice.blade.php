@extends('layouts.sellerdash')

@section('content')
<div class="pc-container">
    <div class="pc-content">
        <div class="top-toggler shadow-sm">
            <a href="{{ route('seller.passbook') }}" class=" text-decoration-none"><i class="ti ti-dashboard"></i> Passbook</a>
            <a href="{{ route('seller.cod') }}" class="  text-decoration-none"><i class="ti ti-shopping-cart"></i> COD Remittance</a>
            <a href="{{ route('seller.shippingcharge') }}" class="  text-decoration-none"><i class="ti ti-alert-circle"></i> Shipping Charges</a>
            <a href="{{ route('seller.allcharges') }}" class="  text-decoration-none"><i class="ti ti-wallet"></i> All Recharges</a>
            <a href="{{ route('seller.invoice') }}" class="active text-decoration-none"><i class="ti ti-settings"></i> Invoices</a>
            <a href="{{ route('seller.creditnote') }}" class="  text-decoration-none"><i class="ti ti-settings"></i> Credit Receipts</a>

          </div>

        <div class=" mt-4">
          <div class="bg-white rounded shadow-sm p-3">

        <div class="d-flex justify-content-between align-items-center mb-3">

          <div class="d-flex align-items-center gap-2">
              <i class="fas fa-filter filter-icon" data-bs-toggle="offcanvas" href="#offcanvasExample"></i>
              <input type="text" id="daterange" class="form-control date-input" placeholder="Select Date Range">
          </div>

          <div class="d-flex align-items-center gap-3">
              <i class="fas fa-sync-alt refresh-icon" title="Refresh"></i>
              <i class="fas fa-download download-icon" title="Download"></i>
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
                      <button class="btn btn-secondary dropdown-toggle w-100" data-bs-toggle="dropdown">Select Option</button>
                      <ul class="dropdown-menu w-100">
                          <li><a class="dropdown-item" href="#">Option 1</a></li>
                          <li><a class="dropdown-item" href="#">Option 2</a></li>
                          <li><a class="dropdown-item" href="#">Option 3</a></li>
                      </ul>
                  </div>
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
