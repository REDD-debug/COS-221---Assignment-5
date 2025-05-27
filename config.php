<?php
class Database {
    private $forConnection;
    private $forHost;
    private $forUser;
    private $forPassword;
    private $forDatabase;
    
    private function __construct() {
        $this->loadEnv(__DIR__ . '/.env');
        
        $this->forHost = $_ENV['DB_HOST'];
        $this->forUser = $_ENV['DB_USER'];
        $this->forPassword = $_ENV['DB_PASSWORD'];
        $this->forDatabase = $_ENV['DB_NAME'];
        
        $this->forConnection = new mysqli($this->forHost, $this->forUser, $this->forPassword, $this->forDatabase);
        if ($this->forConnection->connect_error) {
            die(json_encode([
                "status" => "error",
                "timestamp" => round(microtime(true) * 1000),
                "data" => "Connection to the database has failed: " . $this->forConnection->connect_error
            ]));
        }
    }
    
    private function loadEnv($filePath) {
        if (!file_exists($filePath)) {
            throw new RuntimeException(".env file not found at: $filePath");
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            if (!empty($key)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        $requiredKeys = ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME'];
        foreach ($requiredKeys as $key) {
            if (!isset($_ENV[$key])) {
                throw new RuntimeException("Missing required .env key: $key");
            }
        }
    }
    
    public static function instance() {
        static $forInstances = null;
        if ($forInstances === null) {
            $forInstances = new self();
        }
        return $forInstances;
    }
    
    public function getConnection() {
        return $this->forConnection;
    }
}
?>