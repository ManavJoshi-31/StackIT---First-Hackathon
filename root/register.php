<?php
session_start();
require 'config.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);

    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

        try {
            $stmt->execute([$username, $email, $hashedPassword]);
            $_SESSION['register_success'] = true;
            header("Location: login.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "Email is already registered.";
            } else {
                $errors[] = "Error occurred while registering.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Register - StackIt</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", sans-serif;
    }

    body {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      background: linear-gradient(135deg, #0a0a0a, #1e1e1e);
      color: #fff;
    }

    .register-wrapper {
      padding: 4px;
      background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.778),
        rgba(255, 255, 255, 0.123),
        rgba(255, 255, 255, 0.722)
      );
      border-radius: 26px;
      box-shadow: 0 0 30px rgba(255, 255, 255, 0.177);
      max-width: 450px;
      width: 100%;
    }

    .register-container {
      background-color: #000;
      border-radius: 24px;
      padding: 40px 35px;
      width: 100%;
      backdrop-filter: blur(20px);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
      animation: fadeIn 0.8s ease forwards;
    }

    h2 {
      text-align: center;
      margin-bottom: 20px;
      font-size: 28px;
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-control {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      background-color: rgba(255, 255, 255, 0.05);
      color: #fff;
      font-size: 14px;
      transition: 0.3s;
    }

    .form-control:focus {
      border-color: #fff;
      outline: none;
      background-color: rgba(255, 255, 255, 0.1);
    }

    .btn {
      width: 100%;
      padding: 12px;
      background: linear-gradient(145deg, #3158bb6b, #1b1796);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease, background 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .btn:hover {
      transform: translateY(-2px);
      background: linear-gradient(145deg, #2644916b, #161377);
      box-shadow: 0 0 20px rgba(102, 179, 255, 0.133);
    }

    .error {
      color: red;
      font-size: 14px;
      text-align: center;
      margin-bottom: 10px;
    }

    .login-link {
      display: block;
      text-align: center;
      margin-top: 15px;
      color: #fff;
      font-size: 14px;
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 768px) {
      .register-container {
        padding: 30px 20px;
      }
    }
  </style>
</head>
<body>

<div class="register-wrapper">
  <div class="register-container">
    <h2>Create Your StackIt Account</h2>

    <?php foreach ($errors as $error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <form method="POST">
      <div class="form-group">
        <input type="text" name="username" class="form-control" placeholder="Username" required />
      </div>

      <div class="form-group">
        <input type="email" name="email" class="form-control" placeholder="Email" required />
      </div>

      <div class="form-group">
        <input type="password" name="password" class="form-control" placeholder="Password" required />
      </div>

      <div class="form-group">
        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required />
      </div>

      <button type="submit" class="btn">Register</button>
    </form>

    <a href="login.php" class="login-link">Already have an account? Login</a>
  </div>
</div>

</body>
</html>
