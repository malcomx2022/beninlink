<div class="modal fade" id="pickup-request"  role="dialog" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content" id="data-modal">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">{{ __('menus.select') }} {{ __('pickupRequest.pickup_request') }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="row pickup-request" style="margin:auto">
            <div class="col-6">
                <div class="btn btn-primary regular-btn font-bold" data-toggle="modal" data-target="#regular">
                    <h2 class="text-white mb-2"  ><b>{{ __('pickupRequest.regular') }}</b></h2>
                    <p>{{ __('pickupRequest.delivery') }}</p>
                </div>
            </div>
            <div class="col-6">
                <div class="btn btn-primary regular-btn font-bold" data-toggle="modal" data-target="#express">
                    <h2 class="text-white  mb-2"  ><b>{{ __('pickupRequest.express') }}</b></h2>
                    <p>{{ __('pickupRequest.delivery') }}</p>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

