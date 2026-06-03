<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';
start_admin_session();
require_admin();

$page_title  = 'Menu Management';
$active_page = 'menu';
$db = get_db();
$msg = '';
$err = '';

// ── Handle Add/Edit item ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['save_item'])) {
        $id       = (int)($_POST['item_id'] ?? 0);
        $name     = sanitize($_POST['name'] ?? '');
        $desc     = sanitize($_POST['description'] ?? '');
        $category = sanitize($_POST['category'] ?? '');
        $tag      = sanitize($_POST['tag'] ?? '');
        $avail    = isset($_POST['is_available']) ? 1 : 0;

        // Handle image upload
        $image = sanitize($_POST['existing_image'] ?? '');
        if (!empty($_FILES['image']['tmp_name'])) {
            if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0777, true);
            $ext  = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fname = uniqid('menu_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $fname);
            $image = 'assets/uploads/' . $fname;
        }

        if (!$name || !$desc || !$category || !$image) {
            $err = 'Name, description, category, and image are required.';
        } else {
            if ($id) {
                $stmt = $db->prepare("UPDATE menu_items SET name=?,description=?,category=?,tag=?,image=?,is_available=? WHERE id=?");
                $stmt->execute([$name,$desc,$category,$tag,$image,$avail,$id]);
            } else {
                $stmt = $db->prepare("INSERT INTO menu_items (name,description,category,tag,image,is_available) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$name,$desc,$category,$tag,$image,$avail]);
                $id = $db->lastInsertId();
            }

            // Save portions
            $db->prepare("DELETE FROM item_portions WHERE item_id=?")->execute([$id]);
            $portionNames  = $_POST['portion_name']  ?? [];
            $portionPrices = $_POST['portion_price']  ?? [];
            $portionDefault = (int)($_POST['portion_default'] ?? 0);
            foreach ($portionNames as $i => $pname) {
                if (trim($pname) === '') continue;
                $stmt = $db->prepare("INSERT INTO item_portions (item_id,portion_name,price,is_default) VALUES (?,?,?,?)");
                $stmt->execute([$id, sanitize($pname), (float)$portionPrices[$i], $i === $portionDefault ? 1 : 0]);
            }
            $msg = 'Menu item saved successfully.';
        }
    }

    if (isset($_POST['delete_item'])) {
        $db->prepare("DELETE FROM menu_items WHERE id=?")->execute([(int)$_POST['item_id']]);
        $msg = 'Item deleted.';
    }
}

$items = $db->query("SELECT * FROM menu_items ORDER BY category, id")->fetchAll();
foreach ($items as &$it) {
    $s = $db->prepare("SELECT * FROM item_portions WHERE item_id=? ORDER BY price");
    $s->execute([$it['id']]);
    $it['portions'] = $s->fetchAll();
}

require_once __DIR__ . '/sidebar.php';
?>

<?php if ($msg): ?><div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2);color:#22c55e;padding:0.75rem 1.2rem;border-radius:8px;margin-bottom:1rem;font-size:0.88rem;">✓ <?= $msg ?></div><?php endif; ?>
<?php if ($err): ?><div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:#ef4444;padding:0.75rem 1.2rem;border-radius:8px;margin-bottom:1rem;font-size:0.88rem;">✗ <?= $err ?></div><?php endif; ?>

