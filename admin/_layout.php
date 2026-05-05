<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_admin($pdo);
$adminActive = $adminActive ?? '';
$pageTitle = $pageTitle ?? 'الإدارة — my-ads.cards';
$navActive = 'admin';
include __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
<aside class="admin-side">
  <h3><i data-lucide="layout-dashboard"></i> لوحة الإدارة</h3>
  <nav class="admin-nav">
    <a href="/admin/index.php" class="<?= $adminActive==='home'?'active':'' ?>"><i data-lucide="gauge"></i> نظرة عامة</a>
    <a href="/admin/applications.php" class="<?= $adminActive==='apps'?'active':'' ?>"><i data-lucide="file-text"></i> طلبات الإصدار</a>
    <a href="/admin/cards.php" class="<?= $adminActive==='cards'?'active':'' ?>"><i data-lucide="credit-card"></i> البطاقات</a>
    <a href="/admin/charges.php" class="<?= $adminActive==='charges'?'active':'' ?>"><i data-lucide="refresh-cw"></i> طلبات الشحن</a>
    <a href="/admin/wallet_deposits.php" class="<?= $adminActive==='dep'?'active':'' ?>"><i data-lucide="wallet"></i> إيداعات المحفظة</a>
    <a href="/admin/users.php" class="<?= $adminActive==='users'?'active':'' ?>"><i data-lucide="users"></i> المستخدمون</a>
    <a href="/admin/payment_methods.php" class="<?= $adminActive==='pm'?'active':'' ?>"><i data-lucide="banknote"></i> طرق الدفع</a>
    <a href="/admin/notifications.php" class="<?= $adminActive==='notif'?'active':'' ?>"><i data-lucide="bell"></i> الإشعارات و OTP</a>
    <a href="/admin/settings.php" class="<?= $adminActive==='settings'?'active':'' ?>"><i data-lucide="settings"></i> الإعدادات</a>
  </nav>
</aside>
<section class="admin-main">
