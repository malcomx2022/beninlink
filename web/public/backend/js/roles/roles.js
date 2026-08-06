"use strict";
$(document).ready(function(){
    $(document).on('change', '.common-key', function(){
        var value = $(this).val();
        var value = value.split("_");
            if(value[1] == 'read' || value[0] == 'manage'){
                if (!$(this).is(':checked')) {
                    $(this).closest('tr').find('.common-key').prop('checked', false);
                }
            }
            else{
                if ($(this).is(':checked')) {
                    $(this).closest('tr').find('.common-key').first().prop('checked', true);
                }

            }
    });

});
