@extends('frontend.layouts.fullwidth')

@section('content')
<style>
    * {
        box-sizing: border-box;
    }

    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        background: none !important;
    }

    .login-container {
        min-height: 100vh;
        min-width: 100vw;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        background: url('{{ asset('assets/website/img/herosection.png') }}') center center/cover no-repeat;
        background-size: 400% 400%;
        animation: gradientShift 15s ease infinite;
        position: relative;
        overflow: hidden;
        width: 100vw;
        height: 100vh;
    }

    .login-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: 
            radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.2) 0%, transparent 70%),
            radial-gradient(circle at 80% 30%, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        pointer-events: none;
    }

    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .login-card {
        background: rgba(255,255,255,0.93);
        border-radius: 16px;
        box-shadow: 0 8px 36px 4px rgba(44, 62, 80, 0.20);
        display: flex;
        width: 885px;
        min-width: 320px;
        max-width: 98vw;
        overflow: hidden;
        animation: slideUp 0.6s ease-out;
        position: relative;
        height: 72vh;
        min-height: 560px;
    }

    @keyframes slideUp {
        from { 
            opacity: 0;
            transform: translateY(50px);
        }
        to { 
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Sections */
    .left-section, .right-section {
        flex: 1 1 0px;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-sizing: border-box;
    }
    .left-section {
        background: white;
        -webkit-backdrop-filter: blur(9px);
        backdrop-filter: blur(9px);
        color: #1a202c;
        align-items: center;
        text-align: center;
        box-shadow: none;
        border-right: 1px solid rgba(120,119,198,0.08);
        position: relative;
    }
    .left-section .content {
        position: relative;
        width: 100%;
        background: none;
        padding: 0;
    }
    .left-section .logo {
        max-width: 130px;
        margin-bottom: 16px;
        filter: brightness(0.9) invert(0.02);
        width: 90vw;
        max-width: 130px;
        height: auto;
    }
    .left-section h2 {
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
        color: #292f48;
        background: none;
        text-shadow: 0 1px 18px rgba(120,119,198,0.08);
        animation: none;
    }
    .left-section p {
        font-size: 1rem;
        margin-bottom: 16px;
        line-height: 1.45;
        color: #483586;
        opacity: 0.82;
        font-weight: 400;
    }
    .mockup-image {
        max-width: 325px;
        width: 90vw;
        margin: 0 auto;
        animation: none;
        filter: drop-shadow(0 8px 24px rgba(120, 119, 198, 0.07));
        height: auto;
    }
    .left-section::before,
    .left-section::after {
        display: none !important;
        content: none;
    }
    .right-section {
        background: transparent;
        align-items: stretch;
        justify-content: center;
        position: relative;
        z-index: 2;
        padding: 40px 32px;
        min-width: 0;
        box-sizing: border-box;
        width: 100%;
        height: 100%;
        min-height: 100%;
        /* Remove scroll on right section */
        overflow: visible;
        display: flex;
    }
    .login-form {
        position: relative;
        width: 100%;
        max-width: 350px;
        min-width: 260px;
        margin: auto;
        z-index: 2;
        padding: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .login-form h3 {
        font-size: 1.4rem;
        font-weight: 700;
        background: linear-gradient(135deg, #2d3748 0%, #667eea 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 8px;
        text-align: center;
        position: relative;
        letter-spacing: 0.3px;
    }
    .login-form h3::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 50%;
        transform: translateX(-50%);
        width: 129px;
        height: 2px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 2px;
    }
    .form-subtitle {
        text-align: center;
        color: #718096;
        margin-bottom: 14px;
        font-size: 0.96rem;
        opacity: 0.85;
        font-weight: 400;
    }
    .form-group {
        position: relative;
    }
    .form-group label {
        display: block;
        margin-bottom: 7px;
        font-weight: 500;
        color: #4a5568;
        font-size: 0.99rem;
    }
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 7px;
        font-size: 0.98rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #f9fafc;
        box-shadow: 0 1px 3px rgba(120,119,198,0.08);
        position: relative;
    }
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        background: #fff;
        box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.10),
                    0 2px 8px rgba(102, 126, 234, 0.08);
        transform: translateY(-1px);
    }
    .form-control.is-invalid {
        border-color: #e53e3e;
        background: #fed7d7;
        box-shadow: 0 0 0 2px rgba(229, 62, 62, 0.07);
        animation: shake 0.5s ease-in-out;
    }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-2px); }
        75% { transform: translateX(2px); }
    }
    .text-danger {
        color: #e53e3e;
        font-size: 0.84rem;
        margin-top: 2px;
        min-height: 17px;
        line-height: 1.22;
    }
    .btn-login {
        width: 100%;
        margin-top: 8px;
        padding: 12px 0;
        background: linear-gradient(135deg, #1f4da9 0%, #31599d 50%, #0d2464 100%);
        background-size: 200% 100%;
        border: none;
        border-radius: 5px;
        color: white;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.24s cubic-bezier(0.4,0,0.2,1);
        position: relative;
        overflow: hidden;
        letter-spacing: 0.1em;
        min-height: 42px;
    }
    .btn-login:disabled {
        background: #dfdfdf !important;
        color: #a0aec0 !important;
        cursor: not-allowed;
    }
    .btn-login::before {
        display: none;
    }
    .btn-login:hover:not(:disabled) {
        background-position: 100% 0;
        box-shadow: 0 4px 20px rgba(102,126,234,0.13);
        transform: translateY(-2px) scale(1.01);
    }
    .login-links {
        margin-top: 22px;
    }
    .link-group {
        text-align: center;
        font-size: 0.97rem;
        margin-bottom: 10px;
    }
    .link-group a {
        color: #667eea;
        font-weight: 500;
        transition: color 0.2s ease;
        text-decoration: none;
    }
    .link-group a:hover {
        color: #4f46e5;
        text-decoration: underline;
    }
    .or-divider {
        display: flex;
        align-items: center;
        text-align: center;
        color: #cbd5e0;
        margin: 18px 0;
        font-size: 0.99rem;
    }
    .or-divider::before,
    .or-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e2e8f0;
        margin: 0 6px;
    }
    .or-divider span {
        background: transparent;
        color: #7b7d98;
        padding: 0 7px;
        position: relative;
        z-index: 1;
        font-size: 1.02rem;
        font-weight: 500;
    }
    .alert {
        border: none;
        border-radius: 9px;
        padding: 14px 18px;
        margin-bottom: 15px;
        font-weight: 500;
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(7px);
    }
    .alert-success {
        background: #edfdf5;
        color: #22543d;
        box-shadow: 0 2px 5px rgba(56, 161, 105, 0.05);
    }
    .alert-danger {
        background: #fff5f5;
        color: #742a2a;
        box-shadow: 0 2px 5px rgba(229, 62, 62, 0.06);
    }
    .loading {
        position: relative;
        pointer-events: none;
        overflow: hidden;
    }
    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 3px solid transparent;
        border-top: 3px solid #fff;
        border-right: 3px solid #fff;
        border-radius: 50%;
        animation: spin 0.85s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    /* --- Responsive Design --- */
    @media (max-width: 900px) {
        .login-card {
            width: 96vw;
            min-width: 0;
        }
        .left-section .mockup-image {
            max-width: 180px;
        }
        .right-section, .login-form {
            max-width: 100vw;
        }
    }
    @media (max-width: 660px) {
        .login-card {
            flex-direction: column;
            max-width: 99vw;
            width: 99vw;
            min-height: 100vh;
            height: 96vh !important;
            box-shadow: 0 2px 18px rgba(44,62,80,0.10);
            border-radius: 9px;
        }
        .left-section,
        .left-section .content,
        .left-section .logo,
        .mockup-image {
            display: none !important;
        }
        .right-section {
            padding: 8vw 3vw 8vw 3vw !important;
            width: 100vw;
            min-width: 0;
            height: 100vh;
            min-height: 100vh;
            box-sizing: border-box;
            background: rgba(255,255,255,0.93);
            box-shadow: none;
        }
        .login-form {
            min-height: 72vh;
            height: 70vh;
            justify-content: center;
            max-width: 99vw;
        }
    }
    @media (max-width: 480px) {
        .login-card {
            width: 100vw;
            max-width: 100vw;
            border-radius: 0;
            height: 100vh !important;
            min-height: 100vh !important;
        }
        .right-section {
            padding: 9vw 2vw 9vw 2vw !important;
            min-height: 100vh;
            height: 100vh;
        }
        .login-form {
            max-width: 99vw;
            padding: 0;
            min-height: 82vh;
            height: 80vh;
        }
    }
    @media (max-width: 375px) {
        .login-form {
            min-height: 90vh;
            height: 90vh;
        }
    }
    body::-webkit-scrollbar, html::-webkit-scrollbar {
        display: none;
        width: 0;
        background: transparent;
    }
    body, html {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE 10+ */
    }
