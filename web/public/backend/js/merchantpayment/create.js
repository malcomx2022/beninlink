"use strict";
$(document).on('change', '#payment_method', function () {
    $('#payment_method_name').attr('value',$(this).val());
    var merchant_id= $('#merchant_id').val();
    var editid=$('#editid').val();
    $.ajax({
        type : 'POST',
        url : $(this).data('url'),
        data : {'payment_method': $(this).val(),'merchant_id':merchant_id,'editid':editid},
        dataType : "html",
        success : function (data) {
            $('#info').html(data);
        }
    });
});
