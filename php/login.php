<?php
// =====================================================
// VOTER LOGIN API
// =====================================================

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (getRequestMethod() !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$data = getJsonBody();

if (!$data || !isset($data['voterId']) || !isset($data['password'])) {
    sendResponse(['success' => false, 'message' => 'Missing voter ID or password'], 400);
}

$voterId = sanitize($data['voterId']);
$password = sanitize($data['password']);

// Validate voter ID format
if (!isValidVoterId($voterId)) {
    logAction('LOGIN_FAILED', $voterId, null, null, 'Invalid voter ID format');
    sendResponse(['success' => false, 'message' => 'Invalid voter ID format'], 400);
}

// Prepare statement to prevent SQL injection
$stmt = $conn->prepare("
    SELECT voter_id, voter_name, password_hash, has_voted, voted_for, email
    FROM app_voters
    WHERE voter_id = ?
");

if (!$stmt) {
    logAction('LOGIN_ERROR', null, null, null, 'Database error: ' . $conn->error);
    sendResponse(['success' => false, 'message' => 'Database error'], 500);
}

$stmt->bind_param('s', $voterId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    logAction('LOGIN_FAILED', $voterId, null, null, 'Voter not found');
    sendResponse([
        'success' => false,
        'message' => 'Invalid credentials. Please try again.'
    ], 401);
}

$voter = $result->fetch_assoc();

// Verify password
if (!verifyPassword($password, $voter['password_hash'])) {
    logAction('LOGIN_FAILED', $voterId, null, null, 'Wrong password');
    sendResponse([
        'success' => false,
        'message' => 'Invalid credentials. Please try again.'
    ], 401);
}

// Update last login
$updateStmt = $conn->prepare("UPDATE voters SET last_login = NOW() WHERE voter_id = ?");
$updateStmt->bind_param('s', $voterId);
$updateStmt->execute();

logAction('LOGIN_SUCCESS', $voterId, null, null, 'Voter logged in');

sendResponse([
    'success' => true,
    'message' => 'Login successful',
    'voter' => [
        'voterId' => $voter['voter_id'],
        'voterName' => $voter['voter_name'],
        'email' => $voter['email'],
        'hasVoted' => (bool)$voter['has_voted'],
        'votedFor' => $voter['voted_for']
    ]
], 200);

$stmt->close();
$conn->close();