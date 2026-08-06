"use strict";
$(document).ready(function(){
    //change language  localization
    $(".changeLocale").on('change',function(){
        var lang = $(this).val();

            var url = "/localization/"+lang;
        window.location.href = url;
    });

});
