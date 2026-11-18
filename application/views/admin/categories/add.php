<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container add-user">
    <h2>Thêm mới danh mục</h2>
    <hr>
    <?php echo form_open('admin/categories/add'); ?>
        <?php $this->load->view('admin/categories/_form', ['category' => NULL]); ?>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/categories'); ?>" class="btn btn-secondary">Quay lại</a>
    <?php echo form_close(); ?>
</div>
