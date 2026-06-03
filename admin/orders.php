<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';
start_admin_session();

$page_title  = 'Orders';
$active_page = 'orders';

$db = get_db();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    require_admin();
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([sanitize($_POST['status']), (int)$_POST['order_id']]);
    header('Location: orders.php?updated=1');
    exit;
}

require_once __DIR__ . '/sidebar.php';

$filter = sanitize($_GET['filter'] ?? 'all');
$sql = "SELECT o.*, u.full_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id";
if ($filter !== 'all') {
    $sql .= " WHERE o.status = " . $db->quote($filter);
}
$sql .= " ORDER BY o.created_at DESC";
$orders = $db->query($sql)->fetchAll();

$statuses = ['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'];

// View single order details
$viewOrder = null;
$orderItems = [];
if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT o.*, u.full_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $viewOrder = $stmt->fetch();
    if ($viewOrder) {
        $istmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $istmt->execute([$viewOrder['id']]);
        $orderItems = $istmt->fetchAll();
    }
}
?>

<?php if (isset($_GET['updated'])): ?>
<div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);color:#22c55e;padding:0.75rem 1.2rem;border-radius:8px;margin-bottom:1rem;font-size:0.88rem;">
  ✓ Order status updated successfully.
</div>
<?php endif; ?>

<?php if ($viewOrder): ?>
<!-- Single Order Detail View -->
<div style="margin-bottom:1rem">
  <a href="orders.php" class="btn btn-outline btn-sm">← Back to Orders</a>
</div>
<div class="section-card" style="margin-bottom:1.2rem">
  <div class="section-card-header">
    <h2><?= htmlspecialchars($viewOrder['order_number']) ?></h2>
    <?= '<span class="badge badge-' . $viewOrder['status'] . '">' . str_replace('_',' ',$viewOrder['status']) . '</span>' ?>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;padding:1.5rem">
    <div>
      <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.8rem;letter-spacing:0.08em;text-transform:uppercase">Customer</p>
      <p><strong><?= htmlspecialchars($viewOrder['full_name']) ?></strong></p>
      <p style="color:var(--text-muted);font-size:0.85rem"><?= htmlspecialchars($viewOrder['user_email']) ?></p>
      <p style="color:var(--text-muted);font-size:0.85rem"><?= htmlspecialchars($viewOrder['delivery_phone']) ?></p>
    </div>
    <div>
      <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.8rem;letter-spacing:0.08em;text-transform:uppercase">Delivery Address</p>
      <p style="font-size:0.88rem;line-height:1.6"><?= nl2br(htmlspecialchars($viewOrder['delivery_address'] . ', ' . $viewOrder['delivery_city'] . ' - ' . $viewOrder['delivery_pin'])) ?></p>
      <?php if ($viewOrder['delivery_notes']): ?>
      <p style="color:var(--text-muted);font-size:0.82rem;margin-top:0.4rem">📝 <?= htmlspecialchars($viewOrder['delivery_notes']) ?></p>
      <?php endif; ?>
    </div>
    <div>
      <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.8rem;letter-spacing:0.08em;text-transform:uppercase">Payment</p>
      <p><?= strtoupper($viewOrder['payment_method']) ?> · <span class="badge badge-<?= $viewOrder['payment_status'] ?>"><?= $viewOrder['payment_status'] ?></span></p>
    </div>
    <div>
      <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.8rem;letter-spacing:0.08em;text-transform:uppercase">Order Total</p>
      <p style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;color:var(--gold)">₹<?= number_format($viewOrder['total'],2) ?></p>
      <?php if ($viewOrder['coupon_code']): ?>
      <p style="font-size:0.8rem;color:var(--text-muted)">Coupon: <code><?= $viewOrder['coupon_code'] ?></code> (−₹<?= $viewOrder['discount'] ?>)</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.2rem">
  <div class="section-card">
    <div class="section-card-header"><h2>Items Ordered</h2></div>
    <table class="admin-table">
      <thead><tr><th>Item</th><th>Portion</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($orderItems as $item): ?>
        <tr>
          <td><?= htmlspecialchars($item['item_name']) ?></td>
          <td style="color:var(--text-muted)"><?= htmlspecialchars($item['portion_name']) ?></td>
          <td><?= $item['quantity'] ?></td>
          <td>₹<?= number_format($item['unit_price'],2) ?></td>
          <td style="color:var(--gold)">₹<?= number_format($item['line_total'],2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="section-card">
    <div class="section-card-header"><h2>Update Status</h2></div>
    <div style="padding:1.5rem">
      <form method="POST">
        <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
        <input type="hidden" name="update_status" value="1">
        <div class="form-group">
          <label>Order Status</label>
          <select name="status" class="form-control">
            <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $viewOrder['status'] === $s ? 'selected' : '' ?>><?= str_replace('_', ' ', ucfirst($s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-gold" style="width:100%">Update Status</button>
      </form>
    </div>
  </div>
</div>

<?php else: ?>
<!-- Orders List -->
<div class="section-card">
  <div class="toolbar">
    <input type="text" class="search-input" placeholder="Search orders, customers..." oninput="filterTable(this,'orders-table')">
    <select class="form-control" style="width:auto" onchange="location='orders.php?filter='+this.value">
      <option value="all" <?= $filter==='all'?'selected':'' ?>>All Statuses</option>
      <?php foreach ($statuses as $s): ?>
      <option value="<?= $s ?>" <?= $filter===$s?'selected':'' ?>><?= str_replace('_',' ',ucfirst($s)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <table class="admin-table" id="orders-table">
    <thead>
      <tr><th>Order #</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $o):
        $ic = $db->prepare("SELECT SUM(quantity) FROM order_items WHERE order_id=?");
        $ic->execute([$o['id']]);
        $itemCount = $ic->fetchColumn();
      ?>
      <tr>
        <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
        <td><?= htmlspecialchars($o['full_name']) ?></td>
        <td style="color:var(--text-muted)"><?= $itemCount ?> items</td>
        <td style="color:var(--gold)">₹<?= number_format($o['total'],2) ?></td>
        <td><span class="badge badge-<?= $o['payment_status'] ?>"><?= $o['payment_method'] ?></span></td>
        <td><?= '<span class="badge badge-'.$o['status'].'">'.str_replace('_',' ',$o['status']).'</span>' ?></td>
        <td style="color:var(--text-muted);font-size:0.82rem"><?= date('d M, h:i A', strtotime($o['created_at'])) ?></td>
        <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-outline btn-sm">Details</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($orders)): ?>
      <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem">No orders found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

  </div>
</main>
<script src="assets/admin.js"></script>
</body>
</html>
