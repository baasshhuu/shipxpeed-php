@extends('layouts.app')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/summernote/summernote.min.css') }}">
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">logistics :: logistic Add </h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('logistics')  }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form class="row" method="POST" action="{{ route('logistics.data.add') }}" enctype='multipart/form-data'>
            @csrf
            
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="image">Name</label>
                <input class="form-control" id="image" name="name" type="text" value="" />
                @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>






            <div class="col-lg-6 mt-2">
                <label class="form-label" for="image">Logo</label>
                <input class="form-control" id="image" name="logo" type="file" value="" />
                @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>




                        
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="image">Email</label>
                <input class="form-control" id="image" name="email" type="email" value="" />
                @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>



                                    
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="image">Password</label>
                <input class="form-control" id="image" name="password" type="password" value="" />
                @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>



                        <div class="col-lg-6 mt-2">
                <label class="form-label" for="image">Api key</label>
                <input class="form-control" id="image" name="api_key" type="text" value="" />
                @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>




            

          <div class="col-lg-6 mt-2">
                <label class="form-label" for="image">Api Secret</label>
                <input class="form-control" id="image" name="api_secret" type="text" value="" />
                @error('image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>



            <div class="col-lg-6 mt-2">
                <label class="form-label" for="status">Status</label>
                <select name="status" class="form-select" id="status">
                    <option value="1" @selected(old('status', 1)==1)> Active </option>
                    <option value="0" @selected(old('status', 1)==0)> Inactive </option>
                </select>
                @error('status')
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
