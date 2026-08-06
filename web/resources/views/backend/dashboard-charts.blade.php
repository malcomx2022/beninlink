
<script type="text/javascript">
            //apext charts //income and expense
            var options = {
            series: [  {
                name: '{{ __("income.title")}}',
                type: 'area',
                data: [
                    @foreach($data['incomeDates'] as $date)
                              {{  dayIncomeCount($date) }},
                    @endforeach
                ]
            }, {
                name: '{{  __("expense.title") }}',
                type: 'area',

                data: [
                    @foreach($data['expenseDates'] as $date)
                        {{  dayExpenseCount($date) }},
                    @endforeach
                ]
            }],
            colors:['#2E93fA', '#ff407b'],
            chart: {
                height: 450,
                type: 'area',
            },
            stroke: {
                curve: 'smooth'
            },
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
                text: ' {{ __('income.title') }} / {{ __('expense.title') }}',
            },
            labels: [@foreach($data['expenseDates'] as $date)
                           '{{ $date }}',
                    @endforeach],
            markers: {
                size: 0
            },
            yaxis: [
                {
                    title: {
                        text: ' {{ __('income.title') }} / {{ __('expense.title') }}',
                    },
                },
            ],
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


        var chart = new ApexCharts(document.querySelector("#apexincomeexpense"), options);
        chart.render();

</script>
 

<script type="text/javascript">
    //apex piecharts courier revenue

    var options = {
          series: [{{ $data['courier_income'] }}, {{ $data['courier_expense'] }}],
          chart: {
          width: 600,
          type: 'polarArea'
        },
        labels: ["{{ __('income.title') }}", "{{ __('expense.title') }}"],

        fill: {
          opacity: 1,
          colors:['#2E93fA', '#ff407b'],
        },
        colors:['#2E93fA', '#ff407b'],
        stroke: {
          width: 1,
          colors:['#2E93fA', '#ff407b'],
        },

        title: {
                text: '{{ __('dashboard.courier') }} {{ __('dashboard.revenue') }}',
            },

        yaxis: {
          show: false
        },
        legend: {
          position: 'bottom'
        },

        plotOptions: {

          polarArea: {
            rings: {
              strokeWidth: 0
            },
            spokes: {
              strokeWidth: 0
            },
          }
        },



        };

        var chart = new ApexCharts(document.querySelector("#apexpiecourierrevenue"), options);
        chart.render();
</script>
