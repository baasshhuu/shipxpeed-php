@extends('layouts.app')

@section('content')
@php $filter = request('filter', 'all'); @endphp

<div class="sx-page-head">
    <div>
        <h2>Sellers</h2>
        <p>Switch settings on or off, edit details, or open any seller's panel in a new tab. Changes apply to their panel instantly.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('courier.rate.manager') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-truck-fast me-1"></i>Assign couriers &amp; rates</a>
        <a href="{{ route('register.get') }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i>New seller sign-up</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="sx-filters" id="sellerFilters">
            @foreach ([
                'all' => 'All',
                'active' => 'Active',
                'inactive' => 'Inactive',
                'kyc_pending' => 'KYC pending',
                'kyc_done' => 'KYC verified',
            ] as $key => $label)
                <button type="button" class="btn btn-sm btn-outline-secondary {{ $filter === $key ? 'active' : '' }}" data-filter="{{ $key }}">
                    {{ $label }} <span class="ms-1 text-muted">{{ number_format($counts[$key] ?? 0) }}</span>
                </button>
            @endforeach
        </div>
        <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i>Hover a switch to see what it does</small>
    </div>
    <div class="card-body table-padding">
        <div class="table-responsive scrollbar">
            <table class="table custom-table dt-table-hover mb-0 table-datatable" style="width:100%">
                <thead>
                    <tr>
                        <th>Seller</th>
                        <th>KYC</th>
                        <th>Documents</th>
                        <th class="text-center">Account</th>
                        <th class="text-center">Neg. balance</th>
                        <th class="text-center">RTO charge</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Agreement Modal -->
<div class="modal fade" id="agreementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seller Agreement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="agreementContent">
                <p>Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');
    let currentFilter = @json($filter);

    const table = $('.table-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('seller-list') }}",
            data: d => { d.filter = currentFilter; }
        },
        order: [[6, 'desc']],
        pageLength: 20,
        language: { search: '', searchPlaceholder: 'Search name, email, phone, GST…' },
        columns: [
            { data: 'seller', name: 'seller', orderable: false },
            { data: 'kyc', orderable: false, searchable: false },
            { data: 'documents', orderable: false, searchable: false },
            { data: 'status_switch', orderable: false, searchable: false, className: 'text-center' },
            { data: 'negative_switch', orderable: false, searchable: false, className: 'text-center' },
            { data: 'rto_switch', orderable: false, searchable: false, className: 'text-center' },
            { data: 'created_at', name: 'created_at', searchable: false },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    // Filter pills
    $('#sellerFilters').on('click', '[data-filter]', function () {
        currentFilter = $(this).data('filter');
        $('#sellerFilters .btn').removeClass('active');
        $(this).addClass('active');
        const url = new URL(window.location);
        currentFilter === 'all' ? url.searchParams.delete('filter') : url.searchParams.set('filter', currentFilter);
        history.replaceState(null, '', url);
        table.ajax.reload();
    });

    // Quick switches
    $(document).on('change', '.sx-toggle', function () {
        const $el = $(this);
        const on = $el.is(':checked');
        $el.prop('disabled', true);
        $.ajax({
            url: "{{ url('seller-list') }}/" + $el.data('id') + "/toggle",
            type: 'POST',
            data: { _token: csrf, field: $el.data('field'), value: on ? 1 : 0 },
            success: res => toastr.success(res.message || 'Saved'),
            error: xhr => {
                $el.prop('checked', !on);
                toastr.error(xhr.responseJSON?.message || 'Could not save. Please try again.');
            },
            complete: () => $el.prop('disabled', false)
        });
    });

    // Agreement
    $(document).on('click', '.view-agreement', function () {
        $('#agreementContent').html('<p>Loading...</p>');
        $('#agreementModal').modal('show');
        $.ajax({
            url: "{{ route('seller.agreement.fetch') }}",
            data: { seller_id: $(this).data('id') },
            success: function (res) {
                const esc = s => $('<div>').text(s ?? '').html();
                $('#agreementContent').html(`
                    <p><strong>Client name:</strong> ${esc(res.client_name)}</p>
                    <p><strong>Client address:</strong> ${esc(res.client_address)}</p>
                    <p><strong>Client PAN:</strong> ${esc(res.client_pan)}</p>
                    <hr><div>${res.agreement_content}</div>`);
            },
            error: () => $('#agreementContent').html('<p class="text-muted">This seller has not signed an agreement yet.</p>')
        });
    });

    // Delete
    $(document).on('click', '.delete', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Delete this seller?',
            text: 'Their account and recharge history will be removed. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            confirmButtonColor: '#f04f5f'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: "{{ route('seller-list') }}",
                type: 'DELETE',
                data: { id: id, _token: csrf },
                success: data => {
                    if (data.status) { toastr.success(data.message); table.ajax.reload(null, false); }
                    else { toastr.error(data.message); }
                }
            });
        });
    });
});
</script>
@endsection
