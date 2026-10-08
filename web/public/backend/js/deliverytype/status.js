'use strict';
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
            var key=$(this).val();
            $.ajax({
                type : 'POST',
                url : $('#switch-id').data('url'),
                data : {'key':key},
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
                        })

                        Toast.fire({
                        icon: 'success',
                        title: trad.status_updated
                        })

                    location.reload();
                },
                error:function(data){
                    console.log(data);
                }
            });
        }
      })
    });

});
