@extends('frontend.website.layout.index')
@section('main_contant')
    <section 
        class="rto-section" 
        style="background-image: url('{{ asset('assets/website/img/heroSection.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            ">
        <style>
            .rto-section .rto-content h2 {
                color: #fff;
                font-weight: 700;
                font-size: 2.2rem;
                line-height: 1.25;
                letter-spacing: 0.01em;
                margin-bottom: 2.1rem;
                text-shadow: 0 3px 8px rgba(20, 65, 119, 0.13);
            }
            .rto-section .rto-content p {
                color: #e2e9f3;
                font-size: 1.18rem;
                font-weight: 400;
                letter-spacing: 0.001em;
                margin-bottom: 0;
                text-shadow: 0 1.5px 6px rgba(24, 46, 83, 0.05);
            }
            .rto-section .rto-image img {
                max-width: 94%;
                border-radius: 16px;
              
            }
            @media (max-width: 991.98px) {
                .rto-section .rto-content h2 { font-size: 1.4rem; }
                .rto-section .rto-content p { font-size: 1rem; }
            }
            @media (max-width: 767.98px) {
                .rto-section .rto-content h2 { font-size: 1.15rem; }
                .rto-section .rto-content p { font-size: 0.92rem; }
            }
        </style>
        <div class="container">
            <div class="row align-items-center mb-3">
                <!-- Left Side Content -->
                <div class="col-lg-5 rto-content mb-2 text-center text-lg-start">
                    <h2>Reliable B2B Logistics Solutions for Bulk &amp; Heavy Shipments</h2>
                    <p>Ship your bulky shipments across India with Shipxpeed. Our logistics solutions help you deliver goods
                        to your desired destination.</p>
                </div>
                <!-- Right Side Image -->
                <div class="col-lg-7 rto-image text-center">
                    <img src="{{ asset('assets/website/img/ltlshipment.png') }}" alt="Automated Follow-Ups">
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="services section light-background" style="background-color: #cae3fb;">

        <!-- Section Title -->
        <div class="container section-title aos-init aos-animate" data-aos="fade-up">
          <h3>Why Choose Shipxpeed for B2B LTL Shipping?</h3>
          <p>Shipxpeed provides Less Than Truckload (LTL) shipping for businesses transporting bulk inventory and heavy shipments (10kg and above). Our platform connects you with multiple logistics partners to offer suitable shipping solutions across India.</p>
        </div><!-- End Section Title -->

        <!-- Slider Styles and Markup -->
        <style>
          .ltl-slider-container {
            position: relative;
            overflow: hidden;
            margin: 0 auto;
            padding: 32px 0 24px 0;
            max-width: 1150px;
          }
          .ltl-slider-track {
            display: flex;
            transition: transform 1.1s cubic-bezier(.83,0,.17,1);
            will-change: transform;
          }
          .ltl-slider-item {
            flex: 0 0 33.3333%;
            max-width: 33.3333%;
            box-sizing: border-box;
            padding: 0 14px;
            min-width: 0;
          }

          @media (max-width: 992px) {
            .ltl-slider-item { flex-basis: 100%; max-width: 100%; }
            .ltl-slider-container { max-width: 98vw; }
          }

          @media (max-width: 576px) {
            .ltl-slider-track { gap: 0; }
            .ltl-slider-btn { width: 34px; height: 34px; font-size: 17px; }
            .service-item h3 { font-size: 1.07rem; }
            .service-item p { font-size: 0.96rem; }
            .ltl-slider-item { padding: 0 7px; }
          }

          .ltl-slider-nav {
            text-align: center;
            margin-top: 8px;
            margin-bottom: 0;
            z-index: 4;
          }
          .ltl-slider-btn {
            display: inline-block;
            background: #ff9800;
            border: none;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            color: #fff;
            font-size: 22px;
            line-height: 38px;
            margin: 0 8px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(18, 66, 101, 0.19);
            transition: background 0.2s;
            vertical-align: middle;
          }
          .ltl-slider-btn:hover { background: #e37000; }
        </style>

        <div class="container mt-4">
          <div class="ltl-slider-container">
            <div class="ltl-slider-track">
              <!-- Slide 1 -->
              <div class="ltl-slider-item">
                <div class="service-item item-orange position-relative" style="padding: 5px 10px;">
                  <div class="icon"><i class="fas fa-shipping-fast fa-3x"></i></div>
                  <a href="#" class="stretched-link">
                    <h3>Wide Network of Courier Partners</h3>
                  </a>
                  <p>Shipxpeed connects you with 20+ courier partners, offering a variety of shipping options to match your business needs. Choose from multiple carriers to find the right balance of cost and service for your shipments.</p>
                </div>
              </div>
              <!-- Slide 2 -->
              <div class="ltl-slider-item">
                <div class="service-item item-teal position-relative" style="padding: 5px 10px;">
                  <div class="icon"><i class="fas fa-rupee-sign fa-3x"></i></div>
                  <a href="#" class="stretched-link">
                    <h3>Start Shipping at just ₹6.49/kg</h3>
                  </a>
                  <p>Shipxpeed offers competitive shipping rates starting at ₹6.49/kg, helping businesses manage logistics efficiently. Leverage affordable pricing without compromising on service quality.</p>
                </div>
              </div>
              <!-- Slide 3 -->
              <div class="ltl-slider-item">
                <div class="service-item item-red position-relative" style="padding: 5px 10px;">
                  <div class="icon"><i class="fas fa-map-marked-alt fa-3x"></i></div>
                  <a href="#" class="stretched-link">
                    <h3>Wide Reach Across 29,000+ Pincodes</h3>
                  </a>
                  <p>Shipxpeed enables businesses to ship across 29,000+ pincodes in India, ensuring extensive coverage. Deliver to cities, towns, and remote areas with ease.</p>
                </div>
              </div>
              <!-- Slide 4 -->
              <div class="ltl-slider-item">
                <div class="service-item item-orange position-relative" style="padding: 5px 10px;">
                  <div class="icon"><i class="fas fa-hand-holding-usd fa-3x"></i></div>
                  <a href="#" class="stretched-link">
                    <h3>Reliable COD Remittance</h3>
                  </a>
                  <p>With Shipxpeed, your Cash on Delivery payments are processed efficiently, ensuring you receive them on time so you can manage your business operations effectively.</p>
                </div>
              </div>
              <!-- Slide 5 -->
              <div class="ltl-slider-item">
                <div class="service-item item-teal position-relative" style="padding: 5px 10px;">
                  <div class="icon"><i class="fab fa-whatsapp fa-3x"></i></div>
                  <a href="#" class="stretched-link">
                    <h3>COD Order Verification Via WhatsApp</h3>
                  </a>
                  <p>Verify Cash on Delivery (COD) orders directly through WhatsApp with Shipxpeed. This process helps you confirm orders efficiently, reducing the chances of returns and improving order fulfillment.</p>
                </div>
              </div>
              <!-- Slide 6 -->
              <div class="ltl-slider-item">
                <div class="service-item item-red position-relative" style="padding: 5px 10px;">
                  <div class="icon"><i class="fas fa-truck fa-3x"></i></div>
                  <a href="#" class="stretched-link">
                    <h3>Door-to-Door Delivery</h3>
                  </a>
                  <p>Experience door-to-door delivery with Shipxpeed. Our service ensures your packages are picked up and delivered right to your recipient's doorstep, making shipping easier than ever.</p>
                </div>
              </div>
            </div>
            <div class="ltl-slider-nav">
              <button class="ltl-slider-btn" id="ltlPrevBtn" aria-label="Previous">&#x276E;</button>
              <button class="ltl-slider-btn" id="ltlNextBtn" aria-label="Next">&#x276F;</button>
            </div>
          </div>
        </div>

        <script>
          (function() {
            // Responsiveness -- itemsToShow = 1 for < 992px, 3 otherwise
            function getItemsToShow() {
              return window.innerWidth < 992 ? 1 : 3;
            }

            const track = document.querySelector('.ltl-slider-track');
            let items = Array.from(track.children);
            let totalSlides = items.length;
            let realIndex, itemWidth, itemsToShow, updatedItems, slideCount;
            let resizeTimeout = null;

            // For infinite loop: clone elements to head/tail
            function cloneForLoop() {
              items = Array.from(track.querySelectorAll('.ltl-slider-item'));
              totalSlides = items.length;
              // Remove all clones
              for (let el of track.querySelectorAll('.clone')) el.remove();
              const prepend = [];
              const append = [];
              for (let i = totalSlides - itemsToShow; i < totalSlides; ++i) {
                const node = items[i].cloneNode(true);
                node.classList.add('clone');
                prepend.push(node);
              }
              for (let i = 0; i < itemsToShow; ++i) {
                const node = items[i].cloneNode(true);
                node.classList.add('clone');
                append.push(node);
              }
              prepend.forEach((el) => track.insertBefore(el, track.firstChild));
              append.forEach((el) => track.appendChild(el));
              updatedItems = Array.from(track.children);
              slideCount = updatedItems.length;
              realIndex = itemsToShow;
            }

            function setWidths() {
              itemWidth = 100 / itemsToShow;
              updatedItems.forEach(el => {
                el.style.flex = `0 0 ${itemWidth}%`;
                el.style.maxWidth = `${itemWidth}%`;
              });
            }

            function showSlide(animate=true) {
              if (animate) {
                track.style.transition = "transform 1.1s cubic-bezier(.83,0,.17,1)";
              } else {
                track.style.transition = "none";
              }
              const offset = -(realIndex * 100 / itemsToShow);
              track.style.transform = `translateX(${offset}%)`;
            }

            function goPrev() {
              if (track.style.transition === '') track.style.transition = "transform 1.1s cubic-bezier(.83,0,.17,1)";
              realIndex--;
              showSlide();
              if (realIndex === 0) {
                setTimeout(() => {
                  realIndex = totalSlides;
                  showSlide(false);
                }, 1150);
              }
            }
            function goNext() {
              if (track.style.transition === '') track.style.transition = "transform 1.1s cubic-bezier(.83,0,.17,1)";
              realIndex++;
              showSlide();
              if (realIndex === totalSlides + 1) {
                setTimeout(() => {
                  realIndex = 1;
                  showSlide(false);
                }, 1150);
              }
            }

            // Responsive handler
            function setupSlider(responsiveInit=false) {
              itemsToShow = getItemsToShow();
              cloneForLoop();
              setWidths();
              showSlide(false);

              // Remove any previous event listeners for swipe (to avoid stacking)
              track.replaceWith(track.cloneNode(true));
              // Get the updated track DOM element (since we replaced it)
              const newTrack = document.querySelector('.ltl-slider-track');
              // Copy over children
              for (const child of track.children) newTrack.appendChild(child.cloneNode(true));
              // Replace the old with the new
              track.parentNode.replaceChild(newTrack, track);
              // Redefine all the variables on track
              window['ltlSliderTrack'] = newTrack;
              // rewire handlers
              setSwipeListeners(newTrack);
              // update nav handlers, only once if initial
              if (!responsiveInit) setupNavHandlers();
            }

            function setSwipeListeners(t) {
              let startX = null;
              t.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
              });
              t.addEventListener('touchmove', function(e) {
                if (startX === null) return;
                let diff = e.touches[0].clientX - startX;
                if (Math.abs(diff) > 60) {
                  if (diff > 0) goPrev();
                  else goNext();
                  startX = null;
                }
              });
              t.addEventListener('touchend', function(){ startX = null; });
            }

            function setupNavHandlers() {
              document.getElementById('ltlPrevBtn').onclick = goPrev;
              document.getElementById('ltlNextBtn').onclick = goNext;
            }

            // Initial setup on DOMContentLoaded
            document.addEventListener('DOMContentLoaded', function() {
              itemsToShow = getItemsToShow();
              cloneForLoop();
              setWidths();
              showSlide(false);
              setupNavHandlers();
              setSwipeListeners(track);
            });

            // Re-render slider on resize for responsiveness
            window.addEventListener('resize', function() {
              if (resizeTimeout !== null) clearTimeout(resizeTimeout);
              resizeTimeout = setTimeout(function() {
                itemsToShow = getItemsToShow();
                cloneForLoop();
                setWidths();
                showSlide(false);
              }, 200);
            });
          })();
        </script>
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
