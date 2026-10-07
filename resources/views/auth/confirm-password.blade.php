@extends('layouts.auth')

@section('content')
<div>
    <form method="POST" action="{{ route('password.confirm', $guard) }}">
        @csrf
        <!-- Password -->
        <div class="col-md-12 mb-3">
            <h2>Sign In as <span class="text-secondary">{{ $user->name }}</span></h2>
            <p class="mb-1 text-dark fs-6"> {{ __('This is a secure area of the application.') }} </p>
            <p class="mb-3 text-dark fs-6">{{ __('Please confirm your password before continuing.') }} </p>
        </div>
        <div class="col-12">
            <div class="mb-3">
                <input class="form-control @error('password') is-invalid @enderror" type="password" name="password"
                    placeholder="Password" />
                @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
        <div class="col-12">
            <div class="mb-4">
                <button class="btn btn-secondary w-100" type="submit" name="submit">
                    {{ __('Confirm') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('js')
<script type="text/javascript">
    $("form").validate({
        rules: {
            password: {
                required: true,
                minlength: 6,
                maxlength: 50
            },
        },
        messages: {
            password: {
                required: "Please enter Password.",
            }
        },
    });
</script>
@endsection