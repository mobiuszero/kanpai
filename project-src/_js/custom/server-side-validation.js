/* Find The Form */
var submission_form = $("#submission_form");
/* Validate The Form */
submission_form.validate({
    errorElement: "div",
    errorPlacement: function (error, element) {
        var parent_element = element.parent();
        for (var i = 0; i < parent_element.length; i++) {
            $(parent_element[i]).addClass("has-danger");
        }
        element.addClass("form-control-danger");
        error.appendTo(element.parent("div")).addClass("form-control-feedback alert alert-danger");
    },
    validClass: "form-control-success",
    success: function (element) {
        for (var i = 0; i < element.length; i++) {
            var inputFields = $(element[i]).parent().children();
            $(inputFields[1]).removeClass("form-control-danger");
            $(element[i]).parent().removeClass("has-danger").addClass("has-success");
            $(inputFields[1]).addClass("form-control-success");
            $(element[i]).remove();
        }
    },
    submitHandler: function (form) {
        var serialize_form_data = $(form).serialize();
        $.ajax({
            url: form.action,
            type: 'POST',
            dataType: 'json',
            crossDomain: true,
            data: serialize_form_data,
            statusCode: {
                404: function () {
                    console.log("Server script is missing");
                    self_redirect();
                },
                500: function () {
                    console.log("Server script has encountered a fatal error");
                    self_redirect();
                }
            }
        }).done(function (response) {
            /* Process the server callback */
            if (response.status) {
                server_callback(response);
            } else {
                server_callback(response);
                tryAgain_form_button();
            }
        }).fail(function (response) {
            console.log("Request Failed, Check request made: ", response);
        });

        /* The Loading Form Button Animation */
        loading_form_button();
    }
});

/* Handle Server Callback */
function server_callback(server_response) {
    console.log("Server response: ", server_response);

    var parent_element = $("#" + server_response.field);
    var server_callback_message_field = $(".server-callback." + server_response.field);
    var inputFields = $("#" + server_response.field + "_input");

    /* Process Server Callback */
    if (server_response.status) {
        $(parent_element).addClass("has-success").removeClass("has-danger");
        $(inputFields).removeClass("form-control-danger").addClass("form-control-success");
        success_redirect(server_response.params, server_response.redirect);
    } else if (server_response.status === false && server_response.field === "internal_error") {
        $(parent_element).removeClass("has-success").addClass("has-danger");
        $(server_callback_message_field).html(server_response.message).removeClass("form-control-danger").addClass("form-control-feedback alert alert-danger");
        self_redirect();
    } else {
        $(parent_element).removeClass("has-success").addClass("has-danger");
        $(inputFields).removeClass("form-control-success").addClass("form-control-danger");
        $(server_callback_message_field).html(server_response.message).removeClass("form-control-danger").addClass("form-control-feedback alert alert-danger");
    }
}

/* Loading Animation For Form Button */
function loading_form_button() {
    $(".server-callback").html("").removeClass("form-control-feedback alert alert-danger");
    $("#submission_form_btn").html("Please Wait ... <i class=\"fa fa-cog fa-spin fa-fw\"></i>").attr('disabled', 'disabled');
}

/* Try Again Error Message For Form Button */
function tryAgain_form_button() {
    $("#submission_form_btn").html("Please Try Again!").removeAttr("disabled");
}

/* Redirect for internal errors */
function self_redirect() {
    setInterval(function () {
        document.location.href = window.location.href;
    }, 10000);
}

/* Success redirect */
function success_redirect(response_params, response_redirect) {
    setInterval(function () {
        document.location.href = response_redirect + "?" + response_params;
    }, 1500);
}

/* Expired google recaptcha */
function g_recaptcha_expired() {
    grecaptcha.reset();
}