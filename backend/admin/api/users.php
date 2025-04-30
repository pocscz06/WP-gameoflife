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

$userId = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($userId) {
            getSingleUser($userId);
        } else {
            getAllUsers();
        }
        break;
        
    case 'PUT':
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        updateUser($userId);
        break;
        
    case 'DELETE':
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }
        
        deleteUser($userId);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}

function getAllUsers() {
    try {
        $db = getDBConnection();
        
        $stmt = $db->query(
            "SELECT user_id, username, email, created_at, is_admin 
             FROM users
             ORDER BY created_at DESC"
        );
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'users' => $users]);
    } catch (PDOException $e) {
        error_log("Get All Users Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function getSingleUser($userId) {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare(
            "SELECT user_id, username, email, created_at, is_admin 
             FROM users 
             WHERE user_id = ?"
        );
        
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } catch (PDOException $e) {
        error_log("Get Single User Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function updateUser($userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $isAdmin = isset($data['is_admin']) ? (bool)$data['is_admin'] : null;
    
    try {
        $db = getDBConnection();
        
        $checkStmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $checkStmt->execute([$userId]);
        
        if ($checkStmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        if ($isAdmin !== null) {
            $stmt = $db->prepare("UPDATE users SET is_admin = ? WHERE user_id = ?");
            $result = $stmt->execute([$isAdmin, $userId]);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'User updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update user']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No valid update fields provided']);
        }
    } catch (PDOException $e) {
        error_log("Update User Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function deleteUser($userId) {
    try {
        $db = getDBConnection();
        
        if ($userId == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot delete yourself']);
            exit;
        }
        
        $db->beginTransaction();
        
        $deleteSessionsStmt = $db->prepare("DELETE FROM game_sessions WHERE user_id = ?");
        $deleteSessionsStmt->execute([$userId]);
        
        $deleteUserStmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $result = $deleteUserStmt->execute([$userId]);
        
        if ($result) {
            $db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'User and associated data deleted successfully'
            ]);
        } else {
            $db->rollBack();
            
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
    } catch (PDOException $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        
        error_log("Delete User Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}