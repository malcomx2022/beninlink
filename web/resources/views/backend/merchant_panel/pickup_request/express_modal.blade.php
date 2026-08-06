<div class="modal fade" id="express"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content" id="data-modal">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">{{ __('menus.express') }} {{ __('pickupRequest.pickup_request') }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{ route('merchant.panel.pickup.request.express.store') }}" method="post">
            @csrf
            <div class="modal-body">
                <div class="form-group  ">
                    <label for="parcel">{{ __('levels.name') }}</label>
                    <div class="form-control-wrap  ">
                       <input class="form-control" type="text" name="name" />
                    </div>
                </div>
                <div class="form-group  ">
                    <label for="parcel">{{ __('levels.address') }}</label>
                    <div class="form-control-wrap  ">
                       <input class="form-control" type="text" name="address" />
                    </div>
                </div>
                <div class="form-group  ">
                    <label for="parcel">{{ __('levels.phone') }}</label>
                    <div class="form-control-wrap  ">
                       <input class="form-control" type="text" name="phone" />
                    </div>
                </div>
                <div class="form-group  ">
                    <label for="parcel">{{ __('pickupRequest.cod_amount') }}</label>
                    <div class="form-control-wrap  ">
                       <input class="form-control" type="text" name="cod_amount" />
                    </div>
                </div>
                <div class="form-group  ">
                    <label for="parcel">{{ __('pickupRequest.invoice') }}</label>
                    <div class="form-control-wrap  ">
                       <input class="form-control" type="text" name="invoice" />
                    </div>
                </div>
                <div class="form-group  ">
                    <label for="parcel">{{ __('pickupRequest.weight') }}(KG)</label>
                    <div class="form-control-wrap  ">
                       <input class="form-control" type="number" name="weight" />
                    </div>
                </div>
                <div class="form-group  ">
                    <label for="note">{{ __('parcel.note')}}</label>
                    <div class="form-control-wrap deliveryman-search">
                       <textarea class="form-control" name="note" rows="5"></textarea>
                    </div>
                </div>
                <div class="form-group  ">
                    <div>
                        <div class="form-check" style="padding-left: 0px">
                        <label class="form-check-label" for="flexCheckDefault">
                              {{ __('pickupRequest.exchange_parcel') }}
                        </label>
                         <input class="form-check-input" type="checkbox" value="1" id="flexCheckDefault" name="exchange" style="margin-left:10px ">
                        </div>
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
