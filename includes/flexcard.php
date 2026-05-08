<?php
/**
 * FlexCard API client.
 *
 * All functions return: ['ok'=>bool,'status'=>int,'body'=>mixed,'error'=>?string,'raw'=>?string]
 */

function fc_enabled(PDO $pdo): bool {
    return setting_get($pdo, 'flexcard_enabled', '0') === '1';
}

function fc_config(PDO $pdo): array {
    return [
        'base'   => rtrim((string)setting_get($pdo, 'flexcard_base_url', 'https://flexcard.cards/api/v1'), '/'),
        'key'    => trim((string)setting_get($pdo, 'flexcard_api_key', '')),
        'header' => (string)setting_get($pdo, 'flexcard_auth_header', 'Authorization'),
        'prefix' => (string)setting_get($pdo, 'flexcard_auth_prefix', 'Api-Key'),
    ];
}

function fc_request(PDO $pdo, string $method, string $path, $body = null): array {
    $cfg = fc_config($pdo);
    if ($cfg['key'] === '') {
        return ['ok' => false, 'status' => 0, 'error' => 'API key غير مضبوط', 'body' => null, 'raw' => null];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'status' => 0, 'error' => 'PHP cURL غير مفعّل على الخادم', 'body' => null, 'raw' => null];
    }
    $url = $cfg['base'] . $path;
    $authValue = trim($cfg['prefix'] . ' ' . $cfg['key']);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        $cfg['header'] . ': ' . $authValue,
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($body !== null) {
        $payload = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }
    $resp = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    $json = null;
    if ($resp !== false && $resp !== '') {
        $decoded = json_decode($resp, true);
        if (json_last_error() === JSON_ERROR_NONE) $json = $decoded;
    }
    return [
        'ok'     => $http >= 200 && $http < 300,
        'status' => $http,
        'error'  => $err ?: null,
        'body'   => $json,
        'raw'    => $resp ?: null,
    ];
}

function fc_test(PDO $pdo): array {
    return fc_request($pdo, 'GET', '/cards/cards/?limit=1');
}

/**
 * Try a single combination (header,prefix) against a path.
 * Used by auto-detect; does NOT touch settings.
 */
function fc_request_custom(PDO $pdo, string $header, string $prefix, string $path): array {
    $cfg = fc_config($pdo);
    if ($cfg['key'] === '') {
        return ['ok' => false, 'status' => 0, 'error' => 'API key غير مضبوط', 'body' => null, 'raw' => null, 'tried' => "$header: $prefix"];
    }
    $url = $cfg['base'] . $path;
    $val = $prefix === '' ? $cfg['key'] : trim($prefix . ' ' . $cfg['key']);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        $header . ': ' . $val,
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    $json = null;
    if ($resp !== false && $resp !== '') {
        $d = json_decode($resp, true);
        if (json_last_error() === JSON_ERROR_NONE) $json = $d;
    }
    return [
        'ok'     => $http >= 200 && $http < 300,
        'status' => $http,
        'error'  => $err ?: null,
        'body'   => $json,
        'raw'    => $resp ?: null,
        'tried'  => $header . ': ' . ($prefix !== '' ? $prefix . ' &lt;key&gt;' : '&lt;key&gt;'),
    ];
}

/**
 * Try common auth schemes for FlexCard / DRF backends.
 * Returns array of attempts; each item: ['header','prefix','status','ok','snippet'].
 */
