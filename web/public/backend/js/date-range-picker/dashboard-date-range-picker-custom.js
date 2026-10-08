
'use strict';
$(function() {

    function cb(start, end) {
        $('.date_range_picker').val(start.format('MM/DD/YYYY') + ' To ' + end.format('MM/DD/YYYY'));
    }

    $('.date_range_picker').daterangepicker({
        startDate: moment(),
        endDate: moment(),
        autoUpdateInput: false,
        locale: {
            format: 'MM/DD/YYYY',
            separator: " To ",
            cancelLabel: trad.clear,
            applyLabel: trad.apply,
            customRangeLabel: trad.custom_range,
            daysOfWeek: trad.days_of_week,
            monthNames: trad.month_names
        },
        ranges: {
            [trad.today]: [moment(), moment()],
            [trad.yesterday]: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            [trad.last_7_days]: [moment().subtract(6, 'days'), moment()],
            [trad.last_30_days]: [moment().subtract(29, 'days'), moment()],
            [trad.this_month]: [moment().startOf('month'), moment().endOf('month')],
            [trad.last_month]: [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, cb);

    $('.date_range_picker').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' To ' + picker.endDate.format('MM/DD/YYYY'));
    });

    $('.date_range_picker').on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });


});
