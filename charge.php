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

$priorApproved = approved_charge_count($pdo, (int)$card['id']);
$isFirstCharge = $priorApproved === 0;
$balance = wallet_balance($pdo, (int)$me['id']);

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount <= 0) {
        $err = 'أدخل مبلغاً صحيحاً.';
    } else {
        $fee = $isFirstCharge ? 0.0 : round($amount * $feePct / 100, 2);
        $total = $amount + $fee;
        if ($balance < $total) {
            $err = 'رصيد محفظتك غير كافٍ. تحتاج ' . money($total, $cur) . '. <a href="/wallet.php">اشحن محفظتك</a>.';
        } else {
            $note = 'دفع طلب شحن بطاقة #' . (int)$card['id'] . ' — ' . money($amount, $cur) . ($fee>0 ? ' + عمولة ' . money($fee, $cur) : ' (شحن أول بدون عمولة)');
            if (!wallet_deduct($pdo, (int)$me['id'], $total, $note)) {
                $err = 'تعذّر خصم الرصيد. أعد المحاولة.';
            } else {
                $pdo->prepare('INSERT INTO charge_requests (user_id, card_id, amount, fee, total_paid) VALUES (?,?,?,?,?)')
                    ->execute([$me['id'], $card['id'], $amount, $fee, $total]);
                flash_set('ok', 'تم إرسال طلب الشحن وخصم ' . money($total, $cur) . ' من محفظتك. بانتظار موافقة الإدارة.');
                redirect('/cards.php');
            }
        }
    }
}

$pageTitle = 'شحن البطاقة — my-ads.cards';
$navActive = 'cards';
include __DIR__ . '/includes/header.php';
?>
<section class="page-head reveal">
  <div>
    <h1><i data-lucide="refresh-cw"></i> شحن البطاقة</h1>
    <p class="muted">البطاقة <span dir="ltr"><?= e(mask_card($card['card_number'])) ?></span> — الرصيد الحالي <?= e(money($card['balance'], $cur)) ?></p>
  </div>
  <a href="/wallet.php" class="wallet-badge lg"><i data-lucide="wallet"></i> <?= e(money($balance, $cur)) ?></a>
</section>

<?php if ($err): ?><div class="flash flash-bad reveal-up"><?= $err ?></div><?php endif; ?>
<?php if ($isFirstCharge): ?>
  <div class="flash flash-ok reveal-up"><i data-lucide="gift"></i> أول شحن لهذه البطاقة بدون عمولة!</div>
<?php endif; ?>

<form method="post" class="form panel reveal-up">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label><span><i data-lucide="banknote"></i> المبلغ (<?= e($cur) ?>)</span>
    <input id="amt" type="number" min="1" step="0.01" name="amount" required value="<?= e($_POST['amount'] ?? '') ?>">
  </label>
  <div class="cost-summary">
    <div><span class="muted">العمولة</span><strong id="fee-d"><?= $isFirstCharge ? '<span class="pos">مجاني</span>' : e($feePct).'%' ?></strong></div>
    <div class="cost-total"><span>المجموع المخصوم من محفظتك</span><strong id="tot-d"><?= e(money(0, $cur)) ?></strong></div>
  </div>
  <button class="btn btn-primary lg" type="submit"><i data-lucide="send"></i> إرسال طلب الشحن</button>
</form>

<script>
  (function(){
    const cur = <?= json_encode($cur) ?>;
    const pct = <?= json_encode($feePct) ?>;
    const first = <?= $isFirstCharge ? 'true' : 'false' ?>;
    const fmt = v => (Number(v)||0).toFixed(2) + ' ' + cur;
    const amt = document.getElementById('amt');
    const tot = document.getElementById('tot-d');
    const feeD = document.getElementById('fee-d');
    function update() {
      const v = parseFloat(amt.value || 0);
      const fee = first ? 0 : (v * pct / 100);
      if (!first) feeD.textContent = pct + '% = ' + fmt(fee);
      tot.textContent = fmt(v + fee);
    }
    amt.addEventListener('input', update); update();
  })();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
