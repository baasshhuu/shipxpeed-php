@extends('layouts.app')

@section('content')
    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <div class="mt-4">
                <div class="bg-white rounded shadow p-4 border-0" style="box-shadow: 0 4px 24px rgba(0,0,0,.06);">
                    <!-- Top Bar -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="daterange" class="form-control date-input shadow-sm border-primary"
                                placeholder="Select Date Range" style="max-width: 250px;" autocomplete="off">
                        </div>
                    </div>

                    <!-- Beautified Table -->
                    <div class="table-responsive rounded">
                        <table class="table table-striped table-hover align-middle mb-0 custom-ticket-table">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th class="fw-bold">AWB Number</th>
                                    <th class="fw-bold" style="min-width: 240px; white-space: normal; word-break: break-word;">Message</th>
                                    <th class="fw-bold">Created At</th>
                                    <th class="fw-bold">Updated At</th>
                                    <th class="fw-bold">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                    <tr>
                                        <td class="text-primary fw-semibold">{{ $ticket->order_id }}</td>
                                        <td style="white-space: pre-wrap; word-break: break-word; max-width: 360px; text-align: left;">
                                            {{ $ticket->message }}
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border border-1 border-secondary px-3 py-2 fs-6 shadow-sm">
                                                {{ $ticket->created_at->format('Y-m-d') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border border-1 border-secondary px-3 py-2 fs-6 shadow-sm">
                                                {{ $ticket->updated_at->format('Y-m-d') }}
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('tickets.update-status', $ticket->id) }}" class="d-flex align-items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm rounded-pill bg-light border-primary" style="min-width: 120px; font-weight: 500;" onchange="this.form.submit()">
                                                    <option value="Pending" {{ $ticket->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="Rejected" {{ $ticket->status == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                                    <option value="Resolved" {{ $ticket->status == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fa fa-ticket fa-2x mb-2 text-gray-400"></i>
                                            <div>No tickets found.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <style>
                        /* Beautification styles for the ticket table */
                        .custom-ticket-table thead th {
                            letter-spacing: .02em;
                        }
                        .custom-ticket-table tbody tr {
                            transition: box-shadow 0.1s;
                        }
                        .custom-ticket-table tbody tr:hover {
                            background-color: #F5F9FF !important;
                            box-shadow: 0 2px 8px rgba(71, 141, 255, 0.06);
                        }
                        .custom-ticket-table td, .custom-ticket-table th {
                            vertical-align: middle;
                        }
                        .custom-ticket-table select {
                            cursor: pointer;
                        }
                    </style>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
@endsection