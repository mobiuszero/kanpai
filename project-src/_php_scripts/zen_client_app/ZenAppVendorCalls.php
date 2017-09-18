<?php
/**
 * Make vendor api calls
 */

namespace ZenApp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Recaptcha\ReCaptcha;

class ZenAppVendorCalls
{

    private $api_callback, $parse_function, $utility_function;

    /**
     * Makes request to get user geo data from https://freegeoip.net/
     * NOTE: There is a query limit per request made which is 15,000 queries per hour by default going beyond this limit produce a http 403 message till the hour is up
     * @return string
     */
    public function freegeoip()
    {
        try {
            $free_geo_ip = new Client(['base_uri' => 'https://freegeoip.net/']);
            $response = $free_geo_ip->request('GET', 'json/',
                [
                    'synchronous' => true,
                    'timeout' => 120,
                    'allow_redirects' => false,
                    'delay' => 250
                ]
            );
            $this->api_callback = $response->getBody()->getContents();

        } catch (RequestException $exception) {
            $this->api_callback = json_encode($this->parse_response()->parse_error([
                'internal_error' => 'freegeoip Error: ' . 'request = ' . Psr7\str($exception->getRequest()) . ' request_response = ' . ( $exception->hasResponse() ? Psr7\str($exception->getResponse()) : 'No response returned' )
            ]));
        }
        return $this->api_callback;
    }

    /**
     * Makes calls to the commons methods
     * @return \ZenApp\ZenAppParsingFunctions
     */
    private function parse_response()
    {
        $this->parse_function = new ZenAppParsingFunctions();
        return $this->parse_function;
    }

    /**
     * Makes request to get user geo data from geoplugin.net
     * NOTE: Keep in mind that this API has a limit of 120 calls/minute
     * @return string callback from GeoPlugin | exception
     * @see if you need ssl goto http://www.geoplugin.com/premium#ssl_access_per_year
     */
    public function geoPlugin()
    {
        try {
            $geo_plugin = new Client(['base_uri' => 'http://www.geoplugin.net/json.gp']);
            $response = $geo_plugin->request('GET', '',
                [
                    'synchronous' => true,
                    'timeout' => 120,
                    'allow_redirects' => false
                ]
            );
            $this->api_callback = $response->getBody()->getContents();
            $this->api_callback = str_replace('geoplugin_', '', $this->api_callback);
            $this->api_callback = json_decode($this->api_callback, true);

            foreach ($this->api_callback as $geo_plugin_name => $geo_plugin_value) {
                if ($geo_plugin_name === 'credit' || $geo_plugin_name === 'status') {
                    unset($this->api_callback[$geo_plugin_name]);
                }
            }

            $this->api_callback = json_encode($this->api_callback);

        } catch (RequestException $exception) {
            $this->api_callback = json_encode($this->parse_response()->parse_error([
                'internal_error' => 'GeoPlugin Error: ' . 'request = ' . Psr7\str($exception->getRequest()) . ' request_response = ' . ( $exception->hasResponse() ? Psr7\str($exception->getResponse()) : 'No response returned' )
            ]));
        }
        return $this->api_callback;
    }

    /**
     * Makes request to get user geo data from ipinfodb.com
     * NOTE: There is a query limit per request made which is 2/second anything above this and the api will create a queue
     * @param string $ipInfoDB_api_key
     * @return string
     * @throws \Exception
     * @see http://www.ipinfodb.com/register.php to create account to get api key
     * @see http://www.ipinfodb.com/ip_location_api.php to learn more about the api
     */
    public function ipInfoDB($ipInfoDB_api_key)
    {
        // Make sure the api key is not null
        if (empty($ipInfoDB_api_key)) {
            throw new \Exception('Need API key');
        }

        try {
            $ipInfoDB = new Client(['base_uri' => 'https://api.ipinfodb.com/']);
            $response = $ipInfoDB->request('GET', 'v3/ip-city/',
                [
                    'synchronous' => true,
                    'timeout' => 120,
                    'allow_redirects' => false,
                    'delay' => 250,
                    'query' => [
                        'key' => $ipInfoDB_api_key,
                        'format' => 'json'
                    ]
                ]
            );
            $this->api_callback = $response->getBody()->getContents();
            $invalid_response = json_decode($this->api_callback);
            $this->api_callback = ( $invalid_response->statusCode === "ERROR" ? $invalid_response->statusMessage : $this->api_callback );

        } catch (RequestException $exception) {
            $this->api_callback = json_encode($this->parse_response()->parse_error([
                'internal_error' => 'ipInfoDB Error: ' . 'request = ' . Psr7\str($exception->getRequest()) . ' request_response = ' . ( $exception->hasResponse() ? Psr7\str($exception->getResponse()) : 'No response returned' )
            ]));
        }
        return $this->api_callback;
    }

    /**
     * Makes request to get user geo data from https://tools.keycdn.com/
     * NOTE: There is a query limit per request made which is 1/second anything above this and the api will create a queue
     * @param null $ip_address
     * @return string
     */
    public function keycdn_IP_geolocation($ip_address = null)
    {
        try {
            $ip_address = ( empty($ip_address) ? $this->utility()->get_ip_address() : $ip_address );
            $ipInfoDB = new Client(['base_uri' => 'https://tools.keycdn.com/']);
            $response = $ipInfoDB->request('GET', 'geo.json/',
                [
                    'synchronous' => true,
                    'timeout' => 120,
                    'allow_redirects' => true,
                    'delay' => 250,
                    'query' => [
                        'host' => $ip_address
                    ]
                ]
            );
            $this->api_callback = $response->getBody()->getContents();

        } catch (RequestException $exception) {
            $this->api_callback = json_encode($this->parse_response()->parse_error([
                'internal_error' => 'keycdn_IP geolocation Error: ' . 'request = ' . Psr7\str($exception->getRequest()) . ' request_response = ' . ( $exception->hasResponse() ? Psr7\str($exception->getResponse()) : 'No response returned' )
            ]));
        }
        return $this->api_callback;
    }

    private function utility()
    {
        $this->utility_function = new ZenAppUtilityFunctions();
        return $this->utility_function;
    }

    /**
     * Google recaptcha verification call
     * @param  string $google_recaptcha_secret_key
     * @param  string $client_response the data that needs to be verified.
     * @param  string $user_ip_address user ip address to track in google recaptcha admin console.
     * @return bool|array true if data is valid else will return error message and field that has produce error.
     */
    public function google_recaptcha_verification($google_recaptcha_secret_key, $client_response, $user_ip_address)
    {
        $recaptcha = new ReCaptcha($google_recaptcha_secret_key);
        $recaptcha_callback = $recaptcha->verify($client_response, $user_ip_address);
        if ($recaptcha_callback->isSuccess()) {
            $this->api_callback = true;
        } else {
            $this->api_callback = $this->parse_response()->parse_error(['internal_error' => 'Recaptcha error: ' . $recaptcha_callback->getErrorCodes()]);
        }
        return $this->api_callback;
    }
}