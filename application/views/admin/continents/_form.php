<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Các ô nhập dùng chung cho form thêm / sửa châu lục (tên, slug, thứ tự, ảnh, nội dung).
?>
<?php
/**
 * Các trường form châu lục, dùng chung cho add.php và editContinent.php.
 * @var object|null $continent     NULL khi thêm mới
 * @var string      $upload_error
 */
$v = function ($field, $default = '') use ($continent) {
    return set_value($field, $continent ? (string) $continent->$field : $default);
};
$image_url = $continent ? upload_url('continents', $continent->image) : '';
?>
<h5 class="mt-2 mb-3 text-primary">Thông tin chung</h5>
<div class="row">
    <div class="col-md-5 pb-3">
        <label for="name" class="form-label">Tên châu lục <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control" maxlength="100" value="<?php echo $v('name'); ?>">
        <?php echo form_error('name'); ?>
    </div>
    <div class="col-md-5 pb-3">
        <label for="slug" class="form-label">Slug</label>
        <input type="text" name="slug" id="slug" class="form-control" maxlength="100" value="<?php echo $v('slug'); ?>">
        <small class="text-muted"><?php echo $continent ? 'Đổi slug sẽ làm đổi đường dẫn continent/&lt;slug&gt;. Để trống sẽ tạo lại từ tên.' : 'Để trống sẽ tự tạo từ tên.'; ?></small>
        <?php echo form_error('slug'); ?>
    </div>
    <div class="col-md-2 pb-3">
        <label for="sort_order" class="form-label">Thứ tự</label>
        <input type="number" name="sort_order" id="sort_order" class="form-control" value="<?php echo $v('sort_order', '0'); ?>">
        <small class="text-muted">Nhỏ hiện trước</small>
        <?php echo form_error('sort_order'); ?>
    </div>
</div>

<h5 class="mt-2 mb-3 text-primary">Hình ảnh</h5>
<div class="row">
    <div class="col-md-6 pb-3">
        <label for="image" class="form-label">Ảnh</label>
        <input type="file" name="image" id="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <small class="text-muted d-block"><?php echo $continent ? 'Không chọn file mới thì giữ ảnh cũ. ' : ''; ?>JPG, PNG, WEBP, tối đa 2MB. Chưa có ảnh thì site dùng ảnh quốc gia / thành phố.</small>
        <?php echo $upload_error; ?>
        <img id="previewImage" src="<?php echo $image_url; ?>" alt="" class="rounded border mt-2"
             style="width:160px;<?php echo $image_url === '' ? 'display:none;' : ''; ?>">
    </div>
</div>

<h5 class="mt-2 mb-3 text-primary">Nội dung</h5>
<div class="row">
    <div class="col-12 pb-3">
        <label for="content" class="form-label">Giới thiệu</label>
        <textarea name="content" id="content" rows="8" class="form-control"><?php echo $v('content'); ?></textarea>
        <?php echo form_error('content'); ?>
    </div>
</div>

<?php if ($continent && (!empty($continent->crawler_href) || !empty($continent->crawler_credit))): ?>
    <h5 class="mt-2 mb-3 text-primary">Nguồn dữ liệu</h5>
    <div class="alert alert-info">
        <div><strong>Link gốc:</strong> <a href="<?php echo html_escape($continent->crawler_href); ?>" target="_blank" rel="noopener noreferrer"><?php echo html_escape($continent->crawler_href); ?></a></div>
        <div><strong>Ghi nguồn (hiện trên site):</strong> <?php echo html_escape((string) $continent->crawler_credit); ?></div>
        <small>Crawl lại cùng link sẽ cập nhật ảnh, nội dung; giữ nguyên tên, slug và thứ tự.</small>
    </div>
<?php endif; ?>

<script>
document.getElementById('image').addEventListener('change', function () {
    var img = document.getElementById('previewImage'), file = this.files[0];
    if (!file) { return; }
    var reader = new FileReader();
    reader.onload = function (e) { img.src = e.target.result; img.style.display = 'block'; };
    reader.readAsDataURL(file);
});
<?php if (!$continent): ?>
(function () {
    var name = document.getElementById('name'), slug = document.getElementById('slug'), touched = slug.value !== '';
    function createSlug(s) {
        return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd')
            .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-+/g, '-');
    }
    slug.addEventListener('input', function () { touched = slug.value !== ''; });
    name.addEventListener('input', function () { if (!touched) { slug.value = createSlug(name.value); } });
})();
<?php endif; ?>
</script>
