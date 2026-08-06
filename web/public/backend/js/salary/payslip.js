"use strict";
function printDiv(divID) {
    var divElements = document.getElementById(divID).innerHTML;
    var url = $('#domain').val();
    var mywindow = window.open();
    mywindow.document.write('<html><head><title></title>');
    mywindow.document.write("<link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css\" type=\"text/css\" />");
    mywindow.document.write("<link rel=\"stylesheet\" href=\""+url+"/css/bulk_print.css\" type=\"text/css\" />");

    mywindow.document.write('</head><body >');
    mywindow.document.write(divElements);
    mywindow.document.write('</body></html>');

    mywindow.print();
    // mywindow.close();
}
