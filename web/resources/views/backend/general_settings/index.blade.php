@extends('backend.partials.master')
@section('title')
    {{ __('menus.general_settings') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('dashboard.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('menus.settings') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('general-settings.index')}}" class="breadcrumb-link active">{{__('menus.general_settings')}}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- basic form -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p class="h3">{{__('menus.general_settings')}}</h2>
                    <form action="{{route('general-settings.update')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="name">{{ __('levels.application_name') }}</label>
                                    <input id="name" type="text" name="name" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_name') }}" autocomplete="off" class="form-control @error('name') is-invalid @enderror" value="{{ $settings->name }}" require>
                                    @error('name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="phone">{{ __('levels.phone') }}</label>
                                    <input id="phone" type="text" name="phone" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_phone') }}" autocomplete="off" class="form-control @error('phone') is-invalid @enderror" value="{{ $settings->phone }}" require>
                                    @error('phone')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group pt-3">
                                    <label for="email">{{ __('levels.email') }}</label>
                                    <input id="email" type="text" name="email" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_email') }}" autocomplete="off" class="form-control @error('email') is-invalid @enderror" value="{{ $settings->email }}" require>
                                    @error('email')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group pt-3">
                                    <label for="address">{{ __('levels.address') }}</label>
                                    <textarea id="address" type="text" name="address" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_address') }}" autocomplete="off" class="form-control @error('address') is-invalid @enderror" require>{{ $settings->address }}</textarea>
                                    @error('address')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="currency">{{ __('levels.currency') }}</label>
                                    <select class="form-control @error('currency') is-invalid @enderror" id="currency" name="currency"  required>
                                        <option value="" selected disabled>Select Currency</option>
                                        @forelse ($currencies as $currency)
                                            <option value="{{ $currency->symbol }}" {{$settings->currency == $currency->symbol ? 'selected' : ''}}>{{ @$currency->name }} {{ @$currency->symbol }}</option>
                                        @empty
                                            <option value="&#36;" {{$settings->currency == '$' ? 'selected' : ''}}>Dollar &#36;</option>
                                        @endforelse
                                    </select>
                                    @error('currency')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency">{{ __('settings.parcel_tracking') }} {{ __('levels.prefix') }}</label>
                                            <input type="text" name="par_track_prefix" class="form-control" placeholder="Enter Parcel Tracking Prefix" value="{{  @\Illuminate\Support\Str::upper($settings->par_track_prefix) }}"/>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="currency">{{ __('invoice.invoice') }} {{ __('levels.prefix') }}</label>
                                            <input type="text" name="invoice_prefix" class="form-control" placeholder="Enter Invoice Prefix" value="{{  @\Illuminate\Support\Str::upper($settings->invoice_prefix) }}"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label for="copyright">{{ __('levels.copyright') }}</label>
                                    <input id="copyright" type="text" name="copyright" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_copyright') }}" autocomplete="off" class="form-control @error('copyright') is-invalid @enderror" value="{{ $settings->copyright }}" require>
                                    @error('copyright')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-12">
                                            <label for="logo">{{ __('levels.logo') }}</label>
                                            <input id="logo" type="file" name="logo" data-parsley-trigger="change" placeholder="Enter logo" autocomplete="off" class="form-control @error('logo') is-invalid @enderror" value="{{ old('logo') }}" require>
                                            @error('logo')
                                                <small class="text-danger mt-2">{{ $message }}</small>
                                            @enderror
                                        </div>
                                        <div class="col-6 text-right">
                                            <img src="{{$settings->logo_image}}" alt="user" class="rounded mt-3" width="100%"  style="object-fit: contain">
                                        </div>
                                    </div>
                                </div>
                              
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="light_logo">{{ __('levels.light_logo') }}</label>
                                                <input id="light_logo" type="file" name="light_logo" data-parsley-trigger="change" placeholder="Enter light_logo" autocomplete="off" class="form-control @error('light_logo') is-invalid @enderror" value="{{ old('light_logo') }}" require>
                                                @error('light_logo')
                                                    <small class="text-danger mt-2">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="col-6 text-right  pt-3">
                                                <div class="bg-primary p-3"> 
                                                    <img src="{{$settings->light_logo_image}}" alt="user" class="rounded mt-3" width="100%"  style="object-fit: contain">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                
                                    <div class="form-group pt-3">
                                        <div class="row">
                                            <div class="col-9">
                                                <label for="favicon">{{ __('levels.favicon') }}</label>
                                                <input id="favicon" type="file" name="favicon" data-parsley-trigger="change" placeholder="Enter favicon" autocomplete="off" class="form-control @error('favicon') is-invalid @enderror" value="{{ old('favicon') }}" require>
                                                @error('favicon')
                                                    <small class="text-danger mt-2">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div class="col-3  ">
                                                <img src="{{$settings->favicon_image}}" alt="user" class="rounded mt-3" width="60"  style="object-fit: contain">
                                            </div>
                                        </div>
                                    </div>
                               
                               
                          
                            </div>
                                                     
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="primary_color">{{ __('levels.primary_color') }}</label>
                                                <input id="primary_color" type="color" name="primary_color"  placeholder="Enter favicon" autocomplete="off" class="form-control @error('primary_color') is-invalid @enderror" value="{{ old('primary_color',settings()->primary_color) }}" require>
                                                @error('favicon')
                                                    <small class="text-danger mt-2">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div> 
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="text_color">{{ __('levels.text_color') }}</label>
                                                <input id="text_color" type="color" name="text_color"  placeholder="Enter favicon" autocomplete="off" class="form-control @error('text_color') is-invalid @enderror" value="{{ old('text_color',settings()->text_color) }}" require>
                                                @error('text_color')
                                                    <small class="text-danger mt-2">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div> 
                                    </div>
                                </div>
                            

                            @if(hasPermission('general_settings_update'))
                            <div class="row pt-4">
                                <div class="col-12 text-right">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                            @endif
                        </div>

                    </form>
                </div>
            </div>
        </div>
        <!-- end basic form -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()
