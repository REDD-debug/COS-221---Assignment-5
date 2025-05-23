<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
require_once "config.php";

class UserAPI{
    private static $forInstances = null;
    private $forConnection;

    private function __construct(){
        try {
            $forDatabase = Database::instance();
            $this->forConnection = $forDatabase->getConnection();

            if ($this->forConnection === null || $this->forConnection->connect_error) {
                throw new Exception("The database connection can not be established");
            }
        } catch (Exception $forError) {
            error_log("Connection to database error: " . $forError->getMessage());
            throw $forError;
        }
    }

    public static function getInstance(){
        if (self::$forInstances === null) {
            try {
                self::$forInstances = new self();
            } catch (Exception $forError) {
                error_log("Creation of the UserAPI instance failed: " . $forError->getMessage());
                throw $forError;
            }
        }
        return self::$forInstances;
    }

    private function __clone(){}
    public function __wakeup(){
        throw new Exception("Can not unserialize singleton");
    }

    public function handleRequest(){
        try {
            $forJson = file_get_contents('php://input');
            if ($forJson === false){
                throw new Exception("Failed to read input", 400);
            }

            $forData = json_decode($forJson, true);
            if (json_last_error() !== JSON_ERROR_NONE){
                throw new Exception("Invalid JSON: " . json_last_error_msg(), 400);
            }

            if (!isset($forData['type'])){
                throw new Exception("Missing request type", 400);
            }

            switch ($forData['type']){
                case 'CheckEmail':
                    $this->checkEmail($forData['email'] ?? '');
                    break;
                case 'Register':
                    $this->registerUser(
                        $forData['name'] ?? '',
                        $forData['surname'] ?? '',
                        $forData['email'] ?? '',
                        $forData['password'] ?? '',
                        $forData['user_type'] ?? ''
                    );
                    break;
                case 'Login':
                    try {
                        if (empty($forData['email']) || empty($forData['password'])) {
                            throw new Exception("Your email and password is required", 400);
                        }
                
                        $stmt = $this->forConnection->prepare("SELECT id, api_key, password, forSalt FROM user_info WHERE email = ?");
                        $stmt->bind_param("s", $forData['email']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                
                        if ($result->num_rows > 0) {
                            $user = $result->fetch_assoc();
                            
                            $hashed_input = hash("sha256", $user['forSalt'] . $forData['password']);
                            
                            if ($hashed_input === $user['password']) {
                                echo json_encode([
                                    "status" => "success",
                                    "timestamp" => time(),
                                    "data" => [["apikey" => $user['api_key']]]
                                ]);
                            } else {
                                throw new Exception("The credentials are invalid", 401);
                            }
                        } else {
                            throw new Exception("The credentials are invalid", 401);
                        }
                    } catch (Exception $e) {
                        $this->handleError($e);
                    }
                    break;
                case 'GetAllProducts':
                    $this->getAllProducts($forData);
                    break;
                default:
                    throw new Exception("Unknown request type", 404);
            }
        } catch (Exception $forError) {
            $this->handleError($forError);
        }
    }

    private function checkEmail($email){
        try {
            if (empty($email)){
                throw new Exception("An email is required", 400);
            }

            $forEmails = $this->forConnection->prepare("SELECT email FROM user_info WHERE email = ?");
            if (!$forEmails){
                throw new Exception("Database prepare has failed", 500);
            }

            $forEmails->bind_param("s", $email);
            $forEmails->execute();
            $result = $forEmails->get_result();

            echo json_encode([
                "status" => $result->num_rows > 0 ? "error" : "success",
                "message" => $result->num_rows > 0 ? "The email already exists" : "The email is available",
                "timestamp" => time()
            ]);
        } catch (Exception $forError) {
            $this->handleError($forError);
        }
    }

    private function handleError(Exception $forError){
        http_response_code($forError->getCode() ?: 500);
        echo json_encode([
            "status" => "error",
            "timestamp" => time(),
            "message" => $forError->getMessage()
        ]);
        error_log("API Error: " . $forError->getMessage());
    }
}
try {
    UserAPI::getInstance()->handleRequest();
} catch (Exception $forError) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Internal server error",
        "timestamp" => time()
    ]);
    error_log("Critical API Error: " . $forError->getMessage());
}
?>




