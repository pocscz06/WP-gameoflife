<?php
// HELPER FUNCTIONS
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function displayErrors($errors) {
    if (!empty($errors) && is_array($errors)) {
        $output = '<div class="alert alert-danger"><ul>';
        
        foreach ($errors as $error) {
            $output .= '<li>' . sanitizeInput($error) . '</li>';
        }
        
        $output .= '</ul></div>';
        return $output;
    }
    
    return '';
}

function displaySuccess($message) {
    if (!empty($message)) {
        return '<div class="alert alert-success">' . sanitizeInput($message) . '</div>';
    }
    
    return '';
}

function startGameSession($userId, $patternUsed = null) {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("INSERT INTO game_sessions (user_id, pattern_used) VALUES (?, ?)");
        $result = $stmt->execute([$userId, $patternUsed]);
        
        if ($result) {
            return $db->lastInsertId();
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Start Game Session Error: " . $e->getMessage());
        return false;
    }
}

function endGameSession($sessionId, $generations = 0, $maxPopulation = 0) {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("UPDATE game_sessions 
                             SET end_time = CURRENT_TIMESTAMP,
                                 generations_reached = ?,
                                 max_population = ?
                             WHERE session_id = ?");
        
        return $stmt->execute([$generations, $maxPopulation, $sessionId]);
    } catch (PDOException $e) {
        error_log("End Game Session Error: " . $e->getMessage());
        return false;
    }
}

function getUserGameHistory($userId, $limit = 10) {
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("SELECT * FROM game_sessions 
                             WHERE user_id = ? 
                             ORDER BY start_time DESC 
                             LIMIT ?");
        
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get User History Error: " . $e->getMessage());
        return [];
    }
}