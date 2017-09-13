<?php
error_reporting(E_ALL & ~E_NOTICE);
// Load assets
// -----------------------------------
require 'vendor/autoload.php';
require 'functions/commons.php';
require 'functions/vendor.php';

// Call the submission class
// -----------------------------------
$database = (new ZenApp\ZenAppConnection)->connect();
$database_submission = new ZenApp\ZenAppSubmissionActions($database);

// Call form settings
// -----------------------------------


// Get the user's IP address
// -----------------------------------
$ip_address = get_ip_address();

// Variables
// -----------------------------------
$error_message = [];
$status_message = [];
$filtered_inputs = [];
$redirect_params = [];
$filter_out_inputs = ['form_response'];
$database_data = null;

// Process the form post inputs
// -----------------------------------
$form_params = filter_input(INPUT_POST, 'form_params', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
$google_recaptcha = filter_input(INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING);

$form_params_filter = [
    'email_address' => FILTER_VALIDATE_EMAIL,
    'first_name' => FILTER_SANITIZE_STRING,
    'phone' => FILTER_SANITIZE_STRING,
    'form_response' => FILTER_SANITIZE_STRING,
];
$form_params_filter_options = [
    'email_address' => [
        'flags' => FILTER_NULL_ON_FAILURE
    ],
    'first_name' => [
        'flags' => FILTER_FLAG_STRIP_HIGH
    ],
    'phone' => [
        'flags' => FILTER_FLAG_STRIP_HIGH
    ],
    'form_response' => [
        'flags' => FILTER_FLAG_STRIP_HIGH
    ]
];

foreach ($form_params as $form_params_keys => $form_params_values) {
    $filtered_inputs[$form_params_keys] = filter_var($form_params_values, $form_params_filter[$form_params_keys], $form_params_filter_options[$form_params_keys]);
}

//  Frontend validation - second wave
// -----------------------------------
if (empty($config_setting_array['google_recaptcha'])) {
    $error_message = parse_error(["g_recaptcha" => "Internal configuration error. Please contact support."]);
}

if (empty($google_recaptcha)) {
    $error_message = parse_error(["g_recaptcha" => "Please complete the recaptcha."]);
}

if (empty($config_setting['google_recaptcha'])) {
    $error_message = parse_error(["g_recaptcha" => "Recaptcha key is not set."]);
}

if (empty($config_setting['redirect_url'])) {
    $error_message = parse_error(["internal_error" => "Redirect is not a valid url."]);
}

if (empty($ip_address)) {
    $error_message = parse_error(["internal_error" => "Internal form error. Please contact support."]);
}

if (empty($filtered_inputs['form_response']) === false) {
    $error_message = parse_error(["internal_error" => "Internal form error. Please contact support."]);
}

if (empty($filtered_inputs['email_address'])) {
    $error_message = parse_error(["email_address" => "Email address is invalid."]);
}

if (empty($filtered_inputs['first_name']) && $filtered_inputs['required_params'] === 'first_name') {
    $error_message = parse_error(["first_name" => "This field is required."]);
}

if (empty($filtered_inputs['phone']) && $filtered_inputs['required_params'] === 'phone') {
    $error_message = parse_error(["phone" => "This field is required."]);
}

//  Vendor validation - third wave
// -----------------------------------
if (!empty($config_setting_array['google_recaptcha'])) {
    $google_recaptcha_status = google_recaptcha_verification(
        $config_setting['google_recaptcha']->secret_key,
        $google_recaptcha,
        $ip_address
    );
    if ($google_recaptcha_status !== true) {
        $error_message = $google_recaptcha_status;
    }
}

// Parse redirect Params
// -----------------------------------
if (!empty($config_setting['redirect_params'])) {
    foreach ($config_setting['redirect_params']->params as $field_name) {
        if ($filtered_inputs[$field_name] === $field_name) {
            $redirect_params = $filtered_inputs[$field_name];
        }
    }
}

// Make a call to server side script if data is valid else show error
// -----------------------------------
if (empty($error_message)) {
    $status_message = parse_success_redirect(
        ["success" => "ready"],
        $config_setting['redirect_url']->url, $redirect_params
    );
    $database_data = parse_all_data_into_json($filtered_inputs, $filter_out_inputs);
    $database_submission->record_form_data($ip_address, $database_data);
} else {
    $status_message = $error_message;
}

// Send callback
// -----------------------------------
header("content-type: text/javascript; charset=utf-8");
exit(json_encode($status_message));