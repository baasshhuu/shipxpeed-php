@extends('layouts.app')
@include('partial.sellerdash.common.header')
@section('content')
<section id="widgets" class="py-4">
    <div class="container-fluid">
        <div 
            class="row g-3 
                row-cols-3 
                row-cols-sm-3 
                row-cols-md-3 
                row-cols-lg-6"
        >
            @php
                // First three from backend, other three static
                $cards = [
                    [ 'label' => 'Today Orders', 'value' => $todayOrder, 'icon' => 'fa-solid fa-calendar-check', 'route' => "#" ],
                    [ 'label' => 'Total RTO Orders', 'value' => '120', 'icon' => 'fa-solid fa-undo-alt', 'route' => "#" ],
                    [ 'label' => 'Total COD', 'value' => '32', 'icon' => 'fa-solid fa-money-bill-wave', 'route' => "#" ],
                    [ 'label' => 'Total Weight Disputes', 'value' => '2.4d', 'icon' => 'fa-solid fa-balance-scale', 'route' => "#" ],
                    [ 'label' => 'All Shipment', 'value' => $allOrder, 'icon' => 'fa-solid fa-shipping-fast', 'route' => route('shipment.report') ],
                    [ 'label' => 'All Recharge', 'value' => $sellerRechargeAmount, 'icon' => 'fa-solid fa-coins', 'route' => "#" ]
                ];
            @endphp

            @foreach ($cards as $index => $card)
                <div class="col-6 col-sm-4 col-md-2">
                    <a href="{{ $card['route'] }}" class="text-decoration-none">
                        <div class="mini-dashboard-card d-flex flex-column justify-content-center align-items-center text-center">
                            <div class="icon-section d-flex align-items-center justify-content-center" style="height:fit-content;width:100%;">
                                <i class="{{ $card['icon'] }} icon mb-1"></i>
                            </div>
                            <p class="mb-1 fw-semibold">{{ $card['label'] }}</p>
                            <h5 class="mb-0 fw-bold">
                                {{ is_numeric($card['value']) ? number_format($card['value']) : $card['value'] }}
                            </h5>
                        </div>
                    </a>
                </div>
            @endforeach

        </div>
    </div>
</section>
<style>
    .mini-dashboard-card {
        border-radius: 10px;
        border: none;
        background: #fff;
        padding: 12px 4px 10px 4px;
        color: #16233c;
        transition: box-shadow 0.22s, transform 0.18s;
        height: fit-content;
        min-height: unset;
        max-height: unset;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        /* white 3D box shadow for a 3D look */
        box-shadow: 
        0 8px 18px 0 rgba(32,52,129,0.10),  /* subtle blue shadow below */
        0 1.5px 2px 0 rgba(0,0,0,0.04),      /* soft neutral shadow top */
        0 0.5px 0px 0 rgba(133,133,173,0.09); /* inner highlight bottom */
        /* extra touch of 3D by lifting slightly */
        position: relative;
    }
    .mini-dashboard-card:before {
        /* Subtle highlight effect for top 3D shine */
        content: '';
        display: block;
        position: absolute;
        top: 0; left: 10%; right: 10%; height: 16%;
        border-radius: 10px 10px 6px 6px/9px 9px 4px 4px;
        background: linear-gradient(rgba(255,255,255,0.76),rgba(255,255,255,.16));
        pointer-events: none;
    }

    .mini-dashboard-card:hover {
        box-shadow: 
        0 16px 42px 0 rgba(32,52,129,0.16),
        0 2px 6px 0 rgba(0,0,0,0.065);
        transform: translateY(-3px) scale(1.025);
    }

    .icon-section {
        height: fit-content !important;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
    }

    .mini-dashboard-card .icon {
        font-size: 1.16rem;
        color: #233962;
        opacity: 0.93;
        margin-bottom: 2px;
        margin-top: 2px;
        height: fit-content !important;
        line-height: 1;
        width: fit-content;
    }
    .mini-dashboard-card p {
        font-size: 12px;
        margin-bottom: 1px !important;
        color: #233962;
        opacity: 0.92;
    }
    .mini-dashboard-card h5 {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 0;
        margin-top: 1px;
        color: #1a2443;
        word-break: break-word;
    }

    @media (max-width: 991.98px) {
        .mini-dashboard-card {
            font-size: 13px;
            padding: 9px 2px;
        }
        .mini-dashboard-card .icon { font-size: 1.01rem; }
        .mini-dashboard-card h5 { font-size: 14px; }
    }
    @media (max-width: 575.98px) {
        .mini-dashboard-card {
            font-size: 11px;
            padding: 6px 1px;
        }
        .mini-dashboard-card .icon { font-size: 0.96rem; }
        .mini-dashboard-card h5 { font-size: 12px; }
    }

    /* Remove default <a> color but keep hover underline off */
    .mini-dashboard-card a, .mini-dashboard-card a:visited, .mini-dashboard-card a:hover {
        color: inherit;
        text-decoration: none;
    }
