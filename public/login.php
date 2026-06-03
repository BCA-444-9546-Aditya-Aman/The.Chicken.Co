<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

start_customer_session();

// If already logged in, redirect to index.html
if (get_logged_in_user() !== null) {
    header('Location: index.html');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Both email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address format.';
    } else {
        $db = get_db();
        $stmt = $db->prepare("SELECT id, full_name, email, phone, password, address FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']); // don't keep password hash in session
            $_SESSION['user'] = $user;
            header('Location: index.html');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In — The Chicken Co.</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<style>
  /* ── LOGIN PAGE LAYOUT ── */
  #login-page {
    min-height: 100vh;
    display: flex;
    background: var(--dark);
  }

  /* ── LEFT PANEL — FORM ── */
  .login-left {
    flex: 0 0 55%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 3rem 2rem;
    position: relative;
  }

  .login-form-wrapper {
    width: 100%;
    max-width: 420px;
  }

  .login-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255,248,238,0.4);
    text-decoration: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.8rem;
    letter-spacing: 0.04em;
    margin-bottom: 3rem;
    transition: color 0.3s;
  }
  .login-back:hover { color: var(--gold); }
  .login-back svg { transition: transform 0.3s; }
  .login-back:hover svg { transform: translateX(-3px); }

  .login-title {
    font-family: 'DM Sans', sans-serif;
    font-size: 1.6rem;
    font-weight: 500;
    color: var(--cream);
    margin: 0 0 0.5rem;
  }

  .login-subtitle {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    color: rgba(255,248,238,0.4);
    margin: 0 0 2.5rem;
    font-weight: 300;
  }

  /* ── FORM ── */
  .login-form {
    display: flex;
    flex-direction: column;
    gap: 1.8rem;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
  }

  .form-group label {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.72rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255,248,238,0.45);
    font-weight: 500;
  }

  .form-group input {
    background: transparent;
    border: none;
    border-bottom: 1px solid rgba(255,248,238,0.12);
    color: var(--cream);
    padding: 0.7rem 0;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.95rem;
    outline: none;
    transition: border-bottom-color 0.3s;
    cursor: none;
  }
  .form-group input::placeholder {
    color: rgba(255,248,238,0.2);
  }
  .form-group input:focus {
    border-bottom-color: var(--gold);
  }

  .forgot-row {
    display: flex;
    justify-content: flex-end;
    margin-top: -0.8rem;
  }

  .forgot-link {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.78rem;
    color: rgba(255,184,0,0.55);
    text-decoration: none;
    transition: color 0.3s;
  }
  .forgot-link:hover { color: var(--gold); }

  /* ── SIGN IN BUTTON ── */
  .login-btn {
    background: var(--gold);
    color: var(--dark);
    border: none;
    padding: 0.9rem 1rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    cursor: none;
    transition: background 0.3s, transform 0.2s, box-shadow 0.3s;
    margin-top: 0.5rem;
    width: 100%;
  }
  .login-btn:hover {
    background: var(--fire);
    color: var(--cream);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(255,69,0,0.3);
  }

  /* ── DIVIDER ── */
  .login-divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.8rem 0;
  }
  .login-divider::before,
  .login-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,248,238,0.1);
  }
  .login-divider span {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.72rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255,248,238,0.25);
  }

  /* ── SOCIAL BUTTONS ── */
  .social-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  .social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    width: 100%;
    padding: 0.75rem;
    background: transparent;
    border: 1px solid rgba(255,248,238,0.15);
    color: var(--cream);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.85rem;
    cursor: none;
    transition: border-color 0.3s, background 0.3s;
    letter-spacing: 0.02em;
  }
  .social-btn:hover {
    border-color: rgba(255,184,0,0.35);
    background: rgba(255,248,238,0.03);
  }

  /* ── BOTTOM TEXT ── */
  .login-footer-text {
    text-align: center;
    margin-top: 2.2rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.85rem;
    color: rgba(255,248,238,0.4);
  }
  .login-footer-text a {
    color: var(--gold);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
  }
  .login-footer-text a:hover {
    color: var(--fire);
  }

  /* ── RIGHT PANEL — BRAND ── */
  .login-right {
    flex: 0 0 45%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 2.5rem;
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, var(--gold) 0%, #E6A600 100%);
    border-left: 1px solid rgba(255,184,0,0.2);
  }

  /* Subtle warm glow on brand panel */
  .login-right::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top right, rgba(255,255,255,0.25), transparent 60%);
    pointer-events: none;
    z-index: 2;
  }

  .brand-content {
    position: relative;
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1.5rem;
    text-align: left;
    width: 100%;
    max-width: 400px;
  }

  .brand-logo {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2.8rem;
    color: var(--dark);
    letter-spacing: 0.06em;
  }
  .brand-logo span { color: var(--fire); }

  .brand-accent-line {
    width: 60px;
    height: 3px;
    background: var(--fire);
    opacity: 0.9;
    border-radius: 2px;
  }

  .marketing-text {
    margin-top: 1rem;
  }
  .marketing-text h2 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 3.5rem;
    color: var(--dark);
    letter-spacing: 0.04em;
    line-height: 1.1;
    margin-bottom: 1rem;
  }
  .marketing-text h2 span { color: var(--fire); }
  
  .marketing-text p {
    font-family: 'DM Sans', sans-serif;
    font-size: 1.05rem;
    line-height: 1.6;
    color: rgba(8,6,4,0.85);
    font-weight: 500;
  }

  /* Error container design */
  .error-box {
    color: #ef4444;
    font-size: 0.85rem;
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.2);
    padding: 0.7rem 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
  }

  /* ── RESPONSIVE ── */
  @media (max-width: 900px) {
    #login-page {
      flex-direction: column;
    }
    .login-left {
      flex: 1;
      padding: 2.5rem 1.5rem;
    }
    .login-right {
      display: none;
    }
    .login-form-wrapper {
      max-width: 100%;
    }
    .login-back {
      margin-bottom: 2rem;
    }
  }

  /* Validation message styling */
  .error-msg {
    color: #ef4444;
    font-size: 0.78rem;
    margin-top: 0.3rem;
    display: block;
    min-height: 1.1rem;
    font-family: 'DM Sans', sans-serif;
  }
  .form-group input.invalid {
    border-bottom-color: #ef4444 !important;
  }
