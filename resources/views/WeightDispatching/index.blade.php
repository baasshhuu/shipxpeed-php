@extends('layouts.app')

@section('content')
    <style>
        @media (max-width: 576px) {
            .action-btns-mobile {
                flex-direction: row !important;
                gap: 0.5rem !important;
            }
            .action-btns-mobile .btn {
                padding: 0.25rem 0.6rem !important;
                font-size: 0.83rem !important;
                min-width: 36px;
                min-height: 30px;
            }
            .action-btns-mobile .btn i {
                margin-right: 0 !important;
            }
            .action-btns-mobile .btn span {
                display: none;
            }
        }
    </style>
    <div class="card mb-3" style="padding: 19px 3px;">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Weight Dispatching :: Weight Dispatching List</h5>
            <div class="d-flex align-items-center action-btns-mobile" style="gap:1rem;">
                @if (Helper::userCan(104, 'can_add'))
                    <a href="{{ route('weight.dispatching.create') }}" class="btn btn-outline-secondary me-2" style="padding: 0.35rem 0.85rem; font-size: 1rem;">
                        <i class="fa fa-plus me-1"></i> <span>Add Weight Dispatching</span>
                    </a>
                @endif
                <a href="{{ route('weight.dispatching.download') }}" class="btn btn-outline-success" style="padding: 0.35rem 0.85rem; font-size: 1rem;">
                    <i class="fa fa-download me-1"></i> <span>Download Excel</span>
                </a>
            </div>
        </div>

        <div class="card-body table-padding">
            <div class="table-responsive scrollbar">
                <form action="{{ route('weight.submit') }}" method="POST">
                    @csrf
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark" style="text-align:center;">
                            <tr>
                                <th>AWB</th>
                                <th>Courier</th>
                                <th>Mentioned Weight</th>
                                <th>Charged Weight</th>
                                <th>Weight Mismatch</th>
                                <th>Dispute Charges</th>
                            </tr>
                        </thead>
                        <tbody style="text-align:center;">
                            @foreach($data as $row)
                                <tr>
                                    <td>{{ $row['awb'] }}</td>
                                    <td>{{ $row['courier'] }}</td>
                                    <td>{{ $row['mentionedweight'] ?? 'N/A' }}</td>
                                    <td>{{ $row['chargedweight'] ?? 'N/A' }}</td>
                                    <td>{{ $row['weightmissmatched'] ?? 'N/A' }}</td>
                                    <td>{{ $row['weightdisputecharges'] ?? '0' }}</td>
                                </tr>

                                <!-- Hidden Inputs -->
                                <input type="hidden" name="sellers[{{ $loop->index }}][seller_id]" value="{{ $row['seller_id'] }}">
                                <input type="hidden" name="sellers[{{ $loop->index }}][amount]" value="{{ $row['weightdisputecharges'] }}">
                                <input type="hidden" name="sellers[{{ $loop->index }}][weight_id]" value="{{ $row['id'] }}">
                                <input type="hidden" name="sellers[{{ $loop->index }}][weight_awb]" value="{{ $row['awb'] }}">
                                <input type="hidden" name="sellers[{{ $loop->index }}][weight_courier]" value="{{ $row['courier'] }}">
                                <input type="hidden" name="sellers[{{ $loop->index }}][weight_mentionedweight]" value="{{ $row['mentionedweight'] }}">
                                <input type="hidden" name="sellers[{{ $loop->index }}][weight_chargedweight]" value="{{ $row['chargedweight'] }}">
                                <input type="hidden" name="sellers[{{ $loop->index }}][weight_weightmissmatched]" value="{{ $row['weightmissmatched'] }}">
                                <input type="hidden" name="sellers[{{ $loop->index }}][weight_seller_name]" value="{{ $row['seller_name'] }}">
                            @endforeach
                        </tbody>
                    </table>
                    <button style="margin-left: 12px;" type="submit" class="btn btn-primary mt-3">Submit All</button>
                </form>
                
                <!-- Pagination Links -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $disputes->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
