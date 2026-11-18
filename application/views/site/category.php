<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php /** @var object $category */ /** @var array $places_list */ /** @var array $filters */ /** @var bool $has_places */ ?>

<div class="home">
    <div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo base_url('images/about_background.jpg'); ?>"></div>
    <div class="home_content">
        <div class="home_title"><?php echo html_escape($category->title); ?></div>
    </div>
</div>

<div class="offers page_body">
    <div class="container">

        <?php if (!$has_places): ?>
            <div class="row">
                <div class="col text-center py-5">
                    <p>There are no locations in this category yet.</p>
                    <a href="<?php echo base_url(); ?>">&larr; Back to home page</a>
                </div>
            </div>
        <?php else: ?>
            <?php
            // Nhãn hiển thị cho lựa chọn hiện tại của từng bộ lọc
            $labels = [
                'name'  => ['' => 'name', 'asc' => 'name: A - Z', 'desc' => 'name: Z - A'],
                'stars' => ['' => 'stars', 1 => '1 star', 2 => '2 stars', 3 => '3 stars', 4 => '4 stars', 5 => '5 stars'],
            ];
            $current = [
                'name'  => $filters['name'],
                'stars' => $filters['stars'] ?: '',
            ];
            ?>
            <div class="row">
                <div class="col-lg-1 temp_col"></div>
                <div class="col-lg-11">
                    <div class="offers_sorting_container">
                        <ul class="offers_sorting" id="category_filters"
                            data-url="<?php echo category_url($category); ?>">
                            <?php foreach ($labels as $key => $options): ?>
                                <li>
                                    <span class="sorting_text" data-default="<?php echo $options['']; ?>"><?php echo $labels[$key][$current[$key]]; ?></span>
                                    <i class="fa fa-chevron-down"></i>
                                    <ul>
                                        <?php foreach ($options as $value => $label): ?>
                                            <li class="ajax_filter_btn" data-key="<?php echo $key; ?>" data-value="<?php echo $value; ?>">
                                                <span><?php echo $value === '' ? 'show all' : $label; ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="category_filter_status mt-2">
                            <span id="category_total"><?php echo count($places_list); ?></span> location(s)
                            <a href="#" id="category_reset" class="ml-3"<?php echo array_filter($current) ? '' : ' style="display:none"'; ?>>clear filters</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="offers_grid" id="category_grid">
                        <?php $this->load->view('site/partials/category_items', ['places_list' => $places_list]); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>