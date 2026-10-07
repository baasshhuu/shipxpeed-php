@extends('layouts.app')

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0" data-anchor="data-anchor">Kyc Manage :: Kyc Manage List </h5>
                </div>
                <div class="col-auto ms-auto">

                </div>
            </div>
        </div>
        <div class="card-body table-padding">
            <div class="table-responsive scrollbar">
                <table class="table custom-table table-striped dt-table-hover fs--1 mb-0 table-datatable" style="width:100%">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th>Name</th>
                            <th>Kyc Status</th>
                            <th>Adhar Card Front</th>
                            <th>Adhar Card Back</th>
                            <th>GST Photo</th>
                            <th>Cancel Cheque</th>
                            <th>Pan Card</th>
                            <th>Created Date</th>
                            <th width="100px">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Image Preview Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" id="preview-image" class="img-fluid" alt="Preview" style="max-height: 600px;">
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
    <script type="text/javascript">
        $(function() {
            var table = $('.table-datatable').DataTable({
                ajax: "{{ route('kyc-manage') }}",
                order: [
                    [3, 'desc']
                ],
                columns: [{
                        data: 'name',
                        name: 'name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'adhar_card_front',
                        name: 'adhar_card_front',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'adhar_card_back',
                        name: 'adhar_card_back',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'gst_photo',
                        name: 'gst_photo',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'cancel_cheque',
                        name: 'cancel_cheque',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'pan_card',
                        name: 'pan_card',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'kyc_status',
                        name: 'kyc_status'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
            $(document).on('click', '.img-clickable', function() {
                var imgUrl = $(this).data('img');
                $('#preview-image').attr('src', imgUrl);
            });

            $(document).on('click', ".update_status", function() {
                var id = $(this).data('id');

                Swal.fire({
                    title: "Update Status",
                    text: "Choose an action for this request",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Approve",
                    cancelButtonText: "Reject",
                    reverseButtons: true
                }).then((result) => {
                    let status;

                    if (result.isConfirmed) {
                        status = 1; // Approved
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        status = 2; // Disapproved
                    } else {
                        return; // do nothing
                    }

                    $.ajax({
                        url: "{{ route('update-kyc-status') }}",
                        type: 'POST',
                        data: {
                            id: id,
                            status: status,
                            _token: '{{ csrf_token() }}' // include CSRF token for POST
                        },
                        success: function(data) {
                            if (data.kyc_status) {
                                Swal.fire({
                                    title: 'Success',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonColor: '#198754' // green
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: data.message,
                                    icon: 'error',
                                    confirmButtonColor: '#dc3545'
                                });
                            }
                            table.draw();
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error',
                                text: 'Something went wrong with the server.',
                                icon: 'error',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    });
                });
            });


            $(document).on('click', ".delete", function() {
                var id = $(this).data('id')
                Swal.fire(deleteMessageSwalConfig).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('cms') }} ",
                            data: {
                                'id': id
                            },
                            type: 'DELETE',
                            success: function(data) {
                                if (data.status) {
                                    Swal.fire('', data?.message, "success")
                                    table.draw();
                                } else {
                                    toastr.error(data.message);
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
