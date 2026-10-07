@extends('layouts.app')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/summernote/summernote.min.css') }}">
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">COD Remittance</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('codremittance.index')  }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form class="row" method="POST" action="{{ route('codremittance.upload') }}" enctype='multipart/form-data'>
            @csrf
            
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="image">Upload Remittance</label>
                <input class="form-control" id="image" name="excel_file" type="file" value="" />
                @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="col-lg-12 mt-3 d-flex justify-content-start">
                <button class="btn btn-secondary submitbtn" type="submit">Add</button>
            </div>
        </form>
    </div>
</div>
@endsection
