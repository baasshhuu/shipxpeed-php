<style>
/* Sidebar Collapse/Expand Styles - Overlap on content, always on top */
#sidebar {
    width: 75px;
    background:  linear-gradient(0deg, #252C42 0%, #3A39C4 100%);
    transition: all 0.25s cubic-bezier(.4,0,.2,1);
    box-shadow: 0 0 6px rgba(0,0,0,0.12);
    z-index: 99999 !important; /* High z-index for overlap */
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
}

#sidebar:hover, #sidebar.sidebar-expanded {
    width: 240px;
    min-width: 240px;
    max-width: 240px;
}

#sidebar .sidebar-logo-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px 0 10px 0;
    min-height: 106px;
    transition: all 0.25s cubic-bezier(.4,0,.2,1);
}

#sidebar .navbar-logo {
    background: white;
    padding: 11px;
    border-radius: 50%;
    height: 56px;
    width: 56px;
    object-fit: contain;
    margin: 0 auto;
    box-shadow: 0 2px 12px rgba(100,100,255,0.08);
    display: block;
    transition: all 0.2s;
}

#sidebar .sidebar-appname {
    font-size: 0;
    opacity: 0;
    margin-top: 10px;
    color: white;
    font-weight: 700;
    text-transform: initial;
    line-height: 1.2;
    white-space: nowrap;
    transition: font-size 0.2s, opacity 0.2s;
}

#sidebar:hover .sidebar-appname,
#sidebar.sidebar-expanded .sidebar-appname {
    font-size: 24px;
    opacity: 1;
}

#sidebar .menu-categories li.menu > a div span,
#sidebar .menu-categories li.menu > a div ~ div {
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s;
    width: 0;
    display: inline-block;
}

#sidebar:hover .menu-categories li.menu > a div span,
#sidebar:hover .menu-categories li.menu > a div ~ div,
#sidebar.sidebar-expanded .menu-categories li.menu > a div span,
#sidebar.sidebar-expanded .menu-categories li.menu > a div ~ div {
    opacity: 1;
    pointer-events: auto;
    width: auto;
}

#sidebar .menu-categories li.menu > a div {
    display: flex;
    align-items: center;
    gap: 10px;
}

#sidebar .menu-categories li.menu > a div i {
    font-size: 1.25em;
}

#sidebar .shadow-bottom {
    display: none;
}

/* Fix: Let menu-categories take only available height & scroll */
#sidebar .menu-categories {
    padding-left: 6px !important;
    padding-right: 6px !important;
    flex: 1 1 auto;
    overflow-y: auto;
    max-height: calc(100vh - 136px); /* 106px logo wrapper + 20px margin for possible paddings */
    min-height: 0;
    /* Hide scrollbar but still allow scroll */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none;  /* IE and Edge */
}
#sidebar .menu-categories::-webkit-scrollbar {
    width: 0 !important;
    height: 0 !important;
    display: none !important;
    background: transparent !important;
}
#sidebar .menu-categories::-webkit-scrollbar-thumb {
    background: transparent !important;
    border-radius: 0 !important;
    display: none !important;
}

/* Remove previous custom scroll styles */

#sidebar .submenu {
    display: none !important;
}

#sidebar .menu.open > .submenu {
    display: block !important;
}

#sidebar .menu-categories li.menu > a > div > .fa-chevron-right {
    opacity: 0;
    transition: opacity 0.2s, transform 0.2s;
}

#sidebar:hover .menu-categories li.menu > a > div > .fa-chevron-right,
#sidebar.sidebar-expanded .menu-categories li.menu > a > div > .fa-chevron-right,
#sidebar .menu.open > a > div > .fa-chevron-right {
    opacity: 1;
}

#sidebar .menu.open > a > div > .fa-chevron-right {
    transform: rotate(90deg);
    transition: transform 0.2s;
}