</style>

<section>
    <div class="container-fluid" style="padding-left: 23px;
    padding-right: 16px;">
        <div class="row g-4">
            <!-- Section 1: Total Orders -->
            <div class="col-12 col-md-4 d-flex align-items-stretch">
                <div class="card w-100" style="height: fit-content; min-height: 440px;">
                    <div class="card-body d-flex flex-column justify-content-between" style="height: fit-content;">
                        <h5 class="card-title text-center mb-4">Total Orders</h5>
                        <canvas id="ordersChart" height="210" style="max-height: 210px;"></canvas>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Today</span>
                                <span><strong>45</strong></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Last 7 Days</span>
                                <span><strong>315</strong></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Last 1 Month</span>
                                <span><strong>1240</strong></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total Till Date</span>
                                <span><strong>8921</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Section 2: Total COD To Be Paid -->
            <div class="col-12 col-md-4 d-flex align-items-stretch">
                <div class="card w-100" style="height: fit-content; min-height: 440px;">
                    <div class="card-body d-flex flex-column justify-content-between" style="height: fit-content;">
                        <h5 class="card-title text-center mb-4">Total COD To Be Paid</h5>
                        <canvas id="codChart" height="210" style="max-height: 210px;"></canvas>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Today</span>
                                <span><strong>₹2,100</strong></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Last 7 Days</span>
                                <span><strong>₹17,670</strong></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Last 1 Month</span>
                                <span><strong>₹62,430</strong></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Total Till Date</span>
                                <span><strong>₹551,830</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Section 3: Total Order Zone-wise Split (diagram above text, same height as others!) -->
            <div class="col-12 col-md-4 d-flex align-items-stretch">
                <div class="card w-100" style="height: fit-content; min-height: 440px;">
                    <div class="card-body d-flex flex-column justify-content-between" style="height: fit-content;">
                        <h5 class="card-title text-center mb-2">Total Orders Zone-wise</h5>
                        <div class="d-flex flex-column align-items-center" style="height: fit-content;">
                            <canvas id="zoneChart" height="210" style="max-height: 210px;"></canvas>
                            <div class="w-100">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Zone A</span>
                                    <span><strong>340</strong></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Zone B</span>
                                    <span><strong>265</strong></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Zone C</span>
                                    <span><strong>188</strong></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Zone D</span>
                                    <span><strong>121</strong></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Zone E</span>
                                    <span><strong>78</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Orders Chart (Bar)
        const ordersCtx = document.getElementById('ordersChart').getContext('2d');
        new Chart(ordersCtx, {
            type: 'bar',
            data: {
                labels: ['Today', 'Last 7 Days', 'Last 1 Month', 'Total'],
                datasets: [{
                    label: 'Orders',
                    data: [45, 315, 1240, 8921],
                    backgroundColor: [
                        '#275d8c', '#367ecc', '#7db5e6', '#33c779'
                    ],
                    borderRadius: 6,
                    maxBarThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { font: { size: 12 } } }, x: { ticks: { font: { size: 12 } } } }
            }
        });

        // COD Chart (Line)
        const codCtx = document.getElementById('codChart').getContext('2d');
        new Chart(codCtx, {
            type: 'line',
            data: {
                labels: ['Today', 'Last 7 Days', 'Last 1 Month', 'Total'],
                datasets: [{
                    label: 'COD (₹)',
                    data: [2100, 17670, 62430, 551830],
                    fill: true,
                    backgroundColor: 'rgba(51,199,121,0.10)',
                    borderColor: '#33c779',
                    tension: 0.3,
                    pointBackgroundColor: '#33c779',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { font: { size: 12 } } }, x: { ticks: { font: { size: 12 } } } }
            }
        });

        // Zone Split (Pie)
        const zoneCtx = document.getElementById('zoneChart').getContext('2d');
        new Chart(zoneCtx, {
            type: 'pie',
            data: {
                labels: ['Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
                datasets: [{
                    label: 'Orders',
                    data: [340, 265, 188, 121, 78],
                    backgroundColor: [
                        '#3461ad','#33c779','#ffb84c','#f96c6c','#a885d8'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // hide legend to avoid duplicate info beside text
                    },
                }
            }
        });
    </script>
</section>

<section class="container-fluid" style="padding-left:20px; padding-right:20px;">
    <div class="row">
        <!-- Table: always col-12 -->
        <div class="col-12 mb-2">
            <div class="table-responsive">
                <table class="table table-bordered table-striped w-100">
                    <thead style="background-color: #f4f8fb; text-align: center;">
                        <tr>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Last Recharge Date</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody style="text-align: center;">
                        <tr>
                            <td>Rohit Sharma</td>
                            <td>rohit.sharma@example.com</td>
                            <td>9876543210</td>
                            <td>Never</td>
                            <td>₹0.00</td>
                        </tr>
                        <tr>
                            <td>Priya Verma</td>
                            <td>priya.verma@example.com</td>
                            <td>9834562312</td>
                            <td>15 Feb 2023</td>
                            <td>₹61.25</td>
                        </tr>
                        <tr>
                            <td>Aditya Singh</td>
                            <td>aditya.singh@example.com</td>
                            <td>9887651235</td>
                            <td>Never</td>
                            <td>₹0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Graph: col-12 on mobile, col-lg-4 on desktop -->
        <div class="col-12 col-lg-4 mb-4 d-flex flex-column align-items-center justify-content-center">
            <div class="card w-100 shadow border-0" style="background: linear-gradient(135deg,#f7fafc 60%,#d1e3f6 100%);">
                <div class="card-body">
                    <h6 class="text-center mb-3" style="font-weight: 600; color: #174ea6;">Active vs Inactive Clients</h6>
                    <div style="
                        width:100%;
                        max-width:340px;
                        height:260px;
                        margin:auto;
                        background: #fff;
                        border-radius: 25px;
                        box-shadow: 0 4px 32px 0 rgba(80,145,255,0.14);
                        padding: 32px 24px 24px 24px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <canvas id="clientStatusChart"></canvas>
                    </div>
                    <div style="width:80%;margin:auto;display:flex;justify-content:space-between;padding-top:24px;">
                        <div style="text-align:center;">
                            <span style="display:inline-block;width:16px;height:16px;border-radius:4px;background:linear-gradient(90deg,#2ecc9b 70%,#5bedb2);margin-right:8px;border:1px solid #b7efdf;"></span>
                            <span style="font-weight:600;color:#31a779">Active</span>
                        </div>
                        <div style="text-align:center;">
                            <span style="display:inline-block;width:16px;height:16px;border-radius:4px;background:linear-gradient(90deg,#ff7167 60%,#fdba86);margin-right:8px;border:1px solid #ffd4c2;"></span>
                            <span style="font-weight:600;color:#ff7167">Inactive</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Example data for active/inactive ratio
        const activeClients = 1;
        const inactiveClients = 2;

        const ctx = document.getElementById('clientStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive'],
                datasets: [{
                    data: [activeClients, inactiveClients],
                    backgroundColor: [
                        // Will set gradient below in beforeDraw
                        '#2ecc9b',
                        '#ff7167'
                    ],
                    borderWidth: 5,
                    borderColor: ['#ffffff', '#ffffff'],
                    hoverBorderWidth: 7,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#222',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#eee',
                        borderWidth: 1,
                        padding: 12,
                        caretPadding: 10,
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.raw || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percent = total ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} (${percent}%)`;
                            }
                        }
                    }
                }
            },
            plugins: [
                {
                    id: 'premiumDoughnutGradients',
                    beforeDraw: chart => {
                        // Set gradients as backgroundColor for Chart.js (canvas context gradients)
                        const { ctx, chartArea } = chart;
                        if (!chartArea) return; // skip until area is calculated
                        const dataset = chart.data.datasets[0];
                        // Active gradient
                        const g1 = ctx.createLinearGradient(chartArea.left, chartArea.top, chartArea.right, chartArea.bottom);
                        g1.addColorStop(0, "#2ecc9b");
                        g1.addColorStop(1, "#5bedb2");
                        // Inactive gradient
                        const g2 = ctx.createLinearGradient(chartArea.left, chartArea.top, chartArea.right, chartArea.bottom);
                        g2.addColorStop(0, "#ff7167");
                        g2.addColorStop(1, "#fdba86");
                        dataset.backgroundColor = [g1, g2];
                    }
                },
                {
                    id: 'premiumDoughnutCenterText',
                    afterDraw: chart => {
                        // Draw % and label elegantly in the center
                        const {ctx, chartArea: {left, right, top, bottom, width, height}} = chart;
                        ctx.save();
                        const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        const main = `${Math.round((activeClients/total)*100)}%`;
                        ctx.font = "bold 2.2rem 'Segoe UI', Arial";
                        ctx.fillStyle = "#31a779";
                        ctx.textAlign = "center";
                        ctx.textBaseline = "middle";
                        ctx.fillText(main, left + width/2, top + height/2 - 10);
                        ctx.font = "500 1rem 'Segoe UI', Arial";
                        ctx.fillStyle = "#7f838c";
                        ctx.fillText("Active", left + width/2, top + height/2 + 23);
                        ctx.restore();
                    }
                }
            ]
        });
    </script>
</section>

@endsection
