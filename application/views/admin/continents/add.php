<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang thêm châu lục - Continents::add(). Ô nhập nằm trong _form.php.
?>
<?php /** @var string $upload_error */ ?>
<div class="container add-user">
    <h2>Thêm mới châu lục</h2>
    <hr>
    <?php // Form có upload ảnh (multipart) - gửi POST về chính trang này, controller kiểm tra rồi lưu ?>
    <?php echo form_open_multipart('admin/continents/add'); ?>
        <?php $this->load->view('admin/continents/_form', ['continent' => NULL]); ?>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/continents'); ?>" class="btn btn-secondary">Quay lại</a>
    <?php echo form_close(); ?>
</div>
