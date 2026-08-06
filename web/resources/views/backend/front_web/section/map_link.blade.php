<h3 class="mt-3">{{ __('levels.map_link') }}</h3>
<div class="row">  
    <div class="form-group col-md-8">
        <label for="map_link">{{ __('levels.map_link') }}</label> <span class="text-danger">*</span>
        <input id="map_link" type="text" name="data[map_link]"  placeholder="{{ __('levels.enter_map_link') }}" autocomplete="off" class="form-control @error('map_link') is-invalid @enderror" value="{{old('map_link',@$section['map_link'])}}" required>
        @error('map_link')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>  
</div> 