<?php
$adminActive = 'flexcard';
$pageTitle = 'FlexCard — الإدارة';
require __DIR__ . '/_layout.php';

$result = null;
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'test') {
        $result = fc_test($pdo);
    } elseif ($action === 'services') {
        $result = fc_list_services($pdo, 100);
    } elseif ($action === 'wallets') {
        $result = fc_list_wallets($pdo);
    } elseif ($action === 'sync_otp') {
        $result = ['ok' => true, 'sync' => fc_sync_otps($pdo)];
    } elseif ($action === 'pick_visa') {
        setting_set($pdo, 'flexcard_visa_service', (string)($_POST['service_id'] ?? ''));
        flash_set('ok', 'تم ضبط خدمة Visa.');
        redirect('/admin/flexcard.php');
    } elseif ($action === 'pick_mc') {
        setting_set($pdo, 'flexcard_mc_service', (string)($_POST['service_id'] ?? ''));
        flash_set('ok', 'تم ضبط خدمة Mastercard.');
        redirect('/admin/flexcard.php');
    }
}

$cfg = fc_config($pdo);
$enabled = fc_enabled($pdo);
$visaSvc = setting_get($pdo, 'flexcard_visa_service', '');
$mcSvc   = setting_get($pdo, 'flexcard_mc_service', '');
?>
<h1><i data-lucide="zap"></i> FlexCard — تكامل البطاقات</h1>

<div class="panel">
  <div class="panel-head"><h3><i data-lucide="info"></i> الحالة</h3></div>
  <div class="grid-3 small-info">
    <div><span class="muted">التفعيل</span><strong><?= $enabled ? '<span class="badge ok">مفعّل</span>' : '<span class="badge bad">معطّل</span>' ?></strong></div>
    <div><span class="muted">Base URL</span><strong dir="ltr"><?= e($cfg['base']) ?></strong></div>
    <div><span class="muted">API Key</span><strong dir="ltr"><?= e(substr($cfg['key'],0,8) . '…' . substr($cfg['key'],-4)) ?></strong></div>
    <div><span class="muted">Visa Service ID</span><strong dir="ltr"><?= e($visaSvc ?: '—') ?></strong></div>
    <div><span class="muted">Mastercard Service ID</span><strong dir="ltr"><?= e($mcSvc ?: '—') ?></strong></div>
    <div><span class="muted">آخر OTP id متزامن</span><strong dir="ltr"><?= e(setting_get($pdo,'flexcard_otp_last_id','0')) ?></strong></div>
  </div>
  <p class="muted small">للتعديل: <a href="/admin/settings.php">صفحة الإعدادات</a>.</p>
</div>

<div class="panel">
  <div class="panel-head"><h3><i data-lucide="terminal"></i> اختبارات سريعة</h3></div>
  <div class="row-gap">
    <form method="post" class="inline">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <button name="action" value="test" class="btn btn-outline sm"><i data-lucide="plug"></i> اختبار الاتصال</button>
    </form>
    <form method="post" class="inline">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <button name="action" value="services" class="btn btn-outline sm"><i data-lucide="list"></i> جلب الخدمات (BINs)</button>
    </form>
    <form method="post" class="inline">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <button name="action" value="wallets" class="btn btn-outline sm"><i data-lucide="wallet"></i> جلب المحافظ</button>
    </form>
    <form method="post" class="inline">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <button name="action" value="sync_otp" class="btn btn-primary sm"><i data-lucide="key-round"></i> مزامنة OTPs الآن</button>
    </form>
  </div>
</div>

<?php if ($action === 'services' && $result && $result['ok']):
    $items = $result['body']['results'] ?? ($result['body'] ?? []);
?>
<div class="panel">
  <div class="panel-head"><h3><i data-lucide="layers"></i> الخدمات (BINs) المتوفرة</h3></div>
  <table class="table">
    <thead><tr><th>ID</th><th>الاسم</th><th>IIN</th><th>النظام</th><th>العملة</th><th>3DS</th><th>الحالة</th><th>اختيار</th></tr></thead>
    <tbody>
    <?php foreach ($items as $sv): ?>
      <tr>
        <td dir="ltr"><?= e((string)($sv['id'] ?? '—')) ?></td>
        <td><?= e($sv['name'] ?? '') ?></td>
        <td dir="ltr"><?= e($sv['iin'] ?? '') ?></td>
        <td dir="ltr"><?= e($sv['payment_system'] ?? '') ?></td>
        <td dir="ltr"><?= e($sv['currency'] ?? '') ?></td>
        <td><?= !empty($sv['is_3ds_enabled']) ? '✓' : '—' ?></td>
        <td><?= e($sv['status'] ?? '') ?></td>
        <td>
          <form method="post" class="inline">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="service_id" value="<?= e((string)($sv['id'] ?? '')) ?>">
            <button name="action" value="pick_visa" class="btn btn-ghost sm">اختر لـ Visa</button>
            <button name="action" value="pick_mc" class="btn btn-ghost sm">اختر لـ MC</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if ($action === 'wallets' && $result && $result['ok']):
    $items = $result['body']['results'] ?? ($result['body'] ?? []);
?>
<div class="panel">
  <div class="panel-head"><h3><i data-lucide="wallet"></i> محافظ FlexCoins</h3></div>
  <table class="table">
    <thead><tr><th>ID</th><th>الاسم</th><th>النوع</th><th>العملة</th><th>الرصيد</th><th>المتاح</th></tr></thead>
    <tbody>
    <?php foreach ($items as $w): ?>
      <tr>
        <td dir="ltr"><?= e((string)($w['id'] ?? '—')) ?></td>
        <td><?= e($w['name'] ?? $w['label'] ?? '—') ?></td>
        <td dir="ltr"><?= e($w['kind'] ?? $w['kind_code'] ?? '') ?></td>
        <td dir="ltr"><?= e($w['currency'] ?? '') ?></td>
        <td dir="ltr"><?= e((string)($w['balance'] ?? '')) ?></td>
        <td dir="ltr"><?= e((string)($w['available_amount'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if ($action === 'sync_otp' && $result):
    $s = $result['sync'] ?? [];
?>
<div class="panel">
  <div class="panel-head"><h3><i data-lucide="key-round"></i> نتيجة مزامنة OTP</h3></div>
  <?php if ($s['ok'] ?? false): ?>
    <p>تمت المزامنة. تم إدراج <strong><?= (int)$s['inserted'] ?></strong> رمز جديد. آخر id: <strong dir="ltr"><?= (int)$s['last_id'] ?></strong>.</p>
  <?php else: ?>
    <p class="flash flash-bad">فشلت المزامنة: <?= e($s['error'] ?? 'غير معروف') ?> (HTTP <?= (int)($s['raw_status'] ?? 0) ?>)</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($result && in_array($action, ['test','services','wallets'], true)): ?>
<div class="panel">
  <div class="panel-head"><h3><i data-lucide="code"></i> الاستجابة الخام</h3>
    <span class="badge <?= $result['ok'] ? 'ok' : 'bad' ?>">HTTP <?= (int)$result['status'] ?></span>
  </div>
  <?php if ($result['error']): ?>
    <p class="flash flash-bad"><?= e($result['error']) ?></p>
  <?php endif; ?>
  <pre class="raw-json"><?= e(json_encode($result['body'] ?? $result['raw'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>