</style>
</head>
<body>

<div id="cur"></div>
<div id="cur-r"></div>

<section id="login-page">

  <!-- LEFT — FORM PANEL -->
  <div class="login-left">
    <div class="login-form-wrapper">

      <a href="index.html" class="login-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Back to Home
      </a>

      <h1 class="login-title">Sign In</h1>
      <p class="login-subtitle">Welcome back to The Chicken Co.</p>

      <?php if (!empty($error)): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form class="login-form" id="login-form" method="POST" action="login.php" novalidate>

        <div class="form-group">
          <label for="login-email">Email Address</label>
          <input type="email" id="login-email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="email">
          <span class="error-msg" id="err-login-email"></span>
        </div>

        <div class="form-group">
          <label for="login-password">Password</label>
          <input type="password" id="login-password" name="password" placeholder="••••••••" required autocomplete="current-password">
          <span class="error-msg" id="err-login-password"></span>
        </div>

        <div class="forgot-row">
          <a href="#" class="forgot-link">Forgot Password?</a>
        </div>

        <button type="submit" class="login-btn">Sign In</button>

      </form>

      <!-- DIVIDER -->
      <div class="login-divider"><span>or</span></div>

      <!-- SOCIAL LOGIN -->
      <div class="social-buttons">
        <button type="button" class="social-btn">
          <svg viewBox="0 0 24 24" width="18" height="18"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
          Continue with Google
        </button>
        <button type="button" class="social-btn">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="var(--cream)"><path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
          Continue with Apple
        </button>
      </div>

      <!-- BOTTOM TEXT -->
      <p class="login-footer-text">
        Don't have an account? <a href="register.php">Create one</a>
      </p>

    </div>
  </div>

  <!-- RIGHT — BRAND PANEL -->
  <div class="login-right">
    <div class="brand-content">
      <div class="brand-logo">The<span>.</span>Chicken<span>.</span>Co</div>
      <div class="brand-accent-line"></div>
      
      <div class="marketing-text">
        <h2>Craving That <span>Crunch?</span></h2>
        <p>Sign in to unlock exclusive offers, track your favorite orders in real-time, and get your chicken fixed faster than ever.</p>
      </div>

    </div>
  </div>

</section>

<script src="assets/cart.js"></script>
<script>
  /* ── CURSOR ── */
  const cur = document.getElementById('cur');
  const curR = document.getElementById('cur-r');
  let mx=0, my=0, rx=0, ry=0;

  document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY;
    cur.style.left = mx + 'px';
    cur.style.top = my + 'px';
  });

  (function animR(){
    rx += (mx - rx) * 0.11;
    ry += (my - ry) * 0.11;
    curR.style.left = rx + 'px';
    curR.style.top = ry + 'px';
    requestAnimationFrame(animR);
  })();

  document.querySelectorAll('button, a, input').forEach(el => {
    el.addEventListener('mouseenter', () => {
      cur.style.width = '18px'; cur.style.height = '18px';
      curR.style.width = '54px'; curR.style.height = '54px';
      curR.style.borderColor = 'rgba(255,184,0,.65)';
    });
    el.addEventListener('mouseleave', () => {
      cur.style.width = '10px'; cur.style.height = '10px';
      curR.style.width = '36px'; curR.style.height = '36px';
      curR.style.borderColor = 'rgba(255,184,0,.45)';
    });
  });

  // ── FORM VALIDATION ──
  const loginForm = document.getElementById('login-form');
  const emailInput = document.getElementById('login-email');
  const passwordInput = document.getElementById('login-password');

  function validateEmail(email) {
    if (!email) return "Email address is required.";
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return "Please enter a valid email address.";
    return "";
  }

  function validatePassword(pass) {
    if (!pass) return "Password is required.";
    return "";
  }

  function showError(inputEl, errEl, msg) {
    if (msg) {
      inputEl.classList.add('invalid');
      errEl.textContent = msg;
      return false;
    } else {
      inputEl.classList.remove('invalid');
      errEl.textContent = '';
      return true;
    }
  }

  // Real-time input validation
  emailInput.addEventListener('input', () => {
    showError(emailInput, document.getElementById('err-login-email'), validateEmail(emailInput.value.trim()));
  });

  passwordInput.addEventListener('input', () => {
    showError(passwordInput, document.getElementById('err-login-password'), validatePassword(passwordInput.value));
  });

  // Form submit check
  loginForm.addEventListener('submit', (e) => {
    const emailErr = validateEmail(emailInput.value.trim());
    const passErr = validatePassword(passwordInput.value);

    const isEmailValid = showError(emailInput, document.getElementById('err-login-email'), emailErr);
    const isPassValid = showError(passwordInput, document.getElementById('err-login-password'), passErr);

    if (!isEmailValid || !isPassValid) {
      e.preventDefault();
    }
  });
</script>
</body>
</html>
