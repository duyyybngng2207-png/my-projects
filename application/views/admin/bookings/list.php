<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php /** @var array $bookings_list */ /** @var int $auto_completed */ /** @var string $pagination_links */ /** @var int $offset */ ?>
<?php
$statusMap   = [1 => 'Chờ xác nhận', 2 => 'Đã xác nhận', 3 => 'Đã hoàn thành'];
$statusClass = [1 => 'warning', 2 => 'info', 3 => 'success'];
?>

<h2>Danh sách đặt lịch</h2>
<hr>

<?php if (!empty($auto_completed)): ?>
    <div class="alert alert-info">
        Đã tự động chuyển <?php echo (int) $auto_completed; ?> lịch "Đã xác nhận" đến ngày đi sang "Đã hoàn thành".
    </div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
    <thead>
        <tr>
            <th>STT</th>
            <th>Địa điểm</th>
            <th>Khách sạn</th>
            <th>Tên khách</th>
            <th>Email</th>
            <th>SĐT</th>
            <th>Ngày đặt</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Cập nhật trạng thái</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($bookings_list)): ?>
            <tr><td colspan="11" class="text-muted py-4">Chưa có lịch đặt nào.</td></tr>
        <?php endif; ?>

        <?php foreach ($bookings_list as $key => $item): ?>
            <tr>
                <td><?php echo $offset + $key + 1; ?></td>
                <td><?php echo html_escape($item->place_name); ?></td>
                <td>
                    <?php if (!empty($item->hotel_name)): ?>
                        <?php echo html_escape($item->hotel_name); ?>
                        <br><small class="text-muted"><?php echo (int) $item->nights; ?> đêm</small>
                    <?php else: ?>
                        <span class="text-muted">Chỉ tour</span>
                    <?php endif; ?>
                </td>
                <td><?php echo html_escape($item->guest_name); ?></td>
                <td><?php echo html_escape($item->guest_email); ?></td>
                <td><?php echo html_escape($item->guest_phone); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($item->booking_date)); ?></td>
                <td><strong>$<?php echo number_format((int) $item->total_price); ?></strong></td>
                <td>
                    <span class="badge bg-<?php echo $statusClass[$item->is_status] ?? 'secondary'; ?>">
                        <?php echo $statusMap[$item->is_status] ?? 'Không rõ'; ?>
                    </span>
                </td>
                <td>
                    <?php echo form_open('admin/bookings/updateStatus/' . $item->id, ['class' => 'd-flex gap-1']); ?>
                        <select name="is_status" class="form-select form-select-sm">
                            <?php foreach ($statusMap as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo ($item->is_status == $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                    <?php echo form_close(); ?>
                </td>
                <td>
                    <a href="<?php echo base_url('admin/bookings/detail/' . $item->id); ?>" class="btn btn-info btn-sm">Chi tiết</a>
                    <?= form_open('admin/bookings/deleteBooking' . $item->id, [
                        'class'    => 'd_inline',
                        'onsubmit' => "return confirm('Xóa lịch đặt này?');"
                    ])  ?>
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