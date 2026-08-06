"use strict";
$(document).ready(function() {
    $(document).on('click', '.modalBtn', function(e) {
        e.preventDefault();
        var title = $(this).data('title');
        var modalsize = $(this).data('modalsize');
        if(modalsize !=""){
            $("#modalSize").addClass(modalsize);
        }
        if(title != "" && title !=null){
            $(".modal-title").text(title);
        }
        var url = $(this).data('url');
        $('#modal-main-content').html('');
        $('#modal-loader').show();
        $.ajax({
            url: url,
            type: 'get',
            dataType: 'html'
        })
        .done(function(data) {
            $('#modal-main-content').html('');
            $('#modal-main-content').html(data);
            $('#modal-loader').hide();
        })
        .fail(function() {
            $('#modal-main-content').html('<i class="glyphicon glyphicon-info-sign"></i> Something went wrong...');
            $('#modal-loader').hide();
        });
    });
    $(".regular-btn").click(function(){
        $("#pickup-request").modal('hide');
    });
    $(".express-btn").click(function(){
        $("#pickup-request").modal('hide');
    });


});
