@extends('layouts.auth')

@section('content')
<form class="mt-4" method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="row">
        <div class="col-md-12 mb-3">
            <h2>Forgot your password?</h2>
            <p>Enter your email and we'll send you a reset link.</p>
        </div>
        <div class="col-md-12">
            <div class="mb-3">
                <input class="form-control" name="email" value="{{ old('email') }}" id="email" type="email"
                    placeholder="{{ ucwords(str_replace('_', ' ', $guard)) }} : Email address" required
                    autocomplete="email" autofocus>
                <input type="hidden" name="login_as" value="{{ $guard == 'admin' ? 'web' :  $guard }}">
                @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
        <div class="col-12">
            <div class="mb-4">
                <button class="btn btn-secondary" id="email" type="submit">Send Password Reset Link</button>
            </div>
        </div>
        <div class="col-12">
            <a class="fs--1 text-primary" href="{{ route('loginPage', $guard) }}">Go to Login Page
                <span class="d-inline-block ms-1 py-2">→</span>
            </a>
        </div>
    </div>
</form>
@endsection



@section('js')
<script type="text/javascript">
    $("form").validate({
        rules: {
            email: {
                required: true,
                email: true
            },
            login_as: {
                required: true,
            }
        },
        messages: {
            email: {
                required: "Please enter Email.",
            },
            login_as: {
                required: "Please select Login As.",
            }
        },
    });
</script>
@endsection