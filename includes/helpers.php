<?php
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function flash_set(string $type, string $msg): void { $_SESSION['flash'][] = ['type'=>$type,'msg'=>$msg]; }
function flash_pop(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function redirect(string $url): void { header('Location: ' . $url); exit; }

function money($v, string $cur = 'USD'): string {
    $v = (float)$v;
    return number_format($v, 2) . ' ' . $cur;
}

function status_label(string $s): array {
    return [
        'pending'  => ['قيد المراجعة', 'pending'],
        'approved' => ['مقبول',         'ok'],
        'rejected' => ['مرفوض',         'bad'],
        'active'   => ['نشطة',          'ok'],
        'frozen'   => ['مجمدة',         'warn'],
        'closed'   => ['مغلقة',         'bad'],
    ][$s] ?? [$s, ''];
}

function gen_card_number(): string {
    // Visa starts with 4 — 16 digits total
    $num = '4';
    for ($i = 0; $i < 15; $i++) $num .= random_int(0, 9);
    return $num;
}
function gen_expiry(): string {
    $y = (int)date('y') + random_int(3, 5);
    $m = str_pad((string)random_int(1, 12), 2, '0', STR_PAD_LEFT);
    return $m . '/' . str_pad((string)$y, 2, '0', STR_PAD_LEFT);
}
function gen_cvv(): string {
    return str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
}
function mask_card(string $num): string {
    $clean = preg_replace('/\D/', '', $num);
    if (strlen($clean) < 8) return $num;
    $first = substr($clean, 0, 4);
    $last  = substr($clean, -4);
    return $first . ' •••• •••• ' . $last;
}
function format_card(string $num): string {
    $clean = preg_replace('/\D/', '', $num);
    return trim(chunk_split($clean, 4, ' '));
}

function notify(PDO $pdo, int $userId, ?int $cardId, string $title, string $msg, string $kind = 'info'): void {
    $st = $pdo->prepare('INSERT INTO notifications (user_id, card_id, title, message, kind) VALUES (?,?,?,?,?)');
    $st->execute([$userId, $cardId, $title, $msg, $kind]);
}

function txn(PDO $pdo, int $userId, ?int $cardId, string $type, float $amount, string $note = ''): void {
    $st = $pdo->prepare('INSERT INTO transactions (user_id, card_id, type, amount, note) VALUES (?,?,?,?,?)');
    $st->execute([$userId, $cardId, $type, $amount, $note]);
}
