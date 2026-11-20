<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Danh sách châu lục - Continents::index() (kèm form crawl châu lục). Biến: $continents_list, $pagination_links, $offset.
?>
<?php
/**
 * @var array  $continents_list
 * @var string $pagination_links
 * @var int    $offset
 */
?>

<h2>Danh sách châu lục</h2>
<hr>
<?php
    $this->load->view('admin/partials/crawl_form', [
        'action'      => 'crawler/brand/continent',
        'label'       => 'Crawl châu lục từ KAYAK',
        'placeholder' => 'https://www.kayak.com/Cheap-Hotels-in-Europe.eu.htc.html',
        'hint'        => 'Chỉ nhận link dạng <code>Cheap-Hotels-in-Tên-Châu-Lục.&lt;xx&gt;.htc.html</code> trên kayak.com '
                    . '(Africa .af, Asia .as, Caribbean .cb, Central America .ca, Europe .eu, Middle East .me, North America .na, South America .sa, South Pacific .sp). '
                    . 'Lấy tên, ảnh, nội dung; châu lục đã có sẽ được cập nhật, giữ nguyên tên, slug và thứ tự. '
                    . 'Crawl quốc gia / thành phố cũng tự tạo châu lục nếu chưa có.',
    ]);
?>

<a href="<?php echo base_url('admin/continents/add'); ?>" class="btn btn-success mb-3">Thêm mới châu lục</a>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
    <thead>
        <tr>
            <th>STT</th>
            <th>Ảnh</th>
            <th class="text-start">Tên châu lục / Slug</th>
            <th>Thứ tự</th>
            <th>Số quốc gia</th>
            <th>Số thành phố</th>
            <th>Giá khách sạn từ</th>
            <th>Nguồn</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($continents_list)): ?>
            <tr><td colspan="9" class="text-muted py-4">Chưa có châu lục nào.</td></tr>
        <?php endif; ?>

        <?php foreach ($continents_list as $key => $item): ?>
            <?php $img = upload_url($item->cover_folder, $item->cover_image); ?>
            <tr>
                <th><?php echo $offset + $key + 1; ?></th>
                <td>
                    <?php if ($img !== ''): ?>
                        <img src="<?php echo $img; ?>" alt="" width="80" class="rounded"
                             <?php echo $item->cover_folder !== 'continents' ? 'style="opacity:.5" title="Chưa có ảnh châu lục, đang dùng ảnh quốc gia / thành phố"' : ''; ?>>
                    <?php else: ?>
                        <span class="text-muted small">Chưa có ảnh</span>
                    <?php endif; ?>
                </td>
                <td class="text-start">
                    <strong><?php echo html_escape($item->name); ?></strong>
                    <br><small class="text-muted"><?php echo html_escape((string) $item->slug); ?></small>
                </td>
                <td><?php echo (int) $item->sort_order; ?></td>
                <td><span class="badge bg-primary"><?php echo (int) $item->total_countries; ?></span></td>
                <td><span class="badge bg-secondary"><?php echo (int) $item->total_places; ?></span></td>
                <td><?php echo (int) $item->min_price > 0 ? '$' . number_format((float) $item->min_price) : '-'; ?></td>
                <td><?php echo admin_source_badge($item->crawler_href ?? NULL); ?></td>
                <td class="text-nowrap">
                    <a href="<?php echo base_url('admin/continents/editContinent/' . $item->id); ?>" class="btn btn-warning btn-sm">Sửa</a>
                    <?= form_open('admin/continents/deleteContinent/' . $item->id, [
                        'class'    => 'd-inline',
                        'onsubmit' => "return confirm('Xóa châu lục này?');"
                    ]) ?>
                    <button type="submit" class="btn btn-danger btn-sm" <?php echo (int) $item->total_countries > 0 ? 'disabled title="Châu lục đang có quốc gia"' : ''; ?>>Xóa</button>
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
