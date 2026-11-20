<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Các ô nhập dùng chung cho form thêm / sửa quốc gia (tên, slug, mã, châu lục, ảnh, nội dung, nổi bật).
?>
<?php
/**
 * Các trường form quốc gia, dùng chung cho add.php và editCountry.php.
 * @var object|null $country          NULL khi thêm mới
 * @var array       $continents_list
 * @var string      $upload_error
 */
$v = function ($field, $default = '') use ($country) {
    return set_value($field, $country ? (string) $country->$field : $default);
};
$featured = ($this->input->method() == 'post')
    ? ($this->input->post('is_featured') === '1')
    : ($country && $country->is_featured == 1);
$image_url = $country ? upload_url('countries', $country->image) : '';
?>
<h5 class="mt-2 mb-3 text-primary">Thông tin chung</h5>
<div class="row">
    <div class="col-md-6 pb-3">
        <label for="name" class="form-label">Tên quốc gia <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control" maxlength="255" value="<?php echo $v('name'); ?>">
        <?php echo form_error('name'); ?>
    </div>
    <div class="col-md-6 pb-3">
        <label for="slug" class="form-label">Slug</label>
        <input type="text" name="slug" id="slug" class="form-control" maxlength="255" value="<?php echo $v('slug'); ?>">
        <small class="text-muted"><?php echo $country ? 'Đổi slug sẽ làm đổi đường dẫn country/&lt;slug&gt;. Để trống sẽ tạo lại từ tên.' : 'Để trống sẽ tự tạo từ tên. Trùng sẽ tự thêm -2, -3...'; ?></small>
        <?php echo form_error('slug'); ?>
    </div>
    <div class="col-md-3 pb-3">
        <label for="code" class="form-label">Mã quốc gia (ISO 2 chữ)</label>
        <input type="text" name="code" id="code" class="form-control text-uppercase" maxlength="2" placeholder="VN" value="<?php echo $v('code'); ?>">
        <?php echo form_error('code'); ?>
    </div>
    <div class="col-md-5 pb-3">
        <label for="continent_id" class="form-label">Châu lục</label>
        <select name="continent_id" id="continent_id" class="form-select">
            <option value="">-- Chưa chọn (không hiện ở trang chủ) --</option>
            <?php foreach ($continents_list as $continent): ?>
                <option value="<?php echo $continent->id; ?>" <?php echo set_select('continent_id', (string) $continent->id, $country && $country->continent_id == $continent->id); ?>>
                    <?php echo html_escape($continent->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php echo form_error('continent_id'); ?>
    </div>
    <div class="col-md-4 pb-3">
        <div class="form-check mt-md-4 pt-md-2">
            <input type="checkbox" class="form-check-input" name="is_featured" id="is_featured" value="1" <?php echo $featured ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_featured">Quốc gia nổi bật (ưu tiên ở trang chủ)</label>
        </div>
    </div>
</div>

<h5 class="mt-2 mb-3 text-primary">Hình ảnh</h5>
<div class="row">
    <div class="col-md-6 pb-3">
        <label for="image" class="form-label">Ảnh</label>
        <input type="file" name="image" id="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <small class="text-muted d-block"><?php echo $country ? 'Không chọn file mới thì giữ ảnh cũ. ' : ''; ?>JPG, PNG, WEBP, tối đa 2MB. Chưa có ảnh thì site dùng ảnh thành phố đầu tiên.</small>
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

<?php if ($country && (!empty($country->crawler_href) || !empty($country->crawler_credit))): ?>
    <h5 class="mt-2 mb-3 text-primary">Nguồn dữ liệu</h5>
    <div class="alert alert-info">
        <div><strong>Link gốc:</strong> <a href="<?php echo html_escape($country->crawler_href); ?>" target="_blank" rel="noopener noreferrer"><?php echo html_escape($country->crawler_href); ?></a></div>
        <div><strong>Ghi nguồn (hiện trên site):</strong> <?php echo html_escape((string) $country->crawler_credit); ?></div>
        <small>Crawl lại cùng link sẽ cập nhật châu lục, ảnh, nội dung; giữ nguyên tên, slug, mã và trạng thái nổi bật.</small>
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
<?php if (!$country): ?>
// Tự điền slug theo tên khi người dùng chưa tự sửa ô slug
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
