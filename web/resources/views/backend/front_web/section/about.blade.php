<div class="row"> 
    <div class="form-group col-md-4">
        <label for="about_us">{{ __('levels.about_us') }}</label> <span class="text-danger">*</span>
        <textarea id="about_us" type="text" name="data[about_us]"  placeholder="{{ __('levels.Enter_about_us') }}" autocomplete="off" class="form-control @error('about_us') is-invalid @enderror"  required>{{old('about_us',@$section['about_us'])}}</textarea>
        @error('about_us')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
</div>