<?php
/**
 * Admin Login Page
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

start_admin_session();

// Redirect if already logged in
if (get_logged_in_admin()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        unset($admin['password']);
        $_SESSION['admin'] = $admin;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — The Chicken Co.</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css">
<style>
  body { display: flex; align-items: center; justify-content: center; }
  .login-card {
    width: 100%; max-width: 400px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 2.5rem;
  }
  .login-logo { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; color: var(--cream); margin-bottom: 0.3rem; }
  .login-logo span { color: var(--fire); }
  .login-subtitle { font-size: 0.82rem; color: var(--text-muted); margin-bottom: 2rem; letter-spacing: 0.05em; }
  .error-msg { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #ef4444; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-bottom: 1rem; }
  .login-btn { width: 100%; padding: 0.9rem; background: var(--gold); color: #0a0a0f; border: none; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 0.95rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; transition: background 0.2s; }
  .login-btn:hover { background: #e6a600; }
  .back-link { display: block; text-align: center; margin-top: 1.2rem; font-size: 0.82rem; color: var(--text-muted); text-decoration: none; }
  .back-link:hover { color: var(--cream); }
</style>
</head>
<body>
  <div class="login-card">
    <div class="login-logo">The<span>.</span>Chicken<span>.</span>Co</div>
    <p class="login-subtitle">ADMIN PANEL</p>

    <?php if ($error): ?>
      <div class="error-msg"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="form-control" required placeholder="admin@thechickenco.com">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required placeholder="••••••••">
      </div>
      <button type="submit" class="login-btn">Sign In</button>
    </form>
    <a href="../" class="back-link">← Back to Website</a>
  </div>
</body>
</html>
