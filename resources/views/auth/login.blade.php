@extends('layouts.auth')

@section('css')
<style>
/* Remove old background image */
body {
  margin: 0;
  padding: 0;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  font-family: Arial, sans-serif;
  overflow: hidden; /* Prevent scrollbars with video */
}

/* Fullscreen background video */
#bg-video {
  position: fixed;
  right: 0;
  bottom: 0;
  min-width: 100%;
  min-height: 100%;
  object-fit: cover;
  z-index: -1; /* Keep it behind content */
}

/* Card stays above the video */
.auth-card {
  background: rgba(255, 255, 255, 0.9); /* slight transparency (glass style) */
  border-radius: 15px;
  padding: 40px;
  max-width: 400px;
  width: 100%;
  box-shadow: 0 10px 30px #48a0a3e0;
  text-align: center;
  z-index: 1;
}

.auth-card h2 { margin-bottom:10px; font-weight:700; color:#2c3e50 }
.auth-card p { margin-bottom:20px; color:#666 }
.form-control { border-radius:8px; padding:10px }
.btn-primary { width:100%; padding:12px; border-radius:8px; background:#2c5364; border:none; color:#fff; font-weight:600 }
.btn-primary:hover { background:#203a43 }
.extra-links { margin-top:15px }
.extra-links a { color:#2c5364; font-weight:500 }

.login-container {
    position: relative;
    z-index: 1;
    background: rgba(255, 255, 255, 0.8); /* semi-transparent */
    padding: 30px;
    border-radius: 10px;
}
.video-background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: -1;
}
.logo {
  width: 120px;
  margin-bottom: 20px;
  opacity: 0;
  animation: fadeInDown 1s ease forwards, bounce 0.8s ease 1s forwards;
}

@keyframes fadeInDown {
  0% { opacity:0; transform:translateY(-50px); }
  100% { opacity:1; transform:translateY(0); }
}

@keyframes bounce {
  0%,20%,50%,80%,100% { transform:translateY(0); }
  40% { transform:translateY(-20px); }
  60% { transform:translateY(-10px); }
}
    /* Fullscreen video */
    #bg-video {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;  /* Ensures video covers entire area */
        z-index: -1;        /* Keep video behind content */
    }

    /* Page content */
    .content {
        position: relative;
        z-index: 1;
        color: white;
        text-align: center;
        top: 40%;
        transform: translateY(-40%);
        font-family: Arial, sans-serif;
    }
</style>
@endsection

@section('content')
<!-- Background Video -->
<video autoplay muted loop id="bg-video">
    <source src="{{ asset('videos/bg.mp4') }}" type="video/mp4">
    Your browser does not support HTML5 video.
</video>


<div class="d-flex justify-content-center align-items-center w-100" style="min-height:100vh;">
  <div class="auth-card">
    <img src="{{ asset('/logo1.png') }}" alt="Shipxpeed Logo" class="mb-4 logo" width="150">
    <h2>Welcome Back</h2>
    <p>Login to continue</p>

    <form method="POST" action="{{ route('login') }}">
      @csrf
      <input type="hidden" name="login_as" value="{{ $guard == 'admin' ? 'web' : ($guard ?? 'web') }}">

      <div class="mb-3">
        <input type="text" class="form-control @error('email') is-invalid @enderror"
               name="email" placeholder="Email or Username" value="{{ old('email') }}" required autofocus>
        @error('email') <span class="invalid-feedback"><strong>{{ $message }}</strong></span> @enderror
      </div>

      <div class="mb-3">
        <input type="password" class="form-control @error('password') is-invalid @enderror"
               name="password" placeholder="Password" required>
        @error('password') <span class="invalid-feedback"><strong>{{ $message }}</strong></span> @enderror
      </div>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <label class="d-flex align-items-center gap-2" style="font-weight:500">
          <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember me
        </label>
        @if (Route::has('forget.password'))
          <a href="{{ route('forget.password', $guard ?? 'web') }}">Forgot Password?</a>
        @endif
      </div>

      <button type="submit" class="btn btn-primary">Login</button>

      <div class="extra-links">
        @if (Route::has('register'))
          <p>Don’t have an account? <a href="{{ route('register') }}">Register</a></p>
        @endif
      </div>
    </form>
  </div>
</div>
@endsection
