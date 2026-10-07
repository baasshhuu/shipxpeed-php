<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('partial.sellerdash.common.header')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
/* ...all your previous styles remain... */

/* --- Responsive pc-header left offset --- */
.pc-header {
    position: fixed;
    left: 66px;
    right: 0;
    top: 0;
    width: auto;
    z-index: 1000;
    background: #fff;
    transition: left 0.25s, width 0.25s;
}

@media (max-width: 991.98px) {
    .pc-header {
        left: 0;
        width: 100%;
    }
}

/* --- Override/Add for mobile menu --- */
@media (max-width: 768px) {
    .header-wrapper {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        position: relative;
        min-height: 64px;
    }
    .mobile-header-logo {
        display: flex !important;
        align-items: center;
        justify-content: center;
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        height: 64px;
        z-index: 13990;
        pointer-events: none;
    }
    .mobile-header-logo img {
        margin: auto;
        display: block;
        pointer-events: auto;
    }
    .menu-icon {
        position: absolute;
        top: 12px;
        right: 14px;
        z-index: 14000;
        background: #6c757d;
        color: #fff !important;
        padding: 0.6rem 0.93rem;
        border-radius: 26px;
        font-size: 1.05rem !important;
        display: flex !important;
        align-items: center;
        box-shadow: 0 4px 16px rgba(59,130,246,0.15);
        cursor: pointer;
        transition: background 0.2s, color 0.2s, box-shadow 0.3s;
        pointer-events: auto;
    }
    .pc-header .quick-action-desktop,
    .pc-header .profile-desktop {
        display: none !important;
    }
}

