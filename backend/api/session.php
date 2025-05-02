<?php

// header('Access-Control-Allow-Origin: http://localhost:5173');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type, Authorization');
// header('Access-Control-Allow-Credentials: true');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit(0);
// }

require_once '../../config/database.php';
require_once '../../auth/authentication.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$sessionId = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'POST':
        createSession();
        break;
        
    case 'PUT':
        if (!$sessionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Session ID is required']);
            exit;
        }
        updateSession($sessionId);
        break;
        
    case 'GET':
        if ($sessionId) {
            getSession($sessionId);
        } else {
            getUserSessions();
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function createSession() {
    global $userId;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $pattern = isset($data['pattern']) ? $data['pattern'] : 'custom';
        
        $db = getDBConnection();
        
        $stmt = $db->prepare(
            "INSERT INTO game_sessions (user_id, pattern_used, start_time) 
             VALUES (?, ?, NOW())"
        );
        
        $result = $stmt->execute([$userId, $pattern]);
        
        if ($result) {
            $sessionId = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Game session started',
                'session_id' => $sessionId
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create session']);
        }
    } catch (PDOException $e) {
        error_log("Create Session Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updateSession($sessionId) {
    global $userId;
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            exit;
        }
        
        $db = getDBConnection();
        
        $checkStmt = $db->prepare("SELECT session_id FROM game_sessions WHERE session_id = ? AND user_id = ?");
        $checkStmt->execute([$sessionId, $userId]);
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized to update this session']);
            exit;
        }
        
        $generations = isset($data['generations']) ? (int)$data['generations'] : null;
        $maxPopulation = isset($data['max_population']) ? (int)$data['max_population'] : null;
        $patternUsed = isset($data['pattern_used']) ? $data['pattern_used'] : null;
        $endSession = isset($data['end_session']) ? (bool)$data['end_session'] : false;
        
        $updateFields = [];
        $params = [];
        
        if ($generations !== null) {
            $updateFields[] = "generations_reached = ?";
            $params[] = $generations;
        }
        
        if ($maxPopulation !== null) {
            $updateFields[] = "max_population = ?";
            $params[] = $maxPopulation;
        }
        
        if ($patternUsed !== null) {
            $updateFields[] = "pattern_used = ?";
            $params[] = $patternUsed;
        }
        
        if ($endSession) {
            $updateFields[] = "end_time = NOW()";
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No valid update fields provided']);
            exit;
        }
        
        $query = "UPDATE game_sessions SET " . implode(", ", $updateFields) . " WHERE session_id = ?";
        $params[] = $sessionId;
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute($params);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Session updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update session']);
        }
    } catch (PDOException $e) {
        error_log("Update Session Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getSession($sessionId) {
    global $userId;
    
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare(
            "SELECT * FROM game_sessions 
             WHERE session_id = ? AND user_id = ?"
        );
        
        $stmt->execute([$sessionId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'session' => $session]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Session not found']);
        }
    } catch (PDOException $e) {
        error_log("Get Session Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getUserSessions() {
    global $userId;
    
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare(
            "SELECT * FROM game_sessions 
             WHERE user_id = ? 
             ORDER BY start_time DESC 
             LIMIT 10"
        );
        
        $stmt->execute([$userId]);
        
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'sessions' => $sessions]);
    } catch (PDOException $e) {
        error_log("Get User Sessions Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}