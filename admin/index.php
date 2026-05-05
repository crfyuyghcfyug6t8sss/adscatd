<?php
$adminActive = 'home';
$pageTitle = 'لوحة الإدارة — rozana agency';
require __DIR__ . '/_layout.php';

$cur = setting_get($pdo, 'currency', 'USD');
$users = (int)$pdo->query('SELECT COUNT(*) c FROM users WHERE is_admin=0')->fetch()['c'];
$pendingApps = (int)$pdo->query('SELECT COUNT(*) c FROM applications WHERE status="pending"')->fetch()['c'];
$pendingCharges = (int)$pdo->query('SELECT COUNT(*) c FROM charge_requests WHERE status="pending"')->fetch()['c'];
$activeCards = (int)$pdo->query('SELECT COUNT(*) c FROM cards WHERE status="active"')->fetch()['c'];
$frozenCards = (int)$pdo->query('SELECT COUNT(*) c FROM cards WHERE status="frozen"')->fetch()['c'];
$totalBal    = (float)$pdo->query('SELECT COALESCE(SUM(balance),0) s FROM cards')->fetch()['s'];
$totalWallets = (float)$pdo->query('SELECT COALESCE(SUM(wallet_balance),0) s FROM users WHERE is_admin=0')->fetch()['s'];
$pendingDeposits = (int)$pdo->query('SELECT COUNT(*) c FROM wallet_deposits WHERE status="pending"')->fetch()['c'];

$lastApps = $pdo->query("SELECT a.*, u.phone FROM applications a JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 6")->fetchAll();
$lastCharges = $pdo->query("SELECT cr.*, u.phone FROM charge_requests cr JOIN users u ON u.id=cr.user_id ORDER BY cr.id DESC LIMIT 6")->fetchAll();
?>
<h1><i data-lucide="gauge"></i> نظرة عامة</h1>
<div class="stats-grid">
  <div class="stat"><i data-lucide="users" class="ic"></i><div><span><?= $users ?></span><small>المستخدمون</small></div></div>
  <div class="stat"><i data-lucide="credit-card" class="ic"></i><div><span><?= $activeCards ?></span><small>بطاقات نشطة</small></div></div>
  <div class="stat"><i data-lucide="snowflake" class="ic"></i><div><span><?= $frozenCards ?></span><small>بطاقات مجمدة</small></div></div>
  <div class="stat"><i data-lucide="wallet" class="ic"></i><div><span><?= e(money($totalBal,$cur)) ?></span><small>إجمالي الأرصدة</small></div></div>
  <div class="stat"><i data-lucide="hourglass" class="ic"></i><div><span><?= $pendingApps ?></span><small>طلبات إصدار معلقة</small></div></div>
  <div class="stat"><i data-lucide="refresh-cw" class="ic"></i><div><span><?= $pendingCharges ?></span><small>طلبات شحن معلقة</small></div></div>
  <div class="stat"><i data-lucide="wallet" class="ic"></i><div><span><?= $pendingDeposits ?></span><small>إيداعات محفظة معلقة</small></div></div>
  <div class="stat"><i data-lucide="banknote" class="ic"></i><div><span><?= e(money($totalWallets,$cur)) ?></span><small>مجموع المحافظ</small></div></div>
</div>

<div class="two-col">
  <div class="panel">
    <div class="panel-head"><h3><i data-lucide="file-text"></i> آخر طلبات الإصدار</h3><a href="/admin/applications.php" class="btn btn-ghost sm">عرض الكل</a></div>
    <?php if(!$lastApps): ?><p class="empty">لا يوجد.</p><?php else: ?>
    <table class="table"><thead><tr><th>#</th><th>الهاتف</th><th>الاسم</th><th>المبلغ</th><th>الحالة</th></tr></thead><tbody>
      <?php foreach($lastApps as $a): [$lbl,$cls]=status_label($a['status']); ?>
        <tr><td>#<?= (int)$a['id'] ?></td><td dir="ltr"><?= e($a['phone']) ?></td><td><?= e($a['full_name']) ?></td>
        <td><?= e(money($a['initial_charge']+$a['issuance_fee']+$a['deposit'],$cur)) ?></td>
        <td><span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span></td></tr>
      <?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>
  </div>
  <div class="panel">
    <div class="panel-head"><h3><i data-lucide="refresh-cw"></i> آخر طلبات الشحن</h3><a href="/admin/charges.php" class="btn btn-ghost sm">عرض الكل</a></div>
    <?php if(!$lastCharges): ?><p class="empty">لا يوجد.</p><?php else: ?>
    <table class="table"><thead><tr><th>#</th><th>الهاتف</th><th>المبلغ</th><th>الحالة</th></tr></thead><tbody>
      <?php foreach($lastCharges as $c): [$lbl,$cls]=status_label($c['status']); ?>
        <tr><td>#<?= (int)$c['id'] ?></td><td dir="ltr"><?= e($c['phone']) ?></td><td><?= e(money($c['amount'],$cur)) ?></td>
        <td><span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span></td></tr>
      <?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
