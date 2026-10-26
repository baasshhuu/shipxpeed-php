@extends('layouts.app')

@section('content')
@php
    $docs = [
        'pan_card' => 'PAN card',
        'adhar_card_front' => 'Aadhaar (front)',
        'adhar_card_back' => 'Aadhaar (back)',
        'gst_photo' => 'GST certificate',
        'cancel_cheque' => 'Cancelled cheque',
        'profile' => 'Profile photo',
    ];
    $verified = array_filter([
        'PAN number' => $cms->pan_number ?? null,
        'Name on PAN' => $cms->pan_verified_name ?? null,
        'GSTIN' => ($cms->gst_number ?? null) ?: ($cms->gst_no ?? null),
        'Name on GST' => $cms->gst_verified_name ?? null,
        'Bank' => $cms->bank_name ?? null,
        'Account no.' => !empty($cms->account_number) ? '•••• ' . substr($cms->account_number, -4) : null,
        'IFSC' => $cms->ifsc_code ?? null,
    ]);
    $addr = $cms->sellerAddress ?? null;
@endphp

<div class="sx-page-head">
    <div>
        <h2>{{ $cms->name ?: 'Seller #' . $cms->id }}</h2>
        <p>
            Seller #{{ $cms->id }} · joined {{ optional($cms->created_at)->format('d M Y') }}
            · {!! $cms->status == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' !!}
            {!! $cms->kyc_status == 1 ? '<span class="badge bg-success">KYC verified</span>' : '<span class="badge bg-warning">KYC pending</span>' !!}
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('seller-list') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i>All sellers</a>
        <a href="{{ route('courier.rate.manager') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-truck-fast me-1"></i>Couriers &amp; rates</a>
        <a href="{{ route('seller.impersonate', $cms->id) }}" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Open seller panel</a>
    </div>
</div>

<form id="ediUser" method="POST" action="{{ route('seller-list.edit', $cms->id) }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h5>Profile</h5></div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Name</label>
                        <input class="form-control" id="name" name="name" type="text" value="{{ old('name', $cms->name) }}" />
                        @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="user_type">Account type</label>
                        <select class="form-select" id="user_type" name="user_type">
                            <option value="1" @selected(old('user_type', $cms->user_type) == 1)>Individual</option>
                            <option value="2" @selected(old('user_type', $cms->user_type) == 2)>Business</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email (login)</label>
                        <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $cms->email) }}" />
                        @error('email')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone_number">Phone</label>
                        <input class="form-control" id="phone_number" name="phone_number" type="text" value="{{ old('phone_number', $cms->phone_number) }}" />
                        @error('phone_number')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="gst_no">GST number</label>
                        <input class="form-control" id="gst_no" name="gst_no" type="text" value="{{ old('gst_no', $cms->gst_no) }}" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="new_password">Set new password <span class="text-muted">(optional)</span></label>
                        <input class="form-control" id="new_password" name="new_password" type="text" autocomplete="new-password" placeholder="Leave blank to keep current" />
                        @error('new_password')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                    </div>
                    @if ($addr)
                        <div class="col-12">
                            <label class="form-label">Pickup / business address</label>
                            <div class="form-control" style="height:auto">{{ $addr->address_line }}{{ $addr->pincode ? ' — ' . $addr->pincode : '' }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5>KYC documents</h5></div>
                <div class="card-body row g-3">
                    @foreach ($docs as $field => $label)
                        <div class="col-md-6">
                            <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                @if (!empty($cms->$field))
                                    <a href="{{ asset('storage/' . $cms->$field) }}" target="_blank" rel="noopener" class="sx-chip ok"><i class="fa-solid fa-eye me-1"></i>View current</a>
                                @else
                                    <span class="sx-chip miss">Not uploaded</span>
                                @endif
                            </div>
                            <input class="form-control" id="{{ $field }}" name="{{ $field }}" type="file" accept="image/*,.pdf" />
                            @error($field)<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h5>Account controls</h5></div>
                <div class="card-body d-grid gap-3">
                    <div>
                        <label class="form-label" for="status">Account status</label>
                        <select name="status" class="form-select" id="status">
                            <option value="1" @selected(old('status', $cms->status) == 1)>Active — can log in and ship</option>
                            <option value="0" @selected(old('status', $cms->status) == 0)>Inactive — blocked</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="kyc_status">KYC status</label>
                        <select name="kyc_status" class="form-select" id="kyc_status">
                            <option value="1" @selected(old('kyc_status', $cms->kyc_status) == 1)>Verified</option>
                            <option value="0" @selected(old('kyc_status', $cms->kyc_status) != 1)>Pending</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="negative_balance">Negative wallet balance</label>
                        <select name="negative_balance" class="form-select" id="negative_balance">
                            <option value="1" @selected(old('negative_balance', $cms->negative_balance) == 1)>Allowed</option>
                            <option value="0" @selected(old('negative_balance', $cms->negative_balance) == 0)>Not allowed</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="fixed_price">RTO charge</label>
                        {{-- stored inverted: 0 = charge RTO, 1 = don't charge --}}
                        <select name="fixed_price" class="form-select" id="fixed_price">
                            <option value="0" @selected(old('fixed_price', $cms->fixed_price) == 0)>Charge RTO</option>
                            <option value="1" @selected(old('fixed_price', $cms->fixed_price) == 1)>Don't charge RTO</option>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100 submitbtn" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Save changes</button>
                    <small class="text-muted">Saved changes show up in this seller's panel straight away.</small>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5>Verified KYC data</h5></div>
                <div class="card-body">
                    @forelse ($verified as $k => $v)
                        <div class="d-flex justify-content-between py-1" style="border-bottom:1px solid var(--sx-border)">
                            <span class="text-muted">{{ $k }}</span><span class="fw-semibold text-end">{{ $v }}</span>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No verified KYC data yet. It appears here once the seller completes PAN, GST and bank checks.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('js')
<script>
    $(function () {
        $('#ediUser').on('submit', function () {
            $(this).find('.submitbtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Saving…');
        });
    });
</script>
@endsection
