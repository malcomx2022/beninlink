@extends('backend.partials.master')
@section('title')
    {{ __('news_offer.title') }}    {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{route('news-offer.index')}}" class="breadcrumb-link">{{ __('news_offer.title') }}</a></li>
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
                    <div class="col-10">
                        <p class="h3">{{ __('news_offer.title') }}</p>
                    </div>
                    <div class="col-2">
                        <a href="{{route('news-offer.create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.title') }}</th>
                                    <th>{{ __('levels.file') }}</th>
                                    <th>{{ __('levels.status') }}</th>
                                    <th>{{ __('levels.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($news_offers as $news_offer)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{ $news_offer->title }}</td>
                                    <td>
                                        <img src="{{ $news_offer->image }}" alt="Image" width="45" height="65">
                                    </td>
                                    <td>{!! $news_offer->my_status !!}</td>
                                    <td>
                                        <div class="row">
                                            <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                            <div class="dropdown-menu">
                                                <a href="{{route('news-offer.edit',$news_offer->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                <form id="delete" value="Test" action="{{route('news-offer.delete',$news_offer->id)}}" method="POST" data-title="{{ __('delete.news_offer') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $news_offers->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $news_offers->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $news_offers->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $news_offers->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection()
