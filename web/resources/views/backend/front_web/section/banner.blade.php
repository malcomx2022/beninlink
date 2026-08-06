<div class="row"> 
    <div class="form-group col-md-4">
        <label for="title_1">{{ __('levels.first_title') }}</label> <span class="text-danger">*</span>
        <input id="title_1" type="text" name="data[title_1]"  placeholder="{{ __('levels.Enter_first_title') }}" autocomplete="off" class="form-control @error('title_1') is-invalid @enderror" value="{{old('title_1',@$section['title_1'])}}" required>
        @error('title_1')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="title_2">{{ __('levels.middle_title') }}</label> <span class="text-danger">*</span>
        <input id="title_2" type="text" name="data[title_2]"  placeholder="{{ __('levels.Enter_middle_title') }}" autocomplete="off" class="form-control @error('title_2') is-invalid @enderror" value="{{old('title_2',@$section['title_2'])}}" required>
        @error('title_2')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div> 
    <div class="form-group col-md-4">
        <label for="title_3">{{ __('levels.last_title') }}</label> <span class="text-danger">*</span>
        <input id="title_3" type="text" name="data[title_3]"   placeholder="{{ __('levels.Enter_last_title') }}" autocomplete="off" class="form-control @error('title_3') is-invalid @enderror" value="{{old('title_3',@$section['title_3'])}}" required>
        @error('title_3')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>   
    <div class="form-group col-md-4">
        <label for="banner">{{ __('levels.banner') }}</label> <span class="text-danger">*</span>
        <input id="banner" type="file" name="data[banner]"  class="form-control @error('banner') is-invalid @enderror">
        @error('banner')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
        <div class="mt-3">
            <img src="{{ @$section['banner_image'] }}" width="30%" />
        </div>
    </div>    
</div>