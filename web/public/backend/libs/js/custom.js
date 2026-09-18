
"use strict";
$(document).ready(function(){
    // Parcel status update confarmation
    $(".parcel_status_update_button").on('click',function(e){
        e.preventDefault();
        var self = $(this);
        Swal.fire({
        text: confirmUpdate,
        position: 'top',
        showCancelButton: true,
        confirmButtonText: yes,
        cancelButtonText: cancel,
        }).then((result) => {
        if (result.isConfirmed) {
            location.href = self.attr('href');
        }
        })
    });

    // S26 — la boutique par defaut se change par un FORMULAIRE, plus par un
    // lien : `location.href` ne peut pas emettre un PUT, et un GET qui ecrit
    // echappe a la protection CSRF. On garde la meme confirmation, declenchee
    // sur la soumission — comme `form#delete` juste en dessous.
    $('form.confirm-submit').on('submit', function (e) {
        var form = this;
        if ($(form).data('confirmed')) {
            return;
        }
        e.preventDefault();
        Swal.fire({
            text: confirmUpdate,
            position: 'top',
            showCancelButton: true,
            confirmButtonText: yes,
            cancelButtonText: cancel,
        }).then((result) => {
            if (result.isConfirmed) {
                $(form).data('confirmed', true);
                form.submit();
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

