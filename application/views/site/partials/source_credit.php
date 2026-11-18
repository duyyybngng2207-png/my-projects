<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/**
 * Dòng ghi nguồn cho nội dung crawl (yêu cầu của giấy phép CC BY-SA).
 * @var object $item  bản ghi places / posts có crawler_href, crawler_credit
 */
if (empty($item->crawler_credit) && empty($item->crawler_href)) {
    return;
}
?>
<div class="source_credit">
    <i class="fa fa-info-circle"></i>
    <?php echo html_escape((string) $item->crawler_credit); ?>
    <?php if (!empty($item->crawler_href)): ?>
        <a href="<?php echo html_escape($item->crawler_href); ?>" target="_blank" rel="noopener noreferrer">View original</a>
        &middot;
    <?php endif; ?>
    <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener noreferrer license">License</a>
</div>
