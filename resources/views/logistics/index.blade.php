@extends('layouts.app')

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0" data-anchor="data-anchor">Logistic :: Logistic List </h5>
                </div>
                <div class="col-auto ms-auto">
                    <div class="nav nav-pills nav-pills-falcon">
                        @if (Helper::userCan(104, 'can_add'))
                            <a href="{{ route('logistics.add') }}" class="btn btn-outline-secondary">
                                <i class="fa fa-plus me-1"></i>
                                Add Logistic
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
                            <th>Name</th>
                            {{-- <th>Image</th> --}}
                            <th>Email </th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th width="100px">Action</th>
                        </tr>
                    </thead>


                    <tbody>
    @foreach($brands as $brand)
        <tr>
         <td>{{ $brand->name}}</td>
            {{-- <td>
                <img src="{{ asset('uploads/brands/' . $brand->image) }}" alt="Brand Image" width="50">
            </td> --}}

           <td>{{ $brand->email}}</td>

            <td>
                @if($brand->status == '1')
                    <a class="btn btn-success btn-round btn-xs"
                    href="{{ route('logistics.status', [$brand->id, '0']) }}">
                        Active
                    </a>
                    <span>&nbsp;&nbsp;&nbsp;&nbsp;</span>
                @else
                    <a class="btn btn-dark btn-round btn-xs"
                    href="{{ route('logistics.status', [$brand->id, '1']) }}">
                        Inactive
                    </a>
                    <span>&nbsp;</span>
                @endif
            </td>

            <td>{{ $brand->created_at->format('d-m-Y') }}</td>
            <td>
                {{-- <a href="{{ route('logistics.edit', $brand->id) }}" class="btn btn-sm btn-info">Edit</a> --}}
                <a href="{{ route('logistics.delete', $brand->id) }}"
            onclick="return confirm('Are you sure you want to delete this item?')"
            class="btn btn-sm btn-danger">
                Delete
            </a>

                {{-- <button type="button" class="btn btn-sm btn-danger delete" data-id="{{ $brand->id }}">Delete</button> --}}
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
