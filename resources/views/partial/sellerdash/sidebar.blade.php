
<style>
    :root {
        --sidebar-collapse-width: 69px;
        --sidebar-full-width: 230px;
        --sidebar-transition: cubic-bezier(.4,0,.2,1);
        --sidebar-bg: linear-gradient(0deg, #252C42 0%, #3A39C4 100%);
        --sidebar-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .sidebarCollapse,
    .pc-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        z-index: 1200;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .sidebarCollapse {
        width: var(--sidebar-collapse-width);
        min-width: var(--sidebar-collapse-width);
        max-width: var(--sidebar-collapse-width);
        background: var(--sidebar-bg);
        box-shadow: var(--sidebar-shadow);
        transition:
            opacity 0.24s var(--sidebar-transition),
            visibility 0.18s var(--sidebar-transition),
            box-shadow 0.28s var(--sidebar-transition);
        opacity: 1;
        visibility: visible;
        overflow: hidden;
        z-index: 1200;
    }
    .sidebarCollapse.hide-on-sidebar {
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.21s var(--sidebar-transition), visibility 0.28s;
        pointer-events: none;
    }
    .sidebarCollapse .sidebar {
        overflow-y: auto !important;
        overflow-x: hidden !important;
        flex: 1 1 0%;
        max-height: calc(100vh - 48px);
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .sidebarCollapse .sidebar::-webkit-scrollbar {
        display: none !important;
    }
    .sidebarCollapse .sidebar-menu .nav-item,
    .sidebarCollapse .sidebar-menu .nav-link,
    .sidebarCollapse .sidebar-menu i {
        justify-content: center !important;
        text-align: center !important;
    }
    .sidebarCollapse .sidebar-menu span,
    .sidebarCollapse .sidebar-menu .ms-2 {
        display: none !important;
    }
    .sidebarCollapse .m-header { display:flex;justify-content:center;align-items:center;min-height:48px;padding:8px 0;}
    .sidebarCollapse .m-header img { max-width:38px !important;width:38px !important; }
    .sidebarCollapse .nav-link { justify-content: center; padding: 12px 0 !important; gap: 0 !important; }
    .sidebarCollapse .nav-link i { margin-right: 0; font-size: 22px;}
    .sidebarCollapse .nav-item { text-align: center; }
    .sidebarCollapse .sidebar, .sidebarCollapse .sidebar-menu { width: 100%; min-width:unset;margin-top: -10px; }

    .pc-sidebar {
        width: var(--sidebar-full-width);
        min-width: var(--sidebar-full-width);
        left: 0; top: 0;
        background: var(--sidebar-bg);
        box-shadow: var(--sidebar-shadow);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition:
            opacity 0.27s var(--sidebar-transition),
            visibility 0.33s var(--sidebar-transition),
            left 0.34s var(--sidebar-transition),
            box-shadow 0.34s var(--sidebar-transition);
        z-index: 1300;
        overflow: hidden;
    }
    .pc-sidebar.sidebar-active,
    .sidebarCollapse:hover ~ .pc-sidebar,
    .sidebarCollapse:focus-within ~ .pc-sidebar,
    .pc-sidebar:hover,
    .pc-sidebar:focus-within {
        opacity: 1;
        visibility: visible;
        left: 0;
        pointer-events: all;
        z-index: 1300;
        transition:
            opacity 0.18s var(--sidebar-transition),
            visibility 0.21s var(--sidebar-transition),
            left 0.28s var(--sidebar-transition),
            box-shadow 0.19s var(--sidebar-transition);
    }
    .sidebarCollapse:focus-within { z-index: 1312; }
    .sidebarCollapse:not(.hide-on-sidebar) { opacity: 1; visibility: visible; pointer-events: auto;}
    .pc-sidebar.sidebar-active ~ .sidebarCollapse,
    .pc-sidebar:hover ~ .sidebarCollapse,
    .pc-sidebar:focus-within ~ .sidebarCollapse {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .pc-sidebar .sidebar {
        flex: 1 1 0%;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        max-height: calc(100vh - 70px);
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .pc-sidebar .sidebar::-webkit-scrollbar {
        display: none !important;
    }

    .sidebarCollapse .nav-link span,
    .sidebarCollapse .nav-link .ms-2 { display:none !important; }

    .sidebar,
    .sidebar-menu {
        scroll-behavior: smooth;
    }

    .sidebar,
    .sidebar-menu {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }
    .sidebar::-webkit-scrollbar,
    .sidebar-menu::-webkit-scrollbar {
        display: none !important;
    }

    @media (max-width: 1200px) and (min-width: 991px) {
        .pc-sidebar, .sidebarCollapse {
            width: 170px !important;
            min-width: 170px !important;
        }
        .pc-sidebar .m-header img {
            width: 120px !important;
        }
    }

    @media (max-width: 990px) {
        .sidebarCollapse, .pc-sidebar { display: none !important; }
        .mobile-menu-toggle { display: block !important; }
        .sidebar-overlay {
            display: none;
            position: fixed;top:0;left:0;
            width:100vw;height:100vh;
            z-index:1999;background:rgba(0,0,0,0.5);
            opacity: 0;transition: opacity 0.28s cubic-bezier(.4,0,.2,1);
        }
        .sidebar-overlay.show { display: block; opacity: 1; }
        .mobile-sidebar-drawer {
            display: flex;
            flex-direction: column;
            background: var(--sidebar-bg);
            position: fixed;
            left: -110vw; top: 0;
            z-index: 2001;
            height: 100vh;
            width: var(--sidebar-full-width);
            max-width: 370px;
            transition: left 0.33s var(--sidebar-transition);
            box-shadow: 0 8px 40px rgba(0,0,0,0.17);
            opacity: 1;
        }
        .mobile-sidebar-drawer .sidebar {
            flex:1; 
            overflow-y: auto !important; 
            max-height: calc(100vh - 60px);
        }
        .mobile-sidebar-drawer .sidebar { scrollbar-width: none; -ms-overflow-style: none; }
        .mobile-sidebar-drawer .sidebar::-webkit-scrollbar { display: none !important; }
        .mobile-sidebar-drawer.show { left: 0 !important; }
        .mobile-sidebar-drawer .m-header img {width: 120px !important;}
    }
    @media (max-width: 767.98px) {
        .mobile-sidebar-drawer {
            width: 94vw !important;
            max-width: 94vw !important;
        }
        .mobile-sidebar-drawer .m-header img {width: 100px !important;}
    }
    @media (max-width: 576px) {
        .mobile-sidebar-drawer, .pc-sidebar, .sidebarCollapse {
            width: 66vw !important;
            /* min-width:unset !important;
            max-width: 100vw !important; */
        }
        .mobile-sidebar-drawer{ padding-left:0;padding-right:0;}
        .mobile-sidebar-drawer .m-header img {width: 80px !important;}
        .mobile-menu-toggle {
            left: 8px;
            top: 8px;
            padding: 10px;
            font-size: 16px;
        }
        .sidebar-menu { padding: 0.5rem !important;}
        .sidebar .nav-link { min-height: 38px;}
    }
    @media (max-width: 420px) {
        .mobile-sidebar-drawer, .pc-sidebar, .sidebarCollapse {
            width: 66vw !important;
            /* min-width: unset !important;
            max-width: 100vw !important; */
        }
        .sidebar-menu { padding: 0.2rem !important;}
        .mobile-sidebar-drawer .m-header img {width: 65px !important;}
    }

    .mobile-menu-toggle {
        display: none;
        position: fixed;
        top: 10px;
        left: 15px;
        z-index: 2004;
        background: rgba(102,126,234,.92);
        color: #fff;
        border: none;
        padding: 10px;
        border-radius: 8px;
        font-size: 18px;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,.2);
        transition: background 0.22s, transform 0.19s;
    }
    .mobile-menu-toggle.right {
        left: auto;
        right: 20px;
    }
    .mobile-menu-toggle:hover { background:rgba(102,126,234,1); }

    /* ---- Custom cross button for mobile sidebar ---- */
    .mobile-sidebar-close-btn {
        display: none;
    }
    @media (max-width: 990px) {
        .mobile-sidebar-close-btn {
            display: block;
            position: absolute;
            top: 10px;
            right: 14px;
            z-index: 2050;
            background: rgba(102,126,234,0.97);
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 7px;
            font-size: 26px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,.16);
            transition: background 0.22s, transform 0.19s;
        }
        .mobile-sidebar-close-btn:hover {
            background:rgba(102,126,234,1);
        }
    }

    .nav-item { border-radius:12px;margin-bottom:8px;overflow:hidden;position:relative;}
    .nav-link {
        color:rgba(255,255,255,0.85)!important;
        padding:5px 10px;
        border-radius:12px!important;
        font-weight:500;
        font-size:14px;
        display: flex;align-items:center;gap:2px;
        border:none!important;position:relative;min-height:45px;
        background:transparent;
        transition:background 0.22s, color 0.16s, padding 0.16s;
    }
    .nav-link i { font-size:18px;margin-right:12px; }
    .sidebarCollapse .nav-link i { margin-right:0; }
    .nav-link:hover { background:rgba(255,255,255,0.15)!important; color:rgba(255,255,255,0.99)!important;}
    .nav-item.active > .nav-link {  color:white!important; border-left:4px solid #fff; }
    .nav-indicator {
        position:absolute;right:15px;top:50%;
        transform:translateY(-50%);
        width:6px;height:6px;background:#ffd700;border-radius:50%;
        opacity:0;transition: opacity 0.28s;
    }
    .nav-item.active .nav-indicator { opacity: 1; }
    .sidebar-dropdown-menu, .collapse { transition: height 0.18s var(--sidebar-transition),opacity 0.17s;}
</style>

<!-- Only one toggle shown: left desktop, right mobile -->
<button class="mobile-menu-toggle" onclick="toggleMobileSidebar()" style="display:none;">
    <i class="ti ti-menu-2"></i>
</button>

<div class="sidebar-overlay" onclick="closeMobileSidebar()"></div>

<nav class="sidebarCollapse" id="sidebarCollapseNav" style="z-index:1200;">
    <div class="navbar-wrapper" style="height:100%;display:flex;flex-direction:column;">
        <div class="m-header d-flex justify-content-center align-items-center" style="padding: 18px 0;">
            <a href="javascript:void(0)" class="b-brand text-primary" style="display: flex; justify-content: center; align-items: center;">
                <span style="display:inline-flex;justify-content:center;align-items:center;width: 53px;
                    height: 55px;background: linear-gradient(135deg, #8dabd7 55%, #3784cb 100%);border-radius:50%;box-shadow:0 2px 10px 0 rgba(102,126,234,.09);">
                    <img src="{{ asset('assets/website/img/collapse-logo.png') }}" alt="Logo" style="width:40px;height:40px;object-fit:contain;">
                </span>
            </a>
        </div>
        <div class="sidebar">
            <ul class="nav flex-column sidebar-menu p-3">
                <li class="nav-item {{ request()->routeIs('seller.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('seller.dashboard') }}" class="nav-link" title="Dashboard">
                        <i class="ti ti-dashboard"></i>
                    </a>
                </li>
                <li class="nav-item sidebar-dropdown-item {{ request()->routeIs('seller.order*') ? 'active' : '' }}">
                    <a class="nav-link" title="Orders">
                        <i class="ti ti-shopping-cart"></i>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs(['seller.invoice.add*','seller.monthly.report*']) ? 'active' : '' }}">
                    <a href="{{ route('seller.invoice.add') }}" class="nav-link" title="Invoice">
                        <i class="ti ti-file-invoice"></i>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="javascript:void(0);" class="nav-link" title="NDR">
                        <i class="ti ti-alert-circle"></i>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs(['notifications.index*']) ? 'active' : '' }}">
                    <a href="{{ route('notifications.index') }}" class="nav-link" title="Shipping Notification">
                        <i class="ti ti-file-invoice"></i>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs(['seller.channel.list*']) ? 'active' : '' }}">
                    <a href="{{ route('seller.channel.list') }}" class="nav-link" title="Channel">
                        <i class="fa fa-plug"></i>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('seller.passbook') ? 'active' : '' }}">
                    <a href="{{ route('seller.passbook') }}" class="nav-link" title="Billing">
                        <i class="ti ti-wallet"></i>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('ratecards') ? 'active' : '' }}">
                    <a href="{{ route('ratecards') }}" class="nav-link" title="Tools">
                        <i class="fa-regular fa-screwdriver-wrench"></i>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('seller.weight.discrepancy*') ? 'active' : '' }}">
                    <a href="{{ route('seller.weight.discrepancy') }}" class="nav-link" title="Weight Dispatching">
                        <i class="fa-solid fa-scale-unbalanced-flip"></i>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('seller.warehouse.index*') ? 'active' : '' }}">
                    <a href="{{ route('seller.warehouse.index') }}" class="nav-link" title="Warehouse">
                        <i class="ti ti-building-warehouse"></i>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('seller.api.docs') ? 'active' : '' }}">
                    <a href="{{ route('seller.api.docs') }}" target="_blank" class="nav-link" title="API Documentation">
                        <i class="ti ti-file-download"></i>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('seller.webhook*') ? 'active' : '' }}">
                    <a href="{{ route('seller.webhooks.index') }}" class="nav-link" title="Settings">
                        <i class="ti ti-settings"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<nav class="pc-sidebar" id="pcSidebar" style="z-index:1300;">
    <div class="navbar-wrapper" style="height:100%;display:flex;flex-direction:column;">
        <div class="m-header" style="padding:13px 2px;">
            <a href="javascript:void(0)" class="b-brand text-primary">
                <img src="{{ asset('assets/website/img/sxp_white.png') }}" alt="Logo" style="width: 180px; max-width: 90vw;">
            </a>
        </div>
        <div class="sidebar">
            <ul class="nav flex-column sidebar-menu p-3">
                <li class="nav-item {{ request()->routeIs('seller.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('seller.dashboard') }}" class="nav-link d-flex align-items-center">
                        <i class="ti ti-dashboard"></i>
                        <span class="ms-2">Dashboard</span>
                        <div class="nav-indicator"></div>
                    </a>
                </li>
                @php
                    $ordersActive = request()->routeIs([
                        'seller.order*',
                        'seller.courier.Assigned*',
                        'seller.courier.Cancelled*',
                        'seller.courier.InTransit*',
                        'seller.courier.OutForDelivery*',
                        'seller.courier.Delivered*',
                        'seller.courier.RTO*',
                        'seller.courier.all*',
                        'seller.courier.other*',
                        'seller.b2c.order*',
                        'seller.b2b.order*',
                        'seller.quick.delivery*',
                        'seller.reverse.pickup*',
                        'seller.reverse.order*',
                    ]);
                @endphp
                <li class="nav-item sidebar-dropdown-item {{ $ordersActive ? 'active' : '' }}">
                    <a class="nav-link d-flex align-items-center sidebar-dropdown-toggle" 
                        href="javascript:void(0)" 
                        role="button" 
                        aria-expanded="{{ $ordersActive ? 'true' : 'false' }}" 
                        data-bs-toggle="custom-orders-dropdown" 
                        data-target="#ordersMenuDesk">
                        <i class="ti ti-shopping-cart"></i>
                        <span class="ms-2">Shipments</span>
                        <i class="ti ti-chevron-down ms-auto sidebar-dropdown-arrow"></i>
                        <div class="nav-indicator"></div>
                    </a>
                    <div class="collapse {{ $ordersActive ? 'show' : '' }}" id="ordersMenuDesk">
                        <ul class="nav flex-column sidebar-dropdown-menu">
                            <!-- <li>
                                <a href="{{ route('seller.order') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs(['seller.order*', 'seller.courier.Assigned*', 'seller.courier.Cancelled*','seller.courier.InTransit*','seller.courier.OutForDelivery*','seller.courier.Delivered*','seller.courier.RTO*','seller.courier.all*','seller.courier.other*']) ? 'active' : '' }}">
                                    <span class="ms-2">Add B2C Order</span>
                                </a>
                                <div class="collapse {{ $ordersActive ? 'show' : '' }}" id="ordersMenuDesk">
                                    <ul class="nav flex-column sidebar-dropdown-menu">
                                        <li>
                                            <a href="{{ route('seller.order') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs(['seller.order*', 'seller.courier.Assigned*', 'seller.courier.Cancelled*','seller.courier.InTransit*','seller.courier.OutForDelivery*','seller.courier.Delivered*','seller.courier.RTO*','seller.courier.all*','seller.courier.other*']) ? 'active' : '' }}">
                                                <span class="ms-2">Add B2C Order</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{-- route('seller.b2b.order') --}}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.b2b.order*') ? 'active' : '' }}">
                                                <span class="ms-2">Add B2B Order</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li> -->
                            <li>
                                <!-- Parent Menu -->
                                <a class="nav-link sidebar-sub-nav-link d-flex justify-content-between align-items-center"
                                data-bs-toggle="collapse"
                                href="#b2cOrderMenu"
                                role="button"
                                aria-expanded="{{ request()->routeIs('seller.order*') ? 'true' : 'false' }}"
                                aria-controls="b2cOrderMenu">

                                    <span>Add New Shipment</span>
                                    <i class="fas fa-chevron-down small" style="font-size: 10px;"></i>
                                </a>

                                <!-- B2C Submenu -->
                                <div class="collapse {{ request()->routeIs('seller.order*') ? 'show' : '' }}" id="b2cOrderMenu">
                                    <ul class="nav flex-column sidebar-dropdown-menu">

                                        <li>
                                            <a href="{{ route('seller.order') }}"
                                            class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.order') ? 'active' : '' }}">
                                                <span class="ms-2">Add B2B Order</span>
                                            </a>
                                        </li>

                                        <li>
                                            <a href="{{ route('seller.order') }}"
                                            class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.order.bulk') ? 'active' : '' }}">
                                                <span class="ms-2">Add B2C Order</span>
                                            </a>
                                        </li>

                                    </ul>
                                </div>
                            </li>
                            <li>
                                <a href="{{-- route('seller.b2b.order') --}}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.b2b.order*') ? 'active' : '' }}">
                                    <span class="ms-2">Shipment History</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @php
                    $billingActive = request()->routeIs([
                        'seller.passbook',
                        'seller.cod',
                        'seller.shippingcharge',
                    ]);
                @endphp
                <li class="nav-item sidebar-dropdown-item {{ $billingActive ? 'active' : '' }}">
                    <a class="nav-link d-flex align-items-center sidebar-dropdown-toggle"
                        href="javascript:void(0)"
                        role="button" aria-expanded="{{ $billingActive ? 'true' : 'false' }}"
                        data-bs-toggle="custom-billing-dropdown"
                        data-target="#billingMenuDesk"><i class="ti ti-wallet"></i>
                        <span class="ms-2">Finances</span>
                        <i class="ti ti-chevron-down ms-auto sidebar-dropdown-arrow"></i>
                    </a>
                    <div class="collapse {{ $billingActive ? 'show' : '' }}" id="billingMenuDesk">
                        <ul class="nav flex-column sidebar-dropdown-menu">
                            <li>
                                <a href="{{ route('seller.passbook') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.passbook') ? 'active' : '' }}">
                                    <span class="ms-2">Passbook</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('seller.cod') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.cod') ? 'active' : '' }}">
                                    
                                    <span class="ms-2">COD Remittance</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('seller.shippingcharge') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.shippingcharge') ? 'active' : '' }}">
                                    <span class="ms-2">Shipping Charges</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('seller.recharge') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.shippingcharge') ? 'active' : '' }}">
                                    <span class="ms-2">All Recharges</span>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->routeIs(['seller.invoice.add*','seller.monthly.report*']) ? 'active' : '' }}">
                                <a href="{{ route('seller.invoice.add') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs(['seller.invoice.add*','seller.monthly.report*']) ? 'active' : '' }}">
                                    <span class="ms-2">Invoice</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @php
                    $toolsActive = request()->routeIs([
                        'ratecards',
                        'shipmentprice',
                        'seller.shipment.report',
                        'activitylog',
                        'couriermanage',
                        'track.order',
                        'weightdiscrepancy',
                        'custom-label.*'
                    ]);
                @endphp
                <li class="nav-item sidebar-dropdown-item {{ $toolsActive ? 'active' : '' }}">
                    <a class="nav-link d-flex align-items-center sidebar-dropdown-toggle"
                        href="javascript:void(0)"
                        role="button"
                        aria-expanded="{{ $toolsActive ? 'true' : 'false' }}"
                        data-bs-toggle="custom-tools-dropdown"
                        data-target="#toolsMenuDesk"
                        id="toolsDropdownToggle">
                        <i class="fa-regular fa-screwdriver-wrench"></i>
                        <span class="ms-2">Tools</span>
                        <i class="ti ti-chevron-down ms-auto sidebar-dropdown-arrow" id="toolsChevron"></i>
                        <div class="nav-indicator"></div>
                    </a>
                    <div class="collapse {{ $toolsActive ? 'show' : '' }}" id="toolsMenuDesk">
                        <ul class="nav flex-column sidebar-dropdown-menu">
                            <li>
                                <a href="{{ route('ratecards') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('ratecards') ? 'active' : '' }}">
                                    <span class="ms-2">Rate Calculator</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('seller.custom-label.index') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.custom-label.index') ? 'active' : '' }}">
                                    <span class="ms-2">Custom Label</span>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->routeIs(['notifications.index*']) ? 'active' : '' }}">
                                <a href="{{ route('notifications.index') }}" class="nav-link d-flex align-items-center">
                                    <span class="ms-2">Shipping Notification</span>
                                    <div class="nav-indicator"></div>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->routeIs(['seller.channel.list*']) ? 'active' : '' }}">
                                <a href="{{ route('seller.channel.list') }}" class="nav-link d-flex align-items-center">
                                    <span class="ms-2">Channel</span>
                                    <div class="nav-indicator"></div>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('seller.reverse.order') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.reverse.pickup*') ? 'active' : '' }}">
                                    <span class="ms-2">NDR</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li>
                    <a href="{{ route('shipmentprice') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('shipmentprice') ? 'active' : '' }}">
                        <i class="ti ti-currency-dollar"></i>
                        <span class="ms-2">Shipment Price List</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('seller.shipment.report') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.shipment.report') ? 'active' : '' }}">
                        <i class="ti ti-download"></i>
                        <span class="ms-2">Report Download</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs(['seller.weight.discrepancy*']) ? 'active' : '' }}">
                    <a href="{{ route('seller.weight.discrepancy') }}" class="nav-link d-flex align-items-center">
                        <i class="fa-solid fa-scale-unbalanced-flip"></i>
                        <span class="ms-2">Weight Dispatching</span>
                        <div class="nav-indicator"></div>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('seller.api') ? 'active' : '' }}">
                    <a href="{{ route('seller.api') }}"  class="nav-link d-flex align-items-center">
                        <i class="ti ti-file-download"></i>
                        <span class="ms-2">API Documentation</span>
                        <div class="nav-indicator"></div>
                    </a>
                </li>
                <!-- <li>
                    <a href="{{ route('seller.cod') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.cod') ? 'active' : '' }}">
                        <i class="ti ti-cash"></i>
                        <span class="ms-2">COD Remittance</span>
                    </a>
                </li> -->
                @php
                    $settingsActive = request()->routeIs([
                        'seller.webhook*',
                        'seller.training*',
                    ]);
                @endphp
                <li class="nav-item sidebar-dropdown-item {{ $settingsActive ? 'active' : '' }}">
                    <a class="nav-link d-flex align-items-center sidebar-dropdown-toggle"
                        href="javascript:void(0)"
                        role="button"
                        aria-expanded="{{ $settingsActive ? 'true' : 'false' }}"
                        data-bs-toggle="custom-settings-dropdown"
                        data-target="#settingsMenuDesk"
                        id="settingsDropdownToggle">
                        <i class="ti ti-settings"></i>
                        <span class="ms-2">Settings</span>
                        <i class="ti ti-chevron-down ms-auto sidebar-dropdown-arrow" id="settingsDropdownArrow"></i>
                        <div class="nav-indicator"></div>
                    </a>
                    <div class="collapse {{ $settingsActive ? 'show' : '' }}" id="settingsMenuDesk">
                        <ul class="nav flex-column sidebar-dropdown-menu">
                            <li>
                                <a href="{{ route('seller.webhooks.index') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.webhook*') ? 'active' : '' }}">
                                    <span class="ms-2">Webhook</span>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->routeIs(['seller.warehouse.index*','seller.warehouse.create*','seller.warehouse.edit*']) ? 'active' : '' }}">
                                <a href="{{ route('seller.warehouse.index') }}" class="nav-link d-flex align-items-center">
                                    <span class="ms-2">Warehouse</span>
                                    <div class="nav-indicator"></div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <script>
                    // Sidebar Dropdown Toggle - for all "Orders", "Billings", "Tools", and "Settings"
                    function toggleSidebarDropdown(e) {
                        e.preventDefault();
                        const trigger = e.currentTarget;
                        const targetSelector = trigger.getAttribute('data-target');
                        const target = document.querySelector(targetSelector);

                        const expanded = trigger.getAttribute('aria-expanded') === 'true';

                        if (expanded) {
                            trigger.setAttribute('aria-expanded', 'false');
                            target && target.classList.remove('show');
                        } else {
                            trigger.setAttribute('aria-expanded', 'true');
                            target && target.classList.add('show');
                        }
                    }

                    document.addEventListener('DOMContentLoaded', function() {
                        var dropdownToggles = document.querySelectorAll('.sidebar-dropdown-toggle[data-bs-toggle^="custom-"]');
                        dropdownToggles.forEach(function(el) {
                            el.addEventListener('click', toggleSidebarDropdown);
                        });

                        document.addEventListener('click', function(event) {
                            var isClickInsideAny = false;
                            dropdownToggles.forEach(function(link) {
                                var targetSelector = link.getAttribute('data-target');
                                var target = document.querySelector(targetSelector);
                                if (link.contains(event.target) || (target && target.contains(event.target))) {
                                    isClickInsideAny = true;
                                }
                            });
                            if (!isClickInsideAny) {
                                dropdownToggles.forEach(function(link) {
                                    var target = document.querySelector(link.getAttribute('data-target'));
                                    link.setAttribute('aria-expanded', 'false');
                                    target && target.classList.remove('show');
                                });
                            }
                        });
                    });
                </script>
                <li>
                    <a href="{{ route('seller.help') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.help') ? 'active' : '' }}">
                        <i class="fa-regular fa-circle-question"></i>
                        <span class="ms-2">Help & Support</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<nav class="mobile-sidebar-drawer" style="display:none;">
    <div class="navbar-wrapper" style="height:100%;display:flex;flex-direction:column;position:relative;">
        <!-- <div class="m-header" style="padding:13px 2px; justify-content: center; align-items:center; display: flex; position:relative;">
            <a href="javascript:void(0)" class="b-brand text-primary">
                <img src="{{ asset('assets/website/img/sxp_white.png') }}" alt="Logo" style="width: 105px; max-width: 90vw;">
            </a>
        </div> -->
        <div class="sidebar" style="margin-top:74px;">
            <ul class="nav flex-column sidebar-menu p-3">
                <li class="nav-item {{ request()->routeIs('seller.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('seller.dashboard') }}" class="nav-link d-flex align-items-center">
                        <i class="ti ti-dashboard"></i>
                        <span class="ms-2">Dashboard</span>
                    </a>
                </li>
                @php
                    $ordersActive = request()->routeIs([
                        'seller.order*',
                        'seller.courier.Assigned*',
                        'seller.courier.Cancelled*',
                        'seller.courier.InTransit*',
                        'seller.courier.OutForDelivery*',
                        'seller.courier.Delivered*',
                        'seller.courier.RTO*',
                        'seller.courier.all*',
                        'seller.courier.other*',
                        'seller.b2c.order*',
                        'seller.b2b.order*',
                        'seller.quick.delivery*',
                        'seller.reverse.pickup*',
                        'seller.reverse.order*',
                    ]);
                @endphp
                <li class="nav-item sidebar-dropdown-item {{ $ordersActive ? 'active' : '' }}">
                    <a class="nav-link d-flex align-items-center"
                        href="javascript:void(0)" 
                        role="button"
                        aria-expanded="{{ $ordersActive ? 'true' : 'false' }}"
                        data-bs-toggle="mobile-orders-dropdown"
                        data-target="#ordersMenuMob"
                        id="ordersChevronMobile">
                        <i class="ti ti-shopping-cart"></i>
                        <span class="ms-2">Shipments</span>
                        <i class="ti ti-chevron-down ms-auto sidebar-dropdown-arrow"></i>
                    </a>
                    <div class="collapse {{ $ordersActive ? 'show' : '' }}" id="ordersMenuMob">
                        <ul class="nav flex-column sidebar-dropdown-menu">
                            <li>
                                <a href="{{ route('seller.order') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs(['seller.order*', 'seller.courier.Assigned*', 'seller.courier.Cancelled*','seller.courier.InTransit*','seller.courier.OutForDelivery*','seller.courier.Delivered*','seller.courier.RTO*','seller.courier.all*','seller.courier.other*']) ? 'active' : '' }}">
                                    <span class="ms-2">Add B2C Order</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{-- route('seller.b2b.order') --}}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.b2b.order*') ? 'active' : '' }}">
                                    <span class="ms-2">Add B2B Order</span>
                                </a>
                            </li>

                        </ul>
                    </div>
                </li>
                @php
                    $billingActive = request()->routeIs([
                        'seller.passbook',
                        'seller.cod',
                        'seller.shippingcharge',
                    ]);
                @endphp
                <li class="nav-item sidebar-dropdown-item {{ $billingActive ? 'active' : '' }}">
                    <a class="nav-link d-flex align-items-center"
                        href="javascript:void(0)"
                        role="button"
                        aria-expanded="{{ $billingActive ? 'true' : 'false' }}"
                        data-bs-toggle="mobile-billing-dropdown"
                        data-target="#billingMenuMob"
                        id="billingChevronMobile">
                        <i class="ti ti-wallet"></i>
                        <span class="ms-2">Finances</span>
                        <i class="ti ti-chevron-down ms-auto sidebar-dropdown-arrow"></i>
                    </a>
                    <div class="collapse {{ $billingActive ? 'show' : '' }}" id="billingMenuMob">
                        <ul class="nav flex-column sidebar-dropdown-menu">
                            <li>
                                <a href="{{ route('seller.passbook') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.passbook') ? 'active' : '' }}">
                                    <span class="ms-2">Passbook</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('seller.cod') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.cod') ? 'active' : '' }}">
                                    
                                    <span class="ms-2">COD Remittance</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('seller.shippingcharge') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.shippingcharge') ? 'active' : '' }}">
                                    <span class="ms-2">Shipping Charges</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('seller.recharge') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.shippingcharge') ? 'active' : '' }}">
                                    <span class="ms-2">All Recharges</span>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->routeIs(['seller.invoice.add*','seller.monthly.report*']) ? 'active' : '' }}">
                                <a href="{{ route('seller.invoice.add') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs(['seller.invoice.add*','seller.monthly.report*']) ? 'active' : '' }}">
                                    <span class="ms-2">Invoice</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @php
                    $toolsActive = request()->routeIs([
                        'ratecards',
                        'shipmentprice',
                        'seller.shipment.report',
                        'activitylog',
                        'couriermanage',
                        'track.order',
                        'weightdiscrepancy',
                        'custom-label.*'
                    ]);
                @endphp
                <li class="nav-item sidebar-dropdown-item {{ $toolsActive ? 'active' : '' }}">
                    <a class="nav-link d-flex align-items-center"
                        href="javascript:void(0)"
                        role="button"
                        aria-expanded="{{ $toolsActive ? 'true' : 'false' }}"
                        data-bs-toggle="mobile-tools-dropdown"
                        data-target="#toolsMenuMob"
                        id="toolsChevronMobile">
                        <i class="ti ti-settings"></i>
                        <span class="ms-2">Tools</span>
                        <i class="ti ti-chevron-down ms-auto sidebar-dropdown-arrow"></i>
                    </a>
                    <div class="collapse {{ $toolsActive ? 'show' : '' }}" id="toolsMenuMob">
                        <ul class="nav flex-column sidebar-dropdown-menu">
                            <li>
                                <a href="{{ route('ratecards') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('ratecards') ? 'active' : '' }}">
                                    <span class="ms-2">Rate Calculator</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('seller.custom-label.index') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.custom-label.index') ? 'active' : '' }}">
                                    <span class="ms-2">Custom Label</span>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->routeIs(['notifications.index*']) ? 'active' : '' }}">
                                <a href="{{ route('notifications.index') }}" class="nav-link d-flex align-items-center">
                                    <span class="ms-2">Shipping Notification</span>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->routeIs(['seller.channel.list*']) ? 'active' : '' }}">
                                <a href="{{ route('seller.channel.list') }}" class="nav-link d-flex align-items-center">
                                    <span class="ms-2">Channel</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="javascript:void(0);" class="nav-link d-flex align-items-center">
                                    <span class="ms-2">NDR</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <li>
                    <a href="{{ route('shipmentprice') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('shipmentprice') ? 'active' : '' }}">
                        <i class="ti ti-currency-dollar"></i>
                        <span class="ms-2">Shipment Price List</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('seller.shipment.report') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.shipment.report') ? 'active' : '' }}">
                        <i class="ti ti-download"></i>
                        <span class="ms-2">Report Download</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs(['seller.weight.discrepancy*']) ? 'active' : '' }}">
                    <a href="{{ route('seller.weight.discrepancy') }}" class="nav-link d-flex align-items-center">
                        <i class="fa-solid fa-scale-unbalanced-flip"></i>
                        <span class="ms-2">Weight Dispatching</span>
                        <div class="nav-indicator"></div>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('seller.api') ? 'active' : '' }}">
                    <a href="{{ route('seller.api') }}" class="nav-link d-flex align-items-center">
                        <i class="ti ti-file-download"></i>
                        <span class="ms-2">API Documentation</span>
                        <div class="nav-indicator"></div>
                    </a>
                </li>
                
                @php
                    $settingsActive = request()->routeIs([
                        'seller.webhook*',
                        'seller.training*',
                    ]);
                @endphp
                <li class="nav-item sidebar-dropdown-item {{ $settingsActive ? 'active' : '' }}">
                    <a class="nav-link d-flex align-items-center sidebar-dropdown-toggle"
                        href="javascript:void(0)"
                        id="settingsDropdownToggleMobile"
                        role="button"
                        aria-expanded="{{ $settingsActive ? 'true' : 'false' }}"
                        data-bs-toggle="custom-settings-dropdown-mobile"
                        data-target="#settingsMenuMobile">
                        <i class="ti ti-settings"></i>
                        <span class="ms-2">Settings</span>
                        <i class="ti ti-chevron-down ms-auto sidebar-dropdown-arrow" id="settingsDropdownArrowMobile"></i>
                        <div class="nav-indicator"></div>
                    </a>
                    <div class="collapse {{ $settingsActive ? 'show' : '' }}" id="settingsMenuMobile">
                        <ul class="nav flex-column sidebar-dropdown-menu">
                            <li>
                                <a href="{{ route('seller.webhooks.index') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.webhook*') ? 'active' : '' }}">
                                    <span class="ms-2">Webhook</span>
                                </a>
                            </li>
                            <li class="nav-item {{ request()->routeIs(['seller.warehouse.index*','seller.warehouse.create*','seller.warehouse.edit*']) ? 'active' : '' }}">
                                <a href="{{ route('seller.warehouse.index') }}" class="nav-link d-flex align-items-center">
                                    <span class="ms-2">Warehouse</span>
                                    <div class="nav-indicator"></div>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <script>
                // Attach dropdown logic for mobile sidebar Settings dropdown
                document.addEventListener('DOMContentLoaded', function() {
                    var settingsToggle = document.getElementById('settingsDropdownToggleMobile');
                    var settingsMenu = document.getElementById('settingsMenuMobile');
                    if (settingsToggle && settingsMenu) {
                        settingsToggle.removeEventListener('click', settingsToggle._dropdownHandlerMobile || (() => {}));
                        settingsToggle._dropdownHandlerMobile = function(e) {
                            e.preventDefault();
                            // Only toggles the dropdown, does NOT close sidebar drawer
                            settingsMenu.classList.toggle('show');
                            this.setAttribute('aria-expanded', settingsMenu.classList.contains('show') ? 'true' : 'false');
                        };
                        settingsToggle.addEventListener('click', settingsToggle._dropdownHandlerMobile);
                    }
                });
                </script>
                <li>
                    <a href="{{ route('seller.help') }}" class="nav-link sidebar-sub-nav-link {{ request()->routeIs('seller.help') ? 'active' : '' }}">
                        <i class="fa-regular fa-circle-question"></i>
                        <span class="ms-2">Help & Support</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
