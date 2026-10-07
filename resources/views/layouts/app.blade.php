
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('partial.common.header')

<body class="layout-boxed">
   

    <!--  BEGIN MAIN CONTAINER  -->
    <div class="main-container" id="container">
        <!-- <div class="overlay"></div> -->
        <div class="search-overlay"></div>

        <!--  BEGIN SIDEBAR  -->
        <div class=" sidebar-theme">
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
    <!-- Mobile-specific secondary-nav style adjustment -->
    <style>
    @media (max-width: 767px) {
        .secondary-nav {
            left: 0 !important;
            position: fixed;
            width: 100%;
            z-index: 1030;
        
        }
    }
    </style>
    @include('partial.common.footer')
</body>

</html>
