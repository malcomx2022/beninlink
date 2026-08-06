

'use strict';
$(document).ready(function(){
        $( "#user_search" ).select2({
            ajax: {
                url: $("#user_search").data('url'),
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

        $( "#parcel" ).select2({
            ajax: {
                url: $("#parcel").data('url'),
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

        $( "#income_merchant_id" ).select2({
            ajax: {
                url: $("#income_merchant_id").data('url'),
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

        $( "#income_delivery_man_id" ).select2({
            ajax: {
                url: $("#income_delivery_man_id").data('url'),
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

                    return {
                        results: response
                    };
                },
                cache: true
            }
        });

        $('#usertype').change(function(){
                var user_type   = $("#usertype").val();
                var merchant    = 2;
                var deliveryman = 3;


                if(user_type == merchant){
                    $('.deliverymans').hide();
                    $('.merchants').show();
                }else if(user_type == deliveryman){
                    $('.merchants').hide();
                    $('.deliverymans').show();
                }
        })


});
