
<script type="text/javascript">
    //apext charts parcels
    var options = {
    series: [
         {
            name: '{{ __("dashboard.total")}}',
            type: 'area',
            data: [
                @foreach($dates as $key => $date)
                  {{ $totals[$key] }},
                @endforeach
            ]
        },
        {
            name: '{{  __("dashboard.pending") }}',
            type: 'area',

            data: [
                @foreach($dates as $key => $date)
                  {{ $pendings[$key] }},
                @endforeach
            ]
        },
        {
            name: '{{  __("dashboard.deliver") }}',
            type: 'area',

            data: [
                @foreach($dates as $key => $date)
                  {{ $delivers[$key] }},
                @endforeach
            ]
        },
        {
            name: '{{  __("dashboard.par_deliver") }}',
            type: 'area',

            data: [
                @foreach($dates as $key => $date)
                  {{ $par_delivers[$key] }},
                @endforeach
            ]
        },
        {
            name: '{{  __("dashboard.return") }}',
            type: 'area',

            data: [
                @foreach($dates as $key => $date)
                  {{ $returns[$key] }},
                @endforeach
            ]
        }
    ],
    // colors:['#2E93fA', '#ff407b'],
    chart: {
        height: 600,
        width: '100%',
        type: 'area',
    },
    stroke: {
        curve: 'smooth'
    },
    colors:['#2E93fA', '#ff407b','#009688','#2ec551','#0998b0'],
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.5,
            opacityTo: 0.7,
            stops: [0, 100]
        }
    },
    title: {
        text: ' {{ __('parcel.title') }}',
    },
    labels: [ @foreach($dates as $key => $date)
                  '{{$date }}',
                @endforeach],
    markers: {
        size: 0
    },

    tooltip: {
        shared: true,
        intersect: false,
        y: {
            formatter: function (y) {
                if (typeof y !== "undefined") {
                    return y.toFixed(0);
                }
                return y;
            }
        }
    }
};
var chart = new ApexCharts(document.querySelector("#apexparcels"), options);
chart.render();
 //apex charts parcelspiecharts
 var options = {
          series: [{{ $piedata['total_pending'] }},{{ $piedata['total_delivered'] }},{{ $piedata['total_partial_delivered'] }},{{ $piedata['total_return'] }}],
          chart: {
          width: '100%',
          height: 700,
          type: 'pie',
        },
        colors:[ '#ff407b','#009688','#2ec551','#0998b0'],
        labels: ["{{ __('dashboard.pending') }}","{{ __('dashboard.deliver') }}","{{ __('dashboard.par_deliver') }}","{{ __('dashboard.return') }}"],
        title: {
        text: ' {{ __("parcel.title") }}',
    },
        responsive: [{
          breakpoint: 480,
          options: {
            chart: {
              width: 200
            },
            legend: {
              position: 'bottom'
            }
          }
        }]
        };
var chart = new ApexCharts(document.querySelector("#apexparcelspiechart"), options);
chart.render();
</script>
