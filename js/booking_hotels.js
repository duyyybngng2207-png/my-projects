/* Trang đặt lịch (book/<slug>): lúc đầu chỉ hiện 3 khách sạn, bấm "More" để tải các khách sạn còn lại.
 * Gửi GET tới chính URL đặt lịch: ?more=1&exclude=<id đã hiện>, server trả JSON { count, html }.
 * html là các lựa chọn radio cùng partial với lúc tải trang -> chèn vào #booking_hotels.
 * Phần tạm tính giá trong booking.php nghe sự kiện change trên document nên tự áp dụng cho khách sạn mới.
 */
$(function ()
{
	'use strict';

	// Nút "More hotels" chỉ có khi thành phố còn khách sạn chưa hiện; không có nút -> thoát
	var $btn = $('#booking_hotels_more');
	if (!$btn.length) { return; }

	var $list  = $('#booking_hotels');              // khung chứa các lựa chọn khách sạn
	var $wrap  = $('#booking_hotels_more_wrap');    // khối bao nút "More" (xoá đi khi đã tải hết)
	var $error = $('#booking_hotels_error');        // dòng báo lỗi
	var loading = false;                            // đang tải -> không cho bấm tiếp

	// Bật / tắt trạng thái đang tải: khoá nút + đổi chữ "More hotels" <-> "Loading..."
	function busy(on)
	{
		loading = on;
		$btn.prop('disabled', on);
		$btn.find('.more_idle').toggleClass('d-none', on);
		$btn.find('.more_busy').toggleClass('d-none', !on);
	}

	$btn.on('click', function ()
	{
		if (loading) { return; }

		// Id các khách sạn đang có trên form (gồm cả khách sạn được chọn sẵn ngoài 3 khách sạn đầu)
		var shown = $list.find('input[name="hotel_id"]').map(function () { return this.value; }).get();

		$error.hide();
		busy(true);

		// data-url của nút = URL trang đặt lịch -> Home::book() nhận ra ?more=1 và gọi _book_more_hotels()
		$.ajax({
			url: $btn.data('url'),
			type: 'GET',
			dataType: 'json',
			data: { more: 1, exclude: shown.join(',') },    // exclude = "12,15,20"
			timeout: 15000
		})
		.done(function (res)
		{
			// JSON không đúng dạng mong đợi -> báo lỗi, cho bấm lại
			if (!res || typeof res.html !== 'string') {
				$error.text('Could not load more hotels. Please try again.').show();
				busy(false);
				return;
			}
			// Chuyển chuỗi HTML thành phần tử, chỉ giữ các khối khách sạn, ẩn trước để chạy hiệu ứng trượt
			var $items = $(res.html).filter('.booking_hotel_option').hide();
			$list.append($items);
			$items.slideDown(250);
			$wrap.remove(); // đã hiện tất cả
		})
		.fail(function (xhr)
		{
			// 429 = bấm quá nhiều lần (Rate_limiter phía server)
			var msg = (xhr.status === 429)
				? 'Too many requests, please wait a few seconds and try again.'
				: 'Could not load more hotels. Please try again.';
			$error.text(msg).show();
			busy(false);
		});
	});
});