/* Mobile specific sidebar */
@media (max-width: 767px) {
    #sidebar, #sidebar:hover, #sidebar.sidebar-expanded {
        width: fit-content !important;
        min-width: fit-content !important;
        max-width: 90vw !important;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 99999 !important;
        height: 100vh !important;
        box-shadow: 0 0 8px rgba(0,0,0,0.22);
        /* border-radius: 0 16px 16px 0; */
        background: linear-gradient(0deg, #252C42 0%, #3A39C4 100%);
    }
    #sidebar .sidebar-close-btn {
        display: flex !important;
    }
    #sidebar .menu-categories li.menu > a div span,
    #sidebar .menu-categories li.menu > a div ~ div {
        opacity: 1 !important;
        pointer-events: auto !important;
        width: auto;
    }
    /* Remove display: block for submenu to enable JS toggle on mobile */
    /* #sidebar .submenu {
        display: block !important;
    } */
    #sidebar .sidebar-appname {
        font-size: 20px !important;
        opacity: 1 !important;
    }
    /* Fix: Ensure menu-categories scroll on mobile, not the whole sidebar */
    #sidebar .menu-categories {
        max-height: calc(100vh - 136px);
        min-height: 0;
        overflow-y: auto !important;
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }
    #sidebar .menu-categories::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;
        display: none !important;
        background: transparent !important;
    }
}

.sidebar-close-btn {
    display: none;
    position: absolute;
    top: 12px;
    right: 12px;
    font-size: 28px;
    color: #fff;
    background: transparent;
    border: none;
    z-index: 100001;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    line-height: 1;
    /* Improved clickable area on mobile */
    width: 40px;
    height: 40px;
}

