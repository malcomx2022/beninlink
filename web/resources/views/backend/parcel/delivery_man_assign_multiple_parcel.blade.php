<div class="modal fade" id="delivery_man_assign_multiple_parcel"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content" id="data-modal">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::DELIVERY_MAN_ASSIGN) }}</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('parcel.delivery-man-assign-multiple-parcel',['page'=>$request->page]) }}" method="post">
            @csrf
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group  ">
                            <label for="delivery_man_search">{{ __('deliveryman.title')}}</label>
                            <div class="form-control-wrap deliveryman-search">
                                <select id="delivery_man_assign_search_multiple_parcel" class="form-control delivery_man_assign_search_multiple_parcel" name="delivery_man_id" data-url="{{ route('parcel.deliveryman.search') }}" >
                                    <option selected disabled>Select delivery man</option>
                                    @foreach ($deliverymans as $deliveryman)
                                        <option value="{{ $deliveryman->id }}">{{ $deliveryman->user->name }}</option>
                                    @endforeach
                                </select>
                                @error('delivery_man_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group ">
                            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                            <label for="track_id">{{ __('levels.track_id')}}</label> <span class="text-danger">*</span>
                            <input id="delivery_man_assign_track_id" type="text" name="track_id" placeholder="Enter Tracking Id" class="form-control">
                            <div class="search_message_"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group  ">
                            <label for="note">{{ __('parcel.note')}}</label>
                            <div class="form-control-wrap deliveryman-search">
                                <textarea class="form-control" name="note" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="custom-control custom-checkbox">
                                <input id="send_sms" name="send_sms" class="custom-control-input" type="checkbox"><span class="custom-control-label">Send SMS</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="border"></div>
                <div class="row px-3">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{__('###') }}</th>
                                    <th>{{__('merchant.track_id')}}</th>
                                    <th>{{__('merchant.business_name')}}</th>
                                    <th>{{__('levels.mobile')}}</th>
                                    <th>{{__('parcel.delivert_time')}}</th>
                                    <th>{{__('levels.cash_collection')}}</th>
                                    <th>{{ __('levels.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="_t_body">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"> {{ __('levels.cancel') }}</button>
                <button type="submit" id="delivery_man_assign_multiple_parcel_button" class="btn btn-primary">{{ __('levels.save') }}</button>
            </div>
        </form>
    </div>
</div>
</div>
