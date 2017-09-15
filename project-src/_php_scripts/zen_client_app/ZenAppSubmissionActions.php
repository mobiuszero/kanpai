<?php
/**
 * ZenApp submission actions
 */

namespace ZenApp;

// For the random string
use Rych\Random\Encoder;
use Rych\Random\Random;

class ZenAppSubmissionActions
{
    /**
     * status message
     * @var array|string $status_message
     */
    public $status_message;
    /**
     * For the constructor
     * @var object $pdo
     */
    private $pdo;
    /**
     * For the random string
     * @var string $random_submission_id
     * @var string $random_submission_key
     */
    private $random_submission_id, $random_submission_key;
    /**
     * For retrieval
     * @var string $ip_address
     * @var string $ip_address_key
     * @var string $page_view_id
     */
    private $submission_id, $submission_key, $submission_count, $user_form_data, $user_geo_data, $ip_address_key;

    /**
     * Initialize the object with a specified PDO object
     * @param \PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Makes calls to the database
     * @param $ip_address
     * @param $form_submission_data
     */
    public function record_form_data($ip_address, $form_submission_data)
    {
        try {
            // Data retrieval
            $this->check_for_ip_address_key($ip_address);
            $this->json_submission_data($form_submission_data);
            $this->get_submission_data_return_id_key_counter($ip_address);

        } catch (\Exception $error) {
            $this->status_message = ['status' => 'fail', 'code' => false, 'message' => $error->getMessage()];
        }

        try {
            // Create new entry
            if (empty($this->submission_id) && empty($this->submission_key)) {
                $this->random_string();
                $this->create_submission_entry($ip_address);
                $this->status_message = ['status' => 'success', 'message' => 'new', 'id' => $this->random_submission_id, 'data' => ['form_data' => $this->user_form_data, 'location' => $this->user_geo_data]];
            }

            // Update entry
            if (!empty($this->submission_id) && !empty($this->submission_key)) {
                $this->update_submission_entry($ip_address);
                $this->status_message = ['status' => 'success', 'message' => 'update', 'id' => $this->submission_id, 'data' => ['form_data' => $this->user_form_data, 'location' => $this->user_geo_data]];
            }
        } catch (\Exception $error) {
            $this->status_message = ['status' => 'fail', 'code' => false, 'message' => $error->getMessage()];
        }

    }

