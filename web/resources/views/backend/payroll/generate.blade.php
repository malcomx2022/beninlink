<div class="modal fade" id="autogenerate"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" id="data-modal">
            <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">{{ __('salary.salary_generate') }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            </div>
            <form action="{{ route('salary.auto.generate') }}" method="post">
                @csrf
                <input type="hidden" value="" name="parcel_id" id="modal_parcel_id" class="modal_parcel_id"/>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="date">{{ __('salary.month')}}</label> <span class="text-danger">*</span>
                        <input type="month"   id="month" data-toggle="month" name="month" data-parsley-trigger="change" placeholder="yyyy-mm-dd"  class="form-control" value="{{old('date',date('Y-m'))}}" required>
                        @error('date')
                        <small class="text-danger mt-2">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('menus.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('menus.submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
