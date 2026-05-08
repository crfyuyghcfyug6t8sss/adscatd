<?php
$adminActive = 'apps';
$pageTitle = 'طلبات الإصدار — الإدارة';
require __DIR__ . '/_layout.php';
$cur = setting_get($pdo, 'currency', 'USD');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM applications WHERE id = ?');
    $st->execute([$id]);
    $app = $st->fetch();
    if ($app && $app['status'] === 'pending') {
        if ($action === 'reject') {
            $note = trim($_POST['note'] ?? '');
            $pdo->prepare('UPDATE applications SET status="rejected", admin_note=? WHERE id=?')->execute([$note, $id]);
            // refund total to wallet
            wallet_add($pdo, (int)$app['user_id'], (float)$app['total_paid'], 'استرداد طلب بطاقة #' . $id . ' (مرفوض)');
            notify($pdo, (int)$app['user_id'], null, 'تم رفض طلب الإصدار #'.$id, ($note ?: 'تم رفض الطلب.') . ' تم استرداد ' . money($app['total_paid'], $cur) . ' إلى محفظتك.', 'info');
            flash_set('ok','تم رفض الطلب واسترداد الرصيد.');
        } elseif ($action === 'approve' || $action === 'approve_flexcard') {
            $brand = ($_POST['brand'] ?? $app['brand']) === 'mastercard' ? 'mastercard' : 'visa';
            $holder = trim($_POST['holder_name'] ?? $app['full_name']);
            $bal = (float)($_POST['balance'] ?? $app['initial_charge']);
            $cur = setting_get($pdo, 'currency', 'USD');
            $fcId = null; $fcDataJson = null; $issued = false;

            if ($action === 'approve_flexcard') {
                if (!fc_enabled($pdo)) {
                    flash_set('bad','تكامل FlexCard غير مفعّل. فعّله من الإعدادات.');
                    redirect('/admin/applications.php'); return;
                }
                $svcId = $brand === 'mastercard' ? setting_get($pdo,'flexcard_mc_service','') : setting_get($pdo,'flexcard_visa_service','');
                if ($svcId === '' || $svcId === null) {
                    flash_set('bad','يجب ضبط Service ID لـ ' . card_brand_label($brand) . ' من صفحة FlexCard.');
                    redirect('/admin/applications.php'); return;
                }
                $payload = [
                    'items' => [[
                        'service'        => is_numeric($svcId) ? (int)$svcId : $svcId,
                        'amount'         => $bal,
                        'currency'       => $cur,
                        'holder_name'    => $holder,
                        'is_3ds_enabled' => true,
                        'label'          => 'app#' . $id,
                    ]],
                ];
                $resp = fc_create_cards($pdo, $payload);
                if (!$resp['ok']) {
                    $msg = 'فشل إصدار البطاقة عبر FlexCard (HTTP ' . $resp['status'] . '): '
                        . ($resp['error'] ?? '') . ' — ' . substr((string)$resp['raw'], 0, 400);
                    flash_set('bad', $msg);
                    redirect('/admin/applications.php'); return;
                }
                $card = fc_extract_first_card($resp['body']);
                if (!$card || empty($card['id'])) {
                    flash_set('bad','استجابة FlexCard لا تحتوي على معرّف البطاقة. الردّ: ' . substr((string)$resp['raw'], 0, 400));
                    redirect('/admin/applications.php'); return;
                }
                $fcId = (string)$card['id'];
                // Try fetching sensitive details for PAN/expiry/CVV
                $sens = fc_get_card_sensitive($pdo, $fcId);
                $merged = $card;
                if ($sens['ok'] && is_array($sens['body'])) $merged = array_merge($merged, $sens['body']);
                $num = preg_replace('/\D/','', (string)($merged['card_number'] ?? $merged['number'] ?? $merged['pan'] ?? ''));
                $cvv = preg_replace('/\D/','', (string)($merged['cvv'] ?? $merged['cvc'] ?? ''));
                $expM = (string)($merged['expiry_month'] ?? $merged['exp_month'] ?? '');
                $expY = (string)($merged['expiry_year']  ?? $merged['exp_year']  ?? '');
                if ($expM === '' && !empty($merged['expiry'])) {
                    $exp = (string)$merged['expiry'];
                    if (preg_match('#^(\d{2})[/\-](\d{2,4})$#', $exp, $m)) { $expM = $m[1]; $expY = $m[2]; }
                }
                $expM = str_pad($expM, 2, '0', STR_PAD_LEFT);
                $expY = substr(str_pad($expY, 2, '0', STR_PAD_LEFT), -2);
                $exp = ($expM && $expY) ? ($expM . '/' . $expY) : gen_expiry();
                if (strlen($num) < 13) $num = gen_card_number_for($brand);
                if (strlen($cvv) < 3) $cvv = gen_cvv();
                $fcDataJson = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $pdo->prepare('INSERT INTO cards (user_id, application_id, brand, holder_name, card_number, expiry, cvv, balance, fc_id, fc_data, fc_synced_at) VALUES (?,?,?,?,?,?,?,?,?,?,datetime("now"))')
                    ->execute([$app['user_id'], $app['id'], $brand, $holder, $num, $exp, $cvv, $bal, $fcId, $fcDataJson]);
                $cardId = (int)$pdo->lastInsertId();
                $issued = true;
            } else {
                $num = preg_replace('/\D/','', $_POST['card_number'] ?? '');
                $exp = trim($_POST['expiry'] ?? '');
                $cvv = preg_replace('/\D/','', $_POST['cvv'] ?? '');
                if (strlen($num) < 13 || strlen($num) > 19) { $num = gen_card_number_for($brand); }
                if (!preg_match('#^\d{2}/\d{2}$#', $exp)) { $exp = gen_expiry(); }
                if (strlen($cvv) < 3) { $cvv = gen_cvv(); }
                $pdo->prepare('INSERT INTO cards (user_id, application_id, brand, holder_name, card_number, expiry, cvv, balance) VALUES (?,?,?,?,?,?,?,?)')
                    ->execute([$app['user_id'], $app['id'], $brand, $holder, $num, $exp, $cvv, $bal]);
                $cardId = (int)$pdo->lastInsertId();
                $issued = true;
            }

            if ($issued) {
                $pdo->prepare('UPDATE applications SET status="approved", brand=? WHERE id=?')->execute([$brand, $id]);
                txn($pdo, (int)$app['user_id'], $cardId, 'card_issued', $bal, 'إصدار بطاقة ' . card_brand_label($brand) . ($fcId ? ' (FlexCard)' : ' (يدوي)'));
                notify($pdo, (int)$app['user_id'], $cardId, 'تمت الموافقة وإصدار بطاقتك', 'تم إصدار بطاقتك بنجاح. يمكنك مشاهدتها من قسم بطاقاتي.', 'info');
                flash_set('ok', $fcId ? ('تمت الموافقة وإصدار البطاقة عبر FlexCard (id=' . $fcId . ').') : 'تمت الموافقة وإصدار البطاقة يدوياً.');
            }
        }
    }
    redirect('/admin/applications.php');
}

