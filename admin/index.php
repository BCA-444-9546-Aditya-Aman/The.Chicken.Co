<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';
start_admin_session();

$page_title  = 'Dashboard';
$active_page = 'dashboard';
require_once __DIR__ . '/sidebar.php';

$db = get_db();

// Stats
$totalOrders   = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$todayOrders   = $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$totalRevenue  = $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$totalUsers    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Recent orders
$recentOrders = $db->query("
    SELECT o.*, u.full_name FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 10
")->fetchAll();
?>

<div class="stats-grid">
  <div class="stat-card gold">
    <div class="stat-label">Total Revenue</div>
    <div class="stat-value">₹<?= number_format($totalRevenue, 0) ?></div>
    <div class="stat-sub">All time, excluding cancelled</div>
  </div>
  <div class="stat-card fire">
    <div class="stat-label">Pending Orders</div>
    <div class="stat-value"><?= $pendingOrders ?></div>
    <div class="stat-sub">Needs attention</div>
  </div>
  <div class="stat-card green">
    <div class="stat-label">Orders Today</div>
    <div class="stat-value"><?= $todayOrders ?></div>
    <div class="stat-sub"><?= $totalOrders ?> total all time</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Registered Users</div>
    <div class="stat-value"><?= $totalUsers ?></div>
    <div class="stat-sub">Customer accounts</div>
  </div>
</div>

<div class="section-card">
  <div class="section-card-header">
    <h2>Recent Orders</h2>
    <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <table class="admin-table">
    <thead>
      <tr>
        <th>Order #</th>
        <th>Customer</th>
        <th>Total</th>
        <th>Payment</th>
        <th>Status</th>
        <th>Time</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($recentOrders as $o): ?>
      <tr>
        <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
        <td><?= htmlspecialchars($o['full_name']) ?></td>
        <td style="color:var(--gold)">₹<?= number_format($o['total'], 2) ?></td>
        <td><span class="badge badge-<?= $o['payment_status'] ?>"><?= $o['payment_method'] ?> · <?= $o['payment_status'] ?></span></td>
        <td><?= statusBadgePhp($o['status']) ?></td>
        <td style="color:var(--text-muted);font-size:0.82rem"><?= time_ago($o['created_at']) ?></td>
        <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($recentOrders)): ?>
      <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem">No orders yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php
function statusBadgePhp($s) {
    return '<span class="badge badge-' . $s . '">' . str_replace('_', ' ', $s) . '</span>';
}
?>

  </div><!-- .content -->
</main>
<script src="assets/admin.js"></script>
</body>
</html>