/* Desktop Premium Styles */
@media (min-width: 769px) {
    .menu-icon {
        display: none !important;
    }
    .pc-header .mobile-header-logo {
        display: none !important;
    }
    .pc-header .quick-action-desktop,
    .pc-header .profile-desktop {
        display: flex !important;
    }
    /* Premium look for wallet & recharge - height reduced */
    .header-button-section {
        background: linear-gradient(90deg,#f4f7fc 15%,#e9e6f9 85%);
        border-radius: 1.25rem;
        box-shadow: 0 4px 18px rgba(60,55,170,0.06), 0 2px 14px rgba(40,40,70,0.08);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.1rem 1.1rem 0.1rem 1rem;   /* Reduced padding */
        margin-right: 1.2rem;
        border: 1.5px solid #ecebfa;
        min-width: 230px;
        min-height: 36px;  /* Reduced min height */
        height: 40px;      /* Set fixed small height */
        line-height: 36px;
    }
    .wallet-balance-link {
        color: #363068 !important;
        display: flex;
        align-items: center;
        font-weight: 600;
        font-size: 1.07rem;
        background: none;
        padding: 0 0.3rem 0 0;
        border-radius:2rem 0 0 2rem;
        transition: color 0.17s;
    }
    .wallet-balance-link .fa-wallet {
        color: #3bb274;
        font-size: 1.08rem;
        margin-right: 0.2rem;
    }
    .balance-amount {
        color: #2e3192;
        font-family: 'Inter',sans-serif;
        letter-spacing: 0.3px;
    }
    .header-button-section .recharge {
        background: linear-gradient(90deg, #438cd7 0%, #646dd7 100%);
        color: #fff!important;
        border: none;
        border-radius: 5px;
        font-size: 0.88rem;
        /* font-weight: 600; */
        padding: 0.22rem 0.75rem;
        box-shadow: 0 3px 20px rgba(74,144,226,.13);
        display: flex;
        align-items: center;
        transition: background .18s, box-shadow .22s;
        height: 30px;
        line-height: 18px;
    }
    .header-button-section .recharge .fa-plus-circle {
        color: #ffe968;
        font-size: 1.02rem;
        margin-right: 0.32rem;
    }
    .header-button-section .recharge:hover {
        background: linear-gradient(90deg,#4388ff 0,#32e9b6 100%);
        box-shadow: 0 4px 32px rgba(63,111,228,.15);
    }
    /* Premium quick action dropdown */
    .quick-action-desktop .dropdown-toggle {
        background: linear-gradient(90deg, #f4edfc 0%, #e9f9fa 100%);
        color: #3a3666 !important;
        border: 1.5px solid #e2e3f2;
        border-radius: 0.7rem;
        box-shadow: 0 2px 8px rgba(64,64,100,0.11);
       
        padding: 0.38rem 1.07rem 0.38rem 0.96rem !important; /* Reduce */
        font-size: 0.88rem !important; /* Reduce */
        display: flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 32px;
    }
    .quick-action-desktop .dropdown-toggle:hover {
        background: linear-gradient(90deg, #e6e9fb 0%, #d6e9ff 100%);
        box-shadow: 0 4px 28px rgba(100,126,255,.08);
    }
    .quick-action-desktop .fa-bolt {
        color: #ffd666;
        font-size: 1.1rem;
        margin-right: 0.33rem;
    }
    .quick-action-desktop .fa-chevron-down {
        color: #aab1d8;
        font-size: 0.97rem;
        margin-left: 0.23rem;
    }
}
.menu-icon .fa-times { font-size: 1.5rem;}
.menu-icon .fa-bars { font-size: 1.5rem;}
.mobile-sidebar-backdrop {
    background: rgba(30,30,40,0.18);
    position: fixed;
    inset: 0;
    z-index: 13990;
    display: none;
}
.mobile-sidebar-backdrop.active {
    display: block;
    animation: fadein-bg 0.27s;
}
@keyframes fadein-bg {
    from { opacity: 0; }
    to { opacity: 1; }
}
#mobileSidebarOffcanvas {
    z-index: 15000 !important;
    width: 90vw;
    max-width: 360px;
} 
#mobileSidebarOffcanvas .offcanvas-header {
    justify-content: flex-end;
    flex-direction: row;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
#mobileSidebarOffcanvas .offcanvas-title {
    flex: 1;
    color: #fff;
    font-size: 1.17rem;
    padding-left: 0.5rem;
}
#mobileSidebarOffcanvas .close-menu-btn {
    background: transparent;
    border: none;
    color: white;
    font-size: 2rem;
    cursor: pointer;
    margin-left: auto;
    line-height: 1;
}
#mobileSidebarOffcanvas .offcanvas-body {
    padding: 0;
}
</style>

<body data-pc-preset="preset-1" data-pc-direction="ltr" data-pc-theme="light">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    @include('partial.sellerdash.sidebar')

    <!-- Mobile Sidebar Offcanvas (Menu & Account Overview)-->
    <div class="offcanvas offcanvas-end modern-offcanvas" tabindex="-1" id="mobileSidebarOffcanvas">
        <div class="offcanvas-header px-3 py-3">
            <h5 class="offcanvas-title"> Account Overview
            </h5>
            <button type="button" class="close-menu-btn" id="closeMobileSidebar" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <!-- Account Overview at top -->
            <div style="background:linear-gradient(135deg,#f6fffa 0%,#ebf4ff 120%);padding:0.7rem 1.1rem 1rem 1.1rem;border-bottom:1px solid #e2e8f0;">
                <div class="d-flex align-items-center mb-2">
                    @php
                        $seller = Auth::guard('seller')->user();
                    @endphp
                    @if ($seller && $seller->profile && file_exists(public_path('uploads/seller_profiles/' . $seller->profile)))
                        <img
                            src="{{ asset('uploads/seller_profiles/' . $seller->profile) }}"
                            alt="Seller Image"
                            class="user-avtar me-3"
                            width="54"
                            height="54"
                            style="object-fit:cover;border-radius:50%;box-shadow:0 2px 8px rgba(59,130,246,0.09)">
                    @else
                        <div class="default-avatar me-3" style="width:54px;height:54px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    @endif
                    <div>
                        <div class="fw-bold" style="font-size:1.07rem;">{{ $seller?->name ?? 'Seller' }}</div>
                        <div style="font-size:0.83rem;color:#64748b;">{{ $seller?->email ?? '' }}</div>
                        <div style="font-size:0.75rem;color:#16a34a;font-weight:600;">
                            {{ $seller?->status == 1 ? 'Active' : 'Pending' }}
                        </div>
                    </div>
                </div>
                <div style="display:flex;gap:12px;">
                    <a href="{{ route('profile.get') }}" class="btn btn-outline-primary btn-sm" style="font-size:0.8rem;">
                        <i class="ti ti-user-edit me-1"></i> Edit
                    </a>
                    <form id="logout-form" action="{{ route('seller.logout') }}" method="POST" style="display:none;" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
    @csrf
</form>
                    <form id="mobile-logout-form" action="{{ route('seller.logout') }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm" style="font-size:0.8rem;">
                            <i class="ti ti-power me-1"></i>Logout
                        </button>
                    </form>
                
                </div>
                <!-- Quick Actions now shown in mobile view inside Account Overview -->
                <div class="d-block d-md-none" style="margin-top:1.5rem;">
                    <div style="margin-bottom:0.9rem; color:#25405c; font-weight:600; font-size:1.06rem; letter-spacing:.01em;">
                        <i class="fa-regular fa-bolt me-1" style="color:#8AB5F5;"></i>Quick Actions
                    </div>
                    <div class="d-flex flex-column gap-2" >
                        <a href="{{ route('ratecards') }}"
                            class="btn btn-light text-start py-2 px-3 shadow-none d-flex align-items-center quick-action-btn"
                            style="font-size:1.01rem; border:1px solid #e5e7eb; border-radius:8px; transition: all .13s; color:#25405c; font-weight: 500;">
                            <span style="background:#e6effa; color:#5185e8; border-radius:6px; min-width:34px; height:34px; display:flex; align-items:center; justify-content:center; margin-right:12px;">
                                <i class="fas fa-calculator"></i>
                            </span>
                            Rate Calculator
                        </a>
                        <a href="{{ route('seller.orderadd') }}"
                            class="btn btn-light text-start py-2 px-3 shadow-none d-flex align-items-center quick-action-btn"
                            style="font-size:1.01rem; border:1px solid #e5e7eb; border-radius:8px; transition: all .13s; color:#14532d; font-weight: 500;">
                            <span style="background:#e5faf2; color:#24bb7e; border-radius:6px; min-width:34px; height:34px; display:flex; align-items:center; justify-content:center; margin-right:12px;">
                                <i class="fas fa-plus"></i>
                            </span>
                            Add Order
                        </a>
                        <a href="{{ route('track-order') }}"
                            class="btn btn-light text-start py-2 px-3 shadow-none d-flex align-items-center quick-action-btn"
                            style="font-size:1.01rem; border:1px solid #e5e7eb; border-radius:8px; transition: all .13s; color:#1d3f53; font-weight: 500;">
                            <span style="background:#e7f7fa; color:#4EA7C8; border-radius:6px; min-width:34px; height:34px; display:flex; align-items:center; justify-content:center; margin-right:12px;">
                                <i class="fas fa-search"></i>
                            </span>
                            Track Order
                        </a>
                    </div>
                </div>
                <style>
                    .quick-action-btn:hover, .quick-action-btn:active, .quick-action-btn:focus {
                        background: #f1f5fa !important;
                        border-color: #bcd6f7 !important;
                        color:#314a69 !important;
                        box-shadow: 0 2px 12px -7px #bcd6f7;
                        text-decoration:none;
                    }
                </style>
            </div>
            <!-- Wallet / Progress -->
            @if ($seller && $seller->status == 1)
                <div style="padding:0.5rem 1.1rem 0.5rem 1.1rem;border-bottom:1px solid #e2e8f0;min-height:38px;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span style="font-weight:500;font-size:0.93rem;color:#64748b;">Wallet</span>
                        <span style="font-size:0.98rem;color:#10b981;font-weight:600;">
                            ₹{{ number_format($totalAmount) }}
                        </span>
                    </div>
                    <button class="btn btn-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#rechargeModal" style="font-size:0.79rem;min-height:28px;padding:0.25rem 0;">
                        <i class="fas fa-plus-circle me-1"></i> Recharge Wallet
                    </button>
                </div>
            @endif
            <hr class="my-0">
            <div class="p-0 mt-0">
                @include('partial.sellerdash.sidebar')
            </div>
        </div>
    </div>
    <div class="mobile-sidebar-backdrop" id="mobileSidebarBackdrop"></div>

    <header class="pc-header">
        <div class="header-wrapper">
            <!-- Mobile Menu Icon Button -->
            <div class="menu-icon" id="openMobileSidebar" style="display:none;">
                <i class="fa-solid fa-user"></i>
            </div>
            <!-- Logo element for mobile view (centered on mobile) -->
            <div class="mobile-header-logo align-items-center" style="display: none;">
                <img src="https://shipxpeed.com/assets/website/img/logo_without.png" alt="Logo" width="120" height="34" style="object-fit:contain;height:34px;">
            </div>
            <div class="me-auto pc-mob-drp flex-grow-1">
                <ul class="list-unstyled">
                    <li class="dropdown pc-h-item d-inline-flex d-md-none">
                        <div class="dropdown-menu pc-h-dropdown drp-search">
                            <form class="px-3">
                                <div class="form-group mb-0 d-flex align-items-center">
                                    <i data-feather="search"></i>
                                    <input type="search" class="form-control border-0 shadow-none" placeholder="Search here. . .">
                                </div>
                            </form>
                        </div>
                    </li>
                    <li class="pc-h-item d-none d-md-inline-flex">
                        <form class="header-search" id="awbSearchForm">
                            <i data-feather="search" class="icon-search"></i>
                            <input type="search" class="form-control" id="awbSearchInput" placeholder="Search AWB Number..." autocomplete="off">
                            <div class="search-suggestions" id="searchSuggestions" style="display: none;"></div>
                        </form>
                    </li>
                </ul>
            </div>
            @if ($seller->status == 1)
                <div class="d-flex align-items-center">
                    <div class="header-button-section">
                        <a href="javascript:void(0);" class="wallet-balance-link" style="text-decoration:none;">
                            <i class="fas fa-wallet me-2"></i>
                            <span class="balance-amount">₹{{ number_format($totalAmount) }}</span>
                        </a>
                        <button class="recharge" data-bs-toggle="modal" data-bs-target="#rechargeModal">
                            <i class="fas fa-plus-circle me-2"></i> 
                            <span>Recharge Wallet</span>
                        </button>
                    </div>
                </div>
            @endif
            <ul class="d-flex align-items-center list-unstyled mb-0">
                <li class="pc-h-item me-2">
                    <a href="{{ route('seller.ticket.get') }}" class="export-btn-ticket" style="padding: 5px 8px;">
                        <i class="fas fa-ticket-alt me-2"></i>
                        <span>Support</span>
                    </a>
                </li>
                <!-- Quick Action - desktop only -->
                <li class="pc-h-item me-2 quick-action-desktop">
                    <div class="dropdown" style="position:relative;">
                        <button class="dropdown-toggle" type="button" id="dropdownMenuButtonCustom" aria-expanded="false" autocomplete="off">
                            <i class="fas fa-bolt me-2"></i> Quick Actions
                            
                        </button>
                    </div>
                </li>
                <script>
                    (function () {
                        let open = false;
                        let triggerBtn = document.getElementById('dropdownMenuButtonCustom');
                        let dropdownMenu = null;

                        const dropdownItems = [
                            { href: "{{ route('ratecards') }}", text: "Rate Calculator" },
                            { href: "{{ route('seller.orderadd') }}", text: "Add Order" },
                            { href: "{{ route('track-order') }}", text: "Track Order" }
                        ];

                        function createDropdownMenu() {
                            let oldMenu = document.getElementById('custom-appended-menu');
                            if (oldMenu) oldMenu.remove();
                            const ul = document.createElement('ul');
                            ul.className = 'dropdown-menu show';
                            ul.id = 'custom-appended-menu';
                            ul.style.position = 'absolute';
                            ul.style.zIndex = 999999;
                            ul.style.minWidth = "150px";
                            ul.style.maxWidth = "220px";
                            ul.style.width = "100%";
                            ul.style.display = "flex";
                            ul.style.flexDirection = "column";
                            ul.style.boxShadow = "0 4px 24px rgba(80,80,120,0.13)";
                            ul.style.borderRadius = "17px";
                            ul.style.padding = "0.57rem 0";
                            ul.style.margin = "0";
                            ul.style.background = "#fff";
                            ul.style.fontSize = "1.01rem";
                            ul.style.border = "1.5px solid #ecebfa";
                            ul.style.right = "0";
                            ul.style.left = "auto";
                            dropdownItems.forEach(item => {
                                let li = document.createElement('li');
                                let a = document.createElement('a');
                                a.className = "dropdown-item";
                                a.href = item.href;
                                a.innerText = item.text;
                                a.style.whiteSpace = "nowrap";
                                a.style.fontWeight = "500";
                                a.style.color = "#34477b";
                                a.style.padding = "0.45rem 1.2rem";
                                li.appendChild(a);
                                ul.appendChild(li);
                            });
                            return ul;
                        }
                        function openDropdown() {
                            if (dropdownMenu) return;
                            dropdownMenu = createDropdownMenu();
                            const rect = triggerBtn.getBoundingClientRect();
                            let dropdownWidth = Math.max(140, Math.min(180, rect.width));
                            dropdownMenu.style.width = dropdownWidth + "px";
                            dropdownMenu.style.top = (window.scrollY + rect.bottom + 10) + "px";
                            dropdownMenu.style.left = (window.scrollX + rect.left) + "px";
                            document.body.appendChild(dropdownMenu);
                            open = true;
                        }
                        function closeDropdown() {
                            if (dropdownMenu && dropdownMenu.parentElement) {
                                dropdownMenu.parentElement.removeChild(dropdownMenu);
                                dropdownMenu = null;
                                open = false;
                            }
                        }
                        if (triggerBtn) {
                            triggerBtn.addEventListener('click', function (e) {
                                e.preventDefault();
                                e.stopPropagation();
                                if (open) {
                                    closeDropdown();
                                } else {
                                    openDropdown();
                                }
                            });
                            window.addEventListener('resize', function () { if (open) { closeDropdown(); } });
                            window.addEventListener('scroll', function () { if (open) { closeDropdown(); } });
                            document.addEventListener('click', function (e) {
                                if (open && (!dropdownMenu || !dropdownMenu.contains(e.target)) && e.target !== triggerBtn) {
                                    closeDropdown();
                                }
                            });
                        }
                    })();
                </script>
                <!-- Profile - desktop only -->
                <li class="pc-h-item me-2 profile-desktop">
                    <div class="dropdown" style="position:relative;">
                        <button id="dropdownMenuButtonProfile" aria-expanded="false" autocomplete="off" type="button" style="background:transparent; border:none;">
                            <img src="{{ asset('assets/website/img/profile.png') }}" alt="Seller Image" class="user-avtar" width="40" height="40" style="object-fit:cover; border-radius:50%;">
                        </button>
                    </div>
                </li>
                <script>
                (function () {
                    let open = false;
                    let triggerBtn = document.getElementById('dropdownMenuButtonProfile');
                    let dropdownMenu = null;

                    const dropdownItems = [
                        { href: "{{ route('profile.get') }}", text: "Edit" },
                         { href: "#", text: "Logout", logout: true }
                    ];

                    function createProfileDropdownMenu() {
                        let oldMenu = document.getElementById('profile-appended-menu');
                        if (oldMenu) oldMenu.remove();

                        const ul = document.createElement('ul');
                        ul.className = 'dropdown-menu show';
                        ul.id = 'profile-appended-menu';
                        ul.style.position = 'absolute';
                        ul.style.zIndex = 12000;
                        ul.style.minWidth = "95px";
                        ul.style.maxWidth = "168px";
                        ul.style.width = "92px";
                        ul.style.display = "flex";
                        ul.style.flexDirection = "column";
                        ul.style.boxShadow = "0 4px 16px rgba(80,80,120,0.11)";
                        ul.style.borderRadius = "14px";
                        ul.style.padding = "0.4rem 0";
                        ul.style.margin = "0";
                        ul.style.background = "#fff";
                        ul.style.fontSize = "0.95rem";
                        ul.style.border = "1.5px solid #ecebfa";
                        ul.style.right = "0";
                        ul.style.left = "auto";

                        // dropdownItems.forEach(item => {
                        //     let li = document.createElement('li');
                        //     let a = document.createElement('a');
                        //     a.className = "dropdown-item";
                        //     a.href = item.href;
                        //     a.innerText = item.text;
                        //     a.style.whiteSpace = "nowrap";
                        //     a.style.fontWeight = "500";
                        //     a.style.color = "#34477b";
                        //     a.style.padding = "0.38rem 1.1rem";
                        //     li.appendChild(a);
                        //     ul.appendChild(li);
                        // });
                        dropdownItems.forEach(item => {
    let li = document.createElement('li');
    let a = document.createElement('a');

    a.className = "dropdown-item";
    a.innerText = item.text;

    if(item.logout){
        a.href = "#";
        a.addEventListener("click", function(e){
            e.preventDefault();
            document.getElementById("logout-form").submit();
        });
    }else{
        a.href = item.href;
    }

    a.style.whiteSpace = "nowrap";
    a.style.fontWeight = "500";
    a.style.color = "#34477b";
    a.style.padding = "0.38rem 1.1rem";

    li.appendChild(a);
    ul.appendChild(li);
});
                        return ul;
                    }

                    function openDropdown() {
                        if (dropdownMenu) return;
                        dropdownMenu = createProfileDropdownMenu();

                        const rect = triggerBtn.getBoundingClientRect();
                        dropdownMenu.style.top = (window.scrollY + rect.bottom + 8) + "px";
                        dropdownMenu.style.left = (window.scrollX + rect.left) + "px";
                        document.body.appendChild(dropdownMenu);
                        open = true;
                    }
                    function closeDropdown() {
                        if (dropdownMenu && dropdownMenu.parentElement) {
                            dropdownMenu.parentElement.removeChild(dropdownMenu);
                            dropdownMenu = null;
                            open = false;
                        }
                    }
                    if (triggerBtn) {
                        triggerBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            if (open) {
                                closeDropdown();
                            } else {
                                openDropdown();
                            }
                        });
                        window.addEventListener('resize', function () { if (open) { closeDropdown(); } });
                        window.addEventListener('scroll', function () { if (open) { closeDropdown(); } });
                        document.addEventListener('click', function (e) {
                            if (open && (!dropdownMenu || !dropdownMenu.contains(e.target)) && e.target !== triggerBtn) {
                                closeDropdown();
                            }
                        });
                    }
                })();
                </script>
            </ul>
        </div>
    </header>

    @yield('content')

    @include('partial.sellerdash.common.footer')
    
    <script>
        // --- Mobile menu sidebar open/close logic ---
        document.addEventListener('DOMContentLoaded', function () {
            const openBtn = document.getElementById('openMobileSidebar');
            const sidebarOffcanvas = document.getElementById('mobileSidebarOffcanvas');
            const closeBtn = document.getElementById('closeMobileSidebar');
            const sidebarBackdrop = document.getElementById('mobileSidebarBackdrop');

            function openSidebar() {
                sidebarOffcanvas.classList.add('show');
                sidebarOffcanvas.style.visibility = 'visible';
                sidebarBackdrop.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            function closeSidebar() {
                sidebarOffcanvas.classList.remove('show');
                sidebarOffcanvas.style.visibility = 'hidden';
                sidebarBackdrop.classList.remove('active');
                document.body.style.overflow = '';
            }
            function updateMenuButton() {
                if (window.innerWidth <= 768) {
                    openBtn.style.display = 'flex';
                } else {
                    openBtn.style.display = 'none';
                    closeSidebar();
                }
            }
            window.addEventListener('resize', updateMenuButton);
            updateMenuButton();

            openBtn.addEventListener('click', function () { openSidebar(); });
            closeBtn.addEventListener('click', function () { closeSidebar(); });
            sidebarBackdrop.addEventListener('click', function () { closeSidebar(); });
            document.addEventListener('keydown', (e) => {
                if (sidebarOffcanvas.classList.contains('show') && (e.key === 'Escape' || e.key === 'Esc')) {
                    closeSidebar();
                }
            });
            window.addEventListener('resize', function () {
                if (window.innerWidth > 768 && sidebarOffcanvas.classList.contains('show')) {
                    closeSidebar();
                }
            });

            function mobileHeaderSwitch() {
                const logo = document.querySelector('.mobile-header-logo');
                const quickAction = document.querySelector('.quick-action-desktop');
                const profile = document.querySelector('.profile-desktop');
                if (window.innerWidth <= 768) {
                    if (logo) logo.style.display = 'flex';
                    if (quickAction) quickAction.style.display = 'none';
                    if (profile) profile.style.display = 'none';
                } else {
                    if (logo) logo.style.display = 'none';
                    if (quickAction) quickAction.style.display = '';
                    if (profile) profile.style.display = '';
                }
            }
            mobileHeaderSwitch();
            window.addEventListener('resize', mobileHeaderSwitch);

            // --- Retain all original modal and AWB JS code below: ---
            var modalElements = document.querySelectorAll('.modal');
            modalElements.forEach(function(modalEl) {
                var modal = new bootstrap.Modal(modalEl);
                modalEl.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') { modal.hide(); }
                });
            });
            document.querySelectorAll('.modal-action-item').forEach(function(item) {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
            document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function(trigger) {
                trigger.addEventListener('click', function() {
                    var targetModal = document.querySelector(this.getAttribute('data-bs-target'));
                    if (targetModal) {
                        targetModal.style.display = 'block';
                        setTimeout(function() { targetModal.classList.add('show'); }, 10);
                    }
                });
            });
            function handleModalResize() {
                var modals = document.querySelectorAll('.modal.show');
                modals.forEach(function(modal) {
                    var modalDialog = modal.querySelector('.modal-dialog');
                    if (window.innerWidth < 576) {
                        modalDialog.style.margin = '1rem';
                    } else {
                        modalDialog.style.margin = '1.75rem auto';
                    }
                });
            }
            window.addEventListener('resize', handleModalResize);
            var remainingDropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            remainingDropdowns.forEach(function(dropdown) {
                new bootstrap.Dropdown(dropdown);
            });

            // AWB Search as before
            const awbSearchInput = document.getElementById('awbSearchInput');
            const searchSuggestions = document.getElementById('searchSuggestions');
            const awbSearchModal = new bootstrap.Modal(document.getElementById('awbSearchModal'));
            const searchResults = document.getElementById('awbSearchResults');
            let searchTimeout;
            if (awbSearchInput) {
                awbSearchInput.addEventListener('input', function(e) {
                    const query = e.target.value.trim();
                    clearTimeout(searchTimeout);
                    if (query.length >= 3) {
                        searchSuggestions.innerHTML = '<div class="search-suggestion-item"><i class="loading-spinner"></i> Searching...</div>';
                        searchSuggestions.style.display = 'block';
                        searchTimeout = setTimeout(() => {
                            searchAWB(query);
                        }, 500);
                    } else {
                        searchSuggestions.style.display = 'none';
                    }
                });
                awbSearchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const query = e.target.value.trim();
                        if (query.length >= 3) {
                            showOrderDetails(query);
                        }
                    }
                });
                document.addEventListener('click', function(e) {
                    if (!awbSearchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                        searchSuggestions.style.display = 'none';
                    }
                });
            }
            function searchAWB(query) {
                fetch(`/api/search-awb?q=${encodeURIComponent(query)}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.orders && data.orders.length > 0) {
                        let suggestions = '';
                        data.orders.forEach(order => {
                            suggestions += `
                                <div class="search-suggestion-item" onclick="showOrderDetails('${order.awb_number}')">
                                    <strong>${order.awb_number}</strong> - ${order.receiver_name}
                                    <br><small class="text-muted">${order.receiver_address}</small>
                                </div>
                            `;
                        });
                        searchSuggestions.innerHTML = suggestions;
                    } else {
                        searchSuggestions.innerHTML = '<div class="search-suggestion-item">No orders found</div>';
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchSuggestions.innerHTML = '<div class="search-suggestion-item">Search error occurred</div>';
                });
            }
            function showOrderDetails(awbNumber) {
                searchSuggestions.style.display = 'none';
                searchResults.innerHTML = `
                    <div class="text-center py-4">
                        <div class="loading-spinner mb-3"></div>
                        <p>Loading order details...</p>
                    </div>
                `;
                awbSearchModal.show();
                const modalElement = document.getElementById('awbSearchModal');
                modalElement.style.zIndex = '999999';
                modalElement.style.position = 'fixed';
                fetch(`/api/order-details/${encodeURIComponent(awbNumber)}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.order) {
                        displayOrderDetails(data.order);
                    } else {
                        searchResults.innerHTML = `
                            <div class="no-results">
                                <i class="fas fa-search"></i>
                                <h5>No Order Found</h5>
                                <p>No order found with AWB number: <strong>${awbNumber}</strong></p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching order details:', error);
                    searchResults.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h5>Error</h5>
                            <p>An error occurred while fetching order details.</p>
                        </div>
                    `;
                });
            }
            function displayOrderDetails(order) {
                const statusClass = `status-${order.status ? order.status.toLowerCase().replace(' ', '-') : 'pending'}`;
                searchResults.innerHTML = `
                    <div class="order-detail-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1">AWB: ${order.awb_number || 'N/A'}</h5>
                                <p class="text-muted mb-0">Order ID: ${order.id || 'N/A'}</p>
                            </div>
                            <span class="order-status ${statusClass}">
                                ${order.status || 'Pending'}
                            </span>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-user me-2"></i>Sender Details</h6>
                                <div class="detail-row">
                                    <span class="detail-label">Name:</span>
                                    <span class="detail-value">${order.sender_name || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value">${order.sender_phone || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Address:</span>
                                    <span class="detail-value">${order.sender_address || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Pincode:</span>
                                    <span class="detail-value">${order.sender_pincode || 'N/A'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>Receiver Details</h6>
                                <div class="detail-row">
                                    <span class="detail-label">Name:</span>
                                    <span class="detail-value">${order.receiver_name || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value">${order.receiver_phone || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Address:</span>
                                    <span class="detail-value">${order.receiver_address || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Pincode:</span>
                                    <span class="detail-value">${order.receiver_pincode || 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-box me-2"></i>Package Details</h6>
                                <div class="detail-row">
                                    <span class="detail-label">Weight:</span>
                                    <span class="detail-value">${order.weight || 'N/A'} kg</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Length:</span>
                                    <span class="detail-value">${order.length || 'N/A'} cm</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Width:</span>
                                    <span class="detail-value">${order.width || 'N/A'} cm</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Height:</span>
                                    <span class="detail-value">${order.height || 'N/A'} cm</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3"><i class="fas fa-rupee-sign me-2"></i>Payment Details</h6>
                                <div class="detail-row">
                                    <span class="detail-label">COD Amount:</span>
                                    <span class="detail-value">₹${order.cod_amount || '0'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Shipping Cost:</span>
                                    <span class="detail-value">₹${order.shipping_cost || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Payment Mode:</span>
                                    <span class="detail-value">${order.payment_mode || 'N/A'}</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Created:</span>
                                    <span class="detail-value">${order.created_at ? new Date(order.created_at).toLocaleDateString('en-IN') : 'N/A'}</span>
                                </div>
                            </div>
                        </div>
                        ${order.tracking_url ? `
                            <hr>
                            <div class="text-center">
                                <a href="${order.tracking_url}" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-external-link-alt me-2"></i>Track Order
                                </a>
                            </div>
                        ` : ''}
                    </div>
                `;
            }
            window.showOrderDetails = showOrderDetails;
        });
    </script>
</body>
</html>
