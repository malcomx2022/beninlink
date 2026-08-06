@if(!blank($deliveryCharge))
    <div class="row">
        <input type="hidden" class="form-control" name="weight" id="weight"  value="{{old('weight',$deliveryCharge->weight)}}">
        <div class="form-group col-6">
            <label for="same_day">{{ __('levels.same_day') }}</label> <span class="text-danger">*</span>
            <input id="same_day" type="number" name="same_day" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_same_day') }}" autocomplete="off" class="form-control" value="{{old('same_day',$deliveryCharge->same_day)}}" require>
            @error('same_day')
            <small class="text-danger mt-2">{{ $message }}</small>
            @enderror
        </div>
        <div class="form-group col-6">
            <label for="next_day">{{ __('levels.next_day') }}</label> <span class="text-danger">*</span>
            <input id="next_day" type="number" name="next_day" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_next_day') }}" autocomplete="off" class="form-control" value="{{old('next_day',$deliveryCharge->next_day)}}" require>
            @error('next_day')
            <small class="text-danger mt-2">{{ $message }}</small>
            @enderror
        </div>
        <div class="form-group col-6">
            <label for="sub_city">{{ __('levels.sub_city') }}</label> <span class="text-danger">*</span>
            <input id="sub_city" type="number" name="sub_city" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_sub_city') }}" autocomplete="off" class="form-control" value="{{old('sub_city',$deliveryCharge->sub_city)}}" require>
            @error('sub_city')
            <small class="text-danger mt-2">{{ $message }}</small>
            @enderror
        </div>
        <div class="form-group col-6">
            <label for="outside_city">{{ __('levels.outside_city') }}</label> <span class="text-danger">*</span>
            <input id="outside_city" type="number" name="outside_city" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_outside_city') }}" autocomplete="off" class="form-control" value="{{old('outside_city',$deliveryCharge->outside_city)}}" require>
            @error('outside_city')
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
