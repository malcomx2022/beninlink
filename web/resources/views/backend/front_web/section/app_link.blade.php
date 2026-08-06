<h3 class="mt-3">{{ __('levels.play_store') }}</h3>
<div class="row"> 
    <div class="form-group col-md-4">
        <label for="playstore_icon">{{ __('levels.icon') }}</label>  <span class="text-danger">*</span> <small><a href="https://fontawesome.com/icons" target="_blank" class="text-primary">{{ __('levels.click_here') }}</a></small>
        <input id="playstore_icon" type="text" name="data[playstore_icon]"  placeholder="{{ __('levels.Enter_first_title') }}" autocomplete="off" class="form-control @error('playstore_icon') is-invalid @enderror" value="{{old('playstore_icon',@$section['playstore_icon'])}}" required>
        @error('playstore_icon')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="playstore_link">{{ __('levels.link') }}</label> <span class="text-danger">*</span>
        <input id="playstore_link" type="text" name="data[playstore_link]"  placeholder="{{ __('levels.Enter_middle_title') }}" autocomplete="off" class="form-control @error('playstore_link') is-invalid @enderror" value="{{old('playstore_link',@$section['playstore_link'])}}" required>
        @error('playstore_link')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>  
</div>

<h3 class="mt-3">{{ __('levels.ios_store') }}</h3>
<div class="row"> 
    <div class="form-group col-md-4">
        <label for="ios_icon">{{ __('levels.icon') }} <span class="text-danger">*</span> <small><a href="https://fontawesome.com/icons" target="_blank" class="text-primary">{{ __('levels.click_here') }}</a></small></label>  
        <input id="ios_icon" type="text" name="data[ios_icon]"  placeholder="{{ __('levels.Enter_first_title') }}" autocomplete="off" class="form-control @error('ios_icon') is-invalid @enderror" value="{{old('ios_icon',@$section['ios_icon'])}}" required>
        @error('ios_icon')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="ios_link">{{ __('levels.link') }}</label> <span class="text-danger">*</span>
        <input id="ios_link" type="text" name="data[ios_link]"  placeholder="{{ __('levels.Enter_middle_title') }}" autocomplete="off" class="form-control @error('ios_link') is-invalid @enderror" value="{{old('ios_link',@$section['ios_link'])}}" required>
        @error('ios_link')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>  
</div>