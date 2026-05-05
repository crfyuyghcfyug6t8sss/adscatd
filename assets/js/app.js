(function () {
  function $(s, root) { return (root || document).querySelector(s); }
  function $$(s, root) { return Array.from((root || document).querySelectorAll(s)); }

  // Scroll reveal
  document.addEventListener('DOMContentLoaded', function () {
    var els = $$('.reveal-up');
    if (!('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('in'); }); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add('in');
          io.unobserve(en.target);
        }
      });
    }, { threshold: .12 });
    els.forEach(function (e) { io.observe(e); });
  });

  // Copy card number
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.copy-card');
    if (btn) {
      var num = btn.getAttribute('data-num') || '';
      navigator.clipboard.writeText(num).then(function () {
        var original = btn.innerHTML;
        btn.innerHTML = '<i data-lucide="check"></i> تم النسخ';
        if (window.lucide) lucide.createIcons();
        setTimeout(function () { btn.innerHTML = original; if (window.lucide) lucide.createIcons(); }, 1400);
      });
    }
    var btn2 = e.target.closest('.copy-otp');
    if (btn2) {
      var box = btn2.closest('.otp-box');
      var code = box && box.getAttribute('data-code');
      if (code) navigator.clipboard.writeText(code).then(function () {
        var orig = btn2.innerHTML;
        btn2.innerHTML = '<i data-lucide="check"></i> تم';
        if (window.lucide) lucide.createIcons();
        setTimeout(function () { btn2.innerHTML = orig; if (window.lucide) lucide.createIcons(); }, 1400);
      });
    }
    var tog = e.target.closest('.toggle-mask');
    if (tog) {
      var card = tog.closest('.card-block');
      if (!card) return;
      var num = card.querySelector('.vc-number');
      if (!num) return;
      var hidden = num.dataset.hidden === '1';
      if (hidden) {
        num.textContent = num.dataset.full;
        num.dataset.hidden = '0';
      } else {
        num.textContent = num.dataset.mask;
        num.dataset.hidden = '1';
      }
    }
  });
})();
