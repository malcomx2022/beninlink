<div class="row"> 
    <div class="form-group col-md-4">
        <label for="subscribe_title">{{ __('levels.title') }}</label> <span class="text-danger">*</span>
        <input id="subscribe_title" type="text" name="data[subscribe_title]"  placeholder="{{ __('levels.Enter_first_title') }}" autocomplete="off" class="form-control @error('subscribe_title') is-invalid @enderror" value="{{old('subscribe_title',@$section['subscribe_title'])}}" required>
        @error('subscribe_title')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="subscribe_description">{{ __('levels.description') }}</label> <span class="text-danger">*</span>
        <textarea id="subscribe_description" type="text" name="data[subscribe_description]"  placeholder="{{ __('levels.Enter_description') }}" autocomplete="off" class="form-control @error('subscribe_description') is-invalid @enderror"  required>{{old('subscribe_description',@$section['subscribe_description'])}}</textarea>
        @error('subscribe_description')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>  
</div>