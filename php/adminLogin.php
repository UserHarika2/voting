<?php
// =====================================================
// ADMIN LOGIN API
// =====================================================

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (getRequestMethod() !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$data = getJsonBody();

if (!$data || !isset($data['username']) || !isset($data['password'])) {
    sendResponse(['success' => false, 'message' => 'Missing username or password'], 400);
}

$username = sanitize($data['username']);
$password = sanitize($data['password']);

// Prepare statement
$stmt = $conn->prepare("
    SELECT admin_id, username, email, full_name, role, is_active
    FROM admin_users
    WHERE username = ? AND is_active = TRUE
");

if (!$stmt) {
    sendResponse(['success' => false, 'message' => 'Database error'], 500);
}

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    logAction('ADMIN_LOGIN_FAILED', null, null, null, 'Admin not found: ' . $username);
    sendResponse([
        'success' => false,
        'message' => 'Invalid admin credentials.'
    ], 401);
}

$admin = $result->fetch_assoc();

// Verify password
if (!verifyPassword($password, $admin['password_hash'])) {
    logAction('ADMIN_LOGIN_FAILED', null, null, $admin['admin_id'], 'Wrong password');
    sendResponse([
        'success' => false,
        'message' => 'Invalid admin credentials.'
    ], 401);
}

// Update last login
$updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE admin_id = ?");
$updateStmt->bind_param('i', $admin['admin_id']);
$updateStmt->execute();

logAction('ADMIN_LOGIN_SUCCESS', null, null, $admin['admin_id'], 'Admin logged in');

sendResponse([
    'success' => true,
    'message' => 'Admin login successful',
    'admin' => [
        'adminId' => (int)$admin['admin_id'],
        'username' => $admin['username'],
        'fullName' => $admin['full_name'],
        'email' => $admin['email'],
        'role' => $admin['role']
    ]
], 200);

$stmt->close();
$conn->close();
