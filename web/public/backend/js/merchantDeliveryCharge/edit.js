"use strict";
$(document).on('change', '#deliveryChargeID', function () {
    $.ajax({
        type : 'POST',
        url : $(this).data('url'),
        data : {'delivery_charge_id': $(this).val()},
        dataType : "html",
        success : function (data) {
            $('#deliveryChargeInfo').html(data);
        }
    });
});
