<?php
// =====================================================
// DATABASE CONNECTION
// =====================================================

require_once __DIR__ . '/config.php';

// Create connection using config
$conn = mysqli_connect(
    DB_CONFIG['host'],
    DB_CONFIG['user'],
    DB_CONFIG['pass'],
    DB_CONFIG['name'],
    DB_CONFIG['port']
);

// Check connection
if (!$conn) {
    http_response_code(500);
    logMessage('Database connection failed: ' . mysqli_connect_error(), 'ERROR');
    echo json_encode([
        'success' => false,
        'message' => isDevelopment() ? 'Database connection failed: ' . mysqli_connect_error() : 'Database connection failed'
    ]);
    exit();
}

// Set charset to configured charset
mysqli_set_charset($conn, DB_CONFIG['charset']);

// =====================================================
// HELPER FUNCTIONS
// =====================================================

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Get request method
 */
function getRequestMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Get JSON request body
 */
function getJsonBody() {
    return json_decode(file_get_contents('php://input'), true);
}

/**
 * Get client IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

/**
 * Hash password using SHA256
 */
function hashPassword($password) {
    return hash('sha256', $password);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return hash('sha256', $password) === $hash;
}

/**
 * Sanitize input
 */
function sanitize($input) {
    if (is_string($input)) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

/**
 * Validate voter ID format
 */
function isValidVoterId($voterId) {
    return preg_match('/^V\d{3,}$/', $voterId);
}

/**
 * Log action to voting_logs table
 */
function logAction($action, $voterId = null, $candidateId = null, $adminId = null, $details = null) {
    global $conn;
    
    if (!SECURITY_CONFIG['enable_ip_logging']) {
        $ipAddress = null;
    } else {
        $ipAddress = getClientIP();
    }
    
    $stmt = $conn->prepare("
        INSERT INTO app_voting_logs (action, voter_id, admin_id, candidate_id, action_details, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt) {
        $stmt->bind_param(
            'ssiiis',
            $action,
            $voterId,
            $adminId,
            $candidateId,
            $details,
            $ipAddress
        );
        $result = $stmt->execute();
        $stmt->close();
        
        if (APP_CONFIG['log_queries']) {
            logMessage("Action logged: {$action} by {$voterId}", 'DEBUG');
        }
        
        return $result;
    }
    
    return false;
}
