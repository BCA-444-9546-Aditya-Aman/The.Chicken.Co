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
    $name     = sanitize($_POST['full_name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $phone    = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$phone || !$password) {
        $error = 'All fields are required.';
    } elseif (strlen($name) < 3 || !preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = 'Name must be at least 3 characters and contain letters and spaces only.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (preg_replace('/[^0-9]/', '', $phone) !== $phone || strlen($phone) !== 10) {
        $error = 'Phone number must be exactly 10 digits.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db = get_db();
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $hashed]);
            $userId = $db->lastInsertId();

            $user = [
                'id'        => $userId,
                'full_name' => $name,
                'email'     => $email,
                'phone'     => $phone,
                'address'   => null
            ];
            $_SESSION['user'] = $user;
            header('Location: index.html');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — The Chicken Co.</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,700;1,300&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<style>
  /* ── REGISTER PAGE LAYOUT ── */
  #register-page {
    min-height: 100vh;
    display: flex;
    background: var(--dark);
  }

  /* ── LEFT PANEL — FORM ── */
  .register-left {
    flex: 0 0 55%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 3rem 2rem;
    position: relative;
  }

  .register-form-wrapper {
    width: 100%;
    max-width: 420px;
  }

  .register-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255,248,238,0.4);
    text-decoration: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.8rem;
    letter-spacing: 0.04em;
    margin-bottom: 2rem;
    transition: color 0.3s;
  }
  .register-back:hover { color: var(--gold); }
  .register-back svg { transition: transform 0.3s; }
  .register-back:hover svg { transform: translateX(-3px); }

  .register-title {
    font-family: 'DM Sans', sans-serif;
    font-size: 1.6rem;
    font-weight: 500;
    color: var(--cream);
    margin: 0 0 0.5rem;
  }

  .register-subtitle {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    color: rgba(255,248,238,0.4);
    margin: 0 0 2rem;
    font-weight: 300;
  }

  /* ── FORM ── */
  .register-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
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

  /* ── REGISTER BUTTON ── */
  .register-btn {
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
  .register-btn:hover {
    background: var(--fire);
    color: var(--cream);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(255,69,0,0.3);
  }

  /* ── BOTTOM TEXT ── */
  .register-footer-text {
    text-align: center;
    margin-top: 2rem;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.85rem;
    color: rgba(255,248,238,0.4);
  }
  .register-footer-text a {
    color: var(--gold);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
  }
  .register-footer-text a:hover {
    color: var(--fire);
  }

  /* ── RIGHT PANEL — BRAND ── */
  .register-right {
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
  .register-right::before {
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
    margin-bottom: 1rem;
  }

  /* ── RESPONSIVE ── */
  @media (max-width: 900px) {
    #register-page {
      flex-direction: column;
    }
    .register-left {
      flex: 1;
      padding: 2.5rem 1.5rem;
    }
    .register-right {
      display: none;
    }
    .register-form-wrapper {
      max-width: 100%;
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

<section id="register-page">

  <!-- LEFT — FORM PANEL -->
  <div class="register-left">
    <div class="register-form-wrapper">

      <a href="index.html" class="register-back">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Back to Home
      </a>

      <h1 class="register-title">Create Account</h1>
      <p class="register-subtitle">Join us and start earning loyalty crunch points!</p>

      <?php if (!empty($error)): ?>
        <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form class="register-form" id="register-form" method="POST" action="register.php" novalidate>

        <div class="form-group">
          <label for="reg-name">Full Name</label>
          <input type="text" id="reg-name" name="full_name" placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required autocomplete="name">
          <span class="error-msg" id="err-reg-name"></span>
        </div>

        <div class="form-group">
          <label for="reg-email">Email Address</label>
          <input type="email" id="reg-email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="email">
          <span class="error-msg" id="err-reg-email"></span>
        </div>

        <div class="form-group">
          <label for="reg-phone">Phone Number</label>
          <input type="tel" id="reg-phone" name="phone" placeholder="e.g. 9876543210" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required autocomplete="tel">
          <span class="error-msg" id="err-reg-phone"></span>
        </div>

        <div class="form-group">
          <label for="reg-password">Password</label>
          <input type="password" id="reg-password" name="password" placeholder="Min. 6 characters" required autocomplete="new-password">
          <span class="error-msg" id="err-reg-password"></span>
        </div>

        <button type="submit" class="register-btn">Sign Up</button>

      </form>

      <!-- BOTTOM TEXT -->
      <p class="register-footer-text">
        Already have an account? <a href="login.php">Sign In</a>
      </p>

    </div>
  </div>

  <!-- RIGHT — BRAND PANEL -->
  <div class="register-right">
    <div class="brand-content">
      <div class="brand-logo">The<span>.</span>Chicken<span>.</span>Co</div>
      <div class="brand-accent-line"></div>
      
      <div class="marketing-text">
        <h2>Taste the <span>Tradition!</span></h2>
        <p>Sign up now to unlock secret menus, special member rates, and direct orders with super-fast delivery tracking.</p>
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
  const regForm = document.getElementById('register-form');
  const nameInput = document.getElementById('reg-name');
  const emailInput = document.getElementById('reg-email');
  const phoneInput = document.getElementById('reg-phone');
  const passwordInput = document.getElementById('reg-password');

  function validateName(name) {
    if (!name || name.trim().length < 3) return "Name must be at least 3 characters.";
    if (!/^[a-zA-Z\s]+$/.test(name)) return "Name must contain letters and spaces only.";
    return "";
  }

  function validateEmail(email) {
    if (!email) return "Email address is required.";
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return "Please enter a valid email address.";
    return "";
  }

  function validatePhone(phone) {
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    if (cleanPhone.length !== 10) return "Phone number must be exactly 10 digits.";
    return "";
  }

  function validatePassword(pass) {
    if (!pass || pass.length < 6) return "Password must be at least 6 characters.";
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
  nameInput.addEventListener('input', () => {
    showError(nameInput, document.getElementById('err-reg-name'), validateName(nameInput.value.trim()));
  });

  emailInput.addEventListener('input', () => {
    showError(emailInput, document.getElementById('err-reg-email'), validateEmail(emailInput.value.trim()));
  });

  phoneInput.addEventListener('input', () => {
    showError(phoneInput, document.getElementById('err-reg-phone'), validatePhone(phoneInput.value.trim()));
  });

  passwordInput.addEventListener('input', () => {
    showError(passwordInput, document.getElementById('err-reg-password'), validatePassword(passwordInput.value));
  });

  // Form submit check
  regForm.addEventListener('submit', (e) => {
    const nameErr = validateName(nameInput.value.trim());
    const emailErr = validateEmail(emailInput.value.trim());
    const phoneErr = validatePhone(phoneInput.value.trim());
    const passErr = validatePassword(passwordInput.value);

    const isNameValid = showError(nameInput, document.getElementById('err-reg-name'), nameErr);
    const isEmailValid = showError(emailInput, document.getElementById('err-reg-email'), emailErr);
    const isPhoneValid = showError(phoneInput, document.getElementById('err-reg-phone'), phoneErr);
    const isPassValid = showError(passwordInput, document.getElementById('err-reg-password'), passErr);

    if (!isNameValid || !isEmailValid || !isPhoneValid || !isPassValid) {
      e.preventDefault();
    }
  });
</script>
</body>
</html>
