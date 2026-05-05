</main>
<footer class="footer">
  <div class="container foot-grid">
    <div>
      <div class="brand small">
        <span class="brand-mark"><i data-lucide="credit-card"></i></span>
        <span class="brand-text"><span class="brand-name">my-ads<span>.cards</span></span></span>
      </div>
      <p class="muted">الوكالة الأولى المتخصصة بإصدار بطاقات Visa و Mastercard للإعلانات الممولة والمدفوعات الرقمية.</p>
    </div>
    <div>
      <h4>روابط</h4>
      <ul class="links">
        <li><a href="/">الرئيسية</a></li>
        <li><a href="/register.php">إنشاء حساب</a></li>
        <li><a href="/login.php">تسجيل دخول</a></li>
      </ul>
    </div>
    <div>
      <h4>الدعم</h4>
      <ul class="links">
        <li><i data-lucide="headphones"></i> دعم على مدار الساعة</li>
        <li><i data-lucide="shield-check"></i> معاملات آمنة</li>
        <li><i data-lucide="globe"></i> تغطية دولية</li>
      </ul>
    </div>
  </div>
  <div class="container foot-bottom">
    <span>© <?= date('Y') ?> my-ads.cards</span>
    <span class="muted">All rights reserved</span>
  </div>
</footer>
<script>
  document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
<script src="/assets/js/app.js?v=8" defer></script>
</body>
</html>
