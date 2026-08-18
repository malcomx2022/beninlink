@extends('backend.partials.master')
@section('title'){{ __('customs.rules') }}@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-xl-8 col-lg-10 col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ $rule->country_name }} — {{ __('customs.category_' . $rule->goods_category) }}</h5>
                    {{-- Pays et catégorie identifient la règle (index unique) : non modifiables. --}}
                    <small class="text-muted">{{ __('customs.country') }} / {{ __('customs.category') }}</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('customs.rules.update', $rule->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>{{ __('customs.level') }}</label>
                            <select name="level" class="form-control" required>
                                @foreach($levels as $value => $label)
                                    <option value="{{ $value }}" {{ old('level', $rule->level) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('level')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label>{{ __('customs.required_document') }}</label>
                            <input type="text" name="required_document" class="form-control"
                                   value="{{ old('required_document', $rule->required_document) }}" maxlength="191">
                            @error('required_document')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label>{{ __('customs.message') }}</label>
                            <textarea name="message" class="form-control" rows="3" required>{{ old('message', $rule->message) }}</textarea>
                            @error('message')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>

                        <div class="form-group">
                            <label>{{ __('levels.status') }}</label>
                            <select name="status" class="form-control" required>
                                <option value="1" {{ old('status', $rule->status) == 1 ? 'selected' : '' }}>{{ __('levels.active') }}</option>
                                <option value="0" {{ old('status', $rule->status) == 0 ? 'selected' : '' }}>{{ __('levels.inactive') }}</option>
                            </select>
                        </div>

                        <a href="{{ route('customs.rules') }}" class="btn btn-secondary">{{ __('levels.cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('levels.update') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
