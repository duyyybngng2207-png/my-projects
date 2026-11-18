$(function () {

    /* ========== Contact form (AJAX) ========== */
    var $form = $('#contact_form');
    if ($form.length) {
        var $msg = $('#contact_msg');
        var hideTimer = null;
        var sending = false;

        var showMessage = function (type, text) {
            clearTimeout(hideTimer);
            $msg.stop(true, true).hide()
                .attr('class', 'alert alert-' + type)
                .text(text)
                .fadeIn(300);
            hideTimer = setTimeout(function () { $msg.fadeOut(400); }, 4000);
        };

        $form.on('submit', function (e) {
            e.preventDefault();
            if (sending) { return; }            // chặn bấm liên tục khi đang gửi
            sending = true;

            var $btn = $form.find('button[type=submit]').prop('disabled', true);
            var cooldown = 0;

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                timeout: 15000
            }).done(function (res) {
                $form.find('input[name="' + res.csrf_name + '"]').val(res.csrf_hash);
                if (res.success) {
                    $form[0].reset();
                    cooldown = 10000;           // gửi xong: khoá nút 10 giây
                }
                showMessage(res.success ? 'success' : 'danger', res.message);
            }).fail(function (xhr) {
                var res = xhr.responseJSON || {};
                if (res.csrf_name) {
                    $form.find('input[name="' + res.csrf_name + '"]').val(res.csrf_hash);
                }
                if (xhr.status === 429) {
                    cooldown = (res.retry_after || 30) * 1000;
                    showMessage('warning', res.message || 'Too many requests. Please try again later.');
                } else {
                    showMessage('danger', 'Failed to send. Please reload the page and try again.');
                }
            }).always(function () {
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
    var $input   = $('#search_input');
    var $results = $('#search_results');
    if ($input.length) {
        var timer = null, xhr = null, lastQuery = '', pausedUntil = 0;
        var memo = {};                          // kết quả đã tìm: { 'tu khoa': [...] }

        $('#search_form').on('submit', function (e) { e.preventDefault(); });

        var render = function (items) {
            $results.empty();
            if (!items.length) {
                $results.append($('<div class="ls_empty">').text('No results found'));
            } else {
                $.each(items, function (_, it) {
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

        var showNotice = function (text) {
            $results.empty().append($('<div class="ls_empty">').text(text)).fadeIn(150);
        };

        $input.on('input', function () {
            var q = $.trim($input.val()).substring(0, 100);
            var key = q.toLowerCase();
            clearTimeout(timer);

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

            timer = setTimeout(function () {
                if (Date.now() < pausedUntil) {
                    showNotice('Searching too fast. Please wait a moment...');
                    return;
                }
                if (xhr) { xhr.abort(); }
                lastQuery = key;

                xhr = $.ajax({
                    url: BASE_URL + 'search/suggest',
                    data: { q: q },
                    dataType: 'json',
                    timeout: 10000
                }).done(function (res) {
                    memo[key] = res.items || [];
                    render(memo[key]);
                }).fail(function (x, status) {
                    if (status === 'abort') { return; }
                    lastQuery = '';             // cho phép thử lại cùng từ khoá
                    if (x.status === 429) {
                        var wait = ((x.responseJSON && x.responseJSON.retry_after) || 10) * 1000;
                        pausedUntil = Date.now() + wait;
                        showNotice('Searching too fast. Please wait a moment...');
                    }
                });
            }, 400);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#search_form').length) { $results.hide(); }
        });
    }

});
