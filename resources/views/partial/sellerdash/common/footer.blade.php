<footer class="pc-footer">
    <div class="footer-wrapper container-fluid">
        <div class="row">
            <div class="col-sm my-1">
                <p class="m-0">Developed by Shipxpeed Logistics (All Rights Reserved) 2025
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Flash Message (Alert Box Outside Modal) -->
<div id="flash-message" class="position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>

<!-- Recharge Modal -->
<div class="modal fade" id="rechargeModal" tabindex="-1" aria-labelledby="rechargeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="rechargeModalLabel">Recharge Wallet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.<br>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Recharge Form -->
                <form method="POST" action="{{ route('recharge.add') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" min="500" class="form-control" id="amount" name="amount" placeholder="Enter amount" required>
                    </div>
                    <input type="hidden" name="seller_id" value="{{ auth('seller')->id() }}">

                    <!-- Quick Amount Buttons -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach ([1000, 2000, 5000, 10000, 20000] as $val)
                            <button type="button" class="btn btn-outline-primary quick-amount" data-amount="{{ $val }}">+{{ $val }}</button>
                        @endforeach
                    </div>

                    <!-- Promo Code -->
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" name="code" placeholder="Enter your code">
                        <button class="btn btn-primary" type="button">Apply</button>
                    </div>

                    <!-- Promo Code Toggle -->
                    <div class="mb-3">
                        <a href="#" class="text-primary text-decoration-none" data-bs-toggle="collapse" data-bs-target="#promoCodes">
                            View Available Promo Codes <i class="fas fa-chevron-down"></i>
                        </a>
                        <div id="promoCodes" class="collapse mt-2">
                            <p>No promo codes available.</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Recharge</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="{{ asset('/dashboard-asset/js/plugins/apexcharts.min.js') }}"></script>
<script src="{{ asset('/dashboard-asset/js/pages/dashboard-default.js') }}"></script>
{{-- <script src="../dashboard-asset/js/plugins/popper.min.js"></script>
<script src="../dashboard-asset/js/plugins/simplebar.min.js"></script> --}}
<script src="{{ asset('assets/plugins/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('/dashboard-asset/js/fonts/custom-font.js') }}"></script>
<script src="{{ asset('/dashboard-asset/js/plugins/feather.min.js') }}"></script>
<script src="{{ asset('/dashboard-asset/js/pcoded.js') }}"></script>

<script>
    layout_change('light');
    $('input[name="dates"]').daterangepicker();
</script>

<!-- Quick Amount Fill -->
<script>
    document.querySelectorAll('.quick-amount').forEach(button => {
        button.addEventListener('click', () => {
            const amount = button.getAttribute('data-amount');
            document.getElementById('amount').value = amount;
        });
    });
</script>

<!-- Handle Modal Close & Flash Message -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if (session('success'))
            const modalEl = document.getElementById('rechargeModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

            // Ensure modal is hidden
            modal.hide();

            // Trigger flash message after modal fully hidden
            modalEl.addEventListener('hidden.bs.modal', function () {
                showFlashMessage("{{ session('success') }}", 'success');
            }, { once: true });

            // Force trigger the event
            modalEl.dispatchEvent(new Event('hidden.bs.modal'));
        @endif

        @if (session('error'))
            showFlashMessage("{{ session('error') }}", 'danger');
        @endif
    });

    function showFlashMessage(message, type = 'success') {
        const flash = document.getElementById('flash-message');
        flash.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show shadow" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;

        setTimeout(() => {
            const alert = bootstrap.Alert.getOrCreateInstance(flash.querySelector('.alert'));
            alert.close();
        }, 5000);
    }
</script>

<script>
    change_box_container('false');
    layout_rtl_change('false');
    preset_change("preset-1");
    font_change("Public-Sans");
</script>
