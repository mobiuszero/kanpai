<?php
/**
 * Returns URL of the current page where the function is called.
 * @return string returns current page URL
 */
function current_page_url()
{
    $pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {
        $pageURL .= "s";
    }
    $pageURL .= "://";

    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

/**
 * Parser for errors messages
 * @param  array $error_response_array
 * @return array
 */
function parse_error($error_response_array)
{
    $status_message = [];
    foreach ($error_response_array as $error_response_array_key => $error_response_array_value) {
        $status_message = array(
            "status" => false,
            "field" => $error_response_array_key,
            "message" => $error_response_array_value
        );
    }
    return $status_message;
}

/**
 * Parser for success messages
 * @param  array $success_response_array
 * @return array for callbacks
 */
function parse_success($success_response_array)
{
    $status_message = [];
    foreach ($success_response_array as $success_response_array_key => $success_response_array_value) {
        $status_message = [
            "status" => true,
            "field" => $success_response_array_key,
            "message" => $success_response_array_value
        ];
    }
    return $status_message;
}

/**
 * Parser to send data to the database
 * @param  array $success_response_array
 * @return array
 */
function parse_success_form_data($success_response_array)
{
    $status_message = [];
    foreach ($success_response_array as $success_response_array_key => $success_response_array_value) {
        $status_message = [
            "status" => true,
            "field" => $success_response_array_key,
            "data" => $success_response_array_value
        ];
    }
    return $status_message;
}

/**
 * Parser for success messages with redirect and parameters
 * @param  array $success_response_array
 * @param  string $redirect
 * @param  array $redirect_params
 * @return array
 */
function parse_success_redirect($success_response_array, $redirect, $redirect_params = null)
{
    $parsed_redirect = [];
    if (!empty($redirect_params = urldecode(http_build_query($redirect_params)))) {
        foreach ($success_response_array as $success_response_array_key => $success_response_array_value) {
            $parsed_redirect = [
                "status" => true,
                "field" => $success_response_array_key,
                "message" => $success_response_array_value,
                "params" => $redirect_params,
                "redirect" => $redirect
            ];
        }
    } else {
        foreach ($success_response_array as $success_response_array_key => $success_response_array_value) {
            $parsed_redirect = [
                "status" => true,
                "field" => $success_response_array_key,
                "message" => $success_response_array_value,
                "params" => ["success" => "true"],
                "redirect" => $redirect
            ];
        }
    }

    return $parsed_redirect;
}

/**
 * output datetime
 * @param $type string
 * @param null $format
 * @param $daterelativeformat
 * @return string the formatted datetime
 * @example $set_date->setTimestamp(strtotime("now")) "+5 minute"; $today_date = $set_date->format('Y-m-d H:i:s');
 */
function output_datetime($type = null, $format = null, $daterelativeformat)
{
    $return_date = null;
    /* Create dateTime object $dateformat*/
    $set_date = new DateTime();
    /* Create dateTime object for expiration date */
    $set_date->setTimestamp(strtotime($daterelativeformat));
    if ($type != "unix") {
        if ($format == "dayMonDateSuffix") {
            $return_date = $set_date->format('l, F dS');
        } elseif ($format == "monDayYearTime") {
            $return_date = $set_date->format('F d, Y H:i:s');
        } elseif ($format == "timeMerTZ") {
            $return_date = $set_date->format('g A') . " EASTERN TIME";
        } elseif ($format == "std") {
            $return_date = $set_date->format('Y-d-m H:i:s');
        }
    } elseif ($type === "unix") {
        $return_date = $set_date->format('U');
    }
    return $return_date;
}

/**
 * Gets the user's IP Address v4
 * @return string IPV4
 */
function get_ip_address()
{
    //Just get the headers if we can or else use the SERVER global
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
    } else {
        $headers = $_SERVER;
    }

    if (array_key_exists('HTTP_CLIENT_IP', $headers) && filter_var($headers['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $the_ip = $headers['HTTP_CLIENT_IP'];
    } elseif (array_key_exists('HTTP_X_FORWARDED', $headers) && filter_var($headers['HTTP_X_FORWARDED'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $the_ip = $headers['HTTP_X_FORWARDED'];
    } elseif (array_key_exists('X-Forwarded-For', $headers) && filter_var($headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $the_ip = $headers['X-Forwarded-For'];
    } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $headers) && filter_var($headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $the_ip = $headers['HTTP_X_FORWARDED_FOR'];
    } else {
        $the_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }
    return $the_ip;
}

/**
 * This will parse all the form data to be inserted into the database
 * @param $filtered_data
 * @param null $filter_out
 * @return string json
 */
function parse_all_data_into_json($filtered_data, $filter_out = null)
{
    $database_data = [];
    // Remove the unneeded vars from the input array.
    if (!empty($filter_out)) {
        foreach ($filter_out as $filter_out_key) {
            unset($filtered_data[$filter_out_key]);
        }
    }

    // Reconstruct the array to be used in the database.
    foreach ($filtered_data as $input_keys => $input_values) {
        $database_data['user_form_data'][$input_keys] = $input_values;
    }

    // Get the user geo data and store it in the database_data array as user_geo_data.
    $database_data['user_geo_data'] = get_user_geo_data();

    // Return all the data in json
    return json_encode($database_data);
}
