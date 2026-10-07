@extends('frontend.website.layout.index')
@section('main_contant')
    <main class="main">

        <!-- Hero Section -->
        <!-- Hero Section -->
        <!-- Hero Section -->
        <section class="careers-hero">
            <div class="hero-img-container">
                <img src="{{ asset('assets/website/img/join.jpg') }}" alt="Join Shipxpeed" class="hero-img" />
                <div class="hero-overlay"></div>
            </div>

            <div class="container hero-content">
                <div class="career-box animate-scale" data-aos="fade-left">
                    <h1>Join Shipxpeed</h1>
                    <p>
                        At Shipxpeed, we are reshaping courier logistics. We partner with top-tier third-party services to
                        make deliveries seamless and stress-free.
                        If you're passionate about speed, service, and simplicity — we want you on our team.
                    </p>
                </div>
            </div>
        </section>


        <!-- Open Positions Section -->
        <section class="py-5 bg-light">
            <div class="container">
                <h2 class="text-center mb-5" data-aos="fade-up">Current Openings</h2>
                <div class="row g-4" data-aos="fade-up" data-aos-delay="100">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @foreach ($careers as $career)
                        <div class="col-md-4">
                            <div class="card job-card border shadow-sm h-100">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title">{{ $career->title }}</h5>
                                    <p class="text-muted mb-2"><i class="bi bi-geo-alt"></i> {{ $career->location }}</p>
                                    <p class="text-muted mb-3"><i class="bi bi-clock"></i> {{ $career->timing }}</p>
                                    <p class="text-muted mb-3">
                                        <i class="bi bi-calendar"></i>
                                        Posted: {{ \Carbon\Carbon::parse($career->created_at)->format('F d, Y') }}
                                    </p>
                                    <p class="card-text flex-grow-1">{{ $career->short_description }}</p>
                                    <a href="#" class="btn btn-apply mt-3" data-bs-toggle="modal"
                                        data-bs-target="#applyModal">Apply Now</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </section>


        <!-- Application Modal -->
        <div class="modal fade" id="applyModal" tabindex="-1" aria-labelledby="applyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title" id="applyModalLabel">Apply for this Job</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <form method="POST" action="{{ route('careerdetails') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="title_id" value="{{ $career->id }}">

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control"
                                    placeholder="Enter your full name" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    placeholder="Enter your email" required>
                            </div>

                            <div class="mb-3">
                                <label for="mobilenumber" class="form-label">Mobile Number</label>
                                <input type="text" id="mobilenumber" name="mobilenumber" class="form-control"
                                    placeholder="Enter your mobile number" required>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea id="address" name="address" class="form-control" placeholder="Enter your address" rows="2" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="resume" class="form-label">Upload Resume</label>
                                <input type="file" id="resume" name="resume" class="form-control"
                                    accept=".pdf,.doc,.docx" required>
                            </div>

                            <button type="submit" class="btn btn-secondary w-100">Submit Application</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <!-- Benefits Section -->
        <!-- Benefits Section -->
        <section class="benefits-section">
            <div class="container">
                <h2 class="text-center mb-5" data-aos="fade-up">Why Work With Us?</h2>
                <div class="row g-4">
                    <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                        <div class="card border shadow-sm text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-graph-up-arrow fs-1 text-warning"></i>
                            </div>
                            <h5 class="card-title">Fast-Growing Company</h5>
                            <p class="card-text">Be part of a team that's scaling fast and solving real-world courier
                                problems daily.</p>
                        </div>
                    </div>
                    <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                        <div class="card border shadow-sm text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-bar-chart-line fs-1 text-primary"></i>
                            </div>
                            <h5 class="card-title">Career Advancement</h5>
                            <p class="card-text">Learn, grow, and move upward. We invest in people who invest in us.</p>
                        </div>
                    </div>
                    <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                        <div class="card border shadow-sm text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-laptop fs-1 text-success"></i>
                            </div>
                            <h5 class="card-title">Flexible Hybrid Work</h5>
                            <p class="card-text">Enjoy the balance of working from anywhere or at our collaborative office
                                space.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>



        <section id="contact" class="contact-section">
            <!-- Section Title -->
            <div class=" section-title" data-aos="fade-up">
                <h2>GET IN TOUCH</h2>
                <p>We are here to assist you anytime. Contact us today!</p>
            </div><!-- End Section Title -->

            <div class="container" data-aos="fade-up" data-aos-delay="100">


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
                                <a href="https://www.facebook.com/profile.php?id=61574638229993"><i
                                        class="bi bi-facebook"></i></a>
                                <a href="https://www.instagram.com/shipxpeedofficial?igsh=bzR6OG04aG5sdXY2&utm_source=qr"><i
                                        class="bi bi-instagram"></i></a>
                                <a href="https://www.linkedin.com/company/shipxpeed/"><i class="bi bi-linkedin"></i></a>
                            </div>
                        </div>

                        <!-- Right Side: Form -->
                        <div class="col-lg-6 form-container">
                            <h3 class="form-title">Get Our Discounted Prices!</h3>
                            <form action="https://themewagon.github.io/OnePage/forms/contact.php" method="post"
                                class="php-email-form">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text" id="name" name="name" class="form-control"
                                        placeholder="Enter Name" required>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" class="form-control"
                                        placeholder="Enter Email" required>
                                </div>

                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="text" id="phone" name="phone" class="form-control"
                                        placeholder="Enter Phone" required>
                                </div>

                                <div class="form-group">
                                    <label for="purpose">You Are Here For</label>
                                    <select id="purpose" name="purpose" class="form-control">
                                        <option selected>Choose Your Requirements</option>
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
