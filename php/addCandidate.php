<?php
// =====================================================
// ADD CANDIDATE API
// =====================================================

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (getRequestMethod() !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$data = getJsonBody();

if (!$data || !isset($data['name'])) {
    sendResponse(['success' => false, 'message' => 'Candidate name is required'], 400);
}

$name = sanitize($data['name']);
$party = isset($data['party']) ? sanitize($data['party']) : 'Independent';
$description = isset($data['description']) ? sanitize($data['description']) : NULL;

// Validate candidate name
if (strlen($name) < 2) {
    sendResponse(['success' => false, 'message' => 'Candidate name must be at least 2 characters'], 400);
}

if (strlen($name) > 150) {
    sendResponse(['success' => false, 'message' => 'Candidate name is too long'], 400);
}

// Use stored procedure to add candidate
$stmt = $conn->prepare("CALL sp_add_candidate(?, ?, ?, @success, @message)");

if (!$stmt) {
    sendResponse(['success' => false, 'message' => 'Database error'], 500);
}

$stmt->bind_param('sss', $name, $party, $description);

if (!$stmt->execute()) {
    sendResponse(['success' => false, 'message' => 'Error adding candidate'], 500);
}

$stmt->close();

// Get the output variables
$resultStmt = $conn->query("SELECT @success as success, @message as message");
$result = $resultStmt->fetch_assoc();

if ($result['success']) {
    // Fetch the newly added candidate
    $selectStmt = $conn->prepare("
        SELECT candidate_id, candidate_name, party_name, vote_count
        FROM candidates
        WHERE candidate_name = ?
        LIMIT 1
    ");
    $selectStmt->bind_param('s', $name);
    $selectStmt->execute();
    $candidateResult = $selectStmt->get_result();
    $candidate = $candidateResult->fetch_assoc();
    
    sendResponse([
        'success' => true,
        'message' => 'Candidate added successfully',
        'candidate' => [
            'id' => (int)$candidate['candidate_id'],
            'name' => $candidate['candidate_name'],
            'party' => $candidate['party_name'],
            'voteCount' => (int)$candidate['vote_count']
        ]
    ], 201);
    
    $selectStmt->close();
} else {
    sendResponse([
        'success' => false,
        'message' => $result['message']
    ], 400);
}

$conn->close();
