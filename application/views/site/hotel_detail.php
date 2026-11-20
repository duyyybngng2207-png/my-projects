<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang chi tiết khách sạn /hotel/<id>-<slug> - Home::hotel(). Breadcrumb, thông tin, album ảnh, mô tả, tiện ích, FAQ,
// khối "What guests say" (tổng hợp từ KAYAK), đánh giá của khách trên site, khách sạn khác cùng thành phố.
?>
<?php
/**
 * @var object      $hotel
 * @var object|null $place         Địa điểm của khách sạn (có thể null)
 * @var object|null $country       Quốc gia của địa điểm {slug, name} (có thể null)
 * @var object|null $continent     Châu lục của quốc gia {slug, name} (có thể null)
 * @var array       $other_hotels  Khách sạn khác cùng địa điểm
 * @var array       $albums        Danh sách URL ảnh (từ cột albums)
 * @var array       $amenities     Danh sách tiện ích (từ cột amenities)
 * @var array       $faqs          [['question' => ..., 'answer' => ...], ...] (từ cột faqs)
 * @var array       $reviews       Đánh giá đã duyệt của khách sạn
 * @var object      $review_stats  {total, avg_rating}
 */
$avatars = ['review_1.jpg', 'review_2.jpg'];
$image  = hotel_image_url($hotel, 'images/listing_hotel.jpg');
$rating = max(0, min(5, (int) $hotel->rating));
$rating_count = (int) $hotel->rating_count;
$phone_link   = preg_replace('/[^0-9+]/', '', (string) $hotel->phone);
?>

<!-- Banner -->
<div class="home">
    <div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo base_url('images/single_background.jpg'); ?>"></div>
    <div class="home_content">
        <div class="home_title">Hotels</div>
    </div>
</div>

