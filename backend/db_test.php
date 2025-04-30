<?php
header('Content-Type: text/plain');

$host = 'localhost';
$user = 'kpham21';
$pass = 'kpham21';
$dbname = 'kpham21';
$port = 3306;

echo "Testing MySQL/MariaDB connection:\n\n";

echo "Testing MySQLi connection...\n";
try {
    $mysqli = new mysqli($host, $user, $pass, $dbname, $port);
    
    if ($mysqli->connect_error) {
        echo "MySQLi Error: " . $mysqli->connect_error . "\n";
    } else {
        echo "MySQLi connection successful!\n";
        echo "Connection ID: " . $mysqli->thread_id . "\n";
        echo "Server info: " . $mysqli->server_info . "\n";
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "MySQLi Exception: " . $e->getMessage() . "\n";
}

echo "\n";

echo "Testing PDO connection...\n";
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "PDO connection successful!\n";
    
    $serverVersion = $pdo->query('select version()')->fetchColumn();
    echo "Server version: " . $serverVersion . "\n";
    
    $pdo = null;
} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
}