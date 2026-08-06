"use strict";
$(document).on('change', '#payment_method', function () {
   var payment_method=$(this).val();
    if(payment_method == 'bank'){
        $('.bankform').addClass('d-block');
        $('.bankform').removeClass('d-none');

        $('.mobileform').addClass('d-none');
        $('.mobileform').removeClass('d-block');
    }else if(payment_method == 'mobile'){
        $('.bankform').addClass('d-none');
        $('.bankform').removeClass('d-block');

        $('.mobileform').addClass('d-block');
        $('.mobileform').removeClass('d-none');
    }
});
