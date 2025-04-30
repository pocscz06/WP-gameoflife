<?php

header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once '../../config/database.php';
require_once '../../auth/authentication.php';

requireAdmin();

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    $stats = [];
    
    $userCountStmt = $db->query("SELECT COUNT(*) FROM users");
    $stats['totalUsers'] = (int)$userCountStmt->fetchColumn();
    
    $sessionCountStmt = $db->query("SELECT COUNT(*) FROM game_sessions");
    $stats['totalSessions'] = (int)$sessionCountStmt->fetchColumn();
    
    $avgGenStmt = $db->query(
        "SELECT AVG(generations_reached) 
         FROM game_sessions 
         WHERE generations_reached > 0"
    );
    $avgGen = $avgGenStmt->fetchColumn();
    $stats['averageGenerations'] = $avgGen ? (float)$avgGen : 0;
    
    $db->exec(
        "UPDATE game_sessions 
         SET end_time = start_time + INTERVAL 1 HOUR 
         WHERE end_time IS NULL 
         AND start_time < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (PDOException $e) {
    error_log("Stats API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}