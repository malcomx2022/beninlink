<div class="modal fade" id="parcelstatus{{ \App\Enums\ParcelStatus::PARTIAL_DELIVERED }}"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" id="data-modal">
            <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::PARTIAL_DELIVERED) }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>
            <form action="{{ route('parcel.partial-delivered',['page'=>$request->page,'filter'=>$request->filter? $request->filter:'']) }}" method="post">
                @csrf
                <input type="hidden" value="" name="parcel_id" id="modal_parcel_id" class="modal_parcel_id"/>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="cash_collection">{{ __('parcel.cash_collection') }} </label> <span class="text-danger">*</span>
                        <div class="form-control-wrap">
                            <input type="text" class="form-control cash-collection" id="cash_collection" value="{{ old('cash_collection') }}" name="cash_collection" placeholder="Cash amount" required="">
                            @error('cash_collection')
                            <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group  ">
                        <label for="note">{{ __('parcel.note')}}</label>
                        <div class="form-control-wrap deliveryman-search">
                        <textarea class="form-control" name="note" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="custom-control custom-checkbox">
                                    <input id="send_sms_customer" name="send_sms_customer" class="custom-control-input" type="checkbox"><span class="custom-control-label">Send SMS for customer</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="custom-control custom-checkbox">
                                    <input id="send_sms_merchant" name="send_sms_merchant" class="custom-control-input" type="checkbox"><span class="custom-control-label">Send SMS for merchant </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('levels.cancel') }} </button>
                    <button type="submit" class="btn btn-primary">{{ __('levels.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
