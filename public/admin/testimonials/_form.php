<?php /** Testimonial form (shared). Needs $t, $errors. */ ?>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="panel" style="max-width:640px"><?= csrf_field() ?>
  <div class="form-group"><label>Nama *</label><input type="text" name="name" required value="<?= e($t['name'] ?? '') ?>"></div>
  <div class="form-group"><label>Jabatan / Perusahaan</label><input type="text" name="role_title" value="<?= e($t['role_title'] ?? '') ?>"></div>
  <div class="form-group"><label>Isi Testimoni *</label><textarea name="content" rows="4" required><?= e($t['content'] ?? '') ?></textarea></div>
  <div class="form-group"><label>Urutan</label><input type="number" name="sort_order" value="<?= e($t['sort_order'] ?? 0) ?>"></div>
  <label class="check-label"><input type="checkbox" name="is_active" value="1" <?= ($t['is_active'] ?? 1) ? 'checked' : '' ?>> Tampilkan di website</label>
  <div class="mt-2"><button class="btn btn-primary" type="submit">SIMPAN</button> <a class="btn btn-ghost" href="<?= url('admin/testimonials/index.php') ?>">BATAL</a></div>
</form>
