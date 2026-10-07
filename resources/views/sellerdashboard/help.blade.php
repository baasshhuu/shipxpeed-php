@extends('layouts.sellerdash')

@section('content')
<div class="fade-in py-4" style="background: #f6f8fb;">
    <div class="container-fluid px-0">
        <div class="row justify-content-center" style="padding-top:27px;">
            <div class="col-12 col-lg-10 responsive-support-wrapper" style="margin-left: 57px;">
                <div class="card premium-support-card shadow border-0 mb-4" style="margin-top:56px; border-radius:28px;">
                    <div class="card-header py-4 premium-support-header d-flex align-items-center justify-content-between flex-wrap gap-2 position-relative" style="min-height:110px;">
                        <div>
                            <h3 class="mb-1 text-white fw-bold" style="text-shadow:0 2px 14px #172554a0;letter-spacing:0.035em;font-size:1.7rem;">
                                <i class="fa-solid fa-shield-halved text-primary me-2 premium-badge-shadow"></i>
                                Help & Support 
                            </h3>
                            <div class="mt-1 fw-normal text-light small" style="opacity:0.96;">
                                Your dedicated support team for fast, personal, professional help.
                            </div>
                        </div>
                        {{-- Align the ticket button in the top right corner --}}
                        <div class="ms-auto">
                            <a href="{{ route('seller.ticket.add') }}" class="export-btn">Add Tickets</a>
                        </div>
                    </div>
                    <div class="card-body py-4">
                        <div class="row g-4">
                            <!-- Customer Support Card -->
                            <div class="col-12">
                                <div class="support-contact-card d-flex flex-column flex-md-row align-items-md-center justify-content-between px-3 py-3 mb-2 border-left-premium position-relative">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <div class="support-icon me-3 bg-gradient-blue-glow">
                                            <i class="fa-solid fa-headset"></i>
                                        </div>
                                        <div>
                                            <div class=" fw-semibold text-navy">Customer Support</div>
                                            <div class="support-subtitle text-dark-emphasis">Robin</div>
                                            <div class="support-timings text-muted mt-1">
                                                <i class="fa-regular fa-clock me-1 text-gradient"></i>
                                                Call: <span class="fw-semibold">10:00&nbsp;AM - 6:00&nbsp;PM</span> <span class="text-secondary">| Mon-Sat</span><br>
                                                <i class="fa-solid fa-envelope me-1 text-danger"></i>
                                                Email: <span class="fw-semibold">24/7</span> <span class="text-secondary">(avg. reply: 2-6h)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <a href="tel:9871670388" class="btn btn-link support-link px-3 premium-cta" title="Call (10AM - 6PM)">
                                            <i class="fa-solid fa-phone text-success"></i> 9871670388
                                        </a>
                                        <a href="https://wa.me/919871670388" class="btn btn-link support-link px-3 premium-cta" target="_blank" title="Chat on WhatsApp">
                                            <i class="fa-brands fa-whatsapp text-success"></i>
                                        </a>
                                        <a href="mailto:support@shipxpeed.com" class="btn btn-link support-link px-3 premium-cta" title="Email (24/7)">
                                            <i class="fa-solid fa-envelope text-danger"></i> support@shipxpeed.com
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Key Account Manager Card -->
                            <div class="col-12">
                                <div class="support-contact-card d-flex flex-column flex-md-row align-items-md-center justify-content-between px-3 py-3 mb-2 border-left-sky position-relative">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <div class="support-icon me-3 bg-gradient-sky-glow">
                                            <i class="fa-solid fa-user-tie"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-navy">Key Account Manager</div>
                                            <div class="support-subtitle text-dark-emphasis">Diya Sharma</div>
                                            <div class="support-timings text-muted mt-1">
                                                <i class="fa-regular fa-clock me-1 text-gradient"></i>
                                                Call: <span class="fw-semibold">10:00&nbsp;AM - 6:00&nbsp;PM</span> <span class="text-secondary">| Mon-Sat</span><br>
                                                <i class="fa-solid fa-envelope me-1 text-danger"></i>
                                                Email: <span class="fw-semibold">24/7</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <a href="tel:9319439572" class="btn btn-link support-link px-3 premium-cta" title="Call (10AM - 6PM)">
                                            <i class="fa-solid fa-phone text-success"></i> 9319439572
                                        </a>
                                        <a href="mailto:diya@shipxpeed.com" class="btn btn-link support-link px-3 premium-cta" title="Email (24/7)">
                                            <i class="fa-solid fa-envelope text-danger"></i> diya@shipxpeed.com
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Finance Card -->
                            <div class="col-12">
                                <div class="support-contact-card d-flex flex-column flex-md-row align-items-md-center justify-content-between px-3 py-3 mb-2 border-left-purple position-relative">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <div class="support-icon me-3 bg-gradient-purple-glow">
                                            <i class="fa-solid fa-coins"></i>
                                        </div>
                                        <div>
                                            <div class=" fw-semibold text-navy">Finance Department</div>
                                            <div class="support-subtitle text-dark-emphasis">Bhanu Upadhyay</div>
                                            <div class="support-timings text-muted fst-italic mt-1">
                                                <i class="fa-solid fa-envelope me-1 text-danger"></i>
                                                Email: <span class="fw-semibold">24/7</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <a href="mailto:accounts@shipxpeed.com" class="btn btn-link support-link px-3 premium-cta" title="Email (24/7)">
                                            <i class="fa-solid fa-envelope text-danger"></i> accounts@shipxpeed.com
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Legal Card -->
                            <div class="col-12">
                                <div class="support-contact-card d-flex flex-column flex-md-row align-items-md-center justify-content-between px-3 py-3 mb-2 border-left-indigo position-relative">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <div class="support-icon me-3 bg-gradient-indigo-glow">
                                            <i class="fa-solid fa-scale-balanced"></i>
                                        </div>
                                        <div>
                                            <div class=" fw-semibold text-navy">Legal Team</div>
                                            <div class="support-subtitle text-dark-emphasis">Sachin Malik</div>
                                            <div class="support-timings text-muted mt-1">
                                                <i class="fa-solid fa-envelope me-1 text-danger"></i>
                                                Email: <span class="fw-semibold">24/7</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <a href="mailto:legal@shipxpeed.com" class="btn btn-link support-link px-3 premium-cta" title="Email (24/7)">
                                            <i class="fa-solid fa-envelope text-danger"></i> legal@shipxpeed.com
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Tech Card -->
                            <div class="col-12">
                                <div class="support-contact-card d-flex flex-column flex-md-row align-items-md-center justify-content-between px-3 py-3 mb-2 border-left-orange position-relative">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <div class="support-icon me-3 bg-gradient-orange-glow">
                                            <i class="fa-solid fa-laptop-code"></i>
                                        </div>
                                        <div>
                                            <div class=" fw-semibold text-navy">Tech Team</div>
                                            <div class="support-subtitle text-dark-emphasis">Vikas Kumawat</div>
                                            <div class="support-timings text-muted fst-italic mt-1">
                                                <i class="fa-regular fa-clock me-1 text-gradient"></i>
                                                Call: <span class="fw-semibold">10:00&nbsp;AM - 6:00&nbsp;PM</span> <span class="text-secondary">| Mon-Sat</span><br>
                                                <i class="fa-solid fa-envelope me-1 text-danger"></i>
                                                Email: <span class="fw-semibold">24/7</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <a href="tel:7357169546" class="btn btn-link support-link px-3 premium-cta" title="Call (10AM - 6PM)">
                                            <i class="fa-solid fa-phone text-success"></i> 7357169546
                                        </a>
                                        <a href="mailto:tech@shipxpeed.com" class="btn btn-link support-link px-3 premium-cta" title="Email (24/7)">
                                            <i class="fa-solid fa-envelope text-danger"></i> tech@shipxpeed.com
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- CEO Card -->
                            <div class="col-12">
                                <div class="support-contact-card d-flex flex-column flex-md-row align-items-md-center justify-content-between px-3 py-3 border-left-gold position-relative">
                                    <div class="d-flex align-items-center mb-2 mb-md-0">
                                        <div class="support-icon me-3 bg-gradient-gold-glow">
                                            <i class="fa-solid fa-user-secret"></i>
                                        </div>
                                        <div>
                                            <div class=" fw-semibold text-navy">CEO</div>
                                            <div class="support-subtitle text-dark-emphasis">Bashu Upadhyay</div>
                                            <div class="support-timings text-muted fst-italic mt-1">
                                                <i class="fa-solid fa-envelope me-1 text-danger"></i>
                                                Email: <span class="fw-semibold">24/7 (Serious Only)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <a href="mailto:bashu@shipxpeed.com" class="btn btn-link support-link px-3 premium-cta" title="Email CEO - Serious Issues">
                                            <i class="fa-solid fa-envelope text-danger"></i> bashu@shipxpeed.com
                                        </a>
                                    </div>
                                </div>
                                <div class="text-end small text-danger me-2 mt-1">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                    For urgent/escalated matters ONLY.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-2 small fw-semibold" style="color: #334155;">
                    Our support team is here for you on priority.<br>
                    <!-- <span class="d-inline-block mt-1 premium-support-badge px-3 py-2">
                        <i class="fa fa-shield-halved me-1 text-navy"></i>
                        <span>Premium Seller Care</span>
                    </span> -->
                </div>
            </div>
        </div>
    </div>
