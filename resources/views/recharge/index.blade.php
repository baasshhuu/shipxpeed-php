@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Recharge :: Recharge List </h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    @if(Helper::userCan(107, 'can_add'))
                    <a href="{{ route('recharges.add') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i>
                        Add Balance
                    </a>
                    @endif
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
                        <th>Amount</th>
                        <th>Seller</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        {{-- <th width="100px">Action</th> --}}
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('js')
<script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>

<script>
$(document).on('click', '.change-status', function () {
    var id = $(this).data('id');
    var currentStatus = $(this).data('status');

    Swal.fire({
        title: 'Are you sure?',
        text: "You want to change the status!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "{{ route('recharges.changeStatus') }}", // ✅ Create this route
                type: 'POST',
                data: {
                    id: id,
                    status: currentStatus,
                    _token: '{{ csrf_token() }}'
                },
                
                success: function (response) {
                    if (response.success) {
                        Swal.fire('Updated!', response.message, 'success');
                        $('.table-datatable').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function () {
                    toastr.error('Something went wrong!');
                }
            });
        }
    });
});
</script>


<script type="text/javascript">
    $(function () {
        var table = $('.table-datatable').DataTable({
            ajax: "{{ route('recharges') }}",
            order: [
                [4, 'desc']
            ],
            columns: [{
                data: 'amount',
                name: 'amount',
                orderable: false,
                searchable: false
            },
            {
                data: 'seller_id',
                name: 'seller_id'
            },
            {
                data: 'code',
                name: 'code'
            },
            {
                data: 'status',
                name: 'status'
            },
            {
                data: 'created_at',
                name: 'created_at'
            },
            // {
            //     data: 'action',
            //     name: 'action',
            //     orderable: false,
            //     searchable: false
            // },
            ]
        });
        $(document).on('click', ".delete", function () {
            var id = $(this).data('id')
            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('cms') }} ",
                        data: { 'id': id },
                        type: 'DELETE',
                        success: function (data) {
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