</style>

<div class="login-container">
    <div class="login-card">
        <!-- Left Side - transparent glass effect (hidden on mobile, see CSS above) -->
        <div class="left-section">
            <div class="content">
                <img src="{{ asset('assets/website/img/newlogo-bg.png') }}" alt="Shipxpeed Logo" class="logo img-fluid">
                <h2>Welcome Back!</h2>
                <p>Fast and reliable shipping solutions for your business.<br>Seamless logistics for your success.</p>
                <img src="{{ asset('assets/website/img/loginbg.png') }}" alt="Shipping Mockup" class="mockup-image img-fluid">
            </div>
        </div>
        <!-- Right Side (form) -->
        <div class="right-section">
            <div class="login-form">
                <h3>Login to Shipxpeed</h3>
                <p class="form-subtitle">Sign in to your account below</p>
                <div id="formMessages"></div>
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" id="loginForm" action="{{ route('seller.login') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" name="email" id="email" placeholder="Enter your email" class="form-control" required>
                        <div class="text-danger error-email"></div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" placeholder="Enter password" class="form-control" required>
                        <div class="text-danger error-password"></div>
                    </div>
                    <div class="button-group">
                        <button type="submit" class="btn-login" id="loginBtn">
                            Sign In
                        </button>
                    </div>
                </form>

                <div class="login-links">
                    <div class="link-group">
                        <span style="color:#718096;">Forgot your password?</span>
                        <a href="{{ route('seller.password.request') }}">Reset Password</a>
                    </div>
                    <div class="or-divider"><span>or</span></div>
                    <div class="link-group">
                        <span style="color:#718096;">New to Shipxpeed?</span>
                        <a href="{{ route('register.get') }}">Create an account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Enhanced form validation and submission
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();

        // Clear previous errors and states
        $('.text-danger').html('');
        $('.form-control').removeClass('is-invalid');
        $('#formMessages').html('');
        
        // Add loading state
        const loginBtn = $('#loginBtn');
        const originalText = loginBtn.text();
        loginBtn.addClass('loading').prop('disabled', true).text('Signing In...');

        let formData = $(this).serialize();

        $.ajax({
            url: $(this).attr('action'),
            method: "POST",
            data: formData,
            success: function(response) {
                // Show success message briefly before redirect
                $('#formMessages').html('<div class="alert alert-success">Login successful! Redirecting...</div>');
                
                setTimeout(function() {
                    window.location.href = response.redirect || "{{ route('seller.dashboard') }}";
                }, 1000);
            },
            error: function(xhr) {
                // Remove loading state
                loginBtn.removeClass('loading').prop('disabled', false).text(originalText);
                
                if (xhr.status === 422) {
                    // Validation errors
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        $('.error-' + key).html(value[0]);
                        $('[name="' + key + '"]').addClass('is-invalid');
                    });
                } else if (xhr.status === 401) {
                    // Authentication error
                    $('#formMessages').html('<div class="alert alert-danger">' + xhr.responseJSON.message + '</div>');
                } else {
                    // General error
                    $('#formMessages').html('<div class="alert alert-danger">Something went wrong. Please try again.</div>');
                }
                
                // Auto-hide error messages after 5 seconds
                setTimeout(function() {
                    $('.alert-danger').fadeOut();
                }, 5000);
            }
        });
    });

    // Real-time input validation
    $('.form-control').on('input blur', function() {
        const input = $(this);
        const value = input.val().trim();
        const name = input.attr('name');
        
        // Remove error state when user starts typing
        if (value) {
            input.removeClass('is-invalid');
            $('.error-' + name).html('');
        }
        
        // Basic email validation
        if (name === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                input.addClass('is-invalid');
                $('.error-' + name).html('Please enter a valid email address');
            }
        }
        
        // Password validation
        if (name === 'password' && value && value.length < 6) {
            input.addClass('is-invalid');
            $('.error-' + name).html('Password must be at least 6 characters');
        }
    });

    // Add floating label effect
    $('.form-control').on('focus blur', function() {
        const input = $(this);
        const label = input.siblings('label');
        
        if (input.is(':focus') || input.val()) {
            label.addClass('active');
        } else {
            label.removeClass('active');
        }
    });

    // Add keyboard navigation
    $('.form-control').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const currentIndex = $('.form-control').index(this);
            const nextInput = $('.form-control').eq(currentIndex + 1);
            
            if (nextInput.length) {
                nextInput.focus();
            } else {
                $('#loginForm').submit();
            }
        }
    });

    // Auto-hide success messages
    setTimeout(function() {
        $('.alert-success').fadeOut();
    }, 5000);

    // Add smooth scrolling for mobile
    if (window.innerWidth <= 768) {
        $('.form-control').on('focus', function() {
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: $('.right-section').offset().top - 20
                }, 300);
            }, 300);
        });
    }
});
</script>
@endsection
