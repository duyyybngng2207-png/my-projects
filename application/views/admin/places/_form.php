<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/**
 * Các trường form địa điểm, dùng chung cho add.php và editPlace.php.
 * @var object|null $place            NULL khi thêm mới
 * @var array       $categories_list
 * @var string      $upload_error
 */
$v = function ($field, $default = '') use ($place) {
    return set_value($field, $place ? (string) $place->$field : $default);
};
$featured = ($this->input->method() == 'post')
    ? ($this->input->post('is_featured') === '1')
    : ($place && $place->is_featured == 1);
$image_url = $place ? upload_url('places', $place->image) : '';
?>
<h5 class="mt-2 mb-3 text-primary">Thông tin chung</h5>
<div class="row">
    <div class="col-md-6 pb-3">
        <label for="name" class="form-label">Tên địa điểm <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name" class="form-control" maxlength="255" value="<?php echo $v('name'); ?>">
        <?php echo form_error('name'); ?>
    </div>
    <div class="col-md-6 pb-3">
        <label for="slug" class="form-label">Slug</label>
        <input type="text" name="slug" id="slug" class="form-control" maxlength="255" value="<?php echo $v('slug'); ?>">
        <small class="text-muted"><?php echo $place ? 'Đổi slug sẽ làm đổi đường dẫn địa điểm. Để trống sẽ tạo lại từ tên.' : 'Để trống sẽ tự tạo từ tên. Trùng sẽ tự thêm -2, -3...'; ?></small>
        <?php echo form_error('slug'); ?>
    </div>
    <div class="col-md-4 pb-3">
        <label for="category_id" class="form-label">Danh mục</label>
        <select name="category_id" id="category_id" class="form-select">
            <option value="">-- Chưa phân loại --</option>
            <?php foreach ($categories_list as $cate): ?>
                <option value="<?php echo $cate->id; ?>" <?php echo set_select('category_id', (string) $cate->id, $place && $place->category_id == $cate->id); ?>>
                    <?php echo html_escape($cate->title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php echo form_error('category_id'); ?>
    </div>
    <div class="col-md-8 pb-3">
        <label for="address" class="form-label">Địa chỉ <span class="text-danger">*</span></label>
        <input type="text" name="address" id="address" class="form-control" maxlength="255" value="<?php echo $v('address'); ?>">
        <?php echo form_error('address'); ?>
    </div>
</div>

<h5 class="mt-2 mb-3 text-primary">Đánh giá</h5>
<div class="row">
    <div class="col-md-3 pb-3">
        <label for="rating" class="form-label">Số sao</label>
        <select name="rating" id="rating" class="form-select">
            <option value="">-- Chưa đánh giá --</option>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo set_select('rating', (string) $i, $place && $place->rating == $i); ?>><?php echo $i; ?> sao</option>
            <?php endfor; ?>
        </select>
        <?php echo form_error('rating'); ?>
    </div>
    <div class="col-md-3 pb-3">
        <div class="form-check mt-md-4 pt-md-2">
            <input type="checkbox" class="form-check-input" name="is_featured" id="is_featured" value="1" <?php echo $featured ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_featured">Địa điểm nổi bật (slider trang chủ)</label>
        </div>
    </div>
</div>

<h5 class="mt-2 mb-3 text-primary">Hình ảnh</h5>
<div class="row">
    <div class="col-md-6 pb-3">
        <label for="image" class="form-label">Ảnh</label>
        <input type="file" name="image" id="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <small class="text-muted d-block"><?php echo $place ? 'Không chọn file mới thì giữ ảnh cũ. ' : ''; ?>JPG, PNG, WEBP, tối đa 2MB.</small>
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

<?php if ($place && (!empty($place->crawler_href) || !empty($place->crawler_credit))): ?>
    <h5 class="mt-2 mb-3 text-primary">Nguồn dữ liệu</h5>
    <div class="alert alert-info">
        <div><strong>Link gốc:</strong> <a href="<?php echo html_escape($place->crawler_href); ?>" target="_blank" rel="noopener noreferrer"><?php echo html_escape($place->crawler_href); ?></a></div>
        <div><strong>Ghi nguồn (hiện trên site):</strong> <?php echo html_escape((string) $place->crawler_credit); ?></div>
        <small>Nội dung theo giấy phép CC BY-SA: được sửa, nhưng phải giữ ghi nguồn. Crawl lại cùng link sẽ cập nhật nội dung/ảnh, giữ nguyên giá.</small>
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
<?php if (!$place): ?>
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
