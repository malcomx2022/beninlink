
"use strict";
$(document).ready(function(){
    // Parcel status update confarmation
    $(".parcel_status_update_button").on('click',function(e){
        e.preventDefault();
        var self = $(this);
        Swal.fire({
        text: 'Do you want to update this?',
        position: 'top',
        showOkButton: true,
        showCancelButton: true,
        confirmButtonText: 'Yes',
        denyButtonText: `Cancel`,
        }).then((result) => {
        if (result.isConfirmed) {
            location.href = self.attr('href');
        }
        })
    });

    $(".default_shop_button").on('click',function(e){
        e.preventDefault();
        var self = $(this);
        Swal.fire({
            text: 'Do you want to update this?',
            position: 'top',
            showOkButton: true,
            showCancelButton: true,
            confirmButtonText: 'Yes',
            denyButtonText: `Cancel`,
        }).then((result) => {
            if (result.isConfirmed) {
                location.href = self.attr('href');
            }
        })
    });

  // start
  $('form#delete').on('submit', function (e) {
    var title = $(this).data('title');
    e.preventDefault();
    var form = this;

    Swal.fire({
      text: title,
      position: 'top',
      showOkButton: true,
      showCancelButton: true,
      confirmButtonText: yes,
      cancelButtonText: cancel,
    }).then((result) => {
      if (result.isConfirmed){
        form.submit();
      }
    })
  });
  // end

  $('[data-toggle="datepicker"]').datepicker({
    format: 'yyyy-mm-dd'
  });
  $("#merchant_registration_submit").prop('disabled', true);

  $('#merchant_registration_checkbox').on('change', function() {
    if($(this).is(":checked"))
      $("#merchant_registration_submit").prop('disabled', false);
    else
      $("#merchant_registration_submit").prop('disabled', true);
  });
  $(".select2").select2();
});

