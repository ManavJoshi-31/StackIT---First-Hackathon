<?php
session_start();
require 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['user'] ?? 'User';
$errors = [];

// Fetch all tags from DB
$tagQuery = $conn->query("SELECT name FROM tags ORDER BY name ASC");
$tagList = $tagQuery->fetchAll(PDO::FETCH_COLUMN);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $submittedTags = explode(",", $_POST['tags'] ?? '');
    $submittedTags = array_map('trim', $submittedTags);

    if (empty($title) || empty($description)) {
        $errors[] = "Title and description are required.";
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Insert question
            $stmt = $conn->prepare("INSERT INTO questions (user_id, title, description) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $title, $description]);
            $question_id = $conn->lastInsertId();

            // Link tags if they exist
            foreach ($submittedTags as $tagName) {
                if (!$tagName) continue;
                $stmt = $conn->prepare("SELECT id FROM tags WHERE name = ?");
                $stmt->execute([$tagName]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($row) {
                    $tag_id = $row['id'];
                    $stmt = $conn->prepare("INSERT INTO question_tags (question_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$question_id, $tag_id]);
                }
            }

            $conn->commit();
            header("Location: dashboard.php");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Ask a Question - StackIt</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/taggle@1.16.0/src/taggle.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/taggle@1.16.0/build/taggle.min.js"></script>
  <style>
    body {
      background: linear-gradient(135deg, #0a0a0a, #1e1e1e);
      color: #fff;
      font-family: "Segoe UI", sans-serif;
      padding: 40px;
    }

    nav {
      background: #625d5dff;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #2c2c2c;
      margin-bottom: 30px;
      border-radius: 12px;
    }

    .container {
      max-width: 800px;
      margin: auto;
      background: #1a1a1a;
      padding: 30px;
      border-radius: 20px;
    }

    input, textarea {
      width: 100%;
      padding: 12px;
      background: #0e0e0e;
      color: white;
      border: 1px solid #444;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .taggle_list {
      background-color: rgba(255,255,255,0.05);
      border: 1px solid #444;
      border-radius: 8px;
      padding: 10px;
      min-height: 50px;
    }

    .btn, .cancel-btn {
      padding: 12px 20px;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
    }

    .btn {
      background: linear-gradient(145deg, #3158bb6b, #1b1796);
      color: white;
      border: none;
    }

    .cancel-btn {
      background: #444;
      color: #fff;
      text-decoration: none;
      margin-left: 10px;
    }

    .alert {
      background: rgba(255, 0, 0, 0.1);
      color: #ff6b6b;
      padding: 10px 14px;
      border: 1px solid rgba(255, 0, 0, 0.3);
      margin-bottom: 20px;
      border-radius: 10px;
    }
  </style>
</head>
<body>

<nav>
  <a href="dashboard.php" style="color: white;"> Back to Dashboard</a>
  <div>
    👋 <?= htmlspecialchars($username) ?>
    <a href="logout.php" class="btn">Logout</a>
  </div>
</nav>

<div class="container">
  <h2>Ask a Question</h2>

  <?php foreach ($errors as $error): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
  <?php endforeach; ?>

  <form method="POST">
    <label>Title</label>
    <input type="text" name="title" required>

    <label>Description</label>
    <textarea name="description" rows="6" required></textarea>

    <label>Tags</label>
    <div id="tag-container"></div>
    <input type="hidden" name="tags" id="tags-hidden">

    <button type="submit" class="btn">Submit Question</button>
    <a href="dashboard.php" class="cancel-btn">Cancel</a>
  </form>
</div>

<script>
  const tagList = <?= json_encode($tagList) ?>;

  const taggle = new Taggle('tag-container', {
    allowedTags: tagList,
    preserveCase: false,
    onTagAdd: updateHiddenInput,
    onTagRemove: updateHiddenInput
  });

  function updateHiddenInput() {
    const tags = taggle.getTags().values;
    document.getElementById('tags-hidden').value = tags.join(',');
  }

  updateHiddenInput();
</script>

</body>
</html>
