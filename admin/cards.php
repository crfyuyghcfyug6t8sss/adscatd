<?php
$adminActive = 'cards';
$pageTitle = 'البطاقات — الإدارة';
require __DIR__ . '/_layout.php';
$cur = setting_get($pdo, 'currency', 'USD');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $st = $pdo->prepare('SELECT * FROM cards WHERE id=?');
    $st->execute([$id]);
    $card = $st->fetch();
    if ($card) {
        if ($action === 'freeze') {
            $pdo->prepare('UPDATE cards SET status="frozen" WHERE id=?')->execute([$id]);
            notify($pdo, $card['user_id'], $id, 'تم تجميد البطاقة', 'تم تجميد بطاقتك من قبل الإدارة.', 'info');
        } elseif ($action === 'unfreeze') {
            $pdo->prepare('UPDATE cards SET status="active" WHERE id=?')->execute([$id]);
            notify($pdo, $card['user_id'], $id, 'تم إلغاء تجميد البطاقة', 'تم تفعيل بطاقتك من جديد.', 'info');
        } elseif ($action === 'add') {
            $amt = (float)($_POST['amount'] ?? 0);
            $note= trim($_POST['note'] ?? 'إضافة رصيد من الإدارة');
            if ($amt > 0) {
                $pdo->prepare('UPDATE cards SET balance = balance + ? WHERE id=?')->execute([$amt,$id]);
                txn($pdo, $card['user_id'], $id, 'admin_add', $amt, $note);
                notify($pdo, $card['user_id'], $id, 'تم إضافة رصيد', 'تم إضافة '.money($amt,$cur).' إلى بطاقتك.', 'info');
            }
        } elseif ($action === 'withdraw') {
            $amt = (float)($_POST['amount'] ?? 0);
            $note= trim($_POST['note'] ?? 'سحب رصيد من الإدارة');
            if ($amt > 0) {
                $pdo->prepare('UPDATE cards SET balance = MAX(0, balance - ?) WHERE id=?')->execute([$amt,$id]);
                txn($pdo, $card['user_id'], $id, 'admin_withdraw', -$amt, $note);
                notify($pdo, $card['user_id'], $id, 'تم سحب رصيد', 'تم سحب '.money($amt,$cur).' من بطاقتك.', 'info');
            }
        } elseif ($action === 'edit') {
            $num = preg_replace('/\D/','', $_POST['card_number'] ?? $card['card_number']);
            $exp = trim($_POST['expiry'] ?? $card['expiry']);
            $cvv = preg_replace('/\D/','', $_POST['cvv'] ?? $card['cvv']);
            $holder = trim($_POST['holder_name'] ?? $card['holder_name']);
            $bal = (float)($_POST['balance'] ?? $card['balance']);
            $pdo->prepare('UPDATE cards SET holder_name=?, card_number=?, expiry=?, cvv=?, balance=? WHERE id=?')
                ->execute([$holder,$num,$exp,$cvv,$bal,$id]);
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM cards WHERE id=?')->execute([$id]);
        }
    }
    redirect('/admin/cards.php');
}

$rows = $pdo->query("SELECT c.*, u.phone FROM cards c JOIN users u ON u.id=c.user_id ORDER BY c.id DESC")->fetchAll();
?>
<h1><i data-lucide="credit-card"></i> البطاقات</h1>
<?php if(!$rows): ?><p class="empty">لا توجد بطاقات.</p><?php else: ?>
<table class="table">
<thead><tr><th>#</th><th>المستخدم</th><th>الحامل</th><th>الرقم</th><th>الانتهاء</th><th>CVV</th><th>الرصيد</th><th>الحالة</th><th></th></tr></thead>
<tbody>
<?php foreach($rows as $c): [$lbl,$cls]=status_label($c['status']); ?>
  <tr>
    <td>#<?= (int)$c['id'] ?></td>
    <td dir="ltr"><?= e($c['phone']) ?></td>
    <td><?= e($c['holder_name']) ?></td>
    <td dir="ltr"><?= e(format_card($c['card_number'])) ?></td>
    <td dir="ltr"><?= e($c['expiry']) ?></td>
    <td dir="ltr"><?= e($c['cvv']) ?></td>
    <td><?= e(money($c['balance'],$cur)) ?></td>
    <td><span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span></td>
    <td>
      <details class="row-actions">
        <summary class="btn btn-ghost sm"><i data-lucide="settings"></i> تحكم</summary>
        <div class="row-actions-pop">
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <?php if ($c['status']!=='frozen'): ?>
              <button name="action" value="freeze" class="btn btn-outline sm"><i data-lucide="snowflake"></i> تجميد</button>
            <?php else: ?>
              <button name="action" value="unfreeze" class="btn btn-outline sm"><i data-lucide="sun"></i> إلغاء التجميد</button>
            <?php endif; ?>
          </form>
          <form method="post" class="inline-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <input type="hidden" name="action" value="add">
            <input type="number" step="0.01" name="amount" placeholder="مبلغ +">
            <button class="btn btn-primary sm"><i data-lucide="plus"></i> إضافة</button>
          </form>
          <form method="post" class="inline-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <input type="hidden" name="action" value="withdraw">
            <input type="number" step="0.01" name="amount" placeholder="مبلغ −">
            <button class="btn btn-outline sm"><i data-lucide="minus"></i> سحب</button>
          </form>
          <details class="edit-card">
            <summary class="btn btn-ghost sm"><i data-lucide="pencil"></i> تعديل</summary>
            <form method="post" class="form mt-8">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <input type="hidden" name="action" value="edit">
              <label><span>الحامل</span><input name="holder_name" value="<?= e($c['holder_name']) ?>"></label>
              <label><span>الرقم</span><input name="card_number" dir="ltr" value="<?= e($c['card_number']) ?>"></label>
              <label><span>الانتهاء</span><input name="expiry" dir="ltr" value="<?= e($c['expiry']) ?>"></label>
              <label><span>CVV</span><input name="cvv" dir="ltr" value="<?= e($c['cvv']) ?>"></label>
              <label><span>الرصيد</span><input type="number" step="0.01" name="balance" value="<?= e($c['balance']) ?>"></label>
              <button class="btn btn-primary sm"><i data-lucide="save"></i> حفظ</button>
            </form>
          </details>
          <form method="post" class="inline" onsubmit="return confirm('حذف البطاقة؟');">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button name="action" value="delete" class="btn btn-outline sm danger"><i data-lucide="trash-2"></i> حذف</button>
          </form>
        </div>
      </details>
    </td>
  </tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
