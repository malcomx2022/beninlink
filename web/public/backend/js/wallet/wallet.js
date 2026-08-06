$(document).ready(function(){ 
    'use strict';
  
    $( "#merchant_id").select2({ 
        ajax: {
            url: $('#merchant_id').data('url'),
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) {  
               
                return {
                    searchQuery:true,
                    search: params.term
                };
            },
            processResults: function (response) {  
                return {  
                    results: response
                };
            },
            cache: true
        }
    });
    $( "#wallet_merchant_id").select2({ 
        dropdownParent: $('#add-to-wallet-modal'),
        ajax: {
            url: $('#wallet_merchant_id').data('url'),
            type: "post",
            dataType: 'json',
            delay: 250,
            data: function (params) { 
                return {
                    searchQuery:true,
                    search: params.term
                };
            },
            processResults: function (response) {  
                return { 
                    results: response
                };
            },
            cache: true
        }
    });

    $('.quick-amount').on('click', function() {
        $('#recharge_amount').val(parseFloat($(this).data('amount')));
    }); 
     
});