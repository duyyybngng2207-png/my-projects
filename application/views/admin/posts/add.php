<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang thêm bài viết - Posts::add(). Ô nhập nằm trong _form.php.
?>
<?php /** @var string $upload_error */ ?>
<div class="container add-user">
    <h2>Thêm mới bài viết</h2>
    <hr>
    <?php // Form có upload ảnh (multipart) - gửi POST về chính trang này, controller kiểm tra rồi lưu ?>
    <?php echo form_open_multipart('admin/posts/add'); ?>
        <?php $this->load->view('admin/posts/_form', ['post' => NULL]); ?>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/posts'); ?>" class="btn btn-secondary">Quay lại</a>
    <?php echo form_close(); ?>
</div>
