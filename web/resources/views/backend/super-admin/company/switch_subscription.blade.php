
<form action="{{ route('company.subscription.switch.store',['user_id'=>$user_id]) }}" method="post" > 
    @csrf
    <div class="form-group">
        <span>{{ __('levels.current_plan') }}: {{ @$plan->name }}</span>
    </div>
    <div class="form-group">
        <label for="input-select">{{ __('levels.plan') }}</label> <span
            class="text-danger">*</span>
        <select class="form-control @error('plan_id') is-invalid @enderror" name="plan_id" required>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}" > {{ $plan->name }}</option>
            @endforeach
        </select>
        @error('plan_id')
            <small class="text-danger mt-2">{{ $message }}</small>
        @enderror
    </div>
    {{-- R3 (S75) : le super-administrateur voit la même règle que l'acheteur, avant d'enregistrer. --}}
    <div class="alert alert-warning plan-switch-notice">{{ __('levels.plan_switch_notice') }}</div>
    <div class="text-right">
        <button type="submit" class="btn btn-primary">{{ __('levels.save') }}</button>
    </div>
</form>