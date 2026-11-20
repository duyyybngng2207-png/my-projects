<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Danh sách thẻ quốc gia trên trang châu lục (site/continent.php).
?>
<?php /** @var array $countries_list Quốc gia kèm total_places, min_price, cover_image/cover_folder (Countries_model::getCountriesWithStats) */ ?>

<?php foreach ($countries_list as $item): ?>
    <?php
    $image = upload_url($item->cover_folder, $item->cover_image, 'images/offer_1.jpg');
    $total = (int) $item->total_places;
    ?>
    <div class="offers_item">
        <div class="row">
            <div class="col-lg-1 temp_col"></div>
            <div class="col-lg-3 col-1680-4">
                <div class="offers_image_container">
                    <div class="offers_image_background" style="<?php echo css_bg($image); ?>"></div>
                    <div class="offer_name"><a href="<?php echo country_url($item); ?>"><?php echo html_escape($item->name); ?></a></div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="offers_content">
                    <div class="offers_price country_stats">
                        <?php echo $total . ($total === 1 ? ' destination' : ' destinations'); ?>
                        <?php if ($item->min_price !== null): ?>
                            <span>&middot; hotels from $<?php echo number_format((float) $item->min_price); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="offers_text"><?php echo html_escape(mb_strimwidth(strip_tags((string) $item->content), 0, 220, '...', 'UTF-8')); ?></p>
                    <div class="button book_button"><a href="<?php echo country_url($item); ?>">explore<span></span><span></span><span></span></a></div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
