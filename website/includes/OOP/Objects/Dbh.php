<?php

namespace Objects;
class Dbh
{
    private static $instance = null;
    private $pdo;

    private static function getDbhConnInfo()
    {
        $config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/config.ini', true);
        return array(
            "host" => $config['db']['host'],
            "username" => $config['db']['username'],
            "password" => $config['db']['password'],
            "dbname" => $config['db']['dbname'],
            "port" => $config['db']['port'],
            "charset" => $config['db']['charset']
        );
    }

    private function __construct()
    {
        $dbhConnInfo = self::getDbhConnInfo();
        $dsn = "mysql:host=" . $dbhConnInfo['host'] . ";dbname=" . $dbhConnInfo['dbname'] . ";port=" . $dbhConnInfo['port'] . ";charset=" . $dbhConnInfo['charset'];
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];
        try {
            $this->pdo = new \PDO($dsn, $dbhConnInfo['username'], $dbhConnInfo['password'], $options);
        } catch (\PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Connection failed. Please try again later.");
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Dbh();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    public function Query(
        string $query,
        array  $params = []
    )
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            if (strtoupper(substr(trim($query), 0, 6)) !== 'SELECT') {
                return $stmt->rowCount();
            }

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            return false;
        }
    }

}