@extends('layouts.sellerdash')

@section('content')

<div class="pc-content">
    <div class="header-section">
        <div class="heading">Track Order</div>
    </div>

    <div class="container py-4">
        <div class="d-flex justify-content-center mb-4">
            <input type="text" placeholder="AWB Number" id="awb_number_input" class="form-control w-auto me-2">
            <button class="btn btn-teal btn-primary" id="trackBtn">Track</button>
            <button class="btn ms-2 btn-primary" id="resetBtn">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>

        <div class="d-flex justify-content-center text-secondary mb-4" id="trackingSummary">
            <span>Courier: <span id="courier_name">--</span></span>
            <span class="mx-2">|</span>
            <span>AWB Number: <span id="awb_number">--</span></span>
            <span class="mx-2">|</span>
            <span>Order ID: <span id="order_id">--</span></span>
            <span class="mx-2">|</span>
            <span>Current Status: <span id="current_status">--</span></span>
            <span class="mx-2">|</span>
            <span>Estimated Delivery: <span id="eta">--</span></span>
        </div>

        <div class="bg-white p-4 rounded text-center" id="trackingDataContainer">
            <p class="text-secondary">No tracking data found!</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $('#trackBtn').click(function () {
            let awb = $('#awb_number_input').val().trim();

            if (!awb) {
                alert("Please enter AWB number.");
                return;
            }

            $.ajax({
                url: "{{ route('order.track') }}",
                type: "POST",
                data: {
                    awb_number: awb,
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function () {
                    $('#trackingDataContainer').html('<p class="text-secondary">Tracking...</p>');
                },
                success: function (response) {
                    if (response.success) {
                        const data = response.data;

                        $('#courier_name').text(data.courier_name || 'N/A');
                        $('#awb_number').text(data.awb_number || awb);
                        $('#order_id').text(data.order_id || 'N/A');
                        $('#current_status').text(data.status || 'N/A');
                        $('#eta').text(data.eta || 'N/A');

                        if (data.events && data.events.length > 0) {
                            let timeline = '<ul class="list-group text-start">';
                            data.events.forEach(event => {
                                timeline += `<li class="list-group-item">
                                    <strong>${event.status}</strong><br>
                                    ${event.location} - ${event.timestamp}
                                </li>`;
                            });
                            timeline += '</ul>';
                            $('#trackingDataContainer').html(timeline);
                        } else {
                            $('#trackingDataContainer').html('<p class="text-secondary">No tracking events available.</p>');
                        }
                    } else {
                        $('#trackingDataContainer').html('<p class="text-danger">Tracking failed: ' + response.message + '</p>');
                    }
                },
                error: function () {
                    $('#trackingDataContainer').html('<p class="text-danger">An error occurred while tracking the order.</p>');
                }
            });
        });

        $('#resetBtn').click(function () {
            $('#awb_number_input').val('');
            $('#courier_name, #awb_number, #order_id, #current_status, #eta').text('--');
            $('#trackingDataContainer').html('<p class="text-secondary">No tracking data found!</p>');
        });
    });
</script>
@endpush
