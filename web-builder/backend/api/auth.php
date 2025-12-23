<?php
require_once 'api_base.php';
require_once '../models/User.php';
require_once '../utils/JwtUtil.php';
//require_once '../utils/Database.php';

// Initialize user model
$userModel = new User();

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

// Route based on endpoint
$endpoint = isset($_GET['action']) ? $_GET['action'] : '';

switch ($endpoint) {
    case 'login':
        if ($method !== 'POST') {
            sendError("Method not allowed", 405);
        }
        handleLogin();
        break;
        
    case 'register':
        if ($method !== 'POST') {
            sendError("Method not allowed", 405);
        }
        handleRegister();
        break;
        
    case 'me':
        if ($method !== 'GET') {
            sendError("Method not allowed", 405);
        }
        handleGetCurrentUser();
        break;
        
    case 'logout':
        // Logout is client-side with JWT
        sendResponse([
            'success' => true,
            'message' => 'Logout successful'
        ]);
        break;
        
    default:
        sendError("Endpoint not found", 404);
}

/**
 * Handle user login
 */
function handleLogin() {
    global $userModel;
    
    $data = getJsonData();
    validateRequiredFields($data, ['username', 'password']);
    
    $user = $userModel->findByUsername($data['username']);
    
    if (!$user || !password_verify($data['password'], $user['password'])) {
        sendError("Invalid username or password", 401);
        return;
    }
    
    // Generate JWT token
    $token = JwtUtil::generateToken($user);
    
    // Don't include password in response
    unset($user['password']);
    
    sendResponse([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user
    ]);
}

/**
 * Handle user registration
 */
function handleRegister() {
    global $userModel;
    
    $data = getJsonData();
    validateRequiredFields($data, ['username', 'password', 'email']);
    
    // Check if username already exists
    $existingUser = $userModel->findByUsername($data['username']);
    if ($existingUser) {
        sendError("Username already taken", 409);
        return;
    }
    
    // Create the user
    $userId = $userModel->create($data);
    
    if (!$userId) {
        sendError("Failed to create user", 500);
        return;
    }
    
    sendResponse([
        'success' => true,
        'message' => 'Registration successful',
        'user_id' => $userId
    ]);
}

/**
 * Handle get current user info
 */
function handleGetCurrentUser() {
    global $userModel;
    
    // Get JWT token from Authorization header
    $token = JwtUtil::getBearerToken();
    
    if (!$token) {
        sendError("Unauthorized: No token provided", 401);
        return;
    }
    
    // Validate token
    $payload = JwtUtil::validateToken($token);
    if (!$payload) {
        sendError("Unauthorized: Invalid token", 401);
        return;
    }
    
    // Get user from database
    $userId = $payload['user_id'];
    $user = $userModel->findById($userId);
    
    if (!$user) {
        sendError("Unauthorized: User not found", 401);
        return;
    }
    
    // Don't include password in response
    unset($user['password']);
    
    sendResponse([
        'success' => true,
        'user' => $user
    ]);
}