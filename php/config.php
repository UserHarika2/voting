<?php
/**
 * VoteFlow - Database & Application Configuration
 * 
 * This file contains all configuration settings for the VoteFlow application.
 * Update these values according to your environment.
 */

// =====================================================
// DATABASE CONFIGURATION
// =====================================================

define('DB_CONFIG', [
    'host' => 'localhost',      // Database server
    'user' => 'voteflow_app',   // Database user
    'pass' => 'app_pass_123',   // Database password
    'name' => 'voteflow_app',   // Database name
    'port' => 3306,             // Database port
    'charset' => 'utf8mb4'      // Character set
]);

// =====================================================
// APPLICATION CONFIGURATION
// =====================================================

define('APP_CONFIG', [
    'name' => 'VoteFlow',
    'version' => '1.0.0',
    'environment' => 'development',  // 'development' or 'production'
    'debug_mode' => true,            // Enable/disable debug mode
    'log_queries' => true,           // Log all database queries
    'timezone' => 'UTC'
]);

// =====================================================
// SECURITY CONFIGURATION
// =====================================================

define('SECURITY_CONFIG', [
    'password_algorithm' => 'sha256',  // Use 'sha256' or 'bcrypt' (recommended)
    'session_timeout' => 3600,         // Session timeout in seconds (1 hour)
    'max_login_attempts' => 5,         // Max failed login attempts
    'login_attempt_reset' => 900,      // Reset attempts after (seconds)
    'enable_ip_logging' => true,       // Log IP addresses
    'enable_user_agent_logging' => true,
    'require_https' => false,          // Enforce HTTPS (set to true in production)
    'cors_allowed_origins' => [
        'http://localhost',
        'http://localhost/scm',
        'http://127.0.0.1'
    ]
]);

// =====================================================
// FEATURE FLAGS
// =====================================================

define('FEATURES', [
    'voting_enabled' => true,
    'voter_registration' => false,
    'results_public' => true,
    'one_vote_per_person' => true,
    'auto_refresh_enabled' => true,
    'auto_refresh_interval' => 30000,  // milliseconds
    'candidate_modification' => true,  // Allow admin to add/remove candidates
    'email_notifications' => false,    // TODO: Implement email notifications
    'export_results' => false          // TODO: Implement CSV/PDF export
]);

// =====================================================
// ELECTION SETTINGS
// =====================================================

define('ELECTION_DATES', [
    'start_date' => '2026-04-02',
    'end_date' => '2026-12-31',
    'registration_start' => '2026-03-01',
    'registration_end' => '2026-04-01'
]);

// =====================================================
// API ENDPOINTS
// =====================================================

define('API_ENDPOINTS', [
    'login' => '/scm/php/login.php',
    'admin_login' => '/scm/php/adminLogin.php',
    'get_candidates' => '/scm/php/getCandidates.php',
    'add_candidate' => '/scm/php/addCandidate.php',
    'delete_candidate' => '/scm/php/deleteCandidate.php',
    'vote' => '/scm/php/vote.php',
    'results' => '/scm/php/results.php'
]);

// =====================================================
// RESPONSE CODES
// =====================================================

define('RESPONSE_CODES', [
    'SUCCESS' => 200,
    'CREATED' => 201,
    'BAD_REQUEST' => 400,
    'UNAUTHORIZED' => 401,
    'FORBIDDEN' => 403,
    'NOT_FOUND' => 404,
    'CONFLICT' => 409,
    'SERVER_ERROR' => 500
]);

// =====================================================
// LOGGING CONFIGURATION
// =====================================================

define('LOGGING', [
    'enabled' => true,
    'level' => 'INFO',  // DEBUG, INFO, WARNING, ERROR, CRITICAL
    'log_file' => dirname(__DIR__) . '/logs/voteflow.log',
    'max_file_size' => 10485760,  // 10MB
    'retention_days' => 90
]);

// =====================================================
// EMAIL CONFIGURATION (For Future Use)
// =====================================================

define('EMAIL_CONFIG', [
    'enabled' => false,
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your_email@gmail.com',
    'password' => 'your_password',
    'from_address' => 'noreply@voteflow.com',
    'from_name' => 'VoteFlow System'
]);

// =====================================================
// DATABASE CONNECTION POOL
// =====================================================

class DatabasePool {
    private static $connections = [];
    private static $config = null;

    public static function initialize() {
        self::$config = DB_CONFIG;
    }

    public static function getConnection() {
        $key = md5(json_encode(DB_CONFIG));
        
        if (!isset(self::$connections[$key]) || !self::$connections[$key]) {
            try {
                self::$connections[$key] = new mysqli(
                    DB_CONFIG['host'],
                    DB_CONFIG['user'],
                    DB_CONFIG['pass'],
                    DB_CONFIG['name'],
                    DB_CONFIG['port']
                );

                if (self::$connections[$key]->connect_error) {
                    throw new Exception('Database connection failed: ' . self::$connections[$key]->connect_error);
                }

                self::$connections[$key]->set_charset(DB_CONFIG['charset']);
            } catch (Exception $e) {
                error_log('Database Error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed']);
                exit();
            }
        }

        return self::$connections[$key];
    }

    public static function closeAll() {
        foreach (self::$connections as $conn) {
            if ($conn) {
                $conn->close();
            }
        }
        self::$connections = [];
    }
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

/**
 * Get environment variable or default
 */
function getEnv($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?? $default;
}

/**
 * Check if in development mode
 */
function isDevelopment() {
    return APP_CONFIG['environment'] === 'development';
}

/**
 * Check if in production mode
 */
function isProduction() {
    return APP_CONFIG['environment'] === 'production';
}

/**
 * Log message
 */
function logMessage($message, $level = 'INFO') {
    if (!LOGGING['enabled']) {
        return;
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";

    if (!is_dir(dirname(LOGGING['log_file']))) {
        mkdir(dirname(LOGGING['log_file']), 0755, true);
    }

    file_put_contents(LOGGING['log_file'], $logEntry, FILE_APPEND);
}

/**
 * Set CORS headers
 */
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// =====================================================
// ERROR HANDLING
// =====================================================

if (APP_CONFIG['debug_mode']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ERROR | E_WARNING);
    ini_set('display_errors', 0);
}

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logMessage("Error [{$errno}]: {$errstr} in {$errfile}:{$errline}", 'ERROR');
    if (APP_CONFIG['debug_mode']) {
        echo json_encode(['error' => $errstr]);
    }
    return true;
});

// =====================================================
// INITIALIZATION
// =====================================================

DatabasePool::initialize();

// Set timezone
date_default_timezone_set(APP_CONFIG['timezone']);

// Set content type for all API responses
header('Content-Type: application/json; charset=' . DB_CONFIG['charset']);

// Set CORS headers for development
if (isDevelopment()) {
    setCorsHeaders();
}

?>
