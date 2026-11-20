<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Khối "What guests say" trên trang khách sạn (nạp trong site/hotel_detail.php).
// Dữ liệu: cột hotels.guest_insights do crawler (Brand::hotelInsights) lưu -> Home::hotel() json_decode thành $insights.
?>
<?php
/**
 * Tổng hợp đánh giá của khách trên KAYAK (cột hotels.guest_insights, crawl cùng khách sạn).
 * Đây là số liệu / tóm tắt của KAYAK, KHÔNG phải đánh giá của khách trên site (phần "Reviews" bên dưới).
 * @var array  $insights  xem database/migrations/2026-11-19_hotels_guest_insights.sql
 * @var object $hotel
 */
if (empty($insights) || !is_array($insights)) {
    return;
}
$list = function ($key) use ($insights) {
    return (isset($insights[$key]) && is_array($insights[$key])) ? $insights[$key] : [];
};
$histogram  = $list('histogram');
$categories = $list('categories');
$providers  = $list('providers');
$mentions   = $list('mentions');
$qa         = $list('qa');
$tips       = $list('tips');
$max_count  = $histogram ? max(array_map(function ($h) { return (int) ($h['count'] ?? 0); }, $histogram)) : 0;
?>
<div class="hotel_section guest_insights">
    <div class="reviews_title">What guests say</div>

    <div class="gi_top">
        <?php if (isset($insights['score'])): ?>
            <div class="gi_score_card">
                <div class="gi_score"><?php echo number_format((float) $insights['score'], 1); ?><small>/10</small></div>
                <div class="gi_score_label"><?php echo html_escape($insights['label'] ?? ''); ?></div>
                <?php if (!empty($insights['count'])): ?>
                    <div class="gi_score_count"><?php echo number_format((int) $insights['count']); ?> verified ratings</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($histogram): ?>
            <ul class="gi_histogram">
                <?php foreach ($histogram as $h): ?>
                    <?php $w = $max_count > 0 ? round((int) $h['count'] / $max_count * 100) : 0; ?>
                    <li>
                        <span class="gi_hist_label"><?php echo html_escape($h['label'] ?? ''); ?></span>
                        <span class="gi_hist_bar"><span style="width:<?php echo (int) $w; ?>%"></span></span>
                        <span class="gi_hist_count"><?php echo number_format((int) ($h['count'] ?? 0)); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <?php if ($categories): ?>
        <div class="gi_categories">
            <?php foreach ($categories as $c): ?>
                <?php $s = (float) ($c['score'] ?? 0); ?>
                <div class="gi_category">
                    <div class="gi_category_head">
                        <span><?php echo html_escape($c['label'] ?? ''); ?></span>
                        <strong><?php echo rtrim(rtrim(number_format($s, 1), '0'), '.'); ?></strong>
                    </div>
                    <div class="gi_category_bar"><span style="width:<?php echo (int) round(min($s, 10) * 10); ?>%"></span></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($insights['summary'])): ?>
        <div class="gi_summary">
            <div class="gi_summary_title"><i class="fa fa-quote-left"></i> Summary of reviews</div>
            <p><?php echo html_escape($insights['summary']); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($mentions): ?>
        <div class="gi_mentions">
            <span class="gi_mentions_label">Guests often mention:</span>
            <?php foreach ($mentions as $m): ?>
                <span class="gi_mention"><?php echo html_escape($m); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($qa || $tips): ?>
        <div class="gi_tabs">
            <?php if ($qa): ?>
                <div class="gi_block">
                    <div class="gi_block_title"><i class="fa fa-comments-o"></i> Q&amp;A from reviews</div>
                    <?php foreach ($qa as $i => $item): ?>
                        <details class="hotel_faq"<?php echo $i === 0 ? ' open' : ''; ?>>
                            <summary><?php echo html_escape($item['question'] ?? ''); ?></summary>
                            <p><?php echo html_escape($item['answer'] ?? ''); ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($tips): ?>
                <div class="gi_block">
                    <div class="gi_block_title"><i class="fa fa-lightbulb-o"></i> Tips from guests</div>
                    <ul class="gi_tips">
                        <?php foreach ($tips as $t): ?>
                            <li>
                                <strong><?php echo html_escape($t['title'] ?? ''); ?></strong>
                                <span><?php echo html_escape($t['text'] ?? ''); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($providers): ?>
        <div class="gi_providers">
            <span class="gi_mentions_label">Score by provider:</span>
            <?php foreach ($providers as $p): ?>
                <span class="gi_provider">
                    <strong><?php echo html_escape($p['name'] ?? ''); ?></strong>
                    <?php echo number_format((float) ($p['score'] ?? 0), 1); ?>/10
                    <?php if (!empty($p['reviews'])): ?>
                        <small>(<?php echo number_format((int) $p['reviews']); ?> review<?php echo (int) $p['reviews'] > 1 ? 's' : ''; ?>)</small>
                    <?php endif; ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="gi_disclaimer">
        <i class="fa fa-info-circle"></i>
        Ratings and AI-generated summaries from
        <?php if (!empty($hotel->crawler_href)): ?>
            <a href="<?php echo html_escape($hotel->crawler_href); ?>" target="_blank" rel="noopener noreferrer nofollow">KAYAK</a>.
        <?php else: ?>
            KAYAK.
        <?php endif; ?>
        AI can make mistakes. Reviews written by our own guests are shown below.
    </div>
</div>
