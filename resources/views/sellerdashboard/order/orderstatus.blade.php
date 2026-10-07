@extends('layouts.sellerdash')

@section('content')
<div class="pc-content">

    <div class="toggle-button border block">
      <a href="dashboard.html">Analytics</a>
      <a href="" class="active">Order Status</a>
    </div>

    <!-- [ breadcrumb ] end -->
    <!-- [ Main Content ] start -->
    <div class="row">



      <div class="col-md-12 col-xl-6">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="mb-0">Wallet Transaction</h5>
          <ul class="nav nav-pills justify-content-end mb-0" id="chart-tab-tab" role="tablist"></ul>
        </div>
        <div class="card tbl-card">
          <div class="card-body">
            <div class="table-responsive">
              <div class="no-data">No Data Found</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Shipment -->
      <div class="col-md-12 col-xl-6">
        <h5 class="mb-3">Shipment</h5>
        <div class="card tbl-card">
          <div class="card-body">
            <div class="table-responsive">

            </div>
            <!-- Shipment Status Section -->
            <div class="shipment-status">
              <span class="badge badge-delivered">Delivered</span>
              <span class="badge badge-rto">RTO</span>
              <span class="badge badge-ndr">NDR</span>
            </div>
          </div>
        </div>
      </div>



    </div>
  </div>
@endsection
