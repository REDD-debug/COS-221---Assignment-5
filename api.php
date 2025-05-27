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
                    $this->loginUser($forData);
                    break;
                case 'GetTopRatedProducts':
                    $this->getTopRatedProducts($forData);
                    break;
                case 'GetAllProducts':
                    $this->getAllProducts($forData);
                    break;
                case 'AddRatingReview':
                    $this->addRatingReview($forData);
                    break;
                case 'DeleteProduct':
                    $this->deleteProduct($forData);
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

    private function loginUser($forData) {
        try {
            if (empty($forData['email']) || empty($forData['password'])) {
                throw new Exception("Email and password are required", 400);
            }

            if (!filter_var($forData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format", 400);
            }

            $stmt = $this->forConnection->prepare("SELECT id, name, surname, email, api_key, password, forSalt, type FROM user_info WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Database preparation failed", 500);
            }

            $stmt->bind_param("s", $forData['email']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("No account found with this email address. Please check your email or sign up for a new account.", 401);
            }

            $user = $result->fetch_assoc();
            $hashed_input = hash("sha256", $user['forSalt'] . $forData['password']);
            
            if ($hashed_input !== $user['password']) {
                throw new Exception("Incorrect password. Please try again.", 401);
            }

            echo json_encode([
                "status" => "success",
                "timestamp" => time(),
                "data" => [[
                    "apikey" => $user['api_key'],
                    "user_id" => $user['id'],
                    "name" => $user['name'],
                    "surname" => $user['surname'],
                    "email" => $user['email'],
                    "user_type" => $user['type']
                ]]
            ]);

        } catch (Exception $e) {
            $this->handleError($e);
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
                $errors[] = "Password must be 8+ chars with uppercase, lowercase, number and symbol";
            }

            $valid_types = ["Customer", "Admin"];
            if (!in_array($user_type, $valid_types)) {
                $errors[] = "Invalid user type. Must be Customer or Admin";
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
                throw new Exception("An account with this email already exists. Please use a different email or try logging in.", 409);
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
                "message" => "Registration successful",
                "timestamp" => time(),
                "data" => [
                    "apikey" => $api_key,
                    "user_type" => $user_type
                ]
            ]);

        } catch (Exception $forError){
            error_log("Registration Error: " . $forError->getMessage());
            $this->handleError($forError);
        }
    }

    private function validateApiKey($apiKey) {
        $stmt = $this->forConnection->prepare("SELECT id, name, email, type FROM user_info WHERE api_key = ?");
        if (!$stmt) {
            throw new Exception("Database preparation failed", 500);
        }
        
        $stmt->bind_param("s", $apiKey);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Invalid or expired API key", 401);
        }
        
        return $result->fetch_assoc();
    }

     private function getAllProducts($forData) { //here
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

            $apiKey = $forData['apikey'];
            $userCheck = $this->forConnection->prepare("SELECT id FROM user_info WHERE api_key = ?");
            $userCheck->bind_param("s", $apiKey);
            $userCheck->execute();
            $userResult = $userCheck->get_result();
            if ($userResult->num_rows === 0) {
                throw new Exception("Invalid API key", 401);
            }

            $allowedShoeFields = [
                "Shoe_ID", "Name", "Brand_ID", "Color", "Size", "User_ID", 
                "image_URL", "Release_Date", "Description"
            ];

            $allowedPriceFields = [
                "Price_ID", "Shoe_ID", "Price", "Retailer_ID", "buy_link"
            ];

            $allowedDerivedFields = [
                "AverageRating", "Reviews"
            ];

            $shoeFields = [];
            $priceFields = [];
            $derivedFields = [];
            foreach ($forData['return'] as $field) {
                if (in_array($field, $allowedShoeFields)) {
                    $shoeFields[] = "sp.`$field`";
                } elseif (in_array($field, $allowedPriceFields)) {
                    $priceFields[] = "pl.`$field`";
                } elseif (in_array($field, $allowedDerivedFields)) {
                    $derivedFields[] = $field;
                } else {
                    throw new Exception("Invalid field requested in 'return': '$field'", 400);
                }
            }

            if (empty($shoeFields) && empty($priceFields) && empty($derivedFields)) {
                throw new Exception("No valid return fields specified", 400);
            }

            $selectFields = array_merge($shoeFields, $priceFields);
            if (!empty($selectFields)) {
                $selectClause = implode(", ", $selectFields);
            } else {
                $selectClause = "sp.Shoe_ID"; 
            }

            $query = "SELECT $selectClause";
            $hasDerived = !empty($derivedFields);
            if ($hasDerived) {
                $query .= ",
                            (SELECT AVG(rating.Score)
                             FROM rating
                             WHERE rating.Shoe_ID = sp.Shoe_ID
                             GROUP BY rating.Shoe_ID) AS AverageRating,
                            (SELECT GROUP_CONCAT(review.Comment SEPARATOR '; ')
                             FROM review
                             WHERE review.Shoe_ID = sp.Shoe_ID
                             GROUP BY review.Shoe_ID) AS Reviews";
            }
            $query .= "
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

            if (isset($forData['limit']) && is_numeric($forData['limit']) && $forData['limit'] > 0 && $forData['limit'] <= 500) {
                $query .= " LIMIT " . intval($forData['limit']);
            }

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
                if (isset($row['Reviews']) && $row['Reviews'] !== null) {
                    $row['Reviews'] = explode('; ', $row['Reviews']);
                } else {
                    $row['Reviews'] = [];
                }
                if (isset($row['AverageRating']) && $row['AverageRating'] === null) {
                    $row['AverageRating'] = null;
                }
                $products[] = $row;
            }

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

    private function addRatingReview($forData) { 
        try {
            if (!isset($forData['type']) || $forData['type'] !== 'AddRatingReview') {
                throw new Exception("Missing or invalid 'type'. Expected 'AddRatingReview'.", 400);
            }

            if (!isset($forData['apikey']) || !is_string($forData['apikey']) || trim($forData['apikey']) === '') {
                throw new Exception("Missing or invalid 'apikey'", 400);
            }

            if (!isset($forData['user_id']) || !is_string($forData['user_id']) || trim($forData['user_id']) === '') {
                throw new Exception("Missing or invalid 'user_id'", 400);
            }

            if (!isset($forData['shoe_id']) || !is_string($forData['shoe_id']) || trim($forData['shoe_id']) === '') {
                throw new Exception("Missing or invalid 'shoe_id'", 400);
            }

            if (!isset($forData['rating']) || !is_numeric($forData['rating']) || $forData['rating'] < 1 || $forData['rating'] > 5) {
                throw new Exception("Missing or invalid 'rating'. Must be between 1 and 5.", 400);
            }

            if (isset($forData['review']) && $forData['review'] !== null) {
                if (!is_string($forData['review']) || strlen($forData['review']) > 100) {
                    throw new Exception("Invalid 'review'. Must be a string with 100 characters or less.", 400);
                }
            }

            $apiKey = $forData['apikey'];
            $userCheck = $this->forConnection->prepare("SELECT id FROM user_info WHERE api_key = ?");
            $userCheck->bind_param("s", $apiKey);
            $userCheck->execute();
            $userResult = $userCheck->get_result();
            if ($userResult->num_rows === 0) {
                throw new Exception("Invalid API key", 401);
            }
            $userCheck->close();

            $userId = $forData['user_id'];
            $shoeId = $forData['shoe_id'];
            $rating = (int)$forData['rating'];
            $review = isset($forData['review']) && $forData['review'] !== '' ? $forData['review'] : null;

            $userStmt = $this->forConnection->prepare("SELECT UserID FROM users WHERE UserID = ?");
            $userStmt->bind_param("s", $userId);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            if ($userResult->num_rows === 0) {
                throw new Exception("User not found", 404);
            }
            $userStmt->close();

            $shoeStmt = $this->forConnection->prepare("SELECT Shoe_ID FROM shoe_products WHERE Shoe_ID = ?");
            $shoeStmt->bind_param("s", $shoeId);
            $shoeStmt->execute();
            $shoeResult = $shoeStmt->get_result();
            if ($shoeResult->num_rows === 0) {
                throw new Exception("Shoe not found", 404);
            }
            $shoeStmt->close();

            $this->forConnection->begin_transaction();

            try {
                $ratingStmt = $this->forConnection->prepare(
                    "INSERT INTO rating (UserID, Shoe_ID, Score) VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE Score = ?"
                );
                if (!$ratingStmt) {
                    throw new Exception("Failed to prepare rating statement: " . $this->forConnection->error, 500);
                }
                $ratingStmt->bind_param("ssii", $userId, $shoeId, $rating, $rating);
                if (!$ratingStmt->execute()) {
                    throw new Exception("Failed to save rating: " . $ratingStmt->error, 500);
                }
                $ratingStmt->close();

                if ($review !== null) {
                    $reviewStmt = $this->forConnection->prepare(
                        "INSERT INTO review (UserID, Shoe_ID, Comment) VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE Comment = ?"
                    );
                    if (!$reviewStmt) {
                        throw new Exception("Failed to prepare review statement: " . $this->forConnection->error, 500);
                    }
                    $reviewStmt->bind_param("ssss", $userId, $shoeId, $review, $review);
                    if (!$reviewStmt->execute()) {
                        throw new Exception("Failed to save review: " . $reviewStmt->error, 500);
                    }
                    $reviewStmt->close();
                }

                $this->forConnection->commit();

                echo json_encode([
                    "status" => "success",
                    "timestamp" => time(),
                    "message" => "Rating and review added successfully"
                ]);
            } catch (Exception $e) {
                $this->forConnection->rollback();
                throw $e;
            }
        } catch (Exception $forError) {
            $this->handleError($forError);
        }
    } 

    private function getTopRatedProducts($forData) {
        try {
            if (!isset($forData['apikey'])) {
                throw new Exception("API key is required", 400);
            }

            $user = $this->validateApiKey($forData['apikey']);

            $query = "SELECT 
                        sp.Shoe_ID, 
                        sp.Name, 
                        sp.Brand_ID, 
                        sp.Color, 
                        sp.image_URL, 
                        sp.Description,
                        AVG(r.Score) as AverageRating
                    FROM shoe_products sp
                    JOIN rating r ON sp.Shoe_ID = r.Shoe_ID
                    GROUP BY sp.Shoe_ID
                    ORDER BY AverageRating DESC
                    LIMIT 5";

            $stmt = $this->forConnection->prepare($query);
            if (!$stmt) {
                throw new Exception("Query preparation failed: " . $this->forConnection->error, 500);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = [
                    'Shoe_ID' => $row['Shoe_ID'],
                    'Name' => $row['Name'],
                    'Brand_ID' => $row['Brand_ID'],
                    'Color' => $row['Color'],
                    'image_URL' => $row['image_URL'],
                    'Description' => $row['Description'],
                    'AverageRating' => round($row['AverageRating'], 1)
                ];
            }

            echo json_encode([
                "status" => "success",
                "timestamp" => time(),
                "data" => $products
            ]);

        } catch (Exception $e) {
            $this->handleError($e);
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




