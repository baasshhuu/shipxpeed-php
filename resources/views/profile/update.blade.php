@extends('layouts.'.($user['route'] != 'web' ? $user['route'].'_': '').'app')

@section('content')

@include('partial.common.user_box')

<div class="row g-0">
    <div class="col-lg-8 pe-lg-2">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Profile Settings</h5>
            </div>
            <div class="card-body">
                <form class="row g-3" method="POST" id="profileUpdate" action="{{ request()->url() }}"
                    enctype='multipart/form-data'>
                    @csrf
                    <div class="col-lg-6">
                        <label class="form-label" for="name">First Name</label>
                        <input class="form-control" id="name" name="name" type="text"
                            value="{{ old('name', $user->name) }}" />
                        @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" id="email" type="email" name="email"
                            value="{{ old('email', $user->email) }}" />
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="mobile">Mobile</label>
                        <input class="form-control" id="mobile" name="mobile" type="text"
                            value="{{ old('mobile', $user->mobile) }}" />
                        @error('mobile')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label" for="state_id">State</label>
                        <select name="state_id" onchange="getCity(this.value)" class="form-select" id="state_id">
                            <option value="">Select State</option>
                            @foreach ($states as $state)
                            <option value="{{ $state['id'] }}"
                                @selected(old('state_id',$user['state_id'])==$state['id'])>
                                {{ $state['name'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('state_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="city_id">City</label>
                        <select name="city_id" class="form-select" id="city_id">
                            <option value="">Select City</option>
                        </select>
                        @error('city_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label" for="image">Image</label>
                        <input class="form-control" id="image" name="image" type="file" value="" />
                        @error('image')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-secondary" type="submit">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4 ps-lg-2">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="passUpdate"
                    action="{{ route(($user['route'] != 'web' ? $user['route'].'.' : '' ).'update-password') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="old_password">Old Password</label>
                        <input class="form-control" name="old_password" id="old_password" type="password">
                        @error('old_password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">New Password</label>
                        <input class="form-control" name="password" id="new-password" type="password">
                        @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Confirm Password</label>
                        <input class="form-control" name="password_confirmation" id="password_confirmation"
                            type="password">
                        @error('password_confirmation')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <button class="btn btn-secondary d-block w-100" type="submit">Update Password </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    var city_id = "{{ old('city_id', $user['city_id']) }}";
    function getCity(state_id) {
        $.ajax({
            type: "POST",
            url: "{{ route('cities.list') }}",
            data: { state_id, city_id },
            success: function (data) {
                $('#city_id').html(data);
            },
        });
        return true;
    }

    $(function () {
        $('#profile-image').change(function () {
            var formData = new FormData();
            formData.append('image', this.files[0]);
            $.ajax({
                url: "{{ route(($user['route'] != 'web' ? $user['route'].'.' : '' ).'update-image') }}",
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (result) {
                    if (result.status) {
                        toastr.success(result?.message);
                        $('.profile-img').attr('src', result?.image);
                    } else {
                        toastr.error(result?.message);
                    }
                }
            });
        });

        $("#profileUpdate").validate({
            errorClass: "text-danger fs--1",
            errorElement: "span",
            rules: {
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                email: {
                    required: true,
                    email: true,
                    minlength: 2,
                    maxlength: 100
                },
                mobile: {
                    required: true,
                    number: true,
                    minlength: 10,
                    maxlength: 10
                },
                image: {
                    extension: "jpg|jpeg|png",
                    filesize: 2
                }
            },
            messages: {
                name: {
                    required: "Please enter name",
                },
                email: {
                    required: "Please enter Email",
                },
                mobile: {
                    required: "Please enter Mobile number",
                },
                image: {
                    extension: "Supported Format Only : jpg, jpeg, png"
                }
            },
        });

        $("#passUpdate").validate({
            rules: {
                old_password: {
                    required: true,
                    minlength: 8,
                    maxlength: 50
                },
                password: {
                    required: true,
                    minlength: 8,
                    maxlength: 50
                },
                password_confirmation: {
                    required: true,
                    minlength: 8,
                    maxlength: 50,
                    equalTo: "#new-password"
                },

            },
            messages: {
                old_password: {
                    required: "Please enter old password",
                },
                password: {
                    required: "Please enter new password",
                },
                password_confirmation: {
                    required: "Please enter confirm password",
                },
            },
        });

        setTimeout(() => {
            getCity("{{ old('state_id', $user['state_id']) }}");
        }, 100);
    });
</script>

@endsection
