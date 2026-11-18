<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php /** @var array $posts_list */ /** @var string $pagination_links */ /** @var int $offset */ ?>

<h2>Danh sách bài viết</h2>
<hr>

<a href="<?php echo base_url('admin/posts/add'); ?>" class="btn btn-success mb-3">Thêm mới bài viết</a>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
    <thead>
        <tr>
            <th>STT</th>
            <th>Ảnh</th>
            <th class="text-start">Tiêu đề / Slug</th>
            <th class="text-start">Nội dung</th>
            <th>Trạng thái</th>
            <th>Nguồn</th>
            <th>Ngày tạo</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($posts_list)): ?>
            <tr><td colspan="8" class="text-muted py-4">Chưa có bài viết nào.</td></tr>
        <?php endif; ?>

        <?php foreach ($posts_list as $key => $item): ?>
            <?php $img = upload_url('posts', $item->image); ?>
            <tr>
                <th><?php echo $offset + $key + 1; ?></th>
                <td>
                    <?php if ($img !== ''): ?>
                        <img src="<?php echo $img; ?>" alt="" width="80" class="rounded">
                    <?php else: ?>
                        <span class="text-muted small">Chưa có ảnh</span>
                    <?php endif; ?>
                </td>
                <td class="text-start">
                    <strong><?php echo html_escape($item->title); ?></strong>
                    <br><small class="text-muted"><?php echo html_escape((string) $item->slug); ?></small>
                </td>
                <td class="text-start small"><?php echo html_escape(mb_strimwidth(trim(preg_replace('/\s+/u', ' ', (string) $item->content)), 0, 140, '...', 'UTF-8')); ?></td>
                <td>
                    <?php if ($item->is_published): ?>
                        <span class="badge bg-success">Đã đăng</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Nháp</span>
                    <?php endif; ?>
                </td>
                <td><?php echo admin_source_badge($item->crawler_href ?? NULL); ?></td>
                <td><?php echo date('d/m/Y', strtotime($item->created_at)); ?></td>
                <td class="text-nowrap">
                    <a href="<?php echo base_url('admin/posts/editPost/' . $item->id); ?>" class="btn btn-warning btn-sm">Sửa</a>
                    <?= form_open('admin/posts/deletePost/' . $item->id, [
                        'class'    => 'd-inline',
                        'onsubmit' => "return confirm('Xóa bài viết này?');"
                    ]) ?>
                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                    <?= form_close() ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="mt-3">
    <?php echo $pagination_links; ?>
</div>
