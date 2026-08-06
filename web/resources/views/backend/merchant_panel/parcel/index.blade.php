@extends('backend.partials.master')
@section('title')
    {{ __('parcel.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{ route('merchant-panel.parcel.index') }}" class="breadcrumb-link">{{ __('parcel.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                    <form action="{{route('merchant-panel.parcel.filter')}}"  method="GET">
                     
                        <div class="row">
                            <div class="form-group col-12 col-md-3 col-sm-6  col-lg-3 col-xl-2">
                                <label for="parcel_date">{{ __('parcel.date') }}</label>
                                <input type="text" autocomplete="off" id="date" name="parcel_date" class="form-control date_range_picker" value="{{ old('parcel_date',$request->parcel_date) }}" placeholder="{{ __('merchantPlaceholder.date') }}">
                                @error('parcel_date')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-md-3 col-sm-6  col-lg-3 col-xl-2">
                                <label for="parcelStatus">{{ __('parcel.status') }}</label>
                                <select style="width: 100%" id="parcelStatus"  name="parcel_status" class="form-control @error('parcel_status') is-invalid @enderror" >
                                    <option value="" selected> {{ __('menus.select') }} {{ __('parcel.status') }}</option>
                                    @foreach (trans('merchantParcelStatusFilter') as $key => $status)
                                        <option value="{{ $key}}" {{ (old('parcel_status',$request->parcel_status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                                @error('parcel_status')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-md-3 col-sm-6  col-lg-3 col-xl-2">
                                <label for="parcel_customer">{{ __('parcel.customer_name') }}</label>
                                <input id="parcel_customer" type="text" name="parcel_customer"  placeholder="{{ __('parcel.customer_name') }}" autocomplete="off" class="form-control" value="{{old('parcel_customer',$request->parcel_customer)}}">
                                @error('parcel_customer')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-md-3 col-sm-6  col-lg-3 col-xl-2">
                                <label for="parcel_customer_phone">{{ __('parcel.customer_phone') }}</label>
                                <input id="parcel_customer_phone" type="text" name="parcel_customer_phone"  placeholder="{{ __('parcel.customer_phone') }}" autocomplete="off" class="form-control" value="{{old('parcel_customer_phone',$request->parcel_customer_phone)}}">
                                @error('parcel_customer_phone')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-12 col-md-3 col-sm-6 col-lg-3 col-xl-2">
                                <label for="invoice_id">{{ __('parcel.invoice_id') }}</label>
                                <input id="invoice_id" type="text" name="invoice_id"  placeholder="{{ __('parcel.invoice_id') }}" autocomplete="off" class="form-control" value="{{old('invoice_id',$request->invoice_id)}}">
                                @error('parcel_customer_phone')
                                <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-md-3 col-lg-3 col-xl-2">
                                <div class="pt-4 d-flex margin-top-5px">
                                    <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                    <a href="{{ route('merchant-panel.parcel.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="row pl-4 pr-4 pt-4 merchantParcelPage">
                    <div class="col-8">
                        <div class="d-flex   parcelsearchFlex parcel-import-export-btn">
                            <p class="h3 mr-5">{{ __('parcel.title') }} </p>
                            <div class="d-flex justify-content-start mt-md-0 d-lg-block   ">
                                <a href="{{route('merchant-panel.parcel.file-export',['parcel_date' =>$request->parcel_date, 'parcel_status' => $request->parcel_status,'parcel_customer' => $request->parcel_customer,'parcel_customer_phone' =>$request->parcel_customer_phone])}}" class="btn btn-success btn-sm " data-toggle="tooltip" data-placement="top" title="Add"> <i class="fa fa-download"></i> {{ __('parcel.export_xlsx') }}</a>
                                <a href="{{route('merchant-panel.parcel.file-export',['parcel_date' =>$request->parcel_date, 'parcel_status' => $request->parcel_status,'parcel_customer' => $request->parcel_customer,'parcel_customer_phone' =>$request->parcel_customer_phone,'type'=>'csv'])}}" class="btn btn-success btn-sm " data-toggle="tooltip" data-placement="top" title="Add"> <i class="fa fa-download"></i> {{ __('parcel.export_csv') }}</a>
                       
                            </div>
                        </div>
                    </div>
                    <div class="col-4 ">
                        <div class="d-flex justify-content-end  mt-0 parcel-create-import-btn-start">
                            {{-- multiple parcel label print --}}
                            <form action="{{ route('parcel.multiple.print-label') }}" method="get" target="_blank" id="print_label_form">
                                @csrf
                                <div id="print_label_content"></div>
                                <button type="submit" class="btn btn-sm btn-primary mr-2 multiplelabelprint" data-parcels='' style="display: none">{{ __('levels.print_label') }}</button>
                            </form>
                            {{-- end multiple parcel label print --}}
                        
                            <a href="{{route('merchant-panel.parcel.create')}}" class="btn btn-primary btn-sm mr-2" data-toggle="tooltip" data-placement="top" title="Add"><i class="fa fa-plus"></i> {{ __('levels.add') }}</a>
                            <a href="{{route('merchant-panel.parcel.parcel-import')}}" class="btn btn-success btn-sm " data-toggle="tooltip" data-placement="top" title="Add"><i class="fa fa-plus"></i> {{ __('parcel.import_parcel') }}</a>
                        </div>
                    </div>
                    <div class="col-12 d-lg-none mt-2 ">
                        <div class="d-flex justify-content-end mt-md-0   ">
                            <a href="{{route('merchant-panel.parcel.file-export',['parcel_date' =>$request->parcel_date, 'parcel_status' => $request->parcel_status,'parcel_customer' => $request->parcel_customer,'parcel_customer_phone' =>$request->parcel_customer_phone])}}" class="btn btn-success btn-sm " data-toggle="tooltip" data-placement="top" title="Add"> <i class="fa fa-download"></i> {{ __('parcel.export_xlsx') }}</a>
                            <a href="{{route('merchant-panel.parcel.file-export',['parcel_date' =>$request->parcel_date, 'parcel_status' => $request->parcel_status,'parcel_customer' => $request->parcel_customer,'parcel_customer_phone' =>$request->parcel_customer_phone,'type'=>'csv'])}}" class="btn btn-success btn-sm " data-toggle="tooltip" data-placement="top" title="Add"> <i class="fa fa-download"></i> {{ __('parcel.export_csv') }}</a>
     
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table" class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th class="parcel-index permission-check-box">
                                        <input type="checkbox" id="tick-all" class="form-check-input"/> 
                                    </th>
                                    <th>{{ __('###') }}</th>
                                    <th>{{ __('parcel.tracking_id') }}</th>
                                    <th>{{ __('parcel.recipient_info') }}</th>
                                    <th>{{ __('parcel.amount')}}</th>
                                    <th>{{ __('parcel.status') }}</th>
                                    <th>{{ __('parcel.payment')}}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($parcels as $parcel)
                                <tr>
                                    <td class="parcel-index permission-check-box">
                                        <input type="checkbox" name="parcels[][{{ $parcel->id }}]" value="{{ $parcel->id }}" class="common-key form-check-input" />
                                    </td>
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-sm ml-2 bnone">...</button>
                                            <div class="dropdown-menu">
                                                <a href="{{ route('merchant-panel.parcel.details',$parcel->id) }}" class="dropdown-item"><i class="fa fa-eye" aria-hidden="true"></i> {{__('levels.view')}}</a>
                                                <a href="{{ route('merchant-panel.parcel.logs',$parcel->id) }}" class="dropdown-item"><i class="fas fa-history" aria-hidden="true"></i> {{__('levels.parcel_logs')}}</a>
                                                <a href="{{ route('merchant-parcel.clone',$parcel->id) }}" class="dropdown-item"><i class="fas fa-clone" aria-hidden="true"></i> {{__('levels.clone')}}</a>
                                                @if( \App\Enums\ParcelStatus::DELIVERED !== $parcel->status)
                                                    @if ($parcel->status == App\Enums\ParcelStatus::PENDING)
                                                        <a href="{{route('merchant-panel.parcel.edit',$parcel->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{__('levels.edit')}}</a>
                                                        <form id="delete" value="Test" action="{{route('merchant-panel.parcel.delete',$parcel->id)}}" method="POST" data-title="{{ __('delete.parcel') }}">
                                                            @method('DELETE')
                                                            @csrf
                                                            <input type="hidden" name="" value="Parcel" id="deleteTitle">
                                                            <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $parcel->tracking_id }}</td>
                                    <td class="merchantpayment">
                                        <div class="w150">
                                            <div class="d-flex">
                                                <i class="fa fa-user"></i>&nbsp;<p>{{$parcel->customer_name}}</p>
                                            </div>
                                            <div class="d-flex">
                                                <i class="fas fa-phone"></i>&nbsp;<p>{{$parcel->customer_phone}}</p>
                                            </div>
                                            <div class="d-flex">
                                                <i class="fas fa-map-marker-alt"></i>&nbsp;<p>{{$parcel->customer_address}}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="w250">
                                            {{__('levels.cod')}}: <span class="text-dark">{{settings()->currency}}{{$parcel->cash_collection}}</span>
                                            <br>
                                            {{__('levels.total_delivery_amount')}}: <span class="text-dark">{{settings()->currency}}{{$parcel->total_delivery_amount}}</span>
                                            <br>
                                            {{__('levels.vat_amount')}}: <span class="text-dark">{{settings()->currency}}{{$parcel->vat_amount}}</span>
                                            <br>
                                            {{__('levels.current_payable')}}: <b>{{settings()->currency}}{{$parcel->current_payable}}</b>
                                            <br>
                                        </div>
                                    </td>
                                     <td>
                                        <p>{!! $parcel->parcel_status !!}</p>
                                        <span>{{__('parcel.updated_on')}}: {{\Carbon\Carbon::parse($parcel->updated_at)->format('Y-m-d h:i:s A')}}</span>
                                    </td>
                                    <td> 
                                        @if ($parcel->invoice)     
                                            <p>{{ __('invoice.'.@$parcel->invoice->status) }}</p> 
                                            {{ @$parcel->invoice->invoice_id }}<br/>
                                            @if ($parcel->invoice->status == App\Enums\InvoiceStatus::PAID)
                                                    Paid At: {{ @dateFormat(@$parcel->invoice->updated_at) }}
                                            @endif
                                        @else
                                            N/A 
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $parcels->appends($request->all())->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $parcels->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $parcels->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $parcels->total() }}</span>
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
<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush
<!-- js  -->
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script>
    <script>
        var dateParcel = '{{ $request->parcel_date }}';
    </script>
    <script src="{{ static_asset('backend/js/merchant_panel/parcel/filter.js') }}"></script>
    <script src="{{ static_asset('backend/js/parcel/parcel-search.js') }}"></script>

    <script type="text/javascript">
            $(document).ready(function(){
                
                    //multiple parcel label print 
                    $('#tick-all').on('change', function(){  
                        if(!$(this).is(':checked')){ 
                            $('td').closest('tr').find('.common-key').prop('checked', false)
                        }
                        else{ 
                            if ($(this).is(':checked')) {
                                $('td').closest('tr').find('.common-key').prop('checked', true)
                            } 
                        } 
                        showPrintBtn();
                    }); 

                    $('.common-key').on('click',function(){
                        showPrintBtn();
                    })

                    function showPrintBtn(){
                        if($('.common-key:checked').length > 0){
                            $('.multiplelabelprint').show();
                            var inputs   ='';
                            $('.common-key:checked').each(function(){ 
                                inputs += '<input type="hidden" name="parcels[]" value="'+$(this).val()+'"/>';
                            });
                            $('#print_label_content').html(inputs);
                        }else{
                            $('.multiplelabelprint').hide();
                            $('#tick-all').prop('checked', false);
                            $('#print_label_content').html('');
                        }
                    }

                    //multiple parcel label print
            });
    </script>
@endpush


