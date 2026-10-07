<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ $site_settings['application_name'] ?? ''}}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="{{ $site_settings['favicon'] ?? '' }}" rel="icon">
    <link href="{{ $site_settings['favicon'] ?? '' }}" rel="apple-touch-icon">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://html.shipxpeed.com/">
    <meta property="og:title" content="Shipxpeed - Fast & Reliable Shipping">
    <meta property="og:description" content="Experience seamless logistics and parcel forwarding with Shipxpeed. Join today!">
    <meta property="og:image" content="{{ asset('assets/website/img/whatsappbg.jpeg') }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/" rel="preconnect">
    <link href="https://fonts.gstatic.com/" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&amp;family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('assets/website/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/website/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/website/vendor/aos/aos.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/website/vendor/glightbox/css/glightbox.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/website/vendor/swiper/swiper-bundle.min.css') }}" rel="stylesheet">

    <!-- Main CSS File -->
    <link href="{{ asset('assets/website/css/main.css') }}" rel="stylesheet">

    </script>

    @yield('style')

</head>
<style>
    .hero-slider {
        position: relative;
        width: 100%;
        min-height: 100vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .hero-slider::before {
        content: '';
        position: absolute;
        inset: 0;
        /* background: url('./assets/website/img/1.png') center/cover no-repeat fixed; */
        filter: blur(8px);
        z-index: 0;
    }

    .hero-slider::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.2);
        z-index: 1;
    }

    .hero-slider>* {
        position: relative;
        z-index: 2;
    }

    .slide {
        position: absolute;
        inset: 0;
        opacity: 0;
        transform: translateX(100%);
        transition: opacity 0.8s ease, transform 0.8s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    .slide.active {
        opacity: 1;
        transform: translateX(0);
    }

    .container-homepage {
        padding: 40px 20px;
    }

    .text-section h2 {
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: #000;
    }

    .text-section p {
        font-size: 1.1rem;
        color: #333;
        max-width: 600px;
        margin-bottom: 30px;
    }

    .btn-custom {
        background-color: #2C3E50;
        color: #fff;
        padding: 20px 30px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    /* .btn-custom:hover {
  background-color: #222;
  transform: translateY(-3px);
} */

    .image-section img {
        max-height: 500px;
        border-radius: 10px;
        /* box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); */
        background-color: rgba(0, 0, 0, 0.397);

    }

    .slider-controls {
        position: absolute;
        bottom: 5%;
        left: 0;
        right: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10;
    }

    .slider-prev,
    .slider-next {
        width: 50px;
        height: 50px;
        background: rgba(0, 0, 0, 0.6);
        color: white;
        border: none;
        border-radius: 50%;
        transition: 0.3s;
    }

    .slider-prev:hover,
    .slider-next:hover {
        background: rgba(0, 0, 0, 0.8);
        transform: scale(1.1);
    }

    .dot {
        width: 12px;
        height: 12px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 50%;
        display: inline-block;
        margin: 0 5px;
        cursor: pointer;
        transition: 0.3s;
    }

    .dot.active {
        background: #000;
        transform: scale(1.2);
    }

    .feature-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-10px);
    }


    .feature-card i {
        font-size: 2rem;
        color: #000;
        margin-bottom: 15px;
    }

    .feature-card h3 {
        font-size: 1.3rem;
        margin-bottom: 10px;
    }

    .feature-card p {
        font-size: 0.95rem;
        color: #555;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .container-homepage {
            padding: 20px 15px;
        }

        .text-section h2 {
            font-size: 1.6rem;
            margin-bottom: 15px;
        }

        .text-section p {
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .btn-custom {
            padding: 10px 20px;
            font-size: 1rem;
        }

        .image-section img {
            max-height: 250px;
            margin-top: 20px;
        }


        .slider-controls {
            bottom: 3%;
        }
    }


    /* CSS */
    .section-home2 {
        position: relative;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 40px 20px;
        background: linear-gradient(to bottom, #f9f9f9, #f1f1f1);
        animation: fadeIn 1s ease-in-out;
        overflow: hidden;
    }

    .section-home2 h1 {
        font-size: 2.5rem;
        color: #333;
        margin-bottom: 15px;
        z-index: 50;

    }

    .section-home2 .subtext {
        font-size: 1.1rem;
        color: #666;
        max-width: 600px;
        margin-bottom: 40px;
        z-index: 50;

    }

    .image-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        max-width: 60%;
        overflow: hidden;
    }

    .image-wrapper img {
        max-width: 100%;
        height: auto;
        display: block;
        z-index: 50;
    }

    /* Decorative Rings */
    .ring {
        position: absolute;
        border: 100px solid rgba(0, 0, 0, 0.05);
        border-radius: 50%;
        backdrop-filter: blur(2px);
        pointer-events: none;
        z-index: 0;
    }

    .ring1 {
        width: 500px;
        height: 500px;
        top: 10%;
        right: -350px;
    }

    .ring2 {
        width: 300px;
        height: 300px;
        bottom: 15%;
        left: -150px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        .section-home2 h1 {
            font-size: 2rem;
        }

        .section-home2 .subtext {
            font-size: 1rem;
        }

        .image-wrapper {
            max-width: 90%;
        }
    }
</style>

