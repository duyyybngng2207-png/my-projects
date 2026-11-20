<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Trang châu lục /continent/<slug> - Home::continent(). Liệt kê các quốc gia đã có thành phố.
// Biến: $continent, $countries_list.
?>
<?php /** @var object $continent */ /** @var array $countries_list */ ?>
<?php $banner = upload_url('continents', $continent->image, 'images/about_background.jpg'); ?>

<div class="home">
    <div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo $banner; ?>"></div>
    <div class="home_content">
        <div class="home_title"><?php echo html_escape($continent->name); ?></div>
    </div>
</div>

<div class="offers page_body">
    <div class="container">

        <?php if (empty($countries_list)): ?>
            <div class="row">
                <div class="col text-center py-5">
                    <p>There are no destinations in this continent yet.</p>
                    <a href="<?php echo base_url(); ?>">&larr; Back to home page</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-1 temp_col"></div>
                <div class="col-lg-11">
                    <div class="offers_sorting_container">
                        <?php $n = count($countries_list); ?>
                        <div class="result_pill">
                            <span class="result_pill_chip"><i class="fa fa-flag" aria-hidden="true"></i><?php echo $n . ($n === 1 ? ' country' : ' countries'); ?></span>
                            <span class="result_pill_sep">in</span>
                            <span class="result_pill_chip is_main"><i class="fa fa-globe" aria-hidden="true"></i><?php echo html_escape($continent->name); ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="offers_grid">
                        <?php $this->load->view('site/partials/country_items', ['countries_list' => $countries_list]); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($continent->content)): ?>
            <div class="row">
                <div class="col-lg-1 temp_col"></div>
                <div class="col-lg-11">
                    <div class="country_about">
                        <h2>About <?php echo html_escape($continent->name); ?></h2>
                        <p><?php echo nl2br(html_escape($continent->content)); ?></p>
                        <?php $this->load->view('site/partials/source_credit', ['item' => $continent]); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
