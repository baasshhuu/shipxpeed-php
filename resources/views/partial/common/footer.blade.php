<!-- ===============================================-->
<!--    JavaScripts-->
<!-- ===============================================-->

<div id="overlay" class="bg-loader">
    <div class="flexbox">
        <span class="hm-spinner cv-spinner-"></span>
    </div>
</div>
<input name="config" type="hidden" value="{{ isset($config) ? json_encode($config) : null }}">
<script src="{{ asset('assets/plugins/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.validate.js') }}"></script>
<script src="{{ asset('assets/js/custom-methods.js') }}"></script>
<script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
<script src="{{ asset('assets/plugins/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
<script src="{{ asset('assets/js/waves.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
<script src="{{ asset('assets/js/custom.js') }}"></script>
@yield('js')


@include('partial.toastr')
