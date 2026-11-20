<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Danh sách địa điểm (thành phố) - Places::index(), cũng là trang mở đầu sau khi đăng nhập admin.
// Đầu trang có form "Crawl địa điểm từ KAYAK" (-> crawler/brand/place). Biến: $places_list, $pagination_links, $offset.
?>
<?php
/**
 * @var array  $places_list
 * @var string $pagination_links
 * @var int    $offset
 */
?>

<h2>Danh sách địa điểm</h2>
<hr>
<?php
    $this->load->view('admin/partials/crawl_form', [
        'action'      => 'crawler/brand/place',
        'label'       => 'Crawl địa điểm (thành phố) từ KAYAK',
        'placeholder' => 'https://www.kayak.com/Phu-Quoc-Hotels.54054.hotel.ksp',
        'hint'        => 'Chỉ nhận link trang khách sạn theo thành phố dạng <code>Tên-Thành-Phố-Hotels.&lt;id&gt;.hotel.ksp</code> trên kayak.com. '
                    . 'Quốc gia được tự xác định từ trang nguồn (chưa có thì tự tạo). '
                    . 'Crawl lại cùng link sẽ cập nhật địa chỉ, ảnh, nội dung; giữ nguyên tên, slug, số sao và trạng thái nổi bật.',
    ]);
?>


<a href="<?php echo base_url('admin/places/add'); ?>" class="btn btn-success mb-3">Thêm mới địa điểm</a>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
    <thead>
        <tr>
            <th>STT</th>
            <th>Ảnh</th>
            <th class="text-start">Tên địa điểm / Slug</th>
            <th>Quốc gia</th>
            <th class="text-start">Địa chỉ</th>
            <th>Sao</th>
            <th>Nổi bật</th>
            <th>Nguồn</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($places_list)): ?>
            <tr><td colspan="9" class="text-muted py-4">Chưa có địa điểm nào.</td></tr>
        <?php endif; ?>

        <?php foreach ($places_list as $key => $item): ?>
            <?php $img = upload_url('places', $item->image); ?>
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
                    <strong><?php echo html_escape($item->name); ?></strong>
                    <br><small class="text-muted"><?php echo html_escape((string) $item->slug); ?></small>
                </td>
                <td><?php echo $item->country_name !== null ? html_escape($item->country_name) : '<span class="text-danger">Chưa có quốc gia</span>'; ?></td>
                <td class="text-start"><?php echo html_escape($item->address); ?></td>
                <td><?php echo !empty($item->rating) ? '<span class="text-warning">' . str_repeat('★', (int) $item->rating) . '</span>' : '-'; ?></td>
                <td><?php echo $item->is_featured ? '<span class="badge bg-warning">Nổi bật</span>' : ''; ?></td>
                <td><?php echo admin_source_badge($item->crawler_href ?? NULL); ?></td>
                <td class="text-nowrap">
                    <a href="<?php echo base_url('admin/places/editPlace/' . $item->id); ?>" class="btn btn-warning btn-sm">Sửa</a>
                    <?= form_open('admin/places/deletePlace/' . $item->id, [
                        'class'    => 'd-inline',
                        'onsubmit' => "return confirm('Xóa địa điểm này?');"
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
