<h3 class="mt-5">{{ __('levels.branches') }}</h3>
<div class="row"> 
    <div class="form-group col-md-4">
        <label for="branch_icon">{{ __('levels.icon') }}<span class="text-danger">*</span> <small><a href="https://fontawesome.com/icons" target="_blank" class="text-primary">{{ __('levels.click_here') }}</a></small></label> 
        <input id="branch_icon" type="text" name="data[branch_icon]"  placeholder="{{ __('levels.Enter_icon') }}" autocomplete="off" class="form-control @error('branch_icon') is-invalid @enderror" value="{{old('branch_icon',@$section['branch_icon'])}}" required>
        @error('branch_icon')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="branch_count">{{ __('levels.count') }}</label> <span class="text-danger">*</span>
        <input id="branch_count" type="text" name="data[branch_count]"  placeholder="{{ __('levels.Enter_count') }}" autocomplete="off" class="form-control @error('branch_count') is-invalid @enderror" value="{{old('branch_count',@$section['branch_count'])}}" required>
        @error('branch_count')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="branch_title">{{ __('levels.title') }}</label> <span class="text-danger">*</span>
        <input id="branch_title" type="text" name="data[branch_title]"   placeholder="{{ __('levels.Enter_title') }}" autocomplete="off" class="form-control @error('branch_title') is-invalid @enderror" value="{{old('branch_title',@$section['branch_title'])}}" required>
        @error('branch_title')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>    
</div>
{{-- parcel delivered --}}
<h3 class="mt-2">{{ __('levels.parcel_delivered') }}</h3>
<div class="row"> 
    <div class="form-group col-md-4">
        <label for="parcel_icon">{{ __('levels.icon') }} <span class="text-danger">*</span> <small><a href="https://fontawesome.com/icons" target="_blank" class="text-primary">{{ __('levels.click_here') }}</a></small></label> 
        <input id="parcel_icon" type="text" name="data[parcel_icon]"  placeholder="{{ __('levels.Enter_icon') }}" autocomplete="off" class="form-control @error('parcel_icon') is-invalid @enderror" value="{{old('parcel_icon',@$section['parcel_icon'])}}" required>
        @error('parcel_icon')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="parcel_count">{{ __('levels.count') }}</label> <span class="text-danger">*</span>
        <input id="parcel_count" type="text" name="data[parcel_count]"  placeholder="{{ __('levels.Enter_count') }}" autocomplete="off" class="form-control @error('parcel_count') is-invalid @enderror" value="{{old('parcel_count',@$section['parcel_count'])}}" required>
        @error('parcel_count')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="parcel_title">{{ __('levels.title') }}</label> <span class="text-danger">*</span>
        <input id="parcel_title" type="text" name="data[parcel_title]"   placeholder="{{ __('levels.Enter_title') }}" autocomplete="off" class="form-control @error('parcel_title') is-invalid @enderror" value="{{old('parcel_title',@$section['parcel_title'])}}" required>
        @error('parcel_title')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>    
</div>
{{-- happy merchant --}}
<h3 class="mt-2">{{ __('levels.happy_merchant') }}</h3>
<div class="row"> 
    <div class="form-group col-md-4">
        <label for="merchant_icon">{{ __('levels.icon') }} <span class="text-danger">*</span> <small><a href="https://fontawesome.com/icons" target="_blank" class="text-primary">{{ __('levels.click_here') }}</a></small></label> 
        <input id="merchant_icon" type="text" name="data[merchant_icon]"  placeholder="{{ __('levels.Enter_icon') }}" autocomplete="off" class="form-control @error('merchant_icon') is-invalid @enderror" value="{{old('merchant_icon',@$section['merchant_icon'])}}" required>
        @error('merchant_icon')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="merchant_count">{{ __('levels.count') }}</label> <span class="text-danger">*</span>
        <input id="merchant_count" type="text" name="data[merchant_count]"  placeholder="{{ __('levels.Enter_count') }}" autocomplete="off" class="form-control @error('merchant_count') is-invalid @enderror" value="{{old('merchant_count',@$section['merchant_count'])}}" required>
        @error('merchant_count')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="merchant_title">{{ __('levels.title') }}</label> <span class="text-danger">*</span>
        <input id="merchant_title" type="text" name="data[merchant_title]"   placeholder="{{ __('levels.Enter_title') }}" autocomplete="off" class="form-control @error('merchant_title') is-invalid @enderror" value="{{old('merchant_title',@$section['merchant_title'])}}" required>
        @error('merchant_title')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>    
</div>

{{-- Positive reviews --}}
<h3 class="mt-2">{{ __('levels.positive_reviews') }}</h3>
<div class="row"> 
    <div class="form-group col-md-4">
        <label for="reviews_icon">{{ __('levels.icon') }} <span class="text-danger">*</span> <small><a href="https://fontawesome.com/icons" target="_blank" class="text-primary">{{ __('levels.click_here') }}</a></small></label> 
        <input id="reviews_icon" type="text" name="data[reviews_icon]"  placeholder="{{ __('levels.Enter_icon') }}" autocomplete="off" class="form-control @error('reviews_icon') is-invalid @enderror" value="{{old('reviews_icon',@$section['reviews_icon'])}}" required>
        @error('reviews_icon')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="reviews_count">{{ __('levels.count') }}</label> <span class="text-danger">*</span>
        <input id="reviews_count" type="text" name="data[reviews_count]"  placeholder="{{ __('levels.Enter_count') }}" autocomplete="off" class="form-control @error('reviews_count') is-invalid @enderror" value="{{old('reviews_count',@$section['reviews_count'])}}" required>
        @error('reviews_count')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="reviews_title">{{ __('levels.title') }}</label> <span class="text-danger">*</span>
        <input id="reviews_title" type="text" name="data[reviews_title]"   placeholder="{{ __('levels.Enter_title') }}" autocomplete="off" class="form-control @error('reviews_title') is-invalid @enderror" value="{{old('reviews_title',@$section['reviews_title'])}}" required>
        @error('reviews_title')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>    
</div>