</div>
<style>
/* Responsive wrapper for support card (prevents sidebar from being affected on mobile) */
.responsive-support-wrapper {
    margin-left: 57px;
    margin-right: auto;
}

@media (max-width: 1399.98px) {
    /* Laptop large (<=lg, <1400px) styles */
    .responsive-support-wrapper {
        margin-left: 24px !important;
        margin-right: 12px !important;
    }
    .premium-support-card {
        border-radius: 22px !important;
        margin-top: 40px !important;
    }
    .premium-support-header {
        border-radius: 22px 22px 0 0 !important;
        padding: 1.3rem 1.1rem !important;
        min-height: 98px;
    }
    .support-contact-card {
        border-radius: 12px !important;
        padding: 1rem 1rem !important;
    }
    .support-icon {
        width: 52px !important;
        height: 52px !important;
        font-size: 1.5rem !important;
    }
}

@media (max-width: 1199.98px) {
    /* Typical laptop (<=xl, <1200px) styles */
    .responsive-support-wrapper {
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding: 0 6px !important;
    }
    .premium-support-card {
        border-radius: 18px !important;
        margin-top: 25px !important;
    }
    .premium-support-header {
        border-radius: 18px 18px 0 0 !important;
        padding: 1rem 0.7rem !important;
        min-height: 88px !important;
    }
    .support-contact-card {
        border-radius: 10px !important;
        padding: 0.85rem 0.7rem !important;
    }
    .support-icon {
        width: 47px !important;
        height: 47px !important;
        font-size: 1.28rem !important;
    }
}

