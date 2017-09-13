<?php
/**
 * Used to create and search for existing tables
 */

namespace ZenApp;


class ZenAppDatabaseActions
{
    /**
     * For the constructor
     * @var object $pdo
     */
    private $pdo;

    /**
     * Used for the database actions
     * @var array $table_data
     * @var array $configuration
     * @var array $formPageViews
     * @var array $formSubmissionData
     * @var array $emailVerification
     */
    private $table_data, $configuration, $formPageViews, $formSubmissionData, $emailVerification;

    /**
     * Used for the user table
     * @var array $user_admin_interface
     */
    private $user_admin_interface;

    /**
     * Initialize the object with a specified PDO object
     * @param \PDO $pdo
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Used to create database tables
     * @return bool
     */
    public function set_database_tables()
    {
        $return = false;
        $required_tables = ['configuration', 'formPageViews', 'formSubmissionData', 'emailVerification', 'user_api_interface'];
        foreach ($required_tables as $item => $value) {
            if ($this->database_table_check($value) === false) {
                $this->table_check_result($value);
            } else {
                $return = true;
            }
        }
        return $return;
    }

    /**
     * Runs the query to check the tables in database
     * @param $table_name
     * @return mixed
     * @throws \Exception
     */
    private function database_table_check($table_name)
    {
        if (!empty($table_name)) {
            $sql = "SELECT name FROM sqlite_master WHERE name = :table_name";
            $stmt = $this->pdo->query($sql);
            $stmt->bindParam(':table_name', $table_name, \PDO::PARAM_STR);
            $stmt->execute();
            $return_table = $stmt->fetch(\PDO::FETCH_ASSOC);
        } else {
            throw new \Exception('Table name is missing');
        }

        $this->table_data[] = $return_table;

        return $return_table;
    }

    /**
     * Checks the master table and checks if tables exist then create table if missing
     * @param $missing_table_result
     */
    private function table_check_result($missing_table_result)
    {
        switch ($missing_table_result) {
            case 'configuration':
                $this->set_configuration();
                foreach ($this->configuration as $command) {
                    $this->pdo->exec($command);
                }
                break;
            case 'formPageViews':
                $this->set_formPageViews();
                foreach ($this->formPageViews as $command) {
                    $this->pdo->exec($command);
                }
                break;
            case 'formSubmissionData':
                $this->set_formSubmissionData();
                foreach ($this->formSubmissionData as $command) {
                    $this->pdo->exec($command);
                }
                break;
            case 'emailVerification':
                $this->set_emailVerification();
                foreach ($this->emailVerification as $command) {
                    $this->pdo->exec($command);
                }
                break;
            case 'user_api_interface':
                $this->set_userAdminInterface();
                foreach ($this->user_admin_interface as $command) {
                    $this->pdo->exec($command);
                }
                break;
        }


    }

