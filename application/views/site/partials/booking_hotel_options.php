<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Nạp ở 2 nơi: site/booking.php (3 khách sạn đầu lúc tải trang) và Home::_book_more_hotels()
// (AJAX nút "More": render thành chuỗi HTML rồi gửi trong JSON cho js/booking_hotels.js chèn vào trang).
?>
<?php
/**
 * Các lựa chọn "Tour + khách sạn" trên form đặt lịch (dùng cho lần tải trang và cho AJAX nút "More").
 * @var array  $hotels
 * @var string $current_hotel  id khách sạn đang chọn (chuỗi)
 */
?>
<?php foreach ($hotels as $h): ?>
    <?php $h_rating = max(0, min(5, (int) $h->rating)); ?>
    <div class="form-check border rounded p-3 mb-2 booking_hotel_option">
        <input class="form-check-input ml-0" type="radio" name="hotel_id" id="hotel_<?php echo (int) $h->id; ?>"
               value="<?php echo (int) $h->id; ?>" data-price="<?php echo (int) $h->price; ?>"
               <?php echo $current_hotel === (string) (int) $h->id ? 'checked' : ''; ?>>
        <label class="form-check-label pl-4 w-100" for="hotel_<?php echo (int) $h->id; ?>">
            <strong>Tour + <?php echo html_escape($h->name); ?></strong>
            <?php if ($h_rating > 0): ?>
                <span class="text-warning"><?php echo str_repeat('★', $h_rating); ?></span>
            <?php endif; ?>
            <span class="float-right">from $<?php echo number_format((float) $h->price); ?> / night</span>
            <?php if (!empty($h->address)): ?>
                <br><small class="text-muted"><?php echo html_escape($h->address); ?></small>
            <?php endif; ?>
            <br><small><a href="<?php echo hotel_url($h); ?>" target="_blank">View hotel details</a></small>
        </label>
    </div>
<?php endforeach; ?>
