<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';
start_admin_session();
require_admin();

$page_title  = 'Users';
$active_page = 'users';
$db = get_db();

$users = $db->query("
    SELECT u.*, COUNT(o.id) as order_count, COALESCE(SUM(o.total),0) as total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id AND o.status != 'cancelled'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

require_once __DIR__ . '/sidebar.php';
?>

<div class="section-card">
  <div class="section-card-header">
    <h2>Registered Users (<?= count($users) ?>)</h2>
  </div>
  <div class="toolbar">
    <input type="text" class="search-input" placeholder="Search users..." oninput="filterTable(this,'users-table')">
  </div>
  <table class="admin-table" id="users-table">
    <thead>
      <tr><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spent</th><th>Joined</th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:0.6rem">
            <div style="width:34px;height:34px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-family:'Bebas Neue',sans-serif;font-size:1rem;color:#0a0a0f"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
            <?= htmlspecialchars($u['full_name']) ?>
          </div>
        </td>
        <td style="color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></td>
        <td style="color:var(--text-muted)"><?= htmlspecialchars($u['phone']) ?></td>
        <td><?= $u['order_count'] ?> orders</td>
        <td style="color:var(--gold)">₹<?= number_format($u['total_spent'],2) ?></td>
        <td style="color:var(--text-muted);font-size:0.82rem"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($users)): ?>
      <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">No users registered yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

  </div>
</main>
<script src="assets/admin.js"></script>
</body>
</html>
