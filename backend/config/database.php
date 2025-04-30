<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'kpham21');  
define('DB_PASS', 'kpham21'); 
define('DB_NAME', 'kpham21'); 
define('DB_PORT', 3306);    

function getDBConnection() {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,           
        PDO::ATTR_EMULATE_PREPARES => false,                   
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC       
    ];
    
    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";
    }
    
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        if (!defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $pdo->exec("SET NAMES utf8mb4");
        }
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        
        header('Content-Type: application/json');
        
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'errors' => ['Database connection failed. Please try again later.']
        ]);
        exit;
    }
}