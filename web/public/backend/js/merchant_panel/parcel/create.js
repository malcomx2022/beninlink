"use strict";
$(document).ready(function(){

    $( "#shopID" ).select2();
    $( "#category_id" ).select2();
    $( "#weightID" ).select2();
    $( "#delivery_type_id" ).select2();
    refreshQuote();
});

$(document).on('change', '#shopID', function () {
    var url = $(this).data('url');
    $.ajax({
        type : 'POST',
        url : url,
        data : {'id': $(this).val(),'shop':false},
        dataType : "html",
        success : function (data) {
            var shop = JSON.parse(data);
            $('#merchant_id').val(shop.merchant_id);
            $('#pickup_phone').val(shop.contact_no);
            $('#pickup_address').val(shop.address);
            $('#pickup_lat').val(shop.merchant_lat);
            $('#pickup_long').val(shop.merchant_long);
        }
    });
});


$('#categoryWeight').hide();
$('#weightID').hide();
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
    var categoryId     = $('#category_id').val();
    var deliveryTypeId = $('#delivery_type_id').val();

    // Sans categorie ni type de livraison, il n'y a rien a deviser.
    if (!categoryId || !deliveryTypeId) {
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
                merchant_id: $('#merchant_id').val(),
                category_id: categoryId,
                delivery_type_id: deliveryTypeId,
                weight: $('#weightID').val(),
                cash_collection: $('#cash_collection').val(),
                packaging_id: $('#packaging_id').val(),
                fragileLiquid: $('#fragileLiquid').is(':checked'),
                // D4, etape 5 bis : la route du colis. Les deux champs
                // n'existent que si la societe a des zones ; sinon le
                // serveur recoit `undefined` et devise comme avant.
                zone_id: $('#zone_id').val(),
                delay_id: $('#delay_id').val(),
                destination_country: $('#destination_country').val()
            },
            // Une route non tarifee rend 422 : afficher le message du
            // serveur plutot que de garder a l'ecran les montants du
            // devis precedent, qui ne valent plus rien.
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                    $('#quoteError').text(xhr.responseJSON.message).show();
                }
            },
            success: function (response) {
                $('#quoteError').hide().text('');
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

$(document).on('change', '#delivery_type_id', refreshQuote);
$(document).on('change', '#weightID', refreshQuote);
$(document).on('change', '#zone_id', refreshQuote);
$(document).on('change', '#delay_id', refreshQuote);
$(document).on('keyup change', '#cash_collection', refreshQuote);

$('#packagingShow').hide();
$(document).on('change', '#packaging_id', function () {
    // Le montant de l'emballage vient du devis ; ici on ne fait qu'afficher la ligne.
    var selected = $('select#packaging_id option').filter(':selected').data('packagingamount');
    $('#packagingShow').toggle(!isNaN(parseFloat(selected)));
    refreshQuote();
});

$('.hideShowLiquidFragile').hide();
function processCheck(event) {
    $('.hideShowLiquidFragile').toggle($('#fragileLiquid').is(':checked'));
    refreshQuote();
}
