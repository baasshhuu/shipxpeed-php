@extends('layouts.app')

@section('css')
    <link href="{{ asset('assets/plugins/summernote/summernote.min.css') }}" rel="stylesheet" type="text/css">
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0" data-anchor="data-anchor">Rate Card :: Rate Card Edit </h5>
                </div>
                <div class="col-auto ms-auto">
                    <div class="nav nav-pills nav-pills-falcon flex-grow-1 mt-2" role="tablist">
                        <a href="{{ route('rate-card') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left me-1"></i>
                            Go Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form class="row" id="ediUser" method="POST" action="{{ route('rate-card.edit', $cms['id']) }}"
                enctype='multipart/form-data'>
                @csrf
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="seller_id">Seller Name <span class="required">*</span></label>
                    <select name="seller_id" class="form-select" id="seller_id">
                        <option value="">Select Seller</option>
                        @foreach ($sellers as $seller)
                            <option value="{{ $seller->id }}" @selected(old('seller_id', $cms->seller_id) == $seller->id)>
                                {{ $seller->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('seller_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" class="form-select" id="status">
                        <option value="1" @selected(old('status', $cms['status']) == 1)> Active </option>
                        <option value="0" @selected(old('status', $cms['status']) == 0)> Inactive </option>
                    </select>
                    @error('status')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="rate_pdf_1">Update 10% PDF</label>
                    <div class="img-group mb-2">
                        @if($cms->rate_pdf_1)
                            <p>Current PDF: {{ basename($cms->rate_pdf_1) }}</p>
                        @else
                            <p>No 10% PDF available</p>
                        @endif
                    </div>
                    <input class="form-control" id="rate_pdf_1" name="rate_pdf_1" type="file" value="" />
                    @error('rate_pdf_1')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="rate_pdf_2">Update 20% PDF</label>
                    <div class="img-group mb-2">
                        @if($cms->rate_pdf_2)
                            <p>Current PDF: {{ basename($cms->rate_pdf_2) }}</p>
                        @else
                            <p>No 20% PDF available</p>
                        @endif
                    </div>
                    <input class="form-control" id="rate_pdf_2" name="rate_pdf_2" type="file" value="" />
                    @error('rate_pdf_2')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="rate_pdf_3">Update 30% PDF</label>
                    <div class="img-group mb-2">
                        @if($cms->rate_pdf_3)
                            <p>Current PDF: {{ basename($cms->rate_pdf_3) }}</p>
                        @else
                            <p>No 30% PDF available</p>
                        @endif
                    </div>
                    <input class="form-control" id="rate_pdf_3" name="rate_pdf_3" type="file" value="" />
                    @error('rate_pdf_3')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>




                <div class="col-lg-12 mt-3 d-flex justify-content-start">
                    <button class="btn btn-secondary submitbtn" type="submit">Update</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/plugins/summernote/summernote.min.js') }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
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
                $(value).on('click', function(e) {
                    $(this).attr('data-bs-toggle', 'dropdown')
                })
            });

            $("#ediUser").validate({
                ignore: ".ql-container *",
                rules: {
                    title: {
                        required: true,
                        minlength: 2,
                        maxlength: 100
                    },
                    image: {
                        extension: "jpg|jpeg|png",
                        filesize: 5
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
        });
    </script>
@endsection
