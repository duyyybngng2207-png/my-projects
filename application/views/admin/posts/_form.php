<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/**
 * Các trường form bài viết, dùng chung cho add.php và editPost.php.
 * @var object|null $post          NULL khi thêm mới
 * @var string      $upload_error
 */
$v = function ($field) use ($post) {
    return set_value($field, $post ? (string) $post->$field : '');
};
// Thêm mới: mặc định "đăng ngay"; gửi lỗi thì giữ lựa chọn vừa chọn
$published = ($this->input->method() == 'post')
    ? ($this->input->post('is_published') === '1')
    : ($post ? $post->is_published == 1 : TRUE);
$image_url = $post ? upload_url('posts', $post->image) : '';
?>
<h5 class="mt-2 mb-3 text-primary">Thông tin chung</h5>
<div class="row">
    <div class="col-md-7 pb-3">
        <label for="title" class="form-label">Tiêu đề <span class="text-danger">*</span></label>
        <input type="text" name="title" id="title" class="form-control" maxlength="255" value="<?php echo $v('title'); ?>">
        <?php echo form_error('title'); ?>
    </div>
    <div class="col-md-5 pb-3">
        <label for="slug" class="form-label">Slug</label>
        <input type="text" name="slug" id="slug" class="form-control" maxlength="255" value="<?php echo $v('slug'); ?>">
        <small class="text-muted"><?php echo $post ? 'Đổi slug sẽ làm đổi đường dẫn bài viết.' : 'Để trống sẽ tự tạo từ tiêu đề.'; ?></small>
        <?php echo form_error('slug'); ?>
    </div>
</div>

<h5 class="mt-2 mb-3 text-primary">Nội dung</h5>
<div class="row">
    <div class="col-12 pb-3">
        <label for="content" class="form-label">Nội dung <span class="text-danger">*</span></label>
        <textarea name="content" id="content" rows="14" class="form-control"><?php echo $v('content'); ?></textarea>
        <small class="text-muted">Xuống dòng sẽ được giữ nguyên khi hiển thị.</small>
        <?php echo form_error('content'); ?>
    </div>
</div>

<h5 class="mt-2 mb-3 text-primary">Ảnh &amp; trạng thái</h5>
<div class="row">
    <div class="col-md-6 pb-3">
        <label for="image" class="form-label">Ảnh đại diện</label>
        <input type="file" name="image" id="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <small class="text-muted d-block"><?php echo $post ? 'Không chọn file mới thì giữ ảnh cũ. ' : ''; ?>JPG, PNG, WEBP, tối đa 2MB.</small>
        <?php echo $upload_error; ?>
        <img id="previewImage" src="<?php echo $image_url; ?>" alt="" class="rounded border mt-2"
             style="width:160px;<?php echo $image_url === '' ? 'display:none;' : ''; ?>">
    </div>
    <div class="col-md-6 pb-3">
        <div class="form-check mt-md-4 pt-md-2">
            <input type="checkbox" class="form-check-input" name="is_published" id="is_published" value="1" <?php echo $published ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_published">Đăng ngay (bỏ tích để lưu nháp)</label>
        </div>
    </div>
</div>

<?php if ($post && (!empty($post->crawler_href) || !empty($post->crawler_credit))): ?>
    <h5 class="mt-2 mb-3 text-primary">Nguồn dữ liệu</h5>
    <div class="alert alert-info">
        <div><strong>Link gốc:</strong> <a href="<?php echo html_escape($post->crawler_href); ?>" target="_blank" rel="noopener noreferrer"><?php echo html_escape($post->crawler_href); ?></a></div>
        <div><strong>Ghi nguồn (hiện trên site):</strong> <?php echo html_escape((string) $post->crawler_credit); ?></div>
        <small>Nội dung theo giấy phép CC BY-SA: được biên tập, nhưng phải giữ ghi nguồn. Crawl lại cùng link sẽ ghi đè nội dung đã sửa.</small>
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
<?php if (!$post): ?>
(function () {
    var title = document.getElementById('title'), slug = document.getElementById('slug'), touched = slug.value !== '';
    function createSlug(s) {
        return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd')
            .replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-+/g, '-');
    }
    slug.addEventListener('input', function () { touched = slug.value !== ''; });
    title.addEventListener('input', function () { if (!touched) { slug.value = createSlug(title.value); } });
})();
<?php endif; ?>
</script>
