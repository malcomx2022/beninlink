@if(!blank($deliveryCharges))
    @foreach ($deliveryCharges as $deliveryCharge)
        <option value="{{ $deliveryCharge->weight ?? 0 }}" {{ (old('weight') == $deliveryCharge->weight) ? 'selected' : '' }}>{{ $deliveryCharge->weight }} {{ $deliveryCharge->category->title }}</option>
    @endforeach
@endif
