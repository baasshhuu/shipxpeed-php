@extends('layouts.app')

@section('content')
<style>
    .custom-premium-card {
        border-radius: 1.2rem;
        box-shadow: 0 4px 32px 0 rgba(80, 140, 255, 0.06);
        background: linear-gradient(90deg, #f3f7fd 0%, #f9fafb 100%);
        border: none;
        padding: 0;
        margin: 32px 0 0 0;
    }
    .custom-premium-card .card-header {
        background: linear-gradient(90deg, #e0e7ef 0%, #f1f5fb 100%);
        border-radius: 1.2rem 1.2rem 0 0;
        border: none;
        padding: 1.25rem 2rem;
        border-bottom: 1px solid #e3e7ee;
    }
    .custom-premium-card .card-header h5 {
        font-weight: 700;
        letter-spacing: .6px;
        color: #2d61c4;
        font-size: 1.25rem;
    }
    .custom-premium-card .table-responsive {
        border-radius: 0 0 1.2rem 1.2rem;
        overflow: auto;
    }
    .custom-premium-table th, .custom-premium-table td {
        vertical-align: middle;
        text-align: left;
        border: none;
        background: transparent;
    }
    .custom-premium-table thead {
        background: linear-gradient(90deg, #f3f7fd 0%, #e3e8f0 100%);
    }
    .custom-premium-table th {
        color: #393e46;
        font-weight: 600;
        font-size: 1rem;
        padding: 1.1rem .85rem;
        border-bottom: 2px solid #e3e8f0;
        letter-spacing: .2px;
    }
    .custom-premium-table td {
        font-size: .99rem;
        color: #41517a;
        background: #fff;
        padding: 1rem .85rem;
        border-bottom: 1px solid #f0f1f6;
    }
    .custom-premium-table tbody tr:hover {
        background: #f7faff !important;
    }
    .brand-status-active {
        background: linear-gradient(90deg, #e3f3e0 0%, #cdeadd 100%);
        color: #1a936f;
        font-weight: 600;
        border: none;
        border-radius: 20px;
        padding: .3rem 1.2rem;
        font-size: .98rem;
        box-shadow: 0 2px 6px 0 rgba(52, 199, 89, 0.06);
        transition: filter 0.2s;
        cursor: pointer;
        display: inline-block;
        min-width: 72px;
        text-align: center;
    }
    .brand-status-active:hover {
        filter: brightness(0.97);
        text-decoration: underline;
    }
    .brand-status-inactive {
        background: linear-gradient(90deg, #edeef0 0%, #dad8e7 100%);
        color: #585070;
        font-weight: 500;
        border: none;
        border-radius: 20px;
        padding: .3rem 1.2rem;
        font-size: .98rem;
        box-shadow: none;
        cursor: pointer;
        transition: filter 0.2s;
        display: inline-block;
        min-width: 72px;
        text-align: center;
    }
    .brand-status-inactive:hover {
        filter: brightness(0.97);
        text-decoration: underline;
    }
    .brand-action-btn {
        border: none;
        border-radius: 50%;
        height: 34px;
        width: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        font-weight: 500;
        transition: box-shadow .2s, color .2s, background .2s;
        background: none !important;
        font-size: 1.16rem;
        box-shadow: none !important;
    }
    .brand-action-btn.btn-delete {
        /* NO BACKGROUND for just icon! */
        color: #c0392b;
        background: none !important;
        box-shadow: none !important;
        border: none;
    }
    .brand-action-btn.btn-delete:hover, 
    .brand-action-btn.btn-delete:focus {
        color: #a10e0e;
        background: none !important;
        box-shadow: none !important;
    }
    .btn-delete .delete-icon {
        transition: transform 0.2s cubic-bezier(.4,2.3,.3,1);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .btn-delete:hover .delete-icon,
    .btn-delete:focus .delete-icon {
        transform: rotate(-13deg) scale(1.13);
        color: #c30909;
    }
    .brand-action-btn.btn-edit {
        background: linear-gradient(90deg, #e0e7ef 0%, #93c5fd 100%);
        color: #2563eb;
        border-radius: 18px;
        height: auto;
        width: auto;
        padding: .27rem 1.1rem;
        font-size: .93rem;
    }
    .brand-action-btn.btn-edit:hover {
        background: #e0e7ef;
        color: #1e40af;
    }
    @media (max-width: 767.98px) {
        .custom-premium-card {
            margin: 22px 0 0 0;
        }
        .custom-premium-card .card-header {
            padding: 1rem 1rem;
        }
        .custom-premium-table th, .custom-premium-table td {
            padding: .85rem .45rem;
            font-size: .96rem;
        }
        .custom-premium-table thead {
            font-size: .98rem;
        }
    }
</style>

<!-- Delete Modal -->
<div class="modal fade" id="deleteLogisticsModal" tabindex="-1" aria-labelledby="deleteLogisticsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="border-radius:1rem;">
      <div class="modal-header border-0" style="background:linear-gradient(90deg,#fbeee5 0,#f9fafb 100%);border-radius:1rem 1rem 0 0;">
        <h5 class="modal-title text-danger-emphasis" id="deleteLogisticsModalLabel">
            <i class="fa fa-exclamation-triangle me-2"></i>Confirm Delete
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background: #fff5; border-radius: 5px;"></button>
      </div>
      <div class="modal-body text-center text-muted" style="padding:1.25rem .75rem;">
        <p class="mb-2" style="font-size:1.06rem;">
            Are you sure you want to delete<br>
            <span class="fw-semibold text-dark" id="brandDeleteName"></span>?
        </p>
        <form id="deleteLogisticsForm" method="POST">
            @csrf
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger px-4"><i class="fa fa-trash-alt me-1 delete-icon"></i>Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="card custom-premium-card">
    <div class="card-header">
        <div class="row align-items-center g-2">
            <div class="col">
                <h5 class="mb-0">
                    <i class="fa fa-truck-moving me-2" style="color:#2563eb;"></i>
                    Logistic <span style="color:#bdbdbd;font-weight:500;">::</span> <span style="color:#6366f1;">List</span>
                </h5>
            </div>
            <div class="col-auto">
                @if (Helper::userCan(104, 'can_add'))
                    <a href="{{ route('logistics.add') }}" class="btn brand-action-btn btn-edit ms-2">
                        <i class="fa fa-plus me-1"></i>
                        Add Logistic
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="card-body" style="padding: 1.4rem 0.8rem;">
        <div class="table-responsive">
            <table class="table custom-premium-table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        {{-- <th>Image</th> --}}
                        <th>Email</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th class="text-center" style="min-width:80px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brands as $brand)
                        <tr>
                            <td>
                                <span style="font-weight:600;">
                                    <i class="fa fa-industry me-1 text-primary"></i>{{ $brand->name}}
                                </span>
                            </td>
                            {{-- <td>
                                <img src="{{ asset('uploads/brands/' . $brand->image) }}" alt="Brand Image" class="rounded shadow-sm" width="40">
                            </td> --}}
                            <td>
                                <span style="font-size: .99rem; color:#3366a1;">{{ $brand->email}}</span>
                            </td>
                            <td>
                                @if($brand->status == '1')
                                    <a href="{{ route('logistics.status', [$brand->id, '0']) }}" class="brand-status-active text-decoration-none shadow-sm" title="Set Inactive">
                                        Active
                                    </a>
                                @else
                                    <a href="{{ route('logistics.status', [$brand->id, '1']) }}" class="brand-status-inactive text-decoration-none shadow-sm" title="Set Active">
                                        Inactive
                                    </a>
                                @endif
                            </td>
                            <td>
                                <span style="color:#6c757d;">
                                    <i class="fa fa-calendar-alt me-1 text-info"></i>
                                    {{ $brand->created_at->format('d-m-Y') }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button 
                                    class="brand-action-btn btn-delete"
                                    data-id="{{ $brand->id }}"
                                    data-name="{{ $brand->name }}"
                                    data-delete-url="{{ route('logistics.delete', $brand->id) }}"
                                    onclick="showDeleteModal(this)"
                                    title="Delete"
                                    style="background: none; box-shadow: none;"
                                >
                                    <span class="delete-icon"><i class="fa fa-trash-alt"></i></span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5" style="font-size:1.2rem;">
                                <i class="fa fa-info-circle me-2 text-secondary"></i>
                                No logistics added yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function showDeleteModal(btn) {
        var brandName = btn.getAttribute('data-name') || '';
        var deleteUrl = btn.getAttribute('data-delete-url') || '#';

        document.getElementById('brandDeleteName').innerText = brandName;
        var form = document.getElementById('deleteLogisticsForm');
        form.setAttribute('action', deleteUrl);

        var modal = new bootstrap.Modal(document.getElementById('deleteLogisticsModal'));
        modal.show();
    }
</script>
@endpush

@endsection
