@extends('layouts.app')

@section('css')
    <link href="{{ asset('assets/plugins/summernote/summernote.min.css') }}" rel="stylesheet" type="text/css">
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0" data-anchor="data-anchor">Seller List :: Seller List Edit </h5>
                </div>
                <div class="col-auto ms-auto">
                    <div class="nav nav-pills nav-pills-falcon flex-grow-1 mt-2" role="tablist">
                        <a href="{{ route('seller-list') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left me-1"></i>
                            Go Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form class="row" id="ediUser" method="POST" action="{{ route('seller-list.edit', $cms['id']) }}"
                enctype='multipart/form-data'>
                @csrf
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="name">Name</label>
                    <input class="form-control" id="name" placeholder="name" name="name" type="text"
                        value="{{ old('name', $cms['name']) }}" />
                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="gst_no">GST No</label>
                    <input class="form-control" id="gst_no" placeholder="gst_no" name="gst_no" type="text"
                        value="{{ old('gst_no', $cms['gst_no']) }}" />
                    @error('gst_no')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-lg-6 mt-2">
                    <div class="form-group pb-2">
                        <label>User Type</label>
                        <select class="form-control" name="user_type">
                            <option value="1" {{ $cms->user_type == 1 ? 'selected' : '' }}>Individual</option>
                            <option value="2" {{ $cms->user_type == 2 ? 'selected' : '' }}>Business</option>
                        </select>
                        <div class="text-danger error-user_type"></div>
                    </div>
                </div>
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" placeholder="email" name="email" type="text"
                        value="{{ old('email', $cms['email']) }}" />
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="phone_number">Phone Numer</label>
                    <input class="form-control" id="phone_number" placeholder="phone_number" name="phone_number"
                        type="text" value="{{ old('phone_number', $cms['phone_number']) }}" />
                    @error('phone_number')
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
                    <label class="form-label" for="status">Negative Balance</label>
                    <select name="negative_balance" class="form-select" id="negative_balance">
                        <option value="1" @selected(old('negative_balance', $cms['negative_balance']) == 1)> Active </option>
                        <option value="0" @selected(old('negative_balance', $cms['negative_balance']) == 0)> Inactive </option>
                    </select>
                    @error('negative_balance')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>



                        {{-- <div class="col-lg-6 mt-2">
                    <label class="form-label" for="fixed_price">Fixed Courier Price</label>
                    <input class="form-control" id="fixed_price" placeholder="fixed_price" name="fixed_price"
                        type="text" value="{{ old('fixed_price', $cms['fixed_price']) }}" />
                    @error('fixed_price')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div> --}}

                    <div class="col-lg-6 mt-2">
                    <label class="form-label" for="status">RTO Charge</label>
                    <select name="fixed_price" class="form-select" id="fixed_price">
                        <option value="1" @selected(old('fixed_price', $cms['fixed_price']) == 1)> Inactive </option>
                        <option value="0" @selected(old('fixed_price', $cms['fixed_price']) == 0)> Active </option>
                    </select>
                    @error('fixed_price')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>



                     <div class="col-lg-6 mt-2">
                    <label class="form-label" for="status">RTO Credit</label>
                    <select name="rto_credit" class="form-select" id="rto_credit">
                        <option value="1" @selected(old('rto_credit', $cms['rto_credit']) == 1)> Active </option>
                        <option value="0" @selected(old('rto_credit', $cms['rto_credit']) == 0)> Inactive </option>
                    </select>
                    @error('rto_credit')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>



                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="pan_card">Pan Card</label>
                    <div class="img-group mb-2">
                        <img class="" src="{{ asset('storage/' . $cms['pan_card']) }}" alt="">
                    </div>
                    <input class="form-control" id="pan_card" name="pan_card" type="file" value="" />
                    @error('pan_card')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="profile">Pan Card</label>
                    <div class="img-group mb-2">
                        <img class="" src="{{ asset('storage/' . $cms['profile']) }}" alt="">
                    </div>
                    <input class="form-control" id="profile" name="profile" type="file" value="" />
                    @error('profile')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="adhar_card_front">Adhar Car Front</label>
                    <div class="img-group mb-2">
                        <img class="" src="{{ asset('storage/' . $cms['adhar_card_front']) }}" alt="">
                    </div>
                    <input class="form-control" id="adhar_card_front" name="adhar_card_front" type="file"
                        value="" />
                    @error('adhar_card_front')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="adhar_card_back">Adhar Car Back</label>
                    <div class="img-group mb-2">
                        <img class="" src="{{ asset('storage/' . $cms['adhar_card_back']) }}" alt="">
                    </div>
                    <input class="form-control" id="adhar_card_back" name="adhar_card_back" type="file"
                        value="" />
                    @error('adhar_card_back')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="adhar_card_back">Adhar Car Back</label>
                    <div class="img-group mb-2">
                        <img class="" src="{{ asset('storage/' . $cms['adhar_card_back']) }}" alt="">
                    </div>
                    <input class="form-control" id="adhar_card_back" name="adhar_card_back" type="file"
                        value="" />
                    @error('adhar_card_back')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="gst_photo">Gst Photo</label>
                    <div class="img-group mb-2">
                        <img class="" src="{{ asset('storage/' . $cms['gst_photo']) }}" alt="">
                    </div>
                    <input class="form-control" id="gst_photo" name="gst_photo" type="file" value="" />
                    @error('gst_photo')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-lg-6 mt-2">
                    <label class="form-label" for="cancel_cheque">Cancel Cheque</label>
                    <div class="img-group mb-2">
                        <img class="" src="{{ asset('storage/' . $cms['cancel_cheque']) }}" alt="">
                    </div>
                    <input class="form-control" id="cancel_cheque" name="cancel_cheque" type="file"
                        value="" />
                    @error('cancel_cheque')
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
