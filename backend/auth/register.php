<?php
// header('Access-Control-Allow-Origin: http://localhost:5173');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');
// header('Access-Control-Allow-Credentials: true');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit(0);
// }

ob_end_clean();

ini_set('display_errors', 0);
error_reporting(E_ALL);

ob_start();

try {
    require_once '../config/database.php';
    require_once '../includes/functions.php';
    
    session_start();
    
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Only POST requests are allowed");
    }
    
    $json = file_get_contents('php://input');
    if (empty($json)) {
        throw new Exception("No data received");
    }
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON: " . json_last_error_msg());
    }
    
    $username = isset($data['username']) ? trim($data['username']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $password = isset($data['password']) ? $data['password'] : '';
    $confirm_password = isset($data['confirm_password']) ? $data['confirm_password'] : '';
    
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 6 || strlen($username) > 20) {
        $errors[] = "Username must be between 6 and 20 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (!empty($errors)) {
        throw new Exception("Validation failed");
    }
    
    $db = getDBConnection();
    
    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Username already taken";
        throw new Exception("Username check failed");
    }
    
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Email already registered";
        throw new Exception("Email check failed");
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $result = $stmt->execute([$username, $email, $hashed_password]);
    
    if (!$result) {
        throw new Exception("Failed to insert user");
    }
    
    ob_end_clean();
    
    echo json_encode([
        "success" => true, 
        "message" => "Registration successful! You can now log in."
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    
    error_log("Registration Error: " . $e->getMessage());
    
    $status_code = 500;
    if ($e->getMessage() == "Validation failed" || 
        $e->getMessage() == "Username check failed" || 
        $e->getMessage() == "Email check failed") {
        $status_code = 400;
    } else if ($e->getMessage() == "Only POST requests are allowed") {
        $status_code = 405;
    }
    
    http_response_code($status_code);
    
    echo json_encode([
        "success" => false, 
        "errors" => !empty($errors) ? $errors : [$e->getMessage()]
    ]);
}
?>