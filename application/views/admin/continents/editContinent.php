<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang sửa châu lục - Continents::editContinent($id). Biến: $continent, $upload_error.
?>
<?php /** @var object $continent */ /** @var string $upload_error */ ?>
<div class="container add-user">
    <h2>Chỉnh sửa châu lục</h2>
    <hr>
    <?php // Form có upload ảnh (multipart) - gửi POST về chính trang này, controller kiểm tra rồi lưu ?>
    <?php echo form_open_multipart('admin/continents/editContinent/' . $continent->id); ?>
        <?php $this->load->view('admin/continents/_form', ['continent' => $continent]); ?>
        <div class="text-muted small mb-3">
            Tạo lúc: <?php echo date('d/m/Y H:i', strtotime($continent->created_at)); ?>
            <?php if (!empty($continent->updated_at)): ?>
                &middot; Cập nhật lần cuối: <?php echo date('d/m/Y H:i', strtotime($continent->updated_at)); ?>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/continents'); ?>" class="btn btn-secondary">Quay lại</a>
        <a href="<?php echo continent_url($continent); ?>" target="_blank" class="btn btn-outline-primary">Xem trên site</a>
    <?php echo form_close(); ?>
</div>
