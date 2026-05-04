<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = setting_get($pdo, 'site_name', 'rozana agency') . ' — بطاقات فيزا للإعلانات والمدفوعات';
$navActive = 'home';
$bodyClass = 'home';
include __DIR__ . '/includes/header.php';

$heroTitle    = setting_get($pdo, 'hero_title', 'rozana agency');
$heroSubtitle = setting_get($pdo, 'hero_subtitle', '');
$heroPitch    = setting_get($pdo, 'hero_pitch', '');
?>

<section class="hero">
  <div class="hero-grid">
    <div class="hero-text">
      <span class="pill"><i data-lucide="badge-check"></i> الوكالة الأولى للبطاقات الإعلانية</span>
      <h1><?= e($heroTitle) ?></h1>
      <h2 class="hero-sub"><?= e($heroSubtitle) ?></h2>
      <p class="hero-pitch"><?= e($heroPitch) ?></p>
      <div class="hero-cta">
        <a class="btn btn-primary lg" href="/register.php"><i data-lucide="credit-card"></i> اطلب بطاقتك الآن</a>
        <a class="btn btn-outline lg" href="#features"><i data-lucide="info"></i> تعرّف أكثر</a>
      </div>
      <div class="hero-stats">
        <div><strong>+99%</strong><span>معدل قبول الإعلانات</span></div>
        <div><strong>24/7</strong><span>دعم مباشر</span></div>
        <div><strong>عالمية</strong><span>تعمل في كل الدول</span></div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="visa-card preview">
        <div class="vc-row vc-top">
          <span class="vc-bank">rozana agency</span>
          <span class="vc-brand">VISA</span>
        </div>
        <div class="vc-chip">
          <i data-lucide="cpu"></i>
        </div>
        <div class="vc-number">4•••&nbsp;&nbsp;••••&nbsp;&nbsp;••••&nbsp;&nbsp;0421</div>
        <div class="vc-row vc-bottom">
          <div class="vc-holder">
            <span class="lbl">CARD HOLDER</span>
            <span class="val">YOUR NAME</span>
          </div>
          <div class="vc-exp">
            <span class="lbl">EXPIRES</span>
            <span class="val">12/29</span>
          </div>
        </div>
        <span class="vc-glow"></span>
      </div>
    </div>
  </div>
</section>

<section id="features" class="features">
  <h3 class="section-title"><i data-lucide="sparkles"></i> لماذا rozana agency؟</h3>
  <div class="grid-cards">
    <article class="feature">
      <i data-lucide="megaphone" class="ic"></i>
      <h4>مضمونة للإعلانات الممولة</h4>
      <p>بطاقات مهيّأة للعمل بكفاءة على فيسبوك، إنستغرام، تيك توك، جوجل، تويتر، سناب وكافة منصات الإعلانات.</p>
    </article>
    <article class="feature">
      <i data-lucide="shield-check" class="ic"></i>
      <h4>قبول مرتفع وثبات</h4>
      <p>معدل قبول مرتفع وحماية ضد الإيقاف، مع متابعة لحظية لأي عملية شحن أو دفع.</p>
    </article>
    <article class="feature">
      <i data-lucide="globe" class="ic"></i>
      <h4>تعمل في كل الدول</h4>
      <p>بطاقات تدعم البلدان والعملات المختلفة وتمر على بوابات 3DS العالمية.</p>
    </article>
    <article class="feature">
      <i data-lucide="zap" class="ic"></i>
      <h4>إصدار وشحن سريع</h4>
      <p>تقديم الطلب بدقائق، وبعد موافقة الإدارة تظهر بياناتك مباشرة في "بطاقاتي".</p>
    </article>
    <article class="feature">
      <i data-lucide="lock" class="ic"></i>
      <h4>OTP و 3DS مدعوم</h4>
      <p>تلقَّ رموز التحقق وإشعارات 3DS داخل الموقع تحت بطاقتك مباشرة من قسم البطاقة.</p>
    </article>
    <article class="feature">
      <i data-lucide="wallet" class="ic"></i>
      <h4>طرق دفع متعددة</h4>
      <p>USDT، حوالات بنكية، ويسترن يونيون وغيرها — كلها مرنة ومُدارة يدوياً من الإدارة.</p>
    </article>
  </div>
</section>

<section class="how">
  <h3 class="section-title"><i data-lucide="list-checks"></i> كيف تحصل على بطاقتك؟</h3>
  <ol class="steps">
    <li>
      <span class="step-n">1</span>
      <div>
        <h4>أنشئ حساباً</h4>
        <p>برقم هاتفك وكلمة سر فقط، بدون أي رموز تحقق إضافية.</p>
      </div>
      <i data-lucide="user-plus" class="ic"></i>
    </li>
    <li>
      <span class="step-n">2</span>
      <div>
        <h4>قدّم طلب البطاقة</h4>
        <p>أدخل معلوماتك الشخصية، حدد قيمة الشحن، رسوم الإصدار، والإيداع.</p>
      </div>
      <i data-lucide="file-text" class="ic"></i>
    </li>
    <li>
      <span class="step-n">3</span>
      <div>
        <h4>انتظار مراجعة الإدارة</h4>
        <p>ستتم مراجعة طلبك يدوياً للتأكد من صحته وتأكيد الدفع.</p>
      </div>
      <i data-lucide="user-check" class="ic"></i>
    </li>
    <li>
      <span class="step-n">4</span>
      <div>
        <h4>استلم بطاقتك</h4>
        <p>تظهر البطاقة في قسم "بطاقاتي" بشكل بطاقة فيزا حقيقية وجاهزة للاستخدام.</p>
      </div>
      <i data-lucide="credit-card" class="ic"></i>
    </li>
  </ol>
</section>

<section class="cta">
  <div class="cta-box">
    <div>
      <h3>ابدأ حملاتك الإعلانية بثقة</h3>
      <p>انضم إلى آلاف المعلنين الذين يعتمدون على rozana agency لتشغيل حملاتهم على المنصات العالمية.</p>
    </div>
    <a class="btn btn-primary lg" href="/register.php"><i data-lucide="rocket"></i> أنشئ حسابك الآن</a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
