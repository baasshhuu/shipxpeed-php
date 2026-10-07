
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('partial.common.header')

<body class="layout-boxed">
    <!--  BEGIN NAVBAR  -->
    <div class="header-container">
        <header class="header navbar navbar-expand-sm expand-header">
            <ul class="navbar-item theme-brand flex-row text-center">
                <li class="nav-item theme-logo">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('storage/' . $site_settings['favicon']) }}" class="navbar-logo" alt="logo" />
                    </a>
                </li>
                <li class="nav-item theme-text">
                    <a href="{{ route('dashboard') }}" class="nav-link"> {{ $site_settings['application_name'] }} </a>
                </li>
            </ul>
           <div class="">

           </div>
            <ul class="navbar-item flex-row ms-lg-auto ms-0 action-area">
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
        </header>
    </div>
    <!--  END NAVBAR  -->

    <!--  BEGIN MAIN CONTAINER  -->
    <div class="main-container" id="container">
        <div class="overlay"></div>
        <div class="search-overlay"></div>

        <!--  BEGIN SIDEBAR  -->
        <div class="sidebar-wrapper sidebar-theme">
            @include('partial.sidebar')
        </div>
        <!--  END SIDEBAR  -->

        <div id="content" class="main-content">
            <!-- ===============================================-->
            <!--    Main Content-->
            <!-- ===============================================-->
            <div class="layout-px-spacing">
                <div class="middle-content container-xxl p-0">
                    <div class="secondary-nav">
                        @include('partial.common.breadcrumb')
                    </div>
                    <div class="layout-top-spacing">
                        <div class="container-xxl p-0">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </div>
            <!-- ===============================================-->
            <!--    End of Main Content-->
            <!-- ===============================================-->

            <!-- ===============================================-->
            <!--    FOOTER      -->
            <!-- ===============================================-->
            <div class="footer-wrapper">
                <div class="footer-section f-section-1">
                    {{-- {{ $site_settings['copyright'] }} --}}
                </div>
                <div class="footer-section f-section-2">
                    <p class="">
                        Developed   By : <a href="#" target="_lucky">Vicky Developer</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    @include('partial.common.footer')
</body>

</html>
