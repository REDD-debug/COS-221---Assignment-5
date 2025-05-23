<?php
class Database {
    private $forConnection;
    private $forHost = "wheatley.cs.up.ac.za";
    private  $forUser = "u23538199";
    private $forPassword = "P4ZYT2THXRERAPD622SFGEVMUZT5VE66";
    private $forDatabase = "u23538199_products";
    
    private function __construct() {
        $this->forConnection = new mysqli($this->forHost, $this->forUser, $this->forPassword, $this->forDatabase);
        if ($this->forConnection->connect_error) {
            die(json_encode([
                "status" => "error",
                "timestamp" => round(microtime(true) * 1000),
                "data" => "Connection to the database has failed: " . $this->forConnection->connect_error
            ]));
        }
    }
    
    public static function instance(){
        static $forInstances = null;
        if ($forInstances == null) {
            $forInstances = new Database();
        }
        return $forInstances;
    }

    public function getConnection(){
        return $this->forConnection;
    }
}
?>