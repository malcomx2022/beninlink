"use strict";
function getUser(value) {

    $( "#user_id").select2({
        ajax: {
            url: $("#user_id").data('url'),
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    userType: value,
                    searchQuery: true
                };
            },
            processResults: function (response) {
                return {
                    results: response
                };
            },
            cache: true
        }
    });
}
