<?php
session_start();

require_once __DIR__ . '/connection/db.php';
require_once __DIR__ . '/connection/auth.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$back_href = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'dashboard_m.php' : 'dashboard_s.php';

ims_ensure_users_table($pdo);

$success = '';
$error = '';

$currentUsername = (string)($_SESSION['user'] ?? '');

$isAdmin = (string)($_SESSION['role'] ?? '') === 'admin';
$currentUserRow = ims_get_user_by_username($pdo, $currentUsername);
$currentUserId = (int)($currentUserRow['id'] ?? 0);

$allUsers = $isAdmin ? ims_get_login_credentials($pdo) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  try {
    if (!$isAdmin) {
      throw new RuntimeException('Only admins can change usernames or passwords.');
    }

    if ($action === 'change_username') {
      $targetUserId = (int)($_POST['target_user_id'] ?? 0);
      $newUsername = (string)($_POST['new_username'] ?? '');
      $adminPassword = (string)($_POST['admin_password'] ?? '');

      $usernameError = ims_validate_username($newUsername);
      if ($usernameError) {
        throw new RuntimeException($usernameError);
      }

      if ($targetUserId < 1) {
        throw new RuntimeException('Please select a user.');
      }
      $targetUser = ims_get_user_by_id($pdo, $targetUserId);
      if (!$targetUser) {
        throw new RuntimeException('Selected user not found.');
      }
      if ($newUsername === (string)$targetUser['username']) {
        throw new RuntimeException('New username must be different.');
      }

      if (ims_get_user_by_username($pdo, $newUsername)) {
        throw new RuntimeException('Username already exists.');
      }

      if (ims_validate_password($adminPassword)) {
        throw new RuntimeException('Your current password is required.');
      }
      if (!ims_verify_login($pdo, $currentUsername, $adminPassword)) {
        throw new RuntimeException('Your current password is incorrect.');
      }

      ims_update_username_by_id($pdo, $targetUserId, $newUsername);

      if ($targetUserId === $currentUserId) {
        $_SESSION['user'] = $newUsername;
        $currentUsername = $newUsername;
      }

      $success = 'Username updated successfully.';
    } elseif ($action === 'change_password') {
      $targetUserId = (int)($_POST['target_user_id'] ?? 0);
      $adminPassword = (string)($_POST['admin_password'] ?? '');
      $newPassword = (string)($_POST['new_password'] ?? '');
      $confirmPassword = (string)($_POST['confirm_password'] ?? '');

      if ($targetUserId < 1) {
        throw new RuntimeException('Please select a user.');
      }
      $targetUser = ims_get_user_by_id($pdo, $targetUserId);
      if (!$targetUser) {
        throw new RuntimeException('Selected user not found.');
      }

      if (ims_validate_password($adminPassword)) {
        throw new RuntimeException('Your current password is required.');
      }
      if (!ims_verify_login($pdo, $currentUsername, $adminPassword)) {
        throw new RuntimeException('Your current password is incorrect.');
      }

      $newPasswordError = ims_validate_password($newPassword);
      if ($newPasswordError) {
        throw new RuntimeException($newPasswordError);
      }
      if ($newPassword !== $confirmPassword) {
        throw new RuntimeException('New password and confirm password do not match.');
      }

      ims_update_password_by_id($pdo, $targetUserId, $newPassword);
      $success = 'Password updated successfully.';
    } else {
      throw new RuntimeException('Invalid action.');
    }
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Settings</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="css/style.css">
    <style>
      .settings-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 40px 20px;
      }
      .settings-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        padding: 30px;
      }
    </style>
  </head>

  <body>
    <a href="<?php echo htmlspecialchars($back_href); ?>" class="settings-btn" title="Back">
      <i class="bi bi-arrow-left"></i>
    </a>
    <a href="logout.php" class="logout-btn" title="Logout">
      <i class="bi bi-box-arrow-right"></i>
    </a>

    <div class="settings-container">
      <div class="settings-card">
        <h2 class="fw-bold mb-2">Settings</h2>

        <div class="mb-4">
          <div class="text-muted">Signed in as</div>
          <div class="fw-semibold">
            <?php echo htmlspecialchars($currentUsername); ?>
            <span class="badge text-bg-secondary ms-2"><?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?></span>
          </div>
        </div>

        <?php if ($success): ?>
          <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!$isAdmin): ?>
          <div class="alert alert-warning" role="alert">
            Only admins can change usernames or passwords.
          </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
        <div class="row g-4">
          <div class="col-12 col-lg-6">
            <div class="border rounded-3 p-3 h-100">
              <h5 class="mb-3">Change Username</h5>
              <form method="POST" action="">
                <input type="hidden" name="action" value="change_username" />

                <div class="mb-3">
                  <label class="form-label" for="target_user_id_username">Select user</label>
                  <select class="form-select" id="target_user_id_username" name="target_user_id" required>
                    <option value="" selected disabled>Choose user...</option>
                    <?php foreach ($allUsers as $u): ?>
                      <option value="<?php echo (int)($u['id'] ?? 0); ?>">
                        <?php echo htmlspecialchars(($u['role'] ?? '') . ' — ' . ($u['username'] ?? '')); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label" for="new_username">New username</label>
                  <input
                    class="form-control"
                    type="text"
                    id="new_username"
                    name="new_username"
                    placeholder="e.g. admin2"
                    required
                  />
                </div>

                <div class="mb-3">
                  <label class="form-label" for="admin_password_username">Your current password (admin)</label>
                  <input
                    class="form-control"
                    type="password"
                    id="admin_password_username"
                    name="admin_password"
                    placeholder="Enter your current password"
                    required
                  />
                </div>

                <button type="submit" class="btn btn-primary w-100">Update Username</button>
              </form>
            </div>
          </div>

          <div class="col-12 col-lg-6">
            <div class="border rounded-3 p-3 h-100">
              <h5 class="mb-3">Change Password</h5>
              <form method="POST" action="">
                <input type="hidden" name="action" value="change_password" />

                <div class="mb-3">
                  <label class="form-label" for="target_user_id_password">Select user</label>
                  <select class="form-select" id="target_user_id_password" name="target_user_id" required>
                    <option value="" selected disabled>Choose user...</option>
                    <?php foreach ($allUsers as $u): ?>
                      <option value="<?php echo (int)($u['id'] ?? 0); ?>">
                        <?php echo htmlspecialchars(($u['role'] ?? '') . ' — ' . ($u['username'] ?? '')); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label" for="admin_password">Your current password (admin)</label>
                  <input
                    class="form-control"
                    type="password"
                    id="admin_password"
                    name="admin_password"
                    placeholder="Enter your current password"
                    required
                  />
                </div>

                <div class="mb-3">
                  <label class="form-label" for="new_password">New password</label>
                  <input
                    class="form-control"
                    type="password"
                    id="new_password"
                    name="new_password"
                    placeholder="Enter new password"
                    required
                  />
                </div>

                <div class="mb-3">
                  <label class="form-label" for="confirm_password">Confirm new password</label>
                  <input
                    class="form-control"
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Re-enter new password"
                    required
                  />
                </div>

                <button type="submit" class="btn btn-primary w-100">Update Password</button>
              </form>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <?php require_once __DIR__ . '/partials/footer.php'; ?>
  </body>
</html>
