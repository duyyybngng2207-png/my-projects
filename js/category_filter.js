/* Lọc AJAX trang category: tên (name), số sao (stars).
 * Gửi GET tới chính URL của danh mục, server trả JSON { total, html }.
 * Bộ lọc được ghi lên thanh địa chỉ (?name=asc&stars=4) để có thể chia sẻ/tải lại trang.
 */
$(function ()
{
	'use strict';

	var $filters = $('#category_filters');
	if (!$filters.length) { return; }

	var url    = $filters.data('url');
	var $grid  = $('#category_grid');
	var $total = $('#category_total');
	var $reset = $('#category_reset');
	var request = null;
	var timer   = null;
	var memo    = {};          // kết quả đã lọc: { '?name=asc': {total, html} }
	var pausedUntil = 0;       // bị server chặn (429) thì tạm dừng đến lúc này

	// Trạng thái ban đầu lấy từ query string hiện tại
	var params = new URLSearchParams(window.location.search);
	var state  = {
		name:  params.get('name')  || '',
		stars: params.get('stars') || ''
	};

	function queryString()
	{
		var q = new URLSearchParams();
		$.each(state, function (key, value) { if (value !== '') { q.set(key, value); } });
		var s = q.toString();
		return s ? '?' + s : '';
	}

	function show(res)
	{
		$grid.html(res.html);
		$total.text(res.total);
	}

	function notice(text)
	{
		$grid.html($('<div class="offers_item text-center py-5">').append($('<p>').text(text)));
	}

	// Chống spam: bấm nhiều lựa chọn liên tiếp chỉ gửi 1 request (chờ 300ms),
	// request cũ bị huỷ, bộ lọc đã xem thì lấy lại từ bộ nhớ trình duyệt.
	function load()
	{
		var qs = queryString();
		window.history.replaceState(null, '', url + qs);
		$reset.toggle(qs !== '');

		clearTimeout(timer);
		if (request) { request.abort(); }

		// Bộ lọc này đã tải trước đó -> hiện lại ngay, không gọi server
		if (memo[qs]) { show(memo[qs]); return; }

		// Làm mờ danh sách trong lúc chờ kết quả
		$grid.css('opacity', 0.4);
		timer = setTimeout(function ()
		{
			// Đang bị server tạm chặn (429) -> báo, không gửi
			if (Date.now() < pausedUntil)
			{
				$grid.css('opacity', 1);
				notice('You are filtering too fast. Please wait a moment and try again.');
				return;
			}

			// GET country/<slug>?name=..&stars=.. kèm header AJAX -> Home::country() trả JSON {total, html}
			request = $.ajax({
				url: url + qs,
				method: 'GET',
				dataType: 'json',
				cache: false,      // cùng URL với trang thường -> không dùng cache của trình duyệt
				timeout: 10000
			})
			.done(function (res)
			{
				// Lưu lại kết quả theo bộ lọc rồi hiển thị
				memo[qs] = res;
				show(res);
			})
			.fail(function (xhr, status)
			{
				// Request bị huỷ do người dùng đổi bộ lọc tiếp -> không báo lỗi
				if (status === 'abort') { return; }
				// 429: lọc quá nhanh -> tạm dừng đúng số giây server yêu cầu
				if (xhr.status === 429)
				{
					var wait = ((xhr.responseJSON && xhr.responseJSON.retry_after) || 10) * 1000;
					pausedUntil = Date.now() + wait;
					notice('You are filtering too fast. Please wait a moment and try again.');
				}
				else
				{
					notice('Could not load locations. Please try again.');
				}
			})
			// Xong (thành công hay lỗi) -> bỏ làm mờ
			.always(function () { $grid.css('opacity', 1); });
		}, 300);
	}

	// Chọn một giá trị trong dropdown
	// Mỗi nút có data-key (name / stars) và data-value (asc, desc, 1..5 hoặc '' = bỏ lọc)
	$filters.on('click', '.ajax_filter_btn', function ()
	{
		var $btn  = $(this);
		var key   = $btn.data('key');
		var value = String($btn.data('value'));

		if (state[key] === value) { return; }   // chọn lại đúng giá trị đang chọn: không gửi
		state[key] = value;

		// Đổi chữ trên nút dropdown thành lựa chọn vừa bấm (bỏ lọc -> chữ mặc định)
		var $text = $btn.closest('ul').siblings('.sorting_text');
		$text.text(value === '' ? $text.data('default') : $btn.text().trim());

		load();
	});

	// Xoá tất cả bộ lọc
	$reset.on('click', function (e)
	{
		e.preventDefault();
		state = { name: '', stars: '' };
		$filters.find('.sorting_text').each(function () { $(this).text($(this).data('default')); });
		load();
	});
});
