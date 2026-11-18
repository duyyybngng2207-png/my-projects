<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php /** @var array $places_list Danh sách địa điểm đã lọc (dùng cho cả lần tải trang đầu và AJAX) */ ?>

<?php if (empty($places_list)): ?>
    <div class="offers_item offers_empty text-center py-5">
        <p>No locations match these filters.</p>
    </div>
<?php endif; ?>

<?php foreach ($places_list as $item): ?>
    <?php
    $image  = upload_url('places', $item->image, 'images/offer_1.jpg');
    $rating = max(0, min(5, (int) $item->rating));
    ?>
    <div class="offers_item rating_<?php echo $rating; ?>">
        <div class="row">
            <div class="col-lg-1 temp_col"></div>
            <div class="col-lg-3 col-1680-4">
                <div class="offers_image_container">
                    <div class="offers_image_background" style="<?php echo css_bg($image); ?>"></div>
                    <div class="offer_name"><a href="<?php echo place_url($item); ?>"><?php echo html_escape($item->name); ?></a></div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="offers_content">
                    <div class="rating_r rating_r_<?php echo $rating; ?> offers_rating" data-rating="<?php echo $rating; ?>">
                        <i></i><i></i><i></i><i></i><i></i>
                    </div>
                    <p class="offers_text"><?php echo html_escape(mb_strimwidth(strip_tags((string) $item->content), 0, 200, '...', 'UTF-8')); ?></p>
                    <div class="offers_icons">
                        <ul class="offers_icons_list">
                            <li class="offers_icons_item"><img src="<?php echo base_url('images/post.png'); ?>" alt=""></li>
                            <li class="offers_icons_item"><img src="<?php echo base_url('images/compass.png'); ?>" alt=""></li>
                            <li class="offers_icons_item"><img src="<?php echo base_url('images/bicycle.png'); ?>" alt=""></li>
                            <li class="offers_icons_item"><img src="<?php echo base_url('images/sailboat.png'); ?>" alt=""></li>
                        </ul>
                    </div>
                    <div class="button book_button"><a href="<?php echo book_url($item); ?>">book<span></span><span></span><span></span></a></div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