    /**
     * Get the ip address key
     * @param $ip_address
     * @throws \Exception
     */
    private function check_for_ip_address_key($ip_address)
    {
        if (!empty($ip_address)) {
            $sql = "SELECT ip_key FROM formPageViews WHERE ip = :ip_address";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':ip_address', $ip_address, \PDO::PARAM_STR);
            $stmt->execute();
            $ip_address_key = $stmt->fetch(\PDO::FETCH_OBJ);
            $this->ip_address_key = $ip_address_key->ip_key;
        } else {
            throw new \Exception('Ip address key call: ip address is missing');
        }
    }

    /**
     * Turn the form data into json
     * @param $form_submission_data
     * @throws \Exception
     */
    private function json_submission_data($form_submission_data)
    {
        if (!empty($form_submission_data)) {
            $parse_data = json_decode($form_submission_data);
            $this->user_form_data = json_encode($parse_data->user_form_data);
            $this->user_geo_data = json_encode($parse_data->user_geo_data);
        } else {
            throw new \Exception('Form submission data is missing');
        }
    }

    /**
     * Get the submission id
     * @param $ip_address
     * @throws \Exception
     */
    private function get_submission_data_return_id_key_counter($ip_address)
    {
        if (!empty($ip_address) && !empty($this->ip_address_key)) {
            $sql = "SELECT submission_id,submission_key,submission_count FROM formSubmissionData WHERE ip = :ip_address AND ip_key = :ip_address_key";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':ip_address', $ip_address, \PDO::PARAM_STR);
            $stmt->bindParam(':ip_address_key', $this->ip_address_key, \PDO::PARAM_STR);
            $stmt->execute();
            $submission_id = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->submission_id = $submission_id['submission_id'];
            $this->submission_key = $submission_id['submission_key'];
            $this->submission_count = $submission_id['submission_count'];
        } else {
            throw new \Exception('Submission id call: ip address and/or ip address key is missing');
        }
    }

    /**
     * Generate random secured string
     * @param int $number_of_random_chars
     * @return string
     * @throws \Exception
     */
    private function random_string($number_of_random_chars = 15)
    {
        $random_id_string = null;
        if ($number_of_random_chars > 5) {
            $hex_bytes = new Encoder\HexEncoder();
            $random = new Random();
            $random->setEncoder($hex_bytes);
            $this->random_submission_key = $random->getRandomBytes($number_of_random_chars);
            $this->random_submission_id = implode('-', str_split($random->getRandomBytes($number_of_random_chars), 5));

        } else {
            throw new \Exception('Character count is less than 5');
        }
        return $random_id_string;
    }

    /**
     * Create submission entry in the database
     * @param $ip_address
     * @throws \Exception
     * @internal param $user_form_data
     * @internal param $user_geo_data
     * @internal param $ip_address_key
     * @internal param array $form_submission_data
     */
    private function create_submission_entry($ip_address)
    {
        if (!empty($this->ip_address_key) && !empty($ip_address)) {
            // Database INSERT
            $sql = "INSERT INTO formSubmissionData (submission_id,submission_key,ip,ip_key,submission_form_data,submission_user_geo_data,submission_count) VALUES (:submission_id,:submission_key,:ip,:ip_key,:submission_form_data,:submission_user_geo_data,:submission_count)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':submission_id', $this->random_submission_id, \PDO::PARAM_STR);
            $stmt->bindParam(':submission_key', $this->random_submission_key, \PDO::PARAM_STR);
            $stmt->bindParam(':submission_form_data', $this->user_form_data, \PDO::PARAM_STR);
            $stmt->bindParam(':submission_user_geo_data', $this->user_geo_data, \PDO::PARAM_STR);
            $stmt->bindParam(':ip', $ip_address, \PDO::PARAM_STR);
            $stmt->bindParam(':ip_key', $this->ip_address_key, \PDO::PARAM_STR);
            $stmt->bindValue(':submission_count', 1, \PDO::PARAM_INT);
            $stmt->execute();
        } else {
            throw new \Exception('create submission call: data is missing');
        }
    }

    /**
     * Update a submission entry in the database
     * @param $ip_address
     * @throws \Exception
     * @internal param $user_form_data
     * @internal param $user_geo_data
     */
    private function update_submission_entry($ip_address)
    {
        if (!empty($ip_address)) {
            // Database UPDATE
            $sql = "UPDATE formSubmissionData SET submission_count = submission_count + 1, submission_form_data = :user_form_data, submission_user_geo_data = :user_geo_data WHERE ip = :ip_address AND ip_key = :ip_address_key AND submission_id = :submission_id AND submission_key = :submission_key";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':user_form_data', $this->user_form_data, \PDO::PARAM_STR);
            $stmt->bindParam(':user_geo_data', $this->user_geo_data, \PDO::PARAM_STR);
            $stmt->bindParam(':ip_address', $ip_address, \PDO::PARAM_STR);
            $stmt->bindParam(':ip_address_key', $this->ip_address_key, \PDO::PARAM_STR);
            $stmt->bindParam(':submission_id', $this->submission_id, \PDO::PARAM_STR);
            $stmt->bindParam(':submission_key', $this->submission_key, \PDO::PARAM_STR);
            $stmt->execute();
        } else {
            throw new \Exception('update entry call: ip address is missing');
        }
    }

    /**
     * Closes the connections
     */
    public function __destruct()
    {
        $this->pdo = null;
    }
}