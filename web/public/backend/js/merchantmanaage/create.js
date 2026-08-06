"use strict";
$(document).ready(function(){
    $('#merchant').change(function(){

        var merchant=$(this).val();
        $.ajax({
            type : 'POST',
            url : $(this).data('url'),
            data : {'merchant_id':merchant},
            dataType : "html",
            success : function (data) {

                $('#merchant_account').html(data);
            }
        });

    });

    $( "#merchant" ).select2({
        ajax: {
            url: $('#mercant_url').data('url'),
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


    $('#isprocess').change(function(){
        if($('#isprocess').is(':checked')){
             $('.process').show();
        }else{
            $('.process').hide();
        }
    });
});
