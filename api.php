<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once 'db_config.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();

    // ---- VOTER LOGIN ----
    if ($action === 'voterLogin' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $voter_id = trim($input['voter_id'] ?? '');
        $password = $input['password'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM tbl_electors WHERE voter_id = ? AND pass_hash = ?");
        $stmt->execute([$voter_id, md5($password)]); // simple demo hash
        if ($stmt->fetch()) {
            echo json_encode(['success' => true, 'message' => 'Login successful']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
        exit;
    }

    // ---- GET CANDIDATES ----
    if ($action === 'getCandidates') {
        $stmt = $pdo->query("SELECT id, name, party FROM tbl_candidates ORDER BY id");
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'candidates' => $candidates]);
        exit;
    }

    // ---- CAST VOTE ----
    if ($action === 'castVote' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $voter_id = $input['voter_id'] ?? '';
        $candidate_id = $input['candidate_id'] ?? 0;
        // check if voter exists and has not voted
        $pdo->beginTransaction();
        $checkVoter = $pdo->prepare("SELECT has_voted FROM tbl_electors WHERE voter_id = ? FOR UPDATE");
        $checkVoter->execute([$voter_id]);
        $voter = $checkVoter->fetch();
        if (!$voter) {
            echo json_encode(['success' => false, 'message' => 'Voter not found']);
            $pdo->rollBack();
            exit;
        }
        if ($voter['has_voted'] == 1) {
            echo json_encode(['success' => false, 'message' => 'You have already voted!']);
            $pdo->rollBack();
            exit;
        }
        // update candidate votes
        $updateVote = $pdo->prepare("UPDATE tbl_candidates SET vote_count = vote_count + 1 WHERE id = ?");
        $updateVote->execute([$candidate_id]);
        // mark voter as voted
        $markVoted = $pdo->prepare("UPDATE tbl_electors SET has_voted = 1 WHERE voter_id = ?");
        $markVoted->execute([$voter_id]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Vote cast successfully!']);
        exit;
    }

    // ---- HAS VOTED CHECK ----
    if ($action === 'hasVoted') {
        $voter_id = $_GET['voter_id'] ?? '';
        $stmt = $pdo->prepare("SELECT has_voted FROM tbl_electors WHERE voter_id = ?");
        $stmt->execute([$voter_id]);
        $row = $stmt->fetch();
        echo json_encode(['voted' => $row ? (bool)$row['has_voted'] : false]);
        exit;
    }

    // ---- ADMIN LOGIN ----
    if ($action === 'adminLogin' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $user = $input['username'] ?? '';
        $pwd = $input['password'] ?? '';
        if ($user === 'admin' && $pwd === 'admin123') {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    // ---- ADD CANDIDATE ----
    if ($action === 'addCandidate' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $party = trim($input['party'] ?? '');
        if (!$name || !$party) {
            echo json_encode(['success' => false, 'message' => 'Name and Party required']);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO tbl_candidates (name, party, vote_count) VALUES (?, ?, 0)");
        $stmt->execute([$name, $party]);
        echo json_encode(['success' => true, 'message' => 'Candidate added']);
        exit;
    }

    // ---- GET RESULTS (with vote counts) ----
    if ($action === 'getResults') {
        $stmt = $pdo->query("SELECT id, name, party, vote_count as votes FROM tbl_candidates ORDER BY vote_count DESC");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>