<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Danh sách khách sạn - Hotels::index() (URL /admin/hotels).
// Đầu trang có form "Crawl khách sạn từ KAYAK" (crawl_form.php -> crawler/brand). Biến: $hotels_list, $places_list, $pagination_links, $offset.
?>
<?php /** @var array $hotels_list */ /** @var array $places_list */ /** @var string $pagination_links */ /** @var int $offset */ ?>

<h2>Danh sách khách sạn</h2>
<hr>

<?php
$place_options = [];
foreach ($places_list as $p) {
    $place_options[$p->id] = $p->name;
}
$this->load->view('admin/partials/crawl_form', [
    'action'      => 'crawler/brand',
    'label'       => 'Crawl khách sạn từ KAYAK',
    'placeholder' => 'https://www.kayak.com/...-Hotels-....ksp',
    'hint'        => 'Chỉ nhận link trang chi tiết khách sạn trên kayak.com. Crawl lại cùng khách sạn sẽ cập nhật bản ghi cũ (giữ ảnh tải lên và trạng thái trending).',
    'select'      => ['name' => 'place_id', 'label' => 'Thuộc địa điểm', 'empty' => '-- Tự động theo thành phố --', 'options' => $place_options],
]);
?>

<a href="<?php echo base_url('admin/hotels/add'); ?>" class="btn btn-success mb-3">Thêm mới khách sạn</a>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
    <thead>
        <tr>
            <th>STT</th>
            <th>Ảnh</th>
            <th class="text-start">Tên khách sạn / Slug</th>
            <th>Địa điểm</th>
            <th class="text-start">Địa chỉ / SĐT</th>
            <th>Giá</th>
            <th>Đánh giá</th>
            <th>Trending</th>
            <th>Nguồn</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($hotels_list)): ?>
            <tr><td colspan="10" class="text-muted py-4">Chưa có khách sạn nào.</td></tr>
        <?php endif; ?>

        <?php foreach ($hotels_list as $key => $item): ?>
            <?php
            // Ưu tiên ảnh tải lên, không có thì dùng ảnh đại diện (URL, thường từ crawler)
            $img = upload_url('hotels', $item->image);
            if ($img === '' && !empty($item->thumbnail)) {
                $img = $item->thumbnail;
            }
            ?>
            <tr>
                <th><?php echo $offset + $key + 1; ?></th>
                <td>
                    <?php if ($img !== ''): ?>
                        <img src="<?php echo html_escape($img); ?>" alt="" width="80" class="rounded" referrerpolicy="no-referrer">
                    <?php else: ?>
                        <span class="text-muted small">Chưa có ảnh</span>
                    <?php endif; ?>
                </td>
                <td class="text-start">
                    <strong><?php echo html_escape($item->name); ?></strong>
                    <br><small class="text-muted"><?php echo html_escape($item->slug); ?></small>
                </td>
                <td>
                    <?php echo $item->place_name !== null ? html_escape($item->place_name) : '<span class="text-muted">Không thuộc địa điểm nào</span>'; ?>
                    <?php if (!empty($item->country_name)): ?>
                        <br><small class="text-muted"><?php echo html_escape($item->country_name); ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-start">
                    <?php echo html_escape($item->address ?? ''); ?>
                    <?php if (!empty($item->phone)): ?>
                        <br><small><i class="bi bi-telephone"></i> <?php echo html_escape($item->phone); ?></small>
                    <?php endif; ?>
                </td>
                <td>$<?php echo number_format((float) $item->price); ?></td>
                <td>
                    <?php if (!empty($item->rating)): ?>
                        <span class="text-warning"><?php echo str_repeat('★', (int) $item->rating); ?></span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                    <?php if (!empty($item->rating_count)): ?>
                        <br><small class="text-muted"><?php echo number_format((int) $item->rating_count); ?> lượt</small>
                    <?php endif; ?>
                </td>
                <td><?php echo $item->is_trending ? '<span class="badge bg-warning">Trending</span>' : ''; ?></td>
                <td>
                    <?php if (!empty($item->crawler_href)): ?>
                        <a href="<?php echo html_escape($item->crawler_href); ?>" target="_blank" rel="noopener noreferrer" class="badge bg-info text-decoration-none">Crawler</a>
                    <?php else: ?>
                        <span class="badge bg-secondary">Thủ công</span>
                    <?php endif; ?>
                </td>
                <td class="text-nowrap">
                    <a href="<?php echo base_url('admin/hotels/editHotel/' . $item->id); ?>" class="btn btn-warning btn-sm">Sửa</a>
                    <?= form_open('admin/hotels/deleteHotel/' . $item->id, [
                        'class'    => 'd-inline',
                        'onsubmit' => "return confirm('Xóa khách sạn này?');"
                    ])?>
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
