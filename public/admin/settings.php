<?php
/** ADMIN: site settings (contact info, WhatsApp, hero numbers). */
require_once __DIR__ . '/../../app/config.php';
require_admin();

// To add a new setting: add a line here, then use get_setting('key') anywhere.
$fields = [
    'whatsapp_number'   => ['Nomor WhatsApp (format 62812xxxxxxx)', 'text'],
    'contact_email'     => ['Email (juga menerima notifikasi pesanan, konsultasi, dan chat baru)', 'email'],
    'address'           => ['Alamat Gudang', 'textarea'],
    'instagram'         => ['Link Instagram', 'text'],
    'chat_enabled'      => ['Live chat di website', 'select', ['1' => 'Aktif', '0' => 'Nonaktif']],
    'chat_welcome'      => ['Pesan sambutan live chat', 'textarea'],
    // The 3 big numbers under the hero title (value + label). Use only numbers that are true!
    'stat_products'        => ['Hero angka 1 (contoh: 100%)', 'text'],
    'stat_products_label'  => ['Hero label 1 (contoh: PRODUK IMPOR)', 'text'],
    'stat_gyms'            => ['Hero angka 2 (contoh: GRATIS)', 'text'],
    'stat_gyms_label'      => ['Hero label 2 (contoh: KONSULTASI LAYOUT)', 'text'],
    'stat_provinces'       => ['Hero angka 3 (contoh: 34)', 'text'],
    'stat_provinces_label' => ['Hero label 3 (contoh: PROVINSI JANGKAUAN KIRIM)', 'text'],
];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $clean = [];
    foreach ($fields as $key => $f) {
        $v = input_str($_POST, $key);
        if (isset($f[2])) { if (isset($f[2][$v])) $clean[$key] = $v; continue; }   // select fields: ignore values that aren't an option
        $max = $key === 'chat_welcome' || $key === 'address' ? 500 : 190;
        if (text_len($v) > $max) { $errors[] = $f[0] . ': maksimal ' . $max . ' karakter.'; continue; }
        if ($key === 'whatsapp_number' && $v !== '') {
            $digits = preg_replace('/\D/', '', $v);
            if (str_starts_with($digits, '0')) $digits = '62' . substr($digits, 1);       // 0812... -> 62812...
            if (strlen($digits) < 9 || strlen($digits) > 15) { $errors[] = 'Nomor WhatsApp tidak valid (contoh: 6281234567890).'; continue; }
            $v = $digits;
        }
        if ($key === 'contact_email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Format email tidak valid.'; continue; }
        if ($key === 'instagram' && $v !== '' && !preg_match('#^https?://#i', $v)) {
            if (preg_match('/^@?([A-Za-z0-9._]{1,30})$/', $v, $m)) $v = 'https://instagram.com/' . $m[1];   // "@beesportfit" is fine too
            else { $errors[] = 'Link Instagram harus diawali https:// (atau tulis @username).'; continue; }
        }
        $clean[$key] = $v;
    }
    if (!$errors) {
        foreach ($clean as $k => $v) set_setting($k, $v);
        flash_set('success', 'Pengaturan disimpan.');
        redirect('admin/settings.php');
    }
}
$page_title = 'Pengaturan'; $active = 'settings';
include APP_VIEWS . '/admin_header.php';
?>
<div class="admin-top"><h1>PENGATURAN</h1></div>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="panel" style="max-width:720px"><?= csrf_field() ?>
  <?php foreach ($fields as $key => $f): [$label, $type] = $f; ?>
    <div class="form-group"><label><?= e($label) ?></label>
      <?php if ($type === 'select'): ?><select name="<?= $key ?>"><?php foreach ($f[2] as $ov => $ol): ?><option value="<?= e($ov) ?>" <?= get_setting($key, '1') === (string)$ov ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select>
      <?php elseif ($type === 'textarea'): ?><textarea name="<?= $key ?>" rows="3"><?= e(get_setting($key)) ?></textarea>
      <?php else: ?><input type="<?= $type ?>" name="<?= $key ?>" value="<?= e(is_string($_POST[$key] ?? null) ? $_POST[$key] : get_setting($key)) ?>"><?php endif; ?></div>
  <?php endforeach; ?>
  <button class="btn btn-primary" type="submit">SIMPAN PENGATURAN</button>
</form>
<?php include APP_VIEWS . '/admin_footer.php'; ?>
