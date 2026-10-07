@extends('layouts.sellerdash')

@section('content')
    <div class="pc-container" style="margin-left:69px;background-color:#646dff26;">
        <div class="pc-content">

            <style>
                .sx-ticket-form-wrapper {
                    width: 100%;
                    background: linear-gradient(140deg, #fff 66%, #e7edfe 100%);
                    border-radius: 16px;
                    box-shadow: 0 8px 32px 0 #3c51cc18;
                    padding: 1.7rem 1.7rem 1.7rem 1.7rem;
                    margin: 0 auto;
                    max-width: 100%;
                }
                .sx-ticket-form-label {
                    font-weight: 600;
                    font-size: 1rem;
                    color: #36485a;
                    margin-bottom: 0.45rem;
                    letter-spacing: 0.01em;
                    display: inline-block;
                }
                .sx-ticket-form-input,
                .sx-ticket-form-select,
                .sx-ticket-form-textarea {
                    border-radius: 9px !important;
                    background: #f6f8ff;
                    border: 1.2px solid #d4e2ff;
                    font-size: 1.07em;
                    letter-spacing: 0.01em;
                    padding: 0.67em 1em;
                    box-shadow: none !important;
                    transition: border 0.18s, box-shadow 0.18s;
                    width: 100%;
                }
                .sx-ticket-form-input:focus,
                .sx-ticket-form-select:focus,
                .sx-ticket-form-textarea:focus {
                    border-color: #8aa8fb;
                    background: #f3f7ff;
                    outline: none;
                    box-shadow: 0 0 0 1.1px #2469f8;
                }
                .sx-ticket-form-btn {
                  
                    font-size: 1em;
                  
                    border-radius: 12px;
                    background: linear-gradient(90deg, #3459d9 0%, #8f9fff 90%);
                    border: none;
                    padding: 0.72em 1em;
                    letter-spacing: 0.01em;
                    transition: background 0.21s, box-shadow 0.15s;
                    box-shadow: 0 3px 18px 0 #5a6cff1a;
                }
                .sx-ticket-form-btn:hover {
                    background: linear-gradient(93deg, #364dce 0%, #6c90ff 100%);
                    color: #fff;
                    box-shadow: 0 4px 19px 0 #3848a137;
                }
                /* Responsive design */
                @media (max-width: 1100px) {
                    .sx-ticket-form-wrapper { padding: 2.2rem 1.7rem 2rem 1.7rem; }
                }
                @media (max-width: 900px) {
                    .sx-ticket-form-wrapper { padding: 1.6rem 0.6rem 1.2rem 0.6rem; }
                }
                @media (max-width: 576px) {
                    .sx-ticket-form-wrapper {
                        border-radius: 8px;
                        padding: 1.15rem 0.8rem 1.15rem 0.8rem !important;
                        min-width: 0;
                    }
                    .sx-ticket-form-label { font-size: 0.98rem; }
                    .sx-ticket-form-input, .sx-ticket-form-select, .sx-ticket-form-textarea {
                        font-size: 0.97em;
                        padding: 0.51em 0.7em;
                    }
                }

                /* For desktop, inputs can be stacked with some margin */
                .sx-form-row {
                    display: flex;
                    flex-direction: row;
                    width: 100%;
                    gap: 1.5rem;
                    margin-bottom: 1.7rem;
                    flex-wrap: wrap;
                }
                .sx-form-col {
                    flex: 1 1 90px;
                    /* min-width: 250px; */
                    margin-bottom: 0;
                }

                /* Mobile/Small screen adjustments */
                @media (max-width: 900px) {
                    .sx-form-row { flex-direction: column; gap: 0; margin-bottom: 0rem; }
                    .sx-form-col { width: 100%; min-width: 100%; }
                }
                @media (max-width: 576px) {
                    .sx-form-row { flex-direction: column; gap: 0; margin-bottom: 0; }
                    .sx-form-col { margin-bottom: 0; }
                }

                /* Remove ALL margin between AWB and Message on mobile */
                .sx-form-gap-fix {
                    margin-bottom: 0; 
                }
                @media (max-width: 900px) {
                    .sx-form-gap-fix { margin-bottom: 0; }
                }
                @media (max-width: 576px) {
                    .sx-form-gap-fix { margin-bottom: 0 !important; }
                }
                /* Message field margin controlled by section below */
                .sx-mb-message-desktop { margin-bottom: 1.5rem; margin-top: 0.8rem;}
                @media (max-width: 900px) {
                    .sx-mb-message-desktop { margin-bottom: 1.1rem; margin-top: 0.4rem;}
                }
                @media (max-width: 576px) {
                    .sx-mb-message-desktop { margin-bottom: 0.8rem; margin-top: 0.17rem;}
                }
            </style>
            <div class="sx-ticket-form-wrapper mb-2">
                <form action="{{ route('seller.tickets.store') }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="sx-form-row sx-form-gap-fix">
                        <div class="sx-form-col" style="margin-bottom:0;">
                            <label class="sx-ticket-form-label" for="order_id">AWB Number <span style="color:#fc6d3c;">*</span></label>
                            <input 
                                type="text"
                                name="order_id"
                                id="order_id"
                                class="form-control sx-ticket-form-input"
                                placeholder="Enter AWB / Order ID"
                                required
                                autocomplete="off"
                            >
                        </div>
                    </div>
                    <div class="sx-mb-message-desktop" style="margin-bottom:1.5rem;">
                        <label class="sx-ticket-form-label" for="message">Message <span style="color:#fc6d3c;">*</span></label>
                        <textarea
                            name="message"
                            id="message"
                            class="form-control sx-ticket-form-textarea"
                            rows="4"
                            placeholder="Describe your issue..."
                            required
                            style="resize: vertical; min-height: 95px;"
                        ></textarea>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="sx-ticket-form-btn">
                            <i class="bi bi-send" style="margin-right:7px;position:relative;top:-1px;"></i>
                            Submit Ticket
                        </button>
                    </div>
                </form>
            </div>
            <!-- Bootstrap Icons for send icon if not already loaded -->
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

        </div>
    </div>
@endsection
