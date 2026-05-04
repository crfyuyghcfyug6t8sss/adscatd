<?php
$adminActive = 'settings';
$pageTitle = 'الإعدادات — الإدارة';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $keys = ['site_name','hero_title','hero_subtitle','hero_pitch','currency','issuance_fee','charge_fee_percent','min_initial_charge','min_deposit'];
    foreach ($keys as $k) {
        if (isset($_POST[$k])) setting_set($pdo, $k, $_POST[$k]);
    }
    flash_set('ok','تم الحفظ.');
    redirect('/admin/settings.php');
}
?>
<h1><i data-lucide="settings"></i> الإعدادات العامة</h1>
<form method="post" class="form panel">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <div class="form-section">
    <h3><i data-lucide="layout"></i> هوية الموقع والنصوص</h3>
    <label><span>اسم الموقع</span><input name="site_name" value="<?= e(setting_get($pdo,'site_name')) ?>"></label>
    <label><span>عنوان البطل (Hero)</span><input name="hero_title" value="<?= e(setting_get($pdo,'hero_title')) ?>"></label>
    <label><span>العنوان الفرعي</span><input name="hero_subtitle" value="<?= e(setting_get($pdo,'hero_subtitle')) ?>"></label>
    <label><span>وصف موجز</span><textarea name="hero_pitch" rows="3"><?= e(setting_get($pdo,'hero_pitch')) ?></textarea></label>
  </div>
  <div class="form-section">
    <h3><i data-lucide="banknote"></i> الرسوم والعملة</h3>
    <div class="grid-3">
      <label><span>العملة</span><input name="currency" value="<?= e(setting_get($pdo,'currency')) ?>"></label>
      <label><span>رسوم إصدار البطاقة</span><input type="number" step="0.01" name="issuance_fee" value="<?= e(setting_get($pdo,'issuance_fee')) ?>"></label>
      <label><span>نسبة رسوم الشحن (%)</span><input type="number" step="0.01" name="charge_fee_percent" value="<?= e(setting_get($pdo,'charge_fee_percent')) ?>"></label>
      <label><span>الحد الأدنى للشحن المبدئي</span><input type="number" step="0.01" name="min_initial_charge" value="<?= e(setting_get($pdo,'min_initial_charge')) ?>"></label>
      <label><span>الحد الأدنى للإيداع</span><input type="number" step="0.01" name="min_deposit" value="<?= e(setting_get($pdo,'min_deposit')) ?>"></label>
    </div>
  </div>
  <button class="btn btn-primary lg"><i data-lucide="save"></i> حفظ الإعدادات</button>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
