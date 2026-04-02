<?php
// =====================================================
// VOTE SUBMISSION API
// =====================================================

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (getRequestMethod() !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$data = getJsonBody();

if (!$data || !isset($data['voterId']) || !isset($data['candidateId'])) {
    sendResponse(['success' => false, 'message' => 'Missing voter ID or candidate ID'], 400);
}

$voterId = sanitize($data['voterId']);
$candidateId = (int)$data['candidateId'];
$ipAddress = getClientIP();

// Validate voter ID
if (!isValidVoterId($voterId)) {
    sendResponse(['success' => false, 'message' => 'Invalid voter ID format'], 400);
}

// Validate candidate ID
if ($candidateId <= 0) {
    sendResponse(['success' => false, 'message' => 'Invalid candidate ID'], 400);
}

// Use stored procedure to cast vote safely
$stmt = $conn->prepare("CALL sp_cast_vote(?, ?, ?, @success, @message)");

if (!$stmt) {
    sendResponse(['success' => false, 'message' => 'Database error'], 500);
}

$stmt->bind_param('sis', $voterId, $candidateId, $ipAddress);

if (!$stmt->execute()) {
    logAction('VOTE_ERROR', $voterId, $candidateId, null, $stmt->error);
    sendResponse(['success' => false, 'message' => 'Error processing vote'], 500);
}

$stmt->close();

// Get the output variables
$resultStmt = $conn->query("SELECT @success as success, @message as message");
$result = $resultStmt->fetch_assoc();

if ($result['success']) {
    sendResponse([
        'success' => true,
        'message' => 'Vote recorded successfully!'
    ], 200);
} else {
    logAction('VOTE_FAILED', $voterId, $candidateId, null, $result['message']);
    sendResponse([
        'success' => false,
        'message' => $result['message']
    ], 403);
}

$conn->close();