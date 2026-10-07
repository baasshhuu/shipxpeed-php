<footer id="footer" class="footer premium-footer py-4">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md-6 text-md-start text-center mb-3 mb-md-0">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2">
                    <span class="footer-made-in text-muted fw-semibold">
                        <i class="bi bi-gem me-1 text-primary"></i> 
                        Made with <span class="text-danger" style="font-size:1.15em;">&#10084;&#65039;</span> in India
                    </span>
                    <img src="https://flagcdn.com/w40/in.png" alt="India Flag" class="ms-2" style="width: 22px; height: auto;">
                </div>
            </div>
            <div class="col-md-6 text-md-end text-center">
                <div class="footer-links d-inline-flex gap-3">
                    <a href="{{ route('privacy_policies') }}" class="footer-link">Privacy Policy</a>
                    <span class="footer-separator">|</span>
                    <a href="{{ route('refund_policies') }}" class="footer-link">Refund &amp; Cancellation</a>
                    <span class="footer-separator">|</span>
                    <a href="{{ route('termsandcondition') }}" class="footer-link">Terms &amp; Conditions</a>
                </div>
            </div>
        </div>
        <hr class="footer-divider my-4">
        <div class="row">
            <div class="col text-center">
                <small class="text-muted" style="letter-spacing:.02em;">
                    {{ $site_settings['copyright'] }}
                </small>
            </div>
        </div>
    </div>
    <style>
        .premium-footer {
            background: linear-gradient(90deg, #f8fafc 0%, #e5f2fc 100%);
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -2px 14px 0 rgba(160,160,160,.07);
        }
        .footer-made-in {
            font-size: 1.1em;
            letter-spacing: .01em;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .footer-links .footer-link {
            color: #1883bb;
            font-weight: 500;
            text-decoration: none;
            transition: color .15s;
            font-size: 1em;
        }
        .footer-links .footer-link:hover {
            color: #145f8b;
            text-decoration: underline;
        }
        .footer-separator {
            color: #bfc9d3;
            font-weight: 600;
            font-size: 1.1em;
            user-select: none;
        }
        .footer-divider {
            border: none;
            border-top: 1px solid #dde6ee;
            margin-left: 12%;
            margin-right: 12%;
            opacity: 0.85;
        }
        @media (max-width: 767.98px) {
            .premium-footer {
                padding-top: 1.6rem !important;
            }
            .footer-divider {
                margin-left: 0;
                margin-right: 0;
            }
            .footer-links {
                flex-direction: column;
                gap: 0.7em !important;
            }
            .footer-separator {
                display:none;
            }
            .footer-made-in {
                justify-content: center !important;
            }
        }
    </style>
</footer>
<a href="https://wa.link/fig1tw" class="whatsapp-float" target="_blank" title="Chat with us on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
