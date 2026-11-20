<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Chi tiết 1 đơn đặt lịch - Bookings::detail($id) (URL /admin/bookings/detail/<id>).
// Biến: $booking (kèm tên địa điểm, khách sạn, giá đã chốt lúc đặt và giá hiện tại).
?>
<?php
/** @var object $booking */
$statusMap   = [1 => 'Chờ xác nhận', 2 => 'Đã xác nhận', 3 => 'Đã hoàn thành'];
$statusClass = [1 => 'warning', 2 => 'info', 3 => 'success'];
?>
<div class="container">
    <h2>Chi tiết đặt lịch #<?php echo $booking->id; ?></h2>
    <hr>

    <div class="row mb-4">
        <div class="col-6">
            <h5>Thông tin khách hàng</h5>
            <table class="table table-bordered">
                <tr>
                    <td><strong>Tên</strong></td>
                    <td><?php echo html_escape($booking->guest_name); ?></td>
                </tr>
                <tr>
                    <td><strong>Email</strong></td>
                    <td><?php echo html_escape($booking->guest_email); ?></td>
                </tr>
                <tr>
                    <td><strong>SĐT</strong></td>
                    <td><?php echo html_escape($booking->guest_phone); ?></td>
                </tr>
                <tr>
                    <td><strong>Ngày muốn đặt</strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($booking->booking_date)); ?></td>
                </tr>
                <tr>
                    <td><strong>Ngày gửi yêu cầu</strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($booking->created_at)); ?></td>
                </tr>
            </table>
        </div>

        <div class="col-6">
            <h5>Trạng thái</h5>
            <p>
                <span class="badge bg-<?php echo $statusClass[$booking->is_status] ?? 'secondary'; ?>">
                    <?php echo $statusMap[$booking->is_status] ?? 'Không rõ'; ?>
                </span>
            </p>
            <?php echo form_open('admin/bookings/updateStatus/' . $booking->id); ?>
                <select name="is_status" class="form-control mb-2">
                    <?php foreach ($statusMap as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo ($booking->is_status == $value) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Cập nhật</button>
            <?php echo form_close(); ?>
        </div>
    </div>

    <h5>Địa điểm được đặt</h5>
    <table class="table table-bordered">
        <tr>
            <td><strong>Tên địa điểm</strong></td>
            <td><?php echo html_escape($booking->place_name); ?></td>
        </tr>
        <tr>
            <td><strong>Địa chỉ</strong></td>
            <td><?php echo html_escape($booking->place_address); ?></td>
        </tr>
        <?php if ($booking->place_price !== null): // chỉ booking cũ (trước khi bỏ giá địa điểm) mới có ?>
        <tr>
            <td><strong>Giá tour (lúc đặt)</strong></td>
            <td>$<?php echo number_format($booking->place_price); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <h5>Khách sạn</h5>
    <?php if (!empty($booking->hotel_name)): ?>
        <table class="table table-bordered">
            <tr>
                <td><strong>Tên khách sạn</strong></td>
                <td><?php echo html_escape($booking->hotel_name); ?></td>
            </tr>
            <tr>
                <td><strong>Địa chỉ</strong></td>
                <td><?php echo html_escape($booking->hotel_address); ?></td>
            </tr>
            <tr>
                <td><strong>Hạng sao</strong></td>
                <td><?php echo (int) $booking->hotel_rating > 0 ? str_repeat('★', (int) $booking->hotel_rating) : 'Chưa có'; ?></td>
            </tr>
            <tr>
                <td><strong>Giá / đêm (lúc đặt)</strong></td>
                <td>
                    $<?php echo number_format((float) $booking->hotel_price); ?>
                    <?php if ($booking->current_hotel_price !== null && (int) $booking->current_hotel_price !== (int) $booking->hotel_price): ?>
                        <small class="text-muted">(giá hiện tại: $<?php echo number_format($booking->current_hotel_price); ?>)</small>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Số đêm</strong></td>
                <td><?php echo (int) $booking->nights; ?></td>
            </tr>
        </table>
    <?php else: ?>
        <p class="text-muted">Khách chỉ đặt tour, không kèm khách sạn.</p>
    <?php endif; ?>

    <h5>Tổng tiền</h5>
    <?php $has_hotel = $booking->hotel_price !== null; // giá chốt vẫn còn dù khách sạn đã bị xoá ?>
    <table class="table table-bordered">
        <?php if ($booking->place_price !== null): ?>
        <tr>
            <td>Tour <?php echo html_escape($booking->place_name); ?></td>
            <td class="text-end">$<?php echo number_format((int) $booking->place_price); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($has_hotel): ?>
            <tr>
                <td>
                    Khách sạn <?php echo html_escape($booking->hotel_name ?? "(đã bị xoá)"); ?>:
                    $<?php echo number_format((int) $booking->hotel_price); ?> × <?php echo (int) $booking->nights; ?> đêm
                </td>
                <td class="text-end">$<?php echo number_format((int) $booking->hotel_price * (int) $booking->nights); ?></td>
            </tr>
        <?php endif; ?>
        <tr class="table-warning">
            <td><strong>Tổng cộng</strong></td>
            <td class="text-end"><strong>$<?php echo number_format((int) $booking->total_price); ?></strong></td>
        </tr>
    </table>

    <a href="<?php echo base_url('admin/bookings'); ?>" class="btn btn-primary">Quay lại</a>
</div>