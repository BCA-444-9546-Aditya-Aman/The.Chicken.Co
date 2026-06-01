document.addEventListener('DOMContentLoaded', () => {
  // Authentication State
  const isLoggedIn = localStorage.getItem('chicken_co_auth') === 'true';

  // Toggle dropdown items based on auth
  const authRequiredLinks = document.querySelectorAll('.nav-dropdown .auth-required');
  const authHiddenLinks = document.querySelectorAll('.nav-dropdown .auth-hidden');

  if (isLoggedIn) {
    authRequiredLinks.forEach(el => el.style.display = 'block');
    authHiddenLinks.forEach(el => el.style.display = 'none');
  } else {
    authRequiredLinks.forEach(el => el.style.display = 'none');
    authHiddenLinks.forEach(el => el.style.display = 'block');
  }

  // Handle Logout
  const logoutBtns = document.querySelectorAll('.logout-btn');
  logoutBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.removeItem('chicken_co_auth');
      window.location.href = 'index.html';
    });
  });
});
