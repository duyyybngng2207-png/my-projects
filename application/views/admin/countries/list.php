<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Danh sách quốc gia - Countries::index() (kèm form crawl quốc gia). Biến: $countries_list, $pagination_links, $offset.
?>
<?php
/**
 * @var array  $countries_list
 * @var string $pagination_links
 * @var int    $offset
 */
?>

<h2>Danh sách quốc gia</h2>
<hr>
<?php
    $this->load->view('admin/partials/crawl_form', [
        'action'      => 'crawler/brand/country',
        'label'       => 'Crawl quốc gia từ KAYAK',
        'placeholder' => 'https://www.kayak.com/Vietnam-Hotels.259.dc.html',
        'hint'        => 'Chỉ nhận link trang khách sạn theo quốc gia dạng <code>Tên-Quốc-Gia-Hotels.&lt;id&gt;.dc.html</code> trên kayak.com. '
                    . 'Lấy tên, châu lục, ảnh, nội dung. Quốc gia đã có (cùng link hoặc cùng slug) sẽ được cập nhật; giữ nguyên tên, slug, mã và trạng thái nổi bật. '
                    . 'Thành phố crawl riêng ở trang Địa điểm, quốc gia của thành phố được tự xác định.',
    ]);
?>

<a href="<?php echo base_url('admin/countries/add'); ?>" class="btn btn-success mb-3">Thêm mới quốc gia</a>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
    <thead>
        <tr>
            <th>STT</th>
            <th>Ảnh</th>
            <th class="text-start">Tên quốc gia / Slug</th>
            <th>Mã</th>
            <th>Châu lục</th>
            <th>Số thành phố</th>
            <th>Giá khách sạn từ</th>
            <th>Nổi bật</th>
            <th>Nguồn</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($countries_list)): ?>
            <tr><td colspan="10" class="text-muted py-4">Chưa có quốc gia nào.</td></tr>
        <?php endif; ?>

        <?php foreach ($countries_list as $key => $item): ?>
            <?php $img = upload_url($item->cover_folder, $item->cover_image); ?>
            <tr>
                <th><?php echo $offset + $key + 1; ?></th>
                <td>
                    <?php if ($img !== ''): ?>
                        <img src="<?php echo $img; ?>" alt="" width="80" class="rounded"
                             <?php echo $item->cover_folder !== 'countries' ? 'style="opacity:.5" title="Chưa có ảnh quốc gia, đang dùng ảnh thành phố"' : ''; ?>>
                    <?php else: ?>
                        <span class="text-muted small">Chưa có ảnh</span>
                    <?php endif; ?>
                </td>
                <td class="text-start">
                    <strong><?php echo html_escape($item->name); ?></strong>
                    <br><small class="text-muted"><?php echo html_escape((string) $item->slug); ?></small>
                </td>
                <td><?php echo $item->code !== null ? html_escape($item->code) : '-'; ?></td>
                <td><?php echo $item->continent_name !== null ? html_escape($item->continent_name) : '<span class="text-danger">Chưa có</span>'; ?></td>
                <td><span class="badge bg-primary"><?php echo (int) $item->total_places; ?></span></td>
                <td><?php echo (int) $item->min_price > 0 ? '$' . number_format((float) $item->min_price) : '-'; ?></td>
                <td><?php echo $item->is_featured ? '<span class="badge bg-warning">Nổi bật</span>' : ''; ?></td>
                <td><?php echo admin_source_badge($item->crawler_href ?? NULL); ?></td>
                <td class="text-nowrap">
                    <a href="<?php echo base_url('admin/countries/editCountry/' . $item->id); ?>" class="btn btn-warning btn-sm">Sửa</a>
                    <?= form_open('admin/countries/deleteCountry/' . $item->id, [
                        'class'    => 'd-inline',
                        'onsubmit' => "return confirm('Xóa quốc gia này?');"
                    ]) ?>
                    <button type="submit" class="btn btn-danger btn-sm" <?php echo (int) $item->total_places > 0 ? 'disabled title="Quốc gia đang có thành phố"' : ''; ?>>Xóa</button>
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
