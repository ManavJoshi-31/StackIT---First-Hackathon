<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$answer_id = $_GET['id'] ?? null;

if (!is_numeric($answer_id)) {
    die("Invalid answer ID.");
}

// Fetch the answer and check ownership
$stmt = $conn->prepare("SELECT * FROM answers WHERE id = ?");
$stmt->execute([$answer_id]);
$answer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$answer) {
    die("Answer not found.");
}

if ($answer['user_id'] != $user_id) {
    die("You do not have permission to edit this answer.");
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    if (empty($content)) {
        $errors[] = "Answer cannot be empty.";
    } else {
        $update = $conn->prepare("UPDATE answers SET content = ? WHERE id = ?");
        $update->execute([$content, $answer_id]);

        header("Location: view_question.php?id=" . $answer['question_id']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Answer - StackIt</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background: #0f1117;
      color: #fff;
      font-family: "Segoe UI", sans-serif;
    }

    .container {
      max-width: 700px;
      margin: 60px auto;
      background-color: #1a1c23;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
    }

    textarea {
      background-color: #121417;
      color: #fff;
      border: 1px solid #444;
      border-radius: 8px;
      padding: 15px;
      resize: vertical;
      min-height: 150px;
    }

    .btn-primary {
      background: linear-gradient(145deg, #0066cc, #003366);
      border: none;
    }

    .btn-primary:hover {
      background: linear-gradient(145deg, #004d99, #002244);
    }

    .btn-secondary {
      background-color: #444;
      border: none;
    }

    .alert {
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

<div class="container">
  <h3>Edit Your Answer</h3>

  <?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endforeach; ?>

  <form method="POST">
    <div class="mb-3">
      <textarea name="content" class="form-control" required><?= htmlspecialchars($answer['content']) ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Update Answer</button>
    <a href="view_question.php?id=<?= $answer['question_id'] ?>" class="btn btn-secondary ms-2">Cancel</a>
  </form>
</div>

</body>
</html>
