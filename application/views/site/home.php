<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] TRANG CHỦ - Home::index(). Các khối từ trên xuống: slider + form tìm kiếm, "Explore by continent" ($continents_list),
// slider nổi bật ($featured_list), "the best offers" ($places_list), đánh giá của khách ($testimonials),
// "trending now" ($trending_list).
?>
<?php
/**
 * @var array $places_list
 * @var array $continents_list
 * @var array $featured_list
 * @var array $trending_list
 */

// ----- Dữ liệu cho các khối lặp -----


$slide_count = 3;   // số slide ở banner đầu trang (cũng là số chấm bên dưới)

// [ảnh biểu tượng, nhãn] - dùng chung cho thanh tab và các khung tìm kiếm
$search_tabs = [
    ['placeholder.svg', 'Places',     'places',     'Search places...'],
    ['suitcase.png', 'Hotels',     'hotels',     'Search hotels...'],
    ['message.svg', 'News',        'news',       'Search news...'],
    ['island.png', 'Countries', 'countries', 'Search countries...'],
];

// [ảnh người, ảnh biểu tượng, chú thích ảnh]
// $testimonials = [
// 	['test_1.jpg', 'backpack.png', 'https://unsplash.com/@anniegray'],
// 	['test_2.jpg', 'island_t.png', 'https://unsplash.com/@tschax'],
// 	['test_3.jpg', 'kayak.png',    'https://unsplash.com/@seefromthesky'],
// 	['test_2.jpg', 'island_t.png', ''],
// 	['test_1.jpg', 'backpack.png', ''],
// 	['test_3.jpg', 'kayak.png',    ''],
// ];
?>

	<!-- Home -->

	<div class="home">
		<div class="home_slider_container">

			<div class="owl-carousel owl-theme home_slider">
				<?php for ($i = 1; $i <= $slide_count; $i++): ?>
					<div class="owl-item home_slider_item">
						<div class="home_slider_background" style="background-image:url(<?php echo base_url('images/home_slider.jpg'); ?>)"></div>
						<div class="home_slider_content text-center">
							<div class="home_slider_content_inner" data-animation-in="flipInX" data-animation-out="animate-out fadeOut">
								<h1>discover</h1>
								<h1>the world</h1>
								<div class="button home_slider_button"><div class="button_bcg"></div><a href="#">explore now<span></span><span></span><span></span></a></div>
							</div>
						</div>
					</div>
				<?php endfor; ?>
			</div>

			<?php $this->load->view('site/partials/slider_nav', ['p' => 'home', 'dir' => 'prev']); ?>
			<?php $this->load->view('site/partials/slider_nav', ['p' => 'home', 'dir' => 'next']); ?>

			<div class="home_slider_dots">
				<ul id="home_slider_custom_dots" class="home_slider_custom_dots">
					<?php for ($i = 1; $i <= $slide_count; $i++): ?>
						<li class="home_slider_custom_dot<?php echo ($i === 1) ? ' active' : ''; ?>"><div></div><?php echo sprintf('%02d', $i); ?>.</li>
					<?php endfor; ?>
				</ul>
			</div>

		</div>
	</div>

	<!-- Search -->

	<div class="search">
		<div class="container fill_height">
			<div class="row fill_height">
				<div class="col fill_height">

					<!-- Search Tabs -->
					<div class="search_tabs_container">
						<div class="search_tabs d-flex flex-lg-row flex-column align-items-lg-center align-items-start justify-content-lg-between justify-content-start">
							<?php foreach ($search_tabs as $i => $tab): ?>
								<?php $label = ($i === 0) ? '<span>' . $tab[1] . '</span>' : $tab[1]; ?>
								<div class="search_tab<?php echo ($i === 0) ? ' active' : ''; ?> d-flex flex-row align-items-center justify-content-lg-center justify-content-start"><img src="<?php echo base_url('images/' . $tab[0]); ?>" alt=""<?php echo (substr($tab[0], -4) === '.svg') ? ' style="width:auto;height:30px"' : ''; ?>><?php echo $label; ?></div>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Search Panels (mỗi tab một khung) -->
					<?php foreach ($search_tabs as $i => $tab): ?>
						<?php $n = $i + 1; ?>
						<div class="search_panel<?php echo ($i === 0) ? ' active' : ''; ?>">
							<form action="<?php echo base_url('search'); ?>" method="get" id="search_form_<?php echo $n; ?>" class="search_panel_content d-flex flex-lg-row flex-column align-items-lg-center align-items-start justify-content-lg-between justify-content-start">
								<input type="hidden" name="type" value="<?php echo html_escape($tab[2]); ?>">
								<div class="search_item keyword_item">
									<div>keyword</div>
									<input type="text" name="q" class="destination search_input" placeholder="<?php echo html_escape($tab[3]); ?>" required="required" minlength="2" maxlength="100">
								</div>
								<button type="submit" class="button search_button">search<span></span><span></span><span></span></button>
							</form>
						</div>
					<?php endforeach; ?>

				</div>
			</div>
		</div>
	</div>

	<!-- Intro -->

	<div class="intro">
		<div class="container">
			<div class="row">
				<div class="col">
					<h2 class="intro_title text-center">Explore by continent</h2>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-10 offset-lg-1">
					<div class="intro_text text-center">
						<p>Pick a continent, choose a country, then find the best destinations and hotels for your trip.</p>
					</div>
				</div>
			</div>

			<div class="row intro_items">
				<?php foreach ($continents_list as $continent): ?>
					<?php
					$cover = upload_url($continent->cover_folder, $continent->cover_image, 'images/intro_1.jpg');
					$total = (int) $continent->total_countries;
					?>
					<div class="col-lg-4 intro_col">
						<div class="intro_item">
							<div class="intro_item_overlay"></div>
							<div class="intro_item_background" style="<?php echo css_bg($cover); ?>"></div>
							<div class="intro_item_content d-flex flex-column align-items-center justify-content-center">
								<div class="intro_date"><?php echo $total > 0 ? $total . ($total === 1 ? ' country' : ' countries') : 'coming soon'; ?></div>
								<div class="button intro_button"><div class="button_bcg"></div><a href="<?php echo continent_url($continent); ?>">see more<span></span><span></span><span></span></a></div>
								<div class="intro_center text-center">
									<h1><?php echo html_escape($continent->name); ?></h1>
									<?php if ($continent->min_price !== null): ?>
										<div class="intro_price">From $<?php echo number_format((float) $continent->min_price); ?></div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<!-- CTA -->

	<?php if (!empty($featured_list)): ?>
		<div class="cta">
			<div class="cta_background" style="background-image:url(<?php echo base_url('images/cta.jpg'); ?>)"></div>
			<div class="container">
				<div class="row">
					<div class="col">
						<div class="cta_slider_container">

							<div class="owl-carousel owl-theme cta_slider">
								<?php foreach ($featured_list as $item): ?>
									<div class="owl-item cta_item text-center">
										<div class="cta_title"><?php echo html_escape($item->name); ?></div>
										<div class="rating_r rating_r_<?php echo (int) $item->rating; ?>">
											<i></i><i></i><i></i><i></i><i></i>
										</div>
										<p class="cta_text"><?php echo html_escape(mb_strimwidth(strip_tags((string) $item->content), 0, 300, '...', 'UTF-8')); ?></p>
										<div class="button cta_button"><div class="button_bcg"></div><a href="<?php echo book_url($item); ?>">book now<span></span><span></span><span></span></a></div>
									</div>
								<?php endforeach; ?>
							</div>
							<?php $this->load->view('site/partials/slider_nav', ['p' => 'cta', 'dir' => 'prev']); ?>
							<?php $this->load->view('site/partials/slider_nav', ['p' => 'cta', 'dir' => 'next']); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Offers -->

	<div class="offers" id="offers">
		<div class="container">
			<div class="row">
				<div class="col text-center">
					<h2 class="section_title">the best offers with rooms</h2>
				</div>
			</div>
			<div class="row offers_items">
				<?php foreach ($places_list as $item): ?> 
					<?php
					$image  = upload_url('places', $item->image, 'images/offer_1.jpg');  
					$rating = (int) $item->rating; 
					?>
					<div class="col-lg-6 offers_col">
						<div class="offers_item">
							<div class="row">
								<div class="col-lg-6">
									<div class="offers_image_container">
										<div class="offers_image_background" style="<?php echo css_bg($image); ?>"></div> 
										<div class="offer_name"><a href="<?php echo place_url($item); ?>"><?php echo html_escape($item->name); ?></a></div> 
									</div>
								</div>
								<div class="col-lg-6">
									<div class="offers_content">
										<div class="rating_r rating_r_<?php echo $rating; ?> offers_rating">
											<i></i><i></i><i></i><i></i><i></i>
										</div>
										<p class="offers_text"><?php echo html_escape(mb_strimwidth(strip_tags((string) $item->content), 0, 150, '...', 'UTF-8')); ?></p> 
										<div class="offers_icons">
											<ul class="offers_icons_list">
												<?php foreach (['post.png', 'compass.png', 'bicycle.png', 'sailboat.png'] as $icon): ?>
													<li class="offers_icons_item"><img src="<?php echo base_url('images/' . $icon); ?>" alt=""></li>
												<?php endforeach; ?>
											</ul>
										</div>
										<div class="offers_link"><a href="<?php echo place_url($item); ?>">read more</a></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<!-- Testimonials -->

	<div class="testimonials">
		<div class="test_border"></div>
		<div class="container">
			<div class="row">
				<div class="col text-center">
					<h2 class="section_title">what our clients say about us</h2>
				</div>
			</div>
			<div class="row">
				<div class="col">
					<div class="test_slider_container">
						<?php
						$avatars = [
							['test_1.jpg', 'backpack.png'],
							['test_2.jpg', 'island_t.png'],
							['test_3.jpg', 'kayak.png'],
						];
						?>
						<div class="owl-carousel owl-theme test_slider">
							<?php /** @var stdClass[] $testimonials */ ?>
							<?php foreach ($testimonials as $i => $t): ?>
								<?php $img = $avatars[$i % count($avatars)]; ?>
								<div class="owl-item">
									<div class="test_item">
										<div class="test_image"><img src="<?php echo base_url('images/' . $img[0]); ?>" alt="<?php echo html_escape($t->name); ?>"></div>
										<div class="test_icon"><img src="<?php echo base_url('images/' . $img[1]); ?>" alt=""></div>
										<div class="test_content_container">
											<div class="test_content">
												<div class="test_item_info">
													<div class="test_name"><?php echo html_escape($t->name); ?></div>
													<div class="test_date"><?php echo date('d/m/Y', strtotime($t->created_at)); ?></div>
												</div>
												<div class="test_quote_title"><?php echo str_repeat('★', (int) $t->rating); ?></div>
												<p class="test_quote_text"><?php echo html_escape($t->content); ?></p>
											</div>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<?php $this->load->view('site/partials/slider_nav', ['p' => 'test', 'dir' => 'prev']); ?>
						<?php $this->load->view('site/partials/slider_nav', ['p' => 'test', 'dir' => 'next']); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Trending -->

	<?php if (!empty($trending_list)): ?>
		<div class="trending">
			<div class="container">
				<div class="row">
					<div class="col text-center">
						<h2 class="section_title">trending now</h2>
					</div>
				</div>
				<div class="row trending_container">
					<?php foreach ($trending_list as $hotel): ?>
						<?php
						$hotel_image = hotel_image_url($hotel, 'images/trend_1.png');
						$hotel_link = ' href="' . hotel_url($hotel) . '"';
						?>
						<div class="col-lg-3 col-sm-6">
							<div class="trending_item clearfix">
								<div class="trending_image"><img src="<?php echo $hotel_image; ?>" alt="<?php echo html_escape($hotel->name); ?>"></div>
								<div class="trending_content">
									<div class="trending_title"><a<?php echo $hotel_link; ?>><?php echo html_escape($hotel->name); ?></a></div>
									<div class="trending_price">From $<?php echo number_format((float) $hotel->price); ?></div>
									<div class="trending_location"><?php echo html_escape($hotel->address ?? ''); ?></div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<!-- Contact -->

	<div class="contact">
		<div class="contact_background" style="background-image:url(<?php echo base_url('images/contact.png'); ?>)"></div>

		<div class="container">
			<div class="row">
				<div class="col-lg-5">
					<div class="contact_image"></div>
				</div>
				<div class="col-lg-7">
					<div class="contact_form_container">
						<div class="contact_title">get in touch</div>
						<div id="contact_msg" class="alert" style="display:none"></div>
						<?php echo form_open('contacts/send', ['id' => 'contact_form', 'class' => 'contact_form']); ?>
							<input type="text" name="name" id="contact_form_name" class="contact_form_name input_field" placeholder="Name" required="required" maxlength="255">
							<input type="email" name="email" id="contact_form_email" class="contact_form_email input_field" placeholder="E-mail" required="required" maxlength="255">
							<textarea name="message" id="contact_form_message" class="text_field contact_form_message" rows="4" placeholder="Message" required="required"></textarea>
							<button type="submit" id="form_submit_button" class="form_submit_button button">send message<span></span><span></span><span></span></button>
						<?php echo form_close(); ?>
					</div>
				</div>
			</div>
		</div>
	</div>