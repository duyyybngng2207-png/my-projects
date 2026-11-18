<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
/**
 * @var object $post
 * @var array $other_posts
 */
$time = strtotime($post->created_at);
?>

<div class="home">
    <div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo base_url('images/single_background.jpg'); ?>"></div>
    <div class="home_content">
        <div class="home_title">article</div>
    </div>
</div>

<div class="blog page_body">
    <div class="container">
        <div class="row">

            <div class="col-lg-8">
                <div class="blog_post_container">
                    <div class="blog_post">

                        <div class="post_detail_back"><a href="<?php echo base_url('news'); ?>">&larr; All news</a></div>

                        <h1 class="post_detail_title"><?php echo html_escape($post->title); ?></h1>
                        <div class="post_detail_meta"><i class="fa fa-calendar"></i> <?php echo date('d/m/Y', $time); ?></div>

                        <?php if (upload_url('posts', $post->image) !== ''): ?>
                            <div class="post_detail_image">
                                <img src="<?php echo upload_url('posts', $post->image); ?>" alt="<?php echo html_escape($post->title); ?>">
                            </div>
                        <?php endif; ?>

                        <div class="post_detail_content">
                            <?php echo nl2br(html_escape($post->content)); ?>
                        </div>
                        <?php $this->load->view('site/partials/source_credit', ['item' => $post]); ?>

                    </div>
                </div>
            </div>

            <div class="col-lg-4 sidebar_col">
                <?php $this->load->view('site/partials/latest_posts', [
                    'latest_posts'  => $other_posts,
                    'sidebar_title' => 'Other posts'
                ]); ?>
            </div>

        </div>
    </div>
</div>