@extends('layouts.app')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/summernote/summernote.min.css') }}">
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Recharge :: Recharge Add </h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon flex-grow-1" role="tablist">
                    <a href="{{ route('recharges')  }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form class="row" id="add" method="POST" action="{{ route('recharges.add') }}" enctype='multipart/form-data'>
            @csrf
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="amount">Amount <span class="required">*</span></label>
                <input class="form-control" id="amount" placeholder="Amount" name="amount" type="text"
                    value="{{ old('amount') }}" />
                @error('amount')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="seller_id">Seller:</label>
                      <select class="form-control" name="seller_id" id="seller_id" required>
                          <option value="">Select a Seller</option>
                          @foreach ($sellers as $seller)
                          <option value="{{ $seller->id }}">
                              {{ $seller->name }}
                          </option>
                          @endforeach
                      </select>
              </div>



    <div class="col-lg-6 mt-2">
    <label class="form-label" for="seller_id">Transaction Type:</label>
    <select class="form-control" name="type" id="type" required>
        <option value="">Select Type</option>
        <option value="Credit">Credit</option>
        <option value="Debit">Debit</option>
    </select>
</div>


            <div class="col-lg-12 mt-3 d-flex justify-content-start">
                <button class="btn btn-secondary submitbtn" type="submit">Add</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/plugins/summernote/summernote.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $('#description').summernote({
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['codeview', 'help']],
            ]
        });
        let buttons = $('.note-editor button[data-toggle="dropdown"]');
        buttons.each((key, value) => {
            $(value).on('click', function (e) {
                $(this).attr('data-bs-toggle', 'dropdown')
            })
        })
    })
    $("#add").validate({
        ignore: ".ql-container *",
        rules: {
            title: {
                required: true,
                minlength: 2,
                maxlength: 100
            },
            image: {
                extension: "jpg|jpeg|png"
            }
        },
        messages: {
            title: {
                required: "Please enter title",
            },
            image: {
                extension: "Supported Format Only : jpg, jpeg, png"
            }
        },
    });
</script>
@endsection
