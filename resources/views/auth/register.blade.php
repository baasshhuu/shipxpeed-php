@extends('layouts.auth')

@section('css')
<style>
  body{
    margin:0; padding:0; min-height:100vh;
    display:flex; justify-content:center; align-items:center;
    background: url("{{ asset('bg.png') }}") no-repeat center center;
    background-size: cover;
    font-family: Arial, sans-serif;
  }

  /* Smaller, cleaner card */
  .auth-card{
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(6px);
    border-radius: 14px;
    padding: 28px;
    max-width: 420px;   /* ↓ was 520px */
    width: 100%;
    box-shadow: 0 10px 28px rgba(0,0,0,.18);
    text-align: center;
  }
  @media (max-width: 576px){
    .auth-card{ max-width: 360px; padding: 22px; margin: 12px; }
  }

  .auth-card h2{ margin-bottom:6px; font-weight:700; color:#2c3e50 }
  .auth-card p{ margin-bottom:18px; color:#666 }

  .form-control{
    border-radius: 8px; padding: 12px;
    border:1px solid #ddd; margin-bottom: 12px;
  }
  .form-control:focus{
    border-color:#2c5364; box-shadow:0 0 8px rgba(44,83,100,.22)
  }

  .otp-row{ display:flex; gap:.5rem; align-items:center; margin-bottom:12px }
  .otp-row .form-control{ margin-bottom:0 }

  .btn-primary{
    width:100%; padding:12px; border-radius:8px;
    background:#2c5364; border:none; color:#fff; font-weight:600;
    transition:.2s ease;
  }
  .btn-primary:hover{ background:#203a43 }
  .btn-secondary{ border-radius:8px; padding:10px 16px }

  /* ✅ Terms checkbox alignment fix */
  .form-check{
    display:flex; align-items:flex-start; gap:.5rem;
    text-align:left; margin-bottom: 12px;
  }
  .form-check-input{ margin-top:.25rem; flex-shrink:0; }
  .form-check-label{ font-size:14px; color:#444; line-height:1.4; }
  .form-check-label a{ text-decoration: underline; }

  /* Make validation messages behave nicely under inputs */
  .invalid-feedback{ display:block; text-align:left; }

  .extra-links{ margin-top: 12px }
  .extra-links a{ color:#2c5364; font-weight:500 }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-center align-items-center w-100" style="min-height:100vh;">
  <div class="auth-card">
    <h2>Create Account</h2>
    <p>Fill in the details below to register your company</p>

    <form method="POST" action="{{ route('register') }}">
      @csrf

      <input placeholder="Name" id="name" type="text"
             class="form-control @error('name') is-invalid @enderror"
             name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
      @error('name') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror

      <input class="form-control @error('email') is-invalid @enderror" type="email"
             name="email" required autocomplete="email" placeholder="Email Address"
             value="{{ old('email') }}" />
      @error('email') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror

      <input class="form-control @error('mobile') is-invalid @enderror" type="text"
             name="mobile" required autocomplete="mobile" placeholder="Mobile"
             value="{{ old('mobile') }}" maxlength="10" />
      @error('mobile') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror

      <div class="otp-row">
        <input class="form-control @error('otp') is-invalid @enderror" type="text"
               name="otp" placeholder="OTP Code" id="otp" />
        <button type="button" id="sendOtp" class="btn btn-secondary">
          Send OTP <i class="fa fa-refresh ms-2"></i>
        </button>
      </div>
      @error('otp') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror

      <input class="form-control @error('password') is-invalid @enderror" type="password"
             name="password" placeholder="Password" id="new-password" />
      @error('password') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror

      <input class="form-control @error('password_confirmation') is-invalid @enderror" type="password"
             name="password_confirmation" placeholder="Confirm Password" autocomplete="current-password" />
      @error('password_confirmation') <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span> @enderror

      <div class="form-check">
        <input name="terms" class="form-check-input" type="checkbox" id="form-check-default">
        <label class="form-check-label" for="form-check-default">
          I agree to the <a href="javascript:void(0);" class="text-primary">Terms and Conditions</a>
        </label>
      </div>

      <button class="btn btn-primary">SIGN UP</button>

      <div class="extra-links text-center">
        @if (Route::has('login'))
          <p class="mb-0">Already have an account?
            <a class="text-warning" href="{{ route('login') }}">Log In</a>
          </p>
        @endif
      </div>
    </form>
  </div>
</div>
@endsection

@section('js')
<script type="text/javascript">
$(function () {
  var validator = $("form").validate({
    rules: {
      name: { required: true, minlength: 2, maxlength: 100 },
      email: { required: true, customEmail: true, email: true },
      mobile: { required: true, number: true, indiaMobile: true, exactlength: 10 },
      otp: { required: true, number: true, exactlength: 6 },
      password: { required: true, minlength: 8, maxlength: 50 },
      password_confirmation: { required: true, minlength: 8, maxlength: 50, equalTo: "#new-password" },
      terms: { required: true },
    },
    messages: {
      name: { required: "Please enter name" },
      email: { required: "Please enter Email" },
      mobile: { required: "Please enter Mobile number" },
      password: { required: "Please enter Password" },
      otp: { required: "Please enter OTP Code." },
      password_confirmation: { required: "Please enter Confirm Password" },
      terms: { required: "Please select Terms and Conditions checkbox" },
    },
    errorPlacement: function (error, element) {
      if (element.attr("name") == "terms") {
        error.insertAfter(".form-check");
      } else {
        error.insertAfter(element);
      }
    }
  });

  $('#sendOtp').on('click', function () {
    var mobile = $('[name="mobile"]').val();
    if (!mobile) {
      return validator.showErrors({ mobile: 'Please enter mobile number first..!!' });
    }

    $(this).prop('disabled', true);
    $(this).find('i').addClass('fa-spin');
    var button = this;

    $.ajax({
      url: "{{ url('api/send-otp') }}",
      type: 'post',
      data: { mobile: mobile, is_register: 1 },
      headers: { 'x-api-key': "{{ config('constant.secret_token') }}" },
      dataType: 'json',
      success: function (data) {
        if (data.status) {
          if (window.toastr) toastr.success(data.message);
          setTimeout(() => {
            $(button).prop('disabled', false);
            $(button).find('i').removeClass('fa-spin');
          }, 30000);
        } else {
          if (window.toastr) toastr.error(data.message);
          validator.showErrors(data.data || {});
          $(button).prop('disabled', false);
          $(button).find('i').removeClass('fa-spin');
        }
      },
      error: function (data) {
        console.log('error', data);
        alert("OTP sending failed, please try again.");
        $(button).prop('disabled', false);
        $(button).find('i').removeClass('fa-spin');
      }
    });
  });
});
</script>
@endsection
