<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = setting_get($pdo, 'site_name', 'my-ads.cards') . ' — Visa & Mastercard للإعلانات والمدفوعات';
$navActive = 'home';
$bodyClass = 'home';
include __DIR__ . '/includes/header.php';

$heroTitle    = setting_get($pdo, 'hero_title', 'my-ads.cards');
$heroSubtitle = setting_get($pdo, 'hero_subtitle', '');
$heroPitch    = setting_get($pdo, 'hero_pitch', '');
?>

<section class="hero reveal">
  <div class="hero-grid">
    <div class="hero-text">
      <span class="pill float-in"><i data-lucide="badge-check"></i> الوكالة الأولى للبطاقات الإعلانية — Visa &amp; Mastercard</span>
      <h1 class="float-in delay-1"><?= e($heroTitle) ?></h1>
      <h2 class="hero-sub float-in delay-2"><?= e($heroSubtitle) ?></h2>
      <p class="hero-pitch float-in delay-3"><?= e($heroPitch) ?></p>
      <div class="hero-cta float-in delay-4">
        <a class="btn btn-primary lg pulse-cta" href="/register.php"><i data-lucide="credit-card"></i> اطلب بطاقتك الآن</a>
        <a class="btn btn-outline lg" href="#features"><i data-lucide="info"></i> تعرّف أكثر</a>
      </div>
      <div class="hero-stats float-in delay-5">
        <div><strong>+99%</strong><span>معدل القبول</span></div>
        <div><strong>24/7</strong><span>دعم مباشر</span></div>
        <div><strong>عالمية</strong><span>كل الدول</span></div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="card-stack">
        <div class="visa-card preview brand-mc tilt-1">
          <div class="vc-row vc-top">
            <span class="vc-bank">my-ads.cards</span>
            <span class="vc-mc"><span class="mc-c c-r"></span><span class="mc-c c-y"></span></span>
          </div>
          <div class="vc-chip"><i data-lucide="cpu"></i></div>
          <div class="vc-number">5•••&nbsp;&nbsp;••••&nbsp;&nbsp;••••&nbsp;&nbsp;7193</div>
          <div class="vc-row vc-bottom">
            <div class="vc-holder"><span class="lbl">CARD HOLDER</span><span class="val">YOUR NAME</span></div>
            <div class="vc-exp"><span class="lbl">EXPIRES</span><span class="val">11/30</span></div>
          </div>
          <span class="vc-glow"></span>
        </div>
        <div class="visa-card preview brand-visa tilt-2 floaty">
          <div class="vc-row vc-top">
            <span class="vc-bank">my-ads.cards</span>
            <span class="vc-brand">VISA</span>
          </div>
          <div class="vc-chip"><i data-lucide="cpu"></i></div>
          <div class="vc-number">4•••&nbsp;&nbsp;••••&nbsp;&nbsp;••••&nbsp;&nbsp;0421</div>
          <div class="vc-row vc-bottom">
            <div class="vc-holder"><span class="lbl">CARD HOLDER</span><span class="val">YOUR NAME</span></div>
            <div class="vc-exp"><span class="lbl">EXPIRES</span><span class="val">12/29</span></div>
          </div>
          <span class="vc-glow"></span>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="features" class="features reveal">
  <h3 class="section-title"><i data-lucide="sparkles"></i> لماذا my-ads.cards؟</h3>
  <div class="grid-cards">
    <article class="feature reveal-up">
      <i data-lucide="megaphone" class="ic"></i>
      <h4>مضمونة للإعلانات الممولة</h4>
      <p>بطاقات Visa و Mastercard مهيّأة للعمل بكفاءة على فيسبوك، إنستغرام، تيك توك، جوجل، تويتر، سناب، وكافة منصات الإعلانات.</p>
    </article>
    <article class="feature reveal-up">
      <i data-lucide="shield-check" class="ic"></i>
      <h4>قبول مرتفع وثبات</h4>
      <p>معدل قبول مرتفع وحماية ضد الإيقاف، مع متابعة لحظية لأي عملية شحن أو دفع.</p>
    </article>
    <article class="feature reveal-up">
      <i data-lucide="globe" class="ic"></i>
      <h4>تعمل في كل الدول</h4>
      <p>بطاقات تدعم البلدان والعملات المختلفة وتمر على بوابات 3DS العالمية.</p>
    </article>
    <article class="feature reveal-up">
      <i data-lucide="zap" class="ic"></i>
      <h4>إصدار وشحن سريع</h4>
      <p>عبّئ المحفظة، اختر Visa أو Mastercard، وستظهر بطاقتك خلال دقائق بعد موافقة الإدارة.</p>
    </article>
    <article class="feature reveal-up">
      <i data-lucide="lock" class="ic"></i>
      <h4>OTP و 3DS مدعوم</h4>
      <p>تلقَّ رموز التحقق وإشعارات 3DS مباشرة تحت بطاقتك من قسم البطاقة.</p>
    </article>
    <article class="feature reveal-up">
      <i data-lucide="wallet" class="ic"></i>
      <h4>محفظة موحدة</h4>
      <p>اشحن محفظتك بطرق دفع متعددة، ثم أنشئ بطاقات وشحنها فوراً بدون تعقيدات.</p>
    </article>
  </div>
</section>

<section class="how reveal">
  <h3 class="section-title"><i data-lucide="list-checks"></i> كيف تحصل على بطاقتك؟</h3>
  <ol class="steps">
    <li class="reveal-up">
      <span class="step-n">1</span>
      <div><h4>أنشئ حساباً</h4><p>برقم هاتفك وكلمة سر فقط — بدون أي رموز تحقق إضافية.</p></div>
      <i data-lucide="user-plus" class="ic"></i>
    </li>
    <li class="reveal-up">
      <span class="step-n">2</span>
      <div><h4>اشحن محفظتك</h4><p>عبر طرق الدفع المتاحة (USDT، حوالات بنكية، WU وغيرها).</p></div>
      <i data-lucide="wallet" class="ic"></i>
    </li>
    <li class="reveal-up">
      <span class="step-n">3</span>
      <div><h4>اطلب بطاقتك</h4><p>اختر Visa أو Mastercard، ضع قيمة الشحن — يتم خصم الرسوم من المحفظة.</p></div>
      <i data-lucide="credit-card" class="ic"></i>
    </li>
    <li class="reveal-up">
      <span class="step-n">4</span>
      <div><h4>استخدم بطاقتك</h4><p>تظهر فوراً بعد الموافقة في "بطاقاتي" بشكل بطاقة حقيقية وجاهزة للاستخدام.</p></div>
      <i data-lucide="rocket" class="ic"></i>
    </li>
  </ol>
</section>

<section class="cta reveal">
  <div class="cta-box">
    <div>
      <h3>ابدأ حملاتك الإعلانية بثقة</h3>
      <p>انضم إلى آلاف المعلنين الذين يعتمدون على my-ads.cards لتشغيل حملاتهم على المنصات العالمية.</p>
    </div>
    <a class="btn btn-primary lg pulse-cta" href="/register.php"><i data-lucide="rocket"></i> أنشئ حسابك الآن</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
