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

$method = $_SERVER['REQUEST_METHOD'];

$sessionId = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($sessionId) {
            getSingleSession($sessionId);
        } else {
            getAllSessions();
        }
        break;
        
    case 'DELETE':
        if (!$sessionId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Session ID is required']);
            exit;
        }
        
        deleteSession($sessionId);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function getAllSessions() {
    try {
        $db = getDBConnection();
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;
        
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        
        $query = "SELECT s.*, u.username 
                  FROM game_sessions s
                  JOIN users u ON s.user_id = u.user_id";
        
        $params = [];
        
        if ($userId) {
            $query .= " WHERE s.user_id = ?";
            $params[] = $userId;
        }
        
        $query .= " ORDER BY s.start_time DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $countQuery = "SELECT COUNT(*) FROM game_sessions";
        if ($userId) {
            $countQuery .= " WHERE user_id = ?";
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute([$userId]);
        } else {
            $countStmt = $db->query($countQuery);
        }
        
        $totalCount = $countStmt->fetchColumn();
        
        echo json_encode([
            'success' => true, 
            'sessions' => $sessions,
            'pagination' => [
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalCount / $limit)
            ]
        ]);
    } catch (PDOException $e) {
        error_log("Get All Sessions Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getSingleSession($sessionId) {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare(
            "SELECT s.*, u.username 
             FROM game_sessions s
             JOIN users u ON s.user_id = u.user_id
             WHERE s.session_id = ?"
        );
        
        $stmt->execute([$sessionId]);
        
        if ($stmt->rowCount() > 0) {
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'session' => $session]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Session not found']);
        }
    } catch (PDOException $e) {
        error_log("Get Single Session Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteSession($sessionId) {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("DELETE FROM game_sessions WHERE session_id = ?");
        $result = $stmt->execute([$sessionId]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Session deleted successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete session']);
        }
    } catch (PDOException $e) {
        error_log("Delete Session Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}