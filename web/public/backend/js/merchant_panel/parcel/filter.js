"use strict";
$(document).ready(function(){
    $( "#parcelStatus" ).select2();
    // (S70) Pas de sélecteur de marchand ici : au panneau marchand, le marchand est le
    // compte connecté. L'ancien bloc lisait `merchantUrl`, jamais défini par ces vues —
    // une ReferenceError qui coupait tout ce qui suivait.

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

      
    


});
