<?php
$adminActive = 'users';
$pageTitle = 'المستخدمون — الإدارة';
require __DIR__ . '/_layout.php';
$cur = setting_get($pdo, 'currency', 'USD');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id === (int)$me['id'] && in_array($action,['block','remove_admin','delete'])) {
        flash_set('bad','لا يمكنك تعديل حسابك من هنا.'); redirect('/admin/users.php');
    }
    if ($action === 'block') {
        $pdo->prepare('UPDATE users SET is_blocked=1 WHERE id=?')->execute([$id]);
    } elseif ($action === 'unblock') {
        $pdo->prepare('UPDATE users SET is_blocked=0 WHERE id=?')->execute([$id]);
    } elseif ($action === 'reset_pw') {
        $new = $_POST['new_password'] ?? '';
        if (strlen($new) >= 6) {
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_BCRYPT), $id]);
            flash_set('ok','تم تغيير كلمة السر.');
        } else {
            flash_set('bad','كلمة السر قصيرة.');
        }
    } elseif ($action === 'wallet_add') {
        $amt = (float)($_POST['amount'] ?? 0);
        if ($amt > 0) {
            wallet_add($pdo, $id, $amt, 'إضافة رصيد محفظة من الإدارة');
            notify($pdo, $id, null, 'تم إضافة رصيد لمحفظتك', 'تم إضافة ' . money($amt, $cur) . ' من قبل الإدارة.', 'info');
        }
    } elseif ($action === 'wallet_withdraw') {
        $amt = (float)($_POST['amount'] ?? 0);
        if ($amt > 0) {
            $pdo->prepare('UPDATE users SET wallet_balance = MAX(0, wallet_balance - ?) WHERE id=?')->execute([$amt, $id]);
            txn($pdo, $id, null, 'wallet_admin_withdraw', -$amt, 'سحب رصيد محفظة من الإدارة');
            notify($pdo, $id, null, 'تم سحب رصيد من محفظتك', 'تم سحب ' . money($amt, $cur) . ' من محفظتك من قبل الإدارة.', 'info');
        }
    } elseif ($action === 'make_admin') {
        $pdo->prepare('UPDATE users SET is_admin=1 WHERE id=?')->execute([$id]);
    } elseif ($action === 'remove_admin') {
        $pdo->prepare('UPDATE users SET is_admin=0 WHERE id=?')->execute([$id]);
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
    }
    redirect('/admin/users.php');
}

$rows = $pdo->query("SELECT u.*,
   (SELECT COUNT(*) FROM cards WHERE user_id=u.id) AS cards_count,
   (SELECT COALESCE(SUM(balance),0) FROM cards WHERE user_id=u.id) AS bal_sum
   FROM users u ORDER BY u.id DESC")->fetchAll();
?>
<h1><i data-lucide="users"></i> المستخدمون</h1>
<table class="table">
<thead><tr><th>#</th><th>الهاتف</th><th>الاسم</th><th>المحفظة</th><th>بطاقات</th><th>رصيد البطاقات</th><th>الحالة</th><th>التحكم</th></tr></thead>
<tbody>
<?php foreach($rows as $u): ?>
<tr>
  <td>#<?= (int)$u['id'] ?> <?= ((int)$u['is_admin']===1?'<span class="badge ok">ADMIN</span>':'') ?></td>
  <td dir="ltr"><?= e($u['phone']) ?></td>
  <td><?= e($u['full_name']) ?></td>
  <td><strong><?= e(money($u['wallet_balance'] ?? 0, $cur)) ?></strong></td>
  <td><?= (int)$u['cards_count'] ?></td>
  <td><?= e(money($u['bal_sum'],$cur)) ?></td>
  <td><?= ((int)$u['is_blocked']===1) ? '<span class="badge bad">موقوف</span>' : '<span class="badge ok">نشط</span>' ?></td>
  <td>
    <?php if ((int)$u['id'] !== (int)$me['id']): ?>
    <details class="row-actions">
      <summary class="btn btn-ghost sm"><i data-lucide="settings"></i> تحكم</summary>
      <div class="row-actions-pop">
        <form method="post" class="inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <?php if ((int)$u['is_blocked']===1): ?>
            <button name="action" value="unblock" class="btn btn-outline sm"><i data-lucide="check"></i> إلغاء الإيقاف</button>
          <?php else: ?>
            <button name="action" value="block" class="btn btn-outline sm danger"><i data-lucide="ban"></i> إيقاف</button>
          <?php endif; ?>
        </form>
        <form method="post" class="inline-form">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="hidden" name="action" value="wallet_add">
          <input type="number" step="0.01" name="amount" placeholder="إضافة محفظة">
          <button class="btn btn-primary sm"><i data-lucide="plus"></i></button>
        </form>
        <form method="post" class="inline-form">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="hidden" name="action" value="wallet_withdraw">
          <input type="number" step="0.01" name="amount" placeholder="سحب محفظة">
          <button class="btn btn-outline sm"><i data-lucide="minus"></i></button>
        </form>
        <form method="post" class="inline-form">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="hidden" name="action" value="reset_pw">
          <input type="text" name="new_password" placeholder="كلمة سر جديدة">
          <button class="btn btn-primary sm"><i data-lucide="key"></i></button>
        </form>
        <form method="post" class="inline">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <?php if ((int)$u['is_admin']===1): ?>
            <button name="action" value="remove_admin" class="btn btn-ghost sm"><i data-lucide="user-minus"></i> إزالة الإدارة</button>
          <?php else: ?>
            <button name="action" value="make_admin" class="btn btn-ghost sm"><i data-lucide="shield"></i> ترقية لمشرف</button>
          <?php endif; ?>
        </form>
        <form method="post" class="inline" onsubmit="return confirm('حذف المستخدم وكل بياناته؟');">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <button name="action" value="delete" class="btn btn-outline sm danger"><i data-lucide="trash-2"></i> حذف</button>
        </form>
      </div>
    </details>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php require __DIR__ . '/_footer.php'; ?>
