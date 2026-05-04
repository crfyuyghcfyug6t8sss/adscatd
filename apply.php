<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login($pdo);

$cur          = setting_get($pdo, 'currency', 'USD');
$issuanceFee  = (float)setting_get($pdo, 'issuance_fee', 5);
$minCharge    = (float)setting_get($pdo, 'min_initial_charge', 20);
$minDeposit   = (float)setting_get($pdo, 'min_deposit', 0);

$pmList = $pdo->query('SELECT * FROM payment_methods WHERE active = 1 ORDER BY id ASC')->fetchAll();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $full   = trim($_POST['full_name'] ?? '');
    $dob    = trim($_POST['dob'] ?? '');
    $ctry   = trim($_POST['country'] ?? '');
    $addr   = trim($_POST['address'] ?? '');
    $charge = (float)($_POST['initial_charge'] ?? 0);
    $deposit= (float)($_POST['deposit'] ?? 0);
    $pmId   = (int)($_POST['payment_method_id'] ?? 0);
    $pref   = trim($_POST['payment_reference'] ?? '');

    if ($full === '' || $dob === '' || $ctry === '' || $charge < $minCharge || $deposit < $minDeposit) {
        $err = 'تأكد من تعبئة الحقول والحد الأدنى للشحن والإيداع.';
    } elseif ($pmId <= 0) {
        $err = 'اختر طريقة دفع.';
    } else {
        $st = $pdo->prepare('INSERT INTO applications
            (user_id, full_name, dob, country, address, initial_charge, issuance_fee, deposit, payment_method_id, payment_reference)
            VALUES (?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$me['id'], $full, $dob, $ctry, $addr, $charge, $issuanceFee, $deposit, $pmId, $pref]);
        $aid = (int)$pdo->lastInsertId();
        // Update profile fields if missing
        $pdo->prepare('UPDATE users SET full_name = COALESCE(NULLIF(full_name,""), ?), dob = COALESCE(NULLIF(dob,""), ?) WHERE id = ?')
            ->execute([$full, $dob, $me['id']]);
        flash_set('ok', 'تم إرسال طلب البطاقة #' . $aid . '. بانتظار مراجعة الإدارة.');
        redirect('/dashboard.php');
    }
}

$pageTitle = 'طلب بطاقة جديدة — rozana agency';
$navActive = 'apply';
include __DIR__ . '/includes/header.php';
?>
<section class="page-head">
  <h1><i data-lucide="credit-card"></i> طلب بطاقة جديدة</h1>
  <p class="muted">عبّئ المعلومات بدقة. سيتم مراجعة الطلب ثم إصدار البطاقة.</p>
</section>

<?php if ($err): ?><div class="flash flash-bad"><?= e($err) ?></div><?php endif; ?>

<form method="post" class="form panel">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

  <div class="form-section">
    <h3><i data-lucide="user"></i> المعلومات الشخصية</h3>
    <div class="grid-2">
      <label><span>الاسم الكامل</span>
        <input name="full_name" required value="<?= e($_POST['full_name'] ?? $me['full_name']) ?>">
      </label>
      <label><span>تاريخ الميلاد</span>
        <input type="date" name="dob" required value="<?= e($_POST['dob'] ?? $me['dob']) ?>">
      </label>
      <label><span>الدولة</span>
        <input name="country" required value="<?= e($_POST['country'] ?? '') ?>">
      </label>
      <label><span>العنوان</span>
        <input name="address" value="<?= e($_POST['address'] ?? '') ?>">
      </label>
    </div>
  </div>

  <div class="form-section">
    <h3><i data-lucide="banknote"></i> تفاصيل الشحن والرسوم</h3>
    <div class="grid-3">
      <label><span>قيمة الشحن المبدئي (<?= e($cur) ?>)</span>
        <input type="number" min="<?= e($minCharge) ?>" step="0.01" name="initial_charge" required value="<?= e($_POST['initial_charge'] ?? $minCharge) ?>">
      </label>
      <label><span>رسوم إصدار البطاقة</span>
        <input type="number" value="<?= e($issuanceFee) ?>" disabled>
      </label>
      <label><span>الإيداع (<?= e($cur) ?>)</span>
        <input type="number" min="<?= e($minDeposit) ?>" step="0.01" name="deposit" required value="<?= e($_POST['deposit'] ?? $minDeposit) ?>">
      </label>
    </div>
    <p class="muted small">المجموع الذي ستدفعه = الشحن + رسوم الإصدار + الإيداع.</p>
  </div>

  <div class="form-section">
    <h3><i data-lucide="wallet"></i> طريقة الدفع</h3>
    <div class="pm-list">
      <?php foreach ($pmList as $pm): ?>
        <label class="pm-item">
          <input type="radio" name="payment_method_id" value="<?= (int)$pm['id'] ?>" <?= (($_POST['payment_method_id'] ?? 0)==$pm['id'])?'checked':'' ?>>
          <div>
            <strong><i data-lucide="credit-card"></i> <?= e($pm['name']) ?></strong>
            <pre class="pm-details"><?= e($pm['details']) ?></pre>
          </div>
        </label>
      <?php endforeach; ?>
    </div>
    <label><span>رقم/مرجع الحوالة</span>
      <input name="payment_reference" placeholder="TxID / MTCN / رقم الحوالة" value="<?= e($_POST['payment_reference'] ?? '') ?>">
    </label>
  </div>

  <button class="btn btn-primary lg" type="submit"><i data-lucide="send"></i> إرسال الطلب</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>
