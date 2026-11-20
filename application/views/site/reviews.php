<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Form viết đánh giá /review/<id>-<slug> - Home::review(). Chỉ email có đơn "Đã hoàn thành" ở khách sạn này mới gửi được.
// Có ô ẩn "website" làm bẫy chống bot. Biến: $hotel.
?>
<?php
/** @var object $hotel  khách sạn được đánh giá (getHotelDetail, kèm place_name) */
$image = hotel_image_url($hotel, 'images/listing_hotel.jpg');
?>

<div class="home">
    <div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo base_url('images/single_background.jpg'); ?>"></div>
    <div class="home_content">
        <div class="home_title">reviews</div>
    </div>
</div>

<!-- Phần thân: tách khỏi banner, chia 2 khung (khách sạn | form) -->
<div class="listing page_body">
    <div class="container">
        <div class="row">

            <?php // ---- Cột trái: thẻ khách sạn được đánh giá ---- ?>
            <div class="col-lg-5 mb-4">
                <div class="info_card">
                    <img src="<?php echo html_escape($image); ?>" alt="<?php echo html_escape($hotel->name); ?>" class="info_card_image" referrerpolicy="no-referrer">
                    <div class="info_card_body">
                        <h3 class="info_card_title"><?php echo html_escape($hotel->name); ?></h3>
                        <?php if (!empty($hotel->address)): ?>
                            <p class="info_card_text"><?php echo html_escape($hotel->address); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($hotel->place_name)): ?>
                            <p class="info_card_text"><?php echo html_escape($hotel->place_name); ?></p>
                        <?php endif; ?>
                        <a href="<?php echo hotel_url($hotel); ?>" class="info_card_link">&larr; See hotel details</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="form_card">
                <h4 class="form_card_title">Share your experience</h4>
                <p class="form_card_note">Only guests who have completed a stay at this hotel are eligible to leave a review. Please use the same email address you used for your booking.</p>

                <?php // Gửi thành công -> controller đặt flash 'success' rồi redirect về đây ?>
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
                <?php endif; ?>

                <?php // Form POST về chính review/<id>-<slug> -> Home::review() kiểm tra rồi lưu (chờ duyệt) ?>
                <?php echo form_open(review_url($hotel)); ?>

                    <?php // BẪY CHỐNG BOT: ô "website" bị đẩy ra ngoài màn hình, người thật không thấy nên để trống; bot điền vào -> bị bỏ qua ?>
                    <div style="position:absolute; left:-9999px;" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label for="name">Full name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?php echo set_value('name'); ?>">
                        <?php echo form_error('name'); ?>
                    </div>
                    <?php // Email phải trùng email lúc đặt lịch (có đơn "Đã hoàn thành") - kiểm tra ở Home::_can_review() ?>
                    <div class="mb-3">
                        <label for="email">Email used to book the appointment</label>
                        <input type="text" name="email" id="email" class="form-control" value="<?php echo set_value('email'); ?>">
                        <?php echo form_error('email'); ?>
                    </div>
                    <div class="mb-3">
                        <label for="rating">Rating</label>
                        <select name="rating" id="rating" class="form-control">
                            <option value="">-- Select star rating --</option>
                            <?php // 5 -> 1 sao; set_select() giữ lựa chọn cũ khi form lỗi ?>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo set_select('rating', (string) $i); ?>><?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?></option>
                            <?php endfor; ?>
                        </select>
                        <?php echo form_error('rating'); ?>
                    </div>
                    <div class="mb-3">
                        <label for="content">Content (minimum 10 characters)</label>
                        <textarea name="content" id="content" rows="5" class="form-control"><?php echo set_value('content'); ?></textarea>
                        <?php echo form_error('content'); ?>
                    </div>
                    <button type="submit" class="btn btn-warning btn-lg">Submit review</button>
                <?php echo form_close(); ?>
                </div>
            </div>

        </div>
    </div>
</div>