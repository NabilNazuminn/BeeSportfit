<?php /** Category form (shared). Needs $c (values), $errors, $editing. */ ?>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="panel" style="max-width:640px">
  <?= csrf_field() ?>
  <div class="form-group"><label>Nama Kategori *</label><input type="text" name="name" required maxlength="100" value="<?= e(is_string($c['name'] ?? null) ? $c['name'] : '') ?>"></div>
  <div class="form-group"><label>Deskripsi Singkat</label><input type="text" name="description" maxlength="255" value="<?= e($c['description'] ?? '') ?>"></div>
  <div class="form-group"><label>Urutan Tampil</label><input type="number" name="sort_order" min="-99999" max="99999" value="<?= e($c['sort_order'] ?? 0) ?>"></div>
  <label class="check-label"><input type="checkbox" name="is_active" value="1" <?= ($c['is_active'] ?? 1) ? 'checked' : '' ?>> Aktif</label>
  <div class="mt-2"><button class="btn btn-primary" type="submit">SIMPAN</button> <a class="btn btn-ghost" href="<?= url('admin/categories/index.php') ?>">BATAL</a></div>
</form>
