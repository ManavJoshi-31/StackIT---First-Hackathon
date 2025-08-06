<?php
session_start();
require 'config.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validations
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['username'];
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>StackIt Login</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", sans-serif;
    }

    body {
      display: flex;
      flex-direction: row;
      flex-wrap: wrap;
      min-height: 100vh;
      background: linear-gradient(135deg, #0a0a0a, #1e1e1e);
      color: #fff;
    }

    .left {
      flex: 0 0 30%;
      padding: 60px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: linear-gradient(145deg, #000000, #1a1a1a);
    }

    .right {
      flex: 0 0 70%;
      padding: 60px 40px;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(145deg, #121212, #1a1a1a);
    }

    .login-container-wrapper {
      position: relative;
      border-radius: 26px;
      padding: 4px;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.778), rgba(255, 255, 255, 0.123), rgba(255, 255, 255, 0.722));
      box-shadow: 0 0 30px rgba(255, 255, 255, 0.177);
      width: 100%;
      max-width: 400px;
    }

    .login-container {
      background-color: #000;
      padding: 40px 35px;
      border-radius: 24px;
      width: 100%;
      backdrop-filter: blur(20px);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
      animation: fadeIn 1s ease forwards;
    }

    .login-container h2 {
      font-size: 28px;
      margin-bottom: 8px;
      color: #fff;
      text-align: center;
    }

    .login-container p {
      font-size: 14px;
      margin-bottom: 24px;
      color: #ccc;
      text-align: center;
    }

    .input-group {
      margin-bottom: 18px;
    }

    .input-group input {
      width: 100%;
      padding: 12px 16px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 12px;
      background-color: rgba(255, 255, 255, 0.05);
      color: #fff;
      font-size: 14px;
      transition: 0.3s;
    }

    .input-group input:focus {
      border-color: #fff;
      outline: none;
      background-color: rgba(255, 255, 255, 0.1);
    }

    .login-container button {
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

    .login-container button:hover {
      transform: translateY(-2px);
      background: linear-gradient(145deg, #2644916b, #161377);
      box-shadow: 0 0 20px rgba(102, 179, 255, 0.133);
    }

    .signup {
      text-align: center;
      margin-top: 20px;
      font-size: 13px;
      color: #aaa;
    }

    .signup a {
      color: #fff;
      text-decoration: underline;
    }

    .left h1 {
      font-size: 36px;
      margin-bottom: 20px;
    }

    .left p {
      font-size: 16px;
      margin-bottom: 30px;
      line-height: 1.6;
      color: #ccc;
    }

    .feature {
      margin-bottom: 25px;
    }

    .feature h3 {
      font-size: 18px;
      margin-bottom: 6px;
    }

    .feature p {
      font-size: 14px;
      color: #ccc;
    }

    .error {
      color: #ff6b6b;
      background: rgba(255, 0, 0, 0.1);
      border: 1px solid rgba(255, 0, 0, 0.3);
      padding: 10px;
      border-radius: 10px;
      font-size: 14px;
      margin-bottom: 15px;
      text-align: center;
    }

    @media (max-width: 768px) {
      .left,
      .right {
        flex: 1 1 100%;
        padding: 30px 20px;
      }

      .left {
        align-items: center;
        text-align: center;
      }

      .login-container-wrapper {
        max-width: 100%;
      }

      .login-container {
        padding: 30px 20px;
      }
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
  </style>
</head>
<body>
  <div class="left">
    <h1>STACKIT</h1>
    <p>
      Unlock the full potential of your tech stack with StackIt — the ultimate
      platform for developers and teams to manage, deploy, and innovate faster
      than ever.
    </p>
    <div class="feature">
      <h3>🚀 Lightning Deployment</h3>
      <p>
        Deploy code in seconds with our optimized pipelines and pre-built
        containers.
      </p>
    </div>
    <div class="feature">
      <h3>🔐 Secure & Scalable</h3>
      <p>
        Scale confidently with built-in security, monitoring, and performance
        optimization.
      </p>
    </div>
    <div class="feature">
      <h3>🧠 Smart Integrations</h3>
      <p>
        Connect seamlessly with your favorite tools like GitHub, Slack, and
        Docker.
      </p>
    </div>
  </div>
  <div class="right">
    <div class="login-container-wrapper">
      <div class="login-container">
        <h2>Login</h2>
        <p>Enter your credentials to access StackIt</p>

        <?php foreach ($errors as $error): ?>
          <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <form method="POST">
          <div class="input-group">
            <input type="email" name="email" placeholder="Email" required />
          </div>
          <div class="input-group">
            <input type="password" name="password" placeholder="Password" required />
          </div>
          <button type="submit">Login</button>
        </form>
        <div class="signup">
          <a href="register.php">Don't have an account? Register</a><br />
        </div>
      </div>
    </div>
  </div>
</body>
</html>