.sidebar-close-btn:active, .sidebar-close-btn:focus {
    outline: none;
}
</style>
<nav id="sidebar" style="box-sizing: border-box; position: fixed">

    <button id="sidebarCloseBtn" class="sidebar-close-btn" type="button" aria-label="Close Sidebar" style="display:none;">
        <span aria-hidden="true">&times;</span>
    </button>
    <div class="shadow-bottom"></div>
    <div class="sidebar-logo-wrapper">
        <img src="{{ asset('storage/' . $site_settings['favicon']) }}" class="navbar-logo" alt="logo" />
        <span class="sidebar-appname">{{ $site_settings['application_name'] }}</span>
    </div>
    <ul class="list-unstyled menu-categories" id="accordionExample" tabindex="0">
        <!-- ...your menu items remain unchanged... -->
        <li class="menu @routeis('dashboard') active @endrouteis">
            <a href="{{ route('dashboard') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-duotone fa-house"></i>
                    <span>Dashboard</span>
                </div>
            </a>
        </li>
        <!-- <li class="menu @routeis('order.status.upload.form') active @endrouteis">
            <a href="{{ route('order.status.upload.form') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-upload"></i>
                    <span>Order Status Upload</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('SendWhatsApp.index') active @endrouteis">
            <a href="{{ route('SendWhatsApp.index') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>SendWhatsApp Messages</span>
                </div>
            </a>
        </li> -->
        <li class="menu @routeis('seller-list') active @endrouteis">
            <a href="{{ route('seller-list') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-list"></i>
                    <span>Seller List</span>
                </div>
            </a>
        </li>
        <!-- <li class="menu @routeis('get.orders') active @endrouteis">
            <a href="{{ route('get.orders') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-bars-progress"></i>
                    <span>Order Status</span>
                </div>
            </a>
        </li> -->
        <li class="menu @routeis('get.rto.page') active @endrouteis">
            <a href="{{ route('get.rto.page') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-barcode"></i>
                    <span>RTO Amount</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('invoices.add') active @endrouteis">
            <a href="{{ route('invoices.add') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-money-check-dollar"></i>
                    <span>Seller Invoice</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('logistics') active @endrouteis">
            <a href="{{ route('logistics') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Logistics</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('pricesetting') active @endrouteis">
            <a href="{{ route('pricesetting.add') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-tags"></i>
                    <span>Price Setting</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('zone.price.index') active @endrouteis">
            <a href="{{ route('zone.price.index') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-gear"></i>
                    <span>Zone Price Setting</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('active.slebs') active @endrouteis">
            <a href="{{ route('active.slebs.add') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-toggle-on"></i>
                    <span>Active Slebs</span>
                </div>
            </a>
        </li>
        <!-- <li class="menu @routeis('beyond-work') active @endrouteis">
            <a href="{{ route('beyond-work') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-champagne-glasses"></i>
                    <span>Beyond Work</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('landing-brands') active @endrouteis">
            <a href="{{ route('landing-brands') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-globe"></i>
                    <span>Landing Brand</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('team-spirit') active @endrouteis">
            <a href="{{ route('team-spirit') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-handshake"></i>
                    <span>Team Spirit</span>
                </div>
            </a>
        </li> -->
        <li class="menu @routeis('weight.dispatching.index') active @endrouteis">
            <a href="{{ route('weight.dispatching.index') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-weight-scale"></i>
                    <span>Weight Dispatching</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('codremittance.index') active @endrouteis">
            <a href="{{ route('codremittance.index') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-indian-rupee-sign"></i>
                    <span>COD Remittance</span>
                </div>
            </a>
        </li>
        <li class="menu @routeis('negative-balance.index') active @endrouteis">
            <a href="{{ route('negative-balance.index') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-arrow-trend-down"></i>
                    <span>Negative Balance</span>
                </div>
            </a>
        </li>
        {{-- <li class="menu @routeis('kyc-manage') active @endrouteis">
            <a href="{{ route('profile-manage') }}" class="dropdown-toggle">
                <div>
                    <i class="fa-solid fa-user-check"></i>
                    <span>Profile</span>
                </div>
            </a>
        </li> --}}
        @if(Helper::userCan([102,103]))
        <li class="menu @routeis('roles,users') active @endrouteis">
            <a href="#ticket" class="dropdown-toggle sidebar-has-dropdown" data-bs-toggle="collapse" aria-expanded="false">
                <div>
                    <i class="fa-solid fa-ticket"></i>
                    <span>Tickets</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>
            <ul class="collapse submenu list-unstyled" id="ticket"
                data-bs-parent="#accordionExample">
                <li class="@routeis('ticket') active @endrouteis">
                    <a href="{{ route('ticket') }}">Tickets</a>
                </li>
            </ul>
        </li>
        <!-- @endif
        @if(Helper::userCan([102,103]))
        <li class="menu @routeis('roles,users') active @endrouteis">
            <a href="#master" class="dropdown-toggle sidebar-has-dropdown" data-bs-toggle="collapse" aria-expanded="false">
                <div>
                    <i class="fa-solid fa-sparkles"></i>
                    <span>Master</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>
            <ul class="collapse submenu list-unstyled" id="master"
                data-bs-parent="#accordionExample">
                @if(Helper::userCan(102))
                <li class="@routeis('roles') active @endrouteis">
                    <a href="{{ route('roles') }}">Roles</a>
                </li>
                @endif
                @if(Helper::userCan(103))
                <li class="@routeis('users') active @endrouteis">
                    <a href="{{ route('users') }}">Sub Admins</a>
                </li>
                @endif
            </ul>
        </li> -->
        @endif
        @if(Helper::userCan([107]))
        <li class="menu @routeis('roles') active @endrouteis">
            <a href="#wallet" class="dropdown-toggle sidebar-has-dropdown" data-bs-toggle="collapse" aria-expanded="false">
                <div>
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>Transaction</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>
            <ul class="collapse submenu list-unstyled" id="wallet"
                data-bs-parent="#accordionExample">
                @if(Helper::userCan(107))
                <li class="@routeis('recharges') active @endrouteis">
                    <a href="{{ route('recharges') }}">Recharge</a>
                </li>
                @endif
                @if(Helper::userCan(107))
                <li class="@routeis('balance') active @endrouteis">
                    <a href="{{ route('balance') }}">Balance</a>
                </li>
                @endif
            </ul>
        </li>
        @endif
        @if(Helper::userCan([102,103]))
        <li class="menu @routeis('get-in-touch,inquiries') active @endrouteis">
            <a href="#forms-detail" class="dropdown-toggle sidebar-has-dropdown" data-bs-toggle="collapse" aria-expanded="false">
                <div>
                    <i class="fa-solid fa-clipboard"></i>
                    <span>Forms Detail</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>
            <ul class="collapse submenu list-unstyled" id="forms-detail"
                data-bs-parent="#accordionExample">
                @if(Helper::userCan(102))
                <li class="@routeis('get-in-touch') active @endrouteis">
                    <a href="{{ route('get-in-touch') }}">Get In Touch</a>
                </li>
                @endif
                @if(Helper::userCan(102))
                <li class="@routeis('inquiries') active @endrouteis">
                    <a href="{{ route('inquiries') }}">Inquiries</a>
                </li>
                @endif 
                @if(Helper::userCan(102))
                <li class="@routeis('careers-details') active @endrouteis">
                    <a href="{{ route('careers-details') }}">Career Details</a>
                </li>
                @endif
            </ul>
        </li>
        <!-- @endif
        @if(Helper::userCan([104]))
        <li class="menu @routeis('sliders,testimonials,cms,faq,enquiries,admin-banners') active @endrouteis">
            <a href="#static_content" class="dropdown-toggle sidebar-has-dropdown" data-bs-toggle="collapse" aria-expanded="false">
                <div>
                    <i class="fa-sharp fa-solid fa-photo-film"></i>
                    <span>Content</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>
            <ul class="collapse submenu list-unstyled" id="static_content" data-bs-parent="#accordionExample">
                @if(Helper::userCan(104))
                <li class="@routeis('testimonial') active @endrouteis">
                    <a href="{{ route('testimonial') }}">Testimonial</a>
                </li>
                @endif
                @if(Helper::userCan(104))
                <li class="@routeis('careers') active @endrouteis">
                    <a href="{{ route('careers') }}">Career</a>
                </li>
                @endif
                @if(Helper::userCan(104))
                <li class="@routeis('privacy-policy') active @endrouteis">
                    <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
                </li>
                @endif
                @if(Helper::userCan(104))
                <li class="@routeis('refund-policy') active @endrouteis">
                    <a href="{{ route('refund-policy') }}">Refund Policy</a>
                </li>
                @endif
                @if(Helper::userCan(104))
                <li class="@routeis('terms-and-condition') active @endrouteis">
                    <a href="{{ route('terms-and-condition') }}">Terms And Condition</a>
                </li>
                @endif
            </ul>
        </li> -->
        @endif
        @if(Helper::userCan([105,106]))
        <li class="menu @routeis('states,cities') active @endrouteis">
            <a href="#location_content" class="dropdown-toggle sidebar-has-dropdown" data-bs-toggle="collapse" aria-expanded="false">
                <div>
                    <i class="fa-duotone fa-location-dot"></i>
                    <span>Location</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>
            <ul class="collapse submenu list-unstyled" id="location_content"
                data-bs-parent="#accordionExample">
                @if(Helper::userCan(105))
                <li class="@routeis('states') active @endrouteis">
                    <a class="nav-link" href="{{ route('states') }}">States</a>
                </li>
                @endif
                @if(Helper::userCan(106))
                <li class="@routeis('cities') active @endrouteis">
                    <a class="nav-link" href="{{ route('cities') }}">Cities</a>
                </li>
                @endif
            </ul>
        </li>
        @endif
        @if(Helper::userCan(101))
        <li class="menu @routeis('setting') active @endrouteis">
            <a href="#setting" class="dropdown-toggle sidebar-has-dropdown" data-bs-toggle="collapse" aria-expanded="false">
                <div>
                    <i class="fa fa-cog my-auto"></i>
                    <span>App Setting</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>
            <ul class="collapse submenu list-unstyled" id="setting"
                data-bs-parent="#accordionExample">
                @foreach(config('constant.setting_array', []) as $key => $setting)
                <li class="@if(request()->path() == 'setting/'.$key) active @endif">
                    <a class="nav-link" href="{{ route('setting', ['id' => $key]) }}">
                        {{ $setting }}
                    </a>
                </li>
                @endforeach
            </ul>
        </li>
        <li class="menu">
            <a href="{{route('database_backup')}}" class="dropdown-toggle">
                <div>
                    <i class="fa-duotone fa-database"></i>
                    <span>Database Backup</span>
                </div>
            </a>
        </li>
        <li class="menu  @routeis('server-control') active @endrouteis">
            <a href="{{ route('server-control') }}" aria-expanded="false" class="dropdown-toggle">
                <div>
                    <i class="fa-duotone fa-server"></i>
                    <span>Server Control Panel</span>
                </div>
            </a>
        </li>
        @endif
        <!-- @if(Helper::userCan(101))
        <li class="menu @routeis('setting') active @endrouteis">
            <a href="" class="dropdown-toggle sidebar-has-dropdown" data-bs-toggle="collapse" aria-expanded="false">
                <div>
                    <i class="fa fa-cog my-auto"></i>
                    <span>Others</span>
                </div>
                <div><i class="fa-solid fa-chevron-right"></i></div>
            </a>
            <ul class="collapse submenu list-unstyled" id="setting"
                data-bs-parent="#accordionExample">
                <li class="menu @routeis('beyond-work') active @endrouteis">
                    <a href="{{ route('beyond-work') }}" class="dropdown-toggle">
                        <div>
                            <i class="fa-solid fa-champagne-glasses"></i>
                            <span>Beyond Work</span>
                        </div>
                    </a>
                </li>
                <li class="menu @routeis('landing-brands') active @endrouteis">
                    <a href="{{ route('landing-brands') }}" class="dropdown-toggle">
                        <div>
                            <i class="fa-solid fa-globe"></i>
                            <span>Landing Brand</span>
                        </div>
                    </a>
                </li>
                <li class="menu @routeis('team-spirit') active @endrouteis">
                    <a href="{{ route('team-spirit') }}" class="dropdown-toggle">
                        <div>
                            <i class="fa-solid fa-handshake"></i>
                            <span>Team Spirit</span>
                        </div>
                    </a>
                </li>
            </ul>
        </li>
       
        @endif -->
    </ul>
