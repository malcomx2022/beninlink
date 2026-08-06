<div class="modal fade" id="received_by_hub_multiple_parcel"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content" id="data-modal">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">{{ __('parcelStatus.'.\App\Enums\ParcelStatus::RECEIVED_BY_HUB) }}</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('parcel.received-by-mulbiple-hub',['page'=>$request->page]) }}" method="post">
            @csrf
            <input type="hidden" value="" name="parcel_id" id="modal_parcel_id" class="modal_parcel_id"/>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group  ">
                            <label for="received_transfer-hub">{{ __('levels.hub')}}</label> <span class="text-danger">*</span>
                            <div class="form-control-wrap  h">
                                <select id="received_transfer-hub" class="form-control d" name="from_hub_id"  data-url="{{ route('parcel.transferHub') }}" >
                                    <option selected disabled>Select Hub</option>
                                    @foreach (hubs() as $hub)
                                    <option value="{{ $hub->id }}">{{ $hub->name }}</option>
                                    @endforeach
                                </select>
                                @error('hub_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group multiple-recived-by-hub ">
                            <label for="received_track_id">{{ __('levels.track_id')}}</label> <span class="text-danger">*</span>
                            <input id="received_track_id" name="track_id" placeholder="Enter Tracking Id" data-url="{{ route('parcel.received-by-hub-search') }}" class="form-control"/>
                        </div>
                        <div class="form-group ">
                            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
                            <div id="ids"></div>
                            <small id="transfer_to_hub_track_id_not_found2" class="text-danger mt-2" style="display: none">Parcel not found!</small>
                            <small id="transfer_to_hub_track_id_found2" class="text-success mt-2" style="display: none">Parcel added successfully.</small>
                            <small id="transfer_to_hub_track_id_already_added2" class="text-danger mt-2" style="display: none">Already added!</small>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group ">
                            <label for="note">{{ __('levels.note')}}</label>
                            <textarea name="note" id="note" rows="4" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
                <div class="border"></div>
                <div class="row px-3">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>SL No</th>
                                    <th>{{__('merchant.track_id')}}</th>
                                    <th>{{__('merchant.business_name')}}</th>
                                    <th>{{__('levels.mobile')}}</th>
                                    <th>{{__('parcel.delivert_time')}}</th>
                                    <th>{{__('levels.cash_collection')}}</th>
                                    <th>{{ __('levels.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="received_trakings_ids">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('levels.cancel') }}</button>
                <button type="submit" class="btn btn-primary"> {{ __('levels.save') }}</button>
            </div>
        </form>
    </div>
</div>
</div>
