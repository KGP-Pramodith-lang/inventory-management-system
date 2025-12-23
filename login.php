<?php
session_start();

require_once __DIR__ . '/connection/db.php';
require_once __DIR__ . '/connection/auth.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user'])) {
    // Redirect based on user role
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard_m.php");
    } else {
        header("Location: dashboard_s.php");
    }
    exit();
}

$error = "";

ims_ensure_users_table($pdo);

$loginCredentials = ims_get_login_credentials($pdo);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = (string)($_POST["username"] ?? '');
  $password = (string)($_POST["password"] ?? '');

  $user = ims_verify_login($pdo, $username, $password);
  if ($user) {
    $_SESSION['user'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    if ($user['role'] === 'admin') {
      header("Location: dashboard_m.php");
    } else {
      header("Location: dashboard_s.php");
    }
    exit();
  }

  $error = "Invalid username or password";
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />

    <style>
      * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      body {
        font-family: "Inter", sans-serif;
        background-color: #eef2ff; /* Light blue/purple background */
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }

      .login-main {
        flex: 1;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 24px 0;
      }

      /* Override shared footer spacing for the login layout */
      .ims-footer {
        margin-top: auto;
        width: 100%;
      }

      .login-card {
        background-color: white;
        width: 100%;
        max-width: 400px;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        text-align: center;
      }

      /* Logo Icon Styles */
      .logo-container {
        width: 60px;
        height: 60px;
        background-color: #584cf4; /* The specific purple color */
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px auto;
      }

      .logo-container svg {
        width: 30px;
        height: 30px;
        fill: white;
      }

      /* Heading Styles */
      h2 {
        font-size: 18px;
        font-weight: 500;
        color: #1f2937;
        margin-bottom: 8px;
      }

      .subtitle {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 30px;
      }

      /* Error Message Styles */
      .error-message {
        background-color: #fee;
        color: #c33;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
        display: none;
      }

      .error-message.show {
        display: block;
      }

      /* Form Styles */
      .form-group {
        text-align: left;
        margin-bottom: 20px;
      }

      label {
        display: block;
        font-size: 14px;
        color: #374151;
        margin-bottom: 8px;
      }

      input {
        width: 100%;
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
        color: #374151;
      }

      input:focus {
        border-color: #584cf4;
      }

      input::placeholder {
        color: #9ca3af;
      }

      /* Button Styles */
      .btn-signin {
        width: 100%;
        padding: 12px;
        background-color: #584cf4;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
        margin-bottom: 25px;
      }

      .btn-signin:hover {
        background-color: #4338ca;
      }

      /* Demo Credentials Section */
      .credentials {
        text-align: left;
        border-top: 1px solid #e5e7eb;
        padding-top: 20px;
        color: #4b5563;
      }

      .credentials h3 {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 10px;
      }

      .credentials p {
        font-size: 14px;
        margin-bottom: 6px;
        color: #4b5563;
      }

      .credentials .user-type {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f3f4f6;
      }
    </style>
  </head>
  <body>
    <main class="login-main">
    <div class="login-card">
      <div class="logo-container">
        <svg viewBox="0 0 24 24">
          <path
            d="M12.89 1.45l8 4A2 2 0 0 1 22 7.24v9.53a2 2 0 0 1-1.11 1.79l-8 4a2 2 0 0 1-1.78 0l-8-4a2 2 0 0 1-1.1-1.8V7.24a2 2 0 0 1 1.11-1.79l8-4a2 2 0 0 1 1.78 0zM12 14.5l-6.5-3.25L12 8l6.5 3.25L12 14.5z"
          />
        </svg>
      </div>

      <h2>Inventory Management System</h2>
      <p class="subtitle">Sign in to your account</p>

      <?php if ($error): ?>
        <div class="error-message show"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="Enter your username" required />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
          />
        </div>

        <button type="submit" class="btn-signin">Sign In</button>
      </form>

      <div class="credentials">
        <h3>Credentials</h3>
        <?php if (!$loginCredentials): ?>
          <p class="mb-0">No users found.</p>
        <?php else: ?>
          <?php foreach ($loginCredentials as $cred): ?>
            <div class="user-type">
              <p><strong>Username:</strong> <?php echo htmlspecialchars($cred['username'] ?? ''); ?></p>
              <p><strong>Password:</strong>
                <?php
                  $plain = $cred['password_plain'] ?? null;
                  echo htmlspecialchars(($plain === null || $plain === '') ? 'Not set (reset in Settings)' : $plain);
                ?>
              </p>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    </main>

    <?php require_once __DIR__ . '/partials/footer.php'; ?>
  </body>
</html>
