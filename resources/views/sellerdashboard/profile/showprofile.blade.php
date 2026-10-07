@extends('layouts.sellerdash')

@section('content')
    <div class="pc-content"><!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0)">Users</a></li>
                            <li class="breadcrumb-item" aria-current="page">Account Profile</li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0">Account Profile</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- [ breadcrumb ] end --><!-- [ Main Content ] start -->
        <div class="row">

            <div class="col-lg-7 col-xxl-12">

                <div class="card">
                    <div class="card-header">
                        <h5>Personal Details</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0 pt-0">
                                <div class="row">
                                    <div class="col-md-12">
                                        <p class="mb-1 text-muted">Full Name</p>
                                        <p class="mb-0">{{ $sellerName ?? 'Seller Name Not Found' }}</p>
                                    </div>

                                </div>
                            </li>
                            <li class="list-group-item px-0">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted">Phone</p>
                                        <p class="mb-0">{{ $seller->phone_number }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted">Country</p>
                                        <p class="mb-0">{{ $selleraddress->country ?? 'Country not available' }}</p>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item px-0">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted">Email</p>
                                        <p class="mb-0">{{ $seller->email ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-muted">Zip Code</p>
                                        <p class="mb-0">{{ $selleraddress->pincode ?? 'N/A' }}</p>

                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item px-0 pb-0">
                                <p class="mb-1 text-muted">Address</p>
                                <p class="mb-0">{{ $selleraddress->address_line ?? 'N/A' }}</p>
                            </li>
                        </ul>
                    </div>
                </div>


            </div>

            <div class="col-lg-7 col-xxl-12">

                <div class="card">
                    <div class="card-header">
                        <h5>Bank Details</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0 pt-0">
                                <div class="row">
                                    <div class="col-md-12">
                                        <p class="mb-1 text-muted">Account Holder Name</p>
                                        <p class="mb-0"><p class="mb-0">{{ $sellerbankdetails->account_holder_name ?? 'Account Holder Name not available' }}</p>
                                    </p>
                                    <div class="col-md-12">
                                        <p class="mb-1 text-muted">Account Holder Name</p>
                                        <p class="mb-0"><p class="mb-0">{{ $sellerbankdetails->account_holder_name ?? 'Account Holder Name not available' }}</p>
                                    </p>
                                    </div>
                                </div>
                            </li>

                        </ul>
                    </div>
                </div>


            </div>
        </div>
    </div>
@endsection
