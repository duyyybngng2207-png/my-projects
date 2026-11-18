<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/** @var object|null $category  NULL khi thêm mới */
$v = function ($field) use ($category) {
    return set_value($field, $category ? (string) $category->$field : '');
};
?>
<h5 class="mt-2 mb-3 text-primary">Thông tin danh mục</h5>
<div class="row">
    <div class="col-md-6 pb-3">
        <label for="title" class="form-label">Tên danh mục <span class="text-danger">*</span></label>
        <input type="text" name="title" id="title" class="form-control" maxlength="255" value="<?php echo $v('title'); ?>">
        <small class="text-muted">Được viết hoa tuỳ ý, ví dụ "North of Vietnam".</small>
        <?php echo form_error('title'); ?>
    </div>
    <div class="col-md-6 pb-3">
        <label for="slug" class="form-label">Slug</label>
        <input type="text" name="slug" id="slug" class="form-control" maxlength="255" value="<?php echo $v('slug'); ?>">
        <small class="text-muted">Luôn được lưu chữ thường. <?php echo $category ? 'Đổi slug sẽ làm đổi đường dẫn danh mục.' : 'Để trống sẽ tự tạo từ tên.'; ?></small>
        <?php echo form_error('slug'); ?>
    </div>
    <div class="col-12 pb-3">
        <label for="description" class="form-label">Mô tả</label>
        <textarea name="description" id="description" rows="5" class="form-control"><?php echo $v('description'); ?></textarea>
        <?php echo form_error('description'); ?>
    </div>
</div>
<?php if (!$category): ?>
<script>
// Tự điền slug theo tên khi người dùng chưa tự sửa ô slug
(function () {
    var title = document.getElementById('title'), slug = document.getElementById('slug'), touched = slug.value !== '';
    function createSlug(s) {
        return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd')
            .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-+/g, '-');
    }
    slug.addEventListener('input', function () { touched = slug.value !== ''; });
    title.addEventListener('input', function () { if (!touched) { slug.value = createSlug(title.value); } });
})();
</script>
<?php endif; ?>
