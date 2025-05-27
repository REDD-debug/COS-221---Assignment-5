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

    private function registerUser($name, $surname, $email, $password, $user_type){
        try {
            $errors = [];
            if (empty($name)) $errors[] = "Your name is required";
            if (empty($surname)) $errors[] = "Your surname is required";
            if (empty($email)) $errors[] = "An email is required";
            if (empty($password)) $errors[] = "A password is required";
            if (empty($user_type)) $errors[] = "A User type is required";

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
                $errors[] = "Invalid email format";
            }

            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $password)){
                $errors[] = "You password must be 8+ chars with uppercase, lowercase, number and symbol";
            }

            $valid_types = ["Customer"];
            if (!in_array($user_type, $valid_types)) {
                $errors[] = "Invalid user type";
            }

            if (!empty($errors)){
                throw new Exception(implode(", ", $errors), 400);
            }

            $checking = $this->forConnection->prepare("SELECT email FROM user_info WHERE email = ?");
            if ($checking === false){
                throw new Exception("Database preparation failed: " . $this->forConnection->error, 500);
            }

            $checking->bind_param("s", $email);
            if (!$checking->execute()){
                throw new Exception("Database query failed: " . $checking->error, 500);
            }

            if ($checking->get_result()->num_rows > 0){
                $checking->close();
                throw new Exception("Email already exists", 409);
            }
            $checking->close();

            $api_key = bin2hex(random_bytes(16));
            $salt = bin2hex(random_bytes(16));
            $hashed_password = hash("sha256", $salt . $password);

            $inserting = $this->forConnection->prepare(
                "INSERT INTO user_info 
                (name, surname, email, password, type, api_key, forSalt) 
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if ($inserting === false){
                throw new Exception("Database preparation failed: " . $this->forConnection->error, 500);
            }

            $bindResult = $inserting->bind_param(
                "sssssss", 
                $name, 
                $surname, 
                $email, 
                $hashed_password, 
                $user_type, 
                $api_key,
                $salt
            );

            if (!$bindResult){
                throw new Exception("Parameter binding failed", 500);
            }

            if (!$inserting->execute()){
                throw new Exception("Registration failed: " . $inserting->error, 500);
            }
            $inserting->close();

            echo json_encode([
                "status" => "success",
                "timestamp" => time(),
                "data" => ["apikey" => $api_key]
            ]);

        } catch (Exception $forError){
            error_log("Registration Error: " . $forError->getMessage());
            $this->handleError($forError);
        }
    }

    

      private function getAllProducts($forData) {
    try {
        if (!isset($forData['type']) || !is_string($forData['type']) || $forData['type'] !== "GetAllProducts") {
            throw new Exception("Missing or invalid 'type'. Expected 'GetAllProducts'.", 400);
        }

        if (!isset($forData['apikey']) || !is_string($forData['apikey']) || trim($forData['apikey']) === '') {
            throw new Exception("Missing or invalid 'apikey'", 400);
        }

        if (!isset($forData['return']) || !is_array($forData['return']) || empty($forData['return'])) {
            throw new Exception("Missing or invalid 'return' field", 400);
        }

        // Verify API key
        $apiKey = $forData['apikey'];
        $userCheck = $this->forConnection->prepare("SELECT id FROM user_info WHERE api_key = ?");
        $userCheck->bind_param("s", $apiKey);
        $userCheck->execute();
        $userResult = $userCheck->get_result();
        if ($userResult->num_rows === 0) {
            throw new Exception("Invalid API key", 401);
        }

        // Allowed fields
        $allowedShoeFields = [
            "Shoe_ID", "Name", "Brand_ID", "Color", "Size", "User_ID", 
            "image_URL", "Release_Date", "Description"
        ];

        $allowedPriceFields = [
            "Price_ID", "Shoe_ID", "Price", "Retailer_ID", "buy_link"
        ];

        // Validate requested fields
        $shoeFields = [];
        $priceFields = [];
        foreach ($forData['return'] as $field) {
            if (in_array($field, $allowedShoeFields)) {
                $shoeFields[] = "sp.`$field`";
            } elseif (in_array($field, $allowedPriceFields)) {
                $priceFields[] = "pl.`$field`";
            } else {
                throw new Exception("Invalid field requested in 'return': '$field'", 400);
            }
        }

        if (empty($shoeFields) && empty($priceFields)) {
            throw new Exception("No valid return fields specified", 400);
        }

        // SELECT clause
        $selectFields = array_merge($shoeFields, $priceFields);
        $selectClause = implode(", ", $selectFields);

        // Subquery to get only one price row per product (lowest Price_ID)
        $query = "SELECT $selectClause 
                  FROM shoe_products sp 
                  LEFT JOIN (
                      SELECT *
                      FROM pricelisting pl1
                      WHERE pl1.Price_ID = (
                          SELECT MIN(pl2.Price_ID)
                          FROM pricelisting pl2
                          WHERE pl2.Shoe_ID = pl1.Shoe_ID
                      )
                  ) pl ON sp.Shoe_ID = pl.Shoe_ID";

        $params = [];
        $types = '';
        $where = [];

        // Handle search
        if (isset($forData['search']) && is_array($forData['search'])) {
            foreach ($forData['search'] as $col => $val) {
                if (in_array($col, $allowedShoeFields)) {
                    $tablePrefix = "sp.";
                } elseif (in_array($col, $allowedPriceFields)) {
                    $tablePrefix = "pl.";
                } else {
                    continue;
                }

                $isFuzzy = isset($forData['fuzzy']) && $forData['fuzzy'] === true;
                if ($isFuzzy) {
                    $where[] = "$tablePrefix`$col` LIKE ?";
                    $params[] = '%' . $val . '%';
                } else {
                    $where[] = "$tablePrefix`$col` = ?";
                    $params[] = $val;
                }
                $types .= 's';
            }
        }

        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        // Handle sorting
        if (isset($forData['sort'])) {
            $sortField = $forData['sort'];
            if (in_array($sortField, $allowedShoeFields)) {
                $tablePrefix = "sp.";
            } elseif (in_array($sortField, $allowedPriceFields)) {
                $tablePrefix = "pl.";
            } else {
                throw new Exception("Invalid sort field: '$sortField'", 400);
            }

            $order = isset($forData['order']) ? strtoupper($forData['order']) : "ASC";
            if (!in_array($order, ["ASC", "DESC"])) {
                throw new Exception("Invalid order value: '$order'. Must be 'ASC' or 'DESC'.", 400);
            }

            $query .= " ORDER BY $tablePrefix`$sortField` $order";
        }

        // Handle limit
        if (isset($forData['limit']) && is_numeric($forData['limit']) && $forData['limit'] > 0 && $forData['limit'] <= 500) {
            $query .= " LIMIT " . intval($forData['limit']);
        }

        // Prepare and execute query
        $stmt = $this->forConnection->prepare($query);
        if (!$stmt) {
            throw new Exception("Query prepare failed: " . $this->forConnection->error, 500);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        // Check if searching by Shoe_ID and no results found
        if (isset($forData['search']['Shoe_ID']) && count($products) === 0) {
            throw new Exception("No product found with the specified Shoe_ID", 404);
        }

        echo json_encode([
            "status" => "success",
            "timestamp" => time(),
            "data" => $products
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




