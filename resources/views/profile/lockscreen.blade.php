@extends('layouts.auth')

@section('content')
<form action="{{ url($path.'/lock') }}" method="post">
    @csrf
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="media mb-4">
                <div class="avatar avatar-lg me-3">
                    <img alt="avatar" src="{{  asset('storage/' . $user['image']) }}" class="rounded-circle">
                </div>
                <div class="media-body align-self-center">
                    <h3 class="mb-0">{{ $user['name'] }}</h3>
                    <p class="mb-0">Enter your password to unlock your ID</p>

                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input name="password" type="password" class="form-control" minlength="2" maxlength="100" required
                    autocomplete="off">
            </div>
        </div>
        <div class="col-6">
            <div class="mb-4">
                <button class="btn btn-secondary w-100">UNLOCK</button>
            </div>
        </div>
        <div class="col-6">
            <div class="mb-4">
                <a href="{{ url($path.'/logout') }}" class="btn btn-danger w-100">Logout</a>
            </div>
        </div>
    </div>
</form>
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