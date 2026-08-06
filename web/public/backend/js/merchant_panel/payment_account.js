"use strict";
$(document).ready(function(){
    $('#payment_method').change(function(){
        var payment_method=$(this).val();
         if(payment_method == 'bank'){
             $('.bank').show();
             $('.mobile').hide();
             $('.bank-mobile').show();
         }else if(payment_method == 'mobile'){
            $('.mobile').show();
            $('.bank').hide();
            $('.bank-mobile').show();
         }else if(payment_method == 'cash'){
            $('.mobile').hide();
            $('.bank').hide();
            $('.bank-mobile').show();
         }else{
            $('.mobile').hide();
            $('.bank').hide();
            $('.bank-mobile').hide();
         }
    });
});
