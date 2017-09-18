<?php
/**
 * Created by PhpStorm.
 */

namespace ZenApp;

class ZenAppParsingFunctions
{

    private $parsed_callback;

    /**
     * Parser for errors messages
     * @param  array $error_response_array
     * @return array
     */
    public function parse_error($error_response_array)
    {
        foreach ($error_response_array as $error_response_array_key => $error_response_array_value) {
            $this->parsed_callback = array(
                "status" => false,
                "field" => $error_response_array_key,
                "message" => $error_response_array_value
            );
        }
        return $this->parsed_callback;
    }

    /**
     * Parser for success messages with redirect and parameters
     * @param  array $success_response_array
     * @param  string $redirect
     * @param  array $redirect_params
     * @return array
     */
    public function parse_success_redirect($success_response_array, $redirect, $redirect_params = null)
    {
        if (!empty($redirect_params)) {
            foreach ($success_response_array as $success_response_array_key => $success_response_array_value) {
                $this->parsed_callback = [
                    "status" => true,
                    "field" => $success_response_array_key,
                    "message" => $success_response_array_value,
                    "params" => urldecode(http_build_query($redirect_params)),
                    "redirect" => $redirect
                ];
            }
        } else {
            foreach ($success_response_array as $success_response_array_key => $success_response_array_value) {
                $this->parsed_callback = [
                    "status" => true,
                    "field" => $success_response_array_key,
                    "message" => $success_response_array_value,
                    "params" => ["success" => "true"],
                    "redirect" => $redirect
                ];
            }
        }

        return $this->parsed_callback;
    }

    /**
     * Parser to send data to the database
     * @param  array $success_response_array
     * @return array
     */
    public function parse_success_form_data($success_response_array)
    {
        foreach ($success_response_array as $success_response_array_key => $success_response_array_value) {
            $this->parsed_callback = [
                "status" => true,
                "field" => $success_response_array_key,
                "data" => $success_response_array_value
            ];
        }
        return $this->parsed_callback;
    }

    /**
     * Parser for success messages
     * @param  array $success_response_array
     * @return array for callbacks
     */
    public function parse_success($success_response_array)
    {
        foreach ($success_response_array as $success_response_array_key => $success_response_array_value) {
            $this->parsed_callback = [
                "status" => true,
                "field" => $success_response_array_key,
                "message" => $success_response_array_value
            ];
        }
        return $this->parsed_callback;
    }

    /**
     * This will parse all the form data to be inserted into the database
     * @param array $filtered_data
     * @param string $geo_data
     * @param array|null $filter_out
     * @return string json
     */
    public function parse_all_data_into_json($filtered_data, $geo_data, $filter_out = null)
    {
        // Remove the unneeded vars from the input array.
        if (!empty($filter_out)) {
            foreach ($filter_out as $filter_out_key) {
                unset($filtered_data[$filter_out_key]);
            }
        }

        // Reconstruct the array to be used in the database.
        foreach ($filtered_data as $input_keys => $input_values) {
            $this->parsed_callback['user_form_data'][$input_keys] = $input_values;
        }

        // Get the user geo data and store it in the database_data array as user_geo_data.
        $this->parsed_callback['user_geo_data'] = json_decode($geo_data, true);

        // Return all the data in json
        return json_encode($this->parsed_callback);
    }

}