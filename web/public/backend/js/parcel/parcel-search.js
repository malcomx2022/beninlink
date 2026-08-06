"use strict";
$(document).ready(function(){
    // Write on keyup event of keyword input element
    $("#Psearch").keyup(function(){
        var searchText = $(this).val().toLowerCase();

        // Show only matching TR, hide rest of them
        $.each($("#table tbody tr"), function() {
            if($(this).text().toLowerCase().indexOf(searchText) === -1)
               $(this).hide();
            else
               $(this).show();
        });
    });


});
