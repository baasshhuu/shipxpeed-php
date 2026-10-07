
@extends('layouts.app')

@section('css')
<style>
    .summary-box {
        border-radius: 6px;
        padding: 15px;
        font-size: 16px;
        font-weight: 600;
        text-align: center;
    }
</style>
@endsection

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5>RTO Status Update</h5>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('update.status.all') }}">
            @csrf

            {{-- Seller Select --}}
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Select Seller</label>
                    <select class="form-control" id="seller_id" name="seller_id" required>
                        <option value="">-- Select Seller --</option>
                        @foreach($SellerList as $seller)
                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- RTO Summary --}}
            <div class="row mt-4" id="rto-summary" style="display:none;">
                <div class="col-md-4">
                    <div class="summary-box bg-warning">
                        RTO Orders<br>
                        <span id="rto-order-count">0</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-box bg-danger text-white">
                        RTO Debit<br>
                        <span id="rto-debit-count">0</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-box bg-success text-white">
                        RTO Credit<br>
                        <span id="rto-credit-count">0</span>
                    </div>
                </div>
            </div>

            {{-- Orders Table --}}
            <div class="row mt-4" id="orders-section" style="display:none;">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6>RTO Orders List</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="select-all"></th>
                                            <th>Order Number</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="orders-list">
                                        <tr>
                                            <td colspan="4" class="text-center">Select seller to load orders</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">
                                Update Selected Orders
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

@endsection


@section('js')
<script>
$(document).ready(function () {

    $('#seller_id').change(function () {
        let sellerId = $(this).val();

        $('#orders-list').html('');
        $('#orders-section').hide();
        $('#rto-summary').hide();

        if (sellerId) {
            $.ajax({
                url: "{{ route('get.orders') }}",
                type: "GET",
                data: { seller_id: sellerId },

                beforeSend: function () {
                    $('#orders-list').html(
                        '<tr><td colspan="4" class="text-center">Loading...</td></tr>'
                    );
                    $('#orders-section').show();
                },

                success: function (response) {

                    // Counts
                    $('#rto-order-count').text(response.rto_order_count || 0);
                    $('#rto-debit-count').text(response.rto_debit_count || 0);
                    $('#rto-credit-count').text(response.rto_credit_count || 0);
                    $('#rto-summary').show();

                    let html = '';

                    if (response.orders && response.orders.length > 0) {
                        response.orders.forEach(function (order) {
                            html += `
                                <tr>
                                    <td>
                                        <input type="checkbox" name="order_ids[]" value="${order.id}">
                                    </td>
                                    <td>${order.order_number}</td>
                                    <td>${order.order_amount}</td>
                                    <td>${order.created_at}</td>
                                </tr>
                            `;
                        });
                    } else {
                        html = `
                            <tr>
                                <td colspan="4" class="text-center">
                                    No RTO orders found
                                </td>
                            </tr>
                        `;
                    }

                    $('#orders-list').html(html);
                    $('#orders-section').show();
                },

                error: function () {
                    $('#orders-list').html(
                        '<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>'
                    );
                    $('#orders-section').show();
                }
            });
        }
    });

    // Select All Checkbox
    $(document).on('change', '#select-all', function () {
        $('input[name="order_ids[]"]').prop('checked', this.checked);
    });

});
</script>
@endsection






