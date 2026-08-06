@extends('backend.partials.master')
@section('title')
    {{ __('delivery_charge.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
<!-- wrapper  -->
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.settings')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('delivery_charge.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('delivery-charge.filter')}}"  method="GET">
                        @csrf
                        <div class="row">
                            <div class="form-group col-12 col-md-4" >
                                <label for="category">{{ __('levels.category') }}</label>
                                <select id="category" name="category" class="form-control @error('category') is-invalid @enderror">
                                    <option selected disabled>{{ __('menus.select') }} </option>
                                    @foreach($categories as $category)
                                        <option {{ (old('category',$request->category) == $category->id) ? 'selected' : '' }} value="{{ $category->id }}">{{ $category->title }}</option>
                                    @endforeach
                                </select>
                                @error('category')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-md-4">
                                <label for="date">{{ __('levels.weight')}}</label> <span class="text-danger"></span>
                                <input type="text" id="weight" name="weight" placeholder="{{ __('parcel.weight') }}"  class="form-control" value="{{old('weight', $request->weight)}}">
                                @error('weight')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>


                            <div class="form-group col-12 col-md-4 pt-1">
                                <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 pt-4 d-flex justify-content pl-0">
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('delivery-charge.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>


                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-9">
                        <p class="h3">{{ __('delivery_charge.title') }}</p>
                    </div>
                    @if(hasPermission('delivery_charge_create') == true)
                    <div class="col-3">
                        <a href="{{route('delivery-charge.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                    @endif
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.category') }}</th>
                                    <th>{{ __('levels.weight') }}</th>
                                    <th>{{ __('levels.position') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('levels.same_day') }}</th>
                                    <th>{{ __('levels.next_day') }}</th>
                                    <th>{{ __('levels.sub_city') }}</th>
                                    <th>{{ __('levels.outside_city') }}</th>
                                    @if(hasPermission('delivery_charge_update') == true || hasPermission('delivery_charge_delete') == true)
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($delivery_charges as $delivery_charge)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$delivery_charge->category->title}}</td>
                                    <td>{{$delivery_charge->weight ?? 0}}</td>
                                    <td>{{$delivery_charge->position}}</td>
                                    <td>{!! $delivery_charge->my_status !!}</td>
                                    <td>{{settings()->currency}}{{$delivery_charge->next_day}}</td>
                                    <td>{{settings()->currency}}{{$delivery_charge->same_day}}</td>
                                    <td>{{settings()->currency}}{{$delivery_charge->sub_city}}</td>
                                    <td>{{settings()->currency}}{{$delivery_charge->outside_city}}</td>
                                    @if(hasPermission('delivery_charge_update') == true || hasPermission('delivery_charge_delete') == true)
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                @if(hasPermission('delivery_charge_update') == true )
                                                    <a href="{{route('delivery-charge.edit',$delivery_charge->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                @endif
                                                @if(hasPermission('delivery_charge_delete') == true)
                                                    <form id="delete" value="Test" action="{{route('delivery-charge.delete',$delivery_charge->id)}}" method="POST" data-title="{{ __('delete.delivery_charge') }}">
                                                        @method('DELETE')
                                                        @csrf
                                                        <input type="hidden" name="" value="Delivery Charge" id="deleteTitle">
                                                        <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $delivery_charges->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $delivery_charges->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $delivery_charges->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $delivery_charges->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>

            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()

