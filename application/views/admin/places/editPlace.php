<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php /** @var object $place */ /** @var array $categories_list */ /** @var string $upload_error */ ?>
<div class="container add-user">
    <h2>Chỉnh sửa địa điểm</h2>
    <hr>
    <?php echo form_open_multipart('admin/places/editPlace/' . $place->id); ?>
        <?php $this->load->view('admin/places/_form', ['place' => $place]); ?>
        <div class="text-muted small mb-3">
            Tạo lúc: <?php echo date('d/m/Y H:i', strtotime($place->created_at)); ?>
            <?php if (!empty($place->updated_at)): ?>
                &middot; Cập nhật lần cuối: <?php echo date('d/m/Y H:i', strtotime($place->updated_at)); ?>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/places'); ?>" class="btn btn-secondary">Quay lại</a>
        <a href="<?php echo place_url($place); ?>" target="_blank" class="btn btn-outline-primary">Xem trên site</a>
    <?php echo form_close(); ?>
</div>
