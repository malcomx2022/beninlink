<div class="modal fade" id="regular"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content" id="data-modal">

        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">{{ __('pickupRequest.regular') }} {{ __('pickupRequest.pickup_request') }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('merchant.panel.pickup.request.regular.store') }}" method="post">
            @csrf
            <div class="modal-body">
                <div class="form-group  ">
                    <label for="address">{{ __('pickupRequest.pickup_address') }}</label>
                    <div class="form-control-wrap ">
                            {{ @Auth::user()->merchant->address }}
                    </div>
                </div>
                <div class="form-group  ">
                    <label for="parcel">{{ __('levels.estimetad_parcel') }} ({{ __('pickupRequest.optional') }}) </label>
                    <div class="form-control-wrap deliveryman-search">
                       <input class="form-control" type="number" name="parcel_quantity" />
                    </div>
                </div>
                <div class="form-group  ">
                    <label for="note">{{ __('parcel.note')}} ({{ __('pickupRequest.optional') }})</label>
                    <div class="form-control-wrap deliveryman-search">
                       <textarea class="form-control" name="note" rows="5"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('pickupRequest.close') }}</button>
              <button type="submit" class="btn btn-primary">{{ __('pickupRequest.send_request') }}</button>
            </div>
        </form>
    </div>
</div>
</div>
