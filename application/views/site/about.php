<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang giới thiệu /about - About::index(). Dựng lại theo template gốc (about_styles.css + about_custom.js).
?>
<?php
/**
 * Trang About us - cấu trúc theo template Travelix gốc (about_styles.css / about_custom.js).
 * @var array       $stats       ['years' => ['last','new'], 'items' => [[icon, type, x, y, color], ...]]
 * @var array       $milestones  [[icon, value, text], ...]
 * @var object|null $promo       châu lục cho dải quảng cáo (name, slug, min_price)
 */
?>

<!-- Home -->

<div class="home">
	<div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo base_url('images/about_background.jpg'); ?>"></div>
	<div class="home_content">
		<div class="home_title">about us</div>
	</div>
</div>

<!-- Intro -->

<div class="intro">
	<div class="container">
		<div class="row">
			<div class="col-lg-7">
				<div class="intro_image"><img src="<?php echo base_url('images/intro.png'); ?>" alt="Travel the world with us"></div>
			</div>
			<div class="col-lg-5">
				<div class="intro_content">
					<div class="intro_title">we have the best destinations</div>
					<p class="intro_text">From the islands of Southeast Asia to the cities of Europe and the beaches of the South Pacific, we bring together hand-picked destinations and hotels on every continent. Compare stays, read real guest reviews and book your next trip in just a few clicks &mdash; simple, transparent and at the best possible price.</p>
					<div class="button intro_button"><div class="button_bcg"></div><a href="<?php echo base_url('#offers'); ?>">explore now<span></span><span></span><span></span></a></div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Stats -->

<div class="stats">
	<div class="container">
		<div class="row">
			<div class="col text-center">
				<div class="section_title">years of experience</div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-10 offset-lg-1 text-center">
				<p class="stats_text">Every year more travelers plan their trips with us. Here is how our community has grown compared with last year &mdash; thank you for travelling with us.</p>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-10 offset-lg-1">
				<div class="stats_years d-flex flex-row align-items-center justify-content-between">
					<div class="stats_years_last"><?php echo (int) $stats['years']['last']; ?></div>
					<div class="stats_years_new float-right"><?php echo (int) $stats['years']['new']; ?></div>
				</div>
				<div class="stats_contents">

					<?php foreach ($stats['items'] as $item): ?>
						<?php $icon = base_url('images/' . $item['icon']); ?>
						<!-- Stats Item -->
						<div class="stats_item d-flex flex-md-row flex-column clearfix">
							<div class="stats_last order-md-1 order-3">
								<div class="stats_last_icon d-flex flex-column align-items-center justify-content-end">
									<img src="<?php echo $icon; ?>" alt="">
								</div>
								<div class="stats_last_content">
									<div class="stats_number"><?php echo number_format((int) $item['x']); ?></div>
									<div class="stats_type"><?php echo html_escape($item['type']); ?></div>
								</div>
							</div>
							<div class="stats_bar order-md-2 order-2" data-x="<?php echo (int) $item['x']; ?>" data-y="<?php echo (int) $item['y']; ?>" data-color="<?php echo html_escape($item['color']); ?>">
								<div class="stats_bar_perc">
									<div>
										<div class="stats_bar_value"></div>
									</div>
								</div>
							</div>
							<div class="stats_new order-md-3 order-1 text-right">
								<div class="stats_new_icon d-flex flex-column align-items-center justify-content-end">
									<img src="<?php echo $icon; ?>" alt="">
								</div>
								<div class="stats_new_content">
									<div class="stats_number"><?php echo number_format((int) $item['y']); ?></div>
									<div class="stats_type"><?php echo html_escape($item['type']); ?></div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>

				</div>
			</div>
		</div>
	</div>
</div>

<!-- Add -->

<div class="add">
	<div class="container">
		<div class="row">
			<div class="col">
				<div class="add_container">
					<div class="add_background" style="background-image:url(<?php echo base_url('images/add.jpg'); ?>)"></div>
					<div class="add_content">
						<h1 class="add_title"><?php echo html_escape($promo ? $promo->name : 'the world'); ?></h1>
						<?php if ($promo): ?>
							<div class="add_subtitle">From <span>$<?php echo number_format((float) $promo->min_price); ?></span></div>
						<?php endif; ?>
						<div class="button add_button"><div class="button_bcg"></div><a href="<?php echo $promo ? continent_url($promo) : base_url(); ?>">explore now<span></span><span></span><span></span></a></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Milestones -->

<div class="milestones">
	<div class="container">
		<div class="row">

			<?php foreach ($milestones as $m): ?>
				<!-- Milestone -->
				<div class="col-lg-3 milestone_col">
					<div class="milestone text-center">
						<div class="milestone_icon"><img src="<?php echo base_url('images/' . $m['icon']); ?>" alt=""></div>
						<div class="milestone_counter" data-end-value="<?php echo (int) $m['value']; ?>">0</div>
						<div class="milestone_text"><?php echo html_escape($m['text']); ?></div>
					</div>
				</div>
			<?php endforeach; ?>

		</div>
	</div>
</div>
