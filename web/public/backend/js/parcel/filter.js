"use strict";
$(document).ready(function(){
    $( "#parcelStatus" ).select2();
    $( "#parcelMerchantid" ).select2({
        ajax: {
            url: merchantUrl,
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    searchQuery: true
                };
            },
            processResults: function (response) {
                console.log(response);
                return {

                    results: response
                };
            },
            cache: true
        }

    });

    $( "#parcelDeliveryManID").select2({
        ajax: {
            url: $("#parcelDeliveryManID").data('url'),
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    searchQuery: true
                };
            },
            processResults: function (response) {
                console.log(response);
                return {

                    results: response
                };
            },
            cache: true
        }
    });


    $( "#parcelPickupmanId" ).select2({
        ajax: {
            url: $("#parcelPickupmanId").data('url'),
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    searchQuery: true
                };
            },
            processResults: function (response) {
                console.log(response);
                return {

                    results: response
                };
            },
            cache: true
        }
    });


    $('#dates').datepicker({
        format: 'yyyy-mm-dd'
      });



    $( "#parcel_profit" ).select2({
        ajax: {
            url: '/admin/reports-tracking-parcels',
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function (params) {

                return {
                    search: params.term,
                    searchQuery: true
                };
            },
            processResults: function (response) {
                console.log(response);
                return {

                    results: response
                };
            },
            cache: true
        }
    });

});
