@extends('layouts.app')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/summernote/summernote.min.css') }}">
<style>
    .courier-box {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
        background-color: #f9f9f9;
    }
    .courier-title {
        font-weight: bold;
        margin-bottom: 15px;
        color: #333;
    }
</style>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Status Update :: Status </h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('pricesetting.add') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form class="row" method="POST" action="{{ route('update.status.all') }}" enctype='multipart/form-data'>
            @csrf
            
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="seller_id">Name</label>
                <select class="form-control @error('seller_id') is-invalid @enderror" id="seller_id" name="seller_id" required>
                    <option value="">-- Select Seller --</option>
                    @foreach($SellerList as $seller)
                        <option value="{{ $seller->id }}" 
                            {{ old('seller_id', isset($selectedSeller) && $selectedSeller->id == $seller->id ? 'selected' : '') }}>
                            {{ $seller->name }}
                        </option>
                    @endforeach
                </select>
                @error('seller_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="col-12 mt-4" id="orders-section" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h5>Delivered Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        <th>Order Number</th>
                                        <th>Order Amount</th>
                                        <th>Order Date</th>
                                    </tr>
                                </thead>
                                <tbody id="orders-list">
                                    <!-- Orders will be loaded here dynamically -->
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Update Selected Orders</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@section('js')
<script>
$(document).ready(function() {
    $('#seller_id').change(function() {
        var sellerId = $(this).val();
            // Clear previous results and hide section until we have response
            $('#orders-list').html('');
            $('#orders-section').hide();
        if(sellerId) {
            $.ajax({
                url: '{{ route("get.orders") }}',
                type: 'GET',
                data: { seller_id: sellerId },
                    beforeSend: function() {
                        // optional: show loading indicator
                        $('#orders-list').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
                        $('#orders-section').show();
                    },
                success: function(response) {
                        var html = '';
                        if (response && response.orders && response.orders.length > 0) {
                            response.orders.forEach(function(order) {
                                html += `
                                    <tr>
                                        <td><input type="checkbox" name="order_ids[]" value="${order.id}"></td>
                                        <td>${order.order_number || 'N/A'}</td>
                                        <td>${order.order_amount || '0.00'}</td>
                                        <td>${order.created_at || 'N/A'}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            // If endpoint returns a message (e.g. no seller selected), show friendly message
                            var msg = (response && response.message) ? response.message : 'No delivered orders found';
                            html = `<tr><td colspan="4" class="text-center">${msg}</td></tr>`;
                        }

                        $('#orders-list').html(html);
                },
                    error: function(xhr, status, error) {
                        var msg = 'An error occurred while fetching orders';
                        if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                        $('#orders-list').html(`<tr><td colspan="4" class="text-center">${msg}</td></tr>`);
                        $('#orders-section').show();
                    }
            });
        } else {
            $('#orders-section').hide();
        }
    });

    $('#select-all').change(function() {
        $('input[name="order_ids[]"]').prop('checked', $(this).prop('checked'));
    });
});
</script>
@endsection

@endsection













