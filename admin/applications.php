<?php
$adminActive = 'apps';
$pageTitle = 'طلبات الإصدار — الإدارة';
require __DIR__ . '/_layout.php';
$cur = setting_get($pdo, 'currency', 'USD');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM applications WHERE id = ?');
    $st->execute([$id]);
    $app = $st->fetch();
    if ($app) {
        if ($action === 'reject') {
            $note = trim($_POST['note'] ?? '');
            $pdo->prepare('UPDATE applications SET status="rejected", admin_note=? WHERE id=?')->execute([$note, $id]);
            notify($pdo, $app['user_id'], null, 'تم رفض طلب الإصدار #'.$id, $note ?: 'تم رفض الطلب من قبل الإدارة.', 'info');
            flash_set('ok','تم رفض الطلب.');
        } elseif ($action === 'approve') {
            $holder = trim($_POST['holder_name'] ?? $app['full_name']);
            $num = preg_replace('/\D/','', $_POST['card_number'] ?? '');
            $exp = trim($_POST['expiry'] ?? '');
            $cvv = preg_replace('/\D/','', $_POST['cvv'] ?? '');
            $bal = (float)($_POST['balance'] ?? $app['initial_charge']);
            if (strlen($num) < 13 || strlen($num) > 19) { $num = gen_card_number(); }
            if (!preg_match('#^\d{2}/\d{2}$#', $exp)) { $exp = gen_expiry(); }
            if (strlen($cvv) < 3) { $cvv = gen_cvv(); }
            $pdo->prepare('INSERT INTO cards (user_id, application_id, holder_name, card_number, expiry, cvv, balance) VALUES (?,?,?,?,?,?,?)')
                ->execute([$app['user_id'], $app['id'], $holder, $num, $exp, $cvv, $bal]);
            $cardId = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE applications SET status="approved" WHERE id=?')->execute([$id]);
            txn($pdo, $app['user_id'], $cardId, 'card_issued', $bal, 'إصدار بطاقة جديدة');
            notify($pdo, $app['user_id'], $cardId, 'تمت الموافقة وإصدار بطاقتك', 'تم إصدار بطاقتك بنجاح. يمكنك مشاهدتها من قسم بطاقاتي.', 'info');
            flash_set('ok','تمت الموافقة وإصدار البطاقة.');
        }
    }
    redirect('/admin/applications.php');
}

$filter = $_GET['s'] ?? 'pending';
$where = $filter === 'all' ? '' : 'WHERE a.status = ' . $pdo->quote($filter);
$rows = $pdo->query("SELECT a.*, u.phone FROM applications a JOIN users u ON u.id=a.user_id $where ORDER BY a.id DESC")->fetchAll();
?>
<h1><i data-lucide="file-text"></i> طلبات الإصدار</h1>
<div class="filters">
  <a href="?s=pending"  class="chip <?= $filter==='pending'?'on':'' ?>">قيد المراجعة</a>
  <a href="?s=approved" class="chip <?= $filter==='approved'?'on':'' ?>">المقبولة</a>
  <a href="?s=rejected" class="chip <?= $filter==='rejected'?'on':'' ?>">المرفوضة</a>
  <a href="?s=all"      class="chip <?= $filter==='all'?'on':'' ?>">الكل</a>
</div>

<?php if(!$rows): ?><p class="empty">لا توجد طلبات.</p><?php else: ?>
<div class="apps-list">
<?php foreach($rows as $a): [$lbl,$cls]=status_label($a['status']); $total=$a['initial_charge']+$a['issuance_fee']+$a['deposit']; ?>
  <article class="panel app-row">
    <header class="panel-head">
      <div>
        <h3>طلب #<?= (int)$a['id'] ?> — <?= e($a['full_name']) ?></h3>
        <p class="muted small">هاتف: <span dir="ltr"><?= e($a['phone']) ?></span> · تاريخ الميلاد: <?= e($a['dob']) ?> · الدولة: <?= e($a['country']) ?></p>
      </div>
      <span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span>
    </header>
    <div class="grid-3 small-info">
      <div><span class="muted">الشحن المبدئي</span><strong><?= e(money($a['initial_charge'],$cur)) ?></strong></div>
      <div><span class="muted">رسوم الإصدار</span><strong><?= e(money($a['issuance_fee'],$cur)) ?></strong></div>
      <div><span class="muted">الإيداع</span><strong><?= e(money($a['deposit'],$cur)) ?></strong></div>
      <div><span class="muted">المرجع</span><strong dir="ltr"><?= e($a['payment_reference'] ?: '—') ?></strong></div>
      <div><span class="muted">المجموع</span><strong><?= e(money($total,$cur)) ?></strong></div>
      <div><span class="muted">العنوان</span><strong><?= e($a['address'] ?: '—') ?></strong></div>
    </div>
    <?php if ($a['status']==='pending'): ?>
    <details class="approve-form">
      <summary class="btn btn-primary"><i data-lucide="check"></i> موافقة وإصدار البطاقة</summary>
      <form method="post" class="form mt-12">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
        <input type="hidden" name="action" value="approve">
        <div class="grid-2">
          <label><span>اسم حامل البطاقة</span><input name="holder_name" value="<?= e($a['full_name']) ?>"></label>
          <label><span>رقم البطاقة (16 رقم)</span><input name="card_number" placeholder="فارغ = توليد تلقائي" dir="ltr" maxlength="19"></label>
          <label><span>الانتهاء MM/YY</span><input name="expiry" placeholder="فارغ = توليد تلقائي" dir="ltr"></label>
          <label><span>CVV</span><input name="cvv" placeholder="فارغ = توليد تلقائي" dir="ltr"></label>
          <label><span>الرصيد المبدئي</span><input type="number" step="0.01" name="balance" value="<?= e($a['initial_charge']) ?>"></label>
        </div>
        <button class="btn btn-primary" type="submit"><i data-lucide="check"></i> إصدار البطاقة</button>
      </form>
    </details>
    <details class="reject-form">
      <summary class="btn btn-outline"><i data-lucide="x"></i> رفض</summary>
      <form method="post" class="form mt-12">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
        <input type="hidden" name="action" value="reject">
        <label><span>سبب الرفض</span><input name="note" required></label>
        <button class="btn btn-outline" type="submit"><i data-lucide="x"></i> تأكيد الرفض</button>
      </form>
    </details>
    <?php elseif ($a['admin_note']): ?>
      <p class="muted small"><i data-lucide="message-square"></i> ملاحظة: <?= e($a['admin_note']) ?></p>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
