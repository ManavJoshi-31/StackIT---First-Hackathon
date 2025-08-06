<?php
session_start();
require 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$question_id = $_POST['question_id'] ?? null;
$vote_type = $_POST['vote_type'] ?? null;

if (!$question_id || !in_array($vote_type, ['upvote', 'downvote'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

// Delete existing vote if exists
$stmt = $conn->prepare("DELETE FROM votes WHERE user_id = ? AND question_id = ?");
$stmt->execute([$user_id, $question_id]);

// Insert new vote
$stmt = $conn->prepare("INSERT INTO votes (user_id, question_id, vote_type) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $question_id, $vote_type]);

// Recount votes
$stmt = $conn->prepare("SELECT 
    SUM(CASE WHEN vote_type = 'upvote' THEN 1 WHEN vote_type = 'downvote' THEN -1 ELSE 0 END) AS total 
    FROM votes WHERE question_id = ?");
$stmt->execute([$question_id]);
$total = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'new_count' => $total ?? 0,
    'user_vote' => $vote_type
]);
exit;