<div class="section-card">
  <div class="section-card-header">
    <h2>Menu Items (<?= count($items) ?>)</h2>
    <button class="btn btn-gold" onclick="openModal('add-modal')">+ Add Item</button>
  </div>
  <div class="toolbar">
    <input type="text" class="search-input" placeholder="Search items..." oninput="filterTable(this,'menu-table')">
  </div>
  <table class="admin-table" id="menu-table">
    <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Portions / Prices</th><th>Tag</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><img src="../<?= htmlspecialchars($it['image']) ?>" class="img-preview" onerror="this.src='../assets/chicken_wings_1780138004264.png'"></td>
        <td><strong><?= htmlspecialchars($it['name']) ?></strong></td>
        <td><span class="badge badge-confirmed"><?= $it['category'] ?></span></td>
        <td style="font-size:0.82rem;color:var(--text-muted)">
          <?php foreach ($it['portions'] as $p): ?>
            <?= htmlspecialchars($p['portion_name']) ?>: ₹<?= $p['price'] ?><?= $p['is_default'] ? ' ✦' : '' ?><br>
          <?php endforeach; ?>
        </td>
        <td style="color:var(--text-muted)"><?= htmlspecialchars($it['tag'] ?? '—') ?></td>
        <td><?= $it['is_available'] ? '<span class="badge badge-active">Available</span>' : '<span class="badge badge-inactive">Hidden</span>' ?></td>
        <td>
          <button class="btn btn-outline btn-sm" onclick='editItem(<?= json_encode($it, JSON_HEX_APOS) ?>)'>Edit</button>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete this item?')">
            <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
            <button type="submit" name="delete_item" class="btn btn-danger btn-sm">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="add-modal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <h3 id="modal-title">Add Menu Item</h3>
      <button class="modal-close" onclick="closeModal('add-modal')">×</button>
    </div>
    <form method="POST" enctype="multipart/form-data" id="item-form">
      <input type="hidden" name="save_item" value="1">
      <input type="hidden" name="item_id" id="field-id" value="0">
      <input type="hidden" name="existing_image" id="field-existing-image" value="">
      <div class="form-row">
        <div class="form-group">
          <label>Item Name *</label>
          <input type="text" name="name" id="field-name" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Category *</label>
          <select name="category" id="field-category" class="form-control" required>
            <option value="wings">Wings</option>
            <option value="burgers">Burgers</option>
            <option value="grilled">Grilled</option>
            <option value="combos">Combos</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Description *</label>
        <textarea name="description" id="field-desc" class="form-control" rows="2" required></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Tag (optional)</label>
          <input type="text" name="tag" id="field-tag" class="form-control" placeholder="e.g. Bestseller, New, 🔥 Hot">
        </div>
        <div class="form-group" style="display:flex;align-items:flex-end;gap:0.5rem;padding-bottom:0.1rem">
          <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer">
            <input type="checkbox" name="is_available" id="field-available" checked> Available on Menu
          </label>
        </div>
      </div>
      <div class="form-group">
        <label>Food Image *</label>
        <input type="file" name="image" class="form-control" accept="image/*" onchange="previewImage(this,'img-prev')">
        <img id="img-prev" src="" class="img-preview" style="margin-top:0.5rem;display:none">
      </div>

      <div style="border-top:1px solid var(--border);padding-top:1rem;margin-top:0.5rem">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem">
          <label style="font-size:0.8rem;color:var(--text-muted);letter-spacing:0.08em;text-transform:uppercase">Portions & Prices</label>
          <button type="button" class="btn btn-outline btn-sm" onclick="addPortionRow()">+ Add Portion</button>
        </div>
        <div id="portions-container"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('add-modal')">Cancel</button>
        <button type="submit" class="btn btn-gold">Save Item</button>
      </div>
    </form>
  </div>
</div>

  </div>
</main>
<script src="assets/admin.js"></script>
<script>
let portionCount = 0;

function addPortionRow(name='', price='', isDefault=false) {
  portionCount++;
  const idx = portionCount - 1;
  const div = document.createElement('div');
  div.style.cssText = 'display:grid;grid-template-columns:1fr 100px 80px 30px;gap:0.5rem;margin-bottom:0.5rem;align-items:center';
  div.innerHTML = `
    <input type="text" name="portion_name[]" class="form-control" placeholder="e.g. Half Plate" value="${name}" required>
    <input type="number" name="portion_price[]" class="form-control" placeholder="₹0" step="0.01" value="${price}" required>
    <label style="display:flex;align-items:center;gap:0.3rem;font-size:0.78rem;cursor:pointer">
      <input type="radio" name="portion_default" value="${idx}" ${isDefault?'checked':''}> Default
    </label>
    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:1.1rem">×</button>
  `;
  document.getElementById('portions-container').appendChild(div);
}

function editItem(item) {
  document.getElementById('modal-title').textContent = 'Edit Item';
  document.getElementById('field-id').value = item.id;
  document.getElementById('field-name').value = item.name;
  document.getElementById('field-category').value = item.category;
  document.getElementById('field-desc').value = item.description;
  document.getElementById('field-tag').value = item.tag || '';
  document.getElementById('field-available').checked = item.is_available == 1;
  document.getElementById('field-existing-image').value = item.image;
  const prev = document.getElementById('img-prev');
  prev.src = '../' + item.image;
  prev.style.display = 'block';

  // Render portions
  portionCount = 0;
  document.getElementById('portions-container').innerHTML = '';
  (item.portions || []).forEach((p, i) => addPortionRow(p.portion_name, p.price, p.is_default == 1));

  openModal('add-modal');
}

// Reset form on add new
document.querySelector('[onclick="openModal(\'add-modal\')"]').addEventListener('click', () => {
  document.getElementById('modal-title').textContent = 'Add Menu Item';
  document.getElementById('item-form').reset();
  document.getElementById('field-id').value = 0;
  document.getElementById('field-existing-image').value = '';
  document.getElementById('img-prev').style.display = 'none';
  portionCount = 0;
  document.getElementById('portions-container').innerHTML = '';
  addPortionRow('Half Plate', '', true);
  addPortionRow('Full Plate', '');
});
</script>
</body>
</html>
