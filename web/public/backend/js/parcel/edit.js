"use strict";
$(document).ready(function(){

    $( "#shopID" ).select2();
    $( "#category_id" ).select2();
    $( "#weightID" ).select2();
    $( "#delivery_type_id" ).select2();

    $( "#merchant_id" ).select2({
        ajax: {
            url: merchantUrl,
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    searchQuery: true
                };
            },
            processResults: function (response) {
                // console.log(response);
                return {

                    results: response
                };
            },
            cache: true
        }

    });

});

$(document).on('change', '#merchant_id', function () {
    var url = $(this).data('url');
    $.ajax({
        type : 'POST',
        url : url,
        data : {'id': $(this).val(),'shop':true},
        dataType : "html",
        success : function (data) {
            $('#shopID').html(data);
            shop(url);
            refreshQuote();
        }
    });

});



// Les taux COD ne sont plus lus ici : ils entrent dans le devis, cote serveur.




$(document).on('change', '#shopID', function () {
    var url = $(this).data('url');
    shop(url)
});

function shop(url){

    var shop_id = $("select#shopID option").filter(":selected").val();
    $.ajax({
        type : 'POST',
        url : url,
        data : {'id': shop_id,'shop':false},
        dataType : "html",
        success : function (data) {
            var shop = JSON.parse(data);
            $('#pickup_phone').val(shop.contact_no);
            $('#pickup_address').val(shop.address);
            $('#pickup_lat').val(shop.merchant_lat);
            $('#pickup_long').val(shop.merchant_long);

        }
    });
}
if(category_id === '1'){
    $('#categoryWeight').show();
    $('#weightID').show();
}else{
    $('#categoryWeight').hide();
    $('#weightID').hide();
}

$(document).on('change', '#category_id', function () {
    var category_id = $(this).val();
    if(category_id !==''){
        $.ajax({
            type : 'POST',
            url : $(this).data('url'),
            data : {'category_id': $(this).val()},
            dataType : "html",
            success : function (data) {
                if(category_id == '1'){
                    $('#categoryWeight').show();
                    $('#weightID').show();
                    $('#weightID').html(data);
                    $( "#weightID" ).select2();
                }else {
                    $('#categoryWeight').hide();
                    $('#weightID').hide();
                }
                refreshQuote();
            }
        });

    }
});

/**
 * Devis : c'est le SERVEUR qui calcule, l'ecran se contente d'afficher.
 *
 * Le socle recalculait ici frais de livraison, frais COD, TVA et net a reverser
 * en JavaScript, puis postait le tout dans `chargeDetails` — c'etait la faille
 * S2. Depuis que `ChargeCalculator` fait foi cote serveur, ce calcul local
 * n'etait plus qu'un SECOND bareme : juste tant qu'il coincidait, faux le jour
 * ou il divergeait, et personne ne l'aurait vu. Il est remplace par un appel a
 * `parcel/quote`, qui rend exactement les montants qui seront enregistres.
 *
 * `#chargeDetails` n'est donc plus alimente : le serveur l'ignore.
 */
var quoteTimer = null;

function refreshQuote() {
    var merchantId     = $('#merchant_id').val();
    var categoryId     = $('#category_id').val();
    var deliveryTypeId = $('#delivery_type_id').val();

    // Sans marchand, categorie ni type de livraison, aucun bareme n'est applicable.
    if (!merchantId || !categoryId || !deliveryTypeId) {
        return;
    }

    // La frappe dans le montant declenche cette fonction : on la laisse retomber.
    clearTimeout(quoteTimer);
    quoteTimer = setTimeout(function () {
        $.ajax({
            type: 'POST',
            url: quoteUrl,
            dataType: 'json',
            data: {
                merchant_id: merchantId,
                category_id: categoryId,
                delivery_type_id: deliveryTypeId,
                weight: $('#weightID').val(),
                cash_collection: $('#cash_collection').val(),
                packaging_id: $('#packaging_id').val(),
                fragileLiquid: $('#fragileLiquid').is(':checked')
            },
            success: function (response) {
                var amount = response.display;
                $('#totalCashCollection').text(amount.cash_collection);
                $('#deliveryChargeAmount').text(amount.delivery_charge);
                $('#codChargeAmount').text(amount.cod_amount);
                $('#liquidFragileAmount').text(amount.liquid_fragile_amount);
                $('#packagingAmount').text(amount.packaging_amount);
                $('#totalDeliveryChargeAmount').text(amount.total_delivery_amount);
                $('#VatAmount').text(amount.vat_amount);
                $('#netPayable').text(amount.total_payable_charges);
                $('#currentPayable').text(amount.current_payable);
            }
        });
    }, 300);
}

$(document).on('change', '#merchant_id', refreshQuote);
$(document).on('change', '#delivery_type_id', refreshQuote);
$(document).on('change', '#weightID', refreshQuote);
$(document).on('keyup change', '#cash_collection', refreshQuote);

$(document).on('change', '#packaging_id', function () {
    // Le montant de l'emballage vient du devis ; ici on ne fait qu'afficher la ligne.
    var selected = $('select#packaging_id option').filter(':selected').data('packagingamount');
    $('#packagingShow').toggle(!isNaN(parseFloat(selected)));
    refreshQuote();
});

function processCheck(event) {
    $('.hideShowLiquidFragile').toggle($('#fragileLiquid').is(':checked'));
    refreshQuote();
}
