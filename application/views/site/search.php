<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang kết quả tìm kiếm /search?q=...&type=... - Search::index(). Biến: $q, $type (tab đang chọn), $items.
?>
<?php
/** @var string $q */
/** @var string $type */
/** @var array $items */
$labels = ['places' => 'Places', 'hotels' => 'Hotels', 'news' => 'News', 'countries' => 'Countries'];
?>

<div class="home">
    <div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo base_url('images/single_background.jpg'); ?>"></div>
    <div class="home_content">
        <div class="home_title">Search</div>
    </div>
</div>

<div class="listing page_body">
    <div class="container">

        <h4 class="mb-4">
            <?php echo html_escape($labels[$type]); ?> results for "<?php echo html_escape($q); ?>"
        </h4>

        <?php // 3 trường hợp: từ khoá quá ngắn / không có kết quả / có kết quả (lưới thẻ) ?>
        <?php if (mb_strlen($q) < 2): ?>
            <p>Please enter at least 2 characters.</p>

        <?php elseif (empty($items)): ?>
            <p>No results found.</p>

        <?php else: ?>
            <div class="row">
                <?php // Mọi loại kết quả có cùng dạng {title, text, price, url, image} (Search::index đã chuẩn hoá) ?>
                <?php foreach ($items as $it): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($it['image'])): ?>
                                <img src="<?php echo html_escape($it['image']); ?>" class="card-img-top" alt="" style="height:200px;object-fit:cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo html_escape($it['title']); ?></h5>
                                <?php if (!empty($it['text'])): ?>
                                    <p class="card-text"><?php echo html_escape($it['text']); ?></p>
                                <?php endif; ?>
                                <?php // Chỉ khách sạn có giá; loại khác price = null nên không hiện ?>
                                <?php if ($it['price'] !== null): ?>
                                    <p class="card-text"><strong>Price: $<?php echo number_format($it['price']); ?></strong></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($it['url'])): ?>
                                <div class="card-footer bg-white border-0">
                                    <a href="<?php echo html_escape($it['url']); ?>" class="btn btn-warning btn-sm">View details</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>