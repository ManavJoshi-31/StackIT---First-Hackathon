<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Login required.");
}

$user_id = $_SESSION['user_id'];
$vote_type = $_POST['vote_type'] ?? null;
$answer_id = $_POST['answer_id'] ?? null;
$question_id = $_POST['question_id'] ?? null;

if (!in_array($vote_type, ['upvote', 'downvote'])) {
    die("Invalid vote.");
}

if (!$answer_id && !$question_id) {
    die("Missing target.");
}

// Delete existing vote
if ($answer_id) {
    $check = $conn->prepare("SELECT vote_type FROM votes WHERE user_id = ? AND answer_id = ?");
    $check->execute([$user_id, $answer_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing && $existing['vote_type'] === $vote_type) {
        // Same vote exists, remove
        $delete = $conn->prepare("DELETE FROM votes WHERE user_id = ? AND answer_id = ?");
        $delete->execute([$user_id, $answer_id]);
    } else {
        // Insert/update
        $stmt = $conn->prepare("INSERT INTO votes (user_id, answer_id, vote_type)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE vote_type = VALUES(vote_type)");
        $stmt->execute([$user_id, $answer_id, $vote_type]);
    }

    header("Location: view_question.php?id=" . $_POST['question_id']);
    exit;
}

if ($question_id) {
    $check = $conn->prepare("SELECT vote_type FROM votes WHERE user_id = ? AND question_id = ?");
    $check->execute([$user_id, $question_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing && $existing['vote_type'] === $vote_type) {
        $delete = $conn->prepare("DELETE FROM votes WHERE user_id = ? AND question_id = ?");
        $delete->execute([$user_id, $question_id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO votes (user_id, question_id, vote_type)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE vote_type = VALUES(vote_type)");
        $stmt->execute([$user_id, $question_id, $vote_type]);
    }

    header("Location: view_question.php?id=" . $question_id);
    exit;
}
?>
