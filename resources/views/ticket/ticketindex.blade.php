@extends('layouts.app')

@section('content')
    <!-- [ Main Content ] start -->
    <div class="pc-container">
        <div class="pc-content">
            <div class=" mt-4">
                <div class="bg-white rounded shadow-sm p-3">
                    <!-- Top Bar -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <!-- Left: Filter Icon & Date Input -->
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="daterange" class="form-control date-input"
                                placeholder="Select Date Range">
                        </div>
                    </div>

                    <!-- Responsive Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center">
                            <thead class="table-dark">
                                <tr>
                                    <th>AWB Number</th>
                                    <th>Message</th>
                                    {{-- <th>Status</th> --}}
                                    <th>Created At</th>
                                    <th>Updated At</th>
                                    <th>Action</th>

                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                    <tr>
                                        <td>{{ $ticket->order_id }}</td>
                                        <td>{{ $ticket->message }}</td>
                                        {{-- <td>
                                            @if($ticket->status === 'Pending')
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @elseif($ticket->status === 'Rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @elseif($ticket->status === 'Resolved')
                                                <span class="badge bg-success">Resolved</span>
                                            @endif
                                        </td> --}}
                                        <td>{{ $ticket->created_at->format('Y-m-d') }}</td>
                                        <td>{{ $ticket->updated_at->format('Y-m-d') }}</td>
                                        {{-- @if(auth()->user()->role === 'admin') --}}
                                            <td>
                                                <form method="POST" action="{{ route('tickets.update-status', $ticket->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="Pending" {{ $ticket->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                                        <option value="Rejected" {{ $ticket->status == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                                                        <option value="Resolved" {{ $ticket->status == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                                                    </select>
                                                </form>
                                            </td>
                                        {{-- @endif --}}
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>   
        </div>
    </div>
    <!-- [ Main Content ] end -->
@endsection