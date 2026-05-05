<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login($pdo);
if ((int)$me['is_admin'] === 1) redirect('/admin/index.php');

$cur = setting_get($pdo, 'currency', 'USD');

$cardsCount = (int)$pdo->query('SELECT COUNT(*) c FROM cards WHERE user_id='.(int)$me['id'])->fetch()['c'];
$pendingApps = (int)$pdo->query('SELECT COUNT(*) c FROM applications WHERE status="pending" AND user_id='.(int)$me['id'])->fetch()['c'];
$pendingCharges = (int)$pdo->query('SELECT COUNT(*) c FROM charge_requests WHERE status="pending" AND user_id='.(int)$me['id'])->fetch()['c'];
$balanceSum = (float)$pdo->query('SELECT COALESCE(SUM(balance),0) s FROM cards WHERE user_id='.(int)$me['id'])->fetch()['s'];
$wallet = wallet_balance($pdo, (int)$me['id']);

$apps = $pdo->prepare('SELECT * FROM applications WHERE user_id=? ORDER BY id DESC LIMIT 6');
$apps->execute([$me['id']]);
$apps = $apps->fetchAll();

$notifs = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 6');
$notifs->execute([$me['id']]);
$notifs = $notifs->fetchAll();

$pageTitle = 'لوحتي — my-ads.cards';
$navActive = 'dashboard';
include __DIR__ . '/includes/header.php';
?>
<section class="dash-head reveal">
  <div>
    <h1>أهلاً، <?= e($me['full_name'] ?: $me['phone']) ?></h1>
    <p class="muted">إدارة محفظتك، بطاقاتك، وطلباتك من مكان واحد.</p>
  </div>
  <div class="row-gap">
    <a href="/wallet.php" class="btn btn-outline"><i data-lucide="wallet"></i> شحن المحفظة</a>
    <a href="/apply.php" class="btn btn-primary pulse-cta"><i data-lucide="plus"></i> طلب بطاقة جديدة</a>
  </div>
</section>

<section class="stats-grid reveal-up">
  <div class="stat"><i data-lucide="wallet" class="ic"></i><div><span><?= e(money($wallet, $cur)) ?></span><small>رصيد المحفظة</small></div></div>
  <div class="stat"><i data-lucide="credit-card" class="ic"></i><div><span><?= $cardsCount ?></span><small>بطاقات نشطة</small></div></div>
  <div class="stat"><i data-lucide="banknote" class="ic"></i><div><span><?= e(money($balanceSum, $cur)) ?></span><small>إجمالي رصيد البطاقات</small></div></div>
  <div class="stat"><i data-lucide="hourglass" class="ic"></i><div><span><?= $pendingApps + $pendingCharges ?></span><small>طلبات قيد المراجعة</small></div></div>
</section>

<section class="two-col">
  <div class="panel reveal-up">
    <div class="panel-head"><h3><i data-lucide="file-text"></i> آخر طلبات الإصدار</h3><a href="/apply.php" class="btn btn-ghost sm"><i data-lucide="plus"></i> طلب جديد</a></div>
    <?php if (!$apps): ?>
      <p class="empty">لا توجد طلبات بعد.</p>
    <?php else: ?>
      <table class="table">
        <thead><tr><th>#</th><th>الاسم</th><th>النوع</th><th>الشحن</th><th>المدفوع</th><th>الحالة</th></tr></thead>
        <tbody>
        <?php foreach ($apps as $a): [$lbl,$cls] = status_label($a['status']); ?>
          <tr>
            <td>#<?= (int)$a['id'] ?></td>
            <td><?= e($a['full_name']) ?></td>
            <td><span class="badge <?= $a['brand']==='mastercard'?'warn':'pending' ?>"><?= e(card_brand_label($a['brand'])) ?></span></td>
            <td><?= e(money($a['initial_charge'], $cur)) ?></td>
            <td><?= e(money($a['total_paid'], $cur)) ?></td>
            <td><span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="panel reveal-up">
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
