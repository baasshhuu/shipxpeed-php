@extends('frontend.layouts.fullwidth')

@section('content')
<style>
    * {
        box-sizing: border-box;
    }
    
    .register-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 50%,    
        .form-group {
            margin-bottom: 12px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 3px;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.8rem;
        }%);
        background-size: 400% 400%;
        animation: gradientShift 15s ease infinite;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 0;
        position: relative;
        overflow: hidden;
    }
    
    .register-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: 
            radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
            radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
            radial-gradient(circle at 40% 40%, rgba(120, 119, 198, 0.2) 0%, transparent 50%);
        pointer-events: none;
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
        .register-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        max-width: 850px;
        width: 100%;
        height: 85vh;
        max-height: 600px;
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
        
        .right-section {
            padding: 15px 15px;
            height: 100vh;
            overflow-y: auto;
        }
        
        .register-form h3 {
            font-size: 1.4rem;
            margin-bottom: 5px;
        }
        
        .form-subtitle {
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        
        .form-group label {
            font-size: 0.8rem;
            margin-bottom: 3px;
        }
        
        .form-control {
            padding: 10px 12px;
            font-size: 0.9rem;
        }
        
        .btn-register, .btn-login {
            padding: 12px;
            font-size: 0.9rem;
        }
        
        .button-group {
            margin-top: 8px;
        }
        
        .or-divider {
            margin: 8px 0;
        }
        
        .text-danger {
            font-size: 0.7rem;
            min-height: 14px;
        }
    }
    
    .register-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #667eea);
        background-size: 200% 100%;
        animation: shimmer 3s linear infinite;
    }
    
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    
    .left-section {
        background: linear-gradient(135deg, #1f4da9 0%, #31599d 50%, #0d2464 100%);
        color: white;
        padding: 40px 30px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
        box-shadow: 
            0 6px 20px rgba(102, 126, 234, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }
    
    .left-section::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: 
            radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px),
            radial-gradient(circle, rgba(255,255,255,0.05) 1px, transparent 1px);
        background-size: 50px 50px, 20px 20px;
        animation: float 20s infinite linear;
        pointer-events: none;
    }
    
    .left-section::after {
        content: '';
        position: absolute;
        top: 20px;
        right: 20px;
        width: 100px;
        height: 100px;
        background: linear-gradient(45deg, rgba(255,255,255,0.1), transparent);
        border-radius: 50%;
        animation: pulse 4s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.2); opacity: 0.8; }
    }
    
    @keyframes float {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
    
    .left-section .content {
        position: relative;
        z-index: 2;
    }
    
    .left-section h2 {
        font-size: 2.2rem;
        font-weight: 800;
        margin-bottom: 15px;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: textShine 3s ease-in-out infinite;
        position: relative;
    }
    
    @keyframes textShine {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    
    .left-section p {
        font-size: 1rem;
        opacity: 0.95;
        line-height: 1.6;
        margin-bottom: 25px;
        font-weight: 300;
        letter-spacing: 0.3px;
    }
    
    .mockup-image {
        max-width: 320px;
        height: auto;
        border-radius: 20px;
        box-shadow: 
            0 20px 60px rgba(0, 0, 0, 0.4),
            0 0 0 1px rgba(255, 255, 255, 0.1);
        transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
        transition: transform 0.6s ease;
        animation: floatImage 6s ease-in-out infinite;
    }
    
    .mockup-image:hover {
        transform: perspective(1000px) rotateY(0deg) rotateX(0deg) scale(1.05);
    }
    
    @keyframes floatImage {
        0%, 100% { transform: perspective(1000px) rotateY(-5deg) rotateX(5deg) translateY(0px); }
        50% { transform: perspective(1000px) rotateY(-5deg) rotateX(5deg) translateY(-10px); }
    }
    
    .right-section {
        padding: 25px 25px;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        position: relative;
        background: rgba(255, 255, 255, 0.8);
        height: 100%;
    }
    
    .right-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: 
            radial-gradient(circle at 10% 20%, rgba(102, 126, 234, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 90% 80%, rgba(240, 147, 251, 0.05) 0%, transparent 50%);
        pointer-events: none;
    }
    
        .register-form h3 {
        font-size: 1.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 5px;
        text-align: center;
        position: relative;
    }
    
    .register-form h3::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 50%;
        transform: translateX(-50%);
        width: 40px;
        height: 2px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 2px;
    }
    
    .form-subtitle {
        text-align: center;
        color: #718096;
        margin-bottom: 20px;
        font-size: 0.9rem;
        font-weight: 400;
        opacity: 0.8;
    }
    
    .form-group {
        margin-bottom: 10px;
        position: relative;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 3px;
        font-weight: 600;
        color: #4a5568;
        font-size: 0.8rem;
        font-size: 0.9rem;
    }
    
        .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.9rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 
            0 2px 6px rgba(0, 0, 0, 0.05),
            inset 0 1px 0 rgba(255, 255, 255, 0.7);
        position: relative;
    }
    
    .form-control::placeholder {
        color: #a0aec0;
        font-weight: 400;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        background: linear-gradient(145deg, #ffffff 0%, #ffffff 100%);
        box-shadow: 
            0 0 0 4px rgba(102, 126, 234, 0.15),
            0 8px 25px rgba(102, 126, 234, 0.2),
            inset 0 1px 0 rgba(255, 255, 255, 0.7);
        transform: translateY(-2px);
    }
    
    .form-control.is-invalid {
        border-color: #e53e3e;
        background: linear-gradient(145deg, #fed7d7 0%, #feebc8 100%);
        box-shadow: 
            0 0 0 4px rgba(229, 62, 62, 0.15),
            0 8px 25px rgba(229, 62, 62, 0.2);
        animation: shake 0.5s ease-in-out;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    select.form-control {
        cursor: pointer;
    }
    
    .text-danger {
        color: #e53e3e;
        font-size: 0.7rem;
        margin-top: 1px;
        font-weight: 500;
        min-height: 12px;
        line-height: 1.2;
    }
    
    .btn-register {
        width: 100%;
        padding: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        background-size: 200% 100%;
        border: none;
        border-radius: 8px;
        color: white;
        font-size: 0.85rem;
        font-weight: 700;
        letter-spacing: 0.6px;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .btn-register::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.6s;
    }
    
    .btn-register:hover:not(:disabled) {
        background-position: 100% 0;
        transform: translateY(-3px) scale(1.02);
        box-shadow: 
            0 15px 40px rgba(102, 126, 234, 0.6),
            inset 0 1px 0 rgba(255, 255, 255, 0.3);
    }
    
    .btn-register:hover:not(:disabled)::before {
        left: 100%;
    }
    
    .btn-register:active:not(:disabled) {
        transform: translateY(-1px) scale(1.01);
    }
    
    .btn-register:disabled {
        background: linear-gradient(135deg, #cbd5e0 0%, #a0aec0 100%);
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .button-group {
        margin-top: 8px;
    }
    
   
    .or-divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
    }
    
    .or-divider span {
        background: white;
        padding: 0 15px;
        color: #a0aec0;
        font-size: 0.8rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    .btn-login {
        display: inline-block;
        width: 100%;
        padding: 10px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        color: #4a5568;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 
            0 3px 10px rgba(0, 0, 0, 0.05),
            inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }
    
    .btn-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
        transition: left 0.6s;
    }
    
    .btn-login:hover {
        background: linear-gradient(135deg, #1f4da9 0%, #31599d 50%, #0d2464 100%);
        color: white;
        border-color: #667eea;
        transform: translateY(-2px) scale(1.01);
        box-shadow: 
            0 10px 30px rgba(102, 126, 234, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        text-decoration: none;
    }
    
    .btn-login:hover::before {
        left: 100%;
    }
    
    .button-group {
        margin-top: 20px;
    }
    
    .btn-login {
        display: block;
        width: 100%;
        padding: 10px;
        background: linear-gradient(135deg, #ffffff 0%, #f7fafc 100%);
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        color: #4a5568;
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 
            0 4px 15px rgba(0, 0, 0, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }
    
    .btn-login::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
        transition: left 0.6s;
    }
    
    .btn-login:hover {
        background: linear-gradient(135deg, #1f4da9 0%, #31599d 50%, #0d2464 100%);
        color: white;
        border-color: #667eea;
        transform: translateY(-2px) scale(1.01);
        box-shadow: 
            0 10px 25px rgba(102, 126, 234, 0.3),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        text-decoration: none;
    }
    
    .btn-login:hover::before {
        left: 100%;
    }
    
    .or-divider {
        text-align: center;
        margin: 10px 0;
        position: relative;
    }
    
    .or-divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(to right, transparent, #e2e8f0, transparent);
    }
    
    .or-divider span {
        background: white;
        color: #a0aec0;
        padding: 0 20px;
        font-size: 0.9rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        z-index: 1;
    }
    
    .login-link {
        text-align: center;
        margin-top: 25px;
        color: #718096;
    }
    
    .login-link a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    
    .login-link a:hover {
        color: #764ba2;
        text-decoration: underline;
    }
    
    .alert {
        border: none;
        border-radius: 16px;
        padding: 20px 25px;
        margin-bottom: 25px;
        font-weight: 500;
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
    }
    
    .alert::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: currentColor;
    }
    
    .alert-success {
        background: linear-gradient(135deg, rgba(198, 246, 213, 0.9) 0%, rgba(154, 230, 180, 0.9) 100%);
        color: #22543d;
        box-shadow: 0 10px 25px rgba(56, 161, 105, 0.2);
    }
    
    .alert-danger {
        background: linear-gradient(135deg, rgba(254, 215, 215, 0.9) 0%, rgba(252, 165, 165, 0.9) 100%);
        color: #742a2a;
        box-shadow: 0 10px 25px rgba(229, 62, 62, 0.2);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .register-container {
            padding: 5px;
            min-height: 100vh;
        }
        
        .register-card {
            border-radius: 0;
            height: 100vh;
            max-height: none;
            max-width: 100%;
        }
        
        .left-section {
            display: none;
        }
        
        .col-md-6 {
            max-width: 100%;
        }
    }
    
    /* Tablet Design */
    @media (min-width: 769px) and (max-width: 1024px) {
        .register-container {
            padding: 15px;
        }
        
        .register-card {
            max-width: 95%;
            height: 90vh;
            max-height: 650px;
        }
        
        .left-section {
            padding: 30px 20px;
        }
        
        .left-section h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .left-section p {
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .mockup-image {
            max-width: 200px;
        }
        
        .right-section {
            padding: 20px 20px;
        }
        
        .register-form h3 {
            font-size: 1.6rem;
        }
        
        .form-group {
            margin-bottom: 12px;
        }
    }
    
    /* Large Desktop */
    @media (min-width: 1025px) {
        .register-card {
            max-width: 850px;
        }
    }
    
    /* Loading Animation */
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
        width: 24px;
        height: 24px;
        margin: -12px 0 0 -12px;
        border: 3px solid transparent;
        border-top: 3px solid white;
        border-right: 3px solid white;
        border-radius: 50%;
        animation: spin 1s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Floating elements animation */
    .floating-shapes {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        pointer-events: none;
    }
    
    .floating-shapes::before,
    .floating-shapes::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        animation: floatUpDown 6s ease-in-out infinite;
    }
    
    .floating-shapes::before {
        width: 80px;
        height: 80px;
        top: 20%;
        left: 10%;
        animation-delay: 0s;
    }
    
    .floating-shapes::after {
        width: 120px;
        height: 120px;
        top: 60%;
        right: 10%;
        animation-delay: 3s;
    }
    
    @keyframes floatUpDown {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }
    
    /* Enhanced mockup image */
    .mockup-image {
        max-width: 250px;
        height: auto;
        border-radius: 15px;
        box-shadow: 
            0 20px 40px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.1);
        transition: transform 0.4s ease;
        position: relative;
    }
    
    .mockup-image:hover {
        transform: translateY(-5px) scale(1.02);
    }
</style>

<div class="register-container">
    <div class="register-card">
        <div class="row no-gutters h-100">
            <div class="col-md-6 left-section">
                <div class="floating-shapes"></div>
                <div class="content">
                    <h2>Welcome to<br>✨ Shipxpeed</h2>
                    <p>Fast and reliable shipping solutions for your business. Experience seamless logistics with our innovative platform designed for modern commerce.</p>
                    <img src="{{ asset('assets/website/img/loginmockup.png') }}" alt="Shipping Mockup" class="mockup-image">
                </div>
            </div>

            <div class="col-md-6 right-section">
                <div class="register-form" style="padding:20px;">
                    <h3>Create Account</h3>
                    <p class="form-subtitle">Join thousands of businesses shipping smarter</p>
                    
                    <div id="formMessages"></div>

                    <form id="registerForm" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label>User Type</label>
                            <select class="form-control" name="user_type">
                                <option value="">Select your type</option>
                                <option value="1">Individual</option>
                                <option value="2">Business</option>
                            </select>
                            <div class="text-danger error-user_type"></div>
                        </div>

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Enter your full name">
                            <div class="text-danger error-name"></div>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="Enter your email">
                            <div class="text-danger error-email"></div>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone_number" class="form-control" placeholder="Enter your phone number">
                            <div class="text-danger error-phone_number"></div>
                        </div>

                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" oninput="checkPasswordMatch()">
                            <div class="text-danger error-password"></div>
                        </div>

                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="password_confirmation" id="confirm_password" class="form-control" placeholder="Confirm your password" oninput="checkPasswordMatch()">
                            <div class="text-danger error-password_confirmation"></div>
                        </div>

                        <div class="button-group">
                            <button type="submit" id="submitBtn" class="btn-register" disabled>
                                🚀 Create Account
                            </button>
                            
                            <div class="or-divider">
                                <span>or</span>
                            </div>
                            
                            <a href="{{ route('seller.login') }}" class="btn-login">
                                🔑 Already have an account? Login
                            </a>
                        </div>
                    </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const errorDiv = document.querySelector('.error-password_confirmation');
    const submitBtn = document.getElementById('submitBtn');

    if (password && confirm) {
        if (password !== confirm) {
            errorDiv.textContent = 'Passwords do not match.';
            document.getElementById('confirm_password').classList.add('is-invalid');
            submitBtn.disabled = true;
        } else {
            errorDiv.textContent = '';
            document.getElementById('confirm_password').classList.remove('is-invalid');
            // Check if all required fields are filled
            const requiredFields = ['user_type', 'name', 'email', 'phone_number', 'password'];
            const allFilled = requiredFields.every(field => {
                const element = document.querySelector(`[name="${field}"]`);
                return element && element.value.trim() !== '';
            });
            submitBtn.disabled = !allFilled;
        }
    } else {
        errorDiv.textContent = '';
        document.getElementById('confirm_password').classList.remove('is-invalid');
        submitBtn.disabled = true;
    }
}

// Check form validity on input change
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const inputs = form.querySelectorAll('input, select');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            // Clear previous error styling
            this.classList.remove('is-invalid');
            const errorDiv = document.querySelector(`.error-${this.name}`);
            if (errorDiv) errorDiv.textContent = '';
            
            // Check form validity for submit button
            checkFormValidity();
        });
        
        input.addEventListener('blur', function() {
            // Validate individual field on blur
            validateField(this);
        });
    });
    
    function checkFormValidity() {
        const requiredFields = ['user_type', 'name', 'email', 'phone_number', 'password', 'password_confirmation'];
        const submitBtn = document.getElementById('submitBtn');
        
        const allFilled = requiredFields.every(field => {
            const element = document.querySelector(`[name="${field}"]`);
            return element && element.value.trim() !== '';
        });
        
        const passwordsMatch = document.getElementById('password').value === document.getElementById('confirm_password').value;
        
        submitBtn.disabled = !(allFilled && passwordsMatch);
    }
    
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        switch(field.name) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (value && !emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address.';
                }
                break;
            case 'phone_number':
                if (value && value.length < 10) {
                    isValid = false;
                    errorMessage = 'Phone number must be at least 10 digits.';
                }
                break;
            case 'password':
                if (value && value.length < 6) {
                    isValid = false;
                    errorMessage = 'Password must be at least 6 characters.';
                }
                break;
        }
        
        if (!isValid) {
            field.classList.add('is-invalid');
            const errorDiv = document.querySelector(`.error-${field.name}`);
            if (errorDiv) errorDiv.textContent = errorMessage;
        }
    }
});

