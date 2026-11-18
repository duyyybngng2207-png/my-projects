<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php /** @var object $category */ ?>
<div class="container add-user">
    <h2>Chỉnh sửa danh mục</h2>
    <hr>
    <?php echo form_open('admin/categories/editCategory/' . $category->id); ?>
        <?php $this->load->view('admin/categories/_form', ['category' => $category]); ?>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/categories'); ?>" class="btn btn-secondary">Quay lại</a>
        <a href="<?php echo category_url($category); ?>" target="_blank" class="btn btn-outline-primary">Xem trên site</a>
    <?php echo form_close(); ?>
</div>
