@extends('frontend.website.layout.index')
@section('main_contant')
    <section class="hyperlocal-wrapper">
        <div class="center-content">
            <!-- Center Video -->
            <video autoplay="" muted="" loop="" playsinline="" class="hyperlocal-video-tag">
                <source src="{{ asset('assets/website/img/hyperlocalvideo.mp4') }}" type="video/mp4">

                Your browser does not support the video tag.
            </video>

            <!-- Cards Around Video -->
            <div class="card-box top-left zoom-card">
                <h3>1-Hour Delivery</h3>
                <p>Orders at your doorstep in under an hour.</p>
            </div>

            <div class="card-box top-right zoom-card">
                <h3>Live Tracking</h3>
                <p>Track deliveries in real-time with precision.</p>
            </div>

            <div class="card-box bottom-left zoom-card">
                <h3>Citywide Coverage</h3>
                <p>We reach every part of your city fast.</p>
            </div>

            <div class="card-box bottom-right zoom-card">
                <h3>Secure Handling</h3>
                <p>Your packages are safe and secure with us.</p>
            </div>
        </div>
    </section>

    <section class="hyperlocal-delivery-section py-5" style="background: linear-gradient(135deg, #f8fafd 0%, #f1f4fa 100%);">
        <div class="container">
            <div class="row align-items-center gx-lg-5 gy-5 flex-wrap-reverse">
                <!-- Right Content -->
                <div class="col-lg-6 order-lg-2 aos-init aos-animate" data-aos="fade-left" data-aos-delay="100">
                    <h2 class="fw-bold mb-1">
                        <span class="highlight text-primary">Hyperlocal Delivery</span>
                        <span class="text-secondary" style="font-weight: 400;">– Fast, Reliable &amp; Affordable!</span>
                    </h2>
                    <p class="text-muted lead ">
                        <i class="fas fa-map-marker-alt text-info me-2"></i>
                        Now delivering in <span class="fw-semibold text-dark">20+ cities</span> across India with lightning speed and unbeatable rates.
                    </p>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="rounded-4 border bg-white shadow-sm p-4 h-100 feature-card-pro d-flex flex-column justify-content-between aos-init aos-animate" data-aos="fade-up" data-aos-delay="100">
                                <div class="mb-3">
                                    <span class="feature-icon-container me-2">
                                        <i class="fas fa-bolt text-warning fa-lg"></i>
                                    </span>
                                    <h5 class="fw-bold mb-2">Starting at just ₹50 per delivery</h5>
                                    <p class="text-secondary small mb-0">
                                        Ship faster without burning your budget – perfect for local businesses &amp; e-com sellers.
                                    </p>
                                </div>
                                <a href="#" class="learn-more-link mt-2 text-decoration-none text-primary fw-medium">
                                    Learn More <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rounded-4 border bg-white shadow-sm p-4 h-100 feature-card-pro d-flex flex-column justify-content-between aos-init aos-animate" data-aos="fade-up" data-aos-delay="200">
                                <div class="mb-3">
                                    <span class="feature-icon-container me-2">
                                        <i class="fas fa-city text-success fa-lg"></i>
                                    </span>
                                    <h5 class="fw-bold mb-2">20+ Cities Covered &amp; Growing</h5>
                                    <p class="text-secondary small mb-0">
                                        Expanding rapidly to ensure your products reach customers on time, every time.
                                    </p>
                                </div>
                                <a href="#" class="learn-more-link mt-2 text-decoration-none text-primary fw-medium">
                                    Learn More <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rounded-4 border bg-white shadow-sm p-4 h-100 feature-card-pro d-flex flex-column justify-content-between aos-init aos-animate" data-aos="fade-up" data-aos-delay="300">
                                <div class="mb-3">
                                    <span class="feature-icon-container me-2">
                                        <i class="fas fa-stopwatch text-primary fa-lg"></i>
                                    </span>
                                    <h5 class="fw-bold mb-2">Same-Day &amp; Next-Day Delivery Options</h5>
                                    <p class="text-secondary small mb-0">
                                        Delight your customers with ultra-fast shipping &amp; live tracking.
                                    </p>
                                </div>
                                <a href="#" class="learn-more-link mt-2 text-decoration-none text-primary fw-medium">
                                    Learn More <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rounded-4 border bg-white shadow-sm p-4 h-100 feature-card-pro d-flex flex-column justify-content-between aos-init aos-animate" data-aos="fade-up" data-aos-delay="400">
                                <div class="mb-3">
                                    <span class="feature-icon-container me-2">
                                        <i class="fas fa-box-open text-danger fa-lg"></i>
                                    </span>
                                    <h5 class="fw-bold mb-2">Ideal for Food, Pharma, Fashion &amp; More</h5>
                                    <p class="text-secondary small mb-0">
                                        Versatile for all industries—benefit as a boutique, supermarket, or start-up.
                                    </p>
                                </div>
                                <a href="#" class="learn-more-link mt-2 text-decoration-none text-primary fw-medium">
                                    Learn More <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Left Image -->
                <div class="col-lg-6 order-lg-1 mb-4 mb-lg-0 aos-init aos-animate d-flex justify-content-center" data-aos="fade-right">
                    <div class="shadow-lg rounded-5 overflow-hidden border bg-white">
                        <img src="{{ asset('assets/website/img/hyperlocal.png') }}" alt="Hyperlocal Delivery" class="img-fluid" style="object-fit:contain;">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <style>
        .feature-card-pro {
            transition: box-shadow .2s, transform .2s;
        }
        .feature-card-pro:hover, .feature-card-pro:focus {
            box-shadow: 0 8px 32px 0 rgba(60,60,150,0.09), 0 1.5px 8px 0 rgba(90, 100, 129, 0.08);
            transform: translateY(-5px) scale(1.02);
            border-color: #3572d9;
        }
        .feature-icon-container {
            background: linear-gradient(135deg, #e7f0fd 0%, #f0f3fa 100%);
            border-radius: 12px;
            padding: .5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .learn-more-link {
            font-size: 0.99rem;
            text-decoration: underline;
            transition: color .2s;
        }
        .learn-more-link:hover {
            color: #1c61e7;
        }
    </style>

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
