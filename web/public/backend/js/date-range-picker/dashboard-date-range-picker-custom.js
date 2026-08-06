
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
            cancelLabel: 'Clear'
        },
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, cb);

    $('.date_range_picker').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' To ' + picker.endDate.format('MM/DD/YYYY'));
    });

    $('.date_range_picker').on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });


});