function toggleMobileSidebar() {
    // ENSURE button exists before anything else!
    const btn = document.querySelector('.mobile-menu-toggle:not(.right)');
    const drawer = document.querySelector('.mobile-sidebar-drawer');
    const overlay = document.querySelector('.sidebar-overlay');

    if (!btn || !drawer || !overlay) return;

    // If drawer is currently open
    if (drawer.classList.contains('show')) {
        drawer.classList.remove('show');
        overlay.classList.remove('show');
        btn.innerHTML = '<i class="ti ti-menu-2"></i>';
        document.body.style.overflow = '';
    } else {
        drawer.classList.add('show');
        overlay.classList.add('show');
        btn.innerHTML = '<i class="ti ti-x"></i>';
        document.body.style.overflow = 'hidden';
    }
}

// ---- UNIFIED: Ensure dropdowns work on mobile after sidebar opens ----
function attachMobileDrawerDropdowns() {
    // Orders
    const ordersChevronMobile = document.getElementById('ordersChevronMobile');
    const ordersMenuMob = document.getElementById('ordersMenuMob');
    if (ordersChevronMobile && ordersMenuMob) {
        ordersChevronMobile.removeEventListener('click', ordersChevronMobile._dropdownHandlerMobile || (() => {}));
        ordersChevronMobile._dropdownHandlerMobile = function(e) {
            e.preventDefault();
            ordersMenuMob.classList.toggle('show');
            this.setAttribute('aria-expanded', ordersMenuMob.classList.contains('show') ? 'true' : 'false');
        };
        ordersChevronMobile.addEventListener('click', ordersChevronMobile._dropdownHandlerMobile);
    }
    // Billing
    const billingChevronMobile = document.getElementById('billingChevronMobile');
    const billingMenuMob = document.getElementById('billingMenuMob');
    if (billingChevronMobile && billingMenuMob) {
        billingChevronMobile.removeEventListener('click', billingChevronMobile._dropdownHandlerMobile || (() => {}));
        billingChevronMobile._dropdownHandlerMobile = function(e) {
            e.preventDefault();
            billingMenuMob.classList.toggle('show');
            this.setAttribute('aria-expanded', billingMenuMob.classList.contains('show') ? 'true' : 'false');
        };
        billingChevronMobile.addEventListener('click', billingChevronMobile._dropdownHandlerMobile);
    }
    // Tools
    const toolsChevronMobile = document.getElementById('toolsChevronMobile');
    const toolsMenuMob = document.getElementById('toolsMenuMob');
    if (toolsChevronMobile && toolsMenuMob) {
        toolsChevronMobile.removeEventListener('click', toolsChevronMobile._dropdownHandlerMobile || (() => {}));
        toolsChevronMobile._dropdownHandlerMobile = function(e) {
            e.preventDefault();
            toolsMenuMob.classList.toggle('show');
            this.setAttribute('aria-expanded', toolsMenuMob.classList.contains('show') ? 'true' : 'false');
        };
        toolsChevronMobile.addEventListener('click', toolsChevronMobile._dropdownHandlerMobile);
    }
    // Settings (mobile)
    const settingsDropdownToggleMobile = document.getElementById('settingsDropdownToggleMobile');
    const settingsMenuMobile = document.getElementById('settingsMenuMobile');
    const settingsDropdownArrowMobile = document.getElementById('settingsDropdownArrowMobile');
    if (settingsDropdownToggleMobile && settingsMenuMobile) {
        function toggleSettingsMenuMobile(e) {
            e.preventDefault();
            const isShown = settingsMenuMobile.classList.contains('show');
            settingsMenuMobile.classList.toggle('show');
            settingsDropdownToggleMobile.setAttribute('aria-expanded', isShown ? 'false' : 'true');
        }
        // Remove previous if any
        settingsDropdownToggleMobile.removeEventListener('click', settingsDropdownToggleMobile._dropdownHandlerMobile || (()=>{}));
        settingsDropdownArrowMobile && settingsDropdownArrowMobile.removeEventListener('click', settingsDropdownToggleMobile._dropdownHandlerMobile || (()=>{}));
        settingsDropdownToggleMobile._dropdownHandlerMobile = toggleSettingsMenuMobile;
        settingsDropdownToggleMobile.addEventListener('click', toggleSettingsMenuMobile);
        settingsDropdownArrowMobile && settingsDropdownArrowMobile.addEventListener('click', toggleSettingsMenuMobile);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Attach desktop dropdown logic
    var btn = document.querySelector('.mobile-menu-toggle:not(.right)');
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMobileSidebar();
            setTimeout(attachMobileDrawerDropdowns, 50); // ensure after show
        });
    }
    // Attach to show mobile dropdowns on page load (relevant on small screen, F5 reload etc.)
    setTimeout(attachMobileDrawerDropdowns, 0);
});

