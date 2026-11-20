<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang thêm địa điểm - Places::add(). Ô nhập nằm trong _form.php. Biến: $countries_list, $upload_error.
?>
<?php /** @var array $countries_list */ /** @var string $upload_error */ ?>
<div class="container add-user">
    <h2>Thêm mới địa điểm</h2>
    <hr>
    <?php // Form có upload ảnh (multipart) - gửi POST về chính trang này, controller kiểm tra rồi lưu ?>
    <?php echo form_open_multipart('admin/places/add'); ?>
        <?php $this->load->view('admin/places/_form', ['place' => NULL]); ?>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/places'); ?>" class="btn btn-secondary">Quay lại</a>
    <?php echo form_close(); ?>
</div>
