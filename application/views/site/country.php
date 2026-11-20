<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang quốc gia /country/<slug> - Home::country(). Liệt kê thành phố, có bộ lọc tên / số sao chạy AJAX (js/category_filter.js).
// Biến: $country, $places_list, $filters (bộ lọc đang chọn), $has_places.
?>
<?php /** @var object $country */ /** @var array $places_list */ /** @var array $filters */ /** @var bool $has_places */ ?>
<?php
$banner = upload_url('countries', $country->image, 'images/about_background.jpg');
?>

<?php // ---- Banner: ảnh quốc gia (chạy parallax) + tên quốc gia ---- ?>
<div class="home">
    <div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo $banner; ?>"></div>
    <div class="home_content">
        <div class="home_title"><?php echo html_escape($country->name); ?></div>
    </div>
</div>

<div class="offers page_body">
    <div class="container">

        <?php // ---- Breadcrumb: Châu lục > Quốc gia (chỉ khi quốc gia đã gắn châu lục) ---- ?>
        <?php if (!empty($country->continent_slug)): ?>
            <div class="row">
                <div class="col-lg-1 temp_col"></div>
                <div class="col-lg-11">
                    <?php $this->load->view('site/partials/breadcrumb', ['crumbs' => [
                        ['label' => $country->continent_name, 'url' => continent_url((object) ['slug' => $country->continent_slug]), 'icon' => 'fa-globe'],
                        ['label' => $country->name, 'icon' => 'fa-flag'],
                    ]]); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php // Quốc gia chưa có thành phố nào -> báo trống; có -> hiện bộ lọc + danh sách ?>
        <?php if (!$has_places): ?>
            <div class="row">
                <div class="col text-center py-5">
                    <p>There are no destinations in this country yet.</p>
                    <a href="<?php echo base_url(); ?>">&larr; Back to home page</a>
                </div>
            </div>
        <?php else: ?>
            <?php
            // Nhãn hiển thị cho lựa chọn hiện tại của từng bộ lọc
            $labels = [
                'name'  => ['' => 'name', 'asc' => 'name: A - Z', 'desc' => 'name: Z - A'],
                'stars' => ['' => 'stars', 1 => '1 star', 2 => '2 stars', 3 => '3 stars', 4 => '4 stars', 5 => '5 stars'],
            ];
            $current = [
                'name'  => $filters['name'],
                'stars' => $filters['stars'] ?: '',
            ];
            ?>
            <div class="row">
                <div class="col-lg-1 temp_col"></div>
                <div class="col-lg-11">
                    <?php // ---- Bộ lọc: 2 dropdown (tên, số sao). js/category_filter.js bắt click .ajax_filter_btn rồi gọi AJAX tới data-url ---- ?>
                    <div class="offers_sorting_container">
                        <ul class="offers_sorting" id="category_filters"
                            data-url="<?php echo country_url($country); ?>">
                            <?php foreach ($labels as $key => $options): ?>
                                <li>
                                    <span class="sorting_text" data-default="<?php echo $options['']; ?>"><?php echo $labels[$key][$current[$key]]; ?></span>
                                    <i class="fa fa-chevron-down"></i>
                                    <ul>
                                        <?php foreach ($options as $value => $label): ?>
                                            <li class="ajax_filter_btn" data-key="<?php echo $key; ?>" data-value="<?php echo $value; ?>">
                                                <span><?php echo $value === '' ? 'show all' : $label; ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php // Số kết quả (#category_total, JS cập nhật sau mỗi lần lọc) + link xoá bộ lọc (chỉ hiện khi đang lọc) ?>
                        <div class="category_filter_status mt-2">
                            <span id="category_total"><?php echo count($places_list); ?></span> destination(s)
                            <a href="#" id="category_reset" class="ml-3"<?php echo array_filter($current) ? '' : ' style="display:none"'; ?>>clear filters</a>
                        </div>
                    </div>
                </div>

                <?php // ---- Danh sách thành phố: lọc AJAX sẽ thay toàn bộ nội dung #category_grid bằng HTML server trả về ---- ?>
                <div class="col-lg-12">
                    <div class="offers_grid" id="category_grid">
                        <?php $this->load->view('site/partials/category_items', ['places_list' => $places_list]); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php // ---- Giới thiệu quốc gia (nội dung crawl từ FAQ của KAYAK) + dòng ghi nguồn ---- ?>
        <?php if (!empty($country->content)): ?>
            <div class="row">
                <div class="col-lg-1 temp_col"></div>
                <div class="col-lg-11">
                    <div class="country_about">
                        <h2>About <?php echo html_escape($country->name); ?></h2>
                        <p><?php echo nl2br(html_escape($country->content)); ?></p>
                        <?php $this->load->view('site/partials/source_credit', ['item' => $country]); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
