<?php
/**
 * ZenApp page pixel
 */

namespace ZenApp;

// For the random string
use Rych\Random\Encoder;
use Rych\Random\Random;

class ZenAppPagePixel
{

    /**
     * status message
     * @var array $status_message
     */
    public $status_message;
    /**
     * For the constructor
     * @var object $pdo
     */
    private $pdo;
    /**
     * For the random string
     * @var string $random_ip_address_key
     * @var string $random_page_view_id
     */
    private $random_ip_address_key, $random_page_view_id;
    /**
     * For retrieval
     * @var string $ip_address
     * @var string $ip_address_key
     * @var string $page_view_id
     */
    private $ip_address, $ip_address_key, $page_view_id, $ip_address_hit;

    /**
     * Initialize the object and get the ip address
     * @param \PDO $pdo
     * @param $ip_address
     */
    public function __construct($pdo, $ip_address)
    {
        $this->pdo = $pdo;
        try {
            $this->sanitize_ip_address($ip_address);
        } catch (\Exception $error) {
            exit($error->getMessage());
        }
    }

    /**
     * Checks to make sure IP address passes basic validation and saves the ip address property
     * @param $ip_address
     * @throws \Exception
     */
    private function sanitize_ip_address($ip_address)
    {
        if (!empty($ip_address) && filter_var($ip_address, FILTER_VALIDATE_IP) !== false) {
            $this->ip_address = filter_var($ip_address, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
        } else {
            throw new \Exception('ip address check: ip address is invalid.');
        }
    }

    /**
     * ip_address_key_pixel
     */
    public function ip_address_key_pixel()
    {
        try {
            $this->random_string();
            $this->find_address_key_return_key_hit_and_id();
        } catch (\Exception $error) {
            exit(['status' => 'fail', 'message' => $error->getMessage()]);
        }

        if (empty($this->page_view_id) && empty($this->ip_address_key)) {
            $this->create_ip_address_entry();
            $this->status_message = [
                'status' => 'success',
                'message' => 'new entry',
                'id' => $this->random_page_view_id
            ];
        }

        if (!empty($this->page_view_id) && !empty($this->ip_address_key && $this->ip_address_hit % 50 > 0)) {
            $this->update_ip_address_entry_hit();
            $this->status_message = [
                'status' => 'success',
                'message' => 'update entry hit',
                'id' => $this->page_view_id
            ];
        }

        if (!empty($this->page_view_id) && !empty($this->ip_address_key) && $this->ip_address_hit % 50 === 0) {
            $this->update_ip_address_entry_key_hit();
            $this->status_message = [
                'status' => 'success',
                'message' => 'update entry key',
                'id' => $this->page_view_id
            ];
        }
    }

    /**
     * Generate random secured string
     * @param int $number_of_random_chars
     * @throws \Exception if the characters goes below 5 characters
     */
    private function random_string($number_of_random_chars = 15)
    {
        if ($number_of_random_chars > 5) {
            $hex_bytes = new Encoder\HexEncoder();
            $random = new Random();
            $random->setEncoder($hex_bytes);
            $this->random_ip_address_key = $random->getRandomBytes($number_of_random_chars);
            $this->random_page_view_id = implode('-', str_split($random->getRandomBytes($number_of_random_chars), 5));
        } else {
            throw new \Exception('Character count is less than 5');
        }
    }

    /**
     * find_address_key_return_key_hit_and_id
     * Looks for the ip address and returns the ip address key,hits and id
     * @throws \Exception if the ip address is missing
     */
    private function find_address_key_return_key_hit_and_id()
    {
        $ip_address = $this->ip_address;
        if (!empty($ip_address)) {
            $sql = "SELECT ip_key,ip_hit,view_id FROM formPageViews WHERE ip = :ip_address";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':ip_address', $ip_address, \PDO::PARAM_STR);
            $stmt->execute();
            $search_result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $this->page_view_id = $search_result['view_id'];
            $this->ip_address_key = $search_result['ip_key'];
            $this->ip_address_hit = $search_result['ip_hit'];
        } else {
            throw new \Exception('find_address_key_return_key_hit_and_id call: ip address is missing');
        }
    }

    /**
     * Creates new entry in the database
     * @throws \Exception if the ip address is missing
     */
    private function create_ip_address_entry()
    {
        // Get the vars
        $ip_address = $this->ip_address;
        $random_page_view_id = $this->random_page_view_id;
        $random_ip_address_key = $this->random_ip_address_key;

        if (!empty($ip_address)) {
            // Database INSERT
            $sql = "INSERT INTO formPageViews (view_id,ip,ip_key,ip_hit) VALUES (:view_id,:ip_address,:ip_address_key,:ip_address_hit)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam('view_id', $random_page_view_id, \PDO::PARAM_STR);
            $stmt->bindParam('ip_address', $ip_address, \PDO::PARAM_STR);
            $stmt->bindParam('ip_address_key', $random_ip_address_key, \PDO::PARAM_STR);
            $stmt->bindValue('ip_address_hit', 1, \PDO::PARAM_INT);
            $stmt->execute();
        } else {
            throw new \Exception('create_ip_address_entry: ip data is missing');
        }

    }

    /**
     * updates the entry hit counter in the database
     * @throws \Exception if the ip address is missing
     */
    private function update_ip_address_entry_hit()
    {
        // Get the vars
        $ip_address = $this->ip_address;
        $page_view_id = $this->page_view_id;
        $ip_address_key = $this->ip_address_key;

        if (!empty($ip_address) && !empty($page_view_id)) {
            $sql = "UPDATE formPageViews SET ip_hit = ip_hit + 1 WHERE ip = :ip_address AND ip_key = :ip_address_key AND view_id = :view_page_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':ip_address', $ip_address, \PDO::PARAM_STR);
            $stmt->bindParam(':ip_address_key', $ip_address_key, \PDO::PARAM_STR);
            $stmt->bindParam(':view_page_id', $page_view_id, \PDO::PARAM_STR);
            $stmt->execute();
        } else {
            throw new \Exception('update_ip_address_entry call: ip address is missing');
        }


    }

    /**
     * updates the entry hit counter and submission id in the database
     * @throws \Exception if the ip address is missing
     */
    private function update_ip_address_entry_key_hit()
    {
        // Get the vars
        $ip_address = $this->ip_address;
        $page_view_id = $this->page_view_id;
        $ip_address_key = $this->ip_address_key;
        $random_ip_address_key = $this->random_ip_address_key;

        if (!empty($ip_address)) {
            $sql = "UPDATE formPageViews SET ip_hit = ip_hit + 1,ip_key = :new_ip_address_key  WHERE ip = :ip_address AND ip_key = :ip_address_key AND view_id = :view_page_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':ip_address', $ip_address, \PDO::PARAM_STR);
            $stmt->bindParam(':ip_address_key', $ip_address_key, \PDO::PARAM_STR);
            $stmt->bindParam(':new_ip_address_key', $random_ip_address_key, \PDO::PARAM_STR);
            $stmt->bindParam(':view_page_id', $page_view_id, \PDO::PARAM_STR);
            $stmt->execute();
        } else {
            throw new \Exception('update_ip_address_entry call: ip data is missing');
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