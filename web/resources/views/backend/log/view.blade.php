
<table class="table ">
    <thead>
        <tr class="bg-primary  ">
            <th scope="col" class="text-white">{{ __('logs.title') }}</th>
            <th scope="col" colspan="2" class="text-white  ">{{ __('logs.properties') }}</th>
        </tr>
        <tr>
            <th scope="col" class="bg-primary"></th>
            <th scope="col" class="bg-primary">{{ __('logs.new') }}</th>
            <th scope="col"  class="bg-primary">{{ __('logs.old') }}</th>
        </tr>
    </thead>
    <tbody>
        @if (!isset($logDetails->properties['attributes']) && $logDetails->properties['old'] )
            @foreach ($logDetails->properties['old']  as $key=>$value )
                <tr>
                    <td>{{  __('ActivityLogs.'.$key) }}</td>
                    <td></td>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        @else
            @foreach ( $logDetails->properties['attributes']  as $key=>$value )
                <tr>
                    <td>{{  __('ActivityLogs.'.$key) }}</td>
                    <td>{!! $value !!}</td>
                    <td>{!! @oldLogDetails($logDetails->properties['old'],$key) !!}</td>
                </tr>
            @endforeach
        @endif

    </tbody>
</table>

