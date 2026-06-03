<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';
start_admin_session();
require_admin();

$page_title  = 'Offers & Coupons';
$active_page = 'offers';
$db = get_db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_offer'])) {
        $id     = (int)($_POST['offer_id'] ?? 0);
        $code   = strtoupper(sanitize($_POST['code'] ?? ''));
        $title  = sanitize($_POST['title'] ?? '');
        $desc   = sanitize($_POST['description'] ?? '');
        $type   = sanitize($_POST['discount_type'] ?? 'percent');
        $value  = (float)($_POST['discount_value'] ?? 0);
        $min    = (float)($_POST['min_order_value'] ?? 0);
        $from   = sanitize($_POST['valid_from'] ?? '');
        $until  = sanitize($_POST['valid_until'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($id) {
            $stmt = $db->prepare("UPDATE offers SET code=?,title=?,description=?,discount_type=?,discount_value=?,min_order_value=?,valid_from=?,valid_until=?,is_active=? WHERE id=?");
            $stmt->execute([$code,$title,$desc,$type,$value,$min,$from,$until,$active,$id]);
        } else {
            $stmt = $db->prepare("INSERT INTO offers (code,title,description,discount_type,discount_value,min_order_value,valid_from,valid_until,is_active) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$code,$title,$desc,$type,$value,$min,$from,$until,$active]);
        }
        $msg = 'Offer saved.';
    }
    if (isset($_POST['delete_offer'])) {
        $db->prepare("DELETE FROM offers WHERE id=?")->execute([(int)$_POST['offer_id']]);
        $msg = 'Offer deleted.';
    }
    if (isset($_POST['toggle_offer'])) {
        $curr = $db->prepare("SELECT is_active FROM offers WHERE id=?");
        $curr->execute([(int)$_POST['offer_id']]);
        $new = $curr->fetchColumn() ? 0 : 1;
        $db->prepare("UPDATE offers SET is_active=? WHERE id=?")->execute([$new,(int)$_POST['offer_id']]);
        $msg = 'Offer status toggled.';
    }
    header('Location: offers.php?msg=' . urlencode($msg));
    exit;
}

$msg = sanitize($_GET['msg'] ?? '');
$offers = $db->query("SELECT * FROM offers ORDER BY id DESC")->fetchAll();
require_once __DIR__ . '/sidebar.php';
?>

<?php if ($msg): ?><div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);color:#22c55e;padding:0.75rem 1.2rem;border-radius:8px;margin-bottom:1rem;font-size:0.88rem;">✓ <?= $msg ?></div><?php endif; ?>

<div class="section-card">
  <div class="section-card-header">
    <h2>Offers & Coupons (<?= count($offers) ?>)</h2>
    <button class="btn btn-gold" onclick="openModal('offer-modal'); resetForm()">+ Add Coupon</button>
  </div>
  <table class="admin-table">
    <thead><tr><th>Code</th><th>Title</th><th>Discount</th><th>Min Order</th><th>Valid Until</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($offers as $o): ?>
      <tr>
        <td><code style="background:rgba(255,184,0,0.1);color:var(--gold);padding:0.2rem 0.5rem;border-radius:4px;font-size:0.85rem"><?= htmlspecialchars($o['code']) ?></code></td>
        <td><?= htmlspecialchars($o['title']) ?></td>
        <td><?= $o['discount_type'] === 'percent' ? $o['discount_value'].'%' : '₹'.number_format($o['discount_value'],2) ?> off</td>
        <td>₹<?= $o['min_order_value'] > 0 ? number_format($o['min_order_value'],0) : '—' ?></td>
        <td style="color:var(--text-muted)"><?= date('d M Y', strtotime($o['valid_until'])) ?></td>
        <td>
          <form method="POST" style="display:inline">
            <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
            <button type="submit" name="toggle_offer" class="badge <?= $o['is_active'] ? 'badge-active' : 'badge-inactive' ?>" style="cursor:pointer;border:none;background:inherit">
              <?= $o['is_active'] ? 'Active' : 'Inactive' ?>
            </button>
          </form>
        </td>
        <td>
          <button class="btn btn-outline btn-sm" onclick='editOffer(<?= json_encode($o, JSON_HEX_APOS) ?>)'>Edit</button>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this offer?')">
            <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
            <button type="submit" name="delete_offer" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($offers)): ?><tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem">No offers yet.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<div class="modal-overlay" id="offer-modal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="offer-modal-title">Add Coupon</h3>
      <button class="modal-close" onclick="closeModal('offer-modal')">×</button>
    </div>
    <form method="POST" id="offer-form">
      <input type="hidden" name="save_offer" value="1">
      <input type="hidden" name="offer_id" id="o-id" value="0">
      <div class="form-row">
        <div class="form-group">
          <label>Coupon Code *</label>
          <input type="text" name="code" id="o-code" class="form-control" required placeholder="e.g. SAVE20" style="text-transform:uppercase">
        </div>
        <div class="form-group">
          <label>Title *</label>
          <input type="text" name="title" id="o-title" class="form-control" required placeholder="e.g. Welcome Treat">
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" id="o-desc" class="form-control" rows="2"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Discount Type</label>
          <select name="discount_type" id="o-type" class="form-control">
            <option value="percent">Percentage (%)</option>
            <option value="flat">Flat Amount (₹)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Discount Value *</label>
          <input type="number" name="discount_value" id="o-value" class="form-control" required placeholder="e.g. 20" step="0.01">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Min Order Value (₹)</label>
          <input type="number" name="min_order_value" id="o-min" class="form-control" value="0" step="0.01">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end">
          <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
            <input type="checkbox" name="is_active" id="o-active" checked> Active
          </label>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Valid From *</label>
          <input type="date" name="valid_from" id="o-from" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Valid Until *</label>
          <input type="date" name="valid_until" id="o-until" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('offer-modal')">Cancel</button>
        <button type="submit" class="btn btn-gold">Save Coupon</button>
      </div>
    </form>
  </div>
</div>

  </div>
</main>
<script src="assets/admin.js"></script>
<script>
function resetForm() {
  document.getElementById('offer-modal-title').textContent = 'Add Coupon';
  document.getElementById('offer-form').reset();
  document.getElementById('o-id').value = 0;
}
function editOffer(o) {
  document.getElementById('offer-modal-title').textContent = 'Edit Coupon';
  document.getElementById('o-id').value = o.id;
  document.getElementById('o-code').value = o.code;
  document.getElementById('o-title').value = o.title;
  document.getElementById('o-desc').value = o.description;
  document.getElementById('o-type').value = o.discount_type;
  document.getElementById('o-value').value = o.discount_value;
  document.getElementById('o-min').value = o.min_order_value;
  document.getElementById('o-from').value = o.valid_from;
  document.getElementById('o-until').value = o.valid_until;
  document.getElementById('o-active').checked = o.is_active == 1;
  openModal('offer-modal');
}
</script>
</body>
</html>
