<?php
/**
 * Makes request to get user geo data from freegeoip.net
 * if the request has a status code of 403 than will make a request with geoplugin.net
 * @param $ip_address
 * @return string
 */

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

function get_user_geo_data()
{
    $result = null;
    $status_code = null;
    $client = new GuzzleHttp\Client();
    try {
        $response = $client->request('GET', 'https://freegeoip.net/json/', [
            'synchronous' => true,
            'timeout' => 120,
            'allow_redirects' => false
        ]);
        $status_code = $response->getStatusCode();
        $result = (($status_code == "200") ? json_decode($response->getBody()->getContents(), true) : get_user_geo_data_via_geoplugin());

    } catch (RequestException $exception) {
        $result["request_exception"] = Psr7\str($exception->getRequest());
        if ($exception->hasResponse()) {
            $result["response"] = Psr7\str($exception->getResponse());
        }
        $result = json_encode($result);
    }
    return $result;
}

/**
 * Makes request to get user geo data from geoplugin.net
 * @param $ip_address
 * @return string
 */
function get_user_geo_data_via_geoplugin()
{
    $result = null;
    $client = new GuzzleHttp\Client();
    try {
        $response = $client->request('GET', 'http://www.geoplugin.net/json.gp', [
            'synchronous' => true,
            'timeout' => 120,
            'allow_redirects' => false
        ]);
        $parse_geo_plugin_data = json_decode($response->getBody()->getContents(), true);
        foreach ($parse_geo_plugin_data as $geo_plugin_data_key => $geo_plugin_data_value) {
            if ($geo_plugin_data_key === 'geoplugin_credit') {
                unset($parse_geo_plugin_data[$geo_plugin_data_key]);
            } else {
                $parse_geo_plugin_data[$geo_plugin_data_key] = $geo_plugin_data_value;
            }
        }
        $result = $parse_geo_plugin_data;
    } catch (RequestException $exception) {
        $result["request_exception"] = Psr7\str($exception->getRequest());
        if ($exception->hasResponse()) {
            $result["response"] = Psr7\str($exception->getResponse());
        }
        $result = json_encode($result);
    }

    return $result;
}

/**
 * Google recaptcha verification call
 * @param  string $google_recaptcha_secret_key
 * @param  string $client_response the data that needs to be verified.
 * @param  string $user_ip_address user ip address to track in google recaptcha admin console.
 * @return bool|array true if data is valid else will return error message and field that has produce error.
 */
function google_recaptcha_verification($google_recaptcha_secret_key, $client_response, $user_ip_address)
{
    $secret_key = new \ReCaptcha\ReCaptcha($google_recaptcha_secret_key);
    $google_recaptcha_verification = new \GuzzleHttp\Client();
    $google_response = null;
    $google_status = null;
    $google_callback = $google_recaptcha_verification->post("https://www.google.com/recaptcha/api/siteverify", [
        'query' => [
            'secret' => $secret_key,
            'response' => $client_response,
            'remoteip' => $user_ip_address
        ]
    ]);
    $json_google_callback = $google_callback->getBody()->getContents();
    $google_response = json_decode($json_google_callback, $google_response);
    $google_status = $google_response->success;

    if ($google_status == "false" || $google_status === false) {
        return parse_error(["google_error" => "Recaptcha encountered an error. Please contact support."]);
    } else {
        return true;
    }
}