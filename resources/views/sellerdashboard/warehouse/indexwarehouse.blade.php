




@extends('layouts.sellerdash')

@section('content')
    <style>
        .warehouse-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.15rem 0; /* even tighter banner */
            margin-bottom: 0.35rem;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(102, 126, 234, 0.08);
        }
        
        .warehouse-title {
            font-size: 1.05rem; /* tighter */
            font-weight: 700;
            margin-bottom: 0.12rem;
            text-shadow: 0 1px 1px rgba(0,0,0,0.05);
        }
        
        .warehouse-subtitle {
            font-size: 0.72rem;
            opacity: 0.95;
            font-weight: 300;
        }
        
        .add-warehouse-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 8px;
            padding: 8px 16px; /* smaller */
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: all 0.25s ease;
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.18);
        }
        
        .add-warehouse-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .warehouse-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            border: none;
            overflow: hidden;
            transition: all 0.25s ease;
        }
        
        .warehouse-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .warehouse-table {
            margin: 0;
            border: none;
        }
        
        .warehouse-table thead th {
            background: linear-gradient(90deg, #3576e3 0, #306cc9 100%) !important;
            color: white;
            border: none;
            padding: 0.6rem; /* compact */
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.4px;
        }
        
        .warehouse-table tbody tr {
            transition: all 0.3s ease;
            border: none;
        }
        
        .warehouse-table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            transform: scale(1.01);
        }
        
        .warehouse-table tbody td {
            padding: 0.6rem; /* compact */
            vertical-align: middle;
            border: none;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .action-btn {
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.85rem;
            font-weight: 500;
            margin: 0 2px;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-edit {
            background:linear-gradient(135deg, #dae5e7, #d3d3d3);
            color: black;
            font-size: 16px;
        }
        
        .btn-edit:hover {
            background: linear-gradient(135deg, #138496, #117a8b);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
            color: white;
        }
        
        .btn-delete {
            background:linear-gradient(135deg, #dae5e7, #d3d3d3);
            color: black;
            font-size: 16px;
        }
        
        .btn-delete:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 0.6rem; /* compact */
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 0.6rem;
            transition: all 0.25s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .stats-number {
            font-size: 1.1rem; /* smaller */
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.25rem;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        
        @media (max-width: 768px) {
            .warehouse-title {
                font-size: 2rem;
            }
            
            .warehouse-table {
                font-size: 0.85rem;
            }
            
            .action-btn {
                padding: 4px 8px;
                font-size: 0.75rem;
            }
        }
    </style>

    <div class="pc-container passbook-page">
        <div class="pc-content mt-3">
            <div class="warehouse-main-card responsive-card">
                <!-- Header Section (smaller) -->
                <div class="warehouse-header text-center responsive-header">
                    <h1 class="warehouse-title ">
                        <i class="ti ti-building-warehouse me-2"></i>
                        Warehouse Management
                    </h1>
                    <p class="warehouse-subtitle">Manage your warehouse locations and inventory centers</p>
                </div>

                <!-- Stats Cards (smaller & responsive row) -->
                <style>
                    .mini-premium-header-row {
                        width: 100%;
                    }
                    .mini-premium-stats-row {
                        display: flex;
                        gap: 0.64rem;
                        flex-wrap: wrap;
                        justify-content: flex-start;
                        align-items: stretch;
                        margin-bottom: 0 !important;
                        flex: 1 1 auto;
                    }
                    .mini-premium-stats-card {
                        background: linear-gradient(109deg, #fff 85%, #f7f8fc 100%);
                        border: 1px solid #e6eafe;
                        border-radius: 0.72rem;
                        box-shadow: 0 1px 5px rgba(134, 143, 187, 0.07);
                        padding: 0.4rem 0.9rem 0.4rem 0.4rem;
                        min-width: 104px;
                        max-width: 170px;
                        flex: 1 1 110px;
                        display: flex;
                        flex-direction: row;
                        align-items: center;
                        position: relative;
                        overflow: hidden;
                        transition: box-shadow 0.13s, transform 0.11s;
                        height: 69px;
                    }
                    .mini-premium-stats-card:hover {
                        box-shadow: 0 3px 16px rgba(99,102,241,0.11);
                        border-color: #bec7f8;
                        transform: translateY(-1.5px) scale(1.01);
                    }
                    .mini-premium-icon {
                        flex-shrink: 0;
                        background: linear-gradient(115deg, #f1f5fd 60%, #f8faff 100%);
                        color: #5c69b9;
                        font-size: 1.08rem;
                        width: 29px;
                        height: 29px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-right: 0.6rem;
                        margin-bottom: 0;
                        box-shadow: 0 1px 2.5px rgba(99, 102, 241, 0.07);
                    }
                    .mini-premium-icon.green {
                        background: linear-gradient(120deg, #e6fbee 70%, #dbf8e5 100%);
                        color: #12ab7a;
                    }
                    .mini-premium-icon.gold {
                        background: linear-gradient(120deg,#fdf7e3 60%,#fffbe8 100%);
                        color:#cba940;
                    }
                    .mini-premium-texts {
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: flex-start;
                        min-width: 0;
                    }
                    .mini-premium-number {
                        font-size: 0.99rem;
                        font-weight: 700;
                        color: #23244A;
                        font-family: 'Inter', sans-serif;
                        margin-bottom: 0.03rem;
                        letter-spacing: 0.01em;
                        line-height: 1.1;
                        transition: color 0.13s;
                        white-space: nowrap;
                    }
                    .mini-premium-label {
                        font-size: 0.69rem;
                        letter-spacing: 0.06em;
                        color: #8c91ad;
                        text-transform: uppercase;
                        font-weight: 600;
                        line-height: 1.22;
                        margin-bottom: 0;
                    }
                    .mini-order-actions {
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: flex-end;
                        gap: 0.22rem;
                        align-self: stretch;
                        min-width: 120px;
                        margin-left: 1.2rem;
                    }
                    @media (max-width: 991.98px) {
                        .mini-premium-stats-row {
                            gap: 0.35rem;
                        }
                        .mini-premium-stats-card {
                            padding: 0.37rem 0.6rem 0.37rem 0.37rem;
                            min-width: 95px;
                            max-width: 100%;
                            font-size: 0.91rem;
                            height: 42px;
                        }
                        .mini-premium-number {
                            font-size: 0.93rem;
                        }
                        .mini-premium-label {
                            font-size: 0.63rem;
                        }
                        .mini-order-actions {
                            margin-left: 0.5rem;
                        }
                    }
                    /* --- Mobile style: All stat+btns in a single row, consistent heights, smaller, no border-radius --- */
                    @media (max-width: 600px) {
                        .mini-premium-header-row {
                            flex-wrap: nowrap !important;
                        }
                        .mini-premium-stats-row {
                            flex-direction: row !important;
                            flex-wrap: nowrap !important;
                            gap: 0.13rem !important;
                            width: 100% !important;
                            margin-bottom: 0 !important;
                            align-items: center !important;
                        }
                        .mini-premium-stats-card,
                        .mini-order-actions {
                            /* Ensures every element in the row is same height */
                            height: 28px !important;
                            min-height: 28px !important;
                            max-height: 28px !important;
                            margin-bottom: 0 !important;
                            border-radius: 0 !important; /* Remove border radius */
                        }
                        .mini-premium-stats-card {
                            min-width: 53px !important;
                            max-width: none !important;
                            width: 20vw !important;
                            padding: 0.14rem 0.13rem 0.14rem 0.13rem !important;
                            font-size: 0.60rem !important;
                        }
                        .mini-premium-icon {
                            font-size: 0.60rem !important;
                            width: 17px !important;
                            height: 17px !important;
                            margin-right: 0.14rem !important;
                        }
                        .mini-premium-number {
                            font-size: 0.62rem !important;
                        }
                        .mini-premium-label {
                            font-size: 0.39rem !important;
                        }
                        .mini-premium-texts {
                            max-width: 100%;
                        }
                        .mini-order-actions {
                            flex-direction: row !important;
                            align-items: center !important;
                            gap: 0.13rem !important;
                            margin: 0 !important;
                            min-width: unset;
                        }
                        .add-warehouse-btn.mini-premium {
                            min-height: 28px !important;
                            height: 28px !important;
                            font-size: 0.57rem !important;
                            padding: 0.07rem 0.40rem !important;
                            border-radius: 0 !important; /* Remove border radius for btn */
                            gap: 0.09rem !important;
                            font-weight: 700;
                            line-height: 1.2;
                            display: flex;
                            align-items: center;
                        }
                        .add-warehouse-btn.mini-premium span[style] {
                            padding: 2px !important;
                            font-size: 0.63rem !important;
                            margin-right: 0.08rem !important;
                        }
                        .add-warehouse-btn.mini-premium i {
                            font-size: 0.69rem !important;
                        }
                        .mini-order-tagline {
                            display: none !important;
                        }
                    }
                    .add-warehouse-btn.mini-premium {
                        background: linear-gradient(90deg, #f9fafc 10%, #eef4fb 120%);
                        color: #316084 !important;
                        border-radius: 0.94rem;
                        font-weight: 700;
                        font-size: 0.95rem;
                        padding: 0.33rem 1.07rem;
                        letter-spacing: 0.06em;
                        border: 1.2px solid #e5eafb;
                        box-shadow: 0 1.5px 8px rgba(140, 180, 241, 0.07);
                        transition: background 0.14s, color 0.13s, box-shadow 0.15s, border 0.13s;
                        min-height: 34px;
                        display: flex;
                        align-items: center;
                        gap: 0.37rem;
                    }
                    .add-warehouse-btn.mini-premium:hover, 
                    .add-warehouse-btn.mini-premium:focus {
                        background: linear-gradient(90deg,#e2faf0 20%, #e3e7fa 100%);
                        color: #13ae7e !important;
                        border-color: #c7e4cf;
                        box-shadow: 0 7px 18px rgba(18, 184, 134, 0.08), 0 3px 13px rgba(110,140,222,0.05);
                        transform: translateY(-2px) scale(1.017);
                    }
                </style>

                <div class="d-flex mini-premium-header-row flex-wrap flex-md-nowrap justify-content-between align-items-center mb-2 gap-2">
                    <div class="mini-premium-stats-row flex-grow-1">
                        <div class="mini-premium-stats-card">
                            <div class="mini-premium-icon">
                                <i class="ti ti-building-warehouse"></i>
                            </div>
                            <div class="mini-premium-texts">
                                <span class="mini-premium-number">{{ $Wewarehouse->count() }}</span>
                                <span class="mini-premium-label">Warehouses</span>
                            </div>
                        </div>
                        <div class="mini-premium-stats-card">
                            <div class="mini-premium-icon green">
                                <i class="fas fa-toggle-off"></i>
                            </div>
                            <div class="mini-premium-texts">
                                <span class="mini-premium-number">{{ $Wewarehouse->where('status', 'active')->count() ?? $Wewarehouse->count() }}</span>
                                <span class="mini-premium-label">Active</span>
                            </div>
                        </div>
                        <div class="mini-premium-stats-card">
                            <div class="mini-premium-icon gold">
                                <i class="ti ti-world"></i>
                            </div>
                            <div class="mini-premium-texts">
                                <span class="mini-premium-number">{{ $Wewarehouse->pluck('country')->unique()->count() }}</span>
                                <span class="mini-premium-label">Countries</span>
                            </div>
                        </div>
                    </div>
                    <div class="mini-order-actions mt-2 mt-md-0">
                        <a href="{{ route('seller.warehouse.create') }}" 
                            class="add-warehouse-btn mini-premium d-flex align-items-center shadow-sm"
                            style="
                                background: linear-gradient(98deg, #e6fbf4 0%, #f1f5ff 100%);
                                color: #1fa857;
                                border: 1.2px solid #b5dacb;
                                padding: 0.36rem 1.08rem;
                                font-size: 1rem;
                                font-weight: 700;
                                border-radius: 9px;
                                transition: all 0.16s cubic-bezier(.4,0,.2,1);
                                box-shadow: 0 2.5px 11px rgba(76, 201, 172, 0.07), 0 1px 7px rgba(110,140,222,0.04);
                                letter-spacing: 0.02em;
                                text-shadow: none;
                                outline: none;
                            "
                            onmouseover="this.style.transform='translateY(-2px) scale(1.03)'; this.style.background='linear-gradient(94deg,#dcf6ea 0%,#ecf1fa 100%)';"
                            onmouseout="this.style.transform='none'; this.style.background='linear-gradient(98deg, #e6fbf4 0%, #f1f5ff 100%)';"
                        >
                            <span style="display:inline-flex;align-items:center;">
                                <span style="background: linear-gradient(135deg, #e1f9f4 60%, #f5faff 100%); border-radius:6px; padding:6px; margin-right:0.45rem; display:flex;align-items:center; justify-content:center;">
                                    <i class="ti ti-plus" style="font-size:1.1rem; color: #1fa857;"></i>
                                </span>
                                <span style="font-weight:700;letter-spacing:0.04em;font-size:1rem;">
                                    Add New
                                </span>
                            </span>
                        </a>
                        <div class="mini-order-tagline" style="font-size:0.78rem; color:#68bb97; font-weight:500;text-align:center;margin-top:3px;letter-spacing:0.01em;">
                            Open your next warehouse location!
                        </div>
                    </div>
                </div>

                <!-- Warehouse Table Card -->
                <div class="warehouse-card" style="margin-top: 1.2rem;">
                    <div class="card-header bg-transparent border-0 p-3">
                        <h4 class="mb-0 d-flex align-items-center">
                           
                            Warehouse Locations
                        </h4>
                    </div>
                    <style>
                        /* Reduce top spacing and tighten card padding */
                        .pc-container {
                            padding-top: 0.15rem !important;
                        }
                        .pc-content {
                            margin-top: 0 !important;
                            padding-top: 0 !important;
                        }
                        .warehouse-main-card {
                            margin-top: 0 !important;
                            padding-top: 0 !important;
                        }
                        .responsive-card {
                            width: 100%;
                            margin: 2px 0 0 0; /* even smaller gap above card */
                            background: #646dff26;
                            border-radius: 12px;
                            box-shadow: 0 5px 14px rgba(102,126,234,0.07);
                            padding: 0.6rem 0.6rem; /* tighter vertical padding */
                        }
                        .responsive-header {
                            padding: 0.35rem 0 0.45rem 0;
                            margin-bottom: 0.6rem;
                            margin-top: 12px;
                            border-radius: 8px;
                            min-height: unset;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                            align-items: center;
                            background: linear-gradient(135deg, #929dcf 0%, #4f40cb 100%);
                            color: white;
                            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.12);
                        }
                        .responsive-header .warehouse-title {
                            margin-bottom: 0.12rem;
                            font-size: 1.0rem;
                            font-weight: 700;
                            color: #ffffff;
                            text-shadow: 0 1px 1px rgba(0,0,0,0.05);
                        }
                        .responsive-header .warehouse-subtitle {
                            font-size: 0.75rem;
                            opacity: 0.95;
                            color: rgba(248,249,250,0.95);
                            font-weight: 400;
                        }
                        .responsive-header i,
                        .warehouse-title i {
                            font-size: 0.95rem; /* smaller icon */
                            vertical-align: middle;
                            margin-right: 6px;
                            opacity: 0.95;
                        }
                        @media (max-width: 900px) {
                            .responsive-header .warehouse-title {
                                font-size: 0.95rem !important;
                            }
                            .responsive-header .warehouse-subtitle {
                                font-size: 0.75rem !important;
                            }
                        }
                        @media (max-width: 600px) {
                            .responsive-header .warehouse-title {
                                font-size: 0.9rem !important;
                            }
                            .responsive-header .warehouse-subtitle {
                                font-size: 0.72rem !important;
                            }
                        }
                        .stats-row {
                            gap: 0.5rem;
                        }
                        .stats-col {
                            max-width: 220px;
                            min-width: 120px;
                        }
                        @media (max-width: 1200px) {
                            .responsive-card {
                                width: 98vw;
                                margin-left: auto;
                                margin-right: auto;
                                padding: 1.2rem 0.5rem;
                            }
                            .responsive-header .warehouse-title {
                                font-size: 1.3rem !important;
                            }
                            .responsive-header .warehouse-subtitle {
                                font-size: 0.95rem !important;
                            }
                            .stats-card {
                                min-width: 90px !important;
                                font-size: 0.9rem !important;
                            }
                            .order-actions {
                                margin-left: 0 !important;
                                margin-top: 0.7rem;
                            }
                            .stats-row {
                                flex-direction: column !important;
                                gap: 0.5rem !important;
                            }
                            .stats-col {
                                max-width: 100%;
                                min-width: 90px;
                            }
                        }
                        @media (max-width: 600px) {
                            .responsive-card {
                                width: 100vw;
                                margin-left: 0;
                                margin-right: 0;
                                padding: 0.7rem 0.2rem;
                            }
                            .responsive-header .warehouse-title {
                                font-size: 1.1rem !important;
                            }
                            .responsive-header .warehouse-subtitle {
                                font-size: 0.85rem !important;
                            }
                            .stats-card {
                                min-width: 70px !important;
                                font-size: 0.8rem !important;
                                padding: 0.7rem !important;
                            }
                            .order-actions {
                                margin-left: 0 !important;
                                margin-top: 0.5rem;
                            }
                            .stats-row {
                                flex-direction: column !important;
                                gap: 0.3rem !important;
                            }
                            .stats-col {
                                max-width: 100%;
                                min-width: 70px;
                            }
                        }
                    </style>
                    <div class="table-responsive">
                        @if($Wewarehouse->count() > 0)
                            <table class="warehouse-table table table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="ti ti-user me-1"></i> Seller Name</th>
                                        <th><i class="ti ti-building me-1"></i> Warehouse Name</th>
                                        <th><i class="ti ti-map-pin me-1"></i> Address</th>  
                                        <th><i class="ti ti-building-skyscraper me-1"></i> City</th>
                                        <th><i class="ti ti-flag me-1"></i> State</th>
                                        <!-- <th><i class="ti ti-world me-1"></i> Country</th> -->
                                        <th><i class="ti ti-mail me-1"></i> Zip Code</th>
                                        <!-- <th><i class="ti ti-phone me-1"></i> Phone</th> -->
                                        <th><i class="ti ti-tools me-1"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody style="text-align: center;">
                                    @foreach ($Wewarehouse as $warehouse)
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column align-items-center justify-content-center" style="min-width:120px;">
                                                    <!-- <div class="avatar-circle mb-1" style="font-size:1.05rem; display:flex; align-items:center; justify-content:center; background: linear-gradient(135deg, #e8eefc 60%, #d9f6ee 100%); color:#3779cb; font-weight:700; width:38px; height:38px;">
                                                        {{ substr($warehouse->seller->name, 0, 1) }}
                                                    </div> -->
                                                    <div style="text-align:center;">
                                                        <strong class="d-block" style="font-size:0.96rem; font-weight:600; color:#23244A; letter-spacing:0.02em; margin-bottom:3px;">
                                                            {{ $warehouse->seller->name }}
                                                        </strong>
                                                        <a href="tel:{{ $warehouse->phone }}" class="phone-link d-inline-flex align-items-center" style="font-size:0.83rem; color:#13ae7e; font-weight:500; text-decoration:none; opacity:0.98;">
                                                            <i class="ti ti-phone me-1" style="font-size:1em; color:#13ae7e;"></i>{{ $warehouse->phone }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="warehouse-name-badge">{{ $warehouse->name }}</span>
                                            </td>
                                            <td>
                                                <div class="address-cell" style="white-space: normal; word-break: break-word;">
                                                    <i class="ti ti-map-pin text-muted me-1"></i>
                                                    {{ $warehouse->address_title }}
                                                </div>
                                            </td>
                                            <td>{{ $warehouse->city }}</td>
                                            <td>{{ $warehouse->state }}</td>
                                            <!-- <td>
                                                <span class="country-badge">{{ $warehouse->country }}</span>
                                            </td> -->
                                            <td>
                                                <code class="zip-code">{{ $warehouse->pincode }}</code>
                                            </td>
                                            <!-- <td>
                                                <a href="tel:{{ $warehouse->phone }}" class="phone-link">
                                                    <i class="ti ti-phone me-1"></i>{{ $warehouse->phone }}
                                                </a>
                                            </td> -->
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="{{ route('seller.warehouse.edit', $warehouse->id) }}" 
                                                       class="action-btn btn-edit" 
                                                       title="Edit Warehouse">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <a href="{{ route('seller.warehouse.delete', $warehouse->id) }}"
                                                       onclick="return confirm('Are you sure you want to delete this warehouse? This action cannot be undone.')"
                                                       class="action-btn btn-delete"
                                                       title="Delete Warehouse">
                                                        <i class="ti ti-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="empty-state">
                                <i class="ti ti-building-warehouse"></i>
                                <h4>No Warehouses Found</h4>
                                <p class="mb-4">You haven't added any warehouses yet. Start by creating your first warehouse location.</p>
                                <a href="{{ route('seller.warehouse.create') }}" class="add-warehouse-btn">
                                    <i class="ti ti-plus me-2"></i> Create Your First Warehouse
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .avatar-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .warehouse-name-badge {
            /* background: linear-gradient(135deg, #e3f2fd, #bbdefb); */
            color: #1976d2;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .address-cell {
            max-width: 200px;
            word-wrap: break-word;
        }
        
        .country-badge {
            background: linear-gradient(135deg, #f3e5f5, #e1bee7);
            color: #7b1fa2;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .zip-code {
            background: #f8f9fa;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .phone-link {
            color: #28a745;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .phone-link:hover {
            color: #20c997;
            text-decoration: underline;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            /* justify-content: center;
            flex-wrap: wrap; */
        }
        
        .card-header {
            border-bottom: 2px solid #f8f9fa;
        }
        
        .table-responsive {
            border-radius: 0 0 16px 16px;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .warehouse-header {
                padding: 0.5rem 0;
                /* margin: 0 -15px 1rem -15px; */
                border-radius: 8px;
            }
            
            .warehouse-title {
                font-size: 1.15rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 4px;
            }
            
            .action-btn {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
            
            .stats-card {
                margin-bottom: 0.5rem;
            }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .warehouse-card {
            animation: fadeInUp 0.6s ease;
        }
        
        .stats-card {
            animation: fadeInUp 0.6s ease;
        }
        
        .stats-card:nth-child(1) { animation-delay: 0.1s; }
        .stats-card:nth-child(2) { animation-delay: 0.2s; }
        .stats-card:nth-child(3) { animation-delay: 0.3s; }
    </style>
@endsection