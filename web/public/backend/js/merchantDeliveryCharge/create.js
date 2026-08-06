"use strict";
$(function()  {
    var delivery_charge_id = $("select#deliveryChargeID option").filter(":selected").val();
    if(delivery_charge_id){
        $.ajax({
            type : 'POST',
            url : $('#deliveryChargeID').data('url'),
            data : {'delivery_charge_id':delivery_charge_id},
            dataType : "html",
            success : function (data) {
                $('#deliveryChargeInfo').html(data);
            }
        });
    }

});

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
