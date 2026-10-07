@extends('layouts.sellerdash')

@section('content')
    <div class="pc-container">
        <div class="pc-content">

            <form action="{{ route('pickuprequest.store') }}" method="POST">
                @csrf
                <div class="col-lg-12">
                    <label class="form-label">Pickup Time *</label>
                    <input type="time" name="pickup_time" class="form-control" value="{{ old('pickup_time')}}">
                    @error('pickup_time')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-12">
                    <label class="form-label">Pickup Date *</label>
                    <input type="date" name="pickup_date" class="form-control" value="{{ old('pickup_date') }}">
                    @error('pickup_date')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-12">
                    <label class="form-label">Pickup Location *</label>
                    <input type="text" name="pickup_location" class="form-control" placeholder="Enter Pickup Location"
                        value="{{ old('pickup_location', 'Ayush99tshirts') }}">
                    @error('pickup_location')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-lg-12">
                    <label class="form-label">Expected Package Count *</label>
                    <input type="number" name="expected_package_count" class="form-control"
                        value="{{ old('expected_package_count', 1) }}">
                    @error('expected_package_count')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>


                <div class="col-6 mt-4">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>

        </div>
    </div>
@endsection
