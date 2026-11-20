<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Thanh breadcrumb dạng nhãn (Châu lục > Quốc gia > Thành phố > Khách sạn), dùng ở trang quốc gia / thành phố / khách sạn.
?>
<?php
/**
 * Breadcrumb dạng nhãn: [🌐 Asia] › [🚩 Vietnam] › [📍 Phu Quoc] (› [🛏 Khách sạn]).
 * @var array $crumbs [['label' => 'Asia', 'url' => '...', 'icon' => 'fa-globe'], ..., ['label' => 'Trang hiện tại', 'icon' => '...']]
 *                    Mục cuối là trang hiện tại (không có link). NULL trong mảng được bỏ qua.
 *                    icon: class Font Awesome 4.7 (châu lục fa-globe, quốc gia fa-flag, thành phố fa-map-marker, khách sạn fa-bed).
 */
$crumbs = isset($crumbs) ? array_values(array_filter($crumbs)) : [];
// Chỉ 1 mục (không có cấp trên) thì không cần breadcrumb
if (count($crumbs) < 2) {
    return;
}
?>
<nav class="site_breadcrumb" aria-label="breadcrumb">
    <ol>
        <?php foreach ($crumbs as $i => $crumb): ?>
            <?php
            $is_last = ($i === count($crumbs) - 1);
            $icon    = !empty($crumb['icon']) ? $crumb['icon'] : 'fa-map-marker';
            $inner   = '<i class="fa ' . html_escape($icon) . '" aria-hidden="true"></i><span>' . html_escape($crumb['label']) . '</span>';
            ?>
            <?php if ($i > 0): ?>
                <li class="site_breadcrumb_sep" aria-hidden="true"><i class="fa fa-angle-right"></i></li>
            <?php endif; ?>
            <li class="site_breadcrumb_item<?php echo $is_last ? ' is_current' : ''; ?>"<?php echo $is_last ? ' aria-current="page"' : ''; ?>>
                <?php if (!empty($crumb['url']) && !$is_last): ?>
                    <a href="<?php echo $crumb['url']; ?>"><?php echo $inner; ?></a>
                <?php else: ?>
                    <span class="site_breadcrumb_label"><?php echo $inner; ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