$filter = $_GET['s'] ?? 'pending';
$where = $filter === 'all' ? '' : 'WHERE a.status = ' . $pdo->quote($filter);
$rows = $pdo->query("SELECT a.*, u.phone FROM applications a JOIN users u ON u.id=a.user_id $where ORDER BY a.id DESC")->fetchAll();
?>
<h1><i data-lucide="file-text"></i> طلبات الإصدار</h1>
<div class="filters">
  <a href="?s=pending"  class="chip <?= $filter==='pending'?'on':'' ?>">قيد المراجعة</a>
  <a href="?s=approved" class="chip <?= $filter==='approved'?'on':'' ?>">المقبولة</a>
  <a href="?s=rejected" class="chip <?= $filter==='rejected'?'on':'' ?>">المرفوضة</a>
  <a href="?s=all"      class="chip <?= $filter==='all'?'on':'' ?>">الكل</a>
</div>

<?php if(!$rows): ?><p class="empty">لا توجد طلبات.</p><?php else: ?>
<div class="apps-list">
<?php foreach($rows as $a): [$lbl,$cls]=status_label($a['status']); ?>
  <article class="panel app-row">
    <header class="panel-head">
      <div>
        <h3>طلب #<?= (int)$a['id'] ?> — <?= e($a['full_name']) ?></h3>
        <p class="muted small">
          هاتف: <span dir="ltr"><?= e($a['phone']) ?></span> ·
          النوع: <strong><?= e(card_brand_label($a['brand'] ?? 'visa')) ?></strong> ·
          التاريخ: <?= e($a['created_at']) ?>
        </p>
      </div>
      <span class="badge <?= e($cls) ?>"><?= e($lbl) ?></span>
    </header>
    <div class="grid-3 small-info">
      <div><span class="muted">قيمة الشحن المبدئي</span><strong><?= e(money($a['initial_charge'],$cur)) ?></strong></div>
      <div><span class="muted">رسوم الإصدار</span><strong><?= e(money($a['issuance_fee'],$cur)) ?></strong></div>
      <div><span class="muted">المخصوم من المحفظة</span><strong><?= e(money($a['total_paid'],$cur)) ?></strong></div>
    </div>
    <?php if ($a['status']==='pending'): ?>
    <?php if (fc_enabled($pdo)): ?>
    <details class="approve-form">
      <summary class="btn btn-primary"><i data-lucide="zap"></i> إصدار عبر FlexCard</summary>
      <form method="post" class="form mt-12">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
        <input type="hidden" name="action" value="approve_flexcard">
        <div class="grid-2">
          <label><span>النوع</span>
            <select name="brand">
              <option value="visa" <?= ($a['brand']??'visa')==='visa'?'selected':'' ?>>Visa</option>
              <option value="mastercard" <?= ($a['brand']??'')==='mastercard'?'selected':'' ?>>Mastercard</option>
            </select>
          </label>
          <label><span>اسم حامل البطاقة</span><input name="holder_name" value="<?= e($a['full_name']) ?>"></label>
          <label><span>الرصيد المبدئي</span><input type="number" step="0.01" name="balance" value="<?= e($a['initial_charge']) ?>"></label>
        </div>
        <p class="muted small">سيتم إنشاء البطاقة في FlexCard وجلب بياناتها (رقم/انتهاء/CVV) تلقائياً.</p>
        <button class="btn btn-primary" type="submit"><i data-lucide="zap"></i> إصدار عبر FlexCard</button>
      </form>
    </details>
    <?php endif; ?>
    <details class="approve-form">
      <summary class="btn btn-outline"><i data-lucide="pencil"></i> إصدار يدوي</summary>
      <form method="post" class="form mt-12">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
        <input type="hidden" name="action" value="approve">
        <div class="grid-2">
          <label><span>النوع</span>
            <select name="brand">
              <option value="visa" <?= ($a['brand']??'visa')==='visa'?'selected':'' ?>>Visa</option>
              <option value="mastercard" <?= ($a['brand']??'')==='mastercard'?'selected':'' ?>>Mastercard</option>
            </select>
          </label>
          <label><span>اسم حامل البطاقة</span><input name="holder_name" value="<?= e($a['full_name']) ?>"></label>
          <label><span>رقم البطاقة</span><input name="card_number" placeholder="فارغ = توليد تلقائي" dir="ltr" maxlength="19"></label>
          <label><span>الانتهاء MM/YY</span><input name="expiry" placeholder="فارغ = توليد تلقائي" dir="ltr"></label>
          <label><span>CVV</span><input name="cvv" placeholder="فارغ = توليد تلقائي" dir="ltr"></label>
          <label><span>الرصيد المبدئي</span><input type="number" step="0.01" name="balance" value="<?= e($a['initial_charge']) ?>"></label>
        </div>
        <button class="btn btn-primary" type="submit"><i data-lucide="check"></i> إصدار يدوي</button>
      </form>
    </details>
    <details class="reject-form">
      <summary class="btn btn-outline"><i data-lucide="x"></i> رفض واسترداد الرصيد</summary>
      <form method="post" class="form mt-12">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
        <input type="hidden" name="action" value="reject">
        <label><span>سبب الرفض</span><input name="note" required></label>
        <button class="btn btn-outline" type="submit"><i data-lucide="x"></i> تأكيد الرفض</button>
      </form>
    </details>
    <?php elseif ($a['admin_note']): ?>
      <p class="muted small"><i data-lucide="message-square"></i> ملاحظة: <?= e($a['admin_note']) ?></p>
    <?php endif; ?>
  </article>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>
