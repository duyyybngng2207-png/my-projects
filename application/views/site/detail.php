<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/** @var object $place 
 * @var array  $reviews
 * @var object $review_stats
*/
$image  = upload_url('places', $place->image, 'images/listing_hotel.jpg');
$rating = (int) $place->rating;
$avatars = ['review_1.jpg', 'review_2.jpg'];

?>

<!-- Banner -->
<div class="home">
    <div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo base_url('images/single_background.jpg'); ?>"></div>
    <div class="home_content">
        <div class="home_title"><?php echo html_escape($place->category_name ?? 'Destination'); ?></div>
    </div>
</div>

<!-- Chi tiết địa điểm -->
<div class="listing page_body">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="single_listing">

                    <div class="hotel_info">

                        <div class="hotel_title_container d-flex flex-lg-row flex-column">
                            <div class="hotel_title_content">
                                <h1 class="hotel_title"><?php echo html_escape($place->name); ?></h1>
                                <div class="rating_r rating_r_<?php echo $rating; ?> hotel_rating">
                                    <i></i><i></i><i></i><i></i><i></i>
                                </div>
                                <div class="hotel_location"><?php echo html_escape($place->address); ?></div>
                            </div>
                            <div class="hotel_title_button ml-lg-auto text-lg-right">
                                <div class="button book_button trans_200"><a href="<?php echo book_url($place); ?>">book<span></span><span></span><span></span></a></div>
                            </div>
                        </div>

                        <div class="hotel_image">
                            <img src="<?php echo $image; ?>" alt="<?php echo html_escape($place->name); ?>">
                        </div>

                        <div class="hotel_info_text">
                            <p><?php echo nl2br(html_escape((string) $place->content)); ?></p>
                            <?php $this->load->view('site/partials/source_credit', ['item' => $place]); ?>
                        </div>

                        <div class="hotel_info_tags">
                            <ul class="hotel_icons_list">
                                <li class="hotel_icons_item"><img src="<?php echo base_url('images/post.png'); ?>" alt=""></li>
                                <li class="hotel_icons_item"><img src="<?php echo base_url('images/compass.png'); ?>" alt=""></li>
                                <li class="hotel_icons_item"><img src="<?php echo base_url('images/bicycle.png'); ?>" alt=""></li>
                                <li class="hotel_icons_item"><img src="<?php echo base_url('images/sailboat.png'); ?>" alt=""></li>
                            </ul>
                        </div>

                    </div>

                    <?php if (!empty($hotels)): ?>
                    <!-- Khách sạn gợi ý thuộc địa điểm này -->
                    <div class="rooms suggested_hotels">
                        <div class="reviews_title mb-5">Suggested hotels in <?php echo html_escape($place->name); ?></div>

                        <?php foreach ($hotels as $hotel): ?>
                            <?php
                            $hotel_image  = hotel_image_url($hotel, 'images/trend_1.png');
                            $hotel_rating = max(0, min(5, (int) $hotel->rating));
                            ?>
                            <div class="room">
                                <div class="row">
                                    <div class="col-lg-2">
                                        <div class="room_image">
                                            <a href="<?php echo hotel_url($hotel); ?>"><img src="<?php echo $hotel_image; ?>" alt="<?php echo html_escape($hotel->name); ?>"></a>
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="room_content">
                                            <div class="room_title"><a href="<?php echo hotel_url($hotel); ?>"><?php echo html_escape($hotel->name); ?></a></div>
                                            <?php if ($hotel_rating > 0): ?>
                                                <div class="rating_r rating_r_<?php echo $hotel_rating; ?> mt-1">
                                                    <i></i><i></i><i></i><i></i><i></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="room_price">From $<?php echo number_format((float) $hotel->price); ?> / night</div>
                                            <?php if (!empty($hotel->address)): ?>
                                                <div class="room_extra"><?php echo html_escape($hotel->address); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($hotel->is_trending)): ?>
                                                <div class="room_text">Trending</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 text-lg-right">
                                        <div class="room_button">
                                            <div class="button book_button trans_200"><a href="<?php echo hotel_book_url($place, $hotel); ?>">book<span></span><span></span><span></span></a></div>
                                            <div class="mt-2"><a href="<?php echo hotel_url($hotel); ?>">View details</a></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="reviews">
                        <div class="reviews_title">
                            Reviews
                            <?php if ((int) $review_stats->total > 0): ?>
                                <small class="text-muted" style="font-size:18px;">
                                    (<?php echo (int) $review_stats->total; ?> rating, average <?php echo number_format((float) $review_stats->avg_rating, 1); ?>/5)
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="reviews_container">

                            <?php if (empty($reviews)): ?>
                                <p>There are no reviews for this location yet.</p>
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
                                                <div class="review_name">
                                                    <?php echo html_escape($rv->name); ?>
                                                    <?php /* Đánh giá viết cho khách sạn -> ghi rõ khách sạn nào */ ?>
                                                    &middot; <a href="<?php echo hotel_url((object) ['id' => $rv->hotel_id, 'slug' => $rv->hotel_slug, 'name' => $rv->hotel_name]); ?>"><?php echo html_escape($rv->hotel_name); ?></a>
                                                </div>
                                                <div class="review_date"><?php echo date('d/m/Y', strtotime($rv->created_at)); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <p class="mt-3 text-muted">Reviews are written for each hotel. Open a hotel above to write yours.</p>

                        </div>
                    </div>

                    <!-- Form đặt lịch sẽ nằm ở đây (bước tiếp theo) -->
                    <div class="rooms" id="booking"></div>

                </div>
            </div>
        </div>
    </div>
</div>