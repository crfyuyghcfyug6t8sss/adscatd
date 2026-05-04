<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user($pdo)) redirect('/dashboard.php');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $phone = trim($_POST['phone'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');
    $st = $pdo->prepare('SELECT * FROM users WHERE phone = ?');
    $st->execute([$phone]);
    $u = $st->fetch();
    if (!$u || !password_verify($pass, $u['password_hash'])) {
        $err = 'بيانات الدخول غير صحيحة.';
    } elseif ((int)$u['is_blocked'] === 1) {
        $err = 'حسابك موقوف. تواصل مع الدعم.';
    } else {
        login_user((int)$u['id']);
        flash_set('ok', 'تم تسجيل الدخول.');
        redirect((int)$u['is_admin'] === 1 ? '/admin/index.php' : '/dashboard.php');
    }
}

$pageTitle = 'تسجيل الدخول — rozana agency';
$navActive = 'login';
include __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <i data-lucide="log-in" class="ic-lg"></i>
      <h1>تسجيل الدخول</h1>
      <p class="muted">أدخل رقم هاتفك وكلمة السر للمتابعة.</p>
    </div>
    <?php if ($err): ?><div class="flash flash-bad"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label>
        <span><i data-lucide="phone"></i> رقم الهاتف</span>
        <input type="text" name="phone" required dir="ltr" value="<?= e($_POST['phone'] ?? '') ?>">
      </label>
      <label>
        <span><i data-lucide="lock"></i> كلمة السر</span>
        <input type="password" name="password" required>
      </label>
      <button class="btn btn-primary lg" type="submit"><i data-lucide="arrow-left"></i> دخول</button>
    </form>
    <p class="auth-foot">ما عندك حساب؟ <a href="/register.php">أنشئ حساب جديد</a></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
