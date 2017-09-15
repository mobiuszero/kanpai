<?php
error_reporting(E_ALL & ~E_NOTICE);
// Load assets
// -----------------------------------
require_once 'vendor/autoload.php';
require_once 'functions/commons.php';
require_once 'functions/vendor.php';

// Variables
// -----------------------------------
$error_message = [];
$status_message = [];
$filtered_inputs = [];
$redirect_params = [];
$configuration = [];
$filter_out_inputs = ['form_response'];

// Make call to the database
// -----------------------------------
$database_connection = (new ZenApp\ZenAppConnection())->connect();

// Call settings
// -----------------------------------
$retrieve_settings = new ZenApp\ZenAppSettings($database_connection);
$retrieve_settings->settings_request('read', ['name' => ['google_recaptcha', 'redirect_url', 'redirect_params']]);
foreach ($retrieve_settings->database_callback as $settings) {
    if ($settings['config_name'] === "google_recaptcha") {
        $configuration['google_recaptcha'] = json_decode($settings['config_settings']);
    } elseif ($settings['config_name'] === "redirect_url") {
        $configuration['redirect_url'] = json_decode($settings['config_settings']);
    } elseif ($settings['config_name'] === "redirect_params") {
        $configuration['redirect_params'] = json_decode($settings['config_settings']);
    }
}

// Call submission actions
// -----------------------------------
$form_submission_actions = new ZenApp\ZenAppSubmissionActions($database_connection);

// Get the user's IP address
// -----------------------------------
$user_ip_address = get_ip_address();

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
if (empty($google_recaptcha)) {
    $error_message = parse_error(["g_recaptcha" => "Please complete the recaptcha."]);
}

if (empty($ip_address)) {
    $error_message = parse_error(["internal_error" => "Please contact support. Validation error - IP"]);
}

if (empty($filtered_inputs['form_response']) === false) {
    $error_message = parse_error(["internal_error" => "Please contact support. Validation error - Form"]);
}

if (empty($filtered_inputs['email_address'])) {
    $error_message = parse_error(["email_address" => "Email address is invalid."]);
}

//  Vendor validation - third wave
// -----------------------------------
if (!empty($config_setting_array['google_recaptcha'])) {
    $google_recaptcha_status = google_recaptcha_verification(
        $configuration['google_recaptcha']->secret_key,
        $google_recaptcha,
        $ip_address
    );
    if ($google_recaptcha_status !== true) {
        $error_message = $google_recaptcha_status;
    }
}


// If the redirect parameters were set in the settings than parse them
// -----------------------------------
if (!empty($settings['redirect_params'])) {
    foreach ($settings['redirect_params']->params as $field_name) {
        if ($filtered_inputs[$field_name] === $field_name) {
            $redirect_params = $filtered_inputs[$field_name];
        }
    }
}

// Make a call to server side script if data is valid else show error
// -----------------------------------
$database_data = parse_all_data_into_json($filtered_inputs, $filter_out_inputs);
$form_submission_actions->record_form_data($user_ip_address, $database_data);

if (empty($error_message)) {
    $status_message = parse_success_redirect(
        ["success" => "ready"],
        $configuration['redirect_url']->url, $redirect_params
    );
} else {
    $status_message = $error_message;
}

// Send callback
// -----------------------------------
header("content-type: text/javascript; charset=utf-8");
exit(json_encode($status_message));