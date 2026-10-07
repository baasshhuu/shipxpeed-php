@extends('layouts.sellerdash')

@section('content')
    <style>
        .profile-header {
            background:#646dff26;
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            margin: 12px;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23ffffff" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .profile-image-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-image {
            width: 80px;
            height: 80px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .profile-image:hover {
            transform: scale(1.05);
            border-color: rgba(255, 255, 255, 0.6);
        }
        
        .stat-card {
            border: none;
            /* border-radius: 16px; */
            background: white;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            /* background: linear-gradient(135deg, #667eea, #764ba2); */
            color: white;
            font-size: 16px;
        }
        
        .nav-pills-custom {
            background: white;
            border-radius: 50px;
            padding: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .nav-pills-custom .nav-link {
            border-radius: 50px;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 24px;
            transition: all 0.3s ease;
            border: none;
            background: transparent;
        }
        
        .nav-pills-custom .nav-link.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .info-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background: white;
        }
        
        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
        }
        
        .info-card .card-header {
            background: linear-gradient(135deg, #f8f9ff, #e3e7ff);
            border: none;
            border-radius: 16px 16px 0 0;
            font-weight: 600;
            color: #4c63d2;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 7px;
            color: white;
            padding: 8px 16px;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .badge-custom {
            border-radius: 20px;
            padding: 8px 16px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            border: none;
            border-radius: 20px 20px 0 0;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .profile-welcome {
           
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .welcome-text {
            font-size: 22px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        
        .welcome-subtext {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 0;
        }
    </style>
    
    <div class="">
        <div class="pc-container">
            <div class="pc-content" style="padding-top:0px;">
                <section class="min-vh-100 bg-light">
                    <div class="container py-3">
                        <!-- Modern Profile Header -->
                        <div class="profile-header text-white mb-4 p-4">
                            <div class="profile-welcome p-0">
                                <div class="row align-items-center flex-column-reverse flex-md-row">
                                    <!-- Text Left -->
                                    <div class="col-12 col-md-8 text-center text-md-start">
                                        <h1 class="welcome-text mb-2 mb-md-3">Welcome back, {{ $seller->name ?? 'Seller' }}!</h1>
                                        <p class="welcome-subtext fs-6 mb-0 mb-md-2">Manage your profile and grow your business</p>
                                    </div>
                                    <!-- Image Right -->
                                    <div class="col-12 col-md-4 text-center mb-3 mb-md-0 d-flex justify-content-center justify-content-md-end">
                                        <div class="profile-image-container position-relative">
                                            @if ($seller->profile && file_exists(public_path('uploads/seller_profiles/' . $seller->profile)))
                                                <img src="{{ asset('uploads/seller_profiles/' . $seller->profile) }}"
                                                    alt="Seller Image"
                                                    class="profile-image rounded-circle shadow-lg border border-white border-3"
                                                    style="width:100px;height:100px;object-fit:cover;" />
                                            @else
                                                <img src="{{ asset('default-profile.png') }}"
                                                    alt="Default Image"
                                                    class="profile-image rounded-circle shadow-lg border border-white border-3"
                                                    style="width:100px;height:100px;object-fit:cover;" />
                                            @endif
                                            <!-- Optionally, add a status icon or badge here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-stretch gap-2 mb-2 flex-row flex-nowrap stat-row-premium" style="margin-left: 12px;margin-right: 12px;">
                            <div class="flex-fill stat-responsive-card">
                                <div class="stat-card card h-100 w-100 shadow-lg border-0 stat-premium" style="min-height:88px;">
                                    <div class="card-body py-2 px-2 d-flex flex-column align-items-center" style="height:100%;">
                                        <div class="stat-icon mb-2" style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;font-size:1.7rem;color:#667eea;">
                                            <i class="fas fa-wallet" style="width:38px;height:38px;text-align:center;line-height:38px;"></i>
                                        </div>
                                        <h6 class="text-muted mb-1 stat-label">Wallet Balance</h6>
                                        <h4 class="fw-bold text-dark mb-0 stat-amount">₹{{ number_format($totalAmount, 0) }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-fill stat-responsive-card" style="min-width:0;">
                                <div class="stat-card card h-100 w-100 shadow-lg border-0 stat-premium" style="min-height:88px;">
                                    <div class="card-body py-2 px-2 d-flex flex-column align-items-center" style="height:100%;">
                                        <div class="stat-icon mb-2" style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;font-size:1.7rem;color:#764ba2;">
                                            <i class="fas fa-user-check" style="width:38px;height:38px;text-align:center;line-height:38px;"></i>
                                        </div>
                                        <h6 class="text-muted mb-1 stat-label">Profile Status</h6>
                                        @if ($seller->kyc_status == 1)
                                            <span class="badge-custom badge bg-success stat-badge">✓ Verified</span>
                                        @else
                                            <span class="badge-custom badge bg-warning text-dark stat-badge">⚠ Pending</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex-fill stat-responsive-card" style="min-width:0;">
                                <div class="stat-card card h-100 w-100 shadow-lg border-0 stat-premium" style="min-height:88px;">
                                    <div class="card-body py-2 px-2 d-flex flex-column align-items-center" style="height:100%;">
                                        <div class="stat-icon mb-2" style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;font-size:1.7rem;color:#ffbb54;">
                                            <i class="fa-solid fa-building" style="width:38px;height:38px;text-align:center;line-height:38px;"></i>
                                            
                                        </div>
                                        <h6 class="text-muted mb-1 stat-label">Account Type</h6>
                                        <div class="d-flex align-items-center justify-content-center w-100 stat-completion-wrap">
                                            <span class="badge-custom badge {{ $seller->user_type == 1 ? 'bg-info' : 'bg-warning' }}">
                                                {{ $seller->user_type == 1 ? 'Individual' : 'Business' }}
                                            </span>
                                           
                                        </div>

                                        
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        <style>
                        .stat-premium {
                            min-height: 88px;
                            max-height: 125px;
                        }
                        .stat-row-premium {
                            flex-wrap: nowrap;
                            /* width: 100%; */
                        }
                        @media (max-width: 991.98px) {
                            .stat-premium {
                                min-height: 82px;
                                max-height: 100px;
                            }
                        }
                        @media (max-width: 767.98px) {
                            .stat-row-premium {
                                flex-direction: row !important;
                                flex-wrap: nowrap !important;
                                width: 100%;
                                gap: 0.36rem;
                                /* overflow-x: auto;  -- REMOVED for no scrolling */
                            }
                            .stat-responsive-card {
                                min-width: 0 !important;
                                max-width: none !important;
                                width: 100% !important;
                                margin-left: 0 !important;
                                flex: 1 1 0 !important;
                                display: flex;
                            }
                            .stat-row-premium > .stat-responsive-card:first-child {
                                margin-left: 12px !important;
                            }
                            .stat-card .card-body {
                                padding: 0.55rem 0.38rem !important;
                            }
                            .stat-icon {
                                width: 32px !important;
                                height: 32px !important;
                                font-size: 1.1rem !important;
                            }
                            .stat-icon i {
                                width: 32px !important;
                                height: 32px !important;
                                font-size: 1.1rem !important;
                            }
                            .stat-label,
                            .stat-badge,
                            .stat-amount,
                            .stat-complete {
                                font-size: 0.86rem !important;
                            }
                            .badge-custom {
                                font-size: 0.8rem !important;
                                padding: 0.12em 0.5em !important;
                            }
                            .stat-premium {
                                min-height: 87px !important;
                                max-height: 87px !important;
                                height: 87px !important;
                                display: flex;
                                align-items: center;
                            }
                        }
                        </style>

                        <!-- Modern Navigation Tabs -->
                        <div class="tab-content" id="pills-tabContent" style="margin-left: 12px;margin-right: 12px;">
                            <!-- My Profile Tab -->
                            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-8 col-md-12">
                                        <!-- Contact Details Card -->
                                        <div class="info-card card mb-4">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-user text-primary me-2"></i>
                                                    <span class="fw-semibold">Contact Information</span>
                                                </div>
                                                <button class="btn-edit btn btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editProfileModal">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center mb-3">
                                                            <div class=" rounded-circle p-2 me-3">
                                                                <i class="fas fa-user text-primary"></i>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block">Full Name</small>
                                                                <span class="fw-medium">{{ $seller->name ?? '--' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center mb-3">
                                                            <div class=" rounded-circle p-2 me-3">
                                                                <i class="fas fa-envelope text-primary"></i>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block">Email Address</small>
                                                                <span class="fw-medium">{{ $seller->email ?? '--' }}</span>
                                                                @if (!$seller->email_verified_at)
                                                                    <span class="badge bg-danger ms-2">Unverified</span>
                                                                @else
                                                                    <i class="fas fa-check-circle text-success ms-2"></i>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center mb-3">
                                                            <div class=" rounded-circle p-2 me-3">
                                                                <i class="fas fa-phone text-primary"></i>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block">Phone Number</small>
                                                                <span class="fw-medium">{{ $seller->phone_number ?? '--' }}</span>
                                                                @if ($seller->phone_number)
                                                                    <i class="fas fa-check-circle text-success ms-2"></i>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center">
                                                            <div class=" rounded-circle p-2 me-3">
                                                                <i class="fas fa-calendar text-primary"></i>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block">Member Since</small>
                                                                <span class="fw-medium">{{ $seller->created_at ? $seller->created_at->format('F j, Y') : '--' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Edit Profile Modal -->
                                        <div class="modal fade" id="editProfileModal" tabindex="-1"
                                            aria-labelledby="editProfileModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                {{-- <form method="POST" action="{{ route('seller.update.profile') }}" > --}}
                                                    <form method="POST" action="{{ route('seller.update.profile') }}" enctype="multipart/form-data">

                                                    @csrf
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editProfileModalLabel"><i
                                                                    class="fas fa-edit"></i> Edit Profile</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label for="name" class="form-label">Name</label>
                                                                    <input type="text" name="name"
                                                                        class="form-control" value="{{ $seller->name }}"
                                                                        required>
                                                                </div>
                                                             <div class="col-md-6">
                                                                    <label for="profile" class="form-label">Profile Image</label>
                                                                    <input type="file" name="profile" class="form-control">
                                                                    @if($seller->profile)
                                                                        <small>Current: <img src="{{ asset('storage/profile_images/' . $seller->profile) }}" width="50"></small>
                                                                    @endif
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label for="email" class="form-label">Email</label>
                                                                    <input type="email" name="email"
                                                                        class="form-control" value="{{ $seller->email }}"
                                                                        required>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label for="phone_number" class="form-label">Phone
                                                                        Number</label>
                                                                    <input type="text" name="phone_number"
                                                                        class="form-control"
                                                                        value="{{ $seller->phone_number }}" required>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label for="user_type" class="form-label">User
                                                                        Type</label>
                                                                    <select name="user_type" class="form-select" required>
                                                                        <option value="1"
                                                                            {{ $seller->user_type == 1 ? 'selected' : '' }}>
                                                                            INDIVIDUAL</option>
                                                                        <option value="2"
                                                                            {{ $seller->user_type == 2 ? 'selected' : '' }}>
                                                                            BUSINESS</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save
                                                                Changes</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>


                                        <!-- Address Details Card -->
                                        <div class="info-card card mb-4">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-map-marker-alt text-warning me-2"></i>
                                                    <span class="fw-semibold">Address Information</span>
                                                </div>
                                                <button class="btn-edit btn btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editAddressModal">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                @if ($address)
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class=" rounded-circle p-2 me-3">
                                                                    <i class="fas fa-city text-warning"></i>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">City</small>
                                                                    <span class="fw-medium">{{ optional($address->city)->name ?: 'Not specified' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class=" rounded-circle p-2 me-3">
                                                                    <i class="fas fa-map text-warning"></i>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">State</small>
                                                                    <span class="fw-medium">{{ optional($address->state)->name ?: 'Not specified' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class=" rounded-circle p-2 me-3">
                                                                    <i class="fas fa-globe text-warning"></i>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">Country</small>
                                                                    <span class="fw-medium">{{ $address->country ?: 'Not specified' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class=" rounded-circle p-2 me-3">
                                                                    <i class="fas fa-hashtag text-warning"></i>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">GST Number</small>
                                                                    <span class="fw-medium">{{ $seller->gst_no ?: 'Not provided' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="d-flex align-items-start">
                                                                <div class=" rounded-circle p-2 me-3">
                                                                    <i class="fas fa-home text-warning"></i>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">Full Address</small>
                                                                    <span class="fw-medium">{{ $address->address_line ?: 'Not provided' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-center py-4">
                                                        <div class="bg-light rounded-circle p-3 d-inline-block mb-3">
                                                            <i class="fas fa-map-marker-alt text-warning fa-2x"></i>
                                                        </div>
                                                        <h6 class="text-muted">No address information available</h6>
                                                        <p class="text-muted small">Add your address details to complete your profile</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <!-- Address Edit Modal -->
                                        <div class="modal fade" id="editAddressModal" tabindex="-1"
                                            aria-labelledby="editAddressModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form action="{{ route('seller.update.address') }}" method="POST">
                                                    @csrf
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-warning">
                                                            <h5 class="modal-title" id="editAddressModalLabel">Edit
                                                                Address Details</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="state_id" class="form-label">State</label>
                                                                <select class="form-select" name="state_id"
                                                                    id="state_id" required>
                                                                    <option value="">Select State</option>
                                                                    @foreach ($states as $state)
                                                                        <option value="{{ $state->id }}"
                                                                            {{ $address && $address->state_id == $state->id ? 'selected' : '' }}>
                                                                            {{ $state->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="city_id" class="form-label">City</label>
                                                                <select class="form-select" name="city_id" id="city_id"
                                                                    required>
                                                                    <option value="">Select City</option>
                                                                    @foreach ($cities as $city)
                                                                        <option value="{{ $city->id }}"
                                                                            {{ $address && $address->city_id == $city->id ? 'selected' : '' }}>
                                                                            {{ $city->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="country" class="form-label">Country</label>
                                                                <input type="text" class="form-control" name="country"
                                                                    value="{{ $address->country ?? '' }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="pincode" class="form-label">Pincode</label>
                                                                <input type="text" class="form-control" name="pincode"
                                                                    value="{{ $address->pincode ?? '' }}" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="address_line" class="form-label">Full
                                                                    Address</label>
                                                                <textarea class="form-control" name="address_line" rows="3">{{ $address->address_line ?? '' }}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-warning">Update
                                                                Address</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>


                                        <!-- Bank Details Card -->
                                        <div class="info-card card mb-4">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fas fa-university text-success me-2"></i>
                                                    <span class="fw-semibold">Banking Information</span>
                                                </div>
                                                <button class="btn-edit btn btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editBankDetails">
                                                    <i class="fas fa-edit me-1"></i> Edit
                                                </button>
                                            </div>
                                            <div class="card-body p-4">
                                                @if ($bankDetails)
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class="bg-light rounded-circle p-2 me-3">
                                                                    <i class="fas fa-user text-success"></i>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">Account Holder</small>
                                                                    <span class="fw-medium">{{ $bankDetails->account_holder_name ?? 'Not provided' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class="bg-light rounded-circle p-2 me-3">
                                                                    <i class="fas fa-university text-success"></i>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">Bank Name</small>
                                                                    <span class="fw-medium">{{ $bankDetails->bank_name ?? 'Not provided' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class="bg-light rounded-circle p-2 me-3">
                                                                    <i class="fas fa-code text-success"></i>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">IFSC Code</small>
                                                                    <span class="fw-medium">{{ $bankDetails->ifsc_code ?? 'Not provided' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center mb-3">
                                                                <div class="bg-light rounded-circle p-2 me-3">
                                                                    <i class="fas fa-credit-card text-success"></i>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">Account Type</small>
                                                                    <span class="fw-medium">{{ $bankDetails->account_type ?? 'Not specified' }}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="d-flex align-items-center">
                                                                <div class="bg-light rounded-circle p-2 me-3">
                                                                    <i class="fas fa-hashtag text-success"></i>
                                                                </div>
                                                                <div>
                                                                    <small class="text-muted d-block">Account Number</small>
                                                                    <span class="fw-medium">
                                                                        @if($bankDetails->account_number)
                                                                            ****{{ substr($bankDetails->account_number, -4) }}
                                                                        @else
                                                                            Not provided
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="text-center py-4">
                                                        <div class="bg-light rounded-circle p-3 d-inline-block mb-3">
                                                            <i class="fas fa-university text-success fa-2x"></i>
                                                        </div>
                                                        <h6 class="text-muted">No banking information available</h6>
                                                        <p class="text-muted small">Add your bank details for payments and withdrawals</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <!-- Address Edit Modal -->
                                        <div class="modal fade" id="editBankDetails" tabindex="-1"
                                            aria-labelledby="editbankdetailsLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form action="{{ route('seller.update.bank') }}" method="POST">
                                                    @csrf
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-warning">
                                                            <h5 class="modal-title" id="editbankdetailsLabel">Edit
                                                                Bank Details</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">

                                                            <div class="mb-3">
                                                                <label for="account_holder_name" class="form-label">Acount
                                                                    Holder Name</label>
                                                                <input type="text" class="form-control"
                                                                    name="account_holder_name"
                                                                    value="{{ $bankDetails->account_holder_name ?? '' }}"
                                                                    required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="bank_name" class="form-label">Bank
                                                                    Name</label>
                                                                <input type="text" class="form-control"
                                                                    name="bank_name"
                                                                    value="{{ $bankDetails->bank_name ?? '' }}" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label for="account_number" class="form-label">Account
                                                                    Number</label>
                                                                <input type="text" class="form-control"
                                                                    name="account_number"
                                                                    value="{{ $bankDetails->account_number ?? '' }}"
                                                                    required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="ifsc_code" class="form-label">IFSC
                                                                    Code</label>
                                                                <input type="text" class="form-control"
                                                                    name="ifsc_code"
                                                                    value="{{ $bankDetails->ifsc_code ?? '' }}" required>
                                                            </div>
                                                            <select class="form-control" id="account_type"
                                                                name="account_type">
                                                                <option value="savings"
                                                                    {{ isset($bankDetails) && $bankDetails->account_type == 'savings' ? 'selected' : '' }}>
                                                                    Savings</option>
                                                                <option value="current"
                                                                    {{ isset($bankDetails) && $bankDetails->account_type == 'current' ? 'selected' : '' }}>
                                                                    Current</option>
                                                            </select>


                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-warning">Update
                                                                Address</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- KYC Status Sidebar -->
                                    <div class="col-lg-4 col-md-12">
                                        <div class="info-card card mb-4">
                                            <div class="card-header text-center">
                                                <div class=" rounded-circle p-3 d-inline-block">
                                                    <i class="fas fa-id-card text-info fa-2x"></i>
                                                </div>
                                                <h6 class="mb-0 fw-semibold">KYC Verification</h6>
                                            </div>
                                            <div class="card-body p-4 text-center">
                                                <div class="mb-4">
                                                    @if ($seller->kyc_status == 1)
                                                        <div class=" bg-opacity-10 rounded-circle p-3 d-inline-block">
                                                            <i class="fas fa-check-circle text-success fa-2x"></i>
                                                        </div>
                                                        <h6 class="text-success fw-semibold">Verified Successfully</h6>
                                                        <p class="text-muted small mb-0">Your identity has been verified</p>
                                                    @else
                                                        <div class=" bg-opacity-10 rounded-circle p-3 d-inline-block">
                                                            <i class="fas fa-clock text-warning fa-2x"></i>
                                                        </div>
                                                        <h6 class="text-warning fw-semibold">Verification Pending</h6>
                                                        <p class="text-muted small mb-0">Complete your KYC to unlock all features</p>
                                                    @endif
                                                </div>
                                                
                                                @if (isset($kyc) && $seller->kyc_status == 1)
                                                    <div class="border-top pt-3">
                                                        <small class="text-muted d-block">Verified on</small>
                                                        <span class="fw-medium">{{ $kyc->created_at->format('F j, Y') }}</span>
                                                    </div>
                                                @endif
                                                
                                                @if ($seller->kyc_status != 1)
                                                    <button class="btn btn-warning rounded-pill px-4 mt-3">
                                                        <i class="fas fa-id-card me-2"></i>Start KYC
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Quick Stats -->
                                        <div class="info-card card">
                                            <div class="card-header">
                                                <i class="fas fa-chart-bar text-primary me-2"></i>
                                                <span class="fw-semibold">Quick Stats</span>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <span class="text-muted">Profile Completion</span>
                                                    <span class="fw-bold">75%</span>
                                                </div>
                                                <div class="progress mb-3" style="height: 8px;">
                                                    <div class="progress-bar" style="width: 75%; background: linear-gradient(90deg, #667eea, #764ba2);"></div>
                                                </div>
                                                
                                                <div class="row text-center">
                                                    <div class="col-6">
                                                        <div class="border-end">
                                                            <h6 class="fw-bold text-primary mb-0">₹{{ number_format($totalAmount, 0) }}</h6>
                                                            <small class="text-muted">Wallet</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <h6 class="fw-bold text-success mb-0">{{ $seller->kyc_status == 1 ? 'Verified' : 'Pending' }}</h6>
                                                        <small class="text-muted">Status</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- Change Password Tab -->
                            <div class="tab-pane fade" id="password" role="tabpanel">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8 col-md-10">
                                        <div class="info-card card">
                                            <div class="card-header text-center">
                                                <div class="bg-light rounded-circle p-3 d-inline-block mb-3">
                                                    <i class="fas fa-shield-alt text-primary fa-2x"></i>
                                                </div>
                                                <h5 class="mb-0 fw-semibold">Security Settings</h5>
                                                <p class="text-muted mb-0">Update your password to keep your account secure</p>
                                            </div>
                                            <div class="card-body p-4">
                                                @if (session('success'))
                                                    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                                                        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                    </div>
                                                @endif

                                                @if (session('error'))
                                                    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                                                        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                    </div>
                                                @endif

                                                <form method="POST" action="{{ route('seller.change.password') }}">
                                                    @csrf
                                                    <div class="mb-4">
                                                        <label class="form-label fw-medium">
                                                            <i class="fas fa-lock text-muted me-2"></i>Current Password
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="password" name="current_password" class="form-control" 
                                                                placeholder="Enter your current password" required>
                                                            <button type="button" class="btn btn-outline-secondary toggle-password">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-4">
                                                        <label class="form-label fw-medium">
                                                            <i class="fas fa-key text-muted me-2"></i>New Password
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="password" name="new_password" class="form-control" 
                                                                placeholder="Enter your new password" required>
                                                            <button type="button" class="btn btn-outline-secondary toggle-password">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                        <small class="text-muted">Password must be at least 8 characters long</small>
                                                    </div>
                                                    
                                                    <div class="mb-4">
                                                        <label class="form-label fw-medium">
                                                            <i class="fas fa-check-double text-muted me-2"></i>Confirm New Password
                                                        </label>
                                                        <div class="input-group">
                                                            <input type="password" name="new_password_confirmation" class="form-control"
                                                                placeholder="Confirm your new password" required>
                                                            <button type="button" class="btn btn-outline-secondary toggle-password">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-check mb-4 p-3 bg-light rounded-3">
                                                        <input class="form-check-input" type="checkbox" name="logout_other_devices"
                                                            id="logoutOtherDevices">
                                                        <label class="form-check-label fw-medium" for="logoutOtherDevices">
                                                            <i class="fas fa-sign-out-alt text-warning me-2"></i>
                                                            Logout from other devices
                                                        </label>
                                                        <small class="text-muted d-block mt-1">This will sign you out from all other devices for security</small>
                                                    </div>
                                                    
                                                    <button type="submit" class="btn btn-lg w-100" 
                                                        style="background: linear-gradient(135deg, #667eea, #764ba2); border: none; color: white; border-radius: 12px; padding: 14px;">
                                                        <i class="fas fa-shield-alt me-2"></i>Update Password
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <script>
        // Password toggle functionality
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Profile image upload with preview
        $('#profile-image').change(function () {
            var formData = new FormData();
            formData.append('image', this.files[0]);

            $.ajax({
                url: "{{ route(Auth::guard('seller')->check() ? 'seller.update-image' : 'web.update-image') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (result) {
                    if (result.status) {
                        toastr.success(result.message);
                        $('.profile-img').attr('src', result.image);
                    } else {
                        toastr.error(result.message);
                    }
                }
            });
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                if (alert.classList.contains('show')) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 5000);
    </script>
@endsection
