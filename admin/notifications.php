<?php
$adminActive = 'notif';
$pageTitle = 'الإشعارات و OTP — الإدارة';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'send') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $cardId = (int)($_POST['card_id'] ?? 0) ?: null;
        $kind   = in_array($_POST['kind'] ?? 'info', ['info','otp','3ds']) ? $_POST['kind'] : 'info';
        $title  = trim($_POST['title'] ?? '');
        $msg    = trim($_POST['message'] ?? '');
        if ($userId > 0 && $title !== '') {
            notify($pdo, $userId, $cardId, $title, $msg, $kind);
            flash_set('ok','تم إرسال الإشعار.');
        } else {
            flash_set('bad','تأكد من اختيار المستخدم وعنوان الإشعار.');
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM notifications WHERE id=?')->execute([(int)$_POST['id']]);
    }
    redirect('/admin/notifications.php');
}

$users = $pdo->query('SELECT id, phone, full_name FROM users WHERE is_admin=0 ORDER BY id DESC')->fetchAll();
$cards = $pdo->query('SELECT c.id, c.card_number, c.user_id, u.phone FROM cards c JOIN users u ON u.id=c.user_id ORDER BY c.id DESC')->fetchAll();
$rows = $pdo->query('SELECT n.*, u.phone FROM notifications n JOIN users u ON u.id=n.user_id ORDER BY n.id DESC LIMIT 200')->fetchAll();
?>
<h1><i data-lucide="bell"></i> الإشعارات و OTP</h1>

<div class="panel">
  <div class="panel-head"><h3><i data-lucide="send"></i> إرسال إشعار / OTP / 3DS</h3></div>
  <form method="post" class="form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="send">
    <div class="grid-2">
      <label><span>المستخدم</span>
        <select name="user_id" required>
          <option value="">— اختر —</option>
          <?php foreach($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= e($u['phone']) ?> — <?= e($u['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><span>البطاقة (اختياري)</span>
        <select name="card_id">
          <option value="0">— عام —</option>
          <?php foreach($cards as $c): ?>
            <option value="<?= (int)$c['id'] ?>" data-uid="<?= (int)$c['user_id'] ?>"><?= e($c['phone']) ?> — <?= e(mask_card($c['card_number'])) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><span>النوع</span>
        <select name="kind">
          <option value="info">إشعار عام</option>
          <option value="otp">رمز OTP</option>
          <option value="3ds">3DS</option>
        </select>
      </label>
      <label><span>العنوان</span><input name="title" required placeholder="مثال: رمز التحقق Facebook"></label>
    </div>
    <label><span>الرسالة (إن كان OTP، ضع الرمز داخل النص فقط مثل: 482913)</span>
      <textarea name="message" rows="4" placeholder="تفاصيل الرسالة، رمز OTP، عملية الدفع..."></textarea>
    </label>
    <button class="btn btn-primary"><i data-lucide="send"></i> إرسال</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h3><i data-lucide="list"></i> آخر الإشعارات</h3></div>
  <?php if(!$rows): ?><p class="empty">لا يوجد.</p><?php else: ?>
  <table class="table">
    <thead><tr><th>#</th><th>المستخدم</th><th>النوع</th><th>العنوان</th><th>الرسالة</th><th>التاريخ</th><th></th></tr></thead>
    <tbody>
    <?php foreach($rows as $n): ?>
      <tr>
        <td>#<?= (int)$n['id'] ?></td>
        <td dir="ltr"><?= e($n['phone']) ?></td>
        <td><span class="badge <?= $n['kind']==='otp'?'warn':($n['kind']==='3ds'?'ok':'') ?>"><?= e($n['kind']) ?></span></td>
        <td><?= e($n['title']) ?></td>
        <td class="muted"><?= e(mb_strimwidth($n['message'] ?? '', 0, 80, '...')) ?></td>
        <td class="muted small"><?= e($n['created_at']) ?></td>
        <td>
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
            <button name="action" value="delete" class="btn btn-ghost sm danger"><i data-lucide="trash-2"></i></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
