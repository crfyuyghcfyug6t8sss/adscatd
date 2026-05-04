<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user($pdo)) redirect('/dashboard.php');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $phone = trim($_POST['phone'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');
    $name  = trim($_POST['full_name'] ?? '');

    if ($phone === '' || $pass === '' || $name === '') {
        $err = 'كل الحقول مطلوبة.';
    } elseif (strlen($pass) < 6) {
        $err = 'كلمة السر يجب ألا تقل عن 6 أحرف.';
    } else {
        $st = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
        $st->execute([$phone]);
        if ($st->fetch()) {
            $err = 'هذا الرقم مسجّل مسبقاً.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO users (phone, password_hash, full_name) VALUES (?,?,?)')
                ->execute([$phone, $hash, $name]);
            login_user((int)$pdo->lastInsertId());
            flash_set('ok', 'تم إنشاء الحساب بنجاح. أهلاً بك.');
            redirect('/dashboard.php');
        }
    }
}

$pageTitle = 'إنشاء حساب — rozana agency';
$navActive = 'register';
include __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <i data-lucide="user-plus" class="ic-lg"></i>
      <h1>إنشاء حساب جديد</h1>
      <p class="muted">سجّل برقم هاتفك وكلمة السر فقط — بدون رموز تحقق.</p>
    </div>
    <?php if ($err): ?><div class="flash flash-bad"><?= e($err) ?></div><?php endif; ?>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label>
        <span><i data-lucide="user"></i> الاسم الكامل</span>
        <input type="text" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>">
      </label>
      <label>
        <span><i data-lucide="phone"></i> رقم الهاتف</span>
        <input type="tel" name="phone" required dir="ltr" placeholder="+9639xxxxxxxx" value="<?= e($_POST['phone'] ?? '') ?>">
      </label>
      <label>
        <span><i data-lucide="lock"></i> كلمة السر</span>
        <input type="password" name="password" required minlength="6">
      </label>
      <button class="btn btn-primary lg" type="submit"><i data-lucide="check"></i> إنشاء الحساب</button>
    </form>
    <p class="auth-foot">عندك حساب؟ <a href="/login.php">سجّل الدخول</a></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
