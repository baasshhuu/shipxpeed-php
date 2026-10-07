@extends('frontend.website.layout.index')
@section('main_contant')
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

