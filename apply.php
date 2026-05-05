<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login($pdo);

$cur          = setting_get($pdo, 'currency', 'USD');
$issuanceFee  = (float)setting_get($pdo, 'issuance_fee', 5);
$minCharge    = (float)setting_get($pdo, 'min_initial_charge', 5);
$balance      = wallet_balance($pdo, (int)$me['id']);

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $full   = trim($_POST['full_name'] ?? $me['full_name'] ?? '');
    $brand  = ($_POST['brand'] ?? 'visa') === 'mastercard' ? 'mastercard' : 'visa';
    $charge = (float)($_POST['initial_charge'] ?? 0);
    $total  = $charge + $issuanceFee;

    if ($full === '') {
        $err = 'الاسم الكامل مطلوب.';
    } elseif ($charge < $minCharge) {
        $err = 'الحد الأدنى للشحن: ' . money($minCharge, $cur);
    } elseif ($balance < $total) {
        $err = 'رصيد محفظتك غير كافٍ. تحتاج ' . money($total, $cur) . ' (الشحن + رسوم الإصدار). اشحن محفظتك أولاً.';
    } else {
        $note = 'دفع طلب بطاقة (' . card_brand_label($brand) . ') — رسوم: ' . money($issuanceFee,$cur) . ' + شحن: ' . money($charge,$cur);
        if (!wallet_deduct($pdo, (int)$me['id'], $total, $note)) {
            $err = 'تعذّر خصم الرصيد. أعد المحاولة.';
        } else {
            $st = $pdo->prepare('INSERT INTO applications (user_id, full_name, brand, initial_charge, issuance_fee, total_paid) VALUES (?,?,?,?,?,?)');
            $st->execute([$me['id'], $full, $brand, $charge, $issuanceFee, $total]);
            $aid = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE users SET full_name = COALESCE(NULLIF(full_name,""), ?) WHERE id = ?')->execute([$full, $me['id']]);
            flash_set('ok', 'تم إرسال طلب البطاقة #' . $aid . ' وخصم ' . money($total,$cur) . ' من محفظتك. بانتظار موافقة الإدارة.');
            redirect('/dashboard.php');
        }
    }
}

$pageTitle = 'طلب بطاقة جديدة — my-ads.cards';
$navActive = 'apply';
include __DIR__ . '/includes/header.php';
?>
<section class="page-head reveal">
  <div>
    <h1><i data-lucide="credit-card"></i> طلب بطاقة جديدة</h1>
    <p class="muted">اختر النوع وقيمة الشحن. سيتم خصم الرسوم + الشحن من محفظتك مباشرة.</p>
  </div>
  <a href="/wallet.php" class="wallet-badge lg" title="رصيد المحفظة"><i data-lucide="wallet"></i> <?= e(money($balance, $cur)) ?></a>
</section>

<?php if ($err): ?><div class="flash flash-bad reveal-up"><?= e($err) ?></div><?php endif; ?>

<div class="apply-grid">
  <form method="post" class="form panel reveal-up">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <div class="form-section">
      <h3><i data-lucide="user"></i> الاسم على البطاقة</h3>
      <label><span>الاسم الكامل (English)</span>
        <input name="full_name" required value="<?= e($_POST['full_name'] ?? $me['full_name']) ?>" placeholder="مثال: ALI HASSAN">
      </label>
    </div>

    <div class="form-section">
      <h3><i data-lucide="layers"></i> اختر نوع البطاقة</h3>
      <div class="brand-pick">
        <label class="brand-opt">
          <input type="radio" name="brand" value="visa" <?= (($_POST['brand'] ?? 'visa')==='visa')?'checked':'' ?>>
          <div class="brand-card brand-visa">
            <span class="vc-bank">my-ads.cards</span>
            <span class="vc-brand">VISA</span>
            <small class="muted">Visa Virtual</small>
          </div>
        </label>
        <label class="brand-opt">
          <input type="radio" name="brand" value="mastercard" <?= (($_POST['brand'] ?? '')==='mastercard')?'checked':'' ?>>
          <div class="brand-card brand-mc">
            <span class="vc-bank">my-ads.cards</span>
            <span class="vc-mc"><span class="mc-c c-r"></span><span class="mc-c c-y"></span></span>
            <small class="muted">Mastercard Virtual</small>
          </div>
        </label>
      </div>
    </div>

    <div class="form-section">
      <h3><i data-lucide="banknote"></i> قيمة الشحن المبدئي</h3>
      <label><span>المبلغ (<?= e($cur) ?>)</span>
        <input id="charge-input" type="number" min="<?= e($minCharge) ?>" step="0.01" name="initial_charge" required value="<?= e($_POST['initial_charge'] ?? $minCharge) ?>">
      </label>
      <div class="cost-summary">
        <div><span class="muted">رسوم إصدار البطاقة</span><strong data-issue><?= e(money($issuanceFee, $cur)) ?></strong></div>
        <div><span class="muted">قيمة الشحن المبدئي</span><strong id="charge-display"><?= e(money((float)($_POST['initial_charge'] ?? $minCharge), $cur)) ?></strong></div>
        <div class="cost-total"><span>المجموع المخصوم من محفظتك</span><strong id="total-display"><?= e(money((float)($_POST['initial_charge'] ?? $minCharge) + $issuanceFee, $cur)) ?></strong></div>
      </div>
      <p class="muted small">رصيد محفظتك الحالي: <strong><?= e(money($balance, $cur)) ?></strong>. <a href="/wallet.php">اشحن محفظتك</a> إذا غير كافٍ.</p>
    </div>

    <button class="btn btn-primary lg" type="submit"><i data-lucide="send"></i> إرسال الطلب وخصم الرصيد</button>
  </form>
</div>

<script>
  (function(){
    const cur = <?= json_encode($cur) ?>;
    const fee = <?= json_encode($issuanceFee) ?>;
    const fmt = v => (Number(v)||0).toFixed(2) + ' ' + cur;
    const inp = document.getElementById('charge-input');
    if (!inp) return;
    inp.addEventListener('input', () => {
      const v = parseFloat(inp.value || 0);
      document.getElementById('charge-display').textContent = fmt(v);
      document.getElementById('total-display').textContent = fmt(v + fee);
    });
  })();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
