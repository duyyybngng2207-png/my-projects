// JS dùng chung cho mọi trang công khai (nạp ở cuối site/partials/footer.php).
// Gồm 2 phần: (1) gửi form liên hệ bằng AJAX, (2) gợi ý tìm kiếm khi đang gõ.
// $(function () { ... }) = chạy khi HTML đã tải xong.
$(function () {

    /* ========== Contact form (AJAX) ========== */
    // Form liên hệ có id="contact_form"; trang nào không có form thì bỏ qua cả khối
    var $form = $('#contact_form');
    if ($form.length) {
        var $msg = $('#contact_msg');           // khung hiện thông báo
        var hideTimer = null;                   // hẹn giờ tự ẩn thông báo
        var sending = false;                    // đang gửi -> không cho gửi tiếp

        // Hiện thông báo (type = success / danger / warning theo class Bootstrap), 4 giây sau tự ẩn
        var showMessage = function (type, text) {
            clearTimeout(hideTimer);
            $msg.stop(true, true).hide()
                .attr('class', 'alert alert-' + type)
                .text(text)
                .fadeIn(300);
            hideTimer = setTimeout(function () { $msg.fadeOut(400); }, 4000);
        };

        $form.on('submit', function (e) {
            e.preventDefault();                 // không tải lại trang, gửi bằng AJAX thay thế
            if (sending) { return; }            // chặn bấm liên tục khi đang gửi
            sending = true;

            var $btn = $form.find('button[type=submit]').prop('disabled', true);
            var cooldown = 0;                   // số mili giây khoá nút sau khi gửi

            // Gửi POST tới action của form (contact/send) -> controllers/Contact.php::send()
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),        // toàn bộ ô trong form (kèm CSRF token)
                dataType: 'json',
                timeout: 15000
            }).done(function (res) {
                // CI đổi CSRF token sau mỗi lần POST -> cập nhật ô ẩn để lần gửi sau không bị chặn
                $form.find('input[name="' + res.csrf_name + '"]').val(res.csrf_hash);
                if (res.success) {
                    $form[0].reset();           // xoá trắng form
                    cooldown = 10000;           // gửi xong: khoá nút 10 giây
                }
                showMessage(res.success ? 'success' : 'danger', res.message);
            }).fail(function (xhr) {
                // Lỗi HTTP (429, 500...): vẫn đọc JSON nếu có để lấy token mới
                var res = xhr.responseJSON || {};
                if (res.csrf_name) {
                    $form.find('input[name="' + res.csrf_name + '"]').val(res.csrf_hash);
                }
                // 429 = gửi quá nhiều (Rate_limiter) -> khoá nút đúng số giây server yêu cầu
                if (xhr.status === 429) {
                    cooldown = (res.retry_after || 30) * 1000;
                    showMessage('warning', res.message || 'Too many requests. Please try again later.');
                } else {
                    showMessage('danger', 'Failed to send. Please reload the page and try again.');
                }
            }).always(function () {
                // Dù thành công hay lỗi: hết thời gian khoá thì mở lại nút
                setTimeout(function () {
                    sending = false;
                    $btn.prop('disabled', false);
                }, cooldown);
            });
        });
    }

    /* ========== Live search (AJAX) ==========
     * Chống spam: chờ ngừng gõ 400ms mới gửi, huỷ request cũ, không gửi lại từ khoá vừa tìm,
     * nhớ kết quả trong trình duyệt, bị server chặn (429) thì tạm dừng.
     */
    var $input   = $('#search_input');          // ô tìm kiếm trên header
    var $results = $('#search_results');        // khung danh sách gợi ý
    if ($input.length) {
        var timer = null, xhr = null, lastQuery = '', pausedUntil = 0;
        var memo = {};                          // kết quả đã tìm: { 'tu khoa': [...] }

        // Nhấn Enter trong ô gợi ý không gửi form
        $('#search_form').on('submit', function (e) { e.preventDefault(); });

        // Vẽ danh sách gợi ý từ mảng items [{type, title, url, image}] (Search::suggest trả về)
        var render = function (items) {
            $results.empty();
            if (!items.length) {
                $results.append($('<div class="ls_empty">').text('No results found'));
            } else {
                $.each(items, function (_, it) {
                    // Dùng .text()/.attr() thay vì nối chuỗi HTML -> chống chèn mã độc (XSS)
                    var $a = $('<a class="ls_item">').attr('href', it.url);
                    if (it.image) {
                        $a.append($('<img>').attr('src', it.image).attr('alt', ''));
                    }
                    $a.append($('<span class="ls_text">')
                        .append($('<strong>').text(it.title))
                        .append($('<small>').text(it.type)));
                    $results.append($a);
                });
            }
            $results.fadeIn(150);
        };

        // Hiện 1 dòng thông báo trong khung gợi ý
        var showNotice = function (text) {
            $results.empty().append($('<div class="ls_empty">').text(text)).fadeIn(150);
        };

        // Mỗi lần nội dung ô thay đổi
        $input.on('input', function () {
            var q = $.trim($input.val()).substring(0, 100);
            var key = q.toLowerCase();
            clearTimeout(timer);                // huỷ lần hẹn gửi trước (người dùng vẫn đang gõ)

            // Dưới 2 ký tự -> ẩn gợi ý, huỷ request đang chạy
            if (q.length < 2) {
                if (xhr) { xhr.abort(); }
                lastQuery = '';
                $results.hide().empty();
                return;
            }
            if (key === lastQuery) { return; }  // từ khoá không đổi (vd chỉ thêm khoảng trắng)

            if (memo[key]) {                    // đã tìm rồi: hiện ngay, không gọi server
                if (xhr) { xhr.abort(); }
                lastQuery = key;
                render(memo[key]);
                return;
            }

            // Ngừng gõ 400ms mới gửi request
            timer = setTimeout(function () {
                // Đang bị server tạm chặn -> chỉ báo, không gửi
                if (Date.now() < pausedUntil) {
                    showNotice('Searching too fast. Please wait a moment...');
                    return;
                }
                if (xhr) { xhr.abort(); }       // huỷ request cũ chưa xong
                lastQuery = key;

                // BASE_URL được khai báo trong footer.php
                xhr = $.ajax({
                    url: BASE_URL + 'search/suggest',
                    data: { q: q },
                    dataType: 'json',
                    timeout: 10000
                }).done(function (res) {
                    memo[key] = res.items || [];    // lưu lại để lần sau khỏi gọi server
                    render(memo[key]);
                }).fail(function (x, status) {
                    if (status === 'abort') { return; }    // tự huỷ thì không báo lỗi
                    lastQuery = '';             // cho phép thử lại cùng từ khoá
                    if (x.status === 429) {
                        // Tạm dừng đúng số giây server báo (mặc định 10 giây)
                        var wait = ((x.responseJSON && x.responseJSON.retry_after) || 10) * 1000;
                        pausedUntil = Date.now() + wait;
                        showNotice('Searching too fast. Please wait a moment...');
                    }
                });
            }, 400);
        });

        // Bấm ra ngoài khu tìm kiếm -> ẩn danh sách gợi ý
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#search_form').length) { $results.hide(); }
        });
    }

});
