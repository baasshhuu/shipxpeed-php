@extends('layouts.app')

@section('css')
    <style>
        .progress {
            height: 12px;
            -webkit-border-radius: 0;
            -moz-border-radius: 0;
            border-radius: 30px;
            background-color: #ebedf2;
            display: flex;
            margin-bottom: 0;
        }

        .progress-data .progress .progress-bar {
            margin: 3px;
            background-color: #805dca;
            background-image: linear-gradient(315deg, #805dca 0%, #e7515a 74%);
        }
    </style>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-header">
                    <div class="row flex-between-end">
                        <div class="col-auto align-self-center">
                            <h5 class="mb-0" data-anchor="data-anchor" id="table-example">Server Control Panel </h5>
                        </div>
                        <div class="col-auto ms-auto">
                            <div class="nav nav-pills nav-pills-falcon">
                                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-arrow-left me-1"></i> Go Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-body p-3">
                                            <h6 class="text-secondary fw-bold">Storage</h6>
                                            <div class="progress-data">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small>Total Space</small>
                                                    <small class="fw-bold text-dark">{{ $diskTotal }} GB</small>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <small>Used Space</small>
                                                    <small class="fw-bold text-dark">{{ $diskUsed }} GB</small>
                                                </div>

                                                <div class="d-flex justify-content-end">
                                                    <small class="text-danger fw-bold mb-1">Avilable Space :
                                                        {{ round(($diskFree * 100) / $diskTotal, 2) }}%</small>
                                                </div>

                                                <div class="progress">
                                                    <div class="progress-bar bg-primary" role="progressbar"
                                                        style="width: {{ round(($diskUsed / $diskTotal) * 100) }}%"
                                                        aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <ul class="list-group">
                                <li class="list-group-item bg-secondary">
                                    <strong>Environment Details</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Application Name</strong>
                                    <span class="">
                                        {{ data_get($appData, 'environment.application_name', 'N/A') }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Server IP Address</strong>
                                    <span class="">
                                        {{ request()->server('SERVER_ADDR', 'N/A') }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Laravel Version</strong>
                                    <span class="">
                                        {{ data_get($appData, 'environment.laravel_version', 'N/A') }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Php Version</strong>
                                    <span class="">
                                        {{ data_get($appData, 'environment.php_version', 'N/A') }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Composer Version</strong>
                                    <span class="">
                                        {{ data_get($appData, 'environment.composer_version', 'N/A') }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Environment</strong>
                                    <span class="">
                                        {{ data_get($appData, 'environment.environment', 'N/A') }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Debug Mode</strong>
                                    <span class="">
                                        {{ data_get($appData, 'environment.debug_mode', 'N/A') }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Maintenance Mode</strong>
                                    <span class="">
                                        {{ data_get($appData, 'environment.maintenance_mode', 'N/A') }}
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <div class="col-md-6 mb-3">
                            <ul class="list-group">
                                <li class="list-group-item bg-secondary">
                                    <strong>Application Cache Details</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Configuration</strong>
                                    <span class="">
                                        {{ data_get($appData, 'cache.config', 'N/A') }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Views</strong>
                                    <span class="">
                                        {{ data_get($appData, 'cache.views', 'N/A') }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Routes</strong>
                                    <span class="">
                                        {{ data_get($appData, 'cache.routes', 'N/A') }}
                                    </span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong>Events</strong>
                                    <span class="">
                                        {{ data_get($appData, 'cache.events', 'N/A') }}
                                    </span>
                                </li>
                            </ul>

                            <div class="py-3 d-flex justify-content-between gap-2 flex-wrap mb-2">
                                <button data-anchor-id="1" class="btn btn-light-success flex-fill">Config Cache</button>
                                <button data-anchor-id="2" class="btn btn-light-danger flex-fill">Route Cache</button>
                                <button data-anchor-id="3" class="btn btn-light-secondary flex-fill">View Cache</button>
                                <button data-anchor-id="7" class="btn btn-light-primary flex-fill">Events Cache</button>
                                <button data-anchor-id="4" class="btn btn-light-success flex-fill">Config Clear</button>
                                <button data-anchor-id="5" class="btn btn-light-danger flex-fill">Route Clear</button>
                                <button data-anchor-id="6" class="btn btn-light-secondary flex-fill">View Clear</button>
                                <button data-anchor-id="8" class="btn btn-light-primary flex-fill">Events Clear</button>
                                <button data-anchor-id="9" class="btn btn-light-success flex-fill">
                                    Clear Application Cache
                                </button>
                                <button data-anchor-id="10" class="btn btn-light-danger flex-fill">Optimize</button>
                                <button data-anchor-id="11" class="btn btn-light-secondary flex-fill">
                                    Optimize Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <form action="{{ request()->url() }}" method="post" id="update">
        @csrf
        <input type="hidden" name="type" id="type" value="">
    </form>
@endsection

@section('js')
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('[data-anchor-id]').on('click', function() {
                let type = $(this).data('anchor-id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to do this..!!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Do It.',
                    confirmButtonColor: '#e7515a',
                    cancelButtonColor: '#3b3f5',
                    customClass: {
                        popup: 'pt-1',
                        title: 'fs-4 pt-2',
                        icon: 'mt-3',
                        htmlContainer: 'mt-2',
                        actions: 'mt-2',
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-dark',
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $("#overlay").show();
                        $('#type').val(type);
                        $('#update')[0].submit();
                    }
                })
            })
        });
    </script>
@endsection