</nav>
<script>
function isMobileSidebar() {
    return window.innerWidth <= 767;
}

function sidebarExpandMobile() {
    var sidebar = document.getElementById('sidebar');
    var closeBtn = document.getElementById('sidebarCloseBtn');
    // if(isMobileSidebar()){
    //     sidebar.classList.add('sidebar-expanded');
    //     if (closeBtn) closeBtn.style.display = 'flex';
    // } else {
    //     sidebar.classList.remove('sidebar-expanded');
    //     if (closeBtn) closeBtn.style.display = 'none';
       
    //     sidebar.style.display = '';
    // }


    if (isMobileSidebar()) {
        // ❌ REMOVE auto expand
        sidebar.classList.remove('sidebar-expanded'); 
        sidebar.style.display = 'none'; // 👈 hide by default

        if (closeBtn) closeBtn.style.display = 'flex';
    } else {
        sidebar.classList.remove('sidebar-expanded');
        sidebar.style.display = ''; // reset for desktop

        if (closeBtn) closeBtn.style.display = 'none';
    }
}

// Ensure proper resize/scrolling behavior also for menu-categories
window.addEventListener('resize', function() {
    sidebarExpandMobile();
    var sidebar = document.getElementById('sidebar');
    var menuCategories = sidebar.querySelector('.menu-categories');
    if(menuCategories) {
        // (optional) could trigger scroll restoration here
    }
});

