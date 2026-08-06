@if(!blank($merchantShops))
    @foreach ($merchantShops as $shop)
        <option value="{{ $shop->id }}" {{ (old('shop_id') == $shop->id) ? 'selected' : '' }}>{{ $shop->name }}</option>
    @endforeach
@endif
