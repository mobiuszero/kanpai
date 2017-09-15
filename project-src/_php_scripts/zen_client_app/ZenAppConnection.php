<?php
/**
 * Database connection class
 */

namespace ZenApp;

class ZenAppConnection
{
    private $pdo;

    public function connect()
    {
        try {
            if (empty($this->pdo)) {
                $this->pdo = new \PDO("sqlite:" . ZenAppConnectionConfig::PATH_TO_DATABASE);
            }
        } catch (\PDOException $error) {
            exit("Exception: " . $error->getMessage());
        }
        return $this->pdo;
    }
}