window.addEventListener('DOMContentLoaded', function() {
    sidebarExpandMobile();

    var sidebar = document.getElementById('sidebar');
    var closeBtn = document.getElementById('sidebarCloseBtn');
    var dropdownLinks = sidebar.querySelectorAll('.sidebar-has-dropdown');

    function closeOtherMenus(currentMenu) {
        sidebar.querySelectorAll('li.menu.open').forEach(function(menuLi) {
            if(menuLi !== currentMenu) {
                menuLi.classList.remove('open');
                var aTag = menuLi.querySelector('a.sidebar-has-dropdown');
                if(aTag) aTag.setAttribute('aria-expanded', 'false');
                var submenu = menuLi.querySelector('.submenu');
                if(submenu) submenu.classList.remove('show');
                // Also hide submenu for mobile
                if(isMobileSidebar() && submenu) submenu.style.display = 'none';
            }
        });
    }

    dropdownLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var parentMenu = link.closest('li.menu');
            var submenu = parentMenu.querySelector('ul.submenu');

            var isOpen = parentMenu.classList.contains('open');

            if(!isOpen) {
                closeOtherMenus(parentMenu);
                parentMenu.classList.add('open');
                if(submenu) {
                    submenu.classList.add('show');
                    // Show the submenu on mobile
                    if(isMobileSidebar()){
                        submenu.style.display = 'block';
                    }
                }
                link.setAttribute('aria-expanded', 'true');
            } else {
                parentMenu.classList.remove('open');
                if(submenu) {
                    submenu.classList.remove('show');
                    // Hide the submenu on mobile
                    if(isMobileSidebar()){
                        submenu.style.display = 'none';
                    }
                }
                link.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // Optional: allow click outside to close all open menus
    document.addEventListener('click', function(event) {
        if(!sidebar.contains(event.target)) {
            sidebar.querySelectorAll('li.menu.open').forEach(function(menuLi) {
                menuLi.classList.remove('open');
                var aTag = menuLi.querySelector('a.sidebar-has-dropdown');
                if(aTag) aTag.setAttribute('aria-expanded', 'false');
                var submenu = menuLi.querySelector('.submenu');
                if(submenu) {
                    submenu.classList.remove('show');
                    // Hide submenu for mobile
                    if(isMobileSidebar()){
                        submenu.style.display = 'none';
                    }
                }
            });
        }
    });

    // On resize, make sure dropdown submenus in mobile display correctly
    window.addEventListener('resize', function() {
        sidebar.querySelectorAll('.submenu').forEach(function(submenu) {
            // If parent is open and mobile, show, else hide
            var parentMenu = submenu.closest('li.menu');
            if(isMobileSidebar() && parentMenu && parentMenu.classList.contains('open')) {
                submenu.style.display = 'block';
            } else if(isMobileSidebar()) {
                submenu.style.display = 'none';
            } else {
                submenu.style.display = '';
            }
        });
    });

    // Initial adjustment for mobile dropdowns
    sidebar.querySelectorAll('.submenu').forEach(function(submenu) {
        var parentMenu = submenu.closest('li.menu');
        if(isMobileSidebar()) {
            if (parentMenu && parentMenu.classList.contains('open')) {
                submenu.style.display = 'block';
            } else {
                submenu.style.display = 'none';
            }
        } else {
            submenu.style.display = '';
        }
    });

    // Close button for sidebar on mobile
    if(closeBtn) {
        closeBtn.addEventListener('click', function(){
            var sidebar = document.getElementById('sidebar');
            // Only hide sidebar if in mobile view
            if(isMobileSidebar()) {
                sidebar.style.display = 'none';
            }
        });
    }

    // Optionally scroll focused menu item into view on load (accessibility)
    var menuCategories = sidebar.querySelector('.menu-categories');
    var activeMenuLi = menuCategories && menuCategories.querySelector('li.menu.active');
    if (activeMenuLi && menuCategories) {
        activeMenuLi.scrollIntoView({ block: 'nearest' });
    }

});
</script>