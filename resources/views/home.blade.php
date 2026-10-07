@extends('layouts.app')
@include('partial.sellerdash.common.header')

@section('content')
<section id="widgets" class="py-4">
    <div class="container-fluid">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">

            @php
                $cards = [
                    [ 'label' => 'Today Order', 'value' => $todayOrder, 'icon' => 'fas fa-calendar-day', 'route' => "#" ],
                    [ 'label' => 'All Shipment', 'value' => $allOrder, 'icon' => 'fas fa-truck-loading', 'route' => route('shipment.report') ],
                    [ 'label' => 'All Recharge', 'value' => $sellerRechargeAmount, 'icon' => 'fas fa-wallet', 'route' => "#" ],
                ];
            @endphp

            @foreach ($cards as $index => $card)
                <div class="col">
                    <a href="{{ $card['route'] }}" class="text-decoration-none">
                        <div class="card dashboard-card shadow-lg border-0 rounded-4 h-100">
                            <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                                <i class="{{ $card['icon'] }} icon mb-2"></i>
                                <p class="mb-1 fw-semibold">{{ $card['label'] }}</p>
                                <h4 class="mb-0 fw-bold">{{ number_format($card['value']) }}</h4>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach

        </div>
    </div>
</section>

<style>
/* ===== Dashboard Card Styling ===== */
.dashboard-card {
    border-radius: 16px;
    transition: all 0.4s ease-in-out;
    color: #fff;
    min-height: 140px;
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    background-size: 200% 200%;
}

/* Hover Animation (Gradient Shift) */
.dashboard-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 16px 30px rgba(0,0,0,0.2);
    background-position: right center; /* animate gradient */
}

/* Icon Styling */
.icon {
    font-size: 32px;
    flex-shrink: 0;
}

/* Typography */
.dashboard-card p {
    font-size: 15px;
    opacity: 0.9;
}
.dashboard-card h4 {
    font-size: 22px;
    word-break: break-word;
}

/* Gradient themes per card */
.row .col:nth-child(1) .dashboard-card {
    background: linear-gradient(135deg, #ff7eb3, #ff758c);
    background-size: 200% 200%;
}
.row .col:nth-child(2) .dashboard-card {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
    background-size: 200% 200%;
}
.row .col:nth-child(3) .dashboard-card {
    background: linear-gradient(135deg, #f7971e, #ffd200);
    background-size: 200% 200%;
}
.row .col:nth-child(4) .dashboard-card {
    background: linear-gradient(135deg, #667eea, #764ba2);
    background-size: 200% 200%;
}

/* ===== Responsive Fixes ===== */
@media (max-width: 767px) {
    .dashboard-card {
        padding: 20px;
    }
    .icon {
        margin-bottom: 8px;
    }
}
</style>
@endsection
