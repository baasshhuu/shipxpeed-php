@extends('frontend.website.layout.index')
@section('main_contant')
<main class="main">

    <!-- Hero Section -->

    <!-- Pricing Section -->
    <section id="pricing" class="pricing section">

      <!-- Section Title -->
      <div class="container section-title aos-init aos-animate" data-aos="fade-up">
        <h2>Choose a plan that works perfect for you</h2>

      </div><!-- End Section Title -->

      <div class="container">

        <div class="row g-4 g-lg-0">

          <div class="col-lg-4 aos-init aos-animate" data-aos="zoom-in" data-aos-delay="100">
            <div class="pricing-item">
              <h3>Lite
              </h3>
              <h4><sup>Rs.</sup>26/500<span>  gms</span></h4>
              <ul>
                <li><i class="bi bi-check"></i> <span>1 Ecommerce channel integration</span></li>
                <li><i class="bi bi-check"></i> <span> Chat, Call and Email Support</span></li>
                <li><i class="bi bi-check"></i> <span>Automated Channel Order Sync</span></li>
                <li class=""><i class="bi bi-check"></i> <span> Domestic &amp; International Shipping</span></li>
              </ul>
              <div class="text-center"><a href="#" class="buy-btn">Create an account</a></div>
            </div>
          </div><!-- End Pricing Item -->

          <div class="col-lg-4 featured aos-init aos-animate" data-aos="zoom-in" data-aos-delay="200">
            <div class="pricing-item">
              <h3>Professional
              </h3>
              <h4><sup>Rs.</sup>24/500<span> gms</span></h4>
              <ul>
                <li><i class="bi bi-check"></i> <span>1 Ecommerce channel integration</span></li>
                <li><i class="bi bi-check"></i> <span> Chat, Call and Email Support</span></li>
                <li><i class="bi bi-check"></i> <span>Automated Channel Order Sync</span></li>
                <li class=""><i class="bi bi-check"></i> <span> Domestic &amp; International Shipping</span></li>
                <li class=""><i class="bi bi-check"></i> <span>Multi Channel Price &amp; Inventory Sync</span></li>
                <li class=""><i class="bi bi-check"></i> <span>Free NDR Call Center Setup</span></li>

              </ul>
              <div class="text-center"><a href="#" class="buy-btn">Create an account</a></div>
            </div>
          </div><!-- End Pricing Item -->

          <div class="col-lg-4 aos-init aos-animate" data-aos="zoom-in" data-aos-delay="100">
            <div class="pricing-item">
              <h3>Enterprise</h3>
              <h2>Customized Shipping Solution</h2>
              <ul>
                <li><i class="bi bi-check"></i> <span>1 Ecommerce channel integration</span></li>
                <li><i class="bi bi-check"></i> <span> Chat, Call and Email Support</span></li>
                <li><i class="bi bi-check"></i> <span>Automated Channel Order Sync</span></li>
                <li class=""><i class="bi bi-check"></i> <span> Domestic &amp; International Shipping</span></li>
                <li class=""><i class="bi bi-check"></i> <span>Multi Channel Price &amp; Inventory Sync</span></li>
                <li class=""><i class="bi bi-check"></i> <span>Free NDR Call Center Setup</span></li>

              </ul>
              <div class="text-center"><a href="#" class="buy-btn">Create an account</a></div>
            </div>
          </div><!-- End Pricing Item -->

        </div>

      </div>

    </section><!-- /Pricing Section -->



    <section id="contact" class="contact-section">
      <!-- Section Title -->
      <div class="section-title aos-init" data-aos="fade-up">
        <h2>GET IN TOUCH</h2>
        <p>We are here to assist you anytime. Contact us today!</p>
      </div><!-- End Section Title -->

      <div class="container aos-init" data-aos="fade-up" data-aos-delay="100">


        <div class="contact-container">
          <div class="row">

              <!-- Left Side: Company Details -->
              <div class="col-lg-6 info-section">
                  <h2>"Our team is here to help. Contact us for any queries or suggestions."
                  </h2>

                  <div class="contact-detail">
                    <i class="fas fa-building"></i>
                    <div>
                      <h4>Corporate Office</h4>
                      <p>X-9/38-A, Ground Floor,
                        Near Moni Baba Mandir,
                        Delhi – 110053,
                        India
                        </p>
                    </div>
                  </div>



                  <div class="contact-detail">
                    <i class="fas fa-phone-alt"></i>
                    <div>
                      <h4>Contact:</h4>
                      <p>+91-9871670388</p>
                    </div>
                  </div>

                  <div class="contact-detail">
                    <i class="fas fa-envelope"></i>
                    <div>
                      <p><a href="mailto:support@shipxpeed.com">support@shipxpeed.com</a></p>
                    </div>
                  </div>

                  <div class="contact-detail">
                    <i class="fas fa-globe"></i>
                    <div>
                      <p><a href="https://www.shipxpeed.com" target="_blank">www.shipxpeed.com</a></p>
                    </div>
                  </div>
                   <div class="social-links d-flex justify-content-center justify-content-md-start mt-3">
                <a href="https://x.com/shipxpeed?s=21"><i class="bi bi-twitter-x"></i></a>
                <a href="https://www.facebook.com/profile.php?id=61574638229993"><i class="bi bi-facebook"></i></a>
                <a href="https://www.instagram.com/shipxpeedofficial?igsh=bzR6OG04aG5sdXY2&amp;utm_source=qr"><i class="bi bi-instagram"></i></a>
                <a href="https://www.linkedin.com/company/shipxpeed/"><i class="bi bi-linkedin"></i></a>
              </div>
              </div>

              <!-- Right Side: Form -->
              <div class="col-lg-6 form-container">
                  <h3 class="form-title">Get Our Discounted Prices!</h3>
                  <form action="https://themewagon.github.io/OnePage/forms/contact.php" method="post" class="php-email-form">
                      <div class="form-group">
                          <label for="name">Name</label>
                          <input type="text" id="name" name="name" class="form-control" placeholder="Enter Name" required="">
                      </div>

                      <div class="form-group">
                          <label for="email">Email</label>
                          <input type="email" id="email" name="email" class="form-control" placeholder="Enter Email" required="">
                      </div>

                      <div class="form-group">
                          <label for="phone">Phone</label>
                          <input type="text" id="phone" name="phone" class="form-control" placeholder="Enter Phone" required="">
                      </div>

                      <div class="form-group">
                          <label for="purpose">You Are Here For</label>
                          <select id="purpose" name="purpose" class="form-control">
                              <option selected="">Choose Your Requirements</option>
                              <option value="logistics">Logistics Solutions</option>
                              <option value="shipping">Shipping Services</option>
                              <option value="tracking">Order Tracking</option>
                          </select>
                      </div>

                      <button type="submit" class="form-submit">Submit</button>
                  </form>
              </div>
          </div>
        </div>
      </div>
    </section>


      </main>
@endsection
