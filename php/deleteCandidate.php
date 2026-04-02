<?php
// =====================================================
// DELETE CANDIDATE API
// =====================================================

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (getRequestMethod() !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$data = getJsonBody();

if (!$data || !isset($data['candidateId'])) {
    sendResponse(['success' => false, 'message' => 'Candidate ID is required'], 400);
}

$candidateId = (int)$data['candidateId'];

// Validate candidate ID
if ($candidateId <= 0) {
    sendResponse(['success' => false, 'message' => 'Invalid candidate ID'], 400);
}

// Use stored procedure to delete candidate
$stmt = $conn->prepare("CALL sp_delete_candidate(?, @success, @message)");

if (!$stmt) {
    sendResponse(['success' => false, 'message' => 'Database error'], 500);
}

$stmt->bind_param('i', $candidateId);

if (!$stmt->execute()) {
    sendResponse(['success' => false, 'message' => 'Error deleting candidate'], 500);
}

$stmt->close();

// Get the output variables
$resultStmt = $conn->query("SELECT @success as success, @message as message");
$result = $resultStmt->fetch_assoc();

if ($result['success']) {
    sendResponse([
        'success' => true,
        'message' => 'Candidate deleted successfully'
    ], 200);
} else {
    sendResponse([
        'success' => false,
        'message' => $result['message']
    ], 400);
}

$conn->close();
