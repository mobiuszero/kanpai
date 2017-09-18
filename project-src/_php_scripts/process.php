<?php
error_reporting(E_ALL & ~E_NOTICE);
// Load assets
// -----------------------------------
require_once 'vendor/autoload.php';

// Variables
// -----------------------------------
$error_message = [];
$status_message = [];
$configurations = ['google_recaptcha', 'redirect_url', 'redirect_params', 'filter_redirect_params', 'required_fields'];

// Make call to the database
// -----------------------------------
$db_connection = ( new ZenApp\ZenAppConnection() )->connect();
$db_submission = new ZenApp\ZenAppSubmissionActions($db_connection);
$default_settings = new ZenApp\ZenAppSettings($db_connection);

// Get the user's IP address
// -----------------------------------
$ip_address = ( new ZenApp\ZenAppUtilityFunctions() )->get_ip_address();

// Initialize callback && vendor functions
// -----------------------------------
$callback_function = new ZenApp\ZenAppParsingFunctions();
$vendor_function = new ZenApp\ZenAppVendorCalls();

// Call settings
// -----------------------------------
$default_settings->settings_request('read', ['name' => $configurations]);
foreach ($default_settings->database_callback as $settings) {
    foreach ($configurations as $config_name) {
        if ($settings['config_name'] === $config_name) {
            $configurations[$config_name] = json_decode($settings['config_settings']);
        }
    }
}

// Process the form post inputs
// -----------------------------------
$form_params = filter_input(INPUT_POST, 'form_params', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
$google_recaptcha = filter_input(INPUT_POST, 'g-recaptcha-response', FILTER_SANITIZE_STRING);

$input_filter = [
    'first_name' => FILTER_SANITIZE_STRING,
    'last_name' => FILTER_SANITIZE_STRING,
    'email_address' => FILTER_VALIDATE_EMAIL,
    'phone' => FILTER_SANITIZE_STRING,
    'form_response' => FILTER_SANITIZE_STRING,
];
$input_filter_options = [
    'first_name' => [
        'flags' => FILTER_FLAG_STRIP_HIGH
    ],
    'last_name' => [
        'flags' => FILTER_FLAG_STRIP_HIGH
    ],
    'email_address' => [
        'flags' => FILTER_NULL_ON_FAILURE
    ],
    'phone' => [
        'flags' => FILTER_FLAG_STRIP_HIGH
    ],
    'form_response' => [
        'flags' => FILTER_FLAG_STRIP_HIGH
    ]
];

foreach ($form_params as $params_keys => $params_values) {
    $form_params[$params_keys] = filter_var($params_values, $input_filter[$params_keys], $input_filter_options[$params_values]);
}

//  Frontend validation - second wave
// -----------------------------------
if (!empty($configurations['required_fields'])) {
    foreach ($configurations['required_fields'] as $required_field) {
        if (empty($required_field)) {
            $error_message = $callback_function->parse_error([$required_field => str_replace('_', ' ', ucfirst($required_field)) . " is required."]);
        }
    }
}

if (!empty($form_params['form_response'])) {
    $error_message = $callback_function->parse_error(["internal_error" => "Please contact support. Validation error - Form."]);
}

if (empty($form_params['email_address'])) {
    $error_message = $callback_function->parse_error(["email_address" => "Email address is invalid."]);
}

//  Vendor validation - third wave
// -----------------------------------
if (!empty($configurations['google_recaptcha']) && empty($google_recaptcha)) {
    $error_message = $callback_function->parse_error(["g_recaptcha" => "Please complete the recaptcha."]);
}

// Make a call to server side script if data is valid else show error
// -----------------------------------
if (empty($error_message)) {
    // Database submission
    $db_submission->record_form_data($ip_address, $callback_function->parse_all_data_into_json($form_params, $vendor_function->freegeoip()));
    // Redirect processing
    $status_message = $callback_function->parse_success_redirect(['success' => 'deploy'], $configurations['redirect_url']->url, $configurations['redirect_params']);
} else {
    $status_message = $error_message;
}

// Send callback
// -----------------------------------
header("content-type: text/javascript; charset=utf-8");
exit(json_encode($status_message));