<?php
// =====================================================
// GET ELECTION RESULTS API
// =====================================================

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (getRequestMethod() !== 'GET') {
    sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Get results from view
$stmt = $conn->prepare("SELECT * FROM vw_election_results");

if (!$stmt) {
    sendResponse(['success' => false, 'message' => 'Database error'], 500);
}

$stmt->execute();
$result = $stmt->get_result();

$results = [];
$totalVotes = 0;

while ($row = $result->fetch_assoc()) {
    $results[] = [
        'candidateId' => (int)$row['candidate_id'],
        'name' => $row['candidate_name'],
        'party' => $row['party_name'],
        'votes' => (int)$row['vote_count'],
        'percentage' => (float)$row['vote_percentage'],
        'rank' => (int)$row['ranking'],
        'status' => $row['status']
    ];
    $totalVotes += (int)$row['vote_count'];
}

// Get overall statistics
$statsStmt = $conn->prepare("SELECT * FROM vw_voter_statistics");
$statsStmt->execute();
$statsResult = $statsStmt->get_result();
$stats = $statsResult->fetch_assoc();

sendResponse([
    'success' => true,
    'results' => $results,
    'statistics' => [
        'totalVotes' => $totalVotes,
        'totalVoters' => (int)$stats['total_voters'],
        'votersVoted' => (int)$stats['voters_voted'],
        'votersNotVoted' => (int)$stats['voters_not_voted'],
        'participationRate' => (float)$stats['participation_rate']
    ]
], 200);

$stmt->close();
$statsStmt->close();
$conn->close();