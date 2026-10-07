@extends('layouts.sellerdash')

@section('content')
    <div class="pc-container" style="background:#646dff26;" >
        <style>
            .circular-action-wrap {
                background: #fcfcfe;
                border-radius: 7px;
                box-shadow: 0 1px 10px 0 rgba(180,190,230,0.07);
                margin-bottom: 1.5rem;
                padding: 0.55rem 1.2rem 0.55rem 1.2rem;
                display: flex;
                height:57px;
                margin-left: 31px;
                margin-top: 16px;
                margin-right: 16px;
                align-items: center;
                justify-content: flex-start;
                gap: 1.5rem;
                /* width: 100%; */
            }
            .standard-action-group {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .standard-action-btn {
                background: #f3f6fa;
                color: #677193 !important;
                border: 1px solid #e3e8f3;
                border-radius: 6px;
                padding: 0.5rem 0.5rem;
                font-size: 0.88rem;
                height: 28px;
                min-width: 95px;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.27rem;
                box-shadow: none;
                transition: all 0.16s;
            }
            .standard-action-btn i {
                font-size: 0.95rem;
            }
            .standard-action-btn:hover, .standard-action-btn:focus {
                background: #eceffd;
                color: #232954 !important;
                border-color: #c7cde3;
                text-decoration: none;
            }
            .mini-btn-group {
                display: flex;
                flex-wrap: wrap;
                gap: 0.2rem;
            }
            .mini-btn {
                background: #f7faff;
                color: #626a8b !important;
                border: 1px solid #e3e8f3;
                border-radius: 7px;
                font-size: 0.81rem;
                height: 28px;
                min-width: 82px;
                padding: 0 0.4rem;
                font-weight: 500;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.15s;
            }
            .mini-btn:hover, .mini-btn.active, .mini-btn:focus {
                background: #eef3fe;
                color: #2b325a !important;
                border-color: #ced4e6;
                text-decoration: none;
            }
            @media (max-width: 800px) {
                .circular-action-wrap {
                    flex-direction: column;
                    align-items: stretch;
                    border-radius: 2rem;
                    gap: 0.75rem;
                    padding: 1rem 0.6rem;
                }
                .standard-action-group {
                    justify-content: flex-start;
                }
                .mini-btn-group {
                    flex-wrap: wrap;
                    gap: 0.35rem;
                }
            }
        </style>

        <div class="circular-action-wrap">
            <div class="standard-action-group">
                <a href="#" class="standard-action-btn" title="Sync Orders">
                    <i class="ti ti-refresh"></i>
                    <span>Sync</span>
                </a>
                <a href="#" class="standard-action-btn" title="Bulk Import">
                    <i class="ti ti-upload"></i>
                    <span>Import</span>
                </a>
                <a href="{{ route('seller.orderadd') }}" class="standard-action-btn" title="Add Order">
                    <i class="ti ti-plus"></i>
                    <span>Add</span>
                </a>
            </div>
            <div class="mini-btn-group">
                <a href="{{ route('seller.order') }}" class="mini-btn" onclick="setActive(this)">New</a>
                <a href="{{ route('seller.courier.Assigned') }}" class="mini-btn" onclick="setActive(this)">Assigned</a>
                <a href="{{ route('seller.courier.Cancelled') }}" class="mini-btn" onclick="setActive(this)">Cancelled</a>
                <a href="{{ route('seller.courier.InTransit') }}" class="mini-btn" onclick="setActive(this)">In Transit</a>
                <a href="{{ route('seller.courier.OutForDelivery') }}" class="mini-btn" onclick="setActive(this)">Out for Delivery</a>
                <a href="{{ route('seller.courier.Delivered') }}" class="mini-btn" onclick="setActive(this)">Delivered</a>
                <a href="{{ route('seller.courier.NDR') }}" class="mini-btn" onclick="setActive(this)">NDR</a>
                <a href="{{ route('seller.courier.RTO') }}" class="mini-btn" onclick="setActive(this)">RTO</a>
                <a href="{{ route('seller.courier.all') }}" class="mini-btn" onclick="setActive(this)">All</a>
                <a href="{{ route('seller.courier.other') }}" class="mini-btn" onclick="setActive(this)">Other</a>
            </div>
        </div>


        <div class=" mt-4" style="margin-left:30px; margin-right:18px;">
            <div class="bg-white rounded shadow-sm p-3">

                <!-- Top Bar -->
                <div class="d-flex justify-content-between align-items-center mb-3">

                    <!-- Left: Filter Icon & Date Input -->
                    <div class="d-flex align-items-center gap-2">
                        {{-- <i class="fas fa-filter filter-icon" data-bs-toggle="offcanvas" href="#offcanvasExample"></i> --}}
                        <input type="text" id="daterange" class="form-control date-input"
                            placeholder="Select Date Range" style="padding:4px 5px;">
                    </div>
                    {{-- @if ($rateCard)
                        @if ($rateCard->rate_pdf_1)
                            <a href="{{ asset('storage/' . $rateCard->rate_pdf_1) }}" class="btn btn-primary mb-2"
                                target="_blank">
                                Download Rate PDF 1
                            </a>
                        @endif

                        @if ($rateCard->rate_pdf_2)
                            <a href="{{ asset('storage/' . $rateCard->rate_pdf_2) }}" class="btn btn-primary mb-2"
                                target="_blank">
                                Download Rate PDF 2
                            </a>
                        @endif
                    @endif

                    @if ($commonPdf)
                        <a href="{{ asset('storage/' . $commonPdf) }}" class="btn btn-success mb-2" target="_blank">
                            Download Rate PDF 3
                        </a>
                    @endif --}}

                    <!-- Right: Icons (Settings, Refresh, Download, Width Setting) -->
                    <div class="d-flex align-items-center gap-3">
                        <!-- Refresh Icon -->
                        {{-- <i class="fas fa-sync-alt refresh-icon" title="Refresh"></i>

                        <!-- Download Icon -->
                        <i class="fas fa-download download-icon" title="Download"></i> --}}



                        <!-- Settings Dropdown -->
                        <div class="dropdown">
                            {{-- <i class="fas fa-cog settings-icon" data-bs-toggle="dropdown"></i> --}}
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#">Profile Settings</a></li>
                                <li><a class="dropdown-item" href="#">Table Preferences</a></li>
                                <li><a class="dropdown-item" href="#">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
 


                <!-- Responsive Table -->
                <style>
                    .premium-table {
                        border-radius: 14px;
                        overflow: hidden;
                        box-shadow: 0 4px 20px rgba(28,56,106,0.08);
                        background: #fff;
                        margin-bottom: 1.2rem;
                    }
                    .premium-table thead th {
                        background: linear-gradient(90deg, #3576e3 0, #306cc9 100%) !important;
                        color: #fff !important;
                        font-weight: 500;
                        font-size: 0.87rem;
                        border: none;
                        padding: 0.35rem 0.4rem !important;
                        height: 30px;
                        vertical-align: middle;
                        letter-spacing: 0.01em;
                        transition: background 0.2s;
                    }
                    .premium-table td, .premium-table th {
                        vertical-align: middle !important;
                        padding: 0.57rem 0.45rem;
                        border-color: #e2eafd;
                        background: inherit !important;
                    }
                    .premium-checkbox {
                        width: 17px;
                        height: 17px;
                        accent-color: #3576e3;
                        cursor: pointer;
                        margin: 0 auto;
                        display: block;
                    }
                    .product-item {
                        background: none !important;
                        border: none;
                        box-shadow: none;
                        display: block;
                        padding: 2px 0;
                        color: #243557;
                        font-size: 0.97em;
                        font-weight: 500;
                    }
                    .product-item span {
                        color: #7185a8;
                        font-size: 0.94em;
                        font-weight: 400;
                        margin-left: 2px;
                    }
                    /* Payment badges */
                    .payment-method-badge {
                        border-radius: 6px;
                        padding: 1.5px 5px 1.5px 5px;
                        margin: 0.15em 0 0 0;
                        font-size: 0.8rem;
                        vertical-align: middle;
                        display: inline-block;
                    }
                    .bg-cod {
                        background: #d1f8e5;
                        color: #13a75d;
                        border: 1px solid #aaf2d3;
                    }
                    .bg-prepaid {
                        background: #ddeafb;
                        color: #276bbb;
                        border: 1px solid #a4cdf2;
                    }
                    .premium-table .amount-display {
                        /* font-weight: 700; */
                        color: #274c8b;
                        font-size: 0.8em;
                        font-variant-numeric: tabular-nums;
                        display: block;
                    }
                    .collectable-amount-label {
                        font-size: 0.88em;
                        color: #8fa4ce;
                        font-weight: 600;
                        margin-bottom: 2px;
                        letter-spacing: 0.01em;
                        display: inline-block;
                    }
                    .collectable-amount-value {
                        display: block;
                        font-size: 0.8em;
                        
                        color: #2b4a95;
                    }
                    /* Responsive adjustments */
                    @media (max-width: 992px) {
                        .premium-table thead {
                            display: none;
                        }
                        .premium-table, .premium-table tbody, .premium-table tr, .premium-table td {
                            display: block;
                            width: 100%;
                        }
                        .premium-table tr {
                            background: #f6f8fb;
                            margin-bottom: 1rem;
                            border-radius: 6px;
                            box-shadow: 0 2px 8px rgba(39,86,182,.03);
                            padding: 0.6rem 0 0.4rem 0;
                        }
                        .premium-table td {
                            text-align: left;
                            padding-left: 40%;
                            position: relative;
                            border: none;
                            border-bottom: 1px solid #e2eafd;
                        }
                        .premium-table td:before {
                            position: absolute;
                            top: 0.5rem;
                            left: 1rem;
                            width: 36%;
                            padding-right: 1rem;
                            white-space: nowrap;
                            font-weight: 600;
                            color: #2560a5;
                            content: attr(data-label);
                            font-size: 0.98em;
                        }
                    }
                </style>

                <div class="table-responsive shadow-sm">
                    <table class="table table-bordered premium-table align-middle">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all" class="premium-checkbox"></th>
                                <th>Order ID</th>
                                <!-- <th>AWB Number</th> -->
                                <th>Product(s)</th>
                                <th>Payment</th>
                                <th>Collectable</th>
                                <th>Customer</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                @php
                                    $items = is_array($order->order_items)
                                        ? $order->order_items
                                        : json_decode($order->order_items, true);

                                    $consignee = is_array($order->consignee)
                                        ? $order->consignee
                                        : json_decode($order->consignee, true);

                                    // Normalize payment type
                                    $payType = strtolower($order->payment_type ?? '');
                                    $is_prepaid = in_array($payType, ['prepaid', 'pre-paid']);
                                    $is_cod = in_array($payType, ['cod', 'cash on delivery']);

                                    // Payment label and style
                                    $payment_method = $is_cod ? 'COD' : 'Prepaid';
                                    $payment_badge = $is_cod ? 'bg-cod' : 'bg-prepaid';
                                @endphp

                                <!-- Main Row -->
                                <tr>
                                    <td data-label="Select" class="align-middle">
                                        <input type="checkbox" class="order-checkbox premium-checkbox" value="{{ $order->id }}">
                                    </td>
                                    <td data-label="Order" class="font-monospace fw-bold align-middle">
                                        {{ $order->order_number }}
                                    </td>
                                    <!-- <td data-label="AWB Number" class="align-middle">
                                        {{ $order->awb_number ?? 'N/A' }}
                                    </td> -->
                                    <td data-label="Product(s)" class="align-middle">
                                        @if (!empty($items))
                                            @foreach ($items as $item)
                                                <div class="product-item">
                                                    {{ $item['name'] ?? 'N/A' }}
                                                    <span>
                                                        (Qty: {{ $item['quantity'] ?? '1' }}
                                                        @if(isset($item['weight']))
                                                            , {{ $item['weight'] }}gms
                                                        @elseif(isset($order->package_weight) && count($items) == 1)
                                                            , {{ $order->package_weight }}gms
                                                        @endif
                                                        )
                                                    </span>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="product-item">N/A</div>
                                        @endif
                                    </td>
                                    <td data-label="Payment" class="align-middle">
                                        <span class="amount-display">&#8377;{{ number_format($order->order_amount, 2) }}</span>
                                        <span class="payment-method-badge {{ $payment_badge }}">{{ $payment_method }}</span>
                                    </td>
                                    <td data-label="Collectable Amount" class="align-middle">
                                        <span class="collectable-amount-value">&#8377;{{ number_format($order->collectable_amount, 2) }}</span>
                                    </td>
                                    <td data-label="Customer" class="align-middle">
                                        {{ $consignee['name'] ?? 'N/A' }}
                                    </td>
                                    <td data-label="Status" class="align-middle">
                                        @php
                                            // Map status to light shade bg
                                            $status = strtolower($order->shipping_status ?? 'na');
                                            $statusBg = [
                                                'new' => 'bg-info-subtle',
                                                'assigned' => 'bg-info-subtle',
                                                'cancelled' => 'bg-danger-subtle',
                                                'in transit' => 'bg-primary-subtle',
                                                'out for delivery' => 'bg-warning-subtle',
                                                'delivered' => 'bg-success-subtle',
                                                'ndr' => 'bg-secondary-subtle',
                                                'rto' => 'bg-dark-subtle',
                                                'other' => 'bg-light',
                                                'na' => 'bg-light'
                                            ][$status] ?? 'bg-light';
                                        @endphp
                                        <span class="status-badge {{ $statusBg }} text-dark px-3 py-1 rounded">
                                            {{ $order->shipping_status ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                               
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-secondary py-4">No orders found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($orders->count())
                        <div class="mt-3">
                            {{ $orders->links() }}
                        </div>
                    @else
                        <p class="text-center mt-3 text-muted">No orders found.</p>
                    @endif
                </div>

            </div>
        </div>
    </div>


<!-- Popup Modal -->
<div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Courier List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Courier Name</th>
                            <th>Processed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Delhivery</td>
                            <td>1</td>
                        </tr>
                    </tbody>
                </table>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    {{-- <a href="#" class="btn btn-primary">Download Manifest</a>
                    <a href="#" class="btn btn-primary">Download Label</a> --}}

                    <a href="#" class="btn btn-primary download-manifest" target="_blank">Download Manifest</a>
               <a href="#" class="btn btn-primary download-label" target="_blank">Download Label</a>


                    {{-- <a href="#" class="btn btn-primary download-manifest">Download Manifest</a>
                      <a href="#" class="btn btn-primary download-label">Download Label</a> --}}

                </div>
            </div>
        </div>
    </div>
</div>



<script>
document.querySelectorAll('.open-popup').forEach(button => {
    button.addEventListener('click', function () {
        const orderId = this.getAttribute('data-order-id');

        fetch("{{ route('get.courier.info') }}", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ order_id: orderId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            document.querySelector('#downloadModal tbody').innerHTML = `
                <tr>
                    <td>${data.courier}</td>
                    <td>${data.processed}</td>
                </tr>
            `;

            // Update links dynamically
            const manifestBtn = document.querySelector('.download-manifest');
            const labelBtn = document.querySelector('.download-label');

            if (data.manifest_url) {
                manifestBtn.href = data.manifest_url;
                manifestBtn.style.display = 'inline-block';
            } else {
                manifestBtn.href = '#';
                manifestBtn.style.display = 'none';
            }

            if (data.label_url) {
                labelBtn.href = data.label_url;
                labelBtn.style.display = 'inline-block';
            } else {
                labelBtn.href = '#';
                labelBtn.style.display = 'none';
            }

            const modal = new bootstrap.Modal(document.getElementById('downloadModal'));
            modal.show();
        })
        .catch(error => {
            console.error("Error fetching courier info:", error);
            alert("Something went wrong.");
        });
    });
});
</script>


{{-- 
<script>
document.querySelectorAll('.open-popup').forEach(button => {
    button.addEventListener('click', function () {
        const orderId = this.getAttribute('data-order-id');

        fetch("{{ route('get.courier.info') }}", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ order_id: orderId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            // Update modal content
            document.querySelector('#downloadModal tbody').innerHTML = `
                <tr>
                    <td>${data.courier}</td>
                    <td>${data.processed}</td>
                </tr>
            `;

            document.querySelector('.download-manifest').setAttribute('href', data.manifest_url);
            document.querySelector('.download-label').setAttribute('href', data.label_url);
            document.querySelector('.download-manifest').setAttribute('target', '_blank');
            document.querySelector('.download-label').setAttribute('target', '_blank');

            const modal = new bootstrap.Modal(document.getElementById('downloadModal'));
            modal.show();
        })
        .catch(error => {
            console.error("Error fetching courier info:", error);
            alert("Something went wrong.");
        });
    });
});
</script> --}}



{{-- <script>
    document.querySelectorAll('.open-popup').forEach(button => {
        button.addEventListener('click', function () {
            // You can use order-id if needed to customize modal content later
            const orderId = this.getAttribute('data-order-id');

            // Open Bootstrap modal
            const modal = new bootstrap.Modal(document.getElementById('downloadModal'));
            modal.show();
        });
    });
</script> --}}

    

<script>

    document.getElementById('courier-form').addEventListener('submit', function (e) {
        
        let selected = Array.from(document.querySelectorAll('.order-checkbox:checked'))
                            .map(checkbox => checkbox.value);
        if (selected.length === 0) {
            e.preventDefault();
            alert('Please select at least one order.');
            return;
        }

        document.getElementById('selected-orders').value = selected.join(',');
    });

    // Select all toggle
    document.getElementById('select-all').addEventListener('change', function () {
        let checkboxes = document.querySelectorAll('.order-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>


@endsection



