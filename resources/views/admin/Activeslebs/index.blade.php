@extends('layouts.app')

@section('css')
<style>
    .courier-box {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
        background-color: #f9f9f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: nowrap;
        gap: 1rem;
        box-sizing: border-box;
        /* Height management */
        min-height: 80px;
        height: 80px;
        transition: box-shadow 0.2s;
    }
    .courier-box:hover {
        box-shadow: 0 6px 24px rgba(0,0,0,0.05);
    }

    .courier-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
        flex: 1 1 auto;
    }

    .courier-box img {
        height: 40px;
        width: 40px;
        object-fit: contain;
        flex-shrink: 0;
    }
    .courier-text {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .courier-box h6 {
        margin-bottom: 0;
        word-break: break-all;
        font-weight: 600;
        font-size: 1.08rem;
    }
    .courier-box small {
        color: #555;
        font-size: 0.87em;
        white-space: nowrap;
    }
    .courier-status {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 8px;
        margin-left: 1rem;
        flex-shrink: 0;
        min-width: 120px;
        justify-content: flex-end;
        height: 100%;
    }

    /* Responsive styles */
    @media (max-width: 991.98px) {
        .courier-box {
            min-height: 62px;
            height: auto;
        }
        .courier-info {
            gap: 0.8rem;
        }
        .courier-status {
            margin-left: auto;
            margin-top: 0;
            flex-direction: row;
            justify-content: flex-end;
            min-width: 100px;
        }
        .courier-text h6, .courier-text small {
            font-size: 0.98em;
        }
    }
    @media (max-width: 500px) {
        .courier-box {
            font-size: 0.97em;
            min-height: 56px;
            padding: 10px;
        }
        .courier-box img {
            height: 32px;
            width: 32px;
        }
        .courier-box h6 {
            font-size: 0.985rem;
        }
        .courier-status {
            min-width: 80px;
        }
    }
</style>
@endsection

@section('content')
<div class="card mb-3" style="padding: 19px 3px;">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Price Setting :: Courier Status</h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('pricesetting.add') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Go Back
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">

        {{-- =============== SELLER DROPDOWN =============== --}}
        <div class="col-lg-6 mt-2 mb-4">
            <label class="form-label" for="seller_id">Select Seller</label>
            <select class="form-control" id="seller_id" name="seller_id" required>
                <option value="">-- Select Seller --</option>
                <option value="all" {{ request('seller_id') == 'all' ? 'selected' : '' }}>All Sellers</option>
                @foreach($SellerList as $seller)
                    <option value="{{ $seller->id }}"
                        {{ request('seller_id') == $seller->id ? 'selected' : '' }}>
                        {{ $seller->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- ALWAYS SHOW COURIER LIST --}}
        <div class="col-12 mt-2">
            <h5>Courier Services / Partners</h5>
            @php
                $couriers = [
                    ['name' => 'Delhivery', 'logo' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg'],
                    ['name' => 'Delhivery_Air', 'logo' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg'],
                    ['name' => 'Shadowfax', 'logo' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg'],
                    ['name' => 'Ekart2KG_selloship', 'logo' => 'brands/ecart.png'],
                    ['name' => 'DTDC_Air', 'logo' => 'brands/1750337596_images-removebg-preview.png'],
                    ['name' => 'DTDC_Surface_1kg', 'logo' => 'brands/1750337596_images-removebg-preview.png'],
                    ['name' => 'DTDC_Surface_500gm', 'logo' => 'brands/1750337596_images-removebg-preview.png'],
                    ['name' => 'tekipost_Delhivery_1_KG','logo' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg'],
                    ['name' => 'tekipost_Delhivery_5kg','logo' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg'],
                    ['name' => 'tekipost_Delhivery_10kg','logo' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg'],
                    ['name' => 'tekipost_Ekart_2_KG_Fixed', 'logo' => 'brands/ecart.png'],
                    ['name' => 'tekipost_Amazon_500_GM', 'logo' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg'],
                    ['name' => 'tekipost_Amazon_2_kg', 'logo' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg'],
                    ['name' => 'Parcel_X_Amazon_2KG', 'logo' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg'],
                    ['name' => 'Parcel_X_Amazon_1KG', 'logo' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg'],
                    ['name' => 'Parcel_X_Amazon', 'logo' => 'brands/1703c4f8-55a6-4923-8c94-9bcb98f39c2d.jpeg'],
                    ['name' => 'Parcel_X_Delhivery', 'logo' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg'],
                    ['name' => 'Shiprocket_Bluedart','logo' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png'],
                    ['name' => 'shiprocket_bluedart_1kg','logo' => 'brands/1750337705_WhatsApp_Image_2025-06-19_at_5.17.34_PM__1_-removebg-preview.png'],
                    ['name' => 'Shiprocket_Delhivery','logo' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg'],
                    ['name' => 'Shiprocket_Xpressbee','logo' => 'brands/Xpressbee.png'],
                    ['name' => 'Parcel_X_Delhivery_250gm', 'logo' => 'brands/45438f4b-44ad-4c1a-bd0e-d08695b2dc16.jpeg'],
                    ['name' => 'Ekart500gm','logo' => 'brands/ecart.png'],
                    ['name' => 'Ekart500gm_boxd','logo' => 'brands/ecart.png'],
                ];
                // If seller selected, load saved status
                if(request('seller_id') && request('seller_id') != 'all'){
                    $dbStatus = \App\Models\ActicvSleb::where('seller_id', request('seller_id'))
                                ->pluck('status','LogisticProvider')->toArray();
                } elseif(request('seller_id') == 'all') {
                    // For 'all', show status as active only if ALL sellers have it active
                    $totalSellers = \App\Models\SellerList::count();
                    $dbStatus = [];
                    foreach($couriers as $courier) {
                        $activeSellersCount = \App\Models\ActicvSleb::where('LogisticProvider', $courier['name'])
                                            ->where('status', '1')
                                            ->count();
                        $dbStatus[$courier['name']] = ($activeSellersCount == $totalSellers) ? 1 : 0;
                    }
                } else {
                    $dbStatus = [];
                }
            @endphp

            <div class="row">
            @foreach($couriers as $c)
                @php
                    $name = $c['name'];
                    $logo = $c['logo'];
                    $isActive = $dbStatus[$name] ?? 0;
                @endphp
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="courier-box">
                        <div class="courier-info">
                            <img src="{{ $logo }}" alt="{{ $name }} logo">
                            <div class="courier-text">
                                <h6 class="mb-0">{{ $name }}</h6>
                                <small class="text-muted">Courier Service</small>
                            </div>
                        </div>
                        <div class="courier-status">
                            <div class="form-check form-switch m-0">
                                <input type="checkbox"
                                    class="form-check-input courier-toggle"
                                    data-courier="{{ $name }}"
                                    {{ $isActive ? 'checked' : '' }}>
                            </div>
                            <span class="badge {{ $isActive ? 'bg-success' : 'bg-secondary' }} ms-2">
                                {{ $isActive ? 'ACTIVE' : 'INACTIVE' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ================= AJAX SCRIPT ================= --}}
<script>
document.getElementById('seller_id').addEventListener('change', function () {
    window.location.href = "?seller_id=" + this.value;
});

document.addEventListener('DOMContentLoaded', function () {
    const toggles = document.querySelectorAll('.courier-toggle');

    toggles.forEach(function (toggle) {
        toggle.addEventListener('change', function () {

            let status = this.checked ? 1 : 0;
            let courier = this.getAttribute('data-courier');
            let seller_id = document.getElementById('seller_id').value;
            let badge = this.closest('.courier-status').querySelector('.badge');

            // If seller is NOT selected, block update
            if (seller_id === "") {
                alert("Please select a seller first!");
                this.checked = !this.checked; // revert toggle
                return;
            }

            // If 'all' is selected, confirm bulk update
            if (seller_id === "all") {
                let confirmMsg = status ? 
                    "Are you sure you want to ACTIVATE this courier for ALL sellers?" :
                    "Are you sure you want to DEACTIVATE this courier for ALL sellers?";
                
                if (!confirm(confirmMsg)) {
                    this.checked = !this.checked; // revert toggle
                    return;
                }
            }

            // Update badge UI instantly
            if (status) {
                badge.className = "badge bg-success ms-2";
                badge.textContent = "ACTIVE";
            } else {
                badge.className = "badge bg-secondary ms-2";
                badge.textContent = "INACTIVE";
            }

            // Send AJAX Request
            fetch("{{ route('pricesetting.courier.update') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    seller_id: seller_id,
                    courier: courier,
                    status: status
                })
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => console.log("Updated", data))
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update courier status');
            })
        });
    });
});
</script>
@endsection
