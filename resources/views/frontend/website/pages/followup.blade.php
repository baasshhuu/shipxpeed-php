@extends('frontend.website.layout.index')
@section('main_contant')
    <section class="rto-section-hero-no-gradient position-relative py-5">
        <div class="rto-hero-bg-img"></div>
        <div class="rto-plain-bg"></div>
        <div class="container position-relative z-2">
            <div class="row align-items-center mb-5">
                <!-- Left Side Content -->
                <div class="col-lg-5 rto-content mt-4 text-center text-lg-start d-flex flex-column align-items-center align-items-lg-start">
                    <h1 class="rto-headline mb-3">
                        Reduce RTO with <span class="text-gradient-plain">Shipxpeed’s Automated NDR Follow-Ups</span>
                    </h1>
                    <p class="rto-subtitle mb-4" style="color:#abb2b9;">
                        Minimize undelivered orders with <span class="fw-semibold text-primary-60">Shipxpeed</span>. Our automated follow-ups via 
                        <span class="icon-highlight"><i class="bi bi-whatsapp"></i> WhatsApp</span>, 
                        <span class="icon-highlight"><i class="bi bi-chat-left-text"></i> SMS</span>, and 
                        <span class="icon-highlight"><i class="bi bi-telephone-forward"></i> Calls</span>
                        help in getting customer responses, enabling redelivery attempts and reducing return rates.
                    </p>
                    <a href="{{ route('about') }}" class="btn btn-lg hero-learn-btn fw-bold rounded-pill px-4 py-2 shadow-sm mt-2">Learn More</a>
                </div>
                <!-- Right Side Image -->
                <div class="col-lg-7 rto-image text-center position-relative z-3">
                    <img src="{{ asset('assets/website/img/ndrhomepage.png') }}"
                         alt="Automated Follow-Ups"
                         class="img-fluid wow fadeInRight" style="max-width: 92%; border-radius: 16px;">
                </div>
            </div>
        </div>
        <style>
            /* Responsive background image as a pseudo-element or absolutely positioned div */
            .rto-section-hero-no-gradient {
                min-height: 500px;
                position: relative;
                overflow: hidden;
                color: #f4f9ff;
                background: none !important;
            }
            .rto-hero-bg-img {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                background-image: url('{{ asset('assets/website/img/heroSection.png') }}');
                background-position: center center;
                background-repeat: no-repeat;
                background-size: cover;
                z-index: 0;
                pointer-events: none;
            }
            .rto-plain-bg {
                position: absolute;
                inset: 0;
                z-index: 1;
                background: rgba(10,24,48,0.56);
                pointer-events: none;
            }
            .rto-content {
                z-index: 2;
            }
            .rto-headline {
                font-size: 2.2em;
                color: #f4f9ff;
                font-weight: 800;
                letter-spacing: .01em;
                line-height: 1.13;
                text-shadow: 0 2px 24px rgba(12,24,48,0.17), 0 1.5px 6px rgba(0,0,0,0.09);
            }
            .text-gradient-plain {
                color: #25d6f7;
                font-weight: 900;
                letter-spacing: .01em;
            }
            .rto-subtitle {
                color: white;
                font-size: 1.17em;
                line-height: 1.6;
                text-shadow: 0 1.5px 6px rgba(14,45,84,0.08);
                margin-bottom: 17px;
            }
            .icon-highlight {
                color: #40c3eb;
                background: rgba(240,250,255,0.10);
                border-radius: 0.6em;
                padding: 0.12em 0.53em;
                margin-right: 0.17em;
                margin-left: 0.08em;
                font-weight: 500;
                display: inline-block;
            }
            .hero-learn-btn {
                background: #16307a;
                color: #fff;
                font-size: 1.13em;
                border: none;
                box-shadow: 0 2.5px 12px 0 rgba(52,138,206,.20);
                transition: background .16s, transform .14s;
                letter-spacing: .01em;
            }
            .hero-learn-btn:hover, .hero-learn-btn:focus {
                background: #1587bb;
                color: #edfcff;
                transform: translateY(-2px) scale(1.04);
            }
            .text-primary-60 {
                color: #71cfff !important;
            }
            @media (max-width: 991.98px){
                .rto-section-hero-no-gradient {
                    min-height: 430px;
                    padding-top: 34px !important;
                    padding-bottom: 34px !important;
                }
                .rto-headline {
                    font-size: 1.38em;
                }
            }
            @media (max-width: 767.98px) {
                .rto-section-hero-no-gradient .row {
                    gap: 1.5rem 0;
                }
                .hero-illustration {
                    margin-top: 0;
                }
                .rto-headline {
                    font-size: 1.05em;
                }
                .rto-content, .rto-section-hero-no-gradient {
                    text-align: center!important;
                    align-items: center !important;
                }
                .rto-hero-bg-img {
                    background-position: top center;
                }
            }
        </style>
    </section>

    <section class="ndr-section py-5" style="background: linear-gradient(90deg, #f3f9ff 0%, #eaf6ff 100%); width:100vw; position:relative; left:50%; right:50%; margin-left:-50vw; margin-right:-50vw;">
        <div class="container" style="max-width:1200px;">
            <div class="row">
                <div class="col-lg-12">
                    <div class="mb-4">
                        <span class="badge rounded-pill bg-primary-50 text-primary px-3 py-2" style="font-size:1.07em; letter-spacing:.04em;">NDR Explained</span>
                    </div>
                    <h2 class="ndr-headline mb-3 fw-bold" style="font-size:1.5rem; letter-spacing:-.01em;">What is <span class="text-primary">NDR Follow-Up</span>?</h2>
                    <p class=" mb-3 text-muted" style="font-size:1.22rem; line-height:1.7;">
                        <strong>Non-Delivery Report (NDR) follow-up</strong> is the systematic process of resolving failed deliveries by proactively contacting your customers and taking the right action—be it <span class="text-primary fw-semibold">re-attempting delivery</span>, <span class="text-primary fw-semibold">modifying addresses</span>, or <span class="text-danger">returning orders</span> if needed.
                    </p>
                    <p class="ndr-highlight-text" style="font-size:1.13rem; background:rgba(16,120,180,0.11); border-radius:14px; padding:18px 13px;">
                        <i class="bi bi-lightning-charge-fill text-primary" style="font-size:1.3em; vertical-align:-2px;"></i>
                        <span class="text-dark ms-2">Shipxpeed’s <span class="fw-semibold text-primary">automated NDR follow-up system</span>
                        ensures every failed delivery triggers an instant follow-up —
                        <span class="text-success">reducing RTO</span> &amp; <span class="text-primary">maximizing your successful deliveries!</span>
                        </span>
                    </p>
                </div>
            </div>
        </div>
        <style>
            .bg-primary-50 { background-color: #e5f2fc!important; color: #136daa!important; }
            .ndr-lead strong { color: #183153; }
            .ndr-highlight-text .bi { margin-right: 0.25em; }
            .ndr-section {
                width: 100vw;
                position: relative;
                left: 50%;
                right: 50%;
                margin-left: -50vw;
                margin-right: -50vw;
            }
            @media (max-width: 1600px){
                .ndr-section .container { max-width: 100%; }
            }
            @media (max-width: 991.98px) {
                .ndr-headline { font-size: 1.48rem; }
                .ndr-lead { font-size: 1.03rem;}
            }
            @media (max-width: 767.98px) {
                .ndr-section { padding: 32px 0 22px 0;}
                .ndr-headline { font-size: 1.10rem; }
                .ndr-highlight-text { font-size: 1.01rem; padding:14px 7px; }
            }
        </style>
    </section>

    <section class="ndr-steps-enhanced " style="background:  linear-gradient(90deg, #f3f9ff 0%, #eaf6ff 100%);">
        <div class="container">
            <div class=" mb-3">
                <h2 class="ndr-steps-title fw-bold" style="font-size:1.5rem;">
                    <span class="text-primary"><i class="bi bi-diagram-3-fill me-2"></i>How Shipxpeed’s NDR Follow-Up Works</span>
                </h2>
             
            </div>
            <div class="row g-4 align-items-stretch ndr-steps-row">
                <!-- Step 1 -->
                <div class="col-md-3 ndr-step-col">
                    <div class="ndr-step-card shadow-sm h-100 ">
                        <div class="ndr-step-circle mb-3 mx-auto">
                            <i class="bi bi-bell-fill"></i>
                        </div>
                        <div class="ndr-step-number mb-2 text-center">Step 1</div>
                        <h5 class="ndr-step-head mb-2">Instant NDR Notification</h5>
                        <p class="ndr-step-desc mb-0">Failed deliveries are <span class="fw-semibold text-danger">flagged</span>, and an alert is generated.</p>
                    </div>
                </div>
                <!-- Step 2 -->
                <div class="col-md-3 ndr-step-col">
                    <div class="ndr-step-card shadow-sm h-100 ">
                        <div class="ndr-step-circle mb-3 mx-auto" style="background:#6ce6c2;">
                            <i class="bi bi-chat-text-fill"></i>
                        </div>
                        <div class="ndr-step-number mb-2 text-center">Step 2</div>
                        <h5 class="ndr-step-head mb-2">Automated Follow-Ups</h5>
                        <p class="ndr-step-desc mb-0">Customers are contacted via <span class="fw-semibold text-success">WhatsApp</span>, <span class="text-primary">SMS</span>, and <span class="text-warning fw-semibold">calls</span>.</p>
                    </div>
                </div>
                <!-- Step 3 -->
                <div class="col-md-3 ndr-step-col">
                    <div class="ndr-step-card shadow-sm h-100 ">
                        <div class="ndr-step-circle mb-3 mx-auto" style="background:#ffe082;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="ndr-step-number mb-2 text-center">Step 3</div>
                        <h5 class="ndr-step-head mb-2">Action-Based Resolution</h5>
                        <p class="ndr-step-desc mb-0">Customers select <span class="text-info fw-semibold">redelivery</span>, <span class="fw-semibold text-primary">address update</span>, or <span class="text-danger">cancellation</span>.</p>
                    </div>
                </div>
                <!-- Step 4 -->
                <div class="col-md-3 ndr-step-col">
                    <div class="ndr-step-card shadow-sm h-100">
                        <div class="ndr-step-circle mb-3 mx-auto" style="background:#b1e4fe;">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div class="ndr-step-number mb-2 text-center">Step 4</div>
                        <h5 class="ndr-step-head mb-2">Courier Coordination</h5>
                        <p class="ndr-step-desc mb-0">The selected action is swiftly communicated to courier partners.</p>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .ndr-steps-title {
                letter-spacing: -0.02em;
                font-family: "Inter", blinkmacsystemfont, Arial, sans-serif;
            }
            .ndr-steps-underline {
                width: 74px;
                height: 4px;
                border-radius: 2px;
                background: linear-gradient(90deg, #169ad9 60%, #1bb67c);
            }
            .ndr-steps-row {
                margin-top: 0;
                margin-bottom: 0;
            }
            .ndr-step-col {
                display: flex;
            }
            .ndr-step-card {
                background: #fff;
                border-radius: 18px;
                padding: 32px 18px 22px 18px;
                transition: box-shadow .19s, transform .16s;
                border: 1px solid #e3edf8;
                position: relative;
                z-index: 1;
                min-width: 170px;
            }
            .ndr-step-card:hover {
                box-shadow: 0 4px 22px 0 rgba(17,94,200,0.09), 0 1.5px 7px 0 rgba(16,105,187,0.12);
                transform: translateY(-3px) scale(1.027);
            }
            .ndr-step-circle {
                width:50px; height:50px;
                background: #e5f2fc;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.55em;
                color: #176dbb;
                box-shadow:0 2px 10px 0 rgba(156,199,242,.12);
                transition: background 0.13s;
            }
            .ndr-step-number {
                font-size: 1.07em;
                color: #81b6e8;
                font-weight:600;
                letter-spacing:0.015em;
            }
            .ndr-step-head {
                font-size: 1.15em;
                color: #11436c;
                font-weight: 700;
                margin-bottom: 7px;
                letter-spacing:0.02em;
            }
            .ndr-step-desc {
                font-size: 1.05em;
                color: #537499;
                line-height: 1.45;
            }

            @media (max-width: 991.98px) {
                .ndr-steps-title { font-size:1.37rem; }
                .ndr-step-card { padding: 22px 7px 16px 7px; }
                .ndr-step-desc { font-size: 1em; }
                .ndr-step-head { font-size: 1em; }
            }
            @media (max-width: 767.98px) {
                .ndr-steps-title { font-size:1.07rem;}
                .ndr-step-row { gap: 10px 0;}
                .ndr-step-card { min-width:120px; padding: 16px 4px 11px 4px;}
                .ndr-step-circle { width:38px;height:38px;font-size:1.1em;}
                .ndr-step-number { font-size: 1em;}
            }
            @media (max-width: 575.98px) {
                .ndr-steps-title { font-size:1em;}
                .ndr-step-card { min-width: auto;}
                .ndr-steps-underline { width: 44px; height: 3px;}
            }
        </style>
    </section>

    <section id="chooseus" class="chooseus section" style="position: relative; background: url('{{ asset('assets/website/img/heroSection.png') }}') center center / cover no-repeat; padding: 64px 0 68px 0; overflow: hidden;">
        <div class="chooseus-bg-overlay" style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(24, 69, 130, 0.10);z-index:0;"></div>
            <div class="container position-relative" style="z-index:2;">
                <div class="row justify-content-center aos-animate aos-init" data-aos="zoom-out">
                    <div class="col-xl-7 col-lg-9 text-center">
                        <h2 class="chooseus-title mb-4" style="font-weight: 800; color:white; letter-spacing: 0.01em; text-shadow: 0 2px 16px #fff5;">Why Choose Shipxpeed for NDR Management?</h2>
                    </div>
                </div>
                <div class="row gy-4 mt-2 ">
                    <!-- Row 1: Order Resolution & Automated Communication -->
                    <div class="col-12 col-md-6 d-flex aos-init aos-animate mb-4 mb-md-0" data-aos="zoom-in" data-aos-delay="100">
                        <div class="premium-card p-4 rounded-4 flex-fill h-100 border-0 transition mx-auto"
                             style="background:transparent; box-shadow: 0 8px 40px 0 rgba(50, 150, 255, 0.15); 
                                    transition: box-shadow .17s, transform .14s; border:1.2px solid rgba(255,255,255,0.15);">
                           
                            <h4 class="chooseus-card-title mb-1 mt-1 fw-bold" style="color:#fff;">Order Resolution</h4>
                            <p class=" mb-1" style="font-size:1.05em; color:#fff; text-shadow:0 1.5px 8px rgba(30,30,80,0.10);">
                               Reduce RTO with proactive follow-ups. Our system engages customers through timely notifications, helping you recover undelivered orders efficiently.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 d-flex aos-init aos-animate mb-4 mb-md-0" data-aos="zoom-in" data-aos-delay="200">
                        <div class="premium-card p-4 rounded-4 flex-fill h-100 border-0 transition mx-auto"
                             style="background:transparent; box-shadow: 0 8px 40px 0 rgba(50, 150, 255, 0.15); 
                                    transition: box-shadow .17s, transform .14s; border:1.2px solid rgba(255,255,255,0.15);">
                           
                            <h4 class="chooseus-card-title mb-1 mt-1 fw-bold" style="color:#fff;">Automated Communication</h4>
                            <p class=" mb-1" style="font-size:1.05em; color:#fff; text-shadow:0 1.5px 8px rgba(30,30,80,0.10);">
                               Keep customers informed with updates through WhatsApp, SMS, and calls, ensuring timely responses and improved delivery success.
                            </p>
                        </div>
                    </div>
                </div>
              
                <div class="row gy-4 mt-2">
                    <!-- Row 2: Improved Delivery Success Rates & Reduced Losses -->
                    <div class="col-12 col-md-6 d-flex aos-init aos-animate mb-4 mb-md-0" data-aos="zoom-in" data-aos-delay="300">
                        <div class="premium-card p-4 rounded-4 flex-fill h-100 border-0 transition mx-auto"
                             style="background:transparent; box-shadow: 0 8px 40px 0 rgba(50, 150, 255, 0.15); 
                                    transition: box-shadow .17s, transform .14s; border:1.2px solid rgba(255,255,255,0.15);">
                           
                            <h4 class="chooseus-card-title mb-1 mt-1 fw-bold" style="color:#fff; text-shadow:0 1.5px 10px rgba(44,56,85,0.09);">
                                Delivery Success Rates
                            </h4>
                            <p class=" mb-1" style="font-size:1.05em; color:#fff; text-shadow:0 1.5px 8px rgba(30,30,80,0.10);">
                                Reduce the chances of lost shipments with proactive communication and efficient follow-ups.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 d-flex aos-init aos-animate mb-4 mb-md-0" data-aos="zoom-in" data-aos-delay="400">
                        <div class="premium-card p-4 rounded-4 flex-fill h-100 border-0 transition mx-auto"
                             style="background:transparent; box-shadow: 0 8px 40px 0 rgba(50, 150, 255, 0.15); 
                                    transition: box-shadow .17s, transform .14s; border:1.2px solid rgba(255,255,255,0.15);">
                    
                            <h4 class="chooseus-card-title mb-1 mt-1 fw-bold" style="color:#fff; text-shadow:0 1.5px 10px rgba(44,56,85,0.10);">Reduced Losses</h4>
                            <p class=" mb-1" style="font-size:1.05em; color:#fff; text-shadow:0 1.5px 8px rgba(30,30,80,0.10);">
                                Lower return shipping costs by minimizing undelivered orders through timely follow-ups.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            #chooseus.chooseus.section {
                margin-bottom: 0 !important;
            }
            .chooseus-card {
                border: none;
                background: #fff;
                border-radius: 22px;
                box-shadow: 0 5px 22px 0 rgba(24, 131, 187, 0.07), 0 1px 6px 0 rgba(24, 131, 187, 0.05);
                transition: box-shadow .21s, transform .16s;
                position: relative;
                min-height: 340px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                cursor: pointer;
                overflow: hidden;
            }
            .chooseus-card:hover {
                transform: translateY(-7px) scale(1.025);
                box-shadow: 0 12px 40px 0 rgba(37,128,198,0.13), 0 2.5px 11px 0 rgba(24,131,187,0.08);
            }
            .chooseus-icon-wrapper {
                border: 3.5px solid #e9f3fa;
                box-shadow: 0 2px 8px 0 rgba(24,131,187,0.05);
            }
            .chooseus-card-title {
                font-size: 1.20em;
                letter-spacing: 0.01em;
                font-weight: bold;
            }
            .chooseus-card-desc {
                font-size: 1.08em;
                letter-spacing: 0.003em;
                color: #537499 !important;
                text-align: center;
            }
            @media (max-width: 991.98px) {
                .chooseus-card { min-height: 265px; padding:2em 1.5em !important;}
                .chooseus-title { font-size: 1.28em;}
            }
            @media (max-width: 767.98px) {
                #chooseus.section { padding-bottom: 18px !important; padding-top: 35px !important;}
                .chooseus-title { font-size: 1em;}
                .chooseus-card { min-height: 230px; }
            }
            @media (max-width: 575.98px) {
                .chooseus-title { font-size:0.93em;}
                .chooseus-card { min-height: 185px; padding:.6em .4em !important;}
                .chooseus-icon-wrapper { width: 42px !important; height: 42px !important;}
            }
        </style>
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
          <strong>Registered Address:</strong>
          <div>{{ $site_settings['secondaddress'] }}</div>
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
