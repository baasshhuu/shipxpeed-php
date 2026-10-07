   <!-- Bootstrap Modal -->

<header id="header" class="header position-sticky top-0" style="z-index:1030;">
  <div class="container-xxl d-flex align-items-center justify-content-between px-2 header-inner-nav" style="width:100%;">
    <!-- Desktop Nav with logo INSIDE rounded nav bar even when scrolled -->
    <nav
      class="main-nav-content d-none d-xl-flex flex-row align-items-center justify-content-between shadow nav-elevated"
    >
      <!-- Left: Logo sits flush to left, but inside border radius -->
      <div class="header-logo d-flex align-items-center flex-shrink-0 header-logo-wrapper">
        <a href="{{ route('home') }}" class="d-flex align-items-center text-decoration-none logo py-0 px-2">
          <img src="{{ asset('storage/' . $site_settings['logo']) }}"
               alt="Shipxpeed Logo"
               class="main-logo"
               style="height:30px;max-width:100px;object-fit:contain;transition:height 0.18s;">
        </a>
      </div>
      <ul class="center-nav-list d-flex flex-row align-items-center justify-content-center list-unstyled mb-0"
          style="gap:0.25rem;transition:gap 0.18s;">
        <!-- Features Dropdown -->
        <li class="dropdown">
          <a href="#" class="nav-link dropdown-toggle px-3 py-2 rounded-pill d-flex align-items-center nav-icon-text"
             id="featuresDropdown"
             data-bs-toggle="dropdown"
             aria-expanded="false"
             style="color:#183153;">
            <span class="d-flex align-items-center" style="gap:0.05em;">
              <span>Features</span>
              <i class="bi bi-chevron-down ms-1" style="font-size:0.7em; color:#0171d3;"></i>
            </span>
          </a>
          <ul class="dropdown-menu mt-2 border-0 shadow-sm rounded-3 py-2 px-2 min-w-200" aria-labelledby="featuresDropdown"
              style="font-size:.95rem;">
            <li>
              <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('ndr.follow.ups') }}">
                <span class="d-flex align-items-center">
                  <i class="bi bi-arrow-right-circle text-primary"></i>
                  <span style="margin-left:0.4em;">NDR Follow-Ups</span>
                </span>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('ltl.shipments') }}">
                <span class="d-flex align-items-center">
                  <i class="bi bi-truck text-primary"></i>
                  <span style="margin-left:0.4em;">B2B LTL Shipments</span>
                </span>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('hyperlocal') }}">
                <span class="d-flex align-items-center">
                  <i class="bi bi-geo-alt-fill text-primary"></i>
                  <span style="margin-left:0.4em;">HyperLocal</span>
                </span>
              </a>
            </li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="nav-link dropdown-toggle px-3 py-2 rounded-pill d-flex align-items-center nav-icon-text"
             id="companyDropdown"
             data-bs-toggle="dropdown"
             aria-expanded="false"
             style="color:#183153;">
            <span class="d-flex align-items-center" style="gap:0.05em;">
              <span>Company</span>
              <i class="bi bi-chevron-down ms-1" style="font-size:0.7em; color:#28a745;"></i>
            </span>
          </a>
          <ul class="dropdown-menu mt-2 border-0 shadow-sm rounded-3 py-2 px-2 min-w-200" aria-labelledby="companyDropdown" style="font-size:.95rem;">
            <li>
              <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('about') }}">
                <i class="bi bi-people-fill text-success"></i>
                <span style="margin-left:0.4em;">About Us</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('contact') }}">
                <i class="bi bi-envelope-fill text-success"></i>
                <span style="margin-left:0.4em;">Contact Us</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('life-shipxpeed') }}">
                <i class="bi bi-heart-fill text-danger"></i>
                <span style="margin-left:0.4em;">Life@Shipxpeed</span>
              </a>
            </li>
          </ul>
        </li>
        <!-- Track Button -->
        <li>
          <a href="{{ route('track-order') }}" class="nav-link px-3 py-2 rounded-pill d-flex align-items-center nav-icon-text"
             style="color:#183153;">
            <span>Track</span>
          </a>
        </li>
      </ul>
      <!-- Right: Enquire Now + Sign Up -->
      <div class="header-actions d-flex align-items-center flex-shrink-0 gap-2 ms-2">
        <a href="#"
           data-bs-toggle="modal"
           data-bs-target="#exampleModal"
           class="btn enquire-btn-premium rounded-pill px-3 py-2 d-flex align-items-center justify-content-center"
           style="font-size:.98rem; min-width:100px; font-weight:400; gap:.32em; border: none; box-shadow:0 3px 18px rgba(33,124,229,0.09); background:linear-gradient(98deg, #eff7fe 0%, #e6f2fb 65%, #d9e9f6 100%); color:#1e3e68; letter-spacing:0;">
          <span>Enquire Now</span>
        </a>
        <a href="{{ route('register.get') }}"
           class="btn signup-btn-premium rounded-pill px-3 py-2 d-flex align-items-center justify-content-center"
           style="font-size:.98rem; min-width:98px; font-weight:400; gap:.36em; border: none; background: linear-gradient(92deg, #c4e3fb 0%, #e9f5fe 62%, #f5fcff 100%); color:#3379e6; box-shadow:0 4px 22px rgba(40,127,245,0.06); letter-spacing:0;">
          <span style="max-width:72px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">Sign Up</span>
        </a>
      </div>
    </nav>
    <!-- Hamburger for Mobile (circular background), HIDDEN when sidebar is open -->
    <button class=" d-inline-block d-xl-none btn btn-light border-0 p-0 ms-2"
            type="button"
            aria-label="Toggle navigation"
            id="mobileSidebarOpenBtn">
      <i class="bi bi-list fs-2 text-dark"></i>
    </button>
  </div>

  <!-- SIDEBAR NAV FOR MOBILE -->
  <div id="mobileSidebarNavOverlay" class="mobile-sidebar-overlay"></div>
  <aside id="mobileSidebarNav" class="mobile-sidebar-nav">
    <div class="d-flex justify-content-between align-items-center py-3 px-3 border-bottom">
      <!-- Logo left in sidebar header -->
      <a href="{{ route('home') }}" class="d-flex align-items-center text-decoration-none logo">
        <img src="{{ asset('storage/' . $site_settings['logo']) }}"
          alt="Shipxpeed Logo"
          class="main-logo"
          style="height:22px;max-width:70px;object-fit:contain;">
      </a>
      <!-- Close/Cross Icon (circular background) -->
      <button id="mobileSidebarCloseBtn" class="btn btn-light border-0 p-0 ms-2"
        style="width:44px;height:44px;min-width:44px;min-height:44px;display:flex;align-items:center;justify-content:center;border-radius:50%;box-shadow:none;">
        <i class="bi bi-x-lg fs-4 text-dark"></i>
      </button>
    </div>
    <ul class="list-unstyled mb-0 mobile-nav-list px-3">
      <li class="dropdown">
        <a href="#" 
           class="nav-link dropdown-toggle py-2 px-2 d-flex align-items-center" 
           id="mobileFeaturesDropdown"
           style="color:#183153;"
           onclick="event.preventDefault(); 
                    var el = document.getElementById('mobileFeaturesMenu'); 
                    if(el.classList.contains('show')) {
                      el.classList.remove('show');
                    } else {
                      el.classList.add('show');
                    }">
          <span class="d-flex align-items-center gap-1"><span>Features</span> 
           
          </span>
        </a>
        <ul id="mobileFeaturesMenu" class="collapse ms-4 mb-2">
          <li>
            <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('ndr.follow.ups') }}">
              <span class="d-flex align-items-center">
                <!-- <i class="bi bi-arrow-right-circle text-primary"></i> -->
                <span style="margin-left:0.4em;">NDR Follow-Ups</span>
              </span>
            </a>
          </li>
          <li>
            <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('ltl.shipments') }}">
              <span class="d-flex align-items-center">
                <!-- <i class="bi bi-truck text-primary"></i> -->
                <span style="margin-left:0.4em;">B2B LTL Shipments</span>
              </span>
            </a>
          </li>
          <li>
            <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('hyperlocal') }}">
              <span class="d-flex align-items-center">
                <!-- <i class="bi bi-geo-alt-fill text-primary"></i> -->
                <span style="margin-left:0.4em;">HyperLocal</span>
              </span>
            </a>
          </li>
        </ul>
      </li>
      <li class="dropdown">
        <a href="#" 
           class="nav-link dropdown-toggle py-2 px-2 d-flex align-items-center" 
           id="mobileCompanyDropdown" 
           style="color:#183153;"
           onclick="event.preventDefault(); 
                    var el = document.getElementById('mobileCompanyMenu'); 
                    if(el.classList.contains('show')) {
                      el.classList.remove('show');
                    } else {
                      el.classList.add('show');
                    }">
          <span class="d-flex align-items-center gap-1"><span>Company</span></span>
        </a>
        <ul id="mobileCompanyMenu" class="collapse ms-4 mb-2">
          <li>
            <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('about') }}">
              <!-- <i class="bi bi-people-fill text-success"></i> -->
              <span style="margin-left:0.4em;">About Us</span>
            </a>
          </li>
          <li>
            <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('contact') }}">
              <!-- <i class="bi bi-envelope-fill text-success"></i> -->
              <span style="margin-left:0.4em;">Contact Us</span>
            </a>
          </li>
          <li>
            <a class="dropdown-item py-2 d-flex align-items-center nav-icon-text rounded-pill" href="{{ route('life-shipxpeed') }}">
              <!-- <i class="bi bi-heart-fill text-danger"></i> -->
              <span style="margin-left:0.4em;">Life@Shipxpeed</span>
            </a>
          </li>
        </ul>
      </li>
      <li>
        <a href="{{ route('track-order') }}" class="nav-link py-2 px-2 d-flex align-items-center" style="color:#183153;">
          <span>Track</span>
        </a>
      </li>
      <!-- <li>
        <a href="{{ route('register.get') }}" class="nav-link py-2 px-2 d-flex align-items-center" style="color:#183153;">
          <span>Login/Register</span>
        </a>
      </li> -->
    </ul>
    <!-- Enquire Now + Sign Up at bottom -->
    <div class="px-3 mt-4 mb-3">
      <a href="#"
        data-bs-toggle="modal"
        data-bs-target="#exampleModal"
        class="btn enquire-btn-premium rounded-pill px-3 py-2 w-100 mb-2"
        style="font-size:.98rem;font-weight:400;gap:.32em;border:none;box-shadow:0 3px 18px rgba(33,124,229,0.09);background:linear-gradient(98deg, #eff7fe 0%, #e6f2fb 65%, #d9e9f6 100%);color:#1e3e68;letter-spacing:0;">
        <span>Enquire Now</span>
      </a>
      <a href="{{ route('register.get') }}"
        class="btn signup-btn-premium rounded-pill px-3 py-2 w-100"
        style="font-size:.98rem;font-weight:400;gap:.36em;border:none;background:linear-gradient(92deg, #c4e3fb 0%, #e9f5fe 62%, #f5fcff 100%);color:#3379e6;box-shadow:0 4px 22px rgba(40,127,245,0.06);letter-spacing:0;">
        <span style="max-width:72px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">Sign Up</span>
      </a>
    </div>
  </aside>
