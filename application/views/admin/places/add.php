<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php /** @var array $categories_list */ /** @var string $upload_error */ ?>
<div class="container add-user">
    <h2>Thêm mới địa điểm</h2>
    <hr>
    <?php echo form_open_multipart('admin/places/add'); ?>
        <?php $this->load->view('admin/places/_form', ['place' => NULL]); ?>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/places'); ?>" class="btn btn-secondary">Quay lại</a>
    <?php echo form_close(); ?>
</div>
