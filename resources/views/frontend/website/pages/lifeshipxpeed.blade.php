@extends('frontend.website.layout.index')
@section('main_contant')
    <section class="hero-section py-5">
        <div class="container hero-content">
            <div class="row align-items-center gy-5">
                <!-- Text Column -->
                <div class="col-lg-6 animate-on-scroll text-center text-lg-start animated">
                    <h1 class="display-4 fw-bold mb-4 text-white">Life at Shipxpeed</h1>
                    <p class="lead mb-3 text-light">Where Innovation Meets Passion!</p>
                    <p class="mb-4 text-light">At Shipxpeed, we believe that great ideas are born in an environment where
                        creativity thrives, and individuals are empowered to make a difference.</p>
                    <a href="#culture" class="btn btn-light btn-lg px-4 py-2">Explore Our Culture</a>
                </div>

                <!-- Image Column -->
                <div class="col-lg-6 animate-on-scroll text-center animated">
                    <img src="https://images.unsplash.com/photo-1579389083078-4e7018379f7e?ixlib=rb-4.0.3&amp;auto=format&amp;fit=crop&amp;w=1350&amp;q=80"
                        alt="Shipxpeed Team" class="img-fluid rounded shadow floating"
                        style="animation-delay: 0.3s; max-height: 400px; object-fit: cover;">
                </div>
            </div>
        </div>
    </section>


    <section id="culture" class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center animate-on-scroll animated">
                    <h2 class="section-title">Our Culture</h2>
                    <p class="lead">Our culture is built on a foundation of collaboration, innovation, and growth, making
                        Shipxpeed not just a workplace but a journey of learning and achievement.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 order-lg-2 animate-on-scroll animated">
                    @if ($workculture)
                        <div class="work-culture-item">
                            <img src="{{ Helper::showImage($workculture->image, true) }}" alt="Work Culture"
                                class="img-fluid rounded shadow">
                        </div>
                    @endif

                </div>
                <div class="col-lg-6 order-lg-1 animate-on-scroll animated">
                    <div class="p-4">

                        <h2 class="mb-4"> Work Culture</h2>
                        <p class="mb-4">We foster a dynamic and energetic work atmosphere where every voice matters. Our
                            team thrives on brainstorming, experimenting, and pushing boundaries to redefine the logistics
                            industry.</p>
                        <p>Whether it's solving complex operational challenges or creating tech-driven solutions, we
                            encourage our team to take ownership and lead with confidence.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <section class="py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 animate-on-scroll animated">
                    @if ($teamspirit)
                        <div class="work-culture-item">
                            <img src="{{ Helper::showImage($teamspirit->image, true) }}" alt="Work Culture"
                                class="img-fluid rounded shadow">
                        </div>
                    @endif
                </div>
                <div class="col-lg-6 animate-on-scroll animated">
                    <div class="p-4">

                        <h2 class="mb-4"> Team Spirit</h2>
                        <p class="mb-4">Shipxpeed is more than just a company—it's a family. We work hard, celebrate small
                            wins, and support each other through challenges.</p>
                        <p>Our open-door policy and flat hierarchy ensure that ideas flow freely, fostering a culture where
                            innovation becomes second nature.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center animate-on-scroll animated">
                    <h2 class="section-title">Why Join Shipxpeed?</h2>
                    <p class="lead">We offer an environment where you can grow both professionally and personally</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 animate-on-scroll animated">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Continuous Learning</h3>
                        <p>Regular training sessions, industry insights, and hands-on experiences to keep you ahead in the
                            fast-paced logistics world.</p>
                    </div>
                </div>
                <div class="col-md-4 animate-on-scroll animated" style="transition-delay: 0.2s;">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3>Innovation Focus</h3>
                        <p>A culture that encourages creative thinking and rewards innovative solutions to complex problems.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 animate-on-scroll animated" style="transition-delay: 0.4s;">
                    <div class="feature-box">
                        <div class="feature-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Work-Life Balance</h3>
                        <p>Flexible schedules and fun activities to ensure you thrive both at work and in your personal
                            life.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center animate-on-scroll animated">
                    <h2 class="section-title"> Fun Beyond Work</h2>
                    <p class="lead">Life at Shipxpeed isn't just about work. We balance hard work with fun activities and
                        celebrations.</p>
                </div>
            </div>
            <div class="row">
                @foreach($beyondworks as $index => $beyondwork)
                    @php
                        $delay = ($index % 3) * 0.2; // cycle through delays: 0, 0.2, 0.4
                        $colClass = $index % 5 < 3 ? 'col-md-4' : 'col-md-6'; // first 3 = col-md-4, rest col-md-6
                    @endphp
                    <div class="{{ $colClass }} animate-on-scroll animated" style="transition-delay: {{ $delay }}s;">
                        <div class="gallery-img">
                            <img src="{{ Helper::showImage($beyondwork->image, true) }}" alt="Beyond Work" class="img-fluid rounded shadow">
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center animate-on-scroll animated">
                    <h2 class="section-title">What Our Team Says</h2>
                    <p class="lead">Hear from the people who make Shipxpeed special</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 animate-on-scroll animated">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://th.bing.com/th/id/OIP.LygSssGgg2UaRhCmHzxQgQHaE8?w=298&amp;h=199&amp;c=7&amp;r=0&amp;o=5&amp;pid=1.7"
                                alt="Team Member" class="testimonial-img">
                            <div>
                                <h5 class="mb-0">Rohan Singh</h5>
                                <p class="text-muted mb-0">Software Engineer</p>
                            </div>
                        </div>
                        <p>"Shipxpeed has given me the freedom to explore new technologies and implement innovative
                            solutions. The collaborative environment makes every day exciting!"</p>
                    </div>
                </div>
                <div class="col-md-4 animate-on-scroll animated" style="transition-delay: 0.2s;">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://media.istockphoto.com/id/2158468781/photo/a-serious-indian-man-in-glasses-and-a-blue-shirt-standing-with-crossed-arms-in-a-modern.webp?a=1&amp;b=1&amp;s=612x612&amp;w=0&amp;k=20&amp;c=a3LcmYJQIHZbC-53fz-kzgP2HMSiVafINqKbU0iy3dQ="
                                alt="Team Member" class="testimonial-img">
                            <div>
                                <h5 class="mb-0">Rohit Agarwal</h5>
                                <p class="text-muted mb-0">Operations Manager</p>
                            </div>
                        </div>
                        <p>"What I love most about Shipxpeed is how every team member's opinion is valued. The flat
                            hierarchy means great ideas can come from anywhere."</p>
                    </div>
                </div>
                <div class="col-md-4 animate-on-scroll animated" style="transition-delay: 0.4s;">
                    <div class="testimonial-card">
                        <div class="d-flex align-items-center mb-3">
                            <img src="https://th.bing.com/th/id/OIP.-L86oIeT8ppTqyLdO7nvJAHaLH?w=135&amp;h=203&amp;c=7&amp;r=0&amp;o=5&amp;pid=1.7"
                                alt="Team Member" class="testimonial-img">
                            <div>
                                <h5 class="mb-0">Priya Patel</h5>
                                <p class="text-muted mb-0">Product Designer</p>
                            </div>
                        </div>
                        <p>"The balance between work and fun activities is perfect. I've grown so much professionally while
                            also making lifelong friends at Shipxpeed."</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container cta-content">
            <div class="row justify-content-center text-center animate-on-scroll animated">
                <div class="col-lg-8">
                    <h2 class="display-5 fw-bold mb-4 text-white">Ready to Join Our Team?</h2>
                    <p class="lead mb-5">We're always looking for passionate individuals who want to make an impact in the
                        logistics industry.</p>
                    <a href="{{ route('career') }}" class="btn btn-light btn-lg px-4 py-2 me-3">View Open Positions</a>
                    <a href="contact.html" class="btn btn-outline-light btn-lg px-4 py-2">Contact Us</a>
                </div>
            </div>
        </div>
    </section>
@endsection
