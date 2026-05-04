<?php
$adminActive = 'pm';
$pageTitle = 'طرق الدفع — الإدارة';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $details = trim($_POST['details'] ?? '');
        if ($name !== '') {
            $pdo->prepare('INSERT INTO payment_methods (name, details) VALUES (?,?)')->execute([$name, $details]);
            flash_set('ok','تمت الإضافة.');
        }
    } elseif ($action === 'update') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $details = trim($_POST['details'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $pdo->prepare('UPDATE payment_methods SET name=?, details=?, active=? WHERE id=?')->execute([$name,$details,$active,$id]);
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM payment_methods WHERE id=?')->execute([(int)$_POST['id']]);
    }
    redirect('/admin/payment_methods.php');
}

$rows = $pdo->query('SELECT * FROM payment_methods ORDER BY id DESC')->fetchAll();
?>
<h1><i data-lucide="wallet"></i> طرق الدفع اليدوية</h1>

<div class="panel">
  <div class="panel-head"><h3><i data-lucide="plus"></i> إضافة طريقة جديدة</h3></div>
  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add">
    <label><span>الاسم</span><input name="name" required placeholder="USDT TRC20 / Western Union / ..."></label>
    <label><span>التفاصيل (تظهر للمستخدم)</span><textarea name="details" rows="3" required></textarea></label>
    <button class="btn btn-primary"><i data-lucide="plus"></i> إضافة</button>
  </form>
</div>

<?php foreach($rows as $r): ?>
<div class="panel pm-edit">
  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <input type="hidden" name="action" value="update">
    <div class="grid-2">
      <label><span>الاسم</span><input name="name" value="<?= e($r['name']) ?>" required></label>
      <label class="check"><input type="checkbox" name="active" <?= $r['active']?'checked':'' ?>> <span>مفعّل</span></label>
    </div>
    <label><span>التفاصيل</span><textarea name="details" rows="4"><?= e($r['details']) ?></textarea></label>
    <div class="row-gap">
      <button class="btn btn-primary sm"><i data-lucide="save"></i> حفظ</button>
    </div>
  </form>
  <form method="post" class="inline" onsubmit="return confirm('حذف هذه الطريقة؟');">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <button name="action" value="delete" class="btn btn-outline sm danger"><i data-lucide="trash-2"></i> حذف</button>
  </form>
</div>
<?php endforeach; ?>
<?php require __DIR__ . '/_footer.php'; ?>
