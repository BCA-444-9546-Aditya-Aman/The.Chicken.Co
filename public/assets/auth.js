/* ── auth.js — Session-based auth check ── */
document.addEventListener('DOMContentLoaded', async () => {

  const authRequiredLinks = document.querySelectorAll('.nav-dropdown .auth-required');
  const authHiddenLinks   = document.querySelectorAll('.nav-dropdown .auth-hidden');

  function applyAuthState(loggedIn) {
    if (loggedIn) {
      authRequiredLinks.forEach(el => el.style.display = 'block');
      authHiddenLinks.forEach(el   => el.style.display = 'none');
    } else {
      authRequiredLinks.forEach(el => el.style.display = 'none');
      authHiddenLinks.forEach(el   => el.style.display = 'block');
    }
  }

  // Check real session from server
  try {
    const res  = await fetch('api/auth/me.php');
    const data = await res.json();
    applyAuthState(data.success);
    // Cache user in window for other scripts on same page
    if (data.success) window._authUser = data.user;
  } catch (e) {
    applyAuthState(false);
  }

  // Handle Logout
  document.querySelectorAll('.logout-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      await fetch('api/auth/logout.php', { method: 'POST' });
      window.location.href = 'index.html';
    });
  });
});
