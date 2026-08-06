<div class="modal fade " id="assign_return_merchant"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" >
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content" id="data-modal">

        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::RETURN_ASSIGN_TO_MERCHANT) }}</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('parcel.assign-return-to-merchant-bulk',['page'=>$request->page]) }}" method="post">
            @csrf
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group merchant_search">
                            <label for="return_merchant_bulk_merchant">{{ __('parcel.merchant') }}</label> <span class="text-danger">*</span>
                            <select style="width: 100%" id="return_merchant_bulk_merchant"  name="merchant_id" class="form-control @error('merchant_id') is-invalid @enderror" data-url="{{ route('parcel.merchant.get') }}">
                                <option value=""> {{ __('Select Merchant') }}</option>
                            </select>
                            @error('merchant_id')
                            <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group  ">
                            <label for="return_todeliveryman_bulk">{{ __('deliveryman.title')}}<span class="text-danger">*</span></label>
                            <div class="form-control-wrap deliveryman-search">
                                <select id="return_todeliveryman_bulk" class="form-control " name="delivery_man_id" data-url="{{ route('parcel.deliveryman.search') }}" >
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
                            <label for="return_parcel_tracking_id">{{ __('levels.track_id')}}</label> <span class="text-danger">*</span>
                            <input id="return_parcel_tracking_id" type="text" name="track_id"  data-url="{{ route('assign-return-to-merchant.parcel.search') }}" placeholder="Enter Tracking Id" class="form-control">
                            <div class="search_message"></div>
                        </div>
                        <div class="form-group ">
                            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                            <div id="ids"></div>
                            <small id="transfer_to_hub_track_id_not_found4" class="text-danger mt-2" style="display: none">Parcel not found!</small>
                            <small id="transfer_to_hub_track_id_found4" class="text-success mt-2" style="display: none">Parcel added successfully.</small>
                            <small id="transfer_to_hub_track_id_already_added4" class="text-danger mt-2" style="display: none">Already added!</small>
                        </div>
                        <div class="form-group">
                            <label class="custom-control custom-checkbox">
                                <input id="send_sms" name="send_sms" class="custom-control-input" type="checkbox"><span class="custom-control-label">Send SMS</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group ">
                            <label for="return_date">{{ __('levels.date')}}</label> <span class="text-danger">*</span>
                            <input id="return_date" type="date" name="date" data-toggle="datepicker"  data-parsley-trigger="change" placeholder="yyyy-mm-dd"  class="form-control">
                        </div>
                        <div class="form-group  ">
                            <label for="note">{{ __('parcel.note')}}</label>
                            <div class="form-control-wrap deliveryman-search">
                                <textarea class="form-control" name="note" rows="5"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="border"></div>
                <div class="row px-3">
                    <div class="table-responsive">
                        <table class="table table-striped" style="width:100%">
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
                            <tbody id="return_parcel_added">
                            </tbody>
                        </table>
                    <div class="table-responsive">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('levels.cancel') }}</button>
                <button type="submit" id="assign_return_merchant_btn" class="btn btn-primary">{{ __('levels.save') }}</button>
            </div>
        </form>
    </div>
</div>
</div>
