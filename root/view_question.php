<?php
session_start();
require 'config.php';

$username = $_SESSION['user'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

// Validate question ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid question ID.");
}
$question_id = (int) $_GET['id'];

// Fetch question
$stmt = $conn->prepare("
    SELECT q.id, q.title, q.description, q.created_at, u.username
    FROM questions q
    JOIN users u ON q.user_id = u.user_id
    WHERE q.id = ?
");
$stmt->execute([$question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$question) {
    die("Question not found.");
}

// Fetch question vote count
$voteStmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN vote_type = 'upvote' THEN 1 WHEN vote_type = 'downvote' THEN -1 ELSE 0 END) AS total 
    FROM votes WHERE question_id = ?
");
$voteStmt->execute([$question_id]);
$questionVotes = $voteStmt->fetchColumn() ?? 0;

// Check if current user has voted on this question
$userQuestionVote = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT vote_type FROM votes WHERE user_id = ? AND question_id = ?");
    $stmt->execute([$user_id, $question_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $userQuestionVote = $row['vote_type'] ?? null;
}

// Handle answer submission
$errors = [];
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['content']) && $user_id) {
    $content = trim($_POST['content']);
    if (empty($content)) {
        $errors[] = "Answer cannot be empty.";
    } else {
        $stmt = $conn->prepare("INSERT INTO answers (question_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$question_id, $user_id, $content]);
        header("Location: view_question.php?id=" . $question_id);
        exit;
    }
}

// Fetch answers
$stmt = $conn->prepare("
    SELECT a.id AS answer_id, a.content, a.created_at, a.user_id, u.username
    FROM answers a
    JOIN users u ON a.user_id = u.user_id
    WHERE a.question_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$question_id]);
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title><?= htmlspecialchars($question['title']) ?> - StackIt</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background: #0e1117;
      color: #e6edf3;
      font-family: 'Segoe UI', sans-serif;
    }

    .navbar {
      background-color: #161b22 !important;
      border-bottom: 1px solid #30363d;
      padding: 12px 32px;
    }

    .navbar-brand {
      font-size: 1.6rem;
      font-weight: bold;
      color: #58a6ff !important;
    }

    .btn-outline-light {
      border-color: #58a6ff;
      color: #58a6ff;
    }

    .btn-outline-light:hover {
      background-color: #58a6ff;
      color: #0e1117;
      transition: all 0.3s ease-in-out;
    }

    .container {
      max-width: 900px;
      margin: 40px auto;
    }

    .card-style {
      background-color: #161b22;
      border-radius: 20px;
      padding: 25px;
      margin-bottom: 30px;
      border: 1px solid #30363d;
      box-shadow: 0 0 30px rgba(255,255,255,0.03);
    }

    .btn-vote {
      border: none;
      background: transparent;
      color: #58a6ff;
      font-size: 20px;
      margin: 0 10px;
      cursor: pointer;
    }

    .btn-vote.active {
      color: #00ffcc;
    }

    .btn-action {
      font-size: 13px;
      margin-left: 10px;
    }

    textarea.form-control {
      background-color: #0d1117;
      color: #fff;
      border: 1px solid #30363d;
      border-radius: 10px;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <a class="navbar-brand" href="dashboard.php">StackIt</a>
  <div class="ms-auto">
    <?php if ($user_id): ?>
      <span class="text-light me-3">👋 <?= htmlspecialchars($username) ?></span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
      <a href="register.php" class="btn btn-outline-light btn-sm">Register</a>
    <?php endif; ?>
  </div>
</nav>

<div class="container">

  <!-- Back to Dashboard Link -->
  <div class="mb-4">
    <a href="dashboard.php" class="btn btn-outline-light btn-sm">← Back to Dashboard</a>
  </div>

  <!-- Question -->
  <div class="card-style">
    <h2><?= htmlspecialchars($question['title']) ?></h2>
    <div class="mb-2 text-secondary">Asked by <?= htmlspecialchars($question['username']) ?> on <?= $question['created_at'] ?></div>
    <p><?= nl2br(htmlspecialchars($question['description'])) ?></p>

    <div class="mt-3 d-flex align-items-center">
      <form method="POST" action="vote.php" class="me-2">
        <input type="hidden" name="question_id" value="<?= $question_id ?>">
        <input type="hidden" name="vote_type" value="upvote">
        <button class="btn-vote <?= $userQuestionVote === 'upvote' ? 'active' : '' ?>">🔼</button>
      </form>

      <strong><?= $questionVotes ?></strong>

      <form method="POST" action="vote.php" class="ms-2">
        <input type="hidden" name="question_id" value="<?= $question_id ?>">
        <input type="hidden" name="vote_type" value="downvote">
        <button class="btn-vote <?= $userQuestionVote === 'downvote' ? 'active' : '' ?>">🔽</button>
      </form>
    </div>
  </div>

  <!-- Answers -->
  <div class="card-style">
    <h4><?= count($answers) ?> Answer(s)</h4>
    <?php foreach ($answers as $ans): ?>
      <?php
        $voteStmt = $conn->prepare("SELECT SUM(CASE WHEN vote_type='upvote' THEN 1 WHEN vote_type='downvote' THEN -1 ELSE 0 END) AS total FROM votes WHERE answer_id = ?");
        $voteStmt->execute([$ans['answer_id']]);
        $answerVotes = $voteStmt->fetchColumn() ?? 0;

        $userAnswerVote = null;
        if ($user_id) {
          $stmt = $conn->prepare("SELECT vote_type FROM votes WHERE user_id = ? AND answer_id = ?");
          $stmt->execute([$user_id, $ans['answer_id']]);
          $row = $stmt->fetch(PDO::FETCH_ASSOC);
          $userAnswerVote = $row['vote_type'] ?? null;
        }
      ?>
      <div class="card-style bg-dark mb-3">
        <div class="text-secondary mb-1">Answered by <?= htmlspecialchars($ans['username']) ?> on <?= $ans['created_at'] ?></div>
        <p><?= nl2br(htmlspecialchars($ans['content'])) ?></p>

        <div class="d-flex align-items-center mt-2">
          <form method="POST" action="vote.php" class="me-2">
            <input type="hidden" name="answer_id" value="<?= $ans['answer_id'] ?>">
            <input type="hidden" name="vote_type" value="upvote">
            <button class="btn-vote <?= $userAnswerVote === 'upvote' ? 'active' : '' ?>">🔼</button>
          </form>

          <strong><?= $answerVotes ?></strong>

          <form method="POST" action="vote.php" class="ms-2">
            <input type="hidden" name="answer_id" value="<?= $ans['answer_id'] ?>">
            <input type="hidden" name="vote_type" value="downvote">
            <button class="btn-vote <?= $userAnswerVote === 'downvote' ? 'active' : '' ?>">🔽</button>
          </form>

          <?php if ($user_id == $ans['user_id']): ?>
            <a href="edit_answer.php?id=<?= $ans['answer_id'] ?>" class="btn btn-warning btn-sm btn-action">Edit</a>
            <a href="delete_answer.php?id=<?= $ans['answer_id'] ?>&question_id=<?= $question_id ?>" onclick="return confirm('Delete this answer?')" class="btn btn-danger btn-sm btn-action">Delete</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Answer Form -->
  <div class="card-style">
    <h5>Your Answer</h5>
    <?php if (!$user_id): ?>
      <div class="alert alert-warning">Please <a href="login.php">login</a> to post an answer.</div>
    <?php else: ?>
      <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endforeach; ?>

      <form method="POST">
        <div class="mb-3">
          <textarea name="content" class="form-control" rows="4" placeholder="Write your answer..." required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Submit Answer</button>
      </form>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