/* Tablet and below */
@media (max-width: 991.98px) {
    .premium-support-card {
        border-radius: 16px !important;
    }
    .premium-support-header {
        border-radius: 16px 16px 0 0 !important;
        padding: 1rem 0.8rem !important;
    }
    .support-contact-card {
        border-radius: 10px;
        padding: 1rem .8rem !important;
    }
    .responsive-support-wrapper {
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding: 0 2px !important;
    }
    .support-icon {
        width: 44px !important;
        height: 44px !important;
        font-size: 1.12rem !important;
    }
}

@media (max-width: 767.98px) {
    .responsive-support-wrapper {
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        width: 100vw;
        max-width: 100vw;
    }
    .premium-support-card {
        border-radius: 11px !important;
        width: 98vw !important;
        max-width: 99vw !important;
        margin-left: 1vw !important;
        margin-right: 1vw !important;
    }
    .premium-support-header {
        border-radius: 11px 11px 0 0 !important;
        padding: 0.85rem 0.38rem !important;
        min-height: 72px !important;
    }
    .support-contact-card {
        flex-direction: column !important;
        padding: 1.08rem .35rem !important;
        border-radius: 8px !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    .support-contact-card .support-link {
        font-size: 0.92rem;
        padding: .38rem .35rem;
        min-width: 90px;
    }
    .premium-support-header h3 {
        font-size: 1.08rem !important;
        letter-spacing: 0.015em !important;
    }
    .support-icon {
        min-width: 42px !important;
        width: 43px !important;
        height: 43px !important;
        font-size: 1.25rem !important;
        border-radius: 9px !important;
    }
    .premium-support-card {
        box-shadow: 0 4px 10px 0 #1e293b18, 0 2px 8px #33415515;
    }
}
.premium-support-header {
    border-top-left-radius: 28px;
    border-top-right-radius: 28px;
    background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
    border-bottom: none !important;
    box-shadow:0 7px 30px #17255419;
}
.premium-badge-shadow {
    text-shadow:0 7px 24px #2563eb99, 0 1.5px 12px #22d3ee55;
}
.premium-support-card {
    border-radius: 15px !important;
    width: 75rem;
    box-shadow: 0 8px 36px 0 #1e293b21, 0 4px 20px #33415524;
    border: none !important;
    background: #f7faff;
    transition: box-shadow .3s, scale .18s;
}
.premium-support-card:hover, .premium-support-card:focus-within {
    box-shadow: 0 15px 52px 0 #6366f155, 0 7px 34px #17255421;
    scale:1.012;
}
.support-contact-card {
    border-radius: 9px;
    box-shadow: 0 3px 16px 0 #33415510, 0 1.7px 11px #17255412;
    background: linear-gradient(95deg, #f8fafc 65%, #c7d2fe14 117%);
    margin-bottom: 1.1em;
    transition: transform 0.15s, box-shadow 0.18s;
    border-left: 4px solid #6366f188;
    position: relative;
    overflow: visible;
}
.support-contact-card:hover {
    transform: translateY(-4px) scale(1.025);
    box-shadow: 0 9px 34px 0 #17255419, 0 2px 16px #3b82f620;
    border-left-width:7px;
}
.support-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(48deg,#2563eb22,#f3f4fa44 80%);
    border-radius: 12px;
    width: 58px;
    height: 58px;
    font-size: 2rem;
    color: #1e293b;
    box-shadow:0 2px 16px #6366f14b;
    border:1px solid #dbeafe;
}
/* Animated Glow backgrounds */
.bg-gradient-blue-glow { background: radial-gradient(circle at 70% 45%, #2563eb25 70%, #eef2ff10 100%); }
.bg-gradient-sky-glow { background: radial-gradient(circle at 55% 80%, #0ea5e938 76%, #e0e7fa14 100%); }
.bg-gradient-purple-glow { background: radial-gradient(circle at 50% 40%, #a78bfa38 70%, #ede9fe11 100%); }
.bg-gradient-indigo-glow { background: radial-gradient(circle at 78% 60%, #7c3aed31 90%, #c7d2fe06 106%); }
.bg-gradient-orange-glow { background: radial-gradient(circle at 29% 87%, #f59e423f 75%, #fef3c700 100%); }
.bg-gradient-gold-glow { background: radial-gradient(circle at 90% 10%, #facc1564 62%, #fef9c360 100%); }
/* Premium badge gradient */
.badge-gradient-premium {
    background: linear-gradient(90deg, #dbeafe 0%, #6366f1 70%, #f1f5f9 150%);
    color:#172554;
    border:1.5px solid #6366f13c;
    letter-spacing:.07em;
    font-size:.92rem;
    box-shadow:0 1px 7px #6366f14c;
}
.premium-support-badge {
    color: #1e293b;
    font-weight: 650;
    font-size: 1rem;
    letter-spacing: 0.01em;
    background: linear-gradient(90deg, #ede9fe3a, #f3e8ff38, #e0e7fa19 112%);
    border-radius: 17px;
    padding: 2.5px 14px 2.5px 8px;
    box-shadow:0 1px 8px #33415516;
    border:1px solid #ede9fe59;
}
.support-contact-card .support-link {
    color: #334155;
    background: #f1f5fb;
    font-size: 0.9rem;
    padding: .46rem 1.1rem;
    border: none;
    text-decoration: none;
    border-radius: 9px;
    font-weight: 500;
    transition: background .15s, color .12s, box-shadow .18s;
    box-shadow: 0 1.5px 7px #6366f14a;
    outline: none;
}
.support-contact-card .support-link.premium-cta {
    color: #172554;
    border:1px solid #dbeafe49;
    letter-spacing:.01em;
}
.support-contact-card .support-link:hover, .support-link:focus {
    background: linear-gradient(93deg, #e0e8f7 14%, #f1f5fb 95%, #e1eaff 110%);
    color: #2563eb;
    text-decoration: underline;
    box-shadow:0 3px 16px #6366f156;
}
.support-contact-card .fa-whatsapp {
    font-size: 1.25rem;
}
.fs-5 {
    font-size: 1.20rem !important;
}
.fw-semibold {
    font-weight: 650 !important;
}
.text-navy { color: #172554 !important; }
.support-subtitle {
    font-size: 0.85rem;
    color: #475569 !important;
}
.support-timings {
    font-size:.85rem;
}
.text-dark-emphasis { color:#334155b8 !important; }
.fa-envelope, .fa-envelope-open {
    color: #e11d48 !important;
}
.fa-phone {
    color: #059669 !important;
}
.fa-whatsapp {
    color: #25d366 !important;
}
.text-gradient {
    background: linear-gradient(90deg,#4338ca,#0ea5e9);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}
</style>
@endsection