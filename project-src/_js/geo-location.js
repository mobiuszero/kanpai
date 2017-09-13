// Geo Location update
$.get("https://freegeoip.net/json/", function (response) {
    $("input[name='form_params[user_geo_data]']").val(JSON.stringify(response));
}, "jsonp");