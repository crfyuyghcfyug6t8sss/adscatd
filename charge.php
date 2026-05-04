<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me  = require_login($pdo);
$cur = setting_get($pdo, 'currency', 'USD');
$feePct = (float)setting_get($pdo, 'charge_fee_percent', 3);

$cardId = (int)($_GET['card_id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM cards WHERE id=? AND user_id=?');
$st->execute([$cardId, $me['id']]);
$card = $st->fetch();
if (!$card) { flash_set('bad','البطاقة غير موجودة.'); redirect('/cards.php'); }

$pmList = $pdo->query('SELECT * FROM payment_methods WHERE active = 1 ORDER BY id ASC')->fetchAll();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $amount = (float)($_POST['amount'] ?? 0);
    $pmId   = (int)($_POST['payment_method_id'] ?? 0);
    $pref   = trim($_POST['payment_reference'] ?? '');
    if ($amount <= 0 || $pmId <= 0) {
        $err = 'تأكد من المبلغ وطريقة الدفع.';
    } else {
        $fee = round($amount * $feePct / 100, 2);
        $pdo->prepare('INSERT INTO charge_requests (user_id, card_id, amount, fee, payment_method_id, payment_reference) VALUES (?,?,?,?,?,?)')
            ->execute([$me['id'], $card['id'], $amount, $fee, $pmId, $pref]);
        flash_set('ok', 'تم إرسال طلب الشحن. بانتظار موافقة الإدارة.');
        redirect('/cards.php');
    }
}

$pageTitle = 'شحن البطاقة — rozana agency';
$navActive = 'cards';
include __DIR__ . '/includes/header.php';
?>
<section class="page-head">
  <h1><i data-lucide="refresh-cw"></i> شحن البطاقة</h1>
  <p class="muted">البطاقة <?= e(mask_card($card['card_number'])) ?> — الرصيد الحالي <?= e(money($card['balance'], $cur)) ?></p>
</section>
<?php if ($err): ?><div class="flash flash-bad"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="form panel">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <div class="grid-2">
    <label><span>المبلغ (<?= e($cur) ?>)</span>
      <input type="number" min="1" step="0.01" name="amount" required value="<?= e($_POST['amount'] ?? '') ?>">
    </label>
    <label><span>نسبة رسوم الشحن</span>
      <input value="<?= e($feePct) ?>%" disabled>
    </label>
  </div>

  <h3><i data-lucide="wallet"></i> طريقة الدفع</h3>
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
  <button class="btn btn-primary lg" type="submit"><i data-lucide="send"></i> إرسال طلب الشحن</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
