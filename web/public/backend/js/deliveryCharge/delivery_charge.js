"use strict";
// Delivery charge -> When category selected KG. then show weight otherwise hide weight
  // start
  if($("#category").val() == 1)
  {
      $("#weight_group").show();
  }
  else
  {
      $("#weight_group").hide();
  }

  $("#category").on('change', function () {

      if($("#category").val() == 1)
      {
          $("#weight_group").show();
      }
      else
      {
          $("#weight_group").hide();
      }

  });
  // end
