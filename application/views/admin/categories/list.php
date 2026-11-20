<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Danh sách danh mục - Categories::index(). Biến: $categories_list, $pagination_links, $offset.
?>
<?php /** @var array $categories_list */ /** @var string $pagination_links */ /** @var int $offset */ ?>

<h2>Danh sách danh mục</h2>
<hr>

<a href="<?php echo base_url('admin/categories/add'); ?>" class="btn btn-success mb-3">Thêm mới danh mục</a>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
    <thead>
        <tr>
            <th>STT</th>
            <th class="text-start">Tên danh mục / Slug</th>
            <th class="text-start">Mô tả</th>
            <th>Số địa điểm</th>
            <th>Giá khách sạn từ</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($categories_list)): ?>
            <tr><td colspan="6" class="text-muted py-4">Chưa có danh mục nào.</td></tr>
        <?php endif; ?>

        <?php foreach ($categories_list as $key => $item): ?>
            <tr>
                <th><?php echo $offset + $key + 1; ?></th>
                <td class="text-start">
                    <strong><?php echo html_escape($item->title); ?></strong>
                    <br><small class="text-muted"><?php echo html_escape((string) $item->slug); ?></small>
                </td>
                <td class="text-start small"><?php echo html_escape(mb_strimwidth((string) $item->description, 0, 160, '...', 'UTF-8')); ?></td>
                <td><span class="badge bg-primary"><?php echo (int) $item->total_places; ?></span></td>
                <td><?php echo (int) $item->min_price > 0 ? '$' . number_format((float) $item->min_price) : '-'; ?></td>
                <td class="text-nowrap">
                    <a href="<?php echo base_url('admin/categories/editCategory/' . $item->id); ?>" class="btn btn-warning btn-sm">Sửa</a>
                    <?= form_open('admin/categories/deleteCategory/' . $item->id, [
                        'class'    => 'd-inline',
                        'onsubmit' => "return confirm('Xóa danh mục này?');"
                    ]) ?>
                    <button type="submit" class="btn btn-danger btn-sm" <?php echo (int) $item->total_places > 0 ? 'disabled title="Danh mục đang có địa điểm"' : ''; ?>>Xóa</button>
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
