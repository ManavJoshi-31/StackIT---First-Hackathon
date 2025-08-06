_<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized.");
}

$answer_id = $_GET['id'] ?? null;
$question_id = $_GET['question_id'] ?? null;

$stmt = $conn->prepare("SELECT * FROM answers WHERE id = ? AND user_id = ?");
$stmt->execute([$answer_id, $_SESSION['user_id']]);
if ($stmt->rowCount() === 0) {
    die("Permission denied.");
}

$conn->prepare("DELETE FROM answers WHERE id = ?")->execute([$answer_id]);

header("Location: view_question.php?id=" . $question_id);
exit;
