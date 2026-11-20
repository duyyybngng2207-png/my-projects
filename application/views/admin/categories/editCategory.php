<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang sửa danh mục - Categories::editCategory($id). Biến: $category (dữ liệu cũ để điền sẵn).
?>
<?php /** @var object $category */ ?>
<div class="container add-user">
    <h2>Chỉnh sửa danh mục</h2>
    <hr>
    <?php echo form_open('admin/categories/editCategory/' . $category->id); ?>
        <?php $this->load->view('admin/categories/_form', ['category' => $category]); ?>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/categories'); ?>" class="btn btn-secondary">Quay lại</a>
    <?php echo form_close(); ?>
</div>
