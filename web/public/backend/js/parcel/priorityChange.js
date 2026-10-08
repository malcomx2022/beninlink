
"use strict";
$(document).ready(function () {
    $('.switch-id').change(function (e) {

        Swal.fire({
            text: confirmUpdate,
            position: 'top',
            showCancelButton: true,
            confirmButtonText: yes,
            cancelButtonText: cancel,
          }).then((result) => {
            if (result.isConfirmed){
                    $.ajax({
                        type : 'POST',
                        url : $(this).data('url'),
                        data : {'priority':$(this).val(),'id':$(this).data('id')},
                        dataType : "html",
                        success : function (data) {

                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                                }
                            });

                            Toast.fire({
                                icon: 'success',
                                title: trad.priority_updated
                            });
                            location.reload();

                        }
                    });
            }
          })

    });



});
