<?php
$adminActive = 'settings';
$pageTitle = 'الإعدادات — الإدارة';
require __DIR__ . '/_layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $keys = ['site_name','hero_title','hero_subtitle','hero_pitch','currency','issuance_fee','charge_fee_percent','min_initial_charge','min_wallet_deposit',
             'flexcard_enabled','flexcard_base_url','flexcard_api_key','flexcard_auth_header','flexcard_auth_prefix','flexcard_visa_service','flexcard_mc_service'];
    foreach ($keys as $k) {
        if ($k === 'flexcard_enabled') {
            setting_set($pdo, $k, isset($_POST[$k]) ? '1' : '0');
        } elseif (isset($_POST[$k])) {
            setting_set($pdo, $k, $_POST[$k]);
        }
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
      <label><span>الحد الأدنى لإيداع المحفظة</span><input type="number" step="0.01" name="min_wallet_deposit" value="<?= e(setting_get($pdo,'min_wallet_deposit')) ?>"></label>
    </div>
  </div>
  <div class="form-section">
    <h3><i data-lucide="zap"></i> تكامل FlexCard</h3>
    <label class="check"><input type="checkbox" name="flexcard_enabled" value="1" <?= setting_get($pdo,'flexcard_enabled')==='1'?'checked':'' ?>> <span>تفعيل التكامل (إصدار البطاقات و OTP من FlexCard)</span></label>
    <div class="grid-2">
      <label><span>Base URL</span><input dir="ltr" name="flexcard_base_url" value="<?= e(setting_get($pdo,'flexcard_base_url')) ?>"></label>
      <label><span>API Key</span><input dir="ltr" name="flexcard_api_key" value="<?= e(setting_get($pdo,'flexcard_api_key')) ?>"></label>
      <label><span>Auth header name</span><input dir="ltr" name="flexcard_auth_header" value="<?= e(setting_get($pdo,'flexcard_auth_header')) ?>"></label>
      <label><span>Auth header prefix</span><input dir="ltr" name="flexcard_auth_prefix" value="<?= e(setting_get($pdo,'flexcard_auth_prefix')) ?>" placeholder="مثل: Api-Key أو Bearer"></label>
      <label><span>Visa Service ID</span><input dir="ltr" name="flexcard_visa_service" value="<?= e(setting_get($pdo,'flexcard_visa_service')) ?>"></label>
      <label><span>Mastercard Service ID</span><input dir="ltr" name="flexcard_mc_service" value="<?= e(setting_get($pdo,'flexcard_mc_service')) ?>"></label>
    </div>
    <p class="muted small">يمكنك جلب قائمة الخدمات (BINs) من <a href="/admin/flexcard.php">صفحة FlexCard</a> ثم اختيار المعرّف المناسب لكل علامة.</p>
  </div>

  <button class="btn btn-primary lg"><i data-lucide="save"></i> حفظ الإعدادات</button>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
