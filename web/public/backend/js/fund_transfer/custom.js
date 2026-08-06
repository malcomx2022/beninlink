"use strict";
$(document).ready(function(){
    if($("#current_balance").val() == ''){
        $("p").hide();
        $("#amount").prop('disabled', true);
    }

    $('#currentBalance').text($('#current_balance').val());

    $("#from_account").on('change', function(){
        let value = $("#from_account").val();
        $.ajax({
            type: 'post',
            url: '/admin/accounts/current-balance',
            data: {'search': value},
            success: function (data) {
                if(data.id == $('#old_from_account').val()){
                    $('#currentBalance').text(parseInt(data.balance) + parseInt($("#old_amount").val()));
                }
                else if(data.id == $('#old_to_account').val()){
                    $('#currentBalance').text(parseInt(data.balance) - parseInt($("#old_amount").val()));
                }
                else{
                    $('#currentBalance').text(data.balance);
                }

                $("p").show();
                $("#amount").prop('disabled', false);
                $('#current_balance').val(data.balance);
            }
        })
    });

    // Check balance
    $("#amount").on('keyup', function(){
        if(parseInt($('#currentBalance').text()) < parseInt($("#amount").val())){
            $('.check_message').empty(); 
            $('.check_message').append('<small class="text-danger">Ops! not enough blance.</small>');
        }
        else{
            $('.check_message').empty(); 
        }
    });
});