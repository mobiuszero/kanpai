<?php
/**
 * Used to create,update, and read the database settings
 */

namespace ZenApp;

class ZenAppSettings
{
    /**
     * Vars for the callback on the read function
     * @var array $database_callback
     */
    public $database_callback;
    /**
     * For the constructor
     * @var object $pdo
     */
    private $pdo;
    /**
     * Vars for the callbacks
     * @var array $pdo
     * @var array $pdo
     */
    private $configuration;
    /**
     * Vars for the settings database
     * @var array $config_name
     * @var array $config_settings
     */
    private $config_name, $config_settings;

    /**
     * Initialize the object
     * @param \PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Process the request made
     * @param $request_type
     * @param $configuration
     * @throws \Exception
     */
    public function settings_request($request_type, $configuration)
    {
        if ($request_type === "read") {
            $this->sanitize_data_save("read", $configuration);
            $count_read_array = count($this->config_name);
            for ($i = 0; $i < $count_read_array; $i++) {
                $this->config_read_settings($this->config_name[$i]);
            }
        } elseif ($request_type === "create") {
            $this->sanitize_data_save("create", $configuration);
            $count_create_array = count($this->config_name);
            for ($i = 0; $i < $count_create_array; $i++) {
                $this->config_settings[$i] = json_encode($this->config_settings[$i]);
                $this->config_create_settings($this->config_name[$i], $this->config_settings[$i]);
            }
        } elseif ($request_type === "update") {
            $this->sanitize_data_save("update", $configuration);
            $count_create_array = count($this->config_name);
            for ($i = 0; $i < $count_create_array; $i++) {
                $this->config_settings[$i] = json_encode($this->config_settings[$i]);
                $this->config_update_settings($this->config_name[$i], $this->config_settings[$i]);
            }
        } else {
            throw new \Exception('Request type is missing');
        }

        if (empty($configuration)) {
            throw new \Exception('Configuration array is empty');
        }
    }

    /**
     * Sanitize configuration requests made to the database and save the result
     * @param string $request_type
     * @param array $configuration
     * @throws \Exception
     */
    private function sanitize_data_save($request_type, $configuration)
    {
        if ($request_type === 'update' || $request_type === "create") {
            $sanitize_filter_array = [
                'name' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'flags' => FILTER_FORCE_ARRAY | FILTER_FLAG_STRIP_HIGH
                ],
                'setting' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'flags' => FILTER_FORCE_ARRAY | FILTER_FLAG_STRIP_HIGH
                ],
            ];
            $sanitize_configuration = filter_var_array($configuration, $sanitize_filter_array);
        } elseif ($request_type === 'read') {
            $sanitize_filter_array = [
                'name' => [
                    'filter' => FILTER_SANITIZE_STRING,
                    'flags' => FILTER_FORCE_ARRAY | FILTER_FLAG_STRIP_HIGH
                ]
            ];
            $sanitize_configuration = filter_var_array($configuration, $sanitize_filter_array);
        } else {
            throw new \Exception('Invalid request type');
        }

        $this->configuration = $sanitize_configuration;
        $this->config_name = $this->configuration['name'];
        if (isset($this->configuration['setting'])) {
            $this->config_settings = $this->configuration['setting'];
        }
    }

    /**
     * @param $config_name
     * @return array
     */
    private function config_read_settings($config_name)
    {
        // Database SELECT
        $sql = "SELECT config_name,config_settings FROM configuration WHERE config_name = :config_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':config_name', $config_name, \PDO::PARAM_STR);
        $stmt->execute();
        $configuration_settings = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->database_callback[] = $configuration_settings;
        return $this->database_callback;
    }

    /**
     * Add form settings configurations
     * @param $config_name
     * @param $config_settings
     * @throws \Exception
     */
    private function config_create_settings($config_name, $config_settings)
    {
        if ($this->check_database_on_create($config_name) === false) {
            // Database INSERT
            $sql = "INSERT INTO configuration (config_name,config_settings) VALUES (:config_name,:config_settings)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':config_name', $config_name, \PDO::PARAM_STR);
            $stmt->bindParam(':config_settings', $config_settings, \PDO::PARAM_STR);
            $stmt->execute();
        } else {
            throw new \Exception('Database item exist');
        }
    }

    /**
     * Check database on create if item exists
     * @param $config_name
     * @return bool
     */
    private function check_database_on_create($config_name)
    {
        $return = false;
        $sql = "SELECT config_name FROM configuration WHERE config_name = :config_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':config_name', $config_name, \PDO::PARAM_STR);
        $stmt->execute();
        if (!empty($stmt->fetch(\PDO::FETCH_OBJ)->config_name)) {
            $return = true;
        }

        return $return;
    }

    /**
     *
     * @param $config_name
     * @param $config_settings
     * @throws \Exception
     */
    private function config_update_settings($config_name, $config_settings)
    {
        if (!empty($config_name) && !empty($config_settings)) {
            // Database UPDATE
            $sql = "UPDATE configuration SET config_settings = :config_settings WHERE config_name = :config_name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':config_name', $config_name, \PDO::PARAM_STR);
            $stmt->bindParam(':config_settings', $config_settings, \PDO::PARAM_STR);
            $stmt->execute();
        } else {
            throw new \Exception('Missing update data');
        }
    }

}