<!-- Chi tiết khách sạn -->
<div class="listing page_body">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?php $this->load->view('site/partials/breadcrumb', ['crumbs' => [
                    $continent ? ['label' => $continent->name, 'url' => continent_url($continent), 'icon' => 'fa-globe'] : null,
                    $country   ? ['label' => $country->name,   'url' => country_url($country),     'icon' => 'fa-flag']  : null,
                    $place     ? ['label' => $place->name,     'url' => place_url($place),         'icon' => 'fa-map-marker'] : null,
                    ['label' => $hotel->name, 'icon' => 'fa-bed'],
                ]]); ?>
                <div class="single_listing">

                    <div class="hotel_info">

                        <!-- Tiêu đề + thông tin nhanh -->
                        <div class="hotel_title_container d-flex flex-lg-row flex-column">
                            <div class="hotel_title_content">
                                <h1 class="hotel_title"><?php echo html_escape($hotel->name); ?></h1>
                                <?php if ($rating > 0): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="rating_r rating_r_<?php echo $rating; ?> hotel_rating">
                                            <i></i><i></i><i></i><i></i><i></i>
                                        </div>
                                        <?php if ($rating_count > 0): ?>
                                            <span class="hotel_rating_count"><?php echo number_format($rating_count); ?> reviews</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($hotel->address)): ?>
                                    <div class="hotel_location"><i class="fa fa-map-marker"></i> <?php echo html_escape($hotel->address); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($hotel->phone)): ?>
                                    <div class="hotel_location"><i class="fa fa-phone"></i> <a href="tel:<?php echo html_escape($phone_link); ?>"><?php echo html_escape($hotel->phone); ?></a></div>
                                <?php endif; ?>
                                <div class="hotel_location">
                                    <i class="fa fa-tag"></i>
                                    <?php echo (int) $hotel->price > 0 ? 'Price: from $' . number_format((float) $hotel->price) . ' / night' : 'Price: contact us'; ?>
                                </div>
                                <?php if ($place): ?>
                                    <div class="hotel_location">
                                        <i class="fa fa-compass"></i> Location: <a href="<?php echo place_url($place); ?>"><?php echo html_escape($place->name); ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="hotel_title_button ml-lg-auto text-lg-right">
                                <?php if ($place): ?>
                                    <div class="button book_button trans_200"><a href="<?php echo hotel_book_url($place, $hotel); ?>">book<span></span><span></span><span></span></a></div>
                                <?php else: ?>
                                    <div class="mt-2"><a href="<?php echo base_url('contact'); ?>">Contact us to book</a></div>
                                <?php endif; ?>
                                <div class="mt-2"><a href="<?php echo review_url($hotel); ?>">Write a review</a></div>
                            </div>
                        </div>

                        <!-- Ảnh chính -->
                        <div class="hotel_image">
                            <img src="<?php echo html_escape($image); ?>" alt="<?php echo html_escape($hotel->name); ?>" referrerpolicy="no-referrer">
                            <?php if (!empty($hotel->is_trending)): ?>
                                <div class="hotel_review_container d-flex flex-column align-items-center justify-content-center">
                                    <div class="hotel_review">
                                        <div class="hotel_review_content">
                                            <div class="hotel_review_title">trending</div>
                                        </div>
                                        <?php if ($rating > 0): ?>
                                            <div class="hotel_review_rating text-center"><?php echo number_format($rating, 1); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Album ảnh: bấm để xem lớn (colorbox) -->
                        <?php if (!empty($albums)): ?>
                            <div class="hotel_album">
                                <?php foreach ($albums as $i => $photo): ?>
                                    <a href="<?php echo html_escape($photo); ?>" class="hotel_album_item" title="<?php echo html_escape($hotel->name) . ' - ' . ($i + 1); ?>">
                                        <img src="<?php echo html_escape($photo); ?>" alt="<?php echo html_escape($hotel->name); ?> photo <?php echo $i + 1; ?>" loading="lazy" referrerpolicy="no-referrer">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Mô tả -->
                        <div class="hotel_info_text">
                            <?php if (!empty($hotel->description)): ?>
                                <p><?php echo nl2br(html_escape($hotel->description)); ?></p>
                            <?php elseif ($place): ?>
                                <p>
                                    <?php echo html_escape($hotel->name); ?> is one of our recommended stays in
                                    <a href="<?php echo place_url($place); ?>"><?php echo html_escape($place->name); ?></a>.
                                    You can book this hotel together with your trip to <?php echo html_escape($place->name); ?>
                                    in a single booking request.
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Tiện ích -->
                        <?php
                        $amenity_list = array_values(array_filter(array_map(function ($a) {
                            return is_scalar($a) ? trim((string) $a) : '';
                        }, $amenities), 'strlen'));
                        $amenity_limit = 16;   // hiện trước 16 mục, còn lại bấm "Show all"
                        ?>
                        <?php if (!empty($amenity_list)): ?>
                            <div class="hotel_section">
                                <div class="reviews_title">Amenities <small class="hotel_amenities_total">(<?php echo count($amenity_list); ?>)</small></div>
                                <ul class="hotel_amenities" id="hotel_amenities">
                                    <?php foreach ($amenity_list as $i => $amenity): ?>
                                        <li<?php echo $i >= $amenity_limit ? ' class="is_extra"' : ''; ?>><i class="fa fa-check"></i> <?php echo html_escape($amenity); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (count($amenity_list) > $amenity_limit): ?>
                                    <button type="button" class="hotel_amenities_toggle" id="hotel_amenities_toggle"
                                            data-more="Show all <?php echo count($amenity_list); ?> amenities" data-less="Show less">
                                        Show all <?php echo count($amenity_list); ?> amenities
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Câu hỏi thường gặp -->
                        <?php if (!empty($faqs)): ?>
                            <div class="hotel_section">
                                <div class="reviews_title">Frequently asked questions</div>
                                <div class="hotel_faqs">
                                    <?php foreach ($faqs as $i => $faq): ?>
                                        <details class="hotel_faq"<?php echo $i === 0 ? ' open' : ''; ?>>
                                            <summary><?php echo html_escape($faq['question']); ?></summary>
                                            <?php if ($faq['answer'] !== ''): ?>
                                                <p><?php echo nl2br(html_escape($faq['answer'])); ?></p>
                                            <?php endif; ?>
                                        </details>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Tổng hợp đánh giá trên KAYAK (crawl) -->
                        <?php $this->load->view('site/partials/guest_insights', ['insights' => $insights, 'hotel' => $hotel]); ?>

                    </div>

                    <!-- Đánh giá của khách đã ở khách sạn (đã được admin duyệt) -->
                    <div class="reviews">
                        <div class="reviews_title">
                            Reviews
                            <?php if ((int) $review_stats->total > 0): ?>
                                <small class="text-muted" style="font-size:18px;">
                                    (<?php echo (int) $review_stats->total; ?> rating<?php echo (int) $review_stats->total > 1 ? 's' : ''; ?>, average <?php echo number_format((float) $review_stats->avg_rating, 1); ?>/5)
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="reviews_container">

                            <?php if (empty($reviews)): ?>
                                <p>There are no reviews for this hotel yet.</p>
                            <?php endif; ?>

                            <?php foreach ($reviews as $k => $rv): ?>
                                <div class="review">
                                    <div class="row">
                                        <div class="col-lg-1">
                                            <div class="review_image">
                                                <img src="<?php echo base_url('images/' . $avatars[$k % 2]); ?>" alt="">
                                            </div>
                                        </div>
                                        <div class="col-lg-11">
                                            <div class="review_content">
                                                <div class="review_title_container">
                                                    <div class="review_title"><?php echo str_repeat('★', (int) $rv->rating); ?></div>
                                                    <div class="review_rating"><?php echo number_format((int) $rv->rating, 1); ?></div>
                                                </div>
                                                <div class="review_text">
                                                    <p><?php echo nl2br(html_escape($rv->content)); ?></p>
                                                </div>
                                                <div class="review_name"><?php echo html_escape($rv->name); ?></div>
                                                <div class="review_date"><?php echo date('d/m/Y', strtotime($rv->created_at)); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <p class="mt-3"><a href="<?php echo review_url($hotel); ?>">Write your review</a></p>

                        </div>
                    </div>

                    <?php if (!empty($other_hotels)): ?>
                    <!-- Khách sạn khác cùng địa điểm -->
                    <div class="rooms">
                        <div class="reviews_title mb-5">Other hotels in <?php echo html_escape($place->name); ?></div>

                        <?php foreach ($other_hotels as $oh): ?>
                            <?php
                            $oh_image  = hotel_image_url($oh, 'images/trend_1.png');
                            $oh_rating = max(0, min(5, (int) $oh->rating));
                            ?>
                            <div class="room">
                                <div class="row">
                                    <div class="col-lg-2">
                                        <div class="room_image">
                                            <a href="<?php echo hotel_url($oh); ?>"><img src="<?php echo html_escape($oh_image); ?>" alt="<?php echo html_escape($oh->name); ?>" referrerpolicy="no-referrer"></a>
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="room_content">
                                            <div class="room_title"><a href="<?php echo hotel_url($oh); ?>"><?php echo html_escape($oh->name); ?></a></div>
                                            <?php if ($oh_rating > 0): ?>
                                                <div class="rating_r rating_r_<?php echo $oh_rating; ?> mt-1">
                                                    <i></i><i></i><i></i><i></i><i></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="room_price">From $<?php echo number_format((float) $oh->price); ?> / night</div>
                                            <?php if (!empty($oh->address)): ?>
                                                <div class="room_extra"><?php echo html_escape($oh->address); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 text-lg-right">
                                        <div class="room_button">
                                            <div class="button book_button trans_200"><a href="<?php echo hotel_book_url($place, $oh); ?>">book<span></span><span></span><span></span></a></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Album: bấm ảnh để xem lớn, có nút chuyển ảnh (plugin colorbox đã nạp ở footer)
document.addEventListener('DOMContentLoaded', function () {
    // Tiện ích: mở rộng / thu gọn danh sách
    var toggle = document.getElementById('hotel_amenities_toggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            var list = document.getElementById('hotel_amenities');
            var open = list.classList.toggle('is_open');
            toggle.textContent = open ? toggle.getAttribute('data-less') : toggle.getAttribute('data-more');
        });
    }

    if (window.jQuery && jQuery.fn.colorbox) {
        jQuery('.hotel_album_item').colorbox({ rel: 'hotel_album', maxWidth: '92%', maxHeight: '92%', photo: true });
    }
});
</script>
