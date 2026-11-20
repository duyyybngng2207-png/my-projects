<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Form thêm khách sạn - Hotels::add(). Album / tiện ích nhập mỗi dòng 1 mục; FAQ nhập câu hỏi 1 dòng + trả lời dòng dưới
// (controller tự đổi sang JSON khi lưu). Biến: $places_list, $upload_error.
?>
<?php
/**
 * @var array  $places_list
 * @var string $upload_error
 */
?>
<div class="container add-user">
    <h2>Thêm mới khách sạn</h2>
    <hr>
    <?php // form_open_multipart: form có enctype="multipart/form-data" -> mới gửi được file ảnh lên server ?>
    <?php echo form_open_multipart('admin/hotels/add'); ?>

        <h5 class="mt-2 mb-3 text-primary">Thông tin chung</h5>
        <div class="row">
            <div class="col-md-6 pb-3">
                <label for="name" class="form-label">Tên khách sạn <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" maxlength="255" value="<?php echo set_value('name'); ?>">
                <?php echo form_error('name'); ?>
            </div>
            <div class="col-md-6 pb-3">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" name="slug" id="slug" class="form-control" maxlength="255" value="<?php echo set_value('slug'); ?>">
                <small class="text-muted">Để trống sẽ tự tạo từ tên. Trùng sẽ tự thêm -2, -3...</small>
                <?php echo form_error('slug'); ?>
            </div>
            <div class="col-md-4 pb-3">
                <label for="place_id" class="form-label">Thuộc địa điểm</label>
                <select name="place_id" id="place_id" class="form-select">
                    <option value="">-- Không thuộc địa điểm nào --</option>
                    <?php foreach ($places_list as $place): ?>
                        <option value="<?php echo $place->id; ?>" <?php echo set_select('place_id', (string) $place->id); ?>>
                            <?php echo html_escape($place->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo form_error('place_id'); ?>
            </div>
            <div class="col-md-5 pb-3">
                <label for="address" class="form-label">Địa chỉ</label>
                <input type="text" name="address" id="address" class="form-control" maxlength="255" value="<?php echo set_value('address'); ?>">
                <?php echo form_error('address'); ?>
            </div>
            <div class="col-md-3 pb-3">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input type="text" name="phone" id="phone" class="form-control" maxlength="20" value="<?php echo set_value('phone'); ?>">
                <?php echo form_error('phone'); ?>
            </div>
        </div>

        <h5 class="mt-2 mb-3 text-primary">Giá &amp; đánh giá</h5>
        <div class="row">
            <div class="col-md-3 pb-3">
                <label for="price" class="form-label">Giá từ / đêm ($) <span class="text-danger">*</span></label>
                <input type="number" name="price" id="price" class="form-control" min="0" step="1" value="<?php echo set_value('price'); ?>">
                <?php echo form_error('price'); ?>
            </div>
            <div class="col-md-3 pb-3">
                <label for="rating" class="form-label">Số sao</label>
                <select name="rating" id="rating" class="form-select">
                    <option value="">-- Chưa đánh giá --</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo set_select('rating', (string) $i); ?>><?php echo $i; ?> sao</option>
                    <?php endfor; ?>
                </select>
                <?php echo form_error('rating'); ?>
            </div>
            <div class="col-md-3 pb-3">
                <label for="rating_count" class="form-label">Số lượt đánh giá</label>
                <input type="number" name="rating_count" id="rating_count" class="form-control" min="0" step="1" value="<?php echo set_value('rating_count'); ?>">
                <?php echo form_error('rating_count'); ?>
            </div>
            <div class="col-md-3 pb-3">
                <div class="form-check mt-md-4 pt-md-2">
                    <input type="checkbox" class="form-check-input" name="is_trending" id="is_trending" value="1"
                           <?php echo ($this->input->post('is_trending') === '1') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_trending">Hiện ở mục "trending now" trang chủ</label>
                </div>
            </div>
        </div>

        <h5 class="mt-2 mb-3 text-primary">Hình ảnh</h5>
        <div class="row">
            <div class="col-md-6 pb-3">
                <label for="image" class="form-label">Ảnh tải lên</label>
                <input type="file" name="image" id="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                <small class="text-muted">JPG, PNG, WEBP, tối đa 2MB. Được ưu tiên hiển thị hơn ảnh đại diện (URL).</small>
                <?php echo $upload_error; ?>
            </div>
            <div class="col-md-6 pb-3">
                <label for="thumbnail" class="form-label">Ảnh đại diện (URL)</label>
                <input type="url" name="thumbnail" id="thumbnail" class="form-control" maxlength="255" placeholder="https://..." value="<?php echo set_value('thumbnail'); ?>">
                <?php echo form_error('thumbnail'); ?>
            </div>
            <div class="col-12 pb-3">
                <label for="albums" class="form-label">Album ảnh (mỗi dòng 1 URL ảnh)</label>
                <textarea name="albums" id="albums" rows="4" class="form-control" placeholder="https://.../anh-1.jpg&#10;https://.../anh-2.jpg"><?php echo set_value('albums'); ?></textarea>
                <?php echo form_error('albums'); ?>
            </div>
        </div>

        <h5 class="mt-2 mb-3 text-primary">Nội dung</h5>
        <div class="row">
            <div class="col-12 pb-3">
                <label for="description" class="form-label">Mô tả</label>
                <textarea name="description" id="description" rows="5" class="form-control"><?php echo set_value('description'); ?></textarea>
                <?php echo form_error('description'); ?>
            </div>
            <div class="col-md-6 pb-3">
                <label for="amenities" class="form-label">Tiện ích (mỗi dòng 1 tiện ích)</label>
                <textarea name="amenities" id="amenities" rows="6" class="form-control" placeholder="Free WiFi&#10;Swimming pool&#10;Airport shuttle"><?php echo set_value('amenities'); ?></textarea>
                <?php echo form_error('amenities'); ?>
            </div>
            <div class="col-md-6 pb-3">
                <label for="faqs" class="form-label">Câu hỏi thường gặp (câu hỏi 1 dòng, câu trả lời ở dòng dưới, cách nhau 1 dòng trống)</label>
                <textarea name="faqs" id="faqs" rows="12" class="form-control" placeholder="What time is check-in?&#10;Check-in is from 2:00 PM.&#10;&#10;Is there free Wi-Fi?&#10;Yes, Wi-Fi is free."><?php echo set_value('faqs'); ?></textarea>
                <?php echo form_error('faqs'); ?>
            </div>
        </div>

        <h5 class="mt-2 mb-3 text-primary">Nguồn dữ liệu</h5>
        <div class="row">
            <div class="col-12 pb-3">
                <label for="crawler_href" class="form-label">Link nguồn (crawler)</label>
                <input type="url" name="crawler_href" id="crawler_href" class="form-control" maxlength="255" placeholder="https://www.kayak.com/..." value="<?php echo set_value('crawler_href'); ?>">
                <?php echo form_error('crawler_href'); ?>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/hotels'); ?>" class="btn btn-secondary">Quay lại</a>
    <?php echo form_close(); ?>
</div>

<script>
    // Tự điền slug theo tên (khi người dùng chưa tự sửa ô slug)
    (function () {
        var name = document.getElementById('name');
        var slug = document.getElementById('slug');
        var touched = slug.value !== '';

        function createSlug(str) {
            return str.toLowerCase()
                .normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/đ/g, 'd')
                .replace(/[^a-z0-9\s-]/g, '')
                .trim().replace(/\s+/g, '-').replace(/-+/g, '-');
        }

        slug.addEventListener('input', function () { touched = slug.value !== ''; });
        name.addEventListener('input', function () {
            if (!touched) { slug.value = createSlug(name.value); }
        });
    })();
</script>