<body>

    @include('frontend.website.common.header')

    @yield('main_contant')
    @include('frontend.website.common.footer')

    {{-- @include('frontend.common.footer') --}}
    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    <!-- Preloader -->
    <!-- <div id="preloader"></div> -->

    <script>   
        document.addEventListener("DOMContentLoaded", function() {
            var myModal = new bootstrap.Modal(document.getElementById('exampleModal'));
            myModal.show();
        });


        document.addEventListener('DOMContentLoaded', function() {
            // Main Slider Functionality
            const slides = document.querySelectorAll('.slide');
            const dots = document.querySelectorAll('.dot');
            const prevBtn = document.querySelector('.slider-prev');
            const nextBtn = document.querySelector('.slider-next');
            let currentSlide = 0;
            let slideInterval;
            const slideDuration = 6000; // 6 seconds

            // Initialize slider
            function initSlider() {
                if (slides.length === 0) return;

                slides[currentSlide].classList.add('active');
                dots[currentSlide].classList.add('active');

                // Start auto slide
                startSlideInterval();

                // Animate numbers in stats slide
                if (document.querySelector('.stat-number')) {
                    animateNumbers();
                }
            }

            // Go to specific slide
            function goToSlide(n) {
                slides[currentSlide].classList.remove('active');
                dots[currentSlide].classList.remove('active');

                // Determine animation direction
                if (n > currentSlide) {
                    slides[currentSlide].classList.add('next');
                } else if (n < currentSlide) {
                    slides[currentSlide].classList.add('prev');
                }

                currentSlide = (n + slides.length) % slides.length;

                slides[currentSlide].classList.add('active');
                dots[currentSlide].classList.add('active');

                // Reset animation classes
                setTimeout(() => {
                    slides.forEach(slide => {
                        slide.classList.remove('next', 'prev');
                    });
                }, 1000);

                // Animate numbers if on stats slide
                if (slides[currentSlide].dataset.slide === "3") {
                    animateNumbers();
                }
            }

            // Next slide
            function nextSlide() {
                goToSlide(currentSlide + 1);
                resetSlideInterval();
            }

            // Previous slide
            function prevSlide() {
                goToSlide(currentSlide - 1);
                resetSlideInterval();
            }

            // Start auto slide interval
            function startSlideInterval() {
                slideInterval = setInterval(nextSlide, slideDuration);
            }

            // Reset auto slide interval
            function resetSlideInterval() {
                clearInterval(slideInterval);
                startSlideInterval();
            }

            // Event listeners
            nextBtn.addEventListener('click', nextSlide);
            prevBtn.addEventListener('click', prevSlide);

            dots.forEach(dot => {
                dot.addEventListener('click', function() {
                    const slideIndex = parseInt(this.dataset.slide) - 1;
                    goToSlide(slideIndex);
                    resetSlideInterval();
                });
            });

            // Animate numbers in stats
            function animateNumbers() {
                const statNumbers = document.querySelectorAll('.stat-number');

                statNumbers.forEach(stat => {
                    const target = parseInt(stat.dataset.count);
                    const duration = 2000; // 2 seconds
                    const step = target / (duration / 16); // 60fps

                    let current = 0;
                    const increment = () => {
                        current += step;
                        if (current < target) {
                            stat.textContent = Math.floor(current).toLocaleString();
                            requestAnimationFrame(increment);
                        } else {
                            stat.textContent = target.toLocaleString();
                        }
                    };

                    increment();
                });
            }

            // Testimonial slider functionality
            const testimonialSlides = document.querySelectorAll('.testimonial-slide');
            const testimonialPrev = document.querySelector('.testimonial-prev');
            const testimonialNext = document.querySelector('.testimonial-next');
            let currentTestimonial = 0;

            if (testimonialSlides.length > 0) {
                function showTestimonial(n) {
                    testimonialSlides[currentTestimonial].classList.remove('active');
                    currentTestimonial = (n + testimonialSlides.length) % testimonialSlides.length;
                    testimonialSlides[currentTestimonial].classList.add('active');
                }

                function nextTestimonial() {
                    showTestimonial(currentTestimonial + 1);
                }

                function prevTestimonial() {
                    showTestimonial(currentTestimonial - 1);
                }

                testimonialNext.addEventListener('click', nextTestimonial);
                testimonialPrev.addEventListener('click', prevTestimonial);

                // Auto cycle testimonials
                setInterval(nextTestimonial, 5000);
            }

            // Initialize the slider
            initSlider();
        });
    </script>
    <!-- Vendor JS Files -->
    <script src="{{ asset('assets/website/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/website/vendor/php-email-form/validate.js') }}"></script>
    <script src="{{ asset('assets/website/vendor/aos/aos.js') }}"></script>
    <script src="{{ asset('assets/website/vendor/purecounter/purecounter_vanilla.js') }}"></script>
    <script src="{{ asset('assets/website/vendor/glightbox/js/glightbox.min.js') }}"></script>
    <script src="{{ asset('assets/website/vendor/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/website/vendor/imagesloaded/imagesloaded.pkgd.min.js') }}"></script>
    <script src="{{ asset('assets/website/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>

    <!-- Main JS File -->
    <script src="{{ asset('assets/website/js/main.js') }}"></script>

</body>

</html>
