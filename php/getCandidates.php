<?php
// =====================================================
// GET CANDIDATES API
// =====================================================

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (getRequestMethod() !== 'GET') {
    sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Fetch all candidates
$stmt = $conn->prepare("
    SELECT candidate_id, candidate_name, party_name, description, vote_count
    FROM candidates
    ORDER BY candidate_name ASC
");

if (!$stmt) {
    sendResponse(['success' => false, 'message' => 'Database error'], 500);
}

$stmt->execute();
$result = $stmt->get_result();

$candidates = [];
while ($row = $result->fetch_assoc()) {
    $candidates[] = [
        'id' => (int)$row['candidate_id'],
        'name' => $row['candidate_name'],
        'party' => $row['party_name'],
        'description' => $row['description'],
        'voteCount' => (int)$row['vote_count']
    ];
}

sendResponse([
    'success' => true,
    'candidates' => $candidates
], 200);

$stmt->close();
$conn->close();