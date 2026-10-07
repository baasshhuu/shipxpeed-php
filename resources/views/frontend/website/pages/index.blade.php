@extends('frontend.website.layout.index')
@section('main_contant')

    <!-- <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content border-0 overflow-hidden">
                <div class="row g-0">

                    <div class="col-lg-6 d-none d-lg-flex flex-column" style="background-color: #555B61;">

                        <div class="h-50 p-4 text-white d-flex flex-column justify-content-center">
                            <h3 class="h4 fw-bold mb-3">Fast & Reliable Global Shipping</h3>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-primary me-2">WORLDWIDE</span>
                                <small class="text-white-50">Delivering to 230+ countries</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-truck text-success me-2"></i>
                                <small>Express delivery options available</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-shield-alt text-warning me-2"></i>
                                <small>Secure & insured shipments</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <small>Seamless customs clearance</small>
                            </div>
                        </div>


                        <div class="h-50 d-flex align-items-center p-4">
                            <img src="{{ asset('assets/website/img/img1.jpeg') }}" alt="Global Shipping"
                                class="img-fluid rounded w-100 object-fit-cover">
                        </div>
                    </div>


                  
                    <div class="col-lg-6">
                        <div class="p-4 p-md-5 h-100 d-flex flex-column">
                           
                            <div class="modal-header border-0 px-0 pt-0">
                                <h3 class="modal-title fw-bold fs-3 text-dark mb-1">Complete the form to access your
                                    special rates</h3>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                @if (session('success'))
                                    <div class="alert alert-success">
                                        {{ session('success') }}
                                    </div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form action="{{ route('inquiries.store') }}" method="POST">
                                    @csrf

                                    <label for="name">Name</label>
                                    <input type="text" id="name" name="name" placeholder="Enter Name"
                                        class="form-control mb-3" value="{{ old('name') }}">

                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" placeholder="Enter Email"
                                        class="form-control mb-3" value="{{ old('email') }}">

                                    <label for="phone">Phone</label>
                                    <input type="text" id="phone" name="phone" placeholder="Enter Phone"
                                        class="form-control mb-3" value="{{ old('phone') }}">

                                    <label for="company_name">Company Name</label>
                                    <input type="text" id="company_name" name="company_name"
                                        placeholder="Enter Company Name" class="form-control mb-3"
                                        value="{{ old('company_name') }}">

                                    <div class="form-group">
                                        <label for="service" style="color: #000">You Are Here For</label>
                                        <select id="service" name="service" class="form-control" required>
                                            <option value="" selected disabled>Choose Your Requirements</option>
                                            <option value="logistics">Logistics Solutions</option>
                                            <option value="shipping">Shipping Services</option>
                                            <option value="tracking">Order Tracking</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>

                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div> -->
   

    <style>
        html, body {
            overflow-x: hidden;
            width: 100%;
        }
        .hero-slider {
            min-height: 630px;
            padding: 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px 0 rgba(46,125,207,0.09);
        }
        .hero-slider__video-bg {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
        }
        .hero-slider::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 60% 10%, #d7eafd 0%, rgba(255,255,255,0.54) 80%);
            opacity: .82;
            z-index: 1;
            pointer-events: none;
        }
        .slider-container {
            position: relative;
            z-index: 3;
        }
        .hero-slider .slide {
            min-height: 440px;
            height: 100%;
            transition: opacity .80s, transform .80s cubic-bezier(.4,.2,.3,1);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .hero-slider .slide:not(.active) {
            opacity: 0;
            pointer-events: none;
            position: absolute;
        }
        .hero-slider .slide.active {
            opacity: 1;
            pointer-events: auto;
            position: relative;
            z-index: 5;
            animation: fadeInUpCustom 0.8s;
        }
        @keyframes fadeInUpCustom {
            0% { opacity:0; transform: translateY(28px);}
            100% { opacity:1; transform: translateY(0);}
        }
        .hero-slider h1, .hero-slider h2, .hero-slider h5, .hero-slider p,
        .hero-slider .feature-list li, .hero-slider .stat-badge, .hero-slider .feature-card h5, .hero-slider .feature-card p {
            color: #eaf5ff !important;
            text-shadow: 0 2px 12px rgba(36,124,230,.17), 0px 1px 5px rgba(27,55,84,0.11);
        }
        .hero-slider h1 {
            font-size: 2.95rem;
            font-weight: 800;
            background-clip: text;
        }
        .hero-slider h2 {
            font-size: 1.6rem;
            font-weight: 700;
            background-clip: text;
            letter-spacing: -.009em;
        }
        .hero-slider .btn-start {
            background: linear-gradient(90deg, #2262a7 60%, #45B2FE 99%);
            color: #fff;
            border-radius: 30px;
            padding: 13px 38px;
            font-size: 1.14rem;
            font-weight: 600;
            box-shadow: 0 6px 28px rgba(54,125,234,0.14);
            border: none;
            margin-top: 8px;
            transition: background .19s, color .19s, box-shadow .17s;
        }
        .hero-slider .btn-start:hover, .hero-slider .btn-start:focus {
            background: linear-gradient(112deg, #3699ff 55%, #215ebc 100%);
            color: #fff;
            box-shadow: 0 10px 32px rgba(41,124,230,0.22);
        }
        .hero-slider .slide-img {
            width: 100%;
            max-width: 550px;
            height: 400px;
            margin-top: 14px;
            box-shadow: 0 10px 40px #0d6efd, 0 1.2px 3px rgb(187 214 233 / 9%);
            background: none !important;
            transition: box-shadow .17s;
            border-radius: 8px;
            object-fit: contain;
        }
        @media (max-width: 991px) {
            .hero-slider {
                min-height: 440px;
            }
            .hero-slider .slide {
                max-width: 97%;
                min-height: unset;
                height: 100%;
                padding: 25px 8px;
            }
            .hero-slider h1{ font-size: 2.1rem;}
            .hero-slider h2{ font-size: 1.8rem;}
            .hero-slider .feature-card{ padding:20px 7px 20px 7px;}
        }
        /* MOBILE: keep slide heights the same, 3 points/slide, align as slide 3, sign button centered with margin-bottom, no scroll, paddings */
        @media (max-width: 576px){
            .hero-slider {
                min-height: 520px !important;
                max-height: 520px !important;
                height: 520px !important;
                padding-bottom: 54px !important; 
                position: relative !important;
                display: flex !important;
            }
            .hero-slider .slider-container {
                height: 100% !important;
                min-height: 100% !important;
                max-height: 100% !important;
                display: flex !important;
                flex-direction: column;
                justify-content: center;
            }
            .hero-slider .slide,
            .hero-slider .slide.active {
                margin: 0 !important;
                border-radius: 0 !important;
                min-height: 100% !important;
                max-height: 100% !important;
                height: 100% !important;
                display: flex !important;
                flex-direction: column;
                justify-content: flex-start !important;
                align-items: stretch !important;
                position: absolute !important;
                top: 0; left: 0; right: 0; bottom: 0;
                overflow-y: unset !important;
            }
            .hero-slider .container-homepage,
            .hero-slider .container-homepage .row,
            .hero-slider .container-homepage [class*="col-"] {
                height: 100% !important;
                min-height: 0 !important;
                max-height: 100% !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
                display: flex !important;
                flex-direction: column;
                justify-content: flex-start !important;
                align-items: flex-start !important;
            }
            .hero-slider .image-section,
            .hero-slider .slide-img {
                display: none !important;
            }
            .slide .col-lg-6, .slide .col-md-12, .slide2-content-mobile-fix {
                justify-content: flex-start !important;
                align-items: flex-start !important;
                display: flex !important;
                flex-direction: column !important;
                text-align: left !important;
                width: 100% !important;
                margin: 0 !important;
                padding-left: 15px !important;
                padding-right: 8px !important;
                height: fit-content !important;
                max-width: 100vw !important;
                overflow: unset !important;
            }
            .hero-slider h1, .hero-slider h2 {
                font-size: 1.8rem !important;
                font-weight: 700 !important;
                line-height: 1.33 !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                width: 100% !important;
                padding-left: 15px !important;
                text-align: left !important;
            }
            .hero-slider .feature-list,
            .hero-slider .feature-list li,
            .hero-slider .feature-card,
            .hero-slider .slide2-main-title,
            .hero-slider .responsive-slide-title,
            .hero-slider .responsive-slide-desc,
            .hero-slider .responsive-features,
            .hero-slider .slide2-list,
            .hero-slider .slide2-desc {
                width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding-left: 15px !important;
                text-align: left !important;
            }
            .hero-slider .feature-list li,
            .hero-slider .responsive-features li > div,
            .hero-slider .responsive-features li {
                width: 100% !important;
                padding-left: 15px !important;
                text-align: left !important;
            }
            /* Only show first 3 points in each feature list (ul) in mobile */
            .hero-slider .feature-list li:nth-child(n+4),
            .hero-slider .responsive-features li:nth-child(n+4),
            .slide2-list li:nth-child(n+4) {
                display: none !important;
            }
            /* Slide 4: remove gap between li, fix overlap */
            .hero-slider .slide[data-slide="4"] .responsive-features li {
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
                gap: 0 !important;
                border: none !important;
            }
            .hero-slider .slide[data-slide="4"] .responsive-features {
                margin-bottom: 12px !important;
                gap: 0 !important;
                padding: 0 !important;
            }
            /* Hide <br> in h2 on 4th slide/mobile only */
            .hero-slider .slide[data-slide="4"] h2 br {
                display: none !important;
            }
            .hero-slider .slide[data-slide="4"] h2 {
                word-break: break-word !important;
            }
            /* Center "Sign Up" button on mobile and add margin-bottom */
            .hero-slider .slide[data-slide="1"] a.btn-gradient-premium {
                display: flex !important;
                align-self: center !important;
                justify-content: center !important;
                margin: 15px auto 28px auto !important;
                min-width: 130px;
                max-width: 180px;
            }
            .hero-slider p {
                padding-left: 15px !important;
            }
            /* Mobile bottom arrows */
            .hero-slider .slider-arrows-mobile-wrapper {
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                position: absolute;
                left: 0;
                bottom: 0;
                width: 100%;
                padding-bottom: 13px;
                z-index: 11;
                background: transparent;
            }
            .hero-slider .slider-arrow {
                position: static !important;
                transform: none !important;
                margin: 0 10px !important;
                width: 44px !important;
                height: 44px !important;
                font-size: 1.4rem !important;
                box-shadow: 0 2px 12px rgba(19,48,84,.11);
                background: rgba(255,255,255,0.12) !important;
                color: #2274bc !important;
                border: none !important;
                opacity: 1 !important;
                backdrop-filter: blur(2px) !important;
                transition: background 0.18s, color 0.18s, box-shadow 0.18s;
                border-radius: 50% !important;
            }
            .hero-slider .slider-arrow:active {
                background: rgba(255,255,255,0.22) !important;
                color: #1262b8 !important;
            }
            .hero-slider .slider-arrow:hover,
            .hero-slider .slider-arrow:focus {
                background: rgba(255,255,255,0.28) !important;
                color: #065db7 !important;
                box-shadow: 0 4px 16px rgba(41,124,230,0.12);
            }
            .slide .col-lg-6:last-child, .slide .col-md-12:last-child {
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
            }
        }
        @media (min-width: 577px){
            .slider-arrows-desktop {
                display: block !important;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10;
                pointer-events: none;
            }
            .slider-arrow {
                background: rgba(255,255,255,0.12);
                color: #2274bc;
                border: none;
                opacity: 0.88;
                position: absolute !important;
                top: 50%;
                transform: translateY(-50%);
                transition: background 0.18s, color 0.18s, box-shadow 0.18s;
                box-shadow: 0 4px 28px rgba(19,48,84,.13);
                border-radius: 50% !important;
                backdrop-filter: blur(2px);
                pointer-events: auto;
            }
            .slider-arrow.left {
                left: 24px !important;
                right: auto !important;
            }
            .slider-arrow.right {
                right: 24px !important;
                left: auto !important;
            }
            .hero-slider .slider-arrows-mobile-wrapper {
                display: none !important;
            }
            .slider-arrow:active {
                background: rgba(255,255,255,0.22) !important;
                color: #1262b8 !important;
            }
            .slider-arrow:hover,
            .slider-arrow:focus {
                background: rgba(255,255,255,0.28) !important;
                color: #065db7 !important;
                box-shadow: 0 7px 28px rgba(41,124,230,0.13);
            }
        }
        .hero-slider .stat-badge {
            display: inline-block;
            background: linear-gradient(90deg,#4797f7 60%, #8ee1ff 100%);
            color: #fff !important;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
            margin-right: 8px;
            padding: 7px 18px;
            box-shadow: 0 1px 10px 0 rgba(99,181,226,0.09);
        }
        .hero-slider .feature-list{
            padding-left: 0;
            margin-top: 18px;
        }
        .hero-slider .feature-list li {
            display: flex;
            align-items: center;
            font-size: 1.09rem;
            color: #dcf3ff !important;
            margin-bottom: 10px;
            gap: 13px;
            font-weight: 500;
        }
        .hero-slider .feature-list li i {
            color: #fff;
            font-size: 1.1rem;
            border-radius: 50%;
            display: inline-flex;
            width: 27px;
            height: 27px;
            align-items: center;
            justify-content: center;
        }
        .hero-slider .feature-card {
            background: linear-gradient(111deg, #3998c9 25%, #4ad5ff 100%);
            border: none;
            transition: box-shadow 0.19s, transform .17s;
        }
        .hero-slider .feature-card:hover {
            transform: translateY(-3px) scale(1.03);
        }
        .hero-slider .feature-card i {
            background: linear-gradient(140deg, #32a3f2 70%, #2274c7 100%);
            color: #fff !important;
            border-radius: 50%;
            padding: 15px 0 15px 0;
            width: 52px;
            height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem !important;
            margin-bottom: 12px;
            box-shadow: 0 2px 16px 0 rgba(73,166,248,.11);
        }
        .hero-slider .feature-card h5 {
            font-size: 1.16rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #f3faff !important;
            text-shadow: 0 2px 8px rgba(21, 112, 176, .13);
        }
        .hero-slider .feature-card p {
            color: #e2f9ff !important;
            font-size: 1.03rem;
            font-weight: 500;
        }
        .hero-slider.hide-controllers .slider-arrow {
            display: none !important;
        }
        @media (min-width: 992px) {
            .slide[data-slide="2"] .col-lg-6,
            .slide[data-slide="3"] .col-lg-6,
            .slide[data-slide="4"] .col-lg-6 {
                align-items: flex-start !important;
                text-align: left !important;
            }
            .slide[data-slide="2"] .slide2-content-mobile-fix,
            .slide[data-slide="3"] .col-lg-6,
            .slide[data-slide="4"] .col-lg-6 {
                text-align: left !important;
                align-items: flex-start !important;
            }
            .slide[data-slide="2"] h1,
            .slide[data-slide="2"] h2,
            .slide[data-slide="2"] p,
            .slide[data-slide="2"] ul,
            .slide[data-slide="2"] li,
            .slide[data-slide="3"] h1,
            .slide[data-slide="3"] h2,
            .slide[data-slide="3"] p,
            .slide[data-slide="3"] ul,
            .slide[data-slide="3"] li,
            .slide[data-slide="4"] h1,
            .slide[data-slide="4"] h2,
            .slide[data-slide="4"] p,
            .slide[data-slide="4"] ul,
            .slide[data-slide="4"] li {
                text-align: left !important;
            }
            .slide[data-slide="4"] .col-lg-6 {
                justify-content: flex-start !important;
            }
        }
        @media (min-width: 768px) and (max-width: 991px) {
            .slide[data-slide="2"] .col-md-12,
            .slide[data-slide="3"] .col-md-12,
            .slide[data-slide="4"] .col-md-12 {
                align-items: flex-start !important;
                text-align: left !important;
            }
            .slide[data-slide="2"] .slide2-content-mobile-fix,
            .slide[data-slide="3"] .col-md-12,
            .slide[data-slide="4"] .col-md-12 {
                text-align: left !important;
                align-items: flex-start !important;
            }
            .slide[data-slide="2"] h1,
            .slide[data-slide="2"] h2,
            .slide[data-slide="2"] p,
            .slide[data-slide="2"] ul,
            .slide[data-slide="2"] li,
            .slide[data-slide="3"] h1,
            .slide[data-slide="3"] h2,
            .slide[data-slide="3"] p,
            .slide[data-slide="3"] ul,
            .slide[data-slide="3"] li,
            .slide[data-slide="4"] h1,
            .slide[data-slide="4"] h2,
            .slide[data-slide="4"] p,
            .slide[data-slide="4"] ul,
            .slide[data-slide="4"] li {
                text-align: left !important;
            }
        }
    </style>
    <div class="hero-slider">
        <!-- Hero video background -->
        <div style="position: absolute;top:0;left:0;right:0;bottom:0;width:100%;height:100%;overflow:hidden;">
            <img src="{{ asset('assets/website/img/herosection.PNG') }}" 
                alt="Hero Section Background"
                class="hero-slider__video-bg"
                style="width:100%;height:100%;display:block;background:none;object-fit:unset;object-position:unset;image-rendering:auto;">
        </div>
        <!-- Slider arrows for desktop, positioned left/right center edges of the slider screen -->
        <div class="slider-arrows-desktop">
            <button class="slider-arrow left" type="button" aria-label="Previous Slide">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slider-arrow right" type="button" aria-label="Next Slide">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <div class="slider-container">
            <!-- Slide 1 -->
            <div class="slide active" data-slide="1">
                <div class="container container-homepage py-4 h-100" style="height:100%">
                    <div class="row align-items-center h-100" style="height:100%">
                        <div class="col-lg-6 col-md-12 text-center text-lg-start px-4 mb-5 mb-lg-0 d-flex flex-column justify-content-center justify-content-lg-start align-items-lg-start align-items-center" style="z-index:2;">
                            <h2 style="font-size:2rem; font-weight:800; color:#f7fbff; text-shadow:0 3px 24px #126eb516, 0 1px 3px rgba(59,133,215,0.13); letter-spacing:-0.5px; margin-bottom:1.1rem; line-height:1.28;">
                                Next-Level Logistics<br>
                                <span style="color:#fcfeff;font-weight:700; background: linear-gradient(93deg, #6fd3ff 60%, #3aaaff 110%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Seamless &amp; Reliable Deliveries</span>
                            </h2>
                            <p class="mb-3" style="font-size:1.15rem; color:#e0f6fe; font-weight:500; line-height:1.64; max-width:550px; margin:0 auto 26px auto;">
                                <i class="bi bi-globe-americas me-2" style="color:#56d7fa;font-size:1.25rem;"></i>
                                Premium shipping network—<span style="color:#7fcfff;font-weight:600;">smarter courier connect</span> for speed, safety, and trust.
                            </p>
                            <ul class="feature-list list-unstyled mt-2 mb-4" style="font-size:1.1rem; color:#daf6ff; font-weight:500; gap:11px;">
                                <li>
                                    <i class="bi bi-speedometer2" style="background:linear-gradient(147deg,#56d7fa 64%,#3483b7 100%);color:#fff; font-size:1.18rem; padding:5px; border-radius:50%;"></i>
                                    <span style="color:#c9eaff;">Smart courier choice for <b>faster delivery</b></span>
                                </li>
                                <li>
                                    <i class="bi bi-shield-lock-fill" style="background:linear-gradient(147deg,#56d7fa 64%,#3483b7 100%);color:#fff; font-size:1.18rem; padding:5px; border-radius:50%;"></i>
                                    <span style="color:#c9eaff;">ShipXpeed protects every shipment</span>
                                </li>
                                <li>
                                    <i class="bi bi-bar-chart-fill" style="background:linear-gradient(147deg,#56d7fa 64%,#3483b7 100%);color:#fff; font-size:1.18rem; padding:5px; border-radius:50%;"></i>
                                    <span style="color:#c9eaff;">Scalable, reliable shipping for any need</span>
                                </li>
                                <!-- removed 4th li for mobile (handled by css above) -->
                            </ul>
                            <a href="{{ route('seller.login') }}"
                               class="btn btn-gradient-premium shadow-sm mt-2 px-4 py-2 d-inline-flex align-items-center justify-content-center"
                               style="
                                    font-size: 1.13rem;
                                    font-weight: 700;
                                    border-radius: 9px;
                                    border: none;
                                    background: linear-gradient(92deg, #175cae 48%, #77d8ff 140%);
                                    color: #fff;
                                    box-shadow: 0 4px 24px rgba(35,137,207,0.12);
                                    transition: background 0.17s, box-shadow 0.17s, transform 0.11s;
                                    letter-spacing: 0.01em;
                                    min-width: 130px;
                                    max-width: 180px;
                                    width: auto;
                                   "
                               onmouseover="this.style.background='linear-gradient(92deg,#2175d3 55%, #45c8ff 120%)';this.style.transform='translateY(-1px) scale(1.025)';this.style.boxShadow='0 8px 34px rgba(39,147,229,0.18)';"
                               onmouseout="this.style.background='linear-gradient(92deg,#175cae 48%, #77d8ff 140%)';this.style.transform='none';this.style.boxShadow='0 4px 24px rgba(35,137,207,0.12)';"
                            >
                                <span class="me-2 d-flex align-items-center">
                                    <i class="bi bi-person-plus" style="font-size:1.18rem;color:#ffe89d;"></i>
                                </span>
                                <span href="{{ route('seller.login') }}">Sign Up</span>
                            </a>
                        </div>
                        <div class="col-lg-6 col-md-12 d-flex justify-content-center image-section align-items-center">
                            <img src="{{ asset('assets/website/img/homeimg.png') }}" alt="ShipXpeed Dashboard Preview"
                                class="img-fluid slide-img" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slide 2 -->
            <div class="slide" data-slide="2">
                <div class="container container-homepage py-1 h-100" style="height:100%;">
                    <div class="row align-items-stretch h-100" style="height:100%;">
                        <div class="col-lg-6 col-md-12 px-3 px-md-4 mb-4 mb-lg-0 d-flex flex-column justify-content-center align-items-center align-items-lg-start text-center text-lg-start slide2-content-mobile-fix"
                             style="padding-top:1.2rem;padding-bottom:1.2rem;">
                           
                            <h2
                                class="w-100 slide2-main-title"
                                style="font-size:2.1rem; font-weight:800; letter-spacing:-1px; background: linear-gradient(90deg,#dcefff 70%, #3da8e6 100%); 
                                -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 1.2rem;">
                                Track Your <span style="color:#fcfeff;font-weight:700; background: linear-gradient(93deg, #6fd3ff 60%, #3aaaff 110%);
                                -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">Deliveries <br> </span> in Real Time
                            </h2>
                            <p class="mb-4 w-100 slide2-desc" style="font-size:1.17rem; color:#bae0ff !important; font-weight: 500; line-height:1.64;max-width:550px; margin:0 auto 26px auto;">
                                <i class="fa-regular fa-map me-2"  style="color:#56d7fa;font-size:1.25rem;"></i>
                                Stay informed at every step &mdash; from dispatch to doorstep with complete visibility.
                            </p>
                            <ul class="list-unstyled mb-4 mt-2 w-100 slide2-list"
                                style="font-size:1.08rem; color:#e4f6ff; font-weight:500; line-height:1.7;">
                                <li class="mb-3 d-flex align-items-start">
                                    <span class="flex-shrink-0 me-3">
                                        <i class="fas fa-location-arrow me-2 icon-hide-mobile" style="color:#39b7ff;"></i>
                                    </span>
                                    <div>Live tracking with accurate location updates</div>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <span class="flex-shrink-0 me-3">
                                        <i class="fas fa-clipboard-check me-2 icon-hide-mobile" style="color:#47ffdd"></i>
                                    </span>
                                    <div>Instant alerts for every shipment milestone</div>
                                  
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <span class="flex-shrink-0 me-2"><i class="fas fa-eye me-2 icon-hide-mobile" style="color:#f8ed62;"></i></span>
                                    <div>Transparent delivery progress insights</div>
                                   
                                </li>
                                <!-- removed 4th li for mobile (handled by css above) -->
                            </ul>
                        </div>
                        <div class="col-lg-6 col-md-12 d-flex justify-content-center image-section align-items-center mb-4 mb-lg-0">
                            <img src="{{ asset('assets/website/img/2sliderr.png') }}" alt="Global Shipping"
                                class="img-fluid slide-img" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slide 3 -->
            <div class="slide" data-slide="3">
                <div class="container container-homepage responsive-container py-4 h-100" style="height:100%">
                    <div class="row align-items-center justify-content-between py-4 h-100" style="height:100%">
                        <div class="col-lg-6 col-md-12 px-4 mb-5 mb-lg-0 d-flex flex-column justify-content-center align-items-lg-start"
                             style="align-items: flex-start !important;">
                            <div class="p-0 p-lg-2 w-100">
                                <h2 class="fw-bolder mb-3" style="font-size:2.1rem;letter-spacing:-1.5px;color:#183153;">
                                    Dedicated Support with <br> <span style="color:#3da8e6;">Key Account Manager</span>
                                </h2>
                                <p class="mb-4" style="font-size:1.17rem; color:#42618c; font-weight:500;line-height:1.6;max-width:550px; margin:0 auto 26px auto;">
                                    <i class="fa-solid fa-arrow-trend-up me-2" style="color:#56d7fa;font-size:1.25rem;"></i>
                                    Get personalized support to streamline operations and improve delivery performance.
                                </p>
                                <ul class="feature-list list-unstyled" style="font-size:1.08rem;">
                                    <li class="mb-3 d-flex align-items-start">
                                        <span class="flex-shrink-0 me-3">
                                            <i class="fas fa-user-check" style="color:#06c48c; font-size:1.15rem;"></i>
                                        </span>
                                        <div>Single point of contact for <strong>quick assistance</strong>.</div>
                                    </li>
                                    <li class="mb-3 d-flex align-items-start">
                                        <span class="flex-shrink-0 me-3">
                                            <i class="fas fa-eye" style="color:#41baff; font-size:1.15rem;"></i>
                                        </span>
                                        <div>
                                            <strong>Proactive shipment monitoring</strong>.
                                        </div>
                                    </li>
                                    <li class="mb-3 d-flex align-items-start"> 
                                        <span class="flex-shrink-0 me-3">
                                            <i class="fa-solid fa-gauge-simple-high"  style="font-size:1.15rem;"></i>
                                        </span>
                                        <div>
                                            <strong>Faster issue resolution and coordination</strong>.
                                        </div>
                                    </li>
                                    <!-- removed 4th li for mobile (handled by css above) -->
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12 d-flex justify-content-center image-section align-items-center">
                            <img src="{{ asset('https://shipxpeed.com/assets/website/img/newSlide3.PNG') }}" alt="Dashboard Preview"
                                class="img-fluid slide-img" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slide 4 -->
            <div class="slide" data-slide="4">
                <div class="container container-homepage py-4 h-100" style="height:100%;">
                    <div class="row align-items-center h-100 flex-column-reverse flex-lg-row" style="height:100%;">
                        <div class="col-lg-6 col-md-12 px-3 px-sm-4 mb-4 mb-lg-0 d-flex flex-column justify-content-center align-items-lg-start"
                             style="align-items: flex-start !important;">
                            <h2 class="fw-bold mb-3">
                                <span class="d-none d-sm-inline">
                                    Accelerate Your Cash Flow with <br> <span style="font-weight:800; color:#244ea1;">Early COD</span>
                                </span>
                                <span class="d-inline d-sm-none">
                                    Accelerate Your Cash Flow with <span style="font-weight:800; color:#244ea1;">Early COD</span>
                                </span>
                            </h2>
                            <p style="font-size:1.17rem; color:#42618c; font-weight:500;line-height:1.6;max-width:550px; margin:0 auto 26px auto;">
                                <i class="fa-solid fa-business-time me-2" style="color:#56d7fa;font-size:1.25rem;"></i>
                                Boost your business with faster payouts and smarter financial flow.
                            </p>
                            <ul class="feature-list list-unstyled responsive-features"
                                style="font-size:1.08rem; color:#293c5f;">
                                <li class="d-flex align-items-start">
                                    <span class="me-3" style="color:#27d17a;">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                    <div>
                                        <strong>D+1</strong> early COD settlement for faster business cash flow
                                    </div>
                                </li>
                                <li class="d-flex align-items-start">
                                    <span class="me-3" style="color:#1fa5cb;">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                    <div>
                                        Low charges starting from 0.69% with benefits
                                    </div>
                                </li>
                                <li class="d-flex align-items-start">
                                    <span class="me-3" style="color:#efcb36;">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                    <div>
                                        Improved cash rotation for consistent and business growth
                                    </div>
                                </li>
                                <!-- removed 4th li for mobile (handled by css above) -->
                            </ul>
                        </div>
                        <div class="col-lg-6 col-md-12 d-flex justify-content-center image-section align-items-center mb-4 mb-lg-0">
                            <img src="{{ asset('https://shipxpeed.com/assets/website/img/slider4.png') }}" alt="Dashboard Preview"
                                class="img-fluid slide-img" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Slider arrows for mobile: only right arrow, centered and not overlapping content -->
        <div class="slider-arrows-mobile-wrapper" 
            style="display:none; justify-content:center; align-items:center; position:absolute; left:0; right:0; bottom:20px; z-index:100; pointer-events:none;">
            <button class="slider-arrow right" type="button" aria-label="Next Slide"
                style="
                    display:block;
                    margin: 0 auto;
                    width: 48px;
                    height: 48px;
                    background: rgba(255,255,255,0.17);
                    color: #2274bc;
                    border: none;
                    border-radius: 50%;
                    box-shadow: 0 6px 24px rgba(39,147,237,0.11);
                    backdrop-filter: blur(2px);
                    font-size: 1.8rem;
                    align-items: center;
                    justify-content: center;
                    align-self: center;
                    pointer-events:auto;
                ">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <!-- Slider navigation bullets (desktop only; hidden on mobile via JS) -->
        <div class="slider-nav-bullets" style="position:absolute;left:50%;bottom:88px;transform:translateX(-50%);display:flex;gap:13px;z-index:10;"></div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.hero-slider .slide');
            let currentSlide = 0;
            let autoSlideInterval = null;
            const AUTO_SLIDE_TIME = 4000; // Autoforward speed (faster)

            const slider = document.querySelector('.hero-slider');
            const leftArrows = slider.querySelectorAll('.slider-arrow.left');
            const rightArrows = slider.querySelectorAll('.slider-arrow.right');
            const sliderArrowsDesktop = slider.querySelector('.slider-arrows-desktop');
            const sliderArrowsMobile = slider.querySelector('.slider-arrows-mobile-wrapper');
            const navBulletsContainer = slider.querySelector('.slider-nav-bullets');

            function isMobile() {
                return window.innerWidth <= 576;
            }

            // NAVIGATOR BULLETS (desktop only)
            function renderNavBullets() {
                if (isMobile()) { 
                    navBulletsContainer.style.display = 'none';
                    return;
                }
                navBulletsContainer.innerHTML = '';
                navBulletsContainer.style.display = 'flex';
                slides.forEach((_, idx) => {
                    const bullet = document.createElement('button');
                    bullet.type = "button";
                    bullet.className = "slider-bullet";
                    bullet.setAttribute("aria-label", "Go to slide " + (idx + 1));
                    bullet.style.cssText = `
                        width:12px;height:12px;appearance:none;border-radius:50%;
                        border:2px solid #49adeb;background:#e4f6ff;
                        box-shadow:0 2px 8px #0b214220;
                        transition:background 0.23s,border-color 0.22s;
                        cursor:pointer;padding:0;margin:0;
                        outline:none;display:block;
                    `;
                    if(idx === currentSlide) {
                        bullet.style.background = "#2ca2e4";
                        bullet.style.borderColor = "#44b6ff";
                    }
                    bullet.addEventListener('click', () => {
                        goToSlide(idx);
                        resetAutoSlide();
                    });
                    navBulletsContainer.appendChild(bullet);
                });
            }

            function showSlide(index) {
                slides.forEach((slide, idx) => {
                    if (idx === index) {
                        slide.classList.add('active');
                    } else {
                        slide.classList.remove('active');
                    }
                });
                renderNavBullets();
            }

            function goToSlide(idx) {
                currentSlide = idx;
                showSlide(currentSlide);
            }

            function nextSlide(manual=false) {
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide(currentSlide);
                if (manual) resetAutoSlide();
            }
            function prevSlide(manual=false) {
                currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                showSlide(currentSlide);
                if (manual) resetAutoSlide();
            }

            // Attach handlers only to visible arrows
            function updateArrowsHandlers() {
                // Remove old click handlers
                leftArrows.forEach(btn => btn.replaceWith(btn.cloneNode(true)));
                rightArrows.forEach(btn => btn.replaceWith(btn.cloneNode(true)));

                // Re-query since replaced
                const leftArrowsUpdated = slider.querySelectorAll('.slider-arrow.left');
                const rightArrowsUpdated = slider.querySelectorAll('.slider-arrow.right');

                // Desktop: both arrows
                leftArrowsUpdated.forEach(btn => btn.addEventListener('click', () => prevSlide(true)));
                rightArrowsUpdated.forEach(btn => btn.addEventListener('click', () => nextSlide(true)));

                // Mobile: only right (next), left (prev) hidden by CSS and not present
                if (isMobile() && sliderArrowsMobile) {
                    sliderArrowsMobile.querySelectorAll('.slider-arrow').forEach(btn => {
                        if (btn.classList.contains('left')) btn.style.display = 'none';
                        if (btn.classList.contains('right')) btn.style.display = 'block';
                    });
                }
            }

            // Move nav bullets off mobile and ensure desktop position
            function handleBulletsDisplay() {
                if(isMobile()) {
                    navBulletsContainer.style.display = 'none';
                } else {
                    navBulletsContainer.style.display = 'flex';
                }
            }

            updateArrowsHandlers();

            document.addEventListener('keydown', function(e) {
                if (e.key === "ArrowLeft" && !isMobile()) prevSlide(true);
                if (e.key === "ArrowRight") nextSlide(true);
            });

            // AUTOPLAY
            function startAutoSlide() {
                if (autoSlideInterval) clearInterval(autoSlideInterval);
                autoSlideInterval = setInterval(() => {
                    nextSlide(false);
                }, AUTO_SLIDE_TIME);
            }
            function stopAutoSlide() {
                if (autoSlideInterval) clearInterval(autoSlideInterval);
                autoSlideInterval = null;
            }
            function resetAutoSlide() {
                stopAutoSlide();
                startAutoSlide();
            }

            // Pause on hover/focus (for desktop)
            slider.addEventListener('mouseenter', stopAutoSlide);
            slider.addEventListener('mouseleave', startAutoSlide);
            slider.addEventListener('focusin', stopAutoSlide);
            slider.addEventListener('focusout', startAutoSlide);

            // Touch swipe for mobile
            let startX = null;
            slider.addEventListener('touchstart', function(e){
                if(e.touches.length === 1) {
                    startX = e.touches[0].clientX;
                }
            });
            slider.addEventListener('touchend', function(e){
                if(startX !== null && e.changedTouches.length === 1){
                    let diff = e.changedTouches[0].clientX - startX;
                    if(diff < -40) { nextSlide(true); }
                    else if(diff > 40) { prevSlide(true); }
                }
                startX = null;
            });

            showSlide(currentSlide);
            startAutoSlide();

            const heroSlider = document.querySelector('.hero-slider');
            function checkSliderInView() {
                if (!heroSlider) return;
                const rect = heroSlider.getBoundingClientRect();
                if (rect.bottom < 80 || rect.top > (window.innerHeight-80)) {
                    heroSlider.classList.add('hide-controllers');
                } else {
                    heroSlider.classList.remove('hide-controllers');
                }
            }
            window.addEventListener('scroll', checkSliderInView);
            window.addEventListener('resize', checkSliderInView);

            function handleResizeAndArrows() {
                // Desktop: left/right center. Mobile: center bottom, only next arrow. No overlap.
                if (isMobile()) {
                    if(sliderArrowsDesktop) sliderArrowsDesktop.style.display = 'none';
                    if(sliderArrowsMobile) {
                        sliderArrowsMobile.style.display = 'flex';
                        sliderArrowsMobile.style.justifyContent = "center";
                        sliderArrowsMobile.style.alignItems = "center";
                        sliderArrowsMobile.style.left = "0";
                        sliderArrowsMobile.style.right = "0";
                        sliderArrowsMobile.style.bottom = "20px";
                        sliderArrowsMobile.style.position = "absolute";
                        sliderArrowsMobile.style.width = "100vw";
                        sliderArrowsMobile.style.pointerEvents = "none";
                        sliderArrowsMobile.querySelectorAll('.slider-arrow').forEach(btn => {
                            if (btn.classList.contains('left')) {
                                btn.style.display = 'none';
                            } else if (btn.classList.contains('right')) {
                                btn.style.display = 'block';
                                btn.style.margin = '0 auto';
                                btn.style.position = 'relative';
                                btn.style.pointerEvents = 'auto';
                            }
                        });
                    }
                } else {
                    if(sliderArrowsDesktop) sliderArrowsDesktop.style.display = '';
                    if(sliderArrowsMobile) sliderArrowsMobile.style.display = 'none';
                    sliderArrowsDesktop.querySelectorAll('.slider-arrow.left').forEach(btn => {
                        btn.style.left = "24px";
                        btn.style.right = "auto";
                        btn.style.top = "50%";
                        btn.style.transform = "translateY(-50%)";
                        btn.style.position = "absolute";
                        btn.style.pointerEvents = "auto";
                        btn.style.display = "block";
                    });
                    sliderArrowsDesktop.querySelectorAll('.slider-arrow.right').forEach(btn => {
                        btn.style.right = "24px";
                        btn.style.left = "auto";
                        btn.style.top = "50%";
                        btn.style.transform = "translateY(-50%)";
                        btn.style.position = "absolute";
                        btn.style.pointerEvents = "auto";
                        btn.style.display = "block";
                    });
                }
                updateArrowsHandlers();
                handleBulletsDisplay();
            }
            window.addEventListener('resize', handleResizeAndArrows);
            handleResizeAndArrows();
            checkSliderInView();
        });
    </script>

<style>
        /* Animated premium clients section with minimal logo gap, slow smooth animation */
        #clients.premium-brands-section {
            background: linear-gradient(120deg, #fafdff 60%, #e8f1f8 100%);
            padding: 60px 0 48px 0;
            position: relative;
            overflow: hidden;
        }
        .brands-section-header {
            text-align: center;
            margin-bottom: 48px;
            position: relative;
        }
        .brands-section-header h2 {
            font-size: 2.35rem;
            font-weight: 800;
            color: #155cb7;
            display: inline-block;
            background: linear-gradient(94deg, #237fc8 50%, #68c5fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            padding: 0 22px;
            margin-bottom: 10px;
            letter-spacing: -.012em;
        }
        .brands-section-header p {
            color: #57677c;
            font-size: 1.13rem;
            font-weight: 500;
            letter-spacing: .01em;
            margin-bottom: 0;
        }
        .premium-marquee {
            width: 100%;
            overflow: hidden;
            position: relative;
            background: transparent;
            padding: 12px 0;
        }
        .premium-marquee-track {
            display: flex;
            align-items: center;
            gap: 16px; /* Increased gap to accommodate larger logos */
            width: max-content;
            animation: premium-scroll-slow 64s linear infinite;
            transition: transform 0.4s cubic-bezier(.22,1,.36,1);
            will-change: transform;
        }
        .premium-marquee-track:hover {
            animation-play-state: paused;
        }
        .premium-logo-slide {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 178px;
            max-width: 230px;
            filter: grayscale(45%) brightness(1.02) contrast(1.08);
            opacity: .83;
            transition: filter .28s, opacity .28s, transform .2s cubic-bezier(.16,1,.32,1);
            padding: 0 5px;
        }
        .premium-logo-slide img {
            max-height: 84px;
            width: auto;
            max-width: 178px;
            transition: box-shadow .24s, transform .22s cubic-bezier(.38,1.1,.72,1.1), filter .22s;
            border-radius: 9px;
            box-shadow: 0 3px 10px rgba(38, 93, 187, 0.06);
            background: #f7fafc;
        }
        .premium-logo-slide:hover, .premium-logo-slide:focus-visible {
            filter: none;
            opacity: 1;
            z-index: 2;
        }
        .premium-logo-slide:hover img {
            box-shadow: 0 6px 24px 0 rgba(30,100,232,0.12), 0 1.5px 3.5px rgba(30,100,232,0.05);
            transform: scale(1.07) translateY(-3px);
            filter: saturate(1.04) brightness(1.08) contrast(1.17);
        }
        @keyframes premium-scroll-slow {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        @media (max-width: 992px) {
            .brands-section-header h2 { font-size: 2rem; }
            .premium-logo-slide img { max-width: 132px; max-height: 56px;}
            .premium-logo-slide { min-width: 94px; max-width: 146px;}
            .premium-marquee-track { gap: 11px; }
        }
        @media (max-width: 650px) {
            .brands-section-header h2 { font-size: 1.23rem; }
            .brands-section-header p { font-size: .94rem; }
            .premium-marquee-track { gap: 7px; }
            .premium-logo-slide { min-width: 72px; max-width: 103px; }
            .premium-logo-slide img { max-width: 62px; max-height: 31px;}
        }
    </style>
    <section id="clients" class="premium-brands-section">
        <div class="container">
            <div class="brands-section-header">
                <h2>
                    <i class="fas fa-gem me-2" style="color:#276fcf; opacity:0.85; font-size:1.07em; vertical-align:middle;"></i>
                    Trusted by Leading Brands
                </h2>
                <p>More than <span style="color:#3383dc;font-weight:700;">2,100+</span> brands choose us for a premium shipping experience</p>
            </div>
            
            <div class="premium-marquee">
                <div class="premium-marquee-track" aria-label="List of trusted brands/logos" tabindex="0"
                     onmouseover="this.style.animationPlayState='paused'"
                     onmouseout="this.style.animationPlayState='running'">
                    <!-- @foreach($landingbrands as $landingbrand)
                        <div class="premium-logo-slide">
                            <img src="{{ Helper::showImage($landingbrand->image, true) }}" alt="Brand Logo" loading="lazy">
                        </div>
                    @endforeach
                    @foreach($landingbrands as $landingbrand)
                      
                        <div class="premium-logo-slide">
                            <img src="{{ Helper::showImage($landingbrand->image, true) }}" alt="Brand Logo" loading="lazy">
                        </div>
                    @endforeach -->
                    @foreach($landingbrands as $landingbrand)
                        @php
                            $imageUrl = Helper::showImage($landingbrand->image, true);
                        @endphp

                        @if(!str_contains($imageUrl, '1750648620_3757.jpeg'))
                            <div class="premium-logo-slide">
                                <img src="{{ $imageUrl }}" alt="Brand Logo" loading="lazy">
                            </div>
                        @endif
                    @endforeach

                    @foreach($landingbrands as $landingbrand)
                        @php
                            $imageUrl = Helper::showImage($landingbrand->image, true);
                        @endphp

                        @if(!str_contains($imageUrl, '1750648620_3757.jpeg'))
                            <div class="premium-logo-slide">
                                <img src="{{ $imageUrl }}" alt="Brand Logo" loading="lazy">
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </section>


    <!-- Unified Hero & COD Remittance Section with Single Extended Background, including Services under one background -->
    <section id="hero-cod" class="hero-cod section premium-hero-section position-relative overflow-hidden extended-hero-bg" style="padding-bottom:0; margin-bottom:0;">
        <!-- Responsive background: dynamic height for desktop, mobile -->
        <div 
            class="hero-bg-wrapper position-absolute top-0 start-0 end-0 overflow-hidden"
            id="heroBgResponsiveHeight"
            style="
                z-index:1; pointer-events:none;
                width: 100%;
                min-height:1400px;
                height: 1900px;
                transition: height 0.3s, min-height 0.3s;
                "
            >
            <img src="{{ asset('assets/website/img/herosection.PNG') }}"
                 alt=""
                 class="w-100 h-100 object-fit-cover opacity-80"
                 style="object-fit: cover; min-height:1500px; max-height:100%; object-position:top;">
        </div>

        <div class="container position-relative" style="z-index:2;">
            <style>
                /* --- Responsive Styles for Hero & Feature Cards Section --- */
                .premium-feature-row {
                    margin-top: 0.35rem !important; /* Reduced gap after heading */
                }
                .feature-premium-card {
                    min-height: 230px;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    justify-content: flex-start;
                    align-items: stretch;
                    padding: 1.5rem 1.2rem 1.2rem 1.2rem;
                    background: #fff;
                }
                .feature-premium-card .premium-icon {
                    width: 56px; height: 56px; min-width: 38px; min-height: 38px;
                }
                .feature-premium-card h4 {
                    font-size: 1.13rem;
                    margin: 0.6rem 0 0.3rem 0;
                    font-weight: 600;
                    word-break: break-word;
                }
                .feature-premium-card .description {
                    font-size: 0.96rem;
                    color: #646e7a;
                    margin-bottom: 0;
                    margin-top: 0;
                    flex-grow: 1;
                    line-height: 1.35;
                    min-height: 38px;
                    max-height: 2.8em;
                    overflow: hidden;
                    display: block;
                }
                .feature-premium-card-col {
                    display: flex;
                }
                @media (max-width: 1199.98px) {
                    .feature-premium-card {
                        min-height: 210px;
                        padding: 1.25rem 1rem 1rem 1rem;
                    }
                    .feature-premium-card h4 {
                        font-size: 1.05rem;
                    }
                }
                @media (max-width: 991.98px) {
                    .feature-premium-card {
                        padding: 1rem 0.7rem;
                        min-height: 190px;
                    }
                    .feature-premium-card h4 {
                        font-size: 1rem;
                    }
                }
                @media (max-width: 767.98px) {
                    .premium-feature-row {
                        margin-top: 0.1rem !important;
                    }
                    .feature-premium-card {
                        padding: 0.62rem 0.35rem 0.7rem 0.65rem;
                        min-height: 165px;
                        font-size: 0.91rem;
                    }
                    .feature-premium-card h4 {
                        font-size: 0.97rem;
                        margin: 0.38rem 0 0.26rem 0;
                    }
                    .feature-premium-card .description {
                        font-size: 0.92rem;
                        line-height: 1.27;
                        min-height: 33px;
                        max-height: 2.6em;
                    }
                }
                @media (max-width: 575.98px) {
                    .feature-premium-card {
                        min-height: 132px;
                    }
                    .feature-premium-card .premium-icon {
                        width: 33px; height: 33px;
                    }
                }
                /* Always ensure text stays in box and avoid overflow for smaller screens */
                .feature-premium-card h4,
                .feature-premium-card .description {
                    overflow-wrap: break-word;
                    word-break: break-word;
                }
            </style>
            <!-- Hero Block -->
            <div class="row justify-content-center align-items-center py-4 pb-2">
                <div class="col-xl-8 col-lg-10 text-center">
                    <h1 class="fw-bold display-5 hero-section-title-main" style="color:white; margin-bottom: .41rem;">
                        Why Choose <span class="text-shipxpeed" style="color: #b0d2fd;">Shipxpeed</span> As Your
                        <br class="d-none d-md-inline">
                        <span class="hero-section-title-sub" style="text-gradient: linear-gradient(92deg, #dadee5 0%, #b1c2cf 62%, #d6f2f7 100%);">
                            Courier Aggregator Partner?
                        </span>
                    </h1>
                </div>
            </div>
            <!-- Features Section - Responsive -->
            <div class="row gx-2 gy-3 premium-feature-row justify-content-center">
                <div class="col-12 col-sm-6 col-md-6 col-lg-3 feature-premium-card-col aos-init aos-animate" data-aos="zoom-out" data-aos-delay="100">
                    <div class="icon-box feature-premium-card shadow-sm rounded-4 w-100 mx-auto position-relative responsive-premium-card">
                        <div class="icon premium-icon d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                             style="background: linear-gradient(135deg, #e2f0ff 55%, #fff 100%);">
                            <i class="bi bi-currency-rupee" style="font-size:1.7rem; color:#3385ec"></i>
                        </div>
                        <h4 class="fw-semibold text-dark">
                            <span class="text-gradient" style="color:#2b994c;">Just ₹24 Starting</span>
                        </h4>
                        <p class="description text-secondary mb-0">
                            Unbeatable base rate – ship affordably with zero compromises, ideal for growing eCommerce brands.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-3 feature-premium-card-col aos-init aos-animate" data-aos="zoom-out" data-aos-delay="200">
                    <div class="icon-box feature-premium-card shadow-sm rounded-4 w-100 mx-auto position-relative responsive-premium-card">
                        <div class="icon premium-icon d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                             style="background: linear-gradient(135deg, #e4f0e6 55%, #fff 100%);">
                            <i class="bi bi-geo-alt" style="font-size:1.7rem; color:#45b663"></i>
                        </div>
                        <h4 class="fw-semibold text-dark">
                            <span class="text-gradient" style="color:#2b994c;">29,000+ Pincodes</span>
                        </h4>
                        <p class="description text-secondary mb-0">
                            Pan-India coverage – deeply integrated logistics network for business anywhere in India.
                        </p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-6 col-lg-3 feature-premium-card-col aos-init aos-animate" data-aos="zoom-out" data-aos-delay="300">
                    <div class="icon-box feature-premium-card shadow-sm rounded-4 w-100 mx-auto position-relative responsive-premium-card">
                        <div class="icon premium-icon d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                             style="background: linear-gradient(135deg, #faf0dd 55%, #fff 100%);">
                            <i class="bi bi-lightning-charge" style="font-size:1.7rem; color:#ffac3c"></i>
                        </div>
                        <h4 class="fw-semibold text-dark">
                            <span class="text-gradient" style="color:#de9e37;">SDD &amp; NDD Delivery</span>
                        </h4>
                        <p class="description text-secondary mb-0">
                            Same Day &amp; Next Day options – for businesses that prioritize speed and reliability.
                        </p>
                    </div>
                </div>
                <!-- <div class="col-12 col-sm-6 col-md-6 col-lg-3 feature-premium-card-col aos-init aos-animate" data-aos="zoom-out" data-aos-delay="400">
                    <div class="icon-box feature-premium-card shadow-sm rounded-4 w-100 mx-auto position-relative responsive-premium-card">
                        <div class="icon premium-icon d-flex align-items-center justify-content-center rounded-circle shadow-sm"
                             style="background: linear-gradient(135deg, #e0e9fb 55%, #fff 100%);">
                            <i class="bi bi-globe" style="font-size:1.7rem; color:#1b60d2"></i>
                        </div>
                        <h4 class="fw-semibold text-dark">
                            <span class="text-gradient" style="color:#2479dd;">International Reach</span>
                        </h4>
                        <p class="description text-secondary mb-0">
                            Ship to 230+ countries — one platform, borderless eCommerce made effortless.
                        </p>
                    </div>
                </div> -->
            </div>
            <!-- COD Remittance Section Responsive (Unchanged content, but responsive styles are respected) -->
            <div class="cod-codremittance-section-bg w-100 my-5" style="background: none; margin-left: 0; margin-right: 0;">
                <style>
                    @media (max-width: 991.98px) {
                        .cod-bg-mobile-gradient {
                          
                            background-size: cover !important;
                            background-repeat: no-repeat !important;
                            background-position: center center !important;
                        }
                        .cod-mobile-img {
                            display: none !important;
                        }
                        .cod-codremittance-section-bg {
                            margin-top: 0 !important;
                            padding-top: 0 !important;
                            padding-bottom: 0 !important;
                            margin-bottom: 0 !important;
                        }
                        .cod-content {
                            padding-top: 16px !important;
                            padding-bottom: 10px !important;
                        }
                    }
                </style>
                <div class="cod-bg-mobile-gradient w-100" style="padding:0;margin:0;">
                    <div class="row flex-lg-row flex-column-reverse m-0 w-100" style="width:100%;">
                        <div class="col-lg-7 col-md-10 cod-image image-section aos-init aos-animate text-center mb-3 mb-lg-0" data-aos="fade-left" data-aos-delay="100" style="margin-top:10px;">
                            <img class="cod-mobile-img" src="{{ asset('https://shipxpeed.com/assets/website/img/newwonee.PNG') }}" alt="COD Illustration" style="width:100%;height:400px;max-width: 574px;">
                        </div>
                        <div class="col-lg-5 col-md-12 cod-content aos-init aos-animate" data-aos="fade-right" style="padding-top:22px; padding-bottom:18px;margin-top:10px;">
                            <div class="mb-3">
                                <span class="d-inline-flex align-items-center gap-2 px-3 py-2 cod-remittance-badge"
                                      style="background:linear-gradient(96deg,#eef7fe 0%,#9ed1fb 96%,#e4eef9 100%); border-radius:2.5rem;">
                                    <i class="bi bi-cash-coin" style="font-size:1.3rem;color:#eafffa;"></i>
                                    <span class="fw-semibold" style="font-size:1.18rem; letter-spacing:-0.01em; color:#000000;">COD Remittance</span>
                                </span>
                            </div>
                            <h2 class="mb-3 fw-bold cod-codremittance-title" style="font-size:2.15rem; letter-spacing:-.5px; line-height:1.16; color: #f6fafd; text-align:left;">
                                Empowering Your Growth<br>
                                <span class="whitish-gradient" style="font-weight:600;">with Reliable COD Payouts</span>
                            </h2>
                            <p class="fs-5 mb-4" style="
                                max-width:520px;
                                font-size:1.09em;
                                color:#e3f0fa;
                                letter-spacing:-0.01em;
                                font-weight: 400;
                                line-height:1.48;">
                                Experience <span class="fw-semibold" style="color:#d1ebff;">seamless, secure,</span> and <span class="fw-semibold" style="color:#9cffe2;">timely remittance</span> for all your COD orders.<br>
                            </p>
                            <style>
                                .cod-feature-list {
                                    max-width: 520px;
                                    padding-left: 0;
                                }
                                .cod-feature-list li {
                                    display: flex;
                                    flex-direction: row;
                                    align-items: center;
                                    margin-bottom: 0.8rem;
                                    gap: 0.5em;
                                }
                                .cod-feature-list li:last-child {
                                    margin-bottom: 0;
                                }
                                .cod-feature-icon {
                                    min-width: 2em;
                                    font-size: 1.15rem;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                }
                                .cod-feature-text {
                                    color: #e1eff7;
                                    font-size: 1rem;
                                    display: flex;
                                    align-items: center;
                                    margin-left: 0.4em;
                                }
                                /* Tablet adjustments */
                                @media (max-width: 991.98px) {
                                    .cod-feature-list {
                                        max-width: 100vw;
                                    }
                                    .cod-feature-list li {
                                        margin-bottom: 0.8rem;
                                    }
                                    .cod-feature-text {
                                        font-size: 0.99rem;
                                        margin-left: 0.2em;
                                    }
                                    .cod-feature-icon {
                                        font-size: 1.12rem;
                                    }
                                }
                                /* Mobile adjustments */
                                @media (max-width: 600px) {
                                    .cod-feature-list {
                                        max-width: 99vw;
                                        padding-left: 0;
                                    }
                                    .cod-feature-list li {
                                        gap: 0;
                                        margin-bottom: 0.75rem !important;
                                    }
                                    .cod-feature-icon {
                                        min-width: 1.55em;
                                        margin-right: 0;
                                    }
                                    .cod-feature-text {
                                        margin-left: 0;
                                        padding-left: 0;
                                        font-size: 0.96rem;
                                        min-width: 0;
                                        flex: 1 1 0%;
                                    }
                                }
                            </style>
                            <ul class="list-unstyled mb-4 cod-feature-list">
                                <li>
                                    <span class="cod-feature-icon" style="color:#7fefff;">
                                        <i class="bi bi-shield-lock-fill"></i>
                                    </span>
                                    <span class="cod-feature-text">Secure & direct settlements to your bank account</span>
                                </li>
                                <li>
                                    <span class="cod-feature-icon" style="color:#30ffb6;">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                    <span class="cod-feature-text">Multiple fast payout cycles &mdash; choose your speed</span>
                                </li>
                                <li>
                                    <span class="cod-feature-icon" style="color:#ffe795;">
                                        <i class="bi bi-file-earmark-bar-graph"></i>
                                    </span>
                                    <span class="cod-feature-text">Transparent deductions – no hidden charges</span>
                                </li>
                                <li>
                                    <span class="cod-feature-icon" style="color:#9cb8ff;">
                                        <i class="bi bi-bar-chart-line"></i>
                                    </span>
                                    <span class="cod-feature-text">Trackable remittance status, always</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services (Shipping Process) under extended hero background -->
            <div class="services-section-under-hero position-relative pt-4 pb-5 mobile-bg-custom" style="z-index:2;">
                <h2 style="color: #ffffff; letter-spacing: -0.7px; font-weight: 600; text-shadow: 0 4px 24px rgba(8,28,60,0.22); font-size: 2.45rem;">
                    Everything you need for a seamless shipping experience.
                </h2>
                <!-- <p class="lead" style="color: #e7f2ff; font-weight: 500; text-shadow: 0 2px 12px #13395b40; font-size:1.13rem;">
                    Everything you need for a seamless shipping experience.
                </p> -->
                <div class="shipping-slider-container mt-4">
                    <div class="shipping-slider-track">
                        <!-- Card 1 -->
                         <!-- <img src="{{ asset('https://shipxpeed.com/assets/website/img/service1.PNG') }}" alt="Add/Sync Shipments"
                                    class="shipping-process-img-large" style="max-height: 270px; max-width: 90%;"> -->
                        <div class="shipping-process-card shipping-slider-card impressive-card">
                            <div class="slider-img-wrapper impressive-img-shadow">
                                
                                    <!-- <p style="font-size: 15px;
    line-height: 1.9;
    color: #5f6368;
    text-align: justify;
    font-weight: 500;
    letter-spacing: 0.3px;
    margin: 0;
    padding: 18px 20px;
    transition: all .3s ease;">Optimize your delivery operations with intelligent NDR Management. Resolve failed deliveries faster through automated workflows, real-time customer engagement,helping reduce RTOs and maximize successful deliveries.</p>
                             -->
                            <img src="{{ asset('https://shipxpeed.com/assets/website/img/ndr1.png') }}" alt="Add/Sync Shipments"
                                    class="shipping-process-img-large" style="max-height: 270px; max-width: 90%;">
                            </div>
                            <div class="slider-title-wrapper impressive-title-underline">
                                <span class="card-title">
                                    <a href="{{ route('seller.login') }}">NDR Management</a>
                                </span>
                            </div>
                        </div>
                        <!-- Card 2 -->
                        <div class="shipping-process-card shipping-slider-card impressive-card">
                            <div class="slider-img-wrapper impressive-img-shadow">
                                <img src="{{ asset('https://shipxpeed.com/assets/website/img/order.png') }}" alt="Choose Courier Partner"
                                    class="shipping-process-img-large" style="max-height: 270px; max-width: 90%;">
                                    <!-- <p style="font-size: 15px;
    line-height: 1.9;
    color: #5f6368;
    text-align: justify;
    font-weight: 500;
    letter-spacing: 0.3px;
    margin: 0;
    padding: 18px 20px;
    transition: all .3s ease;">Order Management streamlines the complete order lifecycle, from order placement to fulfillment and delivery. It ensures accurate processing, real-time tracking, and seamless coordination for a faster and more efficient customer experience.</p>
                             -->
                            </div>
                            <div class="slider-title-wrapper impressive-title-underline">
                                <span class="card-title">
                                    <a href="{{ route('seller.login') }}">Order Management</a>
                                </span>
                            </div>
                        </div>
                        <!-- Card 3 -->
                        <div class="shipping-process-card shipping-slider-card impressive-card">
                            <div class="slider-img-wrapper impressive-img-shadow">
                                <img src="{{ asset('https://shipxpeed.com/assets/website/img/redressal.png') }}" alt="Create Shipping Label"
                                    class="shipping-process-img-large" style="max-height: 270px; max-width: 90%;">
                                    <!-- <p style="font-size: 15px;
    line-height: 1.9;
    color: #5f6368;
    text-align: justify;
    font-weight: 500;
    letter-spacing: 0.3px;
    margin: 0;
    padding: 18px 20px;
    transition: all .3s ease;">Weight Dispute Redressal Mechanism ensures transparent and efficient resolution of shipment weight discrepancies through systematic verification. It helps maintain billing accuracy, and deliver a seamless shipping experience.</p>
                             -->
                        
                        </div>
                            <div class="slider-title-wrapper impressive-title-underline">
                                <span class="card-title">
                                    <a href="{{ route('seller.login') }}">Weight Dispute Redressal Mechanism</a>
                                </span>
                            </div>
                        </div>
                        <!-- Card 4 -->
                        <div class="shipping-process-card shipping-slider-card impressive-card">
                            <div class="slider-img-wrapper impressive-img-shadow">
                                <img src="{{ asset('https://shipxpeed.com/assets/website/img/aibased.png') }}" alt="Track Shipment"
                                    class="shipping-process-img-large" style="max-height: 270px; max-width: 90%;">
                                <!-- <p style="font-size: 15px;
    line-height: 1.9;
    color: #5f6368;
    text-align: justify;
    font-weight: 500;
    letter-spacing: 0.3px;
    margin: 0;
    padding: 18px 20px;
    transition: all .3s ease;">AI-Based Courier Allocation intelligently selects the most suitable courier partner by analyzing factors such as cost, delivery speed, serviceability, and performance. This ensures optimized shipping, higher delivery success rates.</p>
                             -->
                        </div>
                            <div class="slider-title-wrapper impressive-title-underline">
                                <span class="card-title">
                                    <a href="{{ route('seller.login') }}">AI Based Courier Allocation</a>
                                </span>
                            </div>
                        </div>

                        <!-- Card 5 -->
                        <div class="shipping-process-card shipping-slider-card impressive-card">
                            <div class="slider-img-wrapper impressive-img-shadow">
                                <img src="{{ asset('https://shipxpeed.com/assets/website/img/rto1.png') }}" alt="Track Shipment"
                                    class="shipping-process-img-large" style="max-height: 270px; max-width: 100%;">
                                    <!-- <p style="font-size: 15px;
    line-height: 1.9;
    color: #5f6368;
    text-align: justify;
    font-weight: 500;
    letter-spacing: 0.3px;
    margin: 0;
    padding: 18px 20px;
    transition: all .3s ease;">RTO Risk Reduction leverages intelligent analytics and proactive shipment monitoring to minimize Return-to-Origin (RTO) cases. By validating customer information and optimizing delivery strategies, it improves delivery success rates.</p>
                             -->
                        </div>
                            <div class="slider-title-wrapper impressive-title-underline">
                                <span class="card-title">
                                    <a href="{{ route('seller.login') }}">RTO Risk Reduction</a>
                                </span>
                            </div>
                        </div>

                        <!-- Card 6 -->
                        <div class="shipping-process-card shipping-slider-card impressive-card">
                            <div class="slider-img-wrapper impressive-img-shadow">
                                <img src="{{ asset('https://shipxpeed.com/assets/website/img/watsappp.png') }}" alt="Track Shipment"
                                    class="shipping-process-img-large" style="max-height: 270px; max-width: 90%;">
                                    <!-- <p style="font-size: 15px;
    line-height: 1.9;
    color: #5f6368;
    text-align: justify;
    font-weight: 500;
    letter-spacing: 0.3px;
    margin: 0;
    padding: 18px 20px;
    transition: all .3s ease;">Our WhatsApp Communication solution facilitates instant, automated messaging for shipping updates, delivery notifications, and customer support. It ensures transparent communication, and strengthens engagement throughout the shipping lifecycle.</p>
                             -->
                        </div>
                            <div class="slider-title-wrapper impressive-title-underline">
                                <span class="card-title">
                                    <a href="{{ route('seller.login') }}">Whatsapp Communication</a>
                                </span>
                            </div>
                        </div>

                        <!-- Card 7 -->
                        <div class="shipping-process-card shipping-slider-card impressive-card">
                            <div class="slider-img-wrapper impressive-img-shadow">
                                <img src="{{ asset('https://shipxpeed.com/assets/website/img/branded.png') }}" alt="Track Shipment"
                                    class="shipping-process-img-large" style="max-height: 270px; max-width: 90%;">
                                    <!-- <p style="font-size: 15px;
    line-height: 1.9;
    color: #5f6368;
    text-align: justify;
    font-weight: 500;
    letter-spacing: 0.3px;
    margin: 0;
    padding: 18px 20px;
    transition: all .3s ease;">Deliver a seamless post-purchase experience with branded tracking pages that showcase your logo, real-time shipment status. Strengthen customer engagement while maintaining complete transparency throughout the delivery journey.</p>
                             -->
                        </div>
                            <!-- <div class="slider-title-wrapper impressive-title-underline">
                                <span class="card-title">
                                    <a href="{{ route('seller.login') }}">Branded Tracking</a>
                                </span>
                            </div> -->
                        </div>
                    </div>
                    <!-- Dots removed for mobile view as per request -->
                </div>
                <script>
                // Simple vanilla JS infinite loop slider for mobile (auto-play, no dots, slow speed)
                document.addEventListener('DOMContentLoaded', function () {
                    function isMobile() {
                        return window.innerWidth <= 767;
                    }

                    const track = document.querySelector('.shipping-slider-track');
                    const cards = Array.from(track.children);

                    if (isMobile()) {
                        let current = 0;
                        let interval;
                        let clonedCardsLeft = [];
                        let clonedCardsRight = [];
                        const total = cards.length;

                        function setupClones() {
                            clonedCardsLeft.forEach(n => track.removeChild(n));
                            clonedCardsRight.forEach(n => track.removeChild(n));
                            clonedCardsLeft = [];
                            clonedCardsRight = [];
                            const first = cards[0].cloneNode(true);
                            const last = cards[cards.length - 1].cloneNode(true);
                            track.insertBefore(last, cards[0]);
                            track.appendChild(first);
                            clonedCardsLeft.push(last);
                            clonedCardsRight.push(first);
                        }

                        setupClones();

                        function setStyles() {
                            track.style.transition = 'none';
                            cards.forEach(card => {
                                card.style.minWidth = '96vw';
                                card.style.maxWidth = '98vw';
                                card.style.margin = '0 2vw';
                            });
                            clonedCardsLeft.forEach(card => {
                                card.style.minWidth = '96vw';
                                card.style.maxWidth = '98vw';
                                card.style.margin = '0 2vw';
                            });
                            clonedCardsRight.forEach(card => {
                                card.style.minWidth = '96vw';
                                card.style.maxWidth = '98vw';
                                card.style.margin = '0 2vw';
                            });
                        }
                        setStyles();

                        function update() {
                            const offset = -((current + 1) * (track.children[0].offsetWidth + 8));
                            track.style.transition = 'transform 1.15s cubic-bezier(0.73,.04,.18,1)';
                            track.style.transform = `translateX(${offset}px)`;
                        }

                        function next() {
                            current++;
                            update();
                            if (current >= total) {
                                setTimeout(() => {
                                    track.style.transition = 'none';
                                    current = 0;
                                    update();
                                }, 1150);
                            }
                        }

                        function prev() {
                            current--;
                            update();
                            if (current < 0) {
                                setTimeout(() => {
                                    track.style.transition = 'none';
                                    current = total - 1;
                                    update();
                                }, 1150);
                            }
                        }

                        function resetInterval() {
                            clearInterval(interval);
                            interval = setInterval(next, 3800);
                        }

                        update();
                        interval = setInterval(next, 3800);

                        // Touch swipe for mobile
                        let startX = 0, deltaX = 0, touching = false;
                        track.addEventListener('touchstart', function(e) {
                            touching = true;
                            clearInterval(interval);
                            startX = e.touches[0].clientX;
                            track.style.transition = 'none';
                        });
                        track.addEventListener('touchmove', function(e) {
                            if (!touching) return;
                            deltaX = e.touches[0].clientX - startX;
                            const offset = -((current + 1) * (track.children[0].offsetWidth + 8)) + deltaX;
                            track.style.transform = `translateX(${offset}px)`;
                        });
                        track.addEventListener('touchend', function() {
                            touching = false;
                            if (Math.abs(deltaX) > 30) {
                                if (deltaX < 0) next();
                                else prev();
                            } else {
                                update();
                            }
                            deltaX = 0;
                            resetInterval();
                        });

                        window.addEventListener('resize', function () {
                            if (!isMobile()) {
                                track.style.transform = '';
                                track.style.transition = '';
                                clearInterval(interval);
                            } else {
                                setStyles();
                                update();
                                resetInterval();
                            }
                        });
                    } else {
                        // Non-mobile: show all, disable slider
                        track.style.transform = '';
                        track.style.transition = '';
                        Array.from(track.children).forEach(card => {
                            card.style.minWidth = '';
                            card.style.maxWidth = '';
                            card.style.margin = '';
                        });
                    }
                });
                </script>
            </div>
            <style>
                .impressive-card {
                    display: flex !important;
                    flex-direction: column;
                    height: 350px !important;
                    width: 99% !important;
                    min-width: 290px !important;
                    max-width: 520px !important;
                    justify-content: flex-start;
                    align-items: center;
                    background: linear-gradient(135deg, #f7fcff 0%, #eaf2fa 85%, #d5e7fa 100%);
                    box-shadow: 0 10px 40px 0 rgba(54,164,255,.17), 0 3px 16px 0 rgba(40,124,245,0.11);
                    border-radius: 1.8rem !important;
                    border: 2px solid #e1edfa;
                    padding: 28px 22px 12px 22px;
                    margin: 0 2vw;
                    transition: transform 0.36s cubic-bezier(.99,.11,.21,.9), box-shadow 0.36s;
                    position: relative;
                }
                .impressive-card:hover, .impressive-card:focus-within {
                    transform: translateY(-9px) scale(1.045);
                    box-shadow: 0 16px 44px 0 rgba(31,173,215,0.18), 0 7px 18px 0 rgba(62,114,222,.21);
                }
                .slider-img-wrapper.impressive-img-shadow {
                    flex: 0 0 67%;
                    height: 67%;
                    width: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 21px;
                    filter: drop-shadow(0 10px 40px #6dd5ee1a);
                }
                .slider-title-wrapper.impressive-title-underline {
                    flex: 0 0 23%;
                    height: 23%;
                    width: 100%;
                    display: flex;
                    align-items: flex-end;
                    justify-content: center;
                    padding-bottom: 6px;
                    position: relative;
                }
                .slider-title-wrapper.impressive-title-underline .card-title {
                    font-weight: 800;
                    font-size: 1.34rem;
                    color: #184b75;
                    letter-spacing: -0.6px;
                    position: relative;
                    display: inline-block;
                    z-index: 1;
                    padding: 0 .3em;
                    text-align: center;
                }
                .slider-title-wrapper.impressive-title-underline .card-title a {
                    color: #1062e1;
                    background: linear-gradient(80deg, #ffe 45%, #e6f0ff 100%);
                    border-radius: 0.55em;
                    padding: 0.17em 0.5em;
                    transition: background 0.2s, color 0.2s;
                    text-decoration: none;
                    box-shadow: 0 2px 7px #6dd5ee22;
                }
                .slider-title-wrapper.impressive-title-underline .card-title a:hover {
                    color: #0660b5;
                    background: #eaf6ff;
                }
                @media (max-width: 1099px) {
                    .impressive-card {
                        min-width: 56vw !important;
                        max-width: 85vw !important;
                        height: 375px !important;
                        padding: 20px 12px 8px 12px;
                    }
                }
                @media (max-width: 767px) {
                    .services-section-under-hero.mobile-bg-custom {
                        background: radial-gradient(circle at 85% 85%, #2530a1 0%, transparent 45%), radial-gradient(circle at 20% 85%, #3f3f53 0%, transparent 50%), linear-gradient(180deg, #0b052f 0%, #2517ed 45%, #080933 100%) !important;
                        margin-left: -13px;
                        margin-right: -13px;
                        padding:13px;
                    }

                    .impressive-card {
                        min-width: 90vw !important;
                        max-width: 96vw !important;
                        margin: 0 2vw;
                        height: 340px !important;
                        padding: 13px 3vw 12px 3vw;
                        border-radius: 1.5rem !important;
                    }
                    .slider-title-wrapper.impressive-title-underline .card-title {
                        font-size: 1.08rem;
                        padding:.09em .25em;
                    }
                }
            </style>
        </div>

        <style>
            .premium-hero-section.extended-hero-bg {
                position: relative;
                padding-bottom: 0;
            }
            .premium-hero-section .hero-bg-wrapper {
                pointer-events: none;
            }
            .premium-hero-section > .container,
            .services-section-under-hero {
                position: relative;
                z-index: 2;
            }
            .services-section-under-hero {
                background: none !important;
            }
            .shipping-slider-container {
                position: relative;
                max-width: 100%;
                margin: 0 auto;
                overflow: hidden;
                padding: 18px 0 10px 0;
            }
            .shipping-slider-track {
                display: flex;
                will-change: transform;
                /* SPEED INCREASED BY 20%: from 28s to 23.33s */
                transition: transform 23.33s cubic-bezier(0.62,0.03,0.22,1);
            }
            .shipping-slider-card {
                flex: 0 0 20%;
                max-width: 20%;
                min-width: 20%;
                margin: 0 1vw;
                background: #fff;
                border-radius: 1.1rem;
                box-shadow: 0 7px 18px 0 rgba(62,114,222,.11), 0 1.5px 3px rgba(60,108,208,.08);
                height: 305px;
                display: flex;
                justify-content: center;
                align-items: center;
                transition: box-shadow 0.66s cubic-bezier(.73,.04,.18,1), transform 1.5s cubic-bezier(.75,.14,.11,.99);
                cursor: pointer;
                position: relative;
                overflow: hidden;
                padding: 0;
            }
            .slider-img-wrapper {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .shipping-process-img-large {
                width: 100%;
                height: 100%;
                /* object-fit: contain; */
                max-width: 100%;
                max-height: 100%;
                border-radius: 16px;
                background: #f0f5fd;
                box-shadow: none;
                transition: transform 1.3s cubic-bezier(.73,.04,.18,1);
                display: block;
            }
            .shipping-slider-card:hover,
            .shipping-slider-card.active {
                box-shadow: 0 16px 38px 0 rgba(8,25,66,0.13), 0 2.5px 11px rgba(26,93,132,.13);
                transform: scale(1.07) translateY(-8px);
                z-index: 2;
                background: #eaf6ff;
            }
            .shipping-slider-card:hover .shipping-process-img-large,
            .shipping-slider-card.active .shipping-process-img-large {
                transform: scale(1.13);
            }
            @media (max-width:1199px) {
                .shipping-slider-card { height: 180px; }
            }
            @media (max-width:991px) {
                .shipping-slider-card { height: 110px; min-width: 28%; max-width: 28%; }
                .slider-img-wrapper { min-width: 100%; min-height: 100%; }
            }
            @media (max-width:767px) {
                .shipping-slider-card { height: 75px; min-width: 45%; max-width: 45%; }
                .shipping-slider-container { padding: 7px 0; }
            }
            .text-gradient {
                background: linear-gradient(92deg, #3763a9 0%, #1d6ca7 62%, #3b99a9 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .bg-gradient-primary { background: linear-gradient(94deg, #2284f6 40%, #3dcdf8 100%) !important;}
            .bg-gradient-warning { background: linear-gradient(91deg, #ffbe41 30%, #ffdf8c 100%) !important;}
            .bg-gradient-danger { background: linear-gradient(89deg, #e0463e 40%, #faa6b0 100%) !important;}
            .bg-gradient-info { background: linear-gradient(93deg, #1fa9e8 40%, #75e6fe 100%) !important;}
        </style>
        <script>
            // Responsive: Adjust the background height according to section content on mobile/tablet
            function setHeroBgHeight() {
                var windowWidth = window.innerWidth;
                var section = document.getElementById('hero-cod');
                var bg = document.getElementById('heroBgResponsiveHeight');
                if (!section || !bg) return;

                if(windowWidth < 576) {
                    // For mobile, set background height till the end of the section!
                    // (No min limit; extend image to section)
                    var sectionHeight = section.offsetHeight;
                    bg.style.height = sectionHeight + "px";
                    bg.style.minHeight = sectionHeight + "px";
                } else if(windowWidth < 768) {
                    var sectionHeight = section.offsetHeight;
                    if(sectionHeight < 550) sectionHeight = 550;
                    bg.style.height = sectionHeight + "px";
                    bg.style.minHeight = sectionHeight + "px";
                } else if(windowWidth < 992) {
                    var sectionHeight = section.offsetHeight;
                    if(sectionHeight < 700) sectionHeight = 700;
                    bg.style.height = sectionHeight + "px";
                    bg.style.minHeight = sectionHeight + "px";
                } else {
                    // Desktop defaults
                    bg.style.height = "1900px";
                    bg.style.minHeight = "1400px";
                }
            }
            window.addEventListener('load', setHeroBgHeight);
            window.addEventListener('resize', setHeroBgHeight);
            setTimeout(setHeroBgHeight,120);

            (function(){
                const track = document.querySelector('.shipping-slider-track');
                const cards = Array.from(track.children);
                const totalCards = cards.length;
                const numVisible = 5;
                // Clone for infinite loop
                for(let i=0;i<totalCards;i++) {
                    let cloneFirst = cards[i].cloneNode(true);
                    cloneFirst.classList.add('clone');
                    track.appendChild(cloneFirst);
                }
                for(let i=totalCards-1;i>=0;i--) {
                    let cloneLast = cards[i].cloneNode(true);
                    cloneLast.classList.add('clone');
                    track.insertBefore(cloneLast, track.firstChild);
                }
                const allCards = Array.from(track.children);
                let cardWidth = allCards[0].offsetWidth;
                let gap = parseFloat(getComputedStyle(allCards[0]).marginLeft) + parseFloat(getComputedStyle(allCards[0]).marginRight) || 0;
                let totalWidth = cardWidth + gap;
                let current = totalCards;

                function setPosition(instant = true) {
                    if (instant) track.style.transition = "none";
                    else track.style.transition = "transform 23.33s cubic-bezier(0.62,0.03,0.22,1)";
                    track.style.transform = `translateX(${-current * totalWidth}px)`;
                    highlightDot(current % totalCards);
                    allCards.forEach(e => e.classList.remove('active'));
                    if(allCards[current]) allCards[current].classList.add('active');
                }
                function updateDimensions() {
                    cardWidth = allCards[0].offsetWidth;
                    gap = parseFloat(getComputedStyle(allCards[0]).marginLeft) + parseFloat(getComputedStyle(allCards[0]).marginRight) || 0;
                    totalWidth = cardWidth + gap;
                    setPosition();
                }
                window.addEventListener('resize', updateDimensions);
                setTimeout(updateDimensions, 50);

                function next() {
                    current++;
                    setPosition(false);
                    if (current >= totalCards * 2) {
                        setTimeout(function() {
                            track.style.transition = 'none';
                            current = totalCards;
                            setPosition(true);
                        }, 23600);
                    }
                }
                function prev() {
                    current--;
                    setPosition(false);
                    if (current < totalCards) {
                        setTimeout(function() {
                            track.style.transition = 'none';
                            current = totalCards * 2 - 1;
                            setPosition(true);
                        }, 23600);
                    }
                }

                let autoSlideInterval = null;
                function startAuto() {
                    if (autoSlideInterval) clearInterval(autoSlideInterval);
                    autoSlideInterval = setInterval(function(){
                        next();
                    }, 23666); // 20% faster than 28400ms
                }
                function stopAuto(){
                    if(autoSlideInterval) clearInterval(autoSlideInterval);
                }

                // No hover pause: Hovering does NOT pause auto sliding, so ignore userHovered
                allCards.forEach((card, idx) => {
                    card.addEventListener('mouseenter', () => {
                        if (!card.classList.contains('clone')) {
                            current = totalCards + (idx % totalCards);
                            setPosition(false);
                        }
                    });
                });
                let startX = null, drag = false;
                track.addEventListener('touchstart', e => {
                    stopAuto();
                    startX = e.touches[0].clientX; drag = true;
                });
                track.addEventListener('touchmove', e => {
                    if (!drag) return;
                    let dx = e.touches[0].clientX - startX;
                    if (dx > 45) { drag=false; prev(); }
                    if (dx < -45) { drag=false; next(); }
                });
                track.addEventListener('touchend', () => { startAuto(); drag=false; });
                const dotWrap = document.querySelector('.shipping-slider-dots');
                dotWrap.innerHTML = '';
                for(let i=0;i<totalCards;i++) {
                    let dot = document.createElement('span');
                    dot.className = "dot";
                    dot.style.display = "inline-block";
                    dot.style.width = '10px'; dot.style.height = '10px'; dot.style.borderRadius = '100%'; dot.style.margin = '0 3px';
                    dot.style.background = '#c2cbe3';
                    dot.style.cursor = "pointer";
                    dot.style.transition = ".25s";
                    (function(index){
                        dot.addEventListener('mouseenter', ()=> {
                            current = index + totalCards;
                            setPosition(false);
                        });
                    })(i);
                    dotWrap.appendChild(dot);
                }
                function highlightDot(idx) {
                    Array.from(dotWrap.children).forEach((d,i)=>{d.style.background = i==idx ? '#2997ff' : '#c2cbe3'});
                }
                track.addEventListener('transitionend', function(){
                    if (current >= totalCards * 2) {
                        track.style.transition = "none";
                        current = totalCards;
                        setPosition(true);
                    }
                    if (current < totalCards) {
                        track.style.transition = "none";
                        current = totalCards * 2 - 1;
                        setPosition(true);
                    }
                });

                setPosition(true);

                // Always auto slide unless window is hidden/out of view (performance)
                function onVisibilityChange() {
                    if (document.visibilityState === "visible") {
                        startAuto();
                    } else {
                        stopAuto();
                    }
                }
                document.addEventListener('visibilitychange', onVisibilityChange);
                onVisibilityChange();

                // Removed user-hover logic and slider container hover logic: Cards always in sliding mode

            })();
        </script>
    </section>
    <!-- End Enhanced Hero Section -->

    <section id="clients" class="clients-section circular-brands-section" style="background: linear-gradient(116deg, #e9f4fd 0%, #f7fafd 96%);min-height:580px;position:relative;overflow:visible;">
        <div class="container pb-3" style="position:relative;z-index:2;">
            <div class="section-header mb-4 text-left" style="text-align:left!important;">
                <h2 style="font-size:2.25rem;letter-spacing:.2px;color:#183559;margin-bottom:8px;font-family:'Segoe UI',Inter,Arial,sans-serif;">
                    Trusted by Leading Courier Partners
                </h2>
                <p style="color:#446087;font-size:1.18rem;margin:0;">
                    We collaborate with global leaders to deliver excellence
                </p>
            </div>
            <div id="brands-slider-container" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:60px;">
                <!-- Standard Desktop Layout (hidden on mobile) -->
                <div class="left-brands-col" style="flex:1 1 430px;min-width:350px;max-width:520px;display:block;" id="desktop-brands">
                    <div class="circular-brands-visual position-relative" style="width:440px;height:430px;margin-left:0;">
                        <div class="circular-brands-center-logo" style="position:absolute;left:50%;top:48%;transform:translate(-50%,-50%);z-index:2;width:120px;height:120px;display:flex;align-items:center;justify-content:center;background:#fff;border-radius:50%;box-shadow:0 4px 32px rgba(50,120,200,0.10);">
                            <img src="{{ asset('assets/website/img/newlogo.PNG') }}" alt="Main Logo" style="max-width:92px;max-height:92px;display:block;margin:auto;">
                        </div>
                        <div class="circular-brands-orbit" id="circularBrandsOrbit" style="position:absolute;left:50%;top:48%;transform:translate(-50%,-50%);width:330px;height:330px;z-index:1;">
                            @php
                                $brandCount = count($brands);
                                $brandImageList = [];
                                foreach($brands as $brand) {
                                    $brandImageList[] = [
                                        'src' => Helper::showImage($brand->image, true),
                                        'alt' => 'Brand Logo'
                                    ];
                                }
                            @endphp
                            <div class="orbit-fallback" style="position:absolute;left:0;top:0;width:100%;height:100%;">
                                @for($i = 0; $i < min($brandCount,8); $i++)
                                    @php
                                        $angle = ($i/min($brandCount,8)) * 2 * M_PI;
                                        $r = 140;
                                        $cx = 165 + $r * cos($angle - M_PI/2);
                                        $cy = 165 + $r * sin($angle - M_PI/2);
                                    @endphp
                                    <img 
                                        src="{{ $brandImageList[$i]['src'] }}" 
                                        alt="{{ $brandImageList[$i]['alt'] }}"
                                        style="position:absolute;left:{{ $cx-44 }}px;top:{{ $cy-26 }}px;width:88px;height:52px;object-fit:contain;filter:drop-shadow(0 4px 18px rgba(120,160,210,0.13));border-radius:12px;background:none;padding:0px;">
                                @endfor
                            </div>
                        </div>
                        <div class="circular-brands-orbit-bg" style="position:absolute;left:50%;top:48%;transform:translate(-50%,-50%);width:330px;height:330px;z-index:0;">
                            <div style="width:100%;height:100%;border-radius:50%;background:radial-gradient(circle at 60% 40%,#ecf2fb 60%,#ddeffd 100%,transparent 103%);box-shadow:0 6px 40px rgba(150,190,230,0.13);opacity:0.91;"></div>
                        </div>
                    </div>
                </div>
                <div class="right-image-col d-flex align-items-center justify-content-center" style="flex:1 1 370px;min-width:330px;display:block;" id="desktop-right-image">
                    <img src="{{ asset('assets/website/img/brandsecbg.png') }}" alt="Supporting Visual" style="max-width:560px;width:100%;height:auto;display:block;border-radius:22px;">
                </div>
                <!-- MOBILE Infinite Horizontal Slider -->
                <div id="brands-mobile-slider-wrapper" style="width:100vw;overflow:hidden;display:none;align-items:center;justify-content:center;padding:16px 0 20px 0;">
                    <div id="brands-mobile-slider" style="display:flex;flex-direction:row;gap:16px;will-change:transform;">
                        @foreach($brandImageList as $brand)
                            <div class="brand-slide-item" style="flex:0 0 auto;display:flex;align-items:center;justify-content:center;background:#fff;padding:7px 17px;border-radius:12px;box-shadow:0 4px 12px rgba(120,160,210,0.09);margin:0;">
                                <img src="{{ $brand['src'] }}" alt="{{ $brand['alt'] }}" style="display:block;width:68px;height:38px;object-fit:contain;">
                            </div>
                        @endforeach
                        @foreach($brandImageList as $brand)
                            <div class="brand-slide-item" style="flex:0 0 auto;display:flex;align-items:center;justify-content:center;background:#fff;padding:7px 17px;border-radius:12px;box-shadow:0 4px 12px rgba(120,160,210,0.09);margin:0;">
                                <img src="{{ $brand['src'] }}" alt="{{ $brand['alt'] }}" style="display:block;width:68px;height:38px;object-fit:contain;">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <script>
            // Desktop circular animation
            document.addEventListener('DOMContentLoaded', function() {
                function isMobile() {
                    return window.innerWidth <= 850;
                }
                // Responsive view toggling
                function handleBrandsView() {
                    var desktop = document.getElementById('desktop-brands');
                    var desktopImg = document.getElementById('desktop-right-image');
                    var mobWrap = document.getElementById('brands-mobile-slider-wrapper');
                    if(isMobile()){
                        if(desktop) desktop.style.display = 'none';
                        if(desktopImg) desktopImg.style.display = 'none';
                        if(mobWrap) mobWrap.style.display = 'flex';
                    } else {
                        if(desktop) desktop.style.display = 'block';
                        if(desktopImg) desktopImg.style.display = 'block';
                        if(mobWrap) mobWrap.style.display = 'none';
                    }
                }
                handleBrandsView();
                window.addEventListener('resize', handleBrandsView);

                // Hide fallback on desktop
                var fallback = document.querySelector('.orbit-fallback');
                if (!isMobile() && fallback) fallback.style.display = "none";

                // Animate desktop circular brands
                if(!isMobile()) {
                    const brands = @json($brandImageList);
                    const N = brands.length;
                    const displayed = Math.min(8, N);
                    if (!N) return;

                    const orbit = document.getElementById('circularBrandsOrbit');
                    orbit && orbit.querySelectorAll(".rotating-brand-logo").forEach(el => el.remove());

                    const RADIUS = 140;
                    const orbitCenter = { x: 165, y: 165 };
                    const logoW = 88, logoH = 52;

                    const logoNodes = [];
                    for (let i = 0; i < displayed; i++) {
                        const img = document.createElement("img");
                        img.src = brands[i % N].src;
                        img.alt = brands[i % N].alt;
                        img.style.position = "absolute";
                        img.style.width = logoW + "px";
                        img.style.height = logoH + "px";
                        img.style.objectFit = "contain";
                        img.style.left = "0px";
                        img.style.top = "0px";
                        img.style.borderRadius = "12px";
                        img.style.background = "none";
                        img.style.padding = "0px";
                        img.style.boxShadow = "0 4px 18px rgba(120,160,210,0.13)";
                        img.classList.add("rotating-brand-logo");
                        orbit.appendChild(img);
                        logoNodes.push(img);
                    }

                    let theta = 0;
                    const spinSpeed = 0.002;
                    function animate() {
                        theta += spinSpeed;
                        for (let i = 0; i < logoNodes.length; i++) {
                            const angle = (2 * Math.PI / displayed) * i + theta;
                            const x = orbitCenter.x + RADIUS * Math.cos(angle);
                            const y = orbitCenter.y + RADIUS * Math.sin(angle);
                            let popZ = Math.cos(angle);
                            let scale = 1 + 0.12 * popZ;
                            let opacity = 0.97 + 0.03 * Math.max(0, popZ);
                            let boxShadow = `0 ${2+2*popZ}px ${8+8*Math.abs(popZ)}px rgba(120,170,210,${0.10+0.05*Math.max(0, popZ)})`;
                            const node = logoNodes[i];
                            node.style.left = (x - logoW/2) + "px";
                            node.style.top = (y - logoH/2) + "px";
                            node.style.transform = `scale(${scale})`;
                            node.style.opacity = opacity;
                            node.style.boxShadow = boxShadow;
                            node.style.zIndex = 10 + Math.round(10 * popZ);
                        }
                        requestAnimationFrame(animate);
                    }
                    animate();
                }

                // Infinite horizontal slider for mobile
                let slider = document.getElementById('brands-mobile-slider');
                let wrap = document.getElementById('brands-mobile-slider-wrapper');
                if (slider && wrap) {
                    let itemCount = slider.children.length / 2;
                    let itemWidth = 0;
                    let step = 1;
                    let pos = 0;
                    function calcWidths() {
                        // Get actual item width including gap
                        if (slider.children[0]) {
                            itemWidth = slider.children[0].offsetWidth + 16; // 16px gap
                        }
                    }
                    function animateSlider() {
                        if(window.innerWidth <= 850) {
                            pos -= step;
                            if (Math.abs(pos) >= itemWidth * itemCount) {
                                pos = 0;
                            }
                            slider.style.transform = 'translateX(' + pos + 'px)';
                            requestAnimationFrame(animateSlider);
                        } else {
                            slider.style.transform = '';
                        }
                    }
                    function onResize(){
                        calcWidths();
                    }
                    window.addEventListener('resize', onResize);
                    // Wait images loaded
                    setTimeout(() => {
                        calcWidths();
                        animateSlider();
                    }, 100);
                }
            });
        </script>
    </section>
    <!-- Unified Hero & COD Remittance Section with Single Extended Background, including Services under one background -->
    <section 
        id="hero-cod" 
        class="hero-cod section premium-hero-section position-relative overflow-hidden extended-hero-bg"
        style="
            position:relative;
            overflow:hidden;
            padding-bottom:0;
            width:100%;
            background-image: url('{{ asset('assets/website/img/herosection.PNG') }}');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: top center;
            min-height: 1400px;
            z-index:1;
        ">
        <div 
            class="container position-relative" 
            style="
                z-index:2;
                padding-left: 24px; 
                padding-right: 24px;
                max-width: 1320px;
                margin: 0 auto;
                width:100%;
            ">
            <!-- Service 1 -->
            <div class="service-cod-box-row">
                <div class="service-cod-box">
                    <div class="service-cod-box-inner flex-lg-row flex-md-row d-flex align-items-center justify-content-between">
                        <div class="service-cod-img-box order-lg-1 order-md-1 order-1">
                            <img src="{{ asset('https://shipxpeed.com/assets/website/img/45.PNG') }}" alt="WhatsApp Alert" />
                        </div>
                        <div class="service-cod-content-box order-lg-2 order-md-2 order-2">
                            <h2>Stay Updated with Smart WhatsApp Delivery Alerts</h2>
                            <p>
                                Never miss an update about your shipment with <span style="color:#f7cf5c;">ShipXpeed’s WhatsApp notifications</span>.
                                Get real-time tracking alerts, delivery status updates, and instant communication directly on your phone. From dispatch to doorstep, stay informed every step of the journey. Enjoy faster coordination, better visibility, and a seamless delivery experience — all within WhatsApp.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Service 2 -->
            <div class="service-cod-box-row">
                <div class="service-cod-box">
                    <div class="service-cod-box-inner flex-lg-row flex-md-row d-flex align-items-center justify-content-between">
                        <div class="service-cod-img-box order-lg-1 order-md-1 order-1">
                            <img src="{{ asset('https://shipxpeed.com/assets/website/img/50.PNG') }}" alt="Real-Time Tracking" />
                        </div>
                        <div class="service-cod-content-box order-lg-2 order-md-2 order-2">
                            <h2>Real-Time Tracking for Complete Delivery Transparency</h2>
                            <p>
                                Stay informed every step of the way with ShipXpeed’s smart live tracking system. Monitor your shipment status, estimated delivery date, and real-time location updates with ease. Our advanced tracking ensures visibility, accuracy, and peace of mind throughout the delivery journey. Experience faster, smarter, and more reliable shipping updates.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Service 3 -->
            <div class="service-cod-box-row">
                <div class="service-cod-box">
                    <div class="service-cod-box-inner flex-lg-row flex-md-row d-flex align-items-center justify-content-between">
                        <div class="service-cod-img-box order-lg-1 order-md-1 order-1">
                            <img src="{{ asset('https://shipxpeed.com/assets/website/img/46.PNG') }}" alt="Shopify Woo Integration" />
                        </div>
                        <div class="service-cod-content-box order-lg-2 order-md-2 order-2">
                            <h2>Seamless Shopify &amp; WooCommerce Integration</h2>
                            <p>
                                Connect your online store effortlessly with ShipXpeed through powerful Shopify and WooCommerce integrations. Automate order syncing, shipment creation, and tracking updates in real time without manual effort. Simplify your logistics workflow while improving order accuracy and delivery speed. Focus on growing your business while we handle the shipping operations smoothly.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Service 4 -->
            <div class="service-cod-box-row">
                <div class="service-cod-box">
                    <div class="service-cod-box-inner flex-lg-row flex-md-row d-flex align-items-center justify-content-between">
                        <div class="service-cod-img-box order-lg-1 order-md-1 order-1">
                            <img src="{{ asset('https://shipxpeed.com/assets/website/img/47.PNG') }}" alt="Account Manager Support" />
                        </div>
                        <div class="service-cod-content-box order-lg-2 order-md-2 order-2">
                            <h2>Dedicated Key Account Manager – Support That Never Stops</h2>
                            <p>
                                Get personalized assistance with a dedicated key account manager available 24/7 to support your business needs. From shipment coordination to quick issue resolution, our experts ensure smooth and hassle-free logistics operations. Enjoy faster responses, proactive communication, and tailored solutions designed for your growth. With ShipXpeed, reliable support is always just a call away.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Service 5 -->
            <div class="service-cod-box-row">
                <div class="service-cod-box">
                    <div class="service-cod-box-inner flex-lg-row flex-md-row d-flex align-items-center justify-content-between">
                        <div class="service-cod-img-box order-lg-1 order-md-1 order-1">
                            <img src="{{ asset('https://shipxpeed.com/assets/website/img/48.PNG') }}" alt="IVR Delivery Success" />
                        </div>
                        <div class="service-cod-content-box order-lg-2 order-md-2 order-2">
                            <h2>Dedicated IVR Calling for Faster Delivery Success</h2>
                            <p>
                                Reduce Non-Delivery Reports (NDR) with our intelligent IVR calling system designed to confirm deliveries and update customers instantly. Automated calls help verify availability, reschedule deliveries, and minimize failed attempts. Improve delivery success rates while saving time and operational effort. ShipXpeed ensures smoother last-mile communication for a more reliable delivery experience.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .service-cod-box-row {
                margin-bottom: 2.1rem;
                display: flex;
                justify-content: center;
            }
            .service-cod-box {
                width: 100%;
                max-width: 1160px;
                background: rgba(17,44,73,0.89);
                border-radius: 18px;
                box-shadow: 0 3px 16px 0 rgba(21,41,67,0.08), 0 1.5px 0px 0 rgba(255,255,255,0.01) inset;
                padding: 0;
            }
            .service-cod-box-inner {
                width: 100%;
                display: flex;
                align-items: stretch;
                justify-content: space-between;
                gap: 0;
            }
            .service-cod-img-box {
                flex: 1 1 45%;
                display: flex;
                justify-content: center;
                align-items: stretch;
                padding: 30px 30px 30px 32px;
            }
            .service-cod-img-box img {
                width: 100%;
                max-width: 480px;
                height: 100%;
                border-radius: 12px;
                object-fit: cover;
                background: #12213c;
                display: block;
            }
            .service-cod-content-box {
                flex: 1 1 55%;
                color: #eaf6ff;
                padding: 26px 38px 26px 16px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .service-cod-content-box h2 {
                font-size: 2.06rem;
                font-weight: bold;
                color: #f6fafd;
                letter-spacing: -0.5px;
                line-height: 1.18;
                margin-bottom: 1.05rem;
                text-shadow: 0 2px 8px rgba(0,0,0,0.09);
            }
            .service-cod-content-box p {
                font-size: 1.08em;
                color: #c9e2ff;
                line-height: 1.6;
                letter-spacing: -0.01em;
                margin-bottom: 1.38rem;
                text-align: justify;
            }
            @media (max-width:1199.98px) {
                .service-cod-box { max-width:100%; }
                .service-cod-img-box, .service-cod-content-box { padding:22px; }
                .service-cod-img-box img { max-width:94vw; }
            }
            @media (max-width:991.98px) {
                .service-cod-box-inner { flex-wrap:wrap; }
                .service-cod-img-box, .service-cod-content-box { padding:18px 14px; }
                .service-cod-img-box img { max-width:90vw; }
                .service-cod-content-box h2 { font-size: 1.36rem; }
                .service-cod-content-box p { font-size: 1em; }
            }
            @media (max-width:767.98px) {
                /* Show as row: image left, text right */
                .service-cod-box-inner {
                    flex-direction: row !important;
                    gap: 0;
                    align-items: stretch;
                }
                .service-cod-img-box,
                .service-cod-content-box {
                    width: 50%;
                    max-width: 50%;
                    box-sizing: border-box;
                    padding: 18px 10px;
                }
                .service-cod-img-box {
                    display: flex;
                    align-items: stretch;
                }
                .service-cod-img-box img {
                    width: 100% !important;
                    max-width: 100% !important;
                    height: 100% !important;
                    min-width: 0;
                    min-height: 0;
                    max-height: none;
                    object-fit: cover;
                    border-radius: 10px;
                    display: block;
                }
                .service-cod-content-box {
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }
                .service-cod-content-box h2 {
                    font-size: 1.07rem;
                    line-height:1.25;
                    margin-bottom: 0.8rem;
                    text-align: left;
                }
                .service-cod-content-box p {
                    font-size: 0.97em;
                    margin-bottom: 1.12rem;
                    max-width: 100%;
                    text-align: left;
                }
            }
            @media (max-width:480px) {
                .service-cod-box {
                    border-radius: 10px;
                    box-shadow: 0 2px 8px 0 rgba(21,41,67,0.06);
                }
                .service-cod-box-inner {
                    flex-direction: column !important;
                    align-items: stretch;
                }
                .service-cod-img-box,
                .service-cod-content-box {
                    width: 100%;
                    max-width: 100%;
                    box-sizing: border-box;
                    padding-left:14px;
                    padding-right:14px;
                    padding-top: 12px;
                    padding-bottom: 12px;
                }
                .service-cod-img-box {
                    display: flex;
                    align-items: stretch;
                }
                .service-cod-img-box img {
                    width: 100% !important;
                    max-width: 100% !important;
                    height: 200px !important;
                    min-width: 0;
                    min-height: 0;
                    max-height: none;
                    object-fit: cover;
                    border-radius: 7px;
                    display: block;
                }
                .service-cod-content-box {
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }
                .service-cod-content-box h2 {
                    font-size: 1.01rem;
                    margin-bottom: 0.7rem;
                }
                .service-cod-content-box p {
                    font-size: 0.94em;
                }
            }
        </style>
        <!-- Responsive min-height for section -->
        <script>
            (function() {
                function updateHeroCODHeight() {
                    var hero = document.getElementById('hero-cod');
                    if (!hero) return;
                    var w = window.innerWidth;
                    if (w <= 480) {
                        hero.style.minHeight = "1050px";
                        hero.style.backgroundPosition = "top center";
                    } else if (w <= 991.98) {
                        hero.style.minHeight = "1400px";
                        hero.style.backgroundPosition = "top center";
                    } else {
                        hero.style.minHeight = "1400px";
                        hero.style.backgroundPosition = "top center";
                    }
                }
                window.addEventListener('resize', updateHeroCODHeight);
                window.addEventListener('DOMContentLoaded', updateHeroCODHeight);
            })();
        </script>
    </section>
 
    <section id="brand-partners" class="brand-partners-section">
        <div class="brand-partners-container" style="display: flex; align-items: stretch; width:100%; flex-direction: row;">
            <!-- Left Section: Text -->
            <div class="brand-partners-left" style="flex: 1; background: transparent; display: flex; flex-direction:column; justify-content:center;">
                <div class="brand-partners-info" style="padding-left:65px;max-width:543px;">
                <style>
                    @media (max-width: 767.98px) {
                        .brand-partners-info { padding-left:35px !important; }
                    }
                </style>
                    <h2 class="mb-3 fw-bold" style="font-size:2.35rem; letter-spacing:-.5px; line-height:1.13; color:#184193;">
                        Effortless Integration Across <span style="color:#1b60d2;">15+ Sales Channels</span>
                    </h2>
                    <p style="color:#2a416a; font-size:1.19em; line-height:1.54; margin-bottom:17px; font-weight:500;">
                        Instantly connect with all major e-commerce platforms—Shopify, WooCommerce, Amazon, Flipkart and more. No tech headaches.
                    </p>
                    <ul style="color:#3d76d0; font-size:1.09em; line-height:1.62; margin-bottom:13px; padding-left:1.3em; list-style:square;">
                        <li>One-click order syncing and management</li>
                        <li>Real-time inventory​ updates, everywhere</li>
                        <li>Centralized dashboard for multi-channel growth</li>
                    </ul>
                    <p style="color:#2567b7; font-size:1.07em;">
                        Expand, automate, and sell smarter—ShipXpeed’s integrations grow with your ambitions.
                    </p>
                </div>
            </div>
            <!-- Right Section: Logos (rotational for desktop, single sliding row for mobile) -->
            <div 
                class="brand-partners-right"
                style="
                    flex:1; 
                    display: flex;  
                    min-height:520px; 
                    position:relative;
                    align-items:center; 
                    justify-content:center;"
                id="brand-partners-right-responsive"
            >
                @php
                    $clientLogos = [
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel8.PNG', 'alt' => 'Company 1'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel9.PNG', 'alt' => 'Company 2'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel10.PNG', 'alt' => 'Company 3'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel11.JPG', 'alt' => 'Company 4'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel23.jpg', 'alt' => 'Company 5'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel13.png', 'alt' => 'Company 6'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel14.webp', 'alt' => 'Company 7'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel15.webp', 'alt' => 'Company 8'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel16.jpg', 'alt' => 'Company 9'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel17.png', 'alt' => 'Company 10'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel18.png', 'alt' => 'Company 11'],
                        ['src' => 'https://shipxpeed.com/assets/website/img/channel/channel22.webp', 'alt' => 'Company 12'],
                    ];
                @endphp

                <div 
                    class="brand-partners-circle-wrapper"
                    style="position:relative; height:480px; width:480px; display: flex; align-items:center; justify-content:center;"
                    id="brand-partners-logos-wrapper"
                >
                    <!-- DESKTOP: Animate circle -->
                    <div id="brand-partner-circle-logos" class="circle-logos-desktop" style="position:absolute; width:100%; height:100%; left:0; top:0; z-index:10; pointer-events:none;">
                        @foreach ($clientLogos as $idx => $logo)
                            <div class="circleOrbitLogoImg"
                                 data-idx="{{$idx}}"
                                 style="position:absolute; width:108px; height:63px; left:0; top:0; display:flex; align-items:center; justify-content:center; transition: transform 0.7s cubic-bezier(.52,.01,.31,.99); will-change:transform;">
                                <img src="{{ asset($logo['src']) }}" alt="{{ $logo['alt'] }}"
                                     class="client-logo"
                                     style="width:98px; height:54px; object-fit:contain; background:#fff; border-radius:18px; box-shadow:0 2px 8px rgba(24,75,117,0.09); border:1.8px solid #f5f8fa; image-rendering: auto;">
                            </div>
                        @endforeach
                    </div>
                    <!-- MOBILE: Single horizontal slider row, infinite slide, margin left and right, NO center logo -->
                    <div id="brand-partner-single-row-logos" class="circle-logos-mobile" style="display:none; width:100%; position:relative; z-index:12; box-sizing: border-box;">
                        <div class="brand-partner-single-row infinite-row-logos" style="overflow:hidden; width:100%; position:relative; box-sizing:border-box; margin-left:6vw; margin-right:6vw;">
                            <div class="logos-slide mobile-logos-slide" style="display: inline-flex; white-space: nowrap;">
                                @foreach(array_merge($clientLogos,$clientLogos) as $logo)
                                    <div class="circleOrbitLogoImgRow" style="width:56px; height:34px; display: inline-flex; align-items: center; justify-content: center; margin-right:10px;">
                                        <img src="{{ asset($logo['src']) }}" alt="{{ $logo['alt'] }}"
                                            class="client-logo"
                                            style="width:52px; height:26px; object-fit:contain; background:#fff; border-radius:12px; box-shadow:0 2px 6px rgba(24,75,117,0.08); border:1.2px solid #f5f8fa; image-rendering: auto;">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <!-- Center logo - hidden on mobile -->
                    <div class="circleCenterLogo mobile-hide" style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); z-index:15;">
                        <div class="semicircle-bg-logo"
                             style="position: relative; width: 134px; height: 127px; display:flex; align-items:center; justify-content:center;">
                            <div class="center-logo-bg-for-mobile"
                                style="
                                position: absolute;
                                width: 130px;
                                height: 130px;
                                border-radius: 50%;
                                background: #ffffff;
                                border: 6px solid #eaf2fd;
                                box-shadow: 0 4px 32px 0 rgba(24,75,117,0.07);
                                left:0;
                                top:0;
                                z-index:1;">
                            </div>
                            <div style="
                                position: relative;
                                width: 100px; height: 100px; 
                                border-radius:50%; 
                                background: none; 
                                z-index: 3; 
                                display:flex; align-items:center; justify-content:center;">
                                <img src="{{ asset('https://shipxpeed.com/assets/website/img/newlogo.PNG') }}" alt="Company Logo" style="width: 97px; height: 97px; object-fit: contain; border-radius: 50%;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            #brand-partners {
                width: 100%;
                background: #f6faff;
                padding: 28px 0 36px 0;
                min-height: 310px;
            }
            .brand-partners-section .section-header h2 {
                margin-bottom: 16px;
                font-size: 2rem;
                text-align:center;
                font-weight: 700;
                color:#1b60d2;
            }
            .semicircle-bg-logo {
                position: relative;
                width: 160px;
                height: 160px;
                display:flex;
                align-items:center;
                justify-content:center;
                overflow: visible;
            }
            .circleOrbitLogoImg img,
            .circleOrbitLogoImgRow img {
                -webkit-backface-visibility: hidden;
                backface-visibility: hidden;
                image-rendering: crisp-edges;
                filter: none !important;
            }
            /* Hide the center logo and show sliding row on mobile */
            @media (max-width: 900px) {
                .brand-partners-container {
                    flex-direction: column !important;
                }
                .brand-partners-right {
                    width: 100% !important;
                    min-height: 100px !important;
                    align-items: center !important;
                    justify-content: center !important;
                    margin: 0 auto !important;
                    display: flex !important;
                }
                .brand-partners-circle-wrapper {
                    width: 100% !important;
                    height: auto !important;
                    min-height: 80px !important;
                    padding-top: 20px !important;
                    padding-bottom: 20px !important;
                }
                #brand-partner-circle-logos.circle-logos-desktop {
                    display: none !important;
                }
                #brand-partner-single-row-logos.circle-logos-mobile {
                    display: block !important;
                }
                .mobile-hide {
                    display: none !important;
                }
            }
            @media (min-width: 901px) {
                #brand-partner-circle-logos.circle-logos-desktop {
                    display: block !important;
                }
                #brand-partner-single-row-logos.circle-logos-mobile {
                    display: none !important;
                }
                .mobile-hide {
                    display: block !important;
                }
            }
            /* Infinite Slide Animation for mobile row */
            @keyframes mobile-logos-slide {
                0% { transform: translateX(0);}
                100% { transform: translateX(-50%);}
            }
            @media (max-width: 900px) {
                .infinite-row-logos .logos-slide {
                    display: inline-flex;
                    white-space: nowrap;
                    min-width: 200%;
                    will-change: transform;
                }
                .infinite-row-logos .mobile-logos-slide {
                    animation: mobile-logos-slide 28s linear infinite;
                }
            }
        </style>
        <script>
        // Responsive centering adjustment for overall block
        function updateBrandPartnersRightCentering() {
            var elem = document.getElementById('brand-partners-right-responsive');
            if (!elem) return;
            if (window.innerWidth <= 900) {
                elem.style.justifyContent = 'center';
                elem.style.alignItems = 'center';
                elem.style.marginLeft = 'auto';
                elem.style.marginRight = 'auto';
                elem.style.display = 'flex';
                elem.style.width = '100%';
                elem.style.minHeight = '100px';
            } else {
                elem.style.justifyContent = 'center';
                elem.style.alignItems = 'center';
                elem.style.marginLeft = '';
                elem.style.marginRight = '';
                elem.style.display = 'flex';
                elem.style.width = '';
                elem.style.minHeight = '520px';
            }
        }
        window.addEventListener('resize', updateBrandPartnersRightCentering);
        window.addEventListener('DOMContentLoaded', updateBrandPartnersRightCentering);

        // Animate circle only on desktop
        function runCircleAnimation() {
            const wrapper = document.getElementById('brand-partner-circle-logos');
            if (!wrapper) return;
            if (window.innerWidth <= 900) return;
            const logos = Array.from(wrapper.getElementsByClassName('circleOrbitLogoImg'));

            function getCircleSettings() {
                let size = Math.min(wrapper.offsetWidth, wrapper.offsetHeight) || 480;
                let r = size * 0.38; // radius for logos' orbit
                let cx = size/2, cy = size/2;
                return { r, cx, cy };
            }
            const logoCount = logos.length;
            const rotationDuration = 40 * 1000;
            let start = performance.now();

            function setLogoPosition(logo, idx, rotationProg) {
                let angleStep = (2*Math.PI) / logoCount;
                let angle = (rotationProg * 2 * Math.PI) + idx * angleStep;
                let { r, cx, cy } = getCircleSettings();

                logo.style.left = (cx + r * Math.cos(angle) - 54) + "px";
                logo.style.top  = (cy + r * Math.sin(angle) - 32) + "px";
                logo.style.opacity = 1;
                logo.style.transform = `scale(1) translateZ(0)`;
                logo.style.visibility = "visible";
                logo.style.pointerEvents = "none";
            }

            function animate(now) {
                if (window.innerWidth <= 900) return;
                let elapsed = now - start;
                let rotationProg = (elapsed % rotationDuration) / rotationDuration;
                for (let i = 0; i < logos.length; ++i) {
                    setLogoPosition(logos[i], i, rotationProg);
                }
                requestAnimationFrame(animate);
            }
            requestAnimationFrame(animate);
        }

        function activateLogoLayoutSwitcher() {
            runCircleAnimation();
        }

        window.addEventListener('DOMContentLoaded', activateLogoLayoutSwitcher);
        window.addEventListener('resize', activateLogoLayoutSwitcher);
        </script>
    </section>
    </section>
    <section class="section-premium-testimonial py-5" style="background: linear-gradient(105deg, #f3f7fa 0%, #cbe6fc 100%); position:relative; z-index:2;">
        <div class="container aos-init aos-animate" data-aos="fade-up" data-aos-delay="100">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center mb-5">
                    <h2 class="display-5 fw-bold" style="color: #1a3353; letter-spacing: -1px;">What Our Clients Say</h2>
                    <p class="lead" style="color: #416384;">Voices from brands that trust Shipxpeed.</p>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="premium-testimonial-slider">
                        <div class="premium-testimonial-list d-flex flex-row gap-4" style="overflow-x:auto; scroll-snap-type:x mandatory;">
                        @foreach($testimonials as $testimonial)
                            <div class="premium-testimonial-card shadow-sm position-relative p-4 rounded-4 bg-white border-0 flex-shrink-0" style="min-width:340px; max-width:370px; scroll-snap-align:center; margin-bottom:12px; transition: box-shadow .2s; border:1px solid #e9eef9;">
                                <span class="premium-quote-icon position-absolute top-0 start-0 translate-middle" style="font-size:2.1rem;color:#54c7ec;opacity:0.18; left:30px; top:20px;">
                                    <i class="fas fa-quote-left"></i>
                                </span>
                                <div class="d-flex flex-row align-items-center mb-3">
                                    <div class="testimonial-img-wrapper rounded-circle bg-light p-1 me-3" style="width:60px; height:60px; box-shadow:0 2px 8px #24344a13;">
                                        <img src="{{ Helper::showImage($testimonial->image, true) }}" alt="Client {{ $loop->iteration }}" class="rounded-circle" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold" style="color: #16436c; font-size:1.1em;">{{ $testimonial->title }}</h6>
                                        @if(isset($testimonial->designation) && $testimonial->designation)
                                            <small class="text-muted" style="font-size:0.95em;">{{ $testimonial->designation }}</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="testimonial-text" style="color: #40667e; font-size:1.03em; line-height:1.54; min-height:110px;">
                                    <span style="font-family:serif; color:#1883bb; font-size:1.6em; font-weight: bold; vertical-align:-5px;">“</span>
                                        {{ $testimonial->description }}
                                    <span style="font-family:serif; color:#1883bb; font-size:1.6em; font-weight: bold; vertical-align:-5px;">”</span>
                                </div>
                            </div>
                        @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .premium-testimonial-slider::-webkit-scrollbar {
                height: 8px;
                background: transparent;
            }
            .premium-testimonial-slider::-webkit-scrollbar-thumb {
                background: #d8eaf7;
                border-radius: 6px;
            }
            .premium-testimonial-card:hover {
                box-shadow: 0 4px 22px 0 #bedffd44;
                border-color: #9ed6f5;
            }
            @media (max-width: 768px) {
                .premium-testimonial-list {
                    gap: 1.2rem !important;
                }
                .premium-testimonial-card {
                    min-width: 85vw !important;
                    max-width: 90vw !important;
                }
            }
        </style>
    </section>

<section id="faq" class="faq-enhanced py-5" style="background: linear-gradient(135deg, #e6f0fd 0%, #f7fafc 100%);">
  <div class="faq-container container">
    <div class="row faq-animated-row align-items-start">
      <!-- Left Column (5) - FAQ Heading and Description -->
      <div class="col-lg-5 aos-init aos-animate d-flex flex-column" data-aos="fade-right" data-aos-duration="900">
        <div class="faq-header mb-4 mb-lg-0 wow fadeInLeft sticky-faq-header align-self-stretch" data-wow-delay="0.3s" style="height:100%;">
            <h3 class="faq-title">Frequently Asked Questions</h3>
            <p class="faq-desc">
              Get instant answers to the most common questions about Shipxpeed.<br>
              Still have doubts? <a href="#contact" class="faq-link">Reach out to us!</a>
            </p>
            <div class="faq-image-animate-wrap">
              <img src="{{ asset('assets/website/img/faq.png') }}" alt="FAQ" 
                class="img-fluid wow pulse faq-image-style" 
                style="animation-delay:0.7s;">
            </div>
        </div>
      </div>
      <!-- Right Column (7) - Accordion -->
      <div class="col-lg-7 aos-init aos-animate d-flex flex-column" data-aos="fade-left" data-aos-duration="900" data-aos-delay="150">
        <div class="accordion beautiful-accordion wow fadeInUp align-self-stretch" id="faqAccordion" data-wow-delay="0.4s">
          @php
            $faqs = [
              [
                "id" => 1, "icon" => "fa-question-circle text-secondary", "title" => "What is Shipxpeed?", "headerTag" => "h6",
                "body" => "<p><strong>Shipxpeed</strong> is a courier aggregator platform that empowers businesses to efficiently navigate their shipping needs across diverse carriers and delivery channels.</p><p>Businesses can seamlessly integrate their shipping operations, access multiple courier services, and streamline the process of sending packages to customers.</p>", 
              ],
              [
                "id" => 2,"icon"=> "fa-cogs text-secondary", "title" => "How does Shipxpeed work?","headerTag" => "h6",
                "body" => "<p>Shipxpeed works by providing a platform where businesses can manage their shipping operations efficiently. It integrates with various courier services, allowing businesses to compare rates, generate shipping labels, track shipments, and streamline the shipping process.</p>
                  <p><strong>Our AI-enabled carrier recommendation</strong>—SmartSelect—analyzes multiple factors, including shipping cost, delivery SLA, pickup performance, POD facilities, and courier serviceability, to recommend the best courier for your shipment.</p>"
              ],
              [
                "id"=>3, "icon"=>"fa-store text-secondary", "title"=>"How can I integrate my eCommerce store with Shipxpeed?","headerTag"=>"h6",
                "body"=>"<p>Shipxpeed provides easy-to-use integration tools and plugins for most major eCommerce platforms. Simply follow the step-by-step guide provided on our website, or reach out to our support team for assistance.</p>"
              ],
              [
                "id"=>4,"icon"=>"fa-shipping-fast text-secondary", "title"=>"Which courier partners are integrated with Shipxpeed?","headerTag"=>"h6",
                "body"=>"<p>Shipxpeed has partnered with a wide range of leading courier companies in India, including <strong>Xpressbees, DTDC, Delhivery</strong>, and many more. This ensures that our users have access to the broadest pincode coverage and the best delivery options available.</p>"
              ],
              [
                "id"=>5,"icon"=>"fa-rupee-sign text-secondary", "title"=>"How will Shipxpeed help in reducing shipping costs?","headerTag"=>"h6",
                "body"=>"<p>Shipxpeed's Shipping Automation platform offers features such as courier prioritisation, bulk shipping actions, and automated workflows. These functionalities collectively help businesses reduce operational overheads and avail the best shipping rates.</p>"
              ],
              [
                "id"=>6,"icon"=>"fa-balance-scale text-secondary", "title"=>"How does Shipxpeed calculate shipping charges?","headerTag"=>"h6",
                "body"=>"<p>Shipping charges are calculated based on factors such as package dimensions, weight, destination, and selected shipping service. Shipxpeed provides <strong>real-time shipping rates</strong> for different carriers. You can check the prices by connecting with us at <strong> sales@shipxpeed.com</strong>.</p>"
              ],
              [
                "id"=>7,"icon"=>"fa-user-shield text-secondary", "title"=>"Does Shipxpeed offer security cover for lost shipments?","headerTag"=>"h6",
                "body"=>"<p>Yes, Shipxpeed offers <strong>security cover for lost shipments</strong> to provide coverage against loss, damage, or theft during transit. For more details, connect with us at <strong>support@shipxpeed.com</strong>.</p>"
              ],
              [
                "id"=>8,"icon"=>"fa-map-marked-alt text-secondary", "title"=>"How does Shipxpeed handle shipping to remote or rural areas?","headerTag"=>"h6",
                "body"=>"<p>Shipxpeed works with its network of courier partners to reach remote or rural areas. While delivery times can vary, Shipxpeed endeavors to ensure <strong>timely and reliable delivery</strong> to all locations within its service network.</p>"
              ],
              [
                "id"=>9,"icon"=>"fa-tags text-secondary", "title"=>"What is the pricing of the platform?","headerTag"=>"h6",
                "body"=>"<p><strong>Shipxpeed is free</strong> for all operating e-commerce sellers, regardless of their current growth phase. You only need to pay freight charges when you ship an order. You can also avail other value-added services by contacting your dedicated shipping advisor or our customer support desk.</p>"
              ]
            ];
          @endphp
          @foreach($faqs as $i => $faq)
          <div class="accordion-item mb-1 border-0 rounded wow fadeInUp faq-animated-item" data-wow-delay="{{ 0.09*$i + 0.3 }}s">
            <<?= $faq['headerTag'] ?> class="accordion-header" id="heading{{$faq['id']}}">
              <button class="accordion-button beautiful-accordion-btn @if($i!=0) collapsed @endif" type="button"
                data-bs-toggle="collapse" data-bs-target="#collapse{{$faq['id']}}" 
                aria-expanded="@if($i==0)true @else false @endif"
                aria-controls="collapse{{$faq['id']}}">
                <span class="faq-question-icon me-2">
                  <i class="fas {{ $faq['icon'] }}"></i>
                </span>
                {{ $faq['title'] }}
              </button>
            </<?= $faq['headerTag'] ?>>
            <div id="collapse{{$faq['id']}}" class="accordion-collapse collapse @if($i==0) show @endif"
              aria-labelledby="heading{{$faq['id']}}" data-bs-parent="#faqAccordion">
              <div class="accordion-body beautiful-accordion-body faq-answer-text">
                {!! $faq['body'] !!}
              </div>
            </div>
          </div>
          @endforeach
        </div>
      </div><!-- End FAQ Column -->
    </div>
  </div>
  <style>
    /* FAQ Enhanced Section Styles */
    .faq-animated-row {
      --wow-animation-delay: .2s;
    }
    /* Stick the FAQ header (image/title/desc) for large screens */
    .sticky-faq-header {
      position: static;
      top: 0;
    }
    @media (min-width: 992px) {
      .sticky-faq-header {
        position: sticky;
        top: 0; /* Sync top of header with accordion */
        z-index: 20;
      }
    }
    .faq-header {
      /* Soft glass morphism effect for wow entrance */
      background: rgba(255,255,255,0.75);
      border-radius: 15px;
      box-shadow: 0 8px 32px #e1eefa40;
      padding: 1.0rem 1.8rem 1.4rem 1.8rem;
      position: relative;
      z-index: 2;
    }
    .faq-title {
      font-size: 1.6rem;
      font-weight: 700;
      margin-bottom: 0.9rem;
      color: #1c3d65;
      letter-spacing: .012em;
    }
    .faq-desc {
      font-size:1.14rem;
      color:#254168;
      margin-bottom: 1.45rem;
    }
    .faq-link {
      color:#129abb; 
      text-decoration: underline;
      font-weight: 600;
    }
    .faq-image-style {
      max-width:400px;
      width:100%;
      height:auto;
      border-radius: 7px;
      box-shadow: 0 15px 54px #19a7d618, 0 4px 32px #004e831a;
      margin-top: 10px;
    }
    .faq-image-animate-wrap {
      text-align: left;
    }
    .faq-animated-row {
      align-items: flex-start;
    }
    @media (max-width:991px) {
      .faq-header {
        margin-bottom: 32px;
        text-align:center;
        padding: 1.1rem 0.7rem 1.7rem 0.7rem;
        /* Remove sticky on tablet/mobile */
        position: static !important;
        top: unset !important;
      }
      .faq-image-animate-wrap {
        text-align: center;
      }
      .sticky-faq-header {
        position: static !important;
        top: unset !important;
      }
    }
    @media (max-width: 767px) {
      .faq-container.container {
        padding-left:9px; padding-right:9px;
      }
      .beautiful-accordion .accordion-body {
        padding: 12px 8px 13px 33px;
        font-size:1.065rem !important;
      }
      .faq-title {
        font-size: 1.44rem;
      }
    }
    .beautiful-accordion {
      background: transparent;
    }
    .beautiful-accordion .accordion-item {
      background: #fff;
      border-radius: 17px !important;
      box-shadow: 0 2px 24px 0 #c3e7ff38;
      transition: box-shadow .3s cubic-bezier(.42,.04,.58,.96), transform 0.14s;
      border: none;
      will-change: box-shadow,transform;
    }
    .beautiful-accordion .accordion-item:hover, .beautiful-accordion .accordion-item:focus-within {
      box-shadow: 0 12px 36px 0 #96ccf64d;
      transform: translateY(-2px) scale(1.01);
    }
    .beautiful-accordion .accordion-item:not(:last-child) {
      margin-bottom: 23px;
    }
    .beautiful-accordion-btn {
      font-weight: 700;
      background: none;
      border: none;
      padding: 13px 21px;
      color: #134e7b;
      transition: background 0.18s cubic-bezier(.46,0,.5,1), color 0.17s;
      box-shadow: none;
      letter-spacing: .002em;
      font-size: 1.18em;
      border-radius: 16px 16px 0 0;
    }
    .beautiful-accordion-btn .faq-question-icon {
      vertical-align: middle;
    }
    .beautiful-accordion-btn:focus {
      box-shadow: 0 0 0 2px #157ead2a;
      outline: none;
    }
    .beautiful-accordion-btn:hover,.beautiful-accordion-btn:active {
      background: #f0f8ff8b;
    }

    /* Highlight active question on expand */
    .beautiful-accordion .accordion-item .accordion-button:not(.collapsed),
    .beautiful-accordion .accordion-item .accordion-collapse.show ~ .accordion-body {
      background: linear-gradient(90deg,#f6fbff 53%,#e3f0fa 97%);
      color: #1563b0;
    }
    .beautiful-accordion .accordion-item .accordion-button:not(.collapsed){
      color:#1563b0 !important;
      border-radius: 16px 16px 0 0;
    }
    .beautiful-accordion .accordion-body {
      background: none;
      padding: 15px 26px 12px 40px;
      transition: color .18s;
    }
    .faq-answer-text {
      font-size: 1.11rem;
      line-height: 1.65;
      color: #23446d;
      font-family: 'Segoe UI', 'Roboto', 'Arial', sans-serif;
      word-break: break-word;
    }
    .beautiful-accordion .accordion-collapse.show .accordion-body,
    .beautiful-accordion .accordion-collapse.collapsing .accordion-body {
      color:#16436c;
      animation: fadeInAccordionBody .38s;
    }
    .accordion-button::after {
      transition: transform 0.19s cubic-bezier(.53,.01,.33,.99);
    }
    /* Custom transition for expand/collapse arrow */
    .accordion-button:not(.collapsed)::after {
      transform: rotate(-180deg) scale(1.15);
    }
    .beautiful-accordion .accordion-button {
      box-shadow:none!important;
    }

    /* Animation Keyframes */
    @keyframes fadeInAccordionBody {
      from { opacity: 0; transform: translateY(14px);}
      to { opacity: 1; transform: translateY(0);}
    }
    /* Animate the entire accordion item on fade in */
    .faq-animated-item {
      animation: fadeInScale 0.74s cubic-bezier(.53,.07,.34,1.01);
    }
    @keyframes fadeInScale {
      0% { opacity: 0; transform: scale(.95); }
      100% { opacity: 1; transform: scale(1);}
    }

    /* Responsive Styles */
    @media (max-width: 991px) {
      .beautiful-accordion {
        margin-top: 18px;
      }
      .faq-header img, .faq-image-style {
        margin:0 auto;
      }
      .faq-answer-text {
        font-size: 1.07rem;
        line-height: 1.62;
      }
    }
    @media (max-width: 767px) {
      .beautiful-accordion .accordion-body {
        padding: 14px 11px 15px 28px;
        font-size:1.04rem !important;
      }
      .beautiful-accordion-btn {
        font-size:1em;
        padding: 10px 13px;
      }
      .faq-answer-text {
        font-size: 0.99rem;
      }
    }
  </style>
 
  <script> if (typeof WOW === "function") { new WOW().init(); } </script>
</section>

<section id="contact" class="contact-section-simple py-5">
  <div class="container">
    <div class="row justify-content-center align-items-stretch">
      <!-- Left: Address / Info -->
      <div class="col-lg-6 col-md-10 mx-auto mb-4 mb-lg-0 d-flex flex-column justify-content-center">
        <div class="mb-4">
          <h2 class="mb-1 responsive-heading">Our Team is Here to Help</h2>
          <p class="text-muted">Contact us for any queries or suggestions.<br>We are here to assist you anytime!</p>
        </div>
        <div class="mb-2">
          <strong>Corporate Office:</strong>
          <div>{{ $site_settings['address'] }}</div>
        </div>
       
        <div class="mb-2">
          <strong>Contact:</strong>
          <div>{{ $site_settings['phone'] }}</div>
        </div>
        <div class="mb-2">
          <strong>Email:</strong>
          <div><a href="mailto:{{ $site_settings['email'] }}">{{ $site_settings['email'] }}</a></div>
        </div>
        <div class="mb-3">
          <strong>Website:</strong>
          <div><a href="https://www.shipxpeed.com" target="_blank">www.shipxpeed.com</a></div>
        </div>
        <div class="social-links mt-3 d-flex gap-2 flex-wrap">
          <a href="{{ $site_settings['twitter'] }}" target="_blank"><i class="bi bi-twitter-x"></i></a>
          <a href="{{ $site_settings['facebook'] }}" target="_blank"><i class="bi bi-facebook"></i></a>
          <a href="{{ $site_settings['instagram'] }}" target="_blank"><i class="bi bi-instagram"></i></a>
          <a href="{{ $site_settings['linkdin'] }}" target="_blank"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>
      <!-- Right: Contact Form -->
      <div class="col-lg-6 col-md-10 mx-auto">
        <div class="simple-contact-form-box p-4 p-md-3 p-lg-4 mx-lg-4">
          <h3 class="text-center mb-3">Get In Touch</h3>

          @if (session('success'))
            <div class="alert alert-success">
              {{ session('success') }}
            </div>
          @endif

          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('getintouch.store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <input type="text" id="name" name="name" class="form-control"
                placeholder="Enter your name" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-control"
                placeholder="Enter your email" required>
            </div>
            <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <input type="text" id="phone" name="phone" class="form-control"
                placeholder="Enter your phone number" required>
            </div>
            <div class="mb-3">
              <label for="service" class="form-label">You Are Here For</label>
              <select id="service" name="service" class="form-control" required>
                <option value="" selected disabled>Choose your requirements</option>
                <option value="logistics">Logistics Solutions</option>
                <option value="shipping">Shipping Services</option>
                <option value="tracking">Order Tracking</option>
              </select>
            </div>
  
            <div class="mt-4">
              <button type="submit" class="btn btn-primary submit-btn-responsive">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <style>
    .responsive-heading {
      font-size: 1.7em;
    }
    @media (max-width: 991.98px) {
      .responsive-heading {
        font-size: 1.35em;
      }
      .simple-contact-form-box {
        margin-top: 2rem;
      }
    }
    @media (max-width: 767.98px) {
      .responsive-heading {
        font-size: 1.15em;
        text-align: center;
      }
      .simple-contact-form-box {
        padding: 1.2rem 0.5rem;
        box-shadow: 0 3px 14px 0 rgba(160,160,160,0.09), 0 1px 4px 0 rgba(160,160,160,0.04);
      }
      .social-links {
        justify-content: center !important;
      }
      .col-lg-6, .col-md-10 {
        padding-left: 6px;
        padding-right: 6px;
      }
      .mb-4 {
        text-align: center;
      }
    }
    @media (max-width: 575.98px) {
      .responsive-heading {
        font-size: 1em;
      }
      .simple-contact-form-box {
        padding: 0.85rem 0.15rem;
      }
      .submit-btn-responsive {
        width: 100%;
        min-width: unset !important;
      }
      .mb-4 {
        font-size: 1em;
      }
    }
    .simple-contact-form-box {
      background: #fff !important;
      border-radius: 12px;
      box-shadow: 0 6px 28px 0 rgba(160,160,160,.14), 0 1.5px 6px 0 rgba(160,160,160,0.10);
      border: none;
      width: 100%;
      max-width: 520px;
      margin: auto;
    }
    .simple-contact-form-box .form-label {
      color: #16436c;
      font-weight: 500;
    }
    .simple-contact-form-box input::placeholder,
    .simple-contact-form-box select,
    .simple-contact-form-box textarea::placeholder {
      color: #98a3ad;
      opacity: 1;
    }
    .btn-primary {
      background: #1883bb;
      border: none;
      font-weight: 600;
      min-width: 140px;
    }
    .btn-primary:hover, .btn-primary:focus {
      background: #145f8b;
    }
    .contact-section-simple {
      background: none !important;
    }
    .social-links a {
      font-size: 1.3em;
      color: #145f8b;
      transition: color 0.2s;
    }
    .social-links a:hover {
      color: #1883bb;
    }
  </style>
</section>
@endsection
