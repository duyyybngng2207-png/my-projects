<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/**
 * @var object $hotel
 * @var array  $places_list
 * @var string $upload_error
 * @var string $albums_text     albums (JSON) đã đổi thành mỗi dòng 1 URL
 * @var string $amenities_text  amenities (JSON) đã đổi thành mỗi dòng 1 tiện ích
 * @var string $faqs_text       faqs (JSON) đã đổi thành từng cặp dòng: câu hỏi / câu trả lời
 */
$trending = ($this->input->method() == 'post')
    ? ($this->input->post('is_trending') === '1')
    : ($hotel->is_trending == 1);
$image_url = upload_url('hotels', $hotel->image);
?>
<div class="container add-user">
    <h2>Chỉnh sửa khách sạn</h2>
    <hr>
    <?php echo form_open_multipart('admin/hotels/editHotel/' . $hotel->id); ?>

        <h5 class="mt-2 mb-3 text-primary">Thông tin chung</h5>
        <div class="row">
            <div class="col-md-6 pb-3">
                <label for="name" class="form-label">Tên khách sạn <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" maxlength="255"
                       value="<?php echo set_value('name', $hotel->name); ?>">
                <?php echo form_error('name'); ?>
            </div>
            <div class="col-md-6 pb-3">
                <label for="slug" class="form-label">Slug</label>
                <input type="text" name="slug" id="slug" class="form-control" maxlength="255"
                       value="<?php echo set_value('slug', $hotel->slug); ?>">
                <small class="text-muted">Đổi slug sẽ làm đổi đường dẫn khách sạn. Để trống sẽ tạo lại từ tên.</small>
                <?php echo form_error('slug'); ?>
            </div>
            <div class="col-md-4 pb-3">
                <label for="place_id" class="form-label">Thuộc địa điểm</label>
                <select name="place_id" id="place_id" class="form-select">
                    <option value="">-- Không thuộc địa điểm nào --</option>
                    <?php foreach ($places_list as $place): ?>
                        <option value="<?php echo $place->id; ?>" <?php echo set_select('place_id', (string) $place->id, ($hotel->place_id == $place->id)); ?>>
                            <?php echo html_escape($place->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php echo form_error('place_id'); ?>
            </div>
            <div class="col-md-5 pb-3">
                <label for="address" class="form-label">Địa chỉ</label>
                <input type="text" name="address" id="address" class="form-control" maxlength="255"
                       value="<?php echo set_value('address', $hotel->address); ?>">
                <?php echo form_error('address'); ?>
            </div>
            <div class="col-md-3 pb-3">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input type="text" name="phone" id="phone" class="form-control" maxlength="20"
                       value="<?php echo set_value('phone', $hotel->phone); ?>">
                <?php echo form_error('phone'); ?>
            </div>
        </div>

        <h5 class="mt-2 mb-3 text-primary">Giá &amp; đánh giá</h5>
        <div class="row">
            <div class="col-md-3 pb-3">
                <label for="price" class="form-label">Giá từ / đêm ($) <span class="text-danger">*</span></label>
                <input type="number" name="price" id="price" class="form-control" min="0" step="1"
                       value="<?php echo set_value('price', $hotel->price); ?>">
                <?php echo form_error('price'); ?>
            </div>
            <div class="col-md-3 pb-3">
                <label for="rating" class="form-label">Số sao</label>
                <select name="rating" id="rating" class="form-select">
                    <option value="">-- Chưa đánh giá --</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo set_select('rating', (string) $i, ($hotel->rating == $i)); ?>><?php echo $i; ?> sao</option>
                    <?php endfor; ?>
                </select>
                <?php echo form_error('rating'); ?>
            </div>
            <div class="col-md-3 pb-3">
                <label for="rating_count" class="form-label">Số lượt đánh giá</label>
                <input type="number" name="rating_count" id="rating_count" class="form-control" min="0" step="1"
                       value="<?php echo set_value('rating_count', $hotel->rating_count); ?>">
                <?php echo form_error('rating_count'); ?>
            </div>
            <div class="col-md-3 pb-3">
                <div class="form-check mt-md-4 pt-md-2">
                    <input type="checkbox" class="form-check-input" name="is_trending" id="is_trending" value="1"
                           <?php echo $trending ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_trending">Hiện ở mục "trending now" trang chủ</label>
                </div>
            </div>
        </div>

        <h5 class="mt-2 mb-3 text-primary">Hình ảnh</h5>
        <div class="row">
            <div class="col-md-6 pb-3">
                <label for="image" class="form-label">Ảnh tải lên</label>
                <input type="file" name="image" id="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                <small class="text-muted d-block">Không chọn file mới thì giữ ảnh cũ. JPG, PNG, WEBP, tối đa 2MB.</small>
                <?php echo $upload_error; ?>
                <?php if ($image_url !== ''): ?>
                    <img src="<?php echo $image_url; ?>" alt="" style="width:120px; margin-top:10px;" class="rounded border">
                <?php endif; ?>
            </div>
            <div class="col-md-6 pb-3">
                <label for="thumbnail" class="form-label">Ảnh đại diện (URL)</label>
                <input type="url" name="thumbnail" id="thumbnail" class="form-control" maxlength="255" placeholder="https://..."
                       value="<?php echo set_value('thumbnail', $hotel->thumbnail); ?>">
                <?php echo form_error('thumbnail'); ?>
                <?php if (!empty($hotel->thumbnail)): ?>
                    <img src="<?php echo html_escape($hotel->thumbnail); ?>" alt="" style="width:120px; margin-top:10px;" class="rounded border" referrerpolicy="no-referrer">
                <?php endif; ?>
            </div>
            <div class="col-12 pb-3">
                <label for="albums" class="form-label">Album ảnh (mỗi dòng 1 URL ảnh)</label>
                <textarea name="albums" id="albums" rows="4" class="form-control"><?php echo set_value('albums', $albums_text); ?></textarea>
                <?php echo form_error('albums'); ?>
            </div>
        </div>

        <h5 class="mt-2 mb-3 text-primary">Nội dung</h5>
        <div class="row">
            <div class="col-12 pb-3">
                <label for="description" class="form-label">Mô tả</label>
                <textarea name="description" id="description" rows="5" class="form-control"><?php echo set_value('description', $hotel->description); ?></textarea>
                <?php echo form_error('description'); ?>
            </div>
            <div class="col-md-6 pb-3">
                <label for="amenities" class="form-label">Tiện ích (mỗi dòng 1 tiện ích)</label>
                <textarea name="amenities" id="amenities" rows="6" class="form-control"><?php echo set_value('amenities', $amenities_text); ?></textarea>
                <?php echo form_error('amenities'); ?>
            </div>
            <div class="col-md-6 pb-3">
                <label for="faqs" class="form-label">Câu hỏi thường gặp</label>
                <textarea name="faqs" id="faqs" rows="12" class="form-control"><?php echo set_value('faqs', $faqs_text); ?></textarea>
                <?php echo form_error('faqs'); ?>
            </div>
        </div>

        <h5 class="mt-2 mb-3 text-primary">Nguồn dữ liệu</h5>
        <div class="row">
            <div class="col-12 pb-3">
                <label for="crawler_href" class="form-label">Link nguồn (crawler)</label>
                <input type="url" name="crawler_href" id="crawler_href" class="form-control" maxlength="255" placeholder="https://www.kayak.com/..."
                       value="<?php echo set_value('crawler_href', $hotel->crawler_href); ?>">
                <?php echo form_error('crawler_href'); ?>
            </div>
            <div class="col-12 pb-3 text-muted small">
                Tạo lúc: <?php echo date('d/m/Y H:i', strtotime($hotel->created_at)); ?>
                <?php if (!empty($hotel->updated_at)): ?>
                    &middot; Cập nhật lần cuối: <?php echo date('d/m/Y H:i', strtotime($hotel->updated_at)); ?>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Xác nhận</button>
        <a href="<?php echo base_url('admin/hotels'); ?>" class="btn btn-secondary">Quay lại</a>
    <?php echo form_close(); ?>
</div>