function fc_auto_detect(PDO $pdo, string $path = '/cards/cards/?limit=1'): array {
    $combos = [
        ['Authorization', 'Api-Key'],
        ['Authorization', 'Bearer'],
        ['Authorization', 'Token'],
        ['Authorization', ''],
        ['X-Api-Key',     ''],
        ['Api-Key',       ''],
    ];
    $out = [];
    foreach ($combos as [$h, $p]) {
        $r = fc_request_custom($pdo, $h, $p, $path);
        $snippet = is_string($r['raw']) ? mb_substr($r['raw'], 0, 160) : '';
        $out[] = [
            'header'   => $h,
            'prefix'   => $p,
            'status'   => $r['status'],
            'ok'       => $r['ok'],
            'snippet'  => $snippet,
        ];
        if ($r['ok']) break;
    }
    return $out;
}
function fc_list_services(PDO $pdo, int $limit = 100): array {
    return fc_request($pdo, 'GET', '/finance/services/?limit=' . $limit);
}
function fc_list_wallets(PDO $pdo): array {
    return fc_request($pdo, 'GET', '/coins/wallets/?limit=100');
}
function fc_get_card(PDO $pdo, $id): array {
    return fc_request($pdo, 'GET', '/cards/cards/' . urlencode((string)$id) . '/');
}
function fc_get_card_sensitive(PDO $pdo, $id): array {
    return fc_request($pdo, 'GET', '/cards/cards/' . urlencode((string)$id) . '/sensitive/');
}
function fc_close_card_remote(PDO $pdo, $id): array {
    return fc_request($pdo, 'PATCH', '/cards/cards/' . urlencode((string)$id) . '/close/');
}
function fc_update_card_remote(PDO $pdo, $id, array $body): array {
    return fc_request($pdo, 'PATCH', '/cards/cards/' . urlencode((string)$id) . '/', $body);
}
function fc_create_cards(PDO $pdo, array $payload): array {
    return fc_request($pdo, 'POST', '/cards/cards/create/', $payload);
}
function fc_list_otp(PDO $pdo, array $params = []): array {
    $q = $params ? '?' . http_build_query($params) : '?limit=100';
    return fc_request($pdo, 'GET', '/cards/otp/' . $q);
}

/**
 * Try to extract a single created card id and details from the create response.
 */
function fc_extract_first_card($body): ?array {
    if (!is_array($body)) return null;
    $candidates = [];
    if (isset($body['results']) && is_array($body['results'])) $candidates = $body['results'];
    elseif (isset($body['items']) && is_array($body['items']))  $candidates = $body['items'];
    elseif (isset($body['cards']) && is_array($body['cards']))  $candidates = $body['cards'];
    elseif (isset($body['successful']) && is_array($body['successful'])) $candidates = $body['successful'];
    elseif (isset($body[0])) $candidates = $body;
    elseif (isset($body['id'])) $candidates = [$body];

    foreach ($candidates as $c) {
        if (is_array($c) && isset($c['id'])) return $c;
        if (is_array($c) && isset($c['card']) && is_array($c['card']) && isset($c['card']['id'])) return $c['card'];
    }
    return null;
}

/**
 * Pull OTPs from FlexCard since last sync and insert them as notifications
 * under the matching local card (matched via fc_id).
 *
 * Returns ['ok'=>bool,'inserted'=>int,'last_id'=>?int,'error'=>?string,'raw_status'=>?int]
 */
function fc_sync_otps(PDO $pdo): array {
    $resp = fc_list_otp($pdo, ['limit' => 200, 'ordering' => '-id']);
    if (!$resp['ok']) {
        return ['ok' => false, 'inserted' => 0, 'last_id' => null, 'error' => $resp['error'] ?: ('HTTP ' . $resp['status']), 'raw_status' => $resp['status']];
    }
    $body = $resp['body'] ?? [];
    $items = $body['results'] ?? (is_array($body) ? $body : []);
    $lastSynced = (int)setting_get($pdo, 'flexcard_otp_last_id', 0);
    $maxId = $lastSynced;
    $inserted = 0;
    foreach ($items as $otp) {
        $oid = (int)($otp['id'] ?? 0);
        if ($oid <= 0 || $oid <= $lastSynced) continue;
        $code = (string)($otp['otp_code'] ?? $otp['code'] ?? '');
        $merchant = (string)($otp['merchant_name'] ?? $otp['merchant'] ?? '');
        $cardFcId = $otp['card'] ?? ($otp['card_id'] ?? null);
        if ($cardFcId === null) continue;
        $st = $pdo->prepare('SELECT id, user_id FROM cards WHERE fc_id = ?');
        $st->execute([(string)$cardFcId]);
        $card = $st->fetch();
        if (!$card) continue;
        $title = $merchant !== '' ? ('رمز تحقق ' . $merchant) : 'رمز تحقق 3DS';
        $msg = trim('رمز التحقق: ' . $code . ($merchant ? "\nالتاجر: $merchant" : ''));
        notify($pdo, (int)$card['user_id'], (int)$card['id'], $title, $msg, 'otp');
        $inserted++;
        if ($oid > $maxId) $maxId = $oid;
    }
    if ($maxId > $lastSynced) setting_set($pdo, 'flexcard_otp_last_id', $maxId);
    return ['ok' => true, 'inserted' => $inserted, 'last_id' => $maxId, 'error' => null, 'raw_status' => $resp['status']];
}
