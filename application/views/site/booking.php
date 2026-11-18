<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/** @var object $place */
$image = upload_url('places', $place->image, 'images/listing_hotel.jpg');
?>

<div class="home">
    <div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo base_url('images/single_background.jpg'); ?>"></div>
    <div class="home_content">
        <div class="home_title">Bookings</div>
    </div>
</div>

<!-- Phần thân: tách khỏi banner, chia 2 khung (địa điểm | form) -->
<div class="listing page_body">
    <div class="container">
        <div class="row">

            <div class="col-lg-5 mb-4">
                <div class="info_card">
                    <img src="<?php echo $image; ?>" alt="<?php echo html_escape($place->name); ?>" class="info_card_image">
                    <div class="info_card_body">
                        <h3 class="info_card_title"><?php echo html_escape($place->name); ?></h3>
                        <?php if (!empty($place->address)): ?>
                            <p class="info_card_text"><?php echo html_escape($place->address); ?></p>
                        <?php endif; ?>
                        <a href="<?php echo place_url($place); ?>" class="info_card_link">&larr; See location details</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="form_card">
                <h4 class="form_card_title">Book your trip</h4>
                <p class="form_card_note">Fill in your details and we will contact you to confirm your booking.</p>
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
                <?php endif; ?>

                <?php echo form_open(book_url($place)); ?>
                    <div class="mb-3">
                        <label for="guest_name">Name</label>
                        <input type="text" name="guest_name" id="guest_name" class="form-control"
                               value="<?php echo set_value('guest_name'); ?>">
                        <?php echo form_error('guest_name'); ?>
                    </div>
                    <div class="mb-3">
                        <label for="guest_email">Email</label>
                        <input type="text" name="guest_email" id="guest_email" class="form-control"
                               value="<?php echo set_value('guest_email'); ?>">
                        <?php echo form_error('guest_email'); ?>
                    </div>
                    <div class="mb-3">
                        <label for="guest_phone">Phone</label>
                        <input type="text" name="guest_phone" id="guest_phone" class="form-control"
                               value="<?php echo set_value('guest_phone'); ?>">
                        <?php echo form_error('guest_phone'); ?>
                    </div>
                    <?php if (!empty($hotels)): ?>
                        <?php
                        // Chọn sẵn: giá trị vừa gửi (khi lỗi validate) > ?hotel=<id> > không khách sạn
                        $hotel_ids     = array_map(function ($h) { return (int) $h->id; }, $hotels);
                        $default_hotel = in_array((int) $selected_hotel, $hotel_ids, TRUE) ? (string) (int) $selected_hotel : '0';
                        $current_hotel = (string) set_value('hotel_id', $default_hotel);
                        ?>
                        <div class="mb-3">
                            <label class="d-block">Booking option</label>

                            <div class="form-check border rounded p-3 mb-2">
                                <input class="form-check-input ml-0" type="radio" name="hotel_id" id="hotel_0" value="0" data-price="0"
                                       <?php echo $current_hotel === '0' ? 'checked' : ''; ?>>
                                <label class="form-check-label pl-4" for="hotel_0">
                                    <strong>Tour only</strong> &mdash; no hotel
                                </label>
                            </div>

                            <?php foreach ($hotels as $h): ?>
                                <?php $h_rating = max(0, min(5, (int) $h->rating)); ?>
                                <div class="form-check border rounded p-3 mb-2">
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
                            <?php echo form_error('hotel_id'); ?>
                        </div>

                        <div class="mb-3" id="nights_group"<?php echo $current_hotel === '0' ? ' style="display:none"' : ''; ?>>
                            <label for="nights">Hotel nights</label>
                            <input type="number" name="nights" id="nights" class="form-control" min="1" max="<?php echo Bookings_model::MAX_NIGHTS; ?>"
                                   value="<?php echo set_value('nights', '1'); ?>">
                            <?php echo form_error('nights'); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Tạm tính: server sẽ tính lại và chốt giá khi gửi -->
                    <div class="alert alert-light border mb-3" id="price_summary">
                        <div class="d-flex justify-content-between" id="hotel_line" style="display:none !important"><span id="hotel_line_label"></span><span id="hotel_line_price"></span></div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between"><strong>Estimated total</strong><strong id="total_price">$0</strong></div>
                    </div>

                    <div class="mb-3">
                        <label for="booking_date">Booking date</label>
                        <input type="date" name="booking_date" id="booking_date" class="form-control"
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo set_value('booking_date'); ?>">
                        <?php echo form_error('booking_date'); ?>
                    </div>
                    <button type="submit" class="btn btn-warning btn-lg">Submit booking request</button>
                <?php echo form_close(); ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// Tạm tính tổng tiền: giá khách sạn/đêm × số đêm (địa điểm không có giá)
(function () {
    var form = document.getElementById('price_summary');
    if (!form) { return; }
    var nightsGroup = document.getElementById('nights_group');
    var nightsInput = document.getElementById('nights');
    var hotelLine = document.getElementById('hotel_line');
    var fmt = function (n) { return '$' + n.toLocaleString('en-US'); };

    function update() {
        var checked = document.querySelector('input[name="hotel_id"]:checked');
        var hotelPrice = checked ? (parseInt(checked.getAttribute('data-price'), 10) || 0) : 0;
        var hasHotel = checked && checked.value !== '0';
        var nights = Math.max(1, Math.min(<?php echo Bookings_model::MAX_NIGHTS; ?>, parseInt(nightsInput ? nightsInput.value : 1, 10) || 1));

        if (nightsGroup) { nightsGroup.style.display = hasHotel ? '' : 'none'; }
        if (hasHotel) {
            hotelLine.style.setProperty('display', 'flex', 'important');
            document.getElementById('hotel_line_label').textContent = 'Hotel: ' + fmt(hotelPrice) + ' × ' + nights + ' night(s)';
            document.getElementById('hotel_line_price').textContent = fmt(hotelPrice * nights);
        } else {
            hotelLine.style.setProperty('display', 'none', 'important');
        }
        document.getElementById('total_price').textContent = fmt(hasHotel ? hotelPrice * nights : 0);
    }

    document.querySelectorAll('input[name="hotel_id"]').forEach(function (r) { r.addEventListener('change', update); });
    if (nightsInput) { nightsInput.addEventListener('input', update); }
    update();
})();
</script>
