<?php
// SQLite + schema + seed (with safe migrations)
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
  wallet_balance REAL DEFAULT 0,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS applications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  full_name TEXT NOT NULL,
  brand TEXT DEFAULT 'visa',
  initial_charge REAL NOT NULL DEFAULT 0,
  issuance_fee REAL NOT NULL DEFAULT 0,
  total_paid REAL NOT NULL DEFAULT 0,
  status TEXT DEFAULT 'pending',
  admin_note TEXT,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS cards (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  application_id INTEGER,
  brand TEXT DEFAULT 'visa',
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
  total_paid REAL NOT NULL DEFAULT 0,
  status TEXT DEFAULT 'pending',
  admin_note TEXT,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS wallet_deposits (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  amount REAL NOT NULL,
  payment_method_id INTEGER,
  payment_reference TEXT,
  note TEXT,
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

// --- Safe migrations for older DBs ---
function col_exists(PDO $pdo, string $table, string $col): bool {
    $rows = $pdo->query("PRAGMA table_info($table)")->fetchAll();
    foreach ($rows as $r) if ($r['name'] === $col) return true;
    return false;
}
if (!col_exists($pdo, 'users', 'wallet_balance')) {
    $pdo->exec("ALTER TABLE users ADD COLUMN wallet_balance REAL DEFAULT 0");
}
if (!col_exists($pdo, 'cards', 'brand')) {
    $pdo->exec("ALTER TABLE cards ADD COLUMN brand TEXT DEFAULT 'visa'");
}
if (!col_exists($pdo, 'applications', 'brand')) {
    $pdo->exec("ALTER TABLE applications ADD COLUMN brand TEXT DEFAULT 'visa'");
}
if (!col_exists($pdo, 'applications', 'total_paid')) {
    $pdo->exec("ALTER TABLE applications ADD COLUMN total_paid REAL NOT NULL DEFAULT 0");
}
if (!col_exists($pdo, 'charge_requests', 'total_paid')) {
    $pdo->exec("ALTER TABLE charge_requests ADD COLUMN total_paid REAL NOT NULL DEFAULT 0");
}
if (!col_exists($pdo, 'cards', 'fc_id')) {
    $pdo->exec("ALTER TABLE cards ADD COLUMN fc_id TEXT");
}
if (!col_exists($pdo, 'cards', 'fc_data')) {
    $pdo->exec("ALTER TABLE cards ADD COLUMN fc_data TEXT");
}
if (!col_exists($pdo, 'cards', 'fc_synced_at')) {
    $pdo->exec("ALTER TABLE cards ADD COLUMN fc_synced_at TEXT");
}

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
    'min_initial_charge'   => '5',
    'min_wallet_deposit'   => '10',
    'currency'             => 'USD',
    'site_name'            => 'my-ads.cards',
    'hero_title'           => 'my-ads.cards',
    'hero_subtitle'        => 'الوكالة الأولى المتخصصة بإصدار بطاقات Visa و Mastercard للإعلانات الممولة والمدفوعات الرقمية',
    'hero_pitch'           => 'أصدر بطاقتك الافتراضية بدقائق، اشحنها من محفظتك، واستخدمها مباشرة على فيسبوك وإنستغرام وتيك توك وجوجل وسناب وكافة منصات الإعلانات والمتاجر العالمية. معدل قبول مرتفع، 3DS مدعوم، استقرار عالٍ.',

    // FlexCard integration
    'flexcard_enabled'     => '1',
    'flexcard_base_url'    => 'https://flexcard.cards/api/v1',
    'flexcard_api_key'     => 'hN1ONAOu.0nuTduWPu6LuvDycWgp8KEfynLnH9AbE',
    'flexcard_auth_header' => 'Authorization',
    'flexcard_auth_prefix' => 'Api-Key',
    'flexcard_visa_service'=> '',
    'flexcard_mc_service'  => '',
    'flexcard_otp_last_id' => '0',
];
foreach ($defaults as $k => $v) {
    if (setting_get($pdo, $k) === null) setting_set($pdo, $k, $v);
}

// Default admin: phone 0968874525 / password Yazenstars1
$adminPhone = '0968874525';
$adminPass  = 'Yazenstars1';
$adminCount = (int)$pdo->query('SELECT COUNT(*) c FROM users WHERE is_admin = 1')->fetch()['c'];
if ($adminCount === 0) {
    $hash = password_hash($adminPass, PASSWORD_BCRYPT);
    $pdo->prepare('INSERT INTO users (phone, password_hash, full_name, is_admin) VALUES (?,?,?,1)')
        ->execute([$adminPhone, $hash, 'Administrator']);
} else {
    // Migrate legacy default admin if present
    $st = $pdo->prepare('SELECT id, password_hash FROM users WHERE phone = ? AND is_admin = 1');
    $st->execute(['admin']);
    $legacy = $st->fetch();
    if ($legacy && password_verify('admin123', $legacy['password_hash'])) {
        // Make sure new admin record exists; if not, rename this legacy one
        $exists = $pdo->prepare('SELECT id FROM users WHERE phone = ?');
        $exists->execute([$adminPhone]);
        if (!$exists->fetch()) {
            $pdo->prepare('UPDATE users SET phone = ?, password_hash = ? WHERE id = ?')
                ->execute([$adminPhone, password_hash($adminPass, PASSWORD_BCRYPT), $legacy['id']]);
        }
    }
}

$pmCount = (int)$pdo->query('SELECT COUNT(*) c FROM payment_methods')->fetch()['c'];
if ($pmCount === 0) {
    $stmt = $pdo->prepare('INSERT INTO payment_methods (name, details) VALUES (?,?)');
    $stmt->execute(['USDT TRC20', "العنوان: TXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\nأرسل المبلغ ثم ضع رقم الحوالة (TxID) في حقل المرجع."]);
    $stmt->execute(['Western Union', "الاسم: my-ads cards\nالدولة: حسب الاتفاق\nأرسل MTCN في حقل المرجع."]);
    $stmt->execute(['تحويل بنكي محلي', "للحصول على تفاصيل الحساب البنكي تواصل مع الدعم بعد طلب الإيداع."]);
}
