@php
$name = request()->route()->getName();
$parts = array_filter(explode('.', $name));

@endphp
<div class="breadcrumbs-container" data-page-heading="Analytics">
    <header class="header navbar navbar-expand-sm">
        <a href="javascript:void(0);" class="btn-toggle sidebarCollapse" data-placement="bottom">
            <i class="fa-duotone fa-bars fs-5"></i>
        </a>
        <div class="d-flex breadcrumb-content">
            <div class="page-header">
                <div class="page-title"></div>
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        @foreach($parts as $part)
                        <li class="breadcrumb-item @if(false) active @endif" aria-current="page">
                            {{ ucwords(str_ireplace(['-', '_'], ' ', $part)) }}
                        </li>
                        @endforeach
                    </ol>
                </nav>
            </div>
        </div>
    </header>
</div>