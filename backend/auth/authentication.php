<?php

function requireLogin() {
    session_start();
    
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'authenticated' => false,
            'message' => 'Authentication required'
        ]);
        exit();
    }
    
    return true;
}

function requireAdmin() {
    session_start();
    
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'authenticated' => false,
            'message' => 'Authentication required'
        ]);
        exit();
    }
    
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
        
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'authenticated' => true,
            'authorized' => false,
            'message' => 'Admin access required'
        ]);
        exit();
    }
    
    return true;
}