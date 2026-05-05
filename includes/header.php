<?php
/** @var PDO $pdo */
$siteName = setting_get($pdo, 'site_name', 'my-ads.cards');
$cur = setting_get($pdo, 'currency', 'USD');
$me = current_user($pdo);
$navActive = $navActive ?? '';
$pageTitle = $pageTitle ?? $siteName;
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="theme-color" content="#0b1020">
<title><?= e($pageTitle) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Inter:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/styles.css?v=14">
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<header class="topbar">
  <div class="container nav">
    <a class="brand" href="/">
      <span class="brand-mark">
        <i data-lucide="credit-card"></i>
      </span>
      <span class="brand-text">
        <span class="brand-name">my-ads<span>.cards</span></span>
        <span class="brand-tag">visa &amp; mastercard for ads</span>
      </span>
    </a>
    <nav class="nav-links">
      <a class="<?= $navActive==='home'?'active':'' ?>" href="/">الرئيسية</a>
      <?php if ($me): ?>
        <a class="<?= $navActive==='dashboard'?'active':'' ?>" href="/dashboard.php">لوحتي</a>
        <a class="<?= $navActive==='cards'?'active':'' ?>" href="/cards.php">بطاقاتي</a>
        <a class="<?= $navActive==='wallet'?'active':'' ?>" href="/wallet.php">محفظتي</a>
        <a class="<?= $navActive==='apply'?'active':'' ?>" href="/apply.php">طلب بطاقة</a>
        <?php if ((int)$me['is_admin']===1): ?>
          <a class="<?= $navActive==='admin'?'active':'' ?>" href="/admin/index.php">الإدارة</a>
        <?php else: ?>
          <span class="wallet-badge" title="رصيد محفظتك"><i data-lucide="wallet"></i> <?= e(money(wallet_balance($pdo, (int)$me['id']), $cur)) ?></span>
        <?php endif; ?>
        <a href="/logout.php" class="btn btn-ghost"><i data-lucide="log-out"></i> خروج</a>
      <?php else: ?>
        <a class="<?= $navActive==='login'?'active':'' ?>" href="/login.php">دخول</a>
        <a class="btn btn-primary" href="/register.php"><i data-lucide="user-plus"></i> إنشاء حساب</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php foreach (flash_pop() as $f): ?>
  <div class="container"><div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div></div>
<?php endforeach; ?>
<main class="container main">