</header>

<style>
  #header {
    background: transparent !important;
    box-shadow: none !important;
    font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    position: sticky;
    top: 0;
    width: 100%;
    z-index: 1030;
    padding: 0 !important;
    border: none;
    transition: 
      box-shadow 0.2s,
      background 0.22s,
      min-height 0.16s,
      padding 0.21s,
      margin 0.23s,
      border-radius 0.22s;
  }
  .header-inner-nav {
    transition:
      padding 0.23s,
      margin 0.23s,
      border-radius 0.21s;
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0 !important;
    margin: 0 !important;
  }
  .main-nav-content {
    min-height: 54px !important;
    width: 100%;
    background: #fff;
    border-radius: 0 !important;
    box-shadow: 0 2px 16px rgba(36,98,175,0.05);
    margin: 0 !important;
    padding-left: 16px !important;
    padding-right: 16px !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    align-items: center;
    flex-wrap: nowrap;
    justify-content: space-between;
    transition: min-height 0.16s, box-shadow 0.16s, background 0.16s, border-radius 0.18s, margin 0.10s, padding 0.16s;
    display: flex;
    position: relative;
  }
  /* When scrolled - nav bar has rounded edges and padding, logo stays INSIDE */
  #header.sticky-scrolled .header-inner-nav {
    padding-left: 25px !important;
    padding-right: 25px !important;
    padding-top: 5px !important;
    margin-top: 14px !important;
    justify-content: center;
  }
  #header.sticky-scrolled .main-nav-content {
    border-radius: 2.1em !important;
    box-shadow: 0px 12px 28px -8px rgba(17,70,150,0.11), 0 2px 16px rgba(36,98,175,0.09);
    margin-top: 0 !important;
    padding-left: 34px !important;
    padding-right: 34px !important;
    transition: border-radius 0.22s, box-shadow 0.19s, padding 0.19s;
  }
  #header.sticky-scrolled .header-logo-wrapper {
    margin-left: 0 !important;
    padding-left: 0 !important;
    position: relative;
    left: 0;
    z-index: 2;
  }
  #header.sticky-scrolled .header-logo {
    min-width: 72px;
    padding-left: 0 !important;
    margin-right: 8px !important;
  }
  /* Ensure logo appears inside radius & not outside */
  #header.sticky-scrolled .main-nav-content > .header-logo-wrapper {
    position: absolute;
    top: 0; left: 0;
    height: 100%;
    display: flex;
    align-items: center;
    padding-left: 22px !important;
    z-index: 2;
  }
  #header.sticky-scrolled .main-nav-content > .header-logo-wrapper .main-logo {
    height: 28px !important;
    max-width: 85px !important;
    margin-top: 0 !important;
    transition: height 0.15s;
  }
  #header.sticky-scrolled .main-nav-content > ul,
  #header.sticky-scrolled .main-nav-content > .header-actions {
    margin-left: 90px !important;
  }
  /* Responsive: Border radius and spacing */
  @media (max-width: 1399.98px) {
    #header.sticky-scrolled .header-inner-nav {
      margin-top: 9px !important;
    }
    #header.sticky-scrolled .main-nav-content {
      border-radius: 1.4em !important;
      padding-left: 13px !important;
      padding-right: 13px !important;
    }
    #header.sticky-scrolled .main-nav-content > .header-logo-wrapper {
      padding-left: 11px !important;
    }
    #header.sticky-scrolled .main-nav-content > ul,
    #header.sticky-scrolled .main-nav-content > .header-actions {
      margin-left: 70px !important;
    }
  }
  @media (max-width: 991.98px) {
    #header.sticky-scrolled .header-inner-nav {
      margin-top: 7px !important;
    }
    #header.sticky-scrolled .main-nav-content {
      border-radius: 1em !important;
      padding-left: 7px !important;
      padding-right: 7px !important;
    }
    #header.sticky-scrolled .main-nav-content > .header-logo-wrapper {
      padding-left: 3px !important;
    }
    #header.sticky-scrolled .main-nav-content > ul,
    #header.sticky-scrolled .main-nav-content > .header-actions {
      margin-left: 53px !important;
    }
  }
  /* FALLBACK for logo always inside nav border, don't place logo outside */
  .main-nav-content > .header-logo-wrapper {
    position: relative;
    left: 0;
    z-index: 2;
    transition: padding-left 0.23s;
    display: flex;
    align-items: center;
    min-width: 72px;
  }

  .container-xxl {
    padding: 0 !important;
    margin: 0 !important;
    min-height: 0 !important;
    height: auto !important;
    width: 100%;
  }
  .header-logo {
    min-width: 70px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-left: 2px !important;
    padding-left: 0 !important;
    transition: padding-left 0.18s;
  }
  .main-logo {
    height: 48px !important;
    max-width: 135px !important;
    transition: height 0.14s, max-width 0.14s;
  }
  .header-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.36rem;
    min-width: 135px;
    margin-right: 2px !important;
    z-index: 3;
  }
  .center-nav-list {
    flex: 0 1 auto;
    justify-content: center !important;
    align-items: center !important;
    margin: 0 .1em !important;
    padding: 0 !important;
    gap: 0.25rem !important;
    min-width: 0 !important;
    width: 100%;
    max-width: 900px;
    transition: gap 0.11s;
  }
  .center-nav-list > li {
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding: 0;
  }
  .center-nav-list .nav-link,
  .center-nav-list .dropdown-item {
    font-size: 1rem !important;
    font-weight: 400 !important;
    color: #183153 !important;
    background: transparent !important;
    border: none !important;
    padding-left: 0.6em !important;
    padding-right: 0.6em !important;
    padding-top: 0.38em !important;
    padding-bottom: 0.38em !important;
    border-radius:2em !important;
    transition: color 0.16s, background 0.12s;
  }
  .center-nav-list .nav-link:hover,
  .center-nav-list .dropdown-item:hover {
    background: #f4f7fb !important;
    color: #0171d3 !important;
    text-decoration: none;
  }
  .center-nav-list .active, .center-nav-list .show > .nav-link {
    background: #e9f5ff !important;
    color: #0171d3 !important;
  }
  .center-nav-list .dropdown-toggle::after { display: none; }
  .center-nav-list .btn {
    font-size: .97em;
    min-width: 80px;
    font-weight: 500;
    line-height: 1.1;
  }
  .enquire-btn-premium {
    transition: background 0.12s, color 0.12s, box-shadow 0.11s, border-color 0.13s;
    border: none !important;
    color: #1e3e68 !important;
    background: linear-gradient(98deg, #eff7fe 0%, #e6f2fb 65%, #d9e9f6 100%) !important;
    font-weight: 400 !important;
    box-shadow:0 3px 18px rgba(33,124,229,0.09) !important;
    letter-spacing: 0.01em;
  }
  .enquire-btn-premium:hover,
  .enquire-btn-premium:focus {
    background: linear-gradient(101deg, #eaf3fc 0%, #dfebfa 50%, #ccdbe8 100%) !important;
    color: #214c82 !important;
    box-shadow:0 5px 24px rgba(33,124,229,0.11) !important;
    transform: translateY(-1px) scale(1.012);
  }
  .signup-btn-premium {
    transition: background 0.14s, color 0.13s, box-shadow 0.13s, border 0.12s;
    color: #3379e6 !important;
    background: linear-gradient(92deg, #c4e3fb 0%, #e9f5fe 62%, #f5fcff 100%) !important;
    border: none !important;
    font-weight: 400 !important;
    box-shadow:0 4px 22px rgba(40,127,245,0.06) !important;
    letter-spacing: 0.01em;
  }
  .signup-btn-premium:hover,
  .signup-btn-premium:focus {
    background: linear-gradient(106deg, #bce2f8 0%, #d1eaf6 70%, #f0f9fc 100%) !important;
    color: #205fae !important;
    box-shadow:0 8px 25px rgba(36,126,255,0.10) !important;
    transform: translateY(-2px) scale(1.014);
  }

  /* Mobile SIDEBAR Styles */
  .mobile-sidebar-overlay {
    display: none;
    position: fixed;
    z-index: 1050;
    top: 0; left: 0;
    width: 100vw; height: 100vh;
    /* background: rgba(48, 48, 60, 0.25); */
    transition: opacity 0.16s;
  }
  .mobile-sidebar-nav {
    display: block;
    position: fixed;
    top: 0;
    left: -100vw;
    z-index: 1060;
    width: 66vw;
    max-width: 350px;
    min-width: 210px;
    background: #fff;
    box-shadow: none !important;
    height: 100dvh !important;
    min-height: 100dvh !important;
    overflow-y: auto;
    overflow-x: hidden;
  }
  .mobile-sidebar-nav.sidebar-open {
    left: 0;
    transition: left 0.23s cubic-bezier(0.37, 0, 0.63, 1);
  }
  .mobile-sidebar-overlay.sidebar-open {
    display: block;
    opacity: 1;
    pointer-events: all;
  }
  .mobile-nav-toggle.hide-when-sidebar-open {
    display: none !important;
  }
  .mobile-nav-list .dropdown-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .mobile-nav-list .nav-link {
    font-size: 1.08rem;
    font-weight: 400;
    border-radius: 1.3em;
    color: #183153 !important;
    background: transparent !important;
    transition: background 0.14s, color 0.13s;
    margin-bottom: .14em;
  }
  .mobile-nav-list .nav-link:active,
  .mobile-nav-list .nav-link:focus,
  .mobile-nav-list .nav-link:hover {
    background: #f4f7fb !important;
    color: #0171d3 !important;
  }
  .mobile-sidebar-nav .dropdown-menu {
    position: static;
    float: none;
    box-shadow: none;
    border: none;
    background: transparent;
    padding: 0;
  }
  .mobile-nav-toggle,
  #mobileSidebarCloseBtn {
    background: #fff !important;
    border-radius: 50% !important;
    width: 44px !important;
    height: 44px !important;
    min-width: 44px !important;
    min-height: 44px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
    box-shadow: 0 2px 6px rgba(30, 41, 59, 0.14) !important;
  }
  .mobile-nav-toggle:active,
  #mobileSidebarCloseBtn:active,
  .mobile-nav-toggle:focus,
  #mobileSidebarCloseBtn:focus {
    background: #f7fafe !important;
  }
  @media(min-width:1200px) {
    .mobile-nav-toggle,
    #mobileSidebarNav,
    #mobileSidebarNavOverlay {
      display: none !important;
    }
    .main-nav-content {
      display: flex !important;
    }
  }
  @media (max-width: 1199.98px) {
    .main-nav-content {
      display: none !important;
    }
    .container-xxl {
      padding-left: .34em !important;
      padding-right: .34em !important;
    }
    .mobile-nav-toggle {
      display: inline-flex !important;
    }
    .mobile-sidebar-nav { 
      display: block !important; 
      box-shadow: none !important;
      height: 119dvh !important;
      min-height: 100dvh !important;
    }
  }
  @media (min-width: 400px) AND (max-width: 600px) {
    .mobile-sidebar-nav { max-width: 96vw; min-width: 60vw; }
  }
</style>
<script>
  // Enhanced: Make nav curly/circular and add padding/margin on scroll, always keep logo INSIDE border radius at left
  document.addEventListener('DOMContentLoaded', function () {
    var header = document.getElementById('header');
    function setStickyNav() {
      if(window.scrollY > 8) {
        header.classList.add('sticky-scrolled');
      } else {
        header.classList.remove('sticky-scrolled');
      }
    }
    setStickyNav();
    window.addEventListener('scroll', setStickyNav);

    // Mobile sidebar nav
    var openBtn = document.getElementById('mobileSidebarOpenBtn');
    var closeBtn = document.getElementById('mobileSidebarCloseBtn');
    var sidebar = document.getElementById('mobileSidebarNav');
    var overlay = document.getElementById('mobileSidebarNavOverlay');

    function openSidebar() {
      sidebar.classList.add('sidebar-open');
      overlay.classList.add('sidebar-open');
      document.body.style.overflow = "hidden";
      if (openBtn) openBtn.classList.add('hide-when-sidebar-open');
    }
    function closeSidebar() {
      sidebar.classList.remove('sidebar-open');
      overlay.classList.remove('sidebar-open');
      document.body.style.overflow = "";
      if (openBtn) openBtn.classList.remove('hide-when-sidebar-open');
    }

    if (openBtn) openBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Collapse handling for mobile dropdowns (manual, to mimic Bootstrap's collapse)
    document.querySelectorAll('#mobileSidebarNav .dropdown-toggle').forEach(toggle => {
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        var target = toggle.getAttribute('data-bs-target');
        if (!target) return;
        var menu = document.querySelector(target);
        if (!menu) return;
        if (menu.classList.contains('show') || menu.classList.contains('open') || menu.classList.contains('collapse')) {
          menu.classList.toggle('show');
          menu.classList.toggle('collapse');
        } else {
          menu.classList.add('show');
          menu.classList.remove('collapse');
        }
      });
    });
  });
</script>
<!-- End Responsive Header -->
