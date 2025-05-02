<?php

// header('Access-Control-Allow-Origin: http://localhost:5173');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');
// header('Access-Control-Allow-Credentials: true');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit(0);
// }

header('Content-Type: application/json');

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => 'codd.cs.gsu.edu', 
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None' 
]);
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

function handleError($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'errors' => ['PHP Error: ' . $errstr]
    ]);
    exit();
}
set_error_handler('handleError');

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }
        
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        
        $errors = [];

        if (empty($username)) {
            $errors[] = "Username is required";
        }
        
        if (empty($password)) {
            $errors[] = "Password is required";
        }
        
        if (empty($errors)) {
            $db = getDBConnection();

            $stmt = $db->prepare("SELECT user_id, username, password, is_admin FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['is_admin'] = (int)$user['is_admin'];
                    $_SESSION['logged_in'] = true;
                    
                    echo json_encode([
                        "success" => true,
                        "user" => [
                            "user_id" => $user['user_id'],
                            "username" => $user['username'],
                            "is_admin" => $user['is_admin']
                        ]
                    ]);
                    exit();
                } else {
                    $errors[] = "Invalid username or password";
                }
            } else {
                $errors[] = "Invalid username or password";
            }
        }
        
        if (!empty($errors)) {
            http_response_code(401);
            echo json_encode(["success" => false, "errors" => $errors]);
            exit();
        }
    } else {
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Only POST requests are allowed"]);
        exit();
    }
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "errors" => ["Database connection error. Please try again later."]
    ]);
    exit();
} catch (Exception $e) {
    error_log("Login Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "errors" => ["An error occurred: " . $e->getMessage()]
    ]);
    exit();
}