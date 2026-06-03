// ── Toast Notification ─────────────────────────────────────────
function showToast(msg, type = 'success') {
  let toast = document.getElementById('admin-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'admin-toast';
    toast.className = 'toast';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.className = `toast ${type} show`;
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => toast.classList.remove('show'), 3000);
}

// ── Modal Helpers ──────────────────────────────────────────────
function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}
// Close on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});

// ── Confirm Delete ─────────────────────────────────────────────
function confirmDelete(message, onConfirm) {
  if (confirm(message || 'Are you sure you want to delete this?')) {
    onConfirm();
  }
}

// ── Fetch Wrapper ──────────────────────────────────────────────
async function adminFetch(url, options = {}) {
  try {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json' },
      ...options
    });
    return await res.json();
  } catch (err) {
    showToast('Network error. Please try again.', 'error');
    return { success: false };
  }
}

// ── Status Badge HTML ──────────────────────────────────────────
function statusBadge(status) {
  return `<span class="badge badge-${status.replace(/ /g, '_')}">${status.replace(/_/g, ' ')}</span>`;
}

// ── Image Preview on Upload ────────────────────────────────────
function previewImage(inputEl, previewId) {
  const file = inputEl.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const img = document.getElementById(previewId);
    if (img) img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

// ── Table Search Filter ────────────────────────────────────────
function filterTable(inputEl, tableId) {
  const query = inputEl.value.toLowerCase();
  const rows = document.querySelectorAll(`#${tableId} tbody tr`);
  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
  });
}
