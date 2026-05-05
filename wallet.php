<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login($pdo);
$cur = setting_get($pdo, 'currency', 'USD');
$minDeposit = (float)setting_get($pdo, 'min_wallet_deposit', 10);
$pmList = $pdo->query('SELECT * FROM payment_methods WHERE active = 1 ORDER BY id ASC')->fetchAll();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $amount = (float)($_POST['amount'] ?? 0);
    $pmId   = (int)($_POST['payment_method_id'] ?? 0);
    $pref   = trim($_POST['payment_reference'] ?? '');
    $note   = trim($_POST['note'] ?? '');
    if ($amount < $minDeposit) {
        $err = 'الحد الأدنى للإيداع: ' . money($minDeposit, $cur);
    } elseif ($pmId <= 0) {
        $err = 'اختر طريقة دفع.';
    } else {
        $pdo->prepare('INSERT INTO wallet_deposits (user_id, amount, payment_method_id, payment_reference, note) VALUES (?,?,?,?,?)')
            ->execute([$me['id'], $amount, $pmId, $pref, $note]);
        flash_set('ok', 'تم إرسال طلب الإيداع. بانتظار موافقة الإدارة.');
        redirect('/wallet.php');
    }
}

$balance = wallet_balance($pdo, (int)$me['id']);
$st = $pdo->prepare('SELECT wd.*, pm.name AS pm_name FROM wallet_deposits wd LEFT JOIN payment_methods pm ON pm.id = wd.payment_method_id WHERE wd.user_id = ? ORDER BY wd.id DESC LIMIT 30');
$st->execute([$me['id']]);
$deposits = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM transactions WHERE user_id = ? AND card_id IS NULL ORDER BY id DESC LIMIT 30');
$st->execute([$me['id']]);
$txs = $st->fetchAll();

$pageTitle = 'محفظتي — my-ads.cards';
$navActive = 'wallet';
include __DIR__ . '/includes/header.php';
?>
<section class="page-head reveal">
  <div>
    <h1><i data-lucide="wallet"></i> محفظتي</h1>
    <p class="muted">اشحن محفظتك ثم استخدم رصيدها لإصدار البطاقات وشحنها.</p>
  </div>
</section>

<div class="wallet-card reveal-up">
  <div>
    <span class="lbl">رصيدك الحالي</span>
    <strong class="balance"><?= e(money($balance, $cur)) ?></strong>
  </div>
  <i data-lucide="wallet" class="ic-xl"></i>
</div>

<?php if ($err): ?><div class="flash flash-bad reveal-up"><?= e($err) ?></div><?php endif; ?>

<div class="two-col">
  <div class="panel reveal-up">
    <div class="panel-head"><h3><i data-lucide="plus-circle"></i> طلب إيداع جديد</h3></div>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label><span><i data-lucide="banknote"></i> المبلغ (<?= e($cur) ?>)</span>
        <input type="number" min="<?= e($minDeposit) ?>" step="0.01" name="amount" required value="<?= e($_POST['amount'] ?? $minDeposit) ?>">
      </label>
      <h4 class="sub-title"><i data-lucide="wallet"></i> طريقة الدفع</h4>
      <div class="pm-list">
        <?php foreach ($pmList as $pm): ?>
          <label class="pm-item">
            <input type="radio" name="payment_method_id" value="<?= (int)$pm['id'] ?>">
            <div>
              <strong><i data-lucide="credit-card"></i> <?= e($pm['name']) ?></strong>
              <pre class="pm-details"><?= e($pm['details']) ?></pre>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
      <label><span>رقم/مرجع الحوالة</span>
        <input name="payment_reference" placeholder="TxID / MTCN / رقم الحوالة">
      </label>
      <label><span>ملاحظة (اختياري)</span>
        <input name="note" placeholder="أي ملاحظة للإدارة">
      </label>
      <button class="btn btn-primary lg" type="submit"><i data-lucide="send"></i> إرسال طلب الإيداع</button>
    </form>
  </div>

  <div class="panel reveal-up">
    <div class="panel-head"><h3><i data-lucide="history"></i> طلبات الإيداع</h3></div>
    <?php if (!$deposits): ?>
      <p class="empty">لا توجد طلبات إيداع.</p>
    <?php else: ?>
      <table class="table">
        <thead><tr><th>#</th><th>المبلغ</th><th>الطريقة</th><th>المرجع</th><th>الحالة</th><th>التاريخ</th></tr></thead>
        <tbody>
          <?php foreach($deposits as $d): [$lbl,$cls]=status_label($d['status']); ?>
          <tr>
            <td>#<?= (int)$d['id'] ?></td>
            <td><?= e(money($d['amount'], $cur)) ?></td>
            <td><?= e($d['pm_name'] ?: '—') ?></td>
            <td dir="ltr"><?= e($d['payment_reference'] ?: '—') ?></td>
            <td><span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span></td>
            <td class="muted small"><?= e($d['created_at']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<div class="panel reveal-up">
  <div class="panel-head"><h3><i data-lucide="activity"></i> حركات المحفظة</h3></div>
  <?php if (!$txs): ?>
    <p class="empty">لا توجد حركات.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>#</th><th>النوع</th><th>المبلغ</th><th>الملاحظة</th><th>التاريخ</th></tr></thead>
      <tbody>
        <?php foreach ($txs as $t): ?>
          <tr>
            <td>#<?= (int)$t['id'] ?></td>
            <td><?= e($t['type']) ?></td>
            <td class="<?= ((float)$t['amount']>=0)?'pos':'neg' ?>"><?= e(money($t['amount'], $cur)) ?></td>
            <td class="muted"><?= e($t['note']) ?></td>
            <td class="muted small"><?= e($t['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
