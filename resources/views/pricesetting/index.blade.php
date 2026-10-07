@extends('layouts.app')

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0" data-anchor="data-anchor">Price Setting :: Price List </h5>
                </div>
                <div class="col-auto ms-auto">
                    <div class="nav nav-pills nav-pills-falcon">
                        @if (Helper::userCan(104, 'can_add'))
                            <a href="{{ route('pricesetting.add') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-plus me-1"></i>
                                Add Price
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body table-padding">
            <div class="table-responsive scrollbar">
                <table class="table custom-table table-striped dt-table-hover fs--1 mb-0 table-datatable"
                    style="width:100%">
                    <thead class="bg-200 text-900">
                        <tr>
                            <th>Seller Name</th>
                             <th>Courier Services/Partners</th>
                            <th>Shipping Charge (%)</th>
                            <th>COD Charge (Rs)</th>
                            <th>COD Charge (%)</th>

                            <th width="100px">Action</th>
                        </tr>
                    </thead>


                    <tbody>
                @foreach($brands as $brand)
                    <tr>
                    <td>{{ $brand->seller->name ?? 'N/A'}}</td>
                    <td>{{ $brand->Provider->name ?? 'N/A'}}</td>
                    <td>{{ $brand->shipping_charge  ?? 'N/A'}}</td>
                    <td>{{ $brand->cod_charge  ?? 'N/A'}}</td>
                    <td>{{ $brand->cod_charge_parsent  ?? 'N/A'}}</td>

                <td>
                <a href="{{ route('pricesetting.edit', $brand->id) }}" class="btn btn-sm btn-warning">
                    <i class="fa fa-edit me-1"></i> Edit
                </a>
            </td>


            
            </td>
        </tr>
    @endforeach
</tbody>

                    {{-- <tbody>




                    </tbody> --}}
                </table>
            </div>
        </div>
    </div>
@endsection
