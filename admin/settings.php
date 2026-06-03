<?php
/**
 * Admin Settings Page — The Chicken Co.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

start_admin_session();

$page_title  = 'Settings';
$active_page = 'settings';
require_once __DIR__ . '/sidebar.php';

$db = get_db();
$adminId = $_SESSION['admin']['id'];

// Fetch current details from DB
$stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$adminDetails = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = sanitize($_POST['name'] ?? '');
    $email           = sanitize($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validations
    if (!$name || !$email) {
        $error = 'Name and Email are required.';
    } elseif (strlen($name) < 3) {
        $error = 'Name must be at least 3 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address format.';
    } else {
        // Verify email uniqueness
        $emailStmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $emailStmt->execute([$email, $adminId]);
        if ($emailStmt->fetch()) {
            $error = 'Email address is already in use by another administrator.';
        } else {
            // Require current password for any change (best security practice)
            if (empty($currentPassword)) {
                $error = 'Current password is required to update settings.';
            } elseif (!password_verify($currentPassword, $adminDetails['password'])) {
                $error = 'Incorrect current password.';
            } else {
                $passwordChange = !empty($newPassword);
                $canUpdate = true;

                if ($passwordChange) {
                    if (strlen($newPassword) < 6) {
                        $error = 'New password must be at least 6 characters.';
                        $canUpdate = false;
                    } elseif ($newPassword !== $confirmPassword) {
                        $error = 'New passwords do not match.';
                        $canUpdate = false;
                    }
                }

                if ($canUpdate) {
                    try {
                        if ($passwordChange) {
                            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                            $updateStmt = $db->prepare("UPDATE admins SET name = ?, email = ?, password = ? WHERE id = ?");
                            $updateStmt->execute([$name, $email, $hashed, $adminId]);
                        } else {
                            $updateStmt = $db->prepare("UPDATE admins SET name = ?, email = ? WHERE id = ?");
                            $updateStmt->execute([$name, $email, $adminId]);
                        }

                        // Update session data so changes are visible instantly
                        $_SESSION['admin']['name']  = $name;
                        $_SESSION['admin']['email'] = $email;

                        // Refresh local variable state
                        $adminDetails['name']  = $name;
                        $adminDetails['email'] = $email;

                        $success = 'Settings updated successfully.';
                    } catch (Exception $e) {
                        $error = 'Failed to save changes. Please try again.';
                    }
                }
            }
        }
    }
}
?>

<style>
  .settings-container {
    max-width: 850px;
    margin: 1rem auto 3rem auto;
  }
  .settings-form {
    padding: 1.8rem 2.2rem;
  }
  .settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2.2rem;
  }
  @media (max-width: 768px) {
    .settings-grid {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
  }
  .form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 1.8rem;
    padding-top: 1.2rem;
    border-top: 1px solid var(--border);
  }
  .error-block {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #ef4444;
    padding: 0.8rem 1.2rem;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 1.5rem;
  }
  .success-block {
    background: rgba(34, 197, 94, 0.08);
    border: 1px solid rgba(34, 197, 94, 0.2);
    color: var(--success);
    padding: 0.8rem 1.2rem;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 1.5rem;
  }
  .settings-section-title {
    font-size: 0.82rem;
    text-transform: uppercase;
    color: var(--gold);
    letter-spacing: 0.08em;
    margin: 0 0 1.2rem 0;
    padding-bottom: 0.4rem;
    border-bottom: 1px solid rgba(255,255,255,0.05);
  }
  .field-error {
    color: #ef4444;
    font-size: 0.76rem;
    margin-top: 0.35rem;
    display: block;
    min-height: 1rem;
  }
  .form-control.invalid {
    border-color: #ef4444 !important;
  }
  .settings-col {
    display: flex;
    flex-direction: column;
  }
</style>

<div class="settings-container">
  <div class="section-card">
    <div class="section-card-header">
      <h2>Edit Profile & Security Settings</h2>
    </div>
    
    <form class="settings-form" id="settings-form" method="POST" novalidate>
      
      <?php if (!empty($error)): ?>
        <div class="error-block"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="success-block"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <div class="settings-grid">
        <!-- LEFT COLUMN: Profile Info & Current Pass -->
        <div class="settings-col">
          <div class="settings-section-title">Profile Information</div>
          
          <div class="form-group">
            <label for="admin-name">Full Name</label>
            <input type="text" id="admin-name" name="name" class="form-control" value="<?= htmlspecialchars($adminDetails['name']) ?>" required autocomplete="name">
            <span class="field-error" id="err-admin-name"></span>
          </div>

          <div class="form-group">
            <label for="admin-email">Email Address</label>
            <input type="email" id="admin-email" name="email" class="form-control" value="<?= htmlspecialchars($adminDetails['email']) ?>" required autocomplete="email">
            <span class="field-error" id="err-admin-email"></span>
          </div>

          <div class="settings-section-title" style="color: var(--danger); margin-top: 1.5rem;">Confirm Update</div>
          
          <div class="form-group" style="margin-bottom: 0;">
            <label for="admin-current-password">Current Password <span style="color: var(--danger);">*</span></label>
            <input type="password" id="admin-current-password" name="current_password" class="form-control" placeholder="Required to save changes" required autocomplete="current-password">
            <span class="field-error" id="err-admin-current-password"></span>
          </div>
        </div>

        <!-- RIGHT COLUMN: Password Change -->
        <div class="settings-col">
          <div class="settings-section-title">Change Password</div>
          
          <div class="form-group">
            <label for="admin-new-password">New Password</label>
            <input type="password" id="admin-new-password" name="new_password" class="form-control" placeholder="••••••••" autocomplete="new-password">
            <span class="field-error" id="err-admin-new-password"></span>
          </div>

          <div class="form-group">
            <label for="admin-confirm-password">Confirm New Password</label>
            <input type="password" id="admin-confirm-password" name="confirm_password" class="form-control" placeholder="••••••••" autocomplete="new-password">
            <span class="field-error" id="err-admin-confirm-password"></span>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-gold">Save Changes</button>
      </div>

    </form>
  </div>
</div>

  </div><!-- .content -->
</main>

<script src="assets/admin.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('settings-form');
    const nameInput = document.getElementById('admin-name');
    const emailInput = document.getElementById('admin-email');
    const newPassInput = document.getElementById('admin-new-password');
    const confirmPassInput = document.getElementById('admin-confirm-password');
    const currentPassInput = document.getElementById('admin-current-password');

    function validateName(val) {
      if (!val || val.trim().length < 3) return "Name must be at least 3 characters.";
      return "";
    }

    function validateEmail(val) {
      if (!val) return "Email address is required.";
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) return "Please enter a valid email address.";
      return "";
    }

    function validateNewPassword(val) {
      if (val && val.length < 6) return "New password must be at least 6 characters.";
      return "";
    }

    function validateConfirmPassword(newVal, confirmVal) {
      if (newVal && newVal !== confirmVal) return "Passwords do not match.";
      return "";
    }

    function validateCurrentPassword(val) {
      if (!val) return "Current password is required to save updates.";
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

    // Input listeners for real-time validation feedback
    nameInput.addEventListener('input', () => {
      showError(nameInput, document.getElementById('err-admin-name'), validateName(nameInput.value));
    });

    emailInput.addEventListener('input', () => {
      showError(emailInput, document.getElementById('err-admin-email'), validateEmail(emailInput.value.trim()));
    });

    newPassInput.addEventListener('input', () => {
      showError(newPassInput, document.getElementById('err-admin-new-password'), validateNewPassword(newPassInput.value));
      showError(confirmPassInput, document.getElementById('err-admin-confirm-password'), validateConfirmPassword(newPassInput.value, confirmPassInput.value));
    });

    confirmPassInput.addEventListener('input', () => {
      showError(confirmPassInput, document.getElementById('err-admin-confirm-password'), validateConfirmPassword(newPassInput.value, confirmPassInput.value));
    });

    currentPassInput.addEventListener('input', () => {
      showError(currentPassInput, document.getElementById('err-admin-current-password'), validateCurrentPassword(currentPassInput.value));
    });

    // Form submit check
    form.addEventListener('submit', (e) => {
      const nameErr = validateName(nameInput.value);
      const emailErr = validateEmail(emailInput.value.trim());
      const newPassErr = validateNewPassword(newPassInput.value);
      const confirmErr = validateConfirmPassword(newPassInput.value, confirmPassInput.value);
      const currentPassErr = validateCurrentPassword(currentPassInput.value);

      const isNameValid = showError(nameInput, document.getElementById('err-admin-name'), nameErr);
      const isEmailValid = showError(emailInput, document.getElementById('err-admin-email'), emailErr);
      const isNewPassValid = showError(newPassInput, document.getElementById('err-admin-new-password'), newPassErr);
      const isConfirmValid = showError(confirmPassInput, document.getElementById('err-admin-confirm-password'), confirmErr);
      const isCurrentValid = showError(currentPassInput, document.getElementById('err-admin-current-password'), currentPassErr);

      if (!isNameValid || !isEmailValid || !isNewPassValid || !isConfirmValid || !isCurrentValid) {
        e.preventDefault();
        // Scroll to the first invalid field
        const firstInvalid = form.querySelector('.form-control.invalid');
        if (firstInvalid) {
          firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
          firstInvalid.focus();
        }
      }
    });

    // Display PHP-generated success toast if present
    <?php if (!empty($success)): ?>
      if (typeof showToast === 'function') {
        showToast(<?= json_encode($success) ?>, 'success');
      }
    <?php endif; ?>
  });
</script>
</body>
</html>
