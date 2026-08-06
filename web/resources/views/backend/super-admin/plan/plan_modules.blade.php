@if (!blank($plan->modules))
    <h3>Module list</h3>
    <div class="row">
        @foreach ($plan->modules as $module)
            <div class="col-md-6">
                <i class="fa fa-check text-success mr-10px"></i>
                {{ __('permissions.' . $module) }}
            </div>
        @endforeach
    </div>
@else
    <div class="text-center">Modules not found.</div>
@endif
