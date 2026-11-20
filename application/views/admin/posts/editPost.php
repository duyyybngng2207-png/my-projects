<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang sửa bài viết - Posts::editPost($id). Biến: $post, $upload_error.
?>
<?php /** @var object $post */ /** @var string $upload_error */ ?>
<div class="container add-user">
    <h2>Chỉnh sửa bài viết</h2>
    <hr>
    <?php // Form có upload ảnh (multipart) - gửi POST về chính trang này, controller kiểm tra rồi lưu ?>
    <?php echo form_open_multipart('admin/posts/editPost/' . $post->id); ?>
        <?php $this->load->view('admin/posts/_form', ['post' => $post]); ?>
        <div class="text-muted small mb-3">
            Tạo lúc: <?php echo date('d/m/Y H:i', strtotime($post->created_at)); ?>
            <?php if (!empty($post->updated_at)): ?>
                &middot; Cập nhật lần cuối: <?php echo date('d/m/Y H:i', strtotime($post->updated_at)); ?>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/posts'); ?>" class="btn btn-secondary">Quay lại</a>
        <?php if ($post->is_published): ?>
            <a href="<?php echo post_url($post); ?>" target="_blank" class="btn btn-outline-primary">Xem trên site</a>
        <?php endif; ?>
    <?php echo form_close(); ?>
</div>
