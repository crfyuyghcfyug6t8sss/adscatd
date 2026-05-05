<?php
$adminActive = 'dep';
$pageTitle = 'إيداعات المحفظة — الإدارة';
require __DIR__ . '/_layout.php';
$cur = setting_get($pdo, 'currency', 'USD');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $st = $pdo->prepare('SELECT * FROM wallet_deposits WHERE id=?');
    $st->execute([$id]);
    $d = $st->fetch();
    if ($d && $d['status'] === 'pending') {
        if ($action === 'approve') {
            wallet_add($pdo, (int)$d['user_id'], (float)$d['amount'], 'إيداع #' . $id . ' عبر طرق الدفع اليدوية');
            $pdo->prepare('UPDATE wallet_deposits SET status="approved" WHERE id=?')->execute([$id]);
            notify($pdo, (int)$d['user_id'], null, 'تمت الموافقة على إيداع المحفظة', 'تم إضافة ' . money($d['amount'], $cur) . ' إلى محفظتك.', 'info');
            flash_set('ok', 'تمت الموافقة وإضافة الرصيد للمحفظة.');
        } elseif ($action === 'reject') {
            $note = trim($_POST['note'] ?? '');
            $pdo->prepare('UPDATE wallet_deposits SET status="rejected", admin_note=? WHERE id=?')->execute([$note, $id]);
            notify($pdo, (int)$d['user_id'], null, 'تم رفض إيداع المحفظة', $note ?: 'تم رفض طلب الإيداع.', 'info');
            flash_set('ok', 'تم رفض الإيداع.');
        }
    }
    redirect('/admin/wallet_deposits.php');
}

$filter = $_GET['s'] ?? 'pending';
$where = $filter === 'all' ? '' : 'WHERE wd.status = ' . $pdo->quote($filter);
$rows = $pdo->query("SELECT wd.*, u.phone, pm.name AS pm_name FROM wallet_deposits wd
    JOIN users u ON u.id = wd.user_id
    LEFT JOIN payment_methods pm ON pm.id = wd.payment_method_id
    $where ORDER BY wd.id DESC")->fetchAll();
?>
<h1><i data-lucide="wallet"></i> إيداعات المحفظة</h1>
<div class="filters">
  <a href="?s=pending"  class="chip <?= $filter==='pending'?'on':'' ?>">قيد المراجعة</a>
  <a href="?s=approved" class="chip <?= $filter==='approved'?'on':'' ?>">المقبولة</a>
  <a href="?s=rejected" class="chip <?= $filter==='rejected'?'on':'' ?>">المرفوضة</a>
  <a href="?s=all"      class="chip <?= $filter==='all'?'on':'' ?>">الكل</a>
</div>

<?php if (!$rows): ?>
  <p class="empty">لا توجد طلبات.</p>
<?php else: ?>
<table class="table">
  <thead><tr><th>#</th><th>المستخدم</th><th>المبلغ</th><th>الطريقة</th><th>المرجع</th><th>الملاحظة</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): [$lbl,$cls] = status_label($r['status']); ?>
    <tr>
      <td>#<?= (int)$r['id'] ?></td>
      <td dir="ltr"><?= e($r['phone']) ?></td>
      <td><?= e(money($r['amount'], $cur)) ?></td>
      <td><?= e($r['pm_name'] ?: '—') ?></td>
      <td dir="ltr"><?= e($r['payment_reference'] ?: '—') ?></td>
      <td class="muted"><?= e($r['note'] ?: '—') ?></td>
      <td><span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span></td>
      <td class="muted small"><?= e($r['created_at']) ?></td>
      <td>
        <?php if ($r['status'] === 'pending'): ?>
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button name="action" value="approve" class="btn btn-primary sm"><i data-lucide="check"></i> قبول</button>
          </form>
          <details class="inline">
            <summary class="btn btn-outline sm"><i data-lucide="x"></i> رفض</summary>
            <form method="post" class="form mt-8">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <input name="note" placeholder="سبب الرفض" required>
              <button class="btn btn-outline sm">تأكيد</button>
            </form>
          </details>
        <?php else: ?>
          <span class="muted small"><?= e($r['admin_note'] ?: '—') ?></span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
