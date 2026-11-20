<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Form ĐẶT LỊCH /book/<slug> - Home::book(). LUỒNG: khách điền thông tin -> chọn "chỉ tour" hoặc "tour + khách sạn"
// -> (nếu có khách sạn) nhập số đêm -> xem tạm tính -> chọn ngày -> gửi POST về chính URL này.
// Lúc đầu chỉ hiện 3 khách sạn; nút "More hotels" tải phần còn lại bằng AJAX (js/booking_hotels.js).
?>
<?php
/**
 * @var object $place
 * @var array  $hotels          3 khách sạn đầu (+ khách sạn đang chọn nếu nằm ngoài 3 khách sạn đó)
 * @var int    $hotels_total    tổng số khách sạn của địa điểm (phần còn lại tải bằng nút "More")
 * @var int    $selected_hotel  ?hotel=<id> chọn sẵn
 */
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

            <?php // ---- Cột trái: thẻ thông tin thành phố (ảnh, tên, địa chỉ, link về trang chi tiết) ---- ?>
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

            <?php // ---- Cột phải: form đặt lịch ---- ?>
            <div class="col-lg-7">
                <div class="form_card">
                <h4 class="form_card_title">Book your trip</h4>
                <p class="form_card_note">Fill in your details and we will contact you to confirm your booking.</p>
                <?php // Đặt thành công -> Home::book() lưu flashdata 'success' rồi redirect về đây -> hiện 1 lần ?>
                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
                <?php endif; ?>

                <?php // form_open(): tạo thẻ form method="post" + ô ẩn CSRF token; gửi về chính /book/<slug> ?>
                <?php echo form_open(book_url($place)); ?>
                    <?php // 3 ô thông tin khách. set_value() giữ lại giá trị đã nhập khi form lỗi; form_error() in lỗi của ô ?>
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
                    <?php // Thành phố có khách sạn -> cho chọn "chỉ tour" hoặc "tour + khách sạn" ?>
                    <?php if (!empty($hotels)): ?>
                        <?php
                        // Chọn sẵn: giá trị vừa gửi (khi lỗi validate) > ?hotel=<id> > không khách sạn
                        $hotel_ids     = array_map(function ($h) { return (int) $h->id; }, $hotels);
                        $default_hotel = in_array((int) $selected_hotel, $hotel_ids, TRUE) ? (string) (int) $selected_hotel : '0';
                        $current_hotel = (string) set_value('hotel_id', $default_hotel);
                        ?>
                        <div class="mb-3">
                            <label class="d-block">Booking option</label>

                            <?php // Lựa chọn "chỉ tour": hotel_id = 0, giá 0 ?>
                            <div class="form-check border rounded p-3 mb-2">
                                <input class="form-check-input ml-0" type="radio" name="hotel_id" id="hotel_0" value="0" data-price="0"
                                       <?php echo $current_hotel === '0' ? 'checked' : ''; ?>>
                                <label class="form-check-label pl-4" for="hotel_0">
                                    <strong>Tour only</strong> &mdash; no hotel
                                </label>
                            </div>

                            <?php // Các lựa chọn "tour + khách sạn" (mỗi radio có data-price = giá/đêm); khách sạn tải thêm được chèn vào đây ?>
                            <div id="booking_hotels">
                                <?php $this->load->view('site/partials/booking_hotel_options', ['hotels' => $hotels, 'current_hotel' => $current_hotel]); ?>
                            </div>

                            <?php // Số khách sạn chưa hiện; còn > 0 thì hiện nút "More hotels (n)" ?>
                            <?php $hotels_left = (int) $hotels_total - count($hotels); ?>
                            <?php if ($hotels_left > 0): ?>
                                <!-- Nút "More": tải các khách sạn còn lại bằng AJAX (js/booking_hotels.js) -->
                                <div class="booking_hotels_more" id="booking_hotels_more_wrap">
                                    <button type="button" class="hotel_amenities_toggle" id="booking_hotels_more"
                                            data-url="<?php echo book_url($place); ?>">
                                        <span class="more_idle">More hotels (<?php echo $hotels_left; ?>) <i class="fa fa-angle-down"></i></span>
                                        <span class="more_busy d-none"><i class="fa fa-spinner fa-spin"></i> Loading...</span>
                                    </button>
                                    <div class="booking_hotels_error text-danger small mt-2" id="booking_hotels_error" style="display:none"></div>
                                </div>
                            <?php endif; ?>
                            <?php echo form_error('hotel_id'); ?>
                        </div>

                        <?php // Ô số đêm: chỉ hiện khi đã chọn khách sạn (JS bên dưới bật / tắt) ?>
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

                    <?php // Ngày đi: min = hôm nay (trình duyệt chặn chọn ngày cũ; server kiểm tra lại bằng _future_date) ?>
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
    // Định dạng tiền: 1500 -> "$1,500"
    var fmt = function (n) { return '$' + n.toLocaleString('en-US'); };

    // Tính lại tạm tính mỗi khi đổi lựa chọn khách sạn / số đêm
    function update() {
        // Radio đang chọn + giá/đêm lấy từ thuộc tính data-price
        var checked = document.querySelector('input[name="hotel_id"]:checked');
        var hotelPrice = checked ? (parseInt(checked.getAttribute('data-price'), 10) || 0) : 0;
        var hasHotel = checked && checked.value !== '0';
        // Số đêm ép vào khoảng 1..MAX_NIGHTS (giống server)
        var nights = Math.max(1, Math.min(<?php echo Bookings_model::MAX_NIGHTS; ?>, parseInt(nightsInput ? nightsInput.value : 1, 10) || 1));

        // Có khách sạn -> hiện ô số đêm và dòng "Hotel: $x × n night(s)"; không -> ẩn
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

    // Uỷ quyền sự kiện lên document: các khách sạn tải thêm bằng nút "More" (AJAX) cũng được tính giá
    document.addEventListener('change', function (e) {
        if (e.target && e.target.name === 'hotel_id') { update(); }
    });
    // Gõ số đêm -> tính lại ngay
    if (nightsInput) { nightsInput.addEventListener('input', update); }
    // Tính 1 lần lúc mở trang (trường hợp đã chọn sẵn khách sạn)
    update();
})();
</script>
