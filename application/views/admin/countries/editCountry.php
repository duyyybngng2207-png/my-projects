<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang sửa quốc gia - Countries::editCountry($id). Biến: $country, $continents_list, $upload_error.
?>
<?php /** @var object $country */ /** @var string $upload_error */ ?>
<div class="container add-user">
    <h2>Chỉnh sửa quốc gia</h2>
    <hr>
    <?php // Form có upload ảnh (multipart) - gửi POST về chính trang này, controller kiểm tra rồi lưu ?>
    <?php echo form_open_multipart('admin/countries/editCountry/' . $country->id); ?>
        <?php $this->load->view('admin/countries/_form', ['country' => $country]); ?>
        <div class="text-muted small mb-3">
            Tạo lúc: <?php echo date('d/m/Y H:i', strtotime($country->created_at)); ?>
            <?php if (!empty($country->updated_at)): ?>
                &middot; Cập nhật lần cuối: <?php echo date('d/m/Y H:i', strtotime($country->updated_at)); ?>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/countries'); ?>" class="btn btn-secondary">Quay lại</a>
        <a href="<?php echo country_url($country); ?>" target="_blank" class="btn btn-outline-primary">Xem trên site</a>
    <?php echo form_close(); ?>
</div>
