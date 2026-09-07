@if(!blank($deliveryCharge))
    <div class="row">
        <input type="hidden" class="form-control" name="weight" id="weight"  value="{{old('weight',$deliveryCharge->weight)}}">
        <div class="form-group col-6">
            <label for="amount">{{ __('delivery_zone.amount') }}</label> <span class="text-danger">*</span>
            <input id="amount" type="number" name="amount" data-parsley-trigger="change" placeholder="{{ __('delivery_zone.amount') }}" autocomplete="off" class="form-control" value="{{ old('amount', $deliveryCharge->amount) }}" require>
            @error('amount')
            <small class="text-danger mt-2">{{ $message }}</small>
            @enderror
        </div>
        <div class="form-group col-6">
            <label for="status">{{__('levels.status')}}</label> <span class="text-danger">*</span>
            <select name="status" class="form-control @error('status') is-invalid @enderror">
                @foreach(trans('status') as $key => $status)
                    <option value="{{ $key }}" {{ (old('status',\App\Enums\Status::ACTIVE) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
            @error('status')
            <small class="text-danger mt-2">{{ $message }}</small>
            @enderror
        </div>
    </div>
@endif