function closeMobileSidebar() {
    const drawer = document.querySelector('.mobile-sidebar-drawer');
    const overlay = document.querySelector('.sidebar-overlay');
    const btn = document.querySelector('.mobile-menu-toggle:not(.right)');
    if (!drawer || !overlay) return;
    drawer.classList.remove('show');
    overlay.classList.remove('show');
    btn && (btn.innerHTML = '<i class="ti ti-menu-2"></i>');
    document.body.style.overflow = '';
}
document.addEventListener('DOMContentLoaded', function() {
    function handleSidebarVisibility() {
        const w = window.innerWidth;
        const mDrawer = document.querySelector('.mobile-sidebar-drawer');
        const desktopSidebar = document.querySelector('.sidebarCollapse');
        const pcSidebar = document.querySelector('.pc-sidebar');
        const toggleBtn = document.querySelector('.mobile-menu-toggle:not(.right)');
        if (!mDrawer || !desktopSidebar || !pcSidebar || !toggleBtn) return;
        if (w <= 990) {
            toggleBtn.style.display = 'block';
            mDrawer.style.display = 'flex';
            desktopSidebar.style.display = 'none';
            pcSidebar.style.display = 'none';
            setTimeout(attachMobileDrawerDropdowns, 10);
        } else {
            toggleBtn.style.display = 'none';
            document.querySelector('.sidebar-overlay').classList.remove('show');
            mDrawer.style.display = 'none';
            desktopSidebar.style.display = 'flex';
            pcSidebar.style.display = '';
            closeMobileSidebar();
        }
    }
    handleSidebarVisibility();
    window.addEventListener('resize', handleSidebarVisibility);

    // Nav link click closes if not dropdown
    document.querySelectorAll('.mobile-sidebar-drawer .nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if (!this.getAttribute('data-bs-toggle')) setTimeout(() => closeMobileSidebar(), 100);
        });
    });

    // Left swipe close on mobile
    let startX = null;
    document.addEventListener('touchstart', function(e) {
        if (window.innerWidth <= 990 && document.querySelector('.mobile-sidebar-drawer').classList.contains('show')) {
            startX = e.touches[0].clientX;
        }
    });
    document.addEventListener('touchmove', function(e) {
        if (startX !== null && window.innerWidth <= 990) {
            const currentX = e.touches[0].clientX;
            const diffX = startX - currentX;
            if (diffX > 50) {
                closeMobileSidebar();
                startX = null;
            }
        }
    });
    document.addEventListener('touchend', function() { startX = null; });

    // Desktop expand/hide logic remains unchanged
    const sidebarCollapse = document.getElementById('sidebarCollapseNav');
    const pcSidebar = document.getElementById('pcSidebar');
    let showTimer = null, hideTimer = null;
    function enterSidebar() {
        clearTimeout(hideTimer);
        showTimer = setTimeout(() => {
            sidebarCollapse.classList.add('hide-on-sidebar');
            pcSidebar.classList.add('sidebar-active');
            pcSidebar.style.zIndex = "1300";
        }, 60);
    }
    function leaveSidebar() {
        clearTimeout(showTimer);
        hideTimer = setTimeout(() => {
            sidebarCollapse.classList.remove('hide-on-sidebar');
            pcSidebar.classList.remove('sidebar-active');
            pcSidebar.style.zIndex = "";
        }, 70);
    }
    if (sidebarCollapse && pcSidebar) {
        sidebarCollapse.addEventListener('mouseenter', enterSidebar);
        sidebarCollapse.addEventListener('focusin', enterSidebar);
        pcSidebar.addEventListener('mouseenter', enterSidebar);
        pcSidebar.addEventListener('focusin', enterSidebar);

        sidebarCollapse.addEventListener('mouseleave', leaveSidebar);
        sidebarCollapse.addEventListener('focusout', leaveSidebar);
        pcSidebar.addEventListener('mouseleave', leaveSidebar);
        pcSidebar.addEventListener('focusout', leaveSidebar);
    }

    // --- Show close cross on sidebar when open and restore hamburger ---
    function updateSidebarCrossButton() {
        const drawer = document.querySelector('.mobile-sidebar-drawer');
        const closeBtn = document.getElementById('mobileSidebarCloseBtn');
        const menuBtn = document.querySelector('.mobile-menu-toggle:not(.right)');
        if (!drawer || !closeBtn || !menuBtn) return;
        if (drawer.classList.contains('show')) {
            closeBtn.style.display = 'block';
            menuBtn.innerHTML = '<i class="ti ti-x"></i>';
        } else {
            closeBtn.style.display = 'none';
            menuBtn.innerHTML = '<i class="ti ti-menu-2"></i>';
        }
    }

    // Show/hide cross icon when the sidebar is toggled
    document.querySelector('.mobile-menu-toggle').addEventListener('click', function() {
        setTimeout(updateSidebarCrossButton, 5);
    });
    document.getElementById('mobileSidebarCloseBtn').addEventListener('click', function() {
        setTimeout(updateSidebarCrossButton, 5);
    });

    // Also update on resize
    window.addEventListener('resize', function() {
        setTimeout(updateSidebarCrossButton, 5);
    });
    // Call once on load to ensure state
    setTimeout(updateSidebarCrossButton, 100);

    // Attach dropdown logic again for mobile on full load
    setTimeout(attachMobileDrawerDropdowns, 100);
});
</script>
