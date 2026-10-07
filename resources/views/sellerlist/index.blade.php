@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Seller List :: Seller List List </h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    {{-- @if (Helper::userCan(104, 'can_add'))
                    <a href="{{ route('cms.add') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i>
                        Add Cms
                    </a>
                    @endif --}}
                </div>
            </div>
        </div>
    </div>
    <div class="card-body table-padding">
        <div class="table-responsive scrollbar">
            <table class="table custom-table table-striped dt-table-hover fs--1 mb-0 table-datatable"
                style="width:100%">
                <thead class="bg-200 text-900">
                    <tr>
                        <th>#</th>
                        <th>Negative Balance</th>
                        <th>RTO Charge</th>
                        <th>Agreement</th>
                        <th>User Type</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Pan Card</th>
                        <th>Adhar Card Front</th>
                        <th>Adhar Card Back</th>
                        <th>Profile</th>
                        <th>GST No</th>
                        <th>Gst Photo</th>
                        <th>Cancel Cheque</th>
                        <th>Status</th>
                        <th>Created Date</th>

                        <th width="100px">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<!-- Global Agreement Modal -->
<div class="modal fade" id="agreementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seller Agreement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="agreementContent">
                <p>Loading...</p>
            </div>
            <div class="modal-footer">
               <a href="{{ route('seller.agreement.download') }}" class="btn btn-primary">
    Download Agreement
</a>

            </div>
        </div>
    </div>
</div>


@endsection

@section('js')
<script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
<script>
    $(function () {
        let table = $('.table-datatable').DataTable({
            ajax: "{{ route('seller-list') }}",
            order: [[14, 'desc']],
            columns: [
                { data: 'id' },
                {data: 'negative_balance' , orderable: false, searchable: false},
                { data: 'fixed_price', orderable: false, searchable: false },
                { data: 'view_agreement', orderable: false, searchable: false },
                { data: 'user_type' },
                { data: 'name' },
                { data: 'email' },
                { data: 'phone_number' },
                { data: 'pan_card', orderable: false, searchable: false },
                { data: 'adhar_card_front', orderable: false, searchable: false },
                { data: 'adhar_card_back', orderable: false, searchable: false },
                { data: 'profile', orderable: false, searchable: false },
                { data: 'gst_no' },
                { data: 'gst_photo', orderable: false, searchable: false },
                { data: 'cancel_cheque', orderable: false, searchable: false },
                { data: 'status' },
                { data: 'created_at' },
                { data: 'action', orderable: false, searchable: false }
            ]
        });

        $(document).on('click', '.view-agreement', function () {
            const id = $(this).data('id');

            $.ajax({
                url: "{{ route('seller.agreement.fetch') }}",
                data: { seller_id: id },
                success: function (res) {
                    $('#agreementContent').html(`
                        <p><strong>Client Name:</strong> ${res.client_name}</p>
                        <p><strong>Client Address:</strong> ${res.client_address}</p>
                        <p><strong>Client PAN:</strong> ${res.client_pan}</p>
                        <hr><p>${res.agreement_content}</p>
                    `);
                    $('#agreementModal').modal('show');
                },
                error: function () {
                    $('#agreementContent').html('<p class="text-danger">Agreement not found.</p>');
                    $('#agreementModal').modal('show');
                }
            });
        });

        $(document).on('click', '.delete', function () {
            let id = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('seller-list') }}",
                        type: 'DELETE',
                        data: { id: id },
                        success: function (data) {
                            if (data.status) {
                                Swal.fire('Deleted!', data.message, 'success');
                                table.draw();
                            } else {
                                Swal.fire('Error!', data.message, 'error');
                            }
                        }
                    });
                }
            });
        });
    });
</script>

@endsection