"use strict";
$(document).ready(function(){

    // Select from
    $(".delivery_man_search").hide();
    $(".merchant_search").hide();
    $(".user_search").hide();
    $("#title_group").hide();

    if($("#account_head").val() != null){
        if($("#account_head").val() == 4){
            $(".merchant_search").show();
        }
        else if($("#account_head").val() == 5){
            $(".delivery_man_search").show();
        }
        else if($("#account_head").val() == 6){
            $(".user_search").show();
            $("#title_group").show();
        }
    }

    $("#account_head").on('change', function(){
        if($("#account_head").val() == 4){
            $(".merchant_search").show();
            $(".delivery_man_search").hide();
            $(".user_search").hide();
            $("#title_group").hide();
        }
        else if($("#account_head").val() == 5){
            $(".merchant_search").hide();
            $(".delivery_man_search").show();
            $(".user_search").hide();
            $("#title_group").hide();
        }
        else if($("#account_head").val() == 6){
            $(".merchant_search").hide();
            $(".delivery_man_search").hide();
            $(".user_search").show();
            $("#title_group").show();
        }
    });
 
    // Show balance
    if($("#account_id").val() == null){
        // $(".amount_div").hide();
        $(".btn").prop('disabled', true);
    }
    $("#account_id").on('change', function(){
        $.ajax({
            type: 'post',
            url: '/admin/expense/search-account/'+ $("#account_id").val(),
            data: {'search': value},
            success: function (data) {
                if($("#account_id").val() == $("#old_account_id").val()){
                    $('#account_balance_').text(parseInt(data['balance']) + parseInt($("#old_amount").val()));
                }
                else{
                    $('#account_balance_').text(data['balance']);
                }
                $('#account_balance').val(parseInt(data['balance']));

                if(parseInt($('#account_balance_').text()) >= parseInt($("#amount").val()) && $("#amount").val() != ''){
                    $(".btn").prop('disabled', false);
                    $('.check_message').empty();
                }
                else if($("#amount").val() == ''){
                    $(".btn").prop('disabled', true);
                    $('.check_message').empty();
                }
                else{
                    $(".btn").prop('disabled', true);
                    $('.check_message').empty();
                    $('.check_message').append('<small class="text-danger">Not enough blance!</small>');
                }
            }
        })
    });

    // Check balance
    $("#amount").on('keyup', function(){
        if(parseInt($('#account_balance_').text()) >= parseInt($("#amount").val()) && $("#amount").val() != ''){
            $(".btn").prop('disabled', false);
            $('.check_message').empty();
        }
        else if($("#amount").val() == ''){
            $(".btn").prop('disabled', true);
            $('.check_message').empty();
        }
        else{
            $(".btn").prop('disabled', true);
            $('.check_message').empty();
            $('.check_message').append('<small class="text-danger">Ops! not enough blance.</small>');
        }
    });

    $( "#parcelMerchantid_" ).select2({
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

    $( "#parcelDeliveryManID_").select2({
        ajax: {
            url: $("#parcelDeliveryManID_").data('url'),
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


    // search user
    $( "#user_id").select2({
        ajax: {
            url: $("#user_id").data('url'),
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



    // Transfer to hub multiple parcel
    let value, submit = 0;
    $('#transfer_to_hub_track_id_').on('keyup', function () {
        value = $('#transfer_to_hub_track_id_').val();
        if(value.length == 14 && submit == 0){
            submit = 1; // multiple request hande!
            $.ajax({
                type: 'post',
                url: '/admin/parcel/search-expense',
                data: {'search': value},
                success: function (data) {
                    setTimeout(function() {
                        submit = 0;
                        if(data == 0){
                            $('#parcel_id').val(null);
                            $('.search_message').empty();
                            $('.search_message').append('<small class="text-danger">Parcel not found!</small>');
                        }
                        else{
                            $('.search_message').empty();
                            $('.search_message').append('<small class="text-success">Parcel found.</small>');
                            $('#parcel_id').val(data['id']);
                        }
                    }, 250);
                }
            })
        }
        else if(value.length > 14 && submit == 0){
            $('#parcel_id').val(null);
            $('.search_message').empty();
            $('.search_message').append('<small class="text-danger">Maximum 14 characters!</small>');
        }
        else{
            $('#parcel_id').val(null);
            $('.search_message').empty();
            $('.search_message').append('<small class="text-danger">Minimum 14 characters!</small>');
        }

    })


    $('.salary_user').on('change',function(){
        $.ajax({
            type: 'post',
            url: '/admin/salary/search-account',
            data: {'user_id': $(this).val(),'month':$('#month').val()},
            success: function (data) {

                $('#user_salary').text('Salary : '+data);
            }
        })
    });

});
