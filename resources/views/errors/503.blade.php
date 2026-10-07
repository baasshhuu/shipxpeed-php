@php($site_settings=\App\Models\Setting::whereSettingType(1)->get()->pluck('filed_value','setting_name')->toArray())

@extends('layouts.error')

@section('title', 'Under Maintenance')

@section('style')
<style>
    body:before {
        display: none;
    }

    body.error {
        color: #888ea8;
        height: 100%;
        font-size: 0.875rem;
        background: #fafafa;
        background-image: linear-gradient(to bottom, #a8edea 0%, #fed6e3 100%);
    }

    .min-vh-80 {
        min-height: 80vh !important;
    }

    .py-vh-10 {
        padding-top: 10vh !important;
        padding-bottom: 10vh !important;
    }

    .theme-logo {
        max-width: 250px;
    }

    .maintanence .maintanence-hero-img img {
        max-width: 100px;
        width: 100px;
    }
</style>
@endsection

@section('content')
<div class="container py-vh-10">
    <div class="row min-vh-80 justify-content-center align-items-center">
        <div class="col-md-8">
            <div class="maintanence-hero-img text-center mb-4">
                <a href="{{ route('home') }}">
                    <img alt="logo" src="{{ asset('storage/' . $site_settings['logo']) }}" class="theme-logo">
                </a>
            </div>
            <h1 class="error-title">Under Maintenance</h1>
            <p class="fs-4">Thank you for visiting us.</p>
            <p class="fs-6">We are currently working on making some improvements <br /> to give you better user
                experience.</p>
            <p class="fs-6">Please visit us again shortly.</p>
            <a href="{{ route('home') }}" class="btn btn-dark mt-3">Go to Home Page</a>
        </div>
    </div>
</div>
@endsection