<?php
require_once __DIR__ . '/includes/bootstrap.php';
$me = require_login($pdo);
$cur = setting_get($pdo, 'currency', 'USD');

$cards = $pdo->prepare('SELECT * FROM cards WHERE user_id=? ORDER BY id DESC');
$cards->execute([$me['id']]);
$cards = $cards->fetchAll();

$pageTitle = 'بطاقاتي — my-ads.cards';
$navActive = 'cards';
include __DIR__ . '/includes/header.php';
?>
<section class="page-head reveal">
  <h1><i data-lucide="credit-card"></i> بطاقاتي</h1>
  <a href="/apply.php" class="btn btn-primary pulse-cta"><i data-lucide="plus"></i> طلب بطاقة جديدة</a>
</section>

<?php if (!$cards): ?>
  <div class="empty-card reveal-up">
    <i data-lucide="credit-card" class="ic-xl"></i>
    <h3>لا توجد بطاقات بعد</h3>
    <p class="muted">اشحن محفظتك ثم اطلب بطاقتك الأولى. ستظهر هنا بعد موافقة الإدارة.</p>
    <div class="row-gap" style="justify-content:center; margin-top: 12px;">
      <a class="btn btn-outline" href="/wallet.php"><i data-lucide="wallet"></i> شحن المحفظة</a>
      <a class="btn btn-primary" href="/apply.php"><i data-lucide="plus"></i> طلب بطاقة</a>
    </div>
  </div>
<?php else: ?>
  <div class="cards-grid">
    <?php foreach ($cards as $c):
      $sNotifs = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? AND (card_id=? OR card_id IS NULL) ORDER BY id DESC LIMIT 10');
      $sNotifs->execute([$me['id'], $c['id']]);
      $cardNotifs = $sNotifs->fetchAll();
      [$lbl,$cls] = status_label($c['status']);
      $brand = $c['brand'] ?? 'visa';
    ?>
      <article class="card-block reveal-up">
        <div class="visa-card brand-<?= e($brand==='mastercard'?'mc':'visa') ?> <?= $c['status']==='frozen' ? 'frozen' : '' ?>">
          <div class="vc-row vc-top">
            <span class="vc-bank">my-ads.cards</span>
            <?php if ($brand==='mastercard'): ?>
              <span class="vc-mc"><span class="mc-c c-r"></span><span class="mc-c c-y"></span></span>
            <?php else: ?>
              <span class="vc-brand">VISA</span>
            <?php endif; ?>
          </div>
          <div class="vc-chip"><i data-lucide="cpu"></i></div>
          <div class="vc-number" data-full="<?= e(format_card($c['card_number'])) ?>" data-mask="<?= e(mask_card($c['card_number'])) ?>"><?= e(format_card($c['card_number'])) ?></div>
          <div class="vc-row vc-bottom">
            <div class="vc-holder">
              <span class="lbl">CARD HOLDER</span>
              <span class="val"><?= e(strtoupper($c['holder_name'])) ?></span>
            </div>
            <div class="vc-exp">
              <span class="lbl">EXPIRES</span>
              <span class="val"><?= e($c['expiry']) ?></span>
            </div>
            <div class="vc-cvv">
              <span class="lbl">CVV</span>
              <span class="val"><?= e($c['cvv']) ?></span>
            </div>
          </div>
          <span class="vc-glow"></span>
          <?php if ($c['status']==='frozen'): ?><span class="vc-frozen-tag"><i data-lucide="snowflake"></i> مجمدة</span><?php endif; ?>
        </div>

        <div class="card-meta">
          <div class="card-meta-row">
            <span class="muted"><i data-lucide="wallet"></i> الرصيد</span>
            <strong><?= e(money($c['balance'], $cur)) ?></strong>
          </div>
          <div class="card-meta-row">
            <span class="muted"><i data-lucide="activity"></i> الحالة</span>
            <span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span>
          </div>
          <div class="card-meta-row">
            <span class="muted"><i data-lucide="layers"></i> النوع</span>
            <strong><?= e(card_brand_label($brand)) ?></strong>
          </div>
          <div class="card-actions">
            <a href="/charge.php?card_id=<?= (int)$c['id'] ?>" class="btn btn-primary"><i data-lucide="refresh-cw"></i> شحن البطاقة</a>
            <button type="button" class="btn btn-outline copy-card" data-num="<?= e(preg_replace('/\D/','',$c['card_number'])) ?>"><i data-lucide="copy"></i> نسخ الرقم</button>
            <button type="button" class="btn btn-ghost toggle-mask"><i data-lucide="eye-off"></i> إخفاء/إظهار</button>
          </div>
        </div>

        <div class="card-notifs">
          <h4><i data-lucide="bell"></i> إشعارات البطاقة (OTP / 3DS)</h4>
          <?php if (!$cardNotifs): ?>
            <p class="empty">لا توجد إشعارات.</p>
          <?php else: ?>
            <ul class="notif-list">
              <?php foreach ($cardNotifs as $n): ?>
                <li class="notif notif-<?= e($n['kind']) ?>">
                  <i data-lucide="<?= $n['kind']==='otp'?'key-round':($n['kind']==='3ds'?'shield-check':'info') ?>" class="ic"></i>
                  <div>
                    <strong><?= e($n['title']) ?></strong>
                    <?php if ($n['kind'] === 'otp' && preg_match('/\b\d{3,8}\b/', (string)$n['message'], $m)): ?>
                      <div class="otp-box" data-code="<?= e($m[0]) ?>">
                        <span class="lbl">رمز التحقق</span>
                        <span class="code"><?= e($m[0]) ?></span>
                        <button type="button" class="btn btn-ghost sm copy-otp"><i data-lucide="copy"></i> نسخ</button>
                      </div>
                    <?php endif; ?>
                    <p><?= nl2br(e($n['message'])) ?></p>
                    <span class="muted small"><?= e($n['created_at']) ?></span>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