    private function set_configuration()
    {
        $this->configuration = [
            'CREATE TABLE IF NOT EXISTS configuration ( config_id INTEGER PRIMARY KEY, config_name VARCHAR (64) UNIQUE NOT NULL, config_settings TEXT NOT NULL, create_date DATETIME, update_date DATETIME )',
            'CREATE TRIGGER UPDATE_configuration_DATE BEFORE UPDATE ON configuration 
        BEGIN UPDATE configuration SET update_date = datetime(\'now\', \'localtime\') WHERE rowid = new.rowid; END', 'CREATE TRIGGER INSERT_configuration_DATE AFTER INSERT ON configuration
        BEGIN UPDATE configuration SET create_date = datetime(\'now\', \'localtime\'),update_date = datetime(\'now\', \'localtime\') WHERE rowid = new.rowid; END'
        ];
    }

    private function set_formPageViews()
    {
        $this->formPageViews = [
            'CREATE TABLE IF NOT EXISTS formPageViews ( view_id VARCHAR (64) PRIMARY KEY, ip VARCHAR (45) NOT NULL, ip_key VARCHAR (64) UNIQUE NOT NULL, ip_hit INTEGER DEFAULT 0, create_date DATETIME, update_date DATETIME)',
            'CREATE TRIGGER UPDATE_formPageViews_DATE BEFORE UPDATE ON formPageViews 
        BEGIN UPDATE formPageViews SET update_date = datetime(\'now\', \'localtime\') WHERE rowid = new.rowid; END', 'CREATE TRIGGER INSERT_formPageViews_DATE AFTER INSERT ON formPageViews 
        BEGIN UPDATE formPageViews SET create_date = datetime(\'now\', \'localtime\'),update_date = datetime(\'now\', \'localtime\') WHERE rowid = new.rowid; END'
        ];
    }

    private function set_formSubmissionData()
    {
        $this->formSubmissionData = [
            'CREATE TABLE IF NOT EXISTS formSubmissionData ( submission_id VARCHAR (64) PRIMARY KEY, submission_key VARCHAR (64) UNIQUE NOT NULL, ip VARCHAR (45) NOT NULL REFERENCES formPageViews(ip), ip_key VARCHAR (64) UNIQUE NOT NULL REFERENCES formPageViews(ip_key) ON UPDATE CASCADE, submission_form_data TEXT NOT NULL, submission_user_geo_data TEXT NOT NULL, submission_count INTEGER DEFAULT 0, create_date DATETIME, update_date DATETIME)',
            'CREATE TRIGGER UPDATE_formSubmissionData_DATE BEFORE UPDATE ON formSubmissionData 
        BEGIN UPDATE formSubmissionData SET update_date = datetime(\'now\', \'localtime\') WHERE rowid = new.rowid; END',
            'CREATE TRIGGER INSERT_formSubmissionData_DATE AFTER INSERT ON formSubmissionData 
        BEGIN UPDATE formSubmissionData SET create_date = datetime(\'now\', \'localtime\'),update_date = datetime(\'now\', \'localtime\') WHERE rowid = new.rowid; END'
        ];
    }

    private function set_emailVerification()
    {
        $this->emailVerification = [
            'CREATE TABLE IF NOT EXISTS emailVerification ( email_id VARCHAR (64) PRIMARY KEY, ip VARCHAR (45) NOT NULL REFERENCES formPageViews(ip), ip_key VARCHAR (64) UNIQUE NOT NULL REFERENCES formPageViews(ip_key) ON UPDATE CASCADE, email_address VARCHAR (255) NOT NULL, email_address_data TEXT NOT NULL, email_address_verified BOOLEAN DEFAULT 0, email_flagged_data TEXT NULL, email_transaction_sent BOOLEAN DEFAULT 0, create_date DATETIME, update_date DATETIME)',
            'CREATE TRIGGER UPDATE_emailVerification_DATE BEFORE UPDATE ON emailVerification 
        BEGIN UPDATE emailVerification SET update_date = datetime(\'now\', \'localtime\') WHERE rowid = new.rowid; END',
            'CREATE TRIGGER INSERT_emailVerification_DATE AFTER INSERT ON emailVerification 
        BEGIN UPDATE emailVerification SET create_date = datetime(\'now\', \'localtime\'),update_date = datetime(\'now\', \'localtime\') WHERE rowid = new.rowid; END'
        ];
    }

    private function set_userAdminInterface()
    {
        $this->user_admin_interface = [
            'CREATE TABLE IF NOT EXISTS user_api_interface ( 
                user_id     VARCHAR(64) PRIMARY KEY,
                user_name   VARCHAR(255) UNIQUE NOT NULL,
                user_type   VARCHAR(32) UNIQUE NOT NULL, 
                user_key    VARCHAR(255) UNIQUE NOT NULL, 
                user_hash   VARCHAR(255) UNIQUE NOT NULL, 
                create_date DATETIME,
                update_date DATETIME 
            )',
            'CREATE TRIGGER UPDATE_user_api_interface_DATE BEFORE UPDATE ON user_api_interface 
        BEGIN UPDATE user_api_interface SET update_date = datetime(\'now\', \'localtime\') WHERE rowid = new.rowid; END',
            'CREATE TRIGGER INSERT_user_api_interface_DATE AFTER INSERT ON user_api_interface
        BEGIN UPDATE user_api_interface SET create_date = datetime(\'now\', \'localtime\'),update_date = datetime(\'now\', \'localtime\') WHERE rowid = new.rowid; END'
        ];
    }
}