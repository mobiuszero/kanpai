// Geo Location
$.get("assets/inc/ip_address_pixel.php", function () {
}).fail(function (response) {
    console.log("Error: ", response);
});