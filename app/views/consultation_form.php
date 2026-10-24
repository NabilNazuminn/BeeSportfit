<?php
/** Consultation form. $return = 'home' or 'page' decides where the visitor lands after sending. */
$return = $return ?? 'page';
?>
<form method="post" action="<?= url('consultation.php') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="return" value="<?= e($return) ?>">
  <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
  <div class="form-group">
    <label for="c-name">Nama Lengkap</label>
    <input type="text" id="c-name" name="name" placeholder="Contoh: Budi Santoso" required maxlength="100" value="<?= old('name') ?>">
  </div>
  <div class="form-row">
    <div class="form-group">
      <label for="c-phone">Nomor WhatsApp</label>
      <input type="tel" id="c-phone" name="phone" placeholder="0812XXXXXXXX" required maxlength="30" value="<?= old('phone') ?>">
    </div>
    <div class="form-group">
      <label for="c-type">Jenis Proyek</label>
      <select id="c-type" name="gym_type">
        <?php foreach (project_types() as $val => $label): ?>
          <option value="<?= e($val) ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="form-row">
    <div class="form-group">
      <label for="c-size">Ukuran Ruangan (opsional)</label>
      <input type="text" id="c-size" name="room_size" placeholder="Contoh: 10 x 15 m" maxlength="50">
    </div>
    <div class="form-group">
      <label for="c-budget">Estimasi Budget (opsional)</label>
      <input type="text" id="c-budget" name="budget" placeholder="Contoh: 500 juta" maxlength="50">
    </div>
  </div>
  <div class="form-group">
    <label for="c-msg">Pesan / Rincian Alat</label>
    <textarea id="c-msg" name="message" rows="3" maxlength="3000" placeholder="Tuliskan kebutuhan tipe alat atau lokasi pengiriman..."></textarea>
  </div>
  <button class="btn btn-primary btn-block" type="submit">KIRIM PESAN SEKARANG</button>
</form>
