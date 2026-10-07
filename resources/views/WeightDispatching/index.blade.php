@extends('layouts.app')

@section('content')
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Weight Dispatching :: Weight Dispatching List</h5>
            <div>
                @if (Helper::userCan(104, 'can_add'))
                    <a href="{{ route('weight.dispatching.create') }}" class="btn btn-outline-secondary me-2">
                        <i class="fa fa-plus me-1"></i> Add Weight Dispatching
                    </a>
                @endif
                <a href="{{ route('weight.dispatching.download') }}" class="btn btn-outline-success">
                    <i class="fa fa-download me-1"></i> Download Excel
                </a>
            </div>
        </div>

        <div class="card-body table-padding">
            <div class="table-responsive scrollbar">
                <form action="{{ route('weight.submit') }}" method="POST">
                    @csrf
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>AWB</th>
                                <th>Courier</th>
                                <th>Mentioned Weight</th>
                                <th>Charged Weight</th>
                                <th>Weight Mismatch</th>
                                <th>Dispute Charges</th>
                            </tr>
                        </thead>
                        <tbody>
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
                    <button type="submit" class="btn btn-primary mt-3">Submit All</button>
                </form>
                
                <!-- Pagination Links -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $disputes->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection



{{-- @extends('layouts.app')

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0" data-anchor="data-anchor">Weight Dispatching :: Weight Dispatching List </h5>
                </div>
                <div class="col-auto ms-auto">
                    <div class="nav nav-pills nav-pills-falcon">
                        @if (Helper::userCan(104, 'can_add'))
                            <a href="{{ route('weight.dispatching.create') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-plus me-1"></i>
                                Add Weight Dispatching
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body table-padding">
            <div class="table-responsive scrollbar">



<form action="{{ route('weight.submit') }}" method="POST">
    @csrf
    <table class="table">
        <thead>
            <tr>
                                <!-- <th>ID</th> -->

                <th>AWB</th>
                <th>Courier</th>
                <th>Mentioned Weight</th>
                <th>Charged Weight</th>
                <th>Weight Mismatch</th>
                <th>Dispute Charges</th>
                <th>Seller Name</th>
            </tr>
        </thead>
        <tbody>

  
            @foreach($data as $row)
                <tr>
                    <td>{{ $row['awb'] }}</td>
                    <td>{{ $row['courier'] }}</td>
                    <td>{{ $row['mentionedweight'] ?? 'N/A' }}</td>
                    <td>{{ $row['chargedweight'] ?? 'N/A' }}</td>
                    <td>{{ $row['weightmissmatched'] ?? 'N/A' }}</td>
                    <td>{{ $row['weightdisputecharges'] ?? '0' }}</td>
                    <td>{{ $row['seller_name'] ?? 'N/A' }}</td>
                </tr>

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

    <button type="submit" class="btn btn-primary mt-3">Submit All</button>
</form>



            </div>
        </div>
    </div>
@endsection --}}
