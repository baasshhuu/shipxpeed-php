@php
$name = request()->route()->getName();
$parts = array_filter(explode('.', $name));
@endphp

<div class="breadcrumbs-container" data-page-heading="Analytics" style="z-index: 10000 !important; position: fixed;">
    <header class="header navbar navbar-expand-sm">
        <div class="d-flex justify-content-between align-items-center breadcrumb-content w-100 flex-wrap">
            <!-- Toggle icon is only shown on screens less than 1024px -->
            <a href="javascript:void(0);" 
                class="btn-toggle sidebarCollapse d-lg-none"
                data-placement="bottom"
                id="sidebarOpenBtn"
                onclick="openSidebarFromTopbar();"
            >
                <i class="fa-duotone fa-bars fs-5"></i>
            </a>

            <div class="page-header">
                <div class="page-title"></div>
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        @foreach($parts as $part)
                        <li class="breadcrumb-item @if(false) active @endif" aria-current="page">
                            {{ ucwords(str_ireplace(['-', '_'], ' ', $part)) }}
                        </li>
                        @endforeach
                    </ol>
                </nav>
            </div>
            
            <!-- Premium & Responsive Search Bar (HIDDEN ON MOBILE) -->
            <div class="breadcrumbs-searchbar-wrapper d-flex flex-grow-1 justify-content-center align-items-center">
                <form class="search-form" autocomplete="off">
                    <div class="breadcrumb-searchbar">
                        <button type="submit" class="breadcrumb-search-icon" tabindex="-1" aria-label="Search">
                            <i class="fa fa-search"></i>
                        </button>
                        <input 
                            type="search" 
                            class="breadcrumb-search-input"
                            placeholder="Search By AWB"
                            aria-label="Search By AWB"
                        >
                    </div>
                </form>
            </div>

            <ul class="navbar-item flex-row ms-lg-auto ms-0 action-area mb-0">
                <li class="nav-item dropdown user-profile-dropdown order-lg-0 order-1">
                    <a href="javascript:void(0);" class="nav-link dropdown-toggle user" id="userProfileDropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="avatar-container">
                            <div class="avatar avatar-sm avatar-indicators avatar-online">
                                <img alt="" src="{{ asset('storage/' . Auth::user()->image) }}"
                                    class="rounded-circle profile-img" />
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-menu position-absolute" aria-labelledby="userProfileDropdown">
                        <div class="user-profile-section">
                            <div class="media mx-auto">
                                <div class="me-2"></div>
                                <div class="media-body">
                                    @if (Auth::check())
                                    <span class="dropdown-item fw-bold text-warning">
                                        <h5>{{ Auth::user()->name }}</h5>
                                        <p>Admin</p>
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-item">
                            <a href="{{ route('profile') }}">
                                <i class="fa-duotone fa-user me-1"></i>
                                <span>Profile</span>
                            </a>
                        </div>
                        <div class="dropdown-item">
                            <a href="{{ route('lock') }}">
                                <i class="fa-duotone fa-lock"></i>
                                <span>Lock Screen</span>
                            </a>
                        </div>
                        <div class="dropdown-item">
                            <a href="{{ route('logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fa-regular fa-arrow-right-from-bracket me-1"></i>
                                <span>Log Out</span>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </a>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </header>
</div>
<style>
/* Hide sidebar toggle in laptop and up */
@media (min-width: 1024px) {
    #sidebarOpenBtn {
        display: none !important;
    }
}

/* Desktop/tablet search bar organization */
.breadcrumbs-searchbar-wrapper {
    flex-grow: 1;
    justify-content: center;
    align-items: center;
}
@media (max-width: 700px) {
    .breadcrumbs-searchbar-wrapper {
        display: none !important;
    }
}

/* DESKTOP SEARCHBAR */
.breadcrumb-searchbar {
    position: relative;
    display: flex;
    align-items: center;
    background: linear-gradient(90deg, #fbfcfe 0%, #f1f5fa 100%);
    box-shadow: 0 1.5px 10px 0 rgba(60,72,88,0.10);
    border-radius: 7px;
    height: 36px;
    min-width: 180px;
    max-width: 320px;
    width: 100%;
    border: 1px solid #e4e8ef;
    transition: box-shadow 0.15s, background 0.14s;
}

.breadcrumb-searchbar:focus-within,
.breadcrumb-searchbar:hover {
    box-shadow: 0 3.5px 18px 0 rgba(60,72,88,0.12);
    background: linear-gradient(90deg, #f4f9ff 0%, #e8eef8 100%);
    border-color: #4361ee;
}

.breadcrumb-search-icon {
    outline: none;
    border: none;
    background: transparent;
    color: #8b98ae;
    font-size: 17px;
    padding: 0 10px;
    display: flex;
    align-items: center;
    height: 34px;
    border-top-left-radius: 7px;
    border-bottom-left-radius: 7px;
    cursor: pointer;
    transition: background 0.09s;
}

.breadcrumb-search-icon:hover, .breadcrumb-search-icon:focus {
    background: #f0f4f8;
    color: #282d36;
}

.breadcrumb-search-input {
    border: none;
    outline: none;
    background: transparent;
    padding: 6.5px 14px 6.5px 0;
    font-size: 1rem;
    font-weight: 500;
    color: #222b45;
    border-radius: 7px;
    width: 100%;
    min-width: 0;
    transition: background 0.13s, color 0.10s;
    height: 34px;
}
.breadcrumb-search-input::placeholder {
    color: #adb9cb;
    opacity: 1;
    font-weight: 400;
}

.search-form {
    width: 100%;
    min-width: 200px;
    max-width: 320px;
    display: flex;
    margin: 0 10px;
}

@media (max-width: 1024px) {
    .search-form, .breadcrumb-searchbar {
        max-width: 270px !important;
        min-width: 110px;
    }
    .search-form { min-width: 110px; }
}
</style>
<script>
function openSidebarFromTopbar() {
    var sidebar = document.getElementById('sidebar');
    var closeBtn = document.getElementById('sidebarCloseBtn');
    if (!sidebar) return;
    if(window.innerWidth <= 767){
        // Mobile sidebar behavior
        sidebar.classList.add('sidebar-expanded');
        sidebar.style.display = '';
        if (closeBtn) closeBtn.style.display = 'flex';
    } else {
        sidebar.classList.add('sidebar-expanded');
        sidebar.style.display = '';
        if (closeBtn) closeBtn.style.display = 'none';
    }
}
</script>