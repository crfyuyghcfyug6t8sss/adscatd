<?php
$adminActive = 'charges';
$pageTitle = 'طلبات الشحن — الإدارة';
require __DIR__ . '/_layout.php';
$cur = setting_get($pdo, 'currency', 'USD');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $st = $pdo->prepare('SELECT * FROM charge_requests WHERE id=?');
    $st->execute([$id]);
    $cr = $st->fetch();
    if ($cr) {
        if ($action === 'approve') {
            $pdo->prepare('UPDATE cards SET balance = balance + ? WHERE id=?')->execute([$cr['amount'], $cr['card_id']]);
            $pdo->prepare('UPDATE charge_requests SET status="approved" WHERE id=?')->execute([$id]);
            txn($pdo, $cr['user_id'], $cr['card_id'], 'charge', $cr['amount'], 'تمت الموافقة على شحن البطاقة');
            notify($pdo, $cr['user_id'], $cr['card_id'], 'تمت الموافقة على شحن بطاقتك', 'تم إضافة '.money($cr['amount'],$cur).' إلى رصيدك.', 'info');
        } elseif ($action === 'reject') {
            $note = trim($_POST['note'] ?? '');
            $pdo->prepare('UPDATE charge_requests SET status="rejected", admin_note=? WHERE id=?')->execute([$note,$id]);
            notify($pdo, $cr['user_id'], $cr['card_id'], 'رفض طلب شحن البطاقة', $note ?: 'تم رفض طلب الشحن.', 'info');
        }
    }
    redirect('/admin/charges.php');
}

$filter = $_GET['s'] ?? 'pending';
$where = $filter === 'all' ? '' : 'WHERE cr.status = ' . $pdo->quote($filter);
$rows = $pdo->query("SELECT cr.*, u.phone, c.card_number FROM charge_requests cr
                     JOIN users u ON u.id=cr.user_id
                     JOIN cards c ON c.id=cr.card_id $where ORDER BY cr.id DESC")->fetchAll();
?>
<h1><i data-lucide="refresh-cw"></i> طلبات الشحن</h1>
<div class="filters">
  <a href="?s=pending"  class="chip <?= $filter==='pending'?'on':'' ?>">قيد المراجعة</a>
  <a href="?s=approved" class="chip <?= $filter==='approved'?'on':'' ?>">المقبولة</a>
  <a href="?s=rejected" class="chip <?= $filter==='rejected'?'on':'' ?>">المرفوضة</a>
  <a href="?s=all"      class="chip <?= $filter==='all'?'on':'' ?>">الكل</a>
</div>
<?php if(!$rows): ?><p class="empty">لا يوجد.</p><?php else: ?>
<table class="table">
<thead><tr><th>#</th><th>المستخدم</th><th>البطاقة</th><th>المبلغ</th><th>الرسوم</th><th>المرجع</th><th>الحالة</th><th></th></tr></thead>
<tbody>
<?php foreach($rows as $r): [$lbl,$cls]=status_label($r['status']); ?>
<tr>
  <td>#<?= (int)$r['id'] ?></td>
  <td dir="ltr"><?= e($r['phone']) ?></td>
  <td dir="ltr"><?= e(mask_card($r['card_number'])) ?></td>
  <td><?= e(money($r['amount'],$cur)) ?></td>
  <td><?= e(money($r['fee'],$cur)) ?></td>
  <td dir="ltr"><?= e($r['payment_reference'] ?: '—') ?></td>
  <td><span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span></td>
  <td>
    <?php if ($r['status']==='pending'): ?>
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
</tbody></table>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
