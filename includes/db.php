<?php
// SQLite + schema + seed
$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) @mkdir($dataDir, 0775, true);

$dbPath = $dataDir . '/rozana.db';
try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
} catch (Throwable $e) {
    http_response_code(500);
    die('Database error: ' . htmlspecialchars($e->getMessage()));
}

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  phone TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  full_name TEXT,
  dob TEXT,
  is_admin INTEGER DEFAULT 0,
  is_blocked INTEGER DEFAULT 0,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS applications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  full_name TEXT NOT NULL,
  dob TEXT NOT NULL,
  country TEXT,
  address TEXT,
  initial_charge REAL NOT NULL DEFAULT 0,
  issuance_fee REAL NOT NULL DEFAULT 0,
  deposit REAL NOT NULL DEFAULT 0,
  payment_method_id INTEGER,
  payment_reference TEXT,
  status TEXT DEFAULT 'pending',
  admin_note TEXT,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS cards (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  application_id INTEGER,
  holder_name TEXT NOT NULL,
  card_number TEXT NOT NULL,
  expiry TEXT NOT NULL,
  cvv TEXT NOT NULL,
  balance REAL DEFAULT 0,
  status TEXT DEFAULT 'active',
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS charge_requests (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  card_id INTEGER NOT NULL,
  amount REAL NOT NULL,
  fee REAL NOT NULL DEFAULT 0,
  payment_method_id INTEGER,
  payment_reference TEXT,
  status TEXT DEFAULT 'pending',
  admin_note TEXT,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  card_id INTEGER,
  type TEXT NOT NULL,
  amount REAL NOT NULL,
  note TEXT,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS payment_methods (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  details TEXT,
  active INTEGER DEFAULT 1,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT
);

CREATE TABLE IF NOT EXISTS notifications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  card_id INTEGER,
  title TEXT NOT NULL,
  message TEXT,
  kind TEXT DEFAULT 'info',
  is_read INTEGER DEFAULT 0,
  created_at TEXT DEFAULT (datetime('now'))
);
");

function setting_get(PDO $pdo, string $key, $fallback = null) {
    $st = $pdo->prepare('SELECT value FROM settings WHERE key = ?');
    $st->execute([$key]);
    $r = $st->fetch();
    return $r ? $r['value'] : $fallback;
}
function setting_set(PDO $pdo, string $key, $value): void {
    $st = $pdo->prepare("INSERT INTO settings(key,value) VALUES(?,?)
      ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    $st->execute([$key, (string)$value]);
}

$defaults = [
    'issuance_fee'         => '5',
    'charge_fee_percent'   => '3',
    'min_initial_charge'   => '20',
    'min_deposit'          => '0',
    'currency'             => 'USD',
    'site_name'            => 'rozana agency',
    'hero_title'           => 'rozana agency',
    'hero_subtitle'        => 'الوكالة الأولى المتخصصة بإصدار بطاقات فيزا للإعلانات الممولة والمدفوعات الرقمية',
    'hero_pitch'           => 'بطاقات فيزا عالمية مضمونة 100% للإعلانات الممولة على فيسبوك، إنستغرام، تيك توك، جوجل، تويتر وكافة منصات الدفع. معدل قبول مرتفع، استقرار عالٍ، ودعم متابعة المدفوعات على مدار الساعة.',
];
foreach ($defaults as $k => $v) {
    if (setting_get($pdo, $k) === null) setting_set($pdo, $k, $v);
}

// Seed default admin
$adminCount = (int)$pdo->query('SELECT COUNT(*) c FROM users WHERE is_admin = 1')->fetch()['c'];
if ($adminCount === 0) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->prepare('INSERT INTO users (phone, password_hash, full_name, is_admin) VALUES (?,?,?,1)')
        ->execute(['admin', $hash, 'Administrator']);
}

// Seed payment methods
$pmCount = (int)$pdo->query('SELECT COUNT(*) c FROM payment_methods')->fetch()['c'];
if ($pmCount === 0) {
    $stmt = $pdo->prepare('INSERT INTO payment_methods (name, details) VALUES (?,?)');
    $stmt->execute(['USDT TRC20', "العنوان: TXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\nأرسل المبلغ ثم ضع رقم الحوالة (TxID) في حقل المرجع."]);
    $stmt->execute(['Western Union', "الاسم: Rozana Agency\nالدولة: حسب الاتفاق\nأرسل MTCN في حقل المرجع."]);
    $stmt->execute(['تحويل بنكي محلي', "للحصول على تفاصيل الحساب البنكي تواصل مع الدعم بعد تقديم الطلب."]);
}
