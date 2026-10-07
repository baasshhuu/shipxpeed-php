@extends('layouts.sellerdash')

@section('content')
    <div class="pc-container">
        <div class="pc-content">

            <form action="{{ route('seller.ndr.store') }}" method="POST">
                @csrf

                <div class="col-lg-12">
                    <label class="form-label">AWB Number *</label>
                    <input type="text" name="awb" class="form-control" placeholder="Enter AWB Number" value="{{ old('awb') }}">
                    @error('awb')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-12">
                    <label class="form-label">Action *</label>
                    <input type="text" name="action" class="form-control" placeholder="e.g. re-attempt" value="{{ old('action', 're-attempt') }}">
                    @error('action')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-12">
                    <label class="form-label">Re-attempt Date *</label>
                    <input type="date" name="re_attempt_date" class="form-control" value="{{ old('re_attempt_date') }}">
                    @error('re_attempt_date')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>

        </div>
    </div>
@endsection
