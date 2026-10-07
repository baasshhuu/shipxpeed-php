@extends('layouts.sellerdash')

@section('content')
<style>
    .passbook-page {
        --pb-primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --pb-success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --pb-danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --pb-info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --pb-dark-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
    }
    .passbook-page .pb-dashboard-header {
        background: linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 0.8rem 0;
        margin-bottom: 1rem;
        border-radius: 7px;
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.3);
    }
    .passbook-page .pb-dashboard-title {
        font-size: 1.3rem;
        margin-bottom: 0.2rem;
    }
    .passbook-page .pb-dashboard-subtitle {
        font-size: 0.8rem;
        opacity: 0.9;
    }
    .passbook-page .pb-nav-tabs {
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
    }
    .passbook-page .pb-nav-tab {
        display: inline-flex;
        align-items: center;
        padding: 0.7rem 1.2rem;
        margin: 0 0.2rem;
        border-radius: 10px;
        text-decoration: none;
        color: #6c757d;
        font-weight: 500;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        border: none;
        background: #f8f9fa;
        cursor: pointer;
    }
    .passbook-page .pb-nav-tab.active,
    .passbook-page .pb-nav-tab:hover {
        color: white;
        background: var(--pb-primary-gradient);
        box-shadow: 0 3px 15px rgba(102, 126, 234, 0.4);
    }
    .passbook-page .pb-nav-tab i {
        margin-right: 0.4rem;
        font-size: 1rem;
    }
    .passbook-page .pb-transactions-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .passbook-page .pb-transactions-header {
        background:linear-gradient(93deg, #8e9fd9 5%, #6791cf 75%, #5054e9 117%) !important;
        color: white;
        padding: 1.2rem;
        text-align: center;
    }
    .passbook-page .pb-transactions-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
    }
    .passbook-page .pb-transactions-content {
        padding: 1.2rem;
    }
    .passbook-page .pb-custom-table {
        border: none;
        border-radius: 7px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }
    .passbook-page .pb-custom-table thead th {
        background:linear-gradient(93deg, #8e9fd9 5%) !important;
        border: none;
        font-weight: 600;
        color: white;
        padding: 0.8rem 0.7rem;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .passbook-page .pb-custom-table tbody td {
        border: none;
        padding: 0.8rem 0.7rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
        font-size: 0.85rem;
    }
    .passbook-page .pb-custom-table tbody tr {
        transition: all 0.3s ease;
    }
    .passbook-page .pb-custom-table tbody tr:hover {
        background-color: #f8f9ff;
        transform: scale(1.005);
    }
    .pb-empty-state {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }
    .pb-empty-state i {
        font-size: 3rem;
        margin-bottom: 0.8rem;
        opacity: 0.5;
    }
    .template-message {
        background: #fff !important;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.85rem;
        color: #333;
        margin-top: 0.5rem;
        white-space: pre-wrap;
        word-break: break-word;
        box-shadow: 0 2px 8px rgba(0,0,0,0.09);
        display: block;
        max-width: 100%;
        overflow-wrap: break-word;
        cursor: pointer;
    }
    @media (max-width: 768px) {
        .passbook-page .pb-dashboard-title {
            font-size: 1.1rem;
        }
        .passbook-page .pb-dashboard-subtitle {
            font-size: 0.75rem;
        }
        .passbook-page .pb-nav-tab {
            padding: 0.6rem 0.8rem;
            font-size: 0.8rem;
        }
        /* Make the template column and cell full width in mobile */
        .pb-custom-table thead th:nth-child(4),
        .pb-custom-table tbody td:nth-child(4) {
            min-width: 100vw !important;
            max-width: 100vw !important;
            width: 100vw !important;
            /* Because table-responsive has overflow scroll,
               let cell content 'break-out' with negative margin if needed */
            margin-left: -16px !important;
            margin-right: -16px !important;
            box-sizing: border-box !important;
        }
        .template-message {
            min-width: 100vw !important;
            max-width: 100vw !important;
            width: 100vw !important;
            margin-left: -16px !important;
            margin-right: -16px !important;
        }
    }
    /* Tooltip styling */
    .pb-tooltip-message {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 99999;
        width: 100vw;
        max-width: 100vw;
        margin: 0 auto;
        padding: 1.1rem 1rem;
        background: #fff;
        color: #333;
        border-radius: 0 0 14px 14px;
        font-size: 0.95rem;
        box-shadow: 0 6px 24px 0 rgba(36,47,87,.17);
        border-bottom: 2px solid #e7e9fa;
        white-space: pre-wrap;
        word-break: break-word;
        overflow-x: auto;
    }
    @media (max-width: 768px) {
        .pb-tooltip-message {
            display: none;
            top: 0 !important;
            left: 0 !important;
            width: 100vw !important;
            max-width: 100vw;
            border-radius: 0 0 14px 14px;
            padding: 1.1rem 1rem;
            font-size: 1rem;
        }
    }
</style>

<div class="pc-container passbook-page">
    <div class="pc-content" style="margin-left:12px;background:#646dff26;">
        <!-- Dashboard Header -->
        <div class="pb-dashboard-header text-center pb-fade-in">
            <div class="container">
                <h1 class="pb-dashboard-title">Notifications Center</h1>
                <p class="pb-dashboard-subtitle">Manage your Email & WhatsApp notifications for order status</p>
            </div>
        </div>
        <!-- Tabs -->
        <div class="pb-nav-tabs">
            <button class="pb-nav-tab active" id="emailTab" onclick="showTab('email')"><i class="ti ti-mail"></i> Email</button>
            <button class="pb-nav-tab" id="whatsappTab" onclick="showTab('whatsapp')"><i class="ti ti-brand-whatsapp"></i> WhatsApp</button>
        </div>
        <!-- Notification Table -->
        <div class="pb-transactions-container pb-fade-in">
            <div class="table-responsive">
                <!-- Email Table -->
                <table class="table pb-custom-table" id="emailTable">
                    <thead>
                        <tr>
                            <th>Order Status</th>
                            <th>Type</th>
                            <th>Enable/Disable</th>
                            <th>Template</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody style="background: #fdfdfd;">
                        @forelse($notifications->where('notification_type', 'email') as $notification)
                            <tr class="pb-slide-in">
                                <td>{{ $notification->order_status }}</td>
                                <td>{{ ucfirst($notification->notification_type) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('notifications.toggle', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-{{ $notification->enabled ? 'success' : 'secondary' }}">
                                            {{ $notification->enabled ? 'Enabled' : 'Disabled' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="template-message"
                                         data-message="{{ htmlentities($notification->template, ENT_QUOTES) }}">
                                        {{ $notification->template }}
                                    </div>
                                </td>
                                <td>{{ $notification->updated_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="pb-empty-state">
                                    <i class="ti ti-database"></i>
                                    <h5>No Email Notification Settings Found</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <!-- WhatsApp Table -->
                <table class="table pb-custom-table" id="whatsappTable" style="display:none;">
                    <thead>
                        <tr>
                            <th>Order Status</th>
                            <th>Type</th>
                            <th>Enable/Disable</th>
                            <th>Template</th>
                            <th>Updated At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications->where('notification_type', 'whatsapp') as $notification)
                            <tr class="pb-slide-in">
                                <td>{{ $notification->order_status }}</td>
                                <td>{{ ucfirst($notification->notification_type) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('notifications.toggle', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-{{ $notification->enabled ? 'success' : 'secondary' }}">
                                            {{ $notification->enabled ? 'Enabled' : 'Disabled' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="template-message"
                                         data-message="{{ htmlentities($notification->template, ENT_QUOTES) }}">
                                        {{ $notification->template }}
                                    </div>
                                </td>
                                <td>{{ $notification->updated_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="pb-empty-state">
                                    <i class="ti ti-database"></i>
                                    <h5>No WhatsApp Notification Settings Found</h5>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="text-center mt-4 mb-3">
            <div style="background: #f8f9fa; border-radius: 10px; display: inline-block; padding: 1rem 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); font-size: 1.05rem; color: #333;">
                <strong>Note:</strong> WhatsApp messages will be charged at <span style="color: #28a745; font-weight:600;">₹1</span> per message and Email messages at <span style="color: #007bff; font-weight:600;">₹0.50</span> per message.
            </div>
        </div>
    </div>
</div>
<!-- Tooltip container -->
<div class="pb-tooltip-message" id="pbTooltipMsg"></div>
<script>
    function showTab(tab) {
        document.getElementById('emailTable').style.display = tab === 'email' ? '' : 'none';
        document.getElementById('whatsappTable').style.display = tab === 'whatsapp' ? '' : 'none';
        document.getElementById('emailTab').classList.toggle('active', tab === 'email');
        document.getElementById('whatsappTab').classList.toggle('active', tab === 'whatsapp');
    }

    // Tooltip logic for template message (mobile only)
    document.addEventListener("DOMContentLoaded", function() {
        const tooltip = document.getElementById("pbTooltipMsg");

        function showTooltip(message) {
            tooltip.innerHTML = message;
            tooltip.style.display = 'block';
        }
        function hideTooltip() {
            tooltip.style.display = 'none';
        }

        // Handler for all template messages
        document.querySelectorAll('.template-message').forEach(function(el) {
            el.addEventListener('mouseenter', function(e) {
                if (window.innerWidth <= 768) {
                    // On mobile, show tooltip on tap
                    return;
                }
                const msg = el.getAttribute('data-message');
                showTooltip(msg);
                // Position not fixed on desktop, top of element
                const rect = el.getBoundingClientRect();
                tooltip.style.top = (rect.bottom + window.scrollY + 6) + "px";
                tooltip.style.left = rect.left + "px";
                tooltip.style.width = rect.width + "px";
            });
            el.addEventListener('mouseleave', function(e) {
                if (window.innerWidth <= 768) {
                    return;
                }
                hideTooltip();
            });
            el.addEventListener('click', function(e) {
                if (window.innerWidth > 768) return;
                e.stopPropagation();
                // On mobile, show tooltip, always at top of viewport and full width
                const msg = el.getAttribute('data-message');
                showTooltip(msg);
                tooltip.style.top = "0px";
                tooltip.style.left = "0";
                tooltip.style.width = window.innerWidth + "px";
                tooltip.style.display = 'block';
            });
        });

        // Hide tooltip on outside tap/click or ESC key
        document.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                hideTooltip();
            }
        });
        tooltip.addEventListener('click', function(e) {
            // Avoid closing itself when clicked inside
            e.stopPropagation();
        });
        window.addEventListener('keydown', function(e) {
            if (e.key === "Escape") hideTooltip();
        });
        window.addEventListener('resize', function() {
            hideTooltip();
        });
    });
</script>
@endsection
