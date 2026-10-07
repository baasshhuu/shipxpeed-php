@extends('layouts.auth')

@section('content')
<div class="card">
    <div class="card-body p-4 p-sm-5">
        <h5 class="mb-0">Reset Password</h5>
        <form class="mt-4" method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="col-md-12 mb-2">
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                    placeholder="Email Address" name="email" value="{{ $email ?? old('email') }}" autocomplete="email"
                    autofocus>
                @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="col-md-12 mb-2">
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                    placeholder="New Password" name="password">
                @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            <div class="col-md-12 mb-2">
                <input id="password-confirm" type="password" class="form-control" placeholder="Confirm Password"
                    name="password_confirmation" required autocomplete="new-password">
            </div>
            <div class="mb-2"></div>
            <button class="btn btn-secondary d-block w-100 mt-3" type="submit">Reset Password</button>
        </form>
        <a class="fs--1 text-primary" href="{{ $loginUrl }}">Go to Login Page
            <span class="d-inline-block ms-1 py-2">→</span>
        </a>
    </div>
</div>
@endsection


@section('js')
<script type="text/javascript">
    $("form").validate({
        rules: {
            email: {
                required: true,
                email: true
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
                equalTo: "#password"
            }
        },
        messages: {
            email: {
                required: "Please enter Email",
            },
            password: {
                required: "Please enter Password",
            },
            password_confirmation: {
                required: "Please enter Confirm Password",
            },
        },
    });
</script>
@endsection