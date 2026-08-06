@extends('backend.partials.master')
@section('title')
   {{ __('smsSettings.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid  dashboard-content">
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{route('sms-send-settings.index')}}" class="breadcrumb-link">{{ __('smsSendSettings.title') }}</a></li>
                                <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="card">
                    <div class="row pl-4 pr-4 pt-4">
                        <div class="col-12">
                            <p class="h3">{{ __('smsSendSettings.title') }}</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table   " style="width:100%">
                                <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.name')}}</th>
                                    @if(hasPermission('sms_send_settings_status_change') == true)
                                    <th>{{ __('levels.status')}}</th>
                                    @endif
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($smsSendSettings as $smsSendSetting)
                                    <tr>
                                        <td>{{ $smsSendSetting->id }}</td>
                                        <td>{{ trans("SmsSendStatus.".$smsSendSetting->sms_send_status)}}</td>
                                        @if(hasPermission('sms_send_settings_status_change') == true)
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input switch-id" type="checkbox" data-url="{{ route('sms-send-settings.status') }}" data-id="{{ $smsSendSetting->id }}"  role="switch" value="{{$smsSendSetting->status}}"   @if($smsSendSetting->status== \App\Enums\Status::ACTIVE) checked @else @endif>
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
                        <span>{{ $smsSendSettings->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $smsSendSettings->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $smsSendSettings->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $smsSendSettings->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection()
@push('styles')
    <link rel="stylesheet" href="{{static_asset('backend')}}/css/switch.css">
@endpush
@push('scripts')
    <script src="{{ static_asset('backend/js/smsSendSetting/smsSetting.js') }}"></script>
@endpush

