<?php
session_start();
require 'config.php';

$loggedIn = isset($_SESSION['user']);
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['user'] ?? '';

$sort = $_GET['sort'] ?? 'newest';
$filter = $_GET['filter'] ?? 'all';

$orderBy = ($sort === 'oldest') ? 'ASC' : 'DESC';
$filterCondition = ($filter === 'unanswered') ? 'HAVING answer_count = 0' : '';

// Fetch questions and vote data
$stmt = $conn->prepare("
    SELECT q.id, q.title, q.description, q.created_at, u.username,
    (SELECT COUNT(*) FROM answers WHERE question_id = q.id) AS answer_count,
    (SELECT SUM(CASE WHEN vote_type = 'upvote' THEN 1 WHEN vote_type = 'downvote' THEN -1 ELSE 0 END) 
     FROM votes WHERE question_id = q.id) AS vote_count
    FROM questions q
    JOIN users u ON q.user_id = u.user_id
    GROUP BY q.id
    $filterCondition
    ORDER BY q.created_at $orderBy
");
$stmt->execute();
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user votes for questions
$userVotes = [];
if ($user_id) {
    $stmt = $conn->prepare("SELECT question_id, vote_type FROM votes WHERE user_id = ? AND question_id IS NOT NULL");
    $stmt->execute([$user_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $vote) {
        $userVotes[$vote['question_id']] = $vote['vote_type'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>StackIt - Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
  body {
    background: linear-gradient(135deg, #0a0a0a, #1e1e1e);
    color: #f0f0f0;
    font-family: "Segoe UI", sans-serif;
  }

  .navbar {
    background: linear-gradient(145deg, #000, #1a1a1a);
    border-bottom: 1px solid #30363d;
    padding: 12px 32px;
  }

  .navbar-brand {
    font-size: 1.8rem;
    font-weight: bold;
    color: #58a6ff !important;
  }

  .container {
    max-width: 1100px;
    margin: auto;
    padding: 40px 20px;
  }

  .form-select {
    background-color: rgba(255, 255, 255, 0.05);
    color: #f0f0f0;
    border: 1px solid rgba(255, 255, 255, 0.15);
  }

  .card {
    background-color: #181818;
    border-radius: 24px;
    padding: 20px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 24px;
    transition: 0.3s ease;
  }

  .card:hover {
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.05);
  }

  .btn-outline-light {
    border-color: #fff;
    color: #fff;
  }

  .btn-outline-light:hover {
    background-color: #fff;
    color: #000;
  }

  .btn-vote {
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    padding: 0 8px;
    color: #bbb;
  }

  .btn-vote.upvoted {
    color: #00ff99;
  }

  .btn-vote.downvoted {
    color: #ff5e57;
  }

  .vote-count {
    font-weight: bold;
    font-size: 18px;
    min-width: 30px;
    display: inline-block;
    text-align: center;
    color: #ddd;
  }

  .vote-count.positive {
    color: #00e6ff;
  }

  .vote-count.negative {
    color: #ffc66e;
  }

  .card-title {
    font-size: 1.5rem;
    color: #ffffff;
  }

  .card-text {
    color: #dddddd;
    font-size: 1.05rem;
    margin-top: 10px;
  }

  .text-secondary {
    color: #bbbbbb !important;
  }

  a.btn-sm {
    font-size: 0.85rem;
  }
</style>

</head>
<body>

<nav class="navbar navbar-expand-lg">
  <a class="navbar-brand" href="dashboard.php">StackIt</a>
  <div class="ms-auto">
    <?php if ($loggedIn): ?>
      <a href="ask_question.php" class="btn btn-outline-light btn-sm me-2">Ask New Question</a>
      <span class="text-light me-2">👋 <?= htmlspecialchars($username) ?></span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
      <a href="register.php" class="btn btn-outline-light btn-sm">Register</a>
    <?php endif; ?>
  </div>
</nav>

<div class="container mt-4">
  <form method="GET" class="d-flex justify-content-start mb-4 flex-wrap gap-3">
    <select name="sort" class="form-select form-select-sm d-inline-block w-auto me-2" onchange="this.form.submit()">
      <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
      <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest</option>
    </select>

    <select name="filter" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
      <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
      <option value="unanswered" <?= $filter === 'unanswered' ? 'selected' : '' ?>>Unanswered</option>
    </select>
  </form>

  <?php foreach ($questions as $q): 
    $voteCount = $q['vote_count'] ?? 0;
    $userVote = $userVotes[$q['id']] ?? null;
  ?>
    <div class="card">
      <div class="d-flex justify-content-between">
        <div>
          <h5 class="card-title"><?= htmlspecialchars($q['title']) ?></h5>
          <p class="card-text"><?= nl2br(htmlspecialchars(substr($q['description'], 0, 150))) ?>...</p>
        </div>
        <div class="text-center">
          <form method="POST" action="vote.php" style="display:inline-block;">
            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
            <input type="hidden" name="vote_type" value="upvote">
            <button type="submit" class="btn-vote <?= $userVote === 'upvote' ? 'upvoted' : '' ?>">🔼</button>
          </form>

          <div class="vote-count <?= $voteCount > 0 ? 'positive' : ($voteCount < 0 ? 'negative' : '') ?>">
            <?= $voteCount ?>
          </div>

          <form method="POST" action="vote.php" style="display:inline-block;">
            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
            <input type="hidden" name="vote_type" value="downvote">
            <button type="submit" class="btn-vote <?= $userVote === 'downvote' ? 'downvoted' : '' ?>">🔽</button>
          </form>
        </div>
      </div>
      <div class="d-flex justify-content-between mt-3">
        <small class="text-secondary">👤 <?= htmlspecialchars($q['username']) ?></small>
        <a href="view_question.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-light"><?= $q['answer_count'] ?> Answer(s)</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
</body>
</html>
