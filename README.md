# rozana agency

موقع PHP لإصدار بطاقات فيزا للإعلانات الممولة والمدفوعات الرقمية.

## التشغيل

يحتاج: PHP 8.1+ مع امتداد `pdo_sqlite`.

```bash
php -S 0.0.0.0:8080 -t .
```

ثم افتح: `http://localhost:8080`

عند أول تشغيل يتم إنشاء قاعدة البيانات في `data/rozana.db` وحساب الأدمن الافتراضي:

- الهاتف: `admin`
- كلمة السر: `admin123`

> غيّر كلمة السر فوراً من قسم المستخدمين بعد الدخول.

## البنية

- `index.php` — الصفحة الرئيسية
- `register.php` / `login.php` / `logout.php` — حسابات
- `dashboard.php` — لوحة المستخدم
- `apply.php` — تقديم طلب بطاقة
- `cards.php` — بطاقاتي + إشعارات OTP/3DS
- `charge.php` — طلب شحن بطاقة
- `admin/` — لوحة الإدارة (طلبات، بطاقات، مستخدمون، طرق دفع، إشعارات، إعدادات)
- `includes/` — bootstrap (DB, auth, helpers, layout)
- `assets/` — CSS و JS