$('#registerForm').on('submit', function(e) {
    e.preventDefault();

    let formData = new FormData(this);
    const submitBtn = $('#submitBtn');
    const originalText = submitBtn.html();
    
    // Clear previous errors and add loading state
    $('.text-danger').html('');
    $('.form-control').removeClass('is-invalid');
    $('#formMessages').html('');
    
    // Add loading state
    submitBtn.addClass('loading').html('Creating Account...').prop('disabled', true);

    $.ajax({
        url: "{{ route('register.add') }}",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#formMessages').html('<div class="alert alert-success"><strong>Success!</strong> ' + response.message + '</div>');
            $('#registerForm')[0].reset();
            
            // Reset submit button
            submitBtn.removeClass('loading').html('✓ Account Created').css('background', 'linear-gradient(135deg, #48bb78 0%, #38a169 100%)');

            setTimeout(function() {
                window.location.href = response.redirect_url;
            }, 2000);
        },
        error: function(xhr) {
            // Reset submit button
            submitBtn.removeClass('loading').html(originalText).prop('disabled', false);
            
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                $('#formMessages').html('<div class="alert alert-danger"><strong>Please fix the following errors:</strong></div>');
                
                $.each(errors, function(key, value) {
                    $('.error-' + key).html(value[0]);
                    $('[name="' + key + '"]').addClass('is-invalid');
                });
                
                // Scroll to first error
                const firstError = $('.is-invalid').first();
                if (firstError.length) {
                    firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            } else {
                $('#formMessages').html('<div class="alert alert-danger"><strong>Error!</strong> Something went wrong. Please try again.</div>');
            }
        }
    });
});
</script>
@endsection
