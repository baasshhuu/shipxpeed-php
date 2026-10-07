@extends('frontend.website.layout.index')
@section('main_contant')
    <main class="main">

        <section id="about" class="about-section position-relative" style="overflow:hidden;padding:0; background: none;">
            <style>
                .about-section-premium {
                    position: relative;
                    border-bottom: 1px solid #e4eaf3;
                    padding: 70px 0 60px 0;
                    /* Show background image clearly with gradient overlay for readability */
                    background: 
                      
                        url('{{ asset('assets/website/img/herosection.PNG') }}') right center no-repeat;
                    background-size: cover;
                }
                /* Remove heavy overlay for better bg visibility */
                .about-section-premium::before {
                    display: none !important;
                }
                .about-premium-header {
                    background:rgba(255,255,255,0.88);
                    border-radius: 16px;
                    box-shadow:0 6px 38px 4px rgba(86,123,219,.07);
                    padding: 38px 36px 24px 36px;
                    margin-bottom: 32px;
                    display:inline-block;
                    max-width: 1184px;
                    position: relative;
                    z-index: 2;
                }
                .about-premium-header .section-subtitle {
                    font-size: 1.1em;
                    font-weight: 600;
                    letter-spacing: 2px;
                    display: inline-block;
                    margin-bottom: 0.1em;
                    text-transform: uppercase;
                    background:linear-gradient(90deg,#667eea,#1883bb);
                    background-clip:text;
                    -webkit-background-clip:text;
                    color:transparent;
                    -webkit-text-fill-color:transparent;
                }
                .about-premium-header .section-title {
                    font-family: 'Inter',sans-serif;
                    font-size: 2.5em;
                    font-weight: 800;
                    color: #122347;
                    letter-spacing: -1px;
                    margin: 0 0 10px 0;
                }
                .about-premium-header .divider {
                    width: 80px;
                    height: 3px;
                    background: linear-gradient(90deg,#2171ce,#35bbd9 60%);
                    border-radius: 2px;
                    margin: 14px auto 20px auto;
                }
                .about-premium-header .section-description {
                    color: #466087;
                    font-size: 1.19em;
                    margin-bottom: 0;
                }

                .about-content-premium {
                    background: #fff;
                    border-radius: 18px;
                    padding: 36px 30px 32px 30px;
                    box-shadow:0 6px 28px 0 rgba(160,160,200,.18),0 1.5px 6px 0 rgba(160,160,160,0.10);
                    position: relative;
                    z-index: 2;
                }
                .about-premium-lead {
                    font-size: 1.12em;
                    color: #23406f;
                    font-weight: 500;
                    margin-bottom: 1.7em;
                }
                .feature-list-premium {
                    list-style: none;
                    padding: 0;
                    margin: 0;
                    display: flex;
                    flex-direction: column;
                    gap: 1.7em;
                }
                .feature-list-premium li {
                    display: flex;
                    align-items: flex-start;
                }
                .feature-icon-premium {
                    width: 48px;
                    height: 48px;
                    border-radius: 14px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-right: 18px;
                    background: linear-gradient(130deg,#eef3ff 18%, #d6eafa 86%);
                    font-size: 1.45em;
                    color: #2171ce;
                    box-shadow: 0 2px 10px 0 rgba(60,120,225,0.035);
                }
                .feature-text-premium h5 {
                    margin: 0 0 2px 0;
                    font-size: 1.12em;
                    color: #1c3774;
                    font-weight: 700;
                }
                .feature-text-premium p {
                    color: #536c97;
                    font-size: .98em;
                    margin: 0;
                }
                .about-card-premium {
                    border-radius: 18px;
                    background: linear-gradient(120deg,#f8fbfe 76%,#eaf0fa 100%);
                    box-shadow: 0 8px 30px 0 rgba(90,130,180,.11);
                    padding:48px 38px 42px 38px;
                    position: relative;
                    overflow: hidden;
                    transition: box-shadow .22s;
                    z-index: 2;
                }
                .about-card-premium:hover {
                    box-shadow: 0 14px 44px 0 rgba(37,79,168,.13);
                }
                .about-card-premium .icon-box-premium {
                    width: 62px;
                    height: 62px;
                    border-radius: 15px;
                    background: linear-gradient(135deg,#39c2ce,#467fdc);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #fff;
                    font-size: 2.1em;
                    margin-bottom: 19px;
                    box-shadow:0 2px 14px 0 rgba(46,116,235,.12);
                }
                .about-card-premium .card-title {
                    font-size: 1.39em;
                    font-weight: 700;
                    color: #263965;
                    margin-bottom: 0.6em;
                }
                .about-card-premium .card-text {
                    color: #405a84;
                    font-size: 1.05em;
                    margin-bottom: 1.8em;
                }
                .about-card-premium .btn-outline-primary {
                    border: 2px solid #2171ce;
                    color: #2171ce;
                    font-weight: 600;
                    width : 130px;
                    font-size: 1em;
                }
                .about-card-premium .btn-outline-primary:hover {
                    background: linear-gradient(90deg,#2171ce,#35bbd9 60%);
                    border-color: #2171ce;
                    color: #fff;
                }
                @media (max-width: 991.98px){
                    .about-section-premium {
                        background:
                            linear-gradient(90deg, rgba(255,255,255,0.97) 65%, rgba(255,255,255,0.77) 97%),
                            url('{{ asset('assets/website/img/herosection.PNG') }}') right center no-repeat;
                        background-size: cover;
                    }
                    .about-premium-header{
                        padding:24px 10px 14px 10px;
                    }
                    .about-card-premium {
                        padding: 34px 14px 30px 14px;
                    }
                    .about-content-premium {
                        padding: 22px 10px 18px 10px;
                    }
                }
                @media (max-width: 767.98px){
                    .about-section-premium { 
                        padding: 40px 0 24px 0;
                        background:
                            linear-gradient(90deg, rgba(255,255,255,0.98) 82%, rgba(255,255,255,0.86) 100%),
                            url('{{ asset('assets/website/img/herosection.PNG') }}') center right no-repeat;
                        background-size: cover;
                    }
                    .about-premium-header .section-title{ font-size: 2em;}
                }
            </style>
            <div class="about-section-premium position-relative">
                <div class="container position-relative" style="z-index:2;">
                    <div class="row justify-content-center">
                        <div class="col-12 d-flex justify-content-center">
                            <div class="about-premium-header text-center shadow aos-init aos-animate" data-aos="fade-up">
                                <span class="section-subtitle">WHO WE ARE</span>
                                <h2 class="section-title">About Shipxpeed</h2>
                                <div class="divider mx-auto"></div>
                                <p class="section-description mb-0">
                                    Welcome to <b>Shipxpeed.com</b>, where we’re reimagining logistics and courier aggregation—making shipping faster, smarter, and more cost-effective for modern e-commerce businesses who demand reliability.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-center mt-2 gy-4 gx-xl-5">
                        <div class="col-lg-6 aos-init aos-animate" data-aos="fade-right" data-aos-delay="120">
                            <div class="about-content-premium h-100 d-flex flex-column justify-content-center">
                                <p class="about-premium-lead mb-3">
                                    Founded by <b>Bashu Upadhyay</b>, a logistics visionary with deep expertise in operations, customer success, and advanced RTO reduction strategies, Shipxpeed empowers online sellers to deliver seamless shipping—no matter their scale.
                                </p>
                                <ul class="feature-list-premium">
                                    <li>
                                        <div class="feature-icon-premium"><i class="fas fa-rocket"></i></div>
                                        <div class="feature-text-premium">
                                            <h5>Revolutionizing Logistics</h5>
                                            <p>Elevating business shipping through cutting-edge technology and creative solutions.</p>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="feature-icon-premium"><i class="fas fa-bolt"></i></div>
                                        <div class="feature-text-premium">
                                            <h5>Smart Shipping</h5>
                                            <p>Data-driven, budget-friendly, rapid shipping—precisely tailored to your e-commerce journey.</p>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="feature-icon-premium"><i class="fas fa-chart-line"></i></div>
                                        <div class="feature-text-premium">
                                            <h5>Business Growth</h5>
                                            <p>Enabling your venture to scale confidently across India and beyond.</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-6 aos-init aos-animate" data-aos="fade-left" data-aos-delay="220">
                            <div class="about-card-premium h-100 d-flex flex-column justify-content-center">
                                <div class="icon-box-premium mb-4"><i class="fas fa-network-wired"></i></div>
                                <h3 class="card-title">Integrated Courier Network</h3>
                                <p class="card-text">
                                    Our platform unifies India’s top courier partners. With real-time comparison, automated workflows, and high transparency, you receive the best rates—and an unmatched, reliable shipping ecosystem. Focus on what matters: growing your business, while we smoothen logistics.
                                </p>
                                <a href="{{ route('about') }}" class="btn btn-outline-primary btn-arrow">
                                    Read More
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission Section (Premium & Professional Look) -->
        <section id="our-mission" class="mission-section bg-gradient py-5" style="background: linear-gradient(120deg,#fafdff 70%,#e0edfa 100%);">
            <div class="container">
                <div class="row justify-content-between flex-lg-row-reverse">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <div class="position-relative overflow-hidden rounded-5 shadow-lg" style="background: #fff;">
                            <img src="{{ asset('assets/website/img/about.png') }}"
                                 alt="Shipping Mission" class="img-fluid p-3 rounded-5"
                                 style="object-fit:cover;min-height:330px;border-radius:2rem;">
                            <div class="position-absolute top-0 start-0 w-100 h-100" 
                                style="background: linear-gradient(155deg,rgba(55,125,255,0.07) 62%,rgba(255,255,255,0.12) 90%); pointer-events:none; border-radius:2rem;">
                            </div>
                        </div>
                        <div style="margin-top:30px;">
                            <a href="{{ route('about') }}" class="btn btn-outline-primary btn-lg "
                                style="background:linear-gradient(96deg,#e8f5ff 66%,#f7fcff 100%);border-width:2px;">
                                Learn About Our Technology
                                <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="p-4 p-lg-5 position-relative" style="z-index:2; margin: 0 auto;">
                            <div class="text-center mb-3">
                                <span class="d-inline-block px-4 py-1 fs-6 fw-semibold mb-1"
                                    style="background:linear-gradient(92deg,#e2edfd 50%,#f6faff 100%);color:#226ec9;border-radius:30px;letter-spacing:1.2px;">
                                    Our Mission
                                </span>
                                <h2 class="fw-bold mb-2" style="background: linear-gradient(91deg,#2d68bb 35%,#6aa5de 100%);-webkit-background-clip: text;-webkit-text-fill-color: transparent;background-clip: text;color:transparent;font-size:1.8rem;">
                                    Empowering Businesses, Simplifying Logistics
                                </h2>
                            </div>
                            <p class="mb-3 text-secondary fs-5 fw-medium" style="letter-spacing:0.01em;">
                                Our mission is to <span style="color: #357ee9; font-weight:600;">eliminate logistics complexities</span> and offer businesses a truly <span style="color:#3ea056; font-weight:600;">affordable</span>, <span style="color:#1aa5c5; font-weight:600;">transparent</span> shipping experience.
                            </p>
                            <ul class="list-unstyled">
                                <li class="d-flex align-items-start mb-2">
                                    <span class="me-3" style="color:#29be9c; font-size:1.15em;">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                    <span class="fw-semibold text-dark">Tech-driven shipping optimization</span>
                                </li>
                                <li class="d-flex align-items-start mb-2">
                                    <span class="me-3" style="color:#0581d1; font-size:1.15em;">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                    <span class="fw-semibold text-dark">Operational cost reduction</span>
                                </li>
                                <li class="d-flex align-items-start mb-2">
                                    <span class="me-3" style="color:#f5a623; font-size:1.15em;">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                    <span class="fw-semibold text-dark">Guaranteed customer satisfaction</span>
                                </li>
                            </ul>
                            <p class="mb-4 text-muted" style="font-size:1em;">
                                We understand the daily challenges faced by sellers – from shipping delays to high costs. Shipxpeed tackles these pain points with premium technology, process transparency, and dedicated support.
                            </p>
                            <!-- <div class="text-center">
                                <a href="{{ route('about') }}" class="btn btn-outline-primary btn-lg "
                                   style="background:linear-gradient(96deg,#e8f5ff 66%,#f7fcff 100%);border-width:2px;">
                                    Learn About Our Technology
                                    <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </section>



        <section id="what-we-do" class="services-premium py-5" style="background:linear-gradient(98deg, #fafdff 70%, #e6f0fb 100%); border-top:1.5px solid #e4eaf3; border-bottom:1.5px solid #e4eaf3;">
            <style>
                .services-premium .premium-section-title {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    margin-bottom: 36px;
                }
                .services-premium .premium-section-title h2 {
                    font-family: 'Inter',sans-serif;
                    font-size: 2.2em;
                    font-weight: 800;
                    letter-spacing: -1.5px;
                    color: #17326b;
                    margin-bottom: 8px;
                    background: linear-gradient(91deg,#2178d5 10%,#47c3ee 90%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }
                .services-premium .premium-section-title .subtitle {
                    font-size: 1.06em;
                    color: #6a92bd;
                    margin-bottom: 0;
                }
                .services-premium .premium-services-row {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 38px 32px;
                    margin-bottom: 38px;
                }
                .services-premium .premium-service-card {
                    background: linear-gradient(105deg,#f7fafd 60%, #eaf1fc 100%);
                    border-radius: 18px;
                    box-shadow: 0 5px 30px 0 rgba(104,153,205,0.08);
                    padding: 38px 30px 30px 30px;
                    text-align: center;
                    transition: box-shadow 0.2s, transform 0.17s;
                    position: relative;
                    border: 1px solid #ecf1f8;
                    flex: 1 1 0;
                    min-width: 0;
                    max-width: 100%;
                }
                /* Only 3 cards per row on lg+, 2 on md, 1 on sm */
                @media (min-width: 992px) {
                    .services-premium .premium-services-row {
                        flex-wrap: nowrap;
                    }
                    .services-premium .premium-service-card {
                        flex-basis: 0;
                        max-width: 33.3333%;
                    }
                }
                @media (max-width: 991.98px) and (min-width: 768px) {
                    .services-premium .premium-services-row {
                        flex-wrap: wrap;
                    }
                    .services-premium .premium-service-card {
                        flex-basis: 48%;
                        max-width: 48%;
                        margin-bottom: 18px;
                    }
                }
                @media (max-width: 767.98px){
                    .services-premium .premium-services-row {
                        flex-direction: column;
                        gap: 18px 0;
                        margin-bottom: 22px;
                    }
                    .services-premium .premium-service-card {
                        padding: 28px 14px 24px 14px;
                        flex-basis: 100%;
                        max-width: 100%;
                        margin-bottom: 0;
                    }
                    .services-premium .premium-service-icon { width: 49px; height: 49px; font-size: 1.45em;}
                }
                .services-premium .premium-service-icon {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg,#37c3eb 1%, #266bce 99%);
                    color: #fff;
                    width: 62px;
                    height: 62px;
                    border-radius: 14px;
                    margin: 0 auto 18px auto;
                    font-size: 2.2em;
                    box-shadow: 0 2px 18px 0 rgba(41,107,178,0.12);
                }
                .services-premium .premium-service-card h3 {
                    font-size: 1.19em;
                    font-weight: 700;
                    color: #183c66;
                    margin-bottom: 13px;
                    margin-top: 10px;
                    font-family: 'Inter',sans-serif;
                }
                .services-premium .premium-service-card p {
                    color: #5777a4;
                    font-size: 1.04em;
                    margin-bottom: 0;
                    min-height: 62px;
                }
            </style>
            <div class="container position-relative" style="z-index:3;">
                <div class="premium-section-title">
                    <h2>What We Do</h2>
                    <span class="subtitle">Comprehensive shipping solutions — reliable, fast, and affordable</span>
                </div>
                <!-- Row 1: First three cards -->
                <div class="premium-services-row">
                    <div class="premium-service-card aos-init aos-animate" data-aos="fade-up" data-aos-delay="100">
                        <div class="premium-service-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <h3>Domestic Shipping</h3>
                        <p>
                            29,000+ pincodes. Fast, secure, and cost-effective delivery across India, ensuring your parcels arrive on time — every time.
                        </p>
                    </div>
                    <div class="premium-service-card aos-init aos-animate" data-aos="fade-up" data-aos-delay="150">
                        <div class="premium-service-icon" style="background: linear-gradient(135deg, #32b8b3 1%, #266bce 99%);">
                            <i class="fas fa-globe"></i>
                        </div>
                        <h3>International Shipping</h3>
                        <p>
                            Ship to 230+ countries. Expand your reach globally with seamless logistics and supportive guidance for cross-border growth.
                        </p>
                    </div>
                    <div class="premium-service-card aos-init aos-animate" data-aos="fade-up" data-aos-delay="200">
                        <div class="premium-service-icon" style="background: linear-gradient(135deg,#fbb63d 1%, #f66363 99%);">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3>Hyperlocal Delivery</h3>
                        <p>
                            Same-day and next-day deliveries in major metro cities — perfect for demanding customers and time-sensitive shipments.
                        </p>
                    </div>
                </div>
                <!-- Row 2: Next three cards -->
                <div class="premium-services-row">
                    <div class="premium-service-card aos-init aos-animate" data-aos="fade-up" data-aos-delay="250">
                        <div class="premium-service-icon" style="background: linear-gradient(135deg,#5dba70 1%, #348ab7 99%);">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <h3>COD &amp; Prepaid Options</h3>
                        <p>
                            Flexible payment modes, including Cash on Delivery — increasing conversions and giving your buyers peace of mind.
                        </p>
                    </div>
                    <div class="premium-service-card aos-init aos-animate" data-aos="fade-up" data-aos-delay="300">
                        <div class="premium-service-icon" style="background: linear-gradient(135deg,#4b93df 1%, #63e5f6 99%);">
                            <i class="fas fa-weight"></i>
                        </div>
                        <h3>No Volumetric Weight</h3>
                        <p>
                            No inflated charges. Benefit from real weight pricing on shipments up to 1 kg, minimizing your shipping costs.
                        </p>
                    </div>
                    <div class="premium-service-card aos-init aos-animate" data-aos="fade-up" data-aos-delay="350">
                        <div class="premium-service-icon" style="background: linear-gradient(135deg,#9b41e2 1%, #39bda7 99%);">
                            <i class="fas fa-phone-volume"></i>
                        </div>
                        <h3>Free NDR Calls</h3>
                        <p>
                            6 months of complimentary NDR (Non-Delivery Report) calls — resolve fake attempts and maximize your order success ratio.
                        </p>
                    </div>
                </div>
            </div>
        </section>


        <section id="founders-vision" style="background:url('{{ asset('assets/website/img/herosection.PNG') }}') right center no-repeat;">
            <div class="container">
                <div class="section-title aos-init aos-animate" data-aos="fade-up">
                    <h2 style="color:white;">Our Founder's Vision</h2>
                </div>
                <div class="vision-content">
                    <div class="vision-image aos-init aos-animate" data-aos="fade-right">
                        <img src="{{ asset('assets/website/img/bashu.jpg') }}" alt="Founder's Vision" style="height:470px;">
                    </div>
                    <div class="vision-text aos-init aos-animate" data-aos="fade-left" data-aos-delay="100" style="color:white;">
                        <p>"When I started Shipxpeed, I envisioned a platform that would eliminate the pain points of e-commerce shipping - the hidden costs, the unreliable services, the lack of transparency. I wanted to create a solution that would level the playing field for small and medium businesses, giving them access to the same logistics capabilities as large enterprises."</p>
                        <p>"Today, Shipxpeed is more than just a shipping aggregator. We're a growth partner for e-commerce businesses, helping them reduce RTOs, improve delivery success rates, and ultimately build trust with their customers through reliable fulfillment."</p>
                        <p>"Our vision is to become the operating system for e-commerce logistics in India, integrating seamlessly with every aspect of the order fulfillment process to create a truly frictionless experience for sellers."</p>

                        <div class="vision-contact mt-4" >
                          <p><i class="fas fa-envelope me-2"></i>
                            <a href="mailto:Bashu@shipxpeed.com" style="color:white;">Bashu@shipxpeed.com</a>
                          </p>

                          <p><i class="fas fa-phone-alt me-2"></i>
                            <a href="tel:+916396697722" style="color:white;">+91-6396697722</a>
                          </p>

                          <p><i class="fab fa-linkedin me-2"></i>
                            <a href="https://www.linkedin.com/in/bashu-upadhyay-18b115240?utm_source=share&amp;utm_campaign=share_via&amp;utm_content=profile&amp;utm_medium=ios_app" target="_blank" style="color:white;">Bashu Upadhyay - LinkedIn</a>
                          </p>
                        </div>

                      </div>


                </div>
            </div>
        </section>

        <!-- <section id="founders-vision" class="py-5">
            <div class="container">
              <div class="section-title mb-4 aos-init aos-animate" data-aos="fade-up">
                <h2>From the Desk of our Chief Investor…</h2>
              </div>
              <div class="row align-items-center">
                <div class="col-md-8 aos-init aos-animate" data-aos="fade-right">
                  <p>
                    To become the most trusted partner for our customers by continuously anticipating their needs, delivering exceptional experiences, and building lasting relationships that empower them to succeed. While others strive to maximize their profits by adding more and more volume to their transactions, we endeavor to be known as the most Customer-Centric partner by focusing on the Quality of Service we deliver to our customers. We strive to create value through innovative solutions and personalized service, placing the customer at the heart of everything we do, thus prioritizing strong, long-term relationships.
                  </p>
                </div>

                <div class="col-md-4 aos-init aos-animate" data-aos="fade-left" data-aos-delay="100">
                  <div class="card border-1 shadow-sm text-center">
                    <img src="{{ asset('assets/website/img/trusty.jpeg') }}" class="card-img-top img-fluid rounded-circle mx-auto d-block mt-3" alt="Founder's Vision" style="width: 120px; height: 120px; object-fit: cover;">
                    <div class="card-body">
                      <h5 class="card-title mb-1"><strong>Pankaj Roy</strong></h5>
                      <p class="card-text mb-0">MCA / IT Expert</p>
                      <p class="card-text"><small><strong>IIT Delhi Alumnus</strong></small></p>
                    </div>
                  </div>
                </div>

              </div>
            </div>
        </section> -->

        <section id="chooseus" class="chooseus section" style="background: linear-gradient(99deg, #fafdff 70%, #e6f0fb 100%); border: 1.5px solid #e4eaf3; box-shadow: 0 8px 40px rgba(40,120,210,0.07);">
            <style>
                .chooseus-premium-title {
                    font-family: 'Inter',sans-serif;
                    font-weight: 800;
                    font-size: 2.1rem;
                    letter-spacing: -0.5px;
                    background: linear-gradient(90deg,#226ec9 30%,#40b7ed 90%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                }
                .chooseus-premium-subtitle {
                    font-size: 1.14rem;
                    color: #4b739a;
                    font-weight: 500;
                    padding-bottom: 4px;
                    letter-spacing: 0.01em;
                }
                .chooseus-cards-row {
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: center;
                    gap: 36px 28px;
                    margin-top: 38px;
                }
                .chooseus-card {
                    background: linear-gradient(104deg,#ffffff 68%, #eaf3fb 100%);
                    border-radius: 20px;
                    box-shadow: 0 8px 28px 0 rgba(64,131,187,0.11);
                    padding: 36px 26px 30px 26px;
                    text-align: center;
                    border: 1.2px solid #e6eaf3;
                    min-width: 260px;
                    flex: 1 1 220px;
                    max-width: 290px;
                    transition: box-shadow 0.18s, transform 0.18s;
                }
                .chooseus-card:hover {
                    box-shadow: 0 18px 36px 0 rgba(64,131,187,0.17);
                    transform: translateY(-9px) scale(1.03);
                }
                .chooseus-icon {
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 18px auto;
                    background: linear-gradient(88deg,#eaf1fc 60%, #d5eaff 100%);
                    font-size: 2.2em;
                    color: #2272e8;
                    box-shadow: 0 4px 20px 0 rgba(55,161,221,0.10);
                }
                .chooseus-card-title {
                    font-size: 1.14em;
                    font-weight: 700;
                    color: #183153;
                    margin-bottom: 0.7em;
                    letter-spacing: 0.01em;
                }
                .chooseus-card-desc {
                    color: #5c6c86;
                    font-size: 1.06em;
                    font-weight: 500;
                    letter-spacing: 0.01em;
                }
                @media (max-width: 992px) {
                    .chooseus-cards-row {
                        flex-wrap: wrap;
                        gap: 22px;
                    }
                    .chooseus-card {
                        min-width: 230px;
                        max-width: 100%;
                    }
                }
                @media (max-width: 640px) {
                    .chooseus-cards-row {
                        flex-direction: column;
                        gap: 18px;
                        margin-top: 22px;
                    }
                    .chooseus-card {
                        padding: 26px 12px 18px 12px;
                        min-width: 0;
                    }
                }
            </style>

            <div class="container">
                <div class="row justify-content-center aos-init aos-animate" data-aos="fade-down">
                    <div class="col-xl-8 col-lg-9 text-center">
                        <div class="chooseus-premium-title">Why Choose Shipxpeed?</div>
                        <div class="chooseus-premium-subtitle mb-0">
                            Reliable, cost-effective, and technology-driven shipping solutions tailored for businesses of all sizes.
                        </div>
                    </div>
                </div>

                <div class="chooseus-cards-row mt-4">

                    <!-- Affordable Shipping Rates -->
                    <div class="chooseus-card aos-init aos-animate" data-aos="fade-up" data-aos-delay="100">
                        <div class="chooseus-icon">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="chooseus-card-title">Affordable Shipping Rates</div>
                        <div class="chooseus-card-desc">
                            Starting at <span style="color:#2272e8; font-weight:600">just ₹21 for 500g shipments</span>, making it a budget-friendly option for businesses.
                        </div>
                    </div>
                    <!-- Pan-India & Global Coverage -->
                    <div class="chooseus-card aos-init aos-animate" data-aos="fade-up" data-aos-delay="200">
                        <div class="chooseus-icon">
                            <i class="fas fa-globe-asia"></i>
                        </div>
                        <div class="chooseus-card-title">Pan-India &amp; Global Coverage</div>
                        <div class="chooseus-card-desc">
                            Extensive reach with reliable domestic &amp; international shipping capabilities.
                        </div>
                    </div>
                    <!-- Tech-Driven Dashboard -->
                    <div class="chooseus-card aos-init aos-animate" data-aos="fade-up" data-aos-delay="300">
                        <div class="chooseus-icon">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <div class="chooseus-card-title">Tech-Driven Dashboard</div>
                        <div class="chooseus-card-desc">
                            Simplified order management &amp; real-time tracking with our user-friendly panel.
                        </div>
                    </div>
                    <!-- Best Courier Partners -->
                    <div class="chooseus-card aos-init aos-animate" data-aos="fade-up" data-aos-delay="400">
                        <div class="chooseus-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="chooseus-card-title">Best Courier Partners Under One Roof</div>
                        <div class="chooseus-card-desc">
                            Choose from multiple trusted courier partners to meet your unique shipping needs.
                        </div>
                    </div>
                </div>
            </div>
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


    </main>
@endsection
