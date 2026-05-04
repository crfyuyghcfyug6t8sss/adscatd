<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login($pdo);
if ((int)$me['is_admin'] === 1) redirect('/admin/index.php');

$cur = setting_get($pdo, 'currency', 'USD');

$cardsCount = (int)$pdo->query('SELECT COUNT(*) c FROM cards WHERE user_id='.(int)$me['id'])->fetch()['c'];
$pendingApps = (int)$pdo->query('SELECT COUNT(*) c FROM applications WHERE status="pending" AND user_id='.(int)$me['id'])->fetch()['c'];
$pendingCharges = (int)$pdo->query('SELECT COUNT(*) c FROM charge_requests WHERE status="pending" AND user_id='.(int)$me['id'])->fetch()['c'];
$balanceSum = (float)$pdo->query('SELECT COALESCE(SUM(balance),0) s FROM cards WHERE user_id='.(int)$me['id'])->fetch()['s'];

$apps = $pdo->prepare('SELECT * FROM applications WHERE user_id=? ORDER BY id DESC LIMIT 6');
$apps->execute([$me['id']]);
$apps = $apps->fetchAll();

$notifs = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 6');
$notifs->execute([$me['id']]);
$notifs = $notifs->fetchAll();

$pageTitle = 'لوحتي — rozana agency';
$navActive = 'dashboard';
include __DIR__ . '/includes/header.php';
?>
<section class="dash-head">
  <div>
    <h1>أهلاً، <?= e($me['full_name'] ?: $me['phone']) ?></h1>
    <p class="muted">إدارة بطاقاتك وطلباتك من مكان واحد.</p>
  </div>
  <a href="/apply.php" class="btn btn-primary"><i data-lucide="plus"></i> طلب بطاقة جديدة</a>
</section>

<section class="stats-grid">
  <div class="stat">
    <i data-lucide="credit-card" class="ic"></i>
    <div><span><?= $cardsCount ?></span><small>بطاقات نشطة</small></div>
  </div>
  <div class="stat">
    <i data-lucide="wallet" class="ic"></i>
    <div><span><?= e(money($balanceSum, $cur)) ?></span><small>إجمالي الرصيد</small></div>
  </div>
  <div class="stat">
    <i data-lucide="hourglass" class="ic"></i>
    <div><span><?= $pendingApps ?></span><small>طلبات إصدار قيد المراجعة</small></div>
  </div>
  <div class="stat">
    <i data-lucide="refresh-cw" class="ic"></i>
    <div><span><?= $pendingCharges ?></span><small>طلبات شحن قيد المراجعة</small></div>
  </div>
</section>

<section class="two-col">
  <div class="panel">
    <div class="panel-head"><h3><i data-lucide="file-text"></i> آخر طلبات الإصدار</h3><a href="/apply.php" class="btn btn-ghost sm"><i data-lucide="plus"></i> طلب جديد</a></div>
    <?php if (!$apps): ?>
      <p class="empty">لا توجد طلبات بعد.</p>
    <?php else: ?>
      <table class="table">
        <thead><tr><th>#</th><th>الاسم</th><th>الشحن المبدئي</th><th>الإيداع</th><th>الحالة</th><th>التاريخ</th></tr></thead>
        <tbody>
        <?php foreach ($apps as $a): [$lbl,$cls] = status_label($a['status']); ?>
          <tr>
            <td>#<?= (int)$a['id'] ?></td>
            <td><?= e($a['full_name']) ?></td>
            <td><?= e(money($a['initial_charge'], $cur)) ?></td>
            <td><?= e(money($a['deposit'], $cur)) ?></td>
            <td><span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span></td>
            <td class="muted"><?= e($a['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="panel">
    <div class="panel-head"><h3><i data-lucide="bell"></i> آخر الإشعارات</h3><a href="/cards.php" class="btn btn-ghost sm"><i data-lucide="credit-card"></i> بطاقاتي</a></div>
    <?php if (!$notifs): ?>
      <p class="empty">لا توجد إشعارات.</p>
    <?php else: ?>
      <ul class="notif-list">
        <?php foreach ($notifs as $n): ?>
          <li class="notif notif-<?= e($n['kind']) ?>">
            <i data-lucide="<?= $n['kind']==='otp'?'key-round':($n['kind']==='3ds'?'shield-check':'info') ?>" class="ic"></i>
            <div>
              <strong><?= e($n['title']) ?></strong>
              <p><?= nl2br(e($n['message'])) ?></p>
              <span class="muted small"><?= e($n['created_at']) ?></span>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
