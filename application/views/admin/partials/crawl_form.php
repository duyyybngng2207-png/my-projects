<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Khung "Crawl dữ liệu từ KAYAK" đặt ở đầu các trang danh sách admin (khách sạn, địa điểm, quốc gia, bài viết...).
// Form POST tới crawler (vd crawler/brand/place) -> Crawler_Controller::respond() đặt flash rồi quay lại trang này.
// Biến nhận vào: $action, $label, $placeholder, $hint, $select (tuỳ chọn).
?>
<?php
/**
 * Khung crawl dữ liệu đặt ở đầu các trang danh sách admin.
 *
 * @var string $action       URL crawler (vd 'crawler/brand')
 * @var string $label        Tiêu đề khung
 * @var string $placeholder  Link mẫu
 * @var string $hint         Ghi chú nguồn / giấy phép
 * @var array  $select       (tuỳ chọn) ['name' => ..., 'label' => ..., 'empty' => ..., 'options' => [id => text]]
 */
$select = isset($select) ? $select : NULL;
?>
<div class="card card-outline card-info mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="bi bi-cloud-download"></i> <?php echo html_escape($label); ?></h3>
    </div>
    <div class="card-body">
        <?php echo form_open($action, ['class' => 'row g-2 align-items-end crawl_form']); ?>
            <div class="<?php echo $select ? 'col-md-7' : 'col-md-10'; ?>">
                <label class="form-label" for="crawl_url_<?php echo md5($action); ?>">Link bài nguồn</label>
                <input type="url" name="url" id="crawl_url_<?php echo md5($action); ?>" class="form-control" required
                       placeholder="<?php echo html_escape($placeholder); ?>">
            </div>
            <?php if ($select): ?>
                <div class="col-md-3">
                    <label class="form-label" for="crawl_sel_<?php echo md5($action); ?>"><?php echo html_escape($select['label']); ?></label>
                    <select name="<?php echo html_escape($select['name']); ?>" id="crawl_sel_<?php echo md5($action); ?>" class="form-select"
                            <?php echo !empty($select['required']) ? 'required' : ''; ?>>
                        <option value=""><?php echo html_escape($select['empty']); ?></option>
                        <?php foreach ($select['options'] as $id => $text): ?>
                            <option value="<?php echo (int) $id; ?>"><?php echo html_escape($text); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-info text-white">
                    <span class="crawl_idle"><i class="bi bi-download"></i> Crawl</span>
                    <span class="crawl_busy d-none"><span class="spinner-border spinner-border-sm"></span> Đang lấy...</span>
                </button>
            </div>
            <div class="col-12">
                <small class="text-muted"><?php echo $hint; ?></small>
            </div>
        <?php echo form_close(); ?>
    </div>
</div>
<script>
// Chặn bấm nhiều lần trong lúc đang crawl (mỗi lần crawl mất vài giây)
document.querySelectorAll('.crawl_form').forEach(function (form) {
    form.addEventListener('submit', function () {
        var btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.querySelector('.crawl_idle').classList.add('d-none');
        btn.querySelector('.crawl_busy').classList.remove('d-none');
    });
});
</script>
