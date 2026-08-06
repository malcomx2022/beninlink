<div class="modal fade" id="parcelstatus{{ \App\Enums\ParcelStatus::PICKUP_RE_SCHEDULE }}"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" id="data-modal">
            <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">{{ __('parcel.pickup_re_schedule') }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>
            <form action="{{ route('parcel.pickup.re.schedule',['page'=>$request->page,'filter'=>$request->filter? $request->filter:'']) }}" method="post">
                @csrf
                <input type="hidden" value="" name="parcel_id" id="modal_parcel_id" class="modal_parcel_id"/>
                <div class="modal-body">
                    <div class="form-group  ">
                        <label for="delivery_man_search_reschedule">{{ __('parcel.pickup_man')}}</label> <span class="text-danger">*</span>
                        <div class="form-control-wrap deliveryman-search">
                            <select id="delivery_man_search_reschedule" class="form-control delivery_man_search " name="delivery_man_id" data-url="{{ route('parcel.deliveryman.search') }}" >
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

                    <div class="form-group pt-1">
                        <label for="date">{{ __('levels.date') }}</label> <span class="text-danger">*</span>
                        <input type="date" id="date"  data-toggle="datepicker" name="date" data-parsley-trigger="change" placeholder="yyyy-mm-dd"  class="form-control" value="{{old('date')}}" require>
                        @error('date')
                            <small class="text-danger mt-2">{{ $message }}</small>
                        @enderror
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
                                    <input id="send_sms_pickuman" name="send_sms_pickuman" class="custom-control-input" type="checkbox"><span class="custom-control-label">Send SMS for pickup man</span>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('levels.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"> {{ __('levels.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
