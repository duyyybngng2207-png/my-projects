<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Danh sách tin tức /news - Blog::index(). Biến: $posts_list (6 bài/trang), $latest_posts (sidebar), $pagination_links.
?>
<?php
/**
 * @var array $posts_list
 * @var array $latest_posts
 * @var string $pagination_links
 */
?>

<div class="home">
    <div class="home_background parallax-window" data-parallax="scroll" data-image-src="<?php echo base_url('images/blog_background.jpg'); ?>"></div>
    <div class="home_content">
        <div class="home_title">news</div>
    </div>
</div>

<div class="blog page_body">
    <div class="container">
        <div class="row">

            <!-- Danh sách bài viết -->
            <div class="col-lg-8">
                <div class="blog_post_container">

                    <?php if (empty($posts_list)): ?>
                        <p>There are no posts yet.</p>
                    <?php endif; ?>

                    <?php foreach ($posts_list as $item): ?>
                        <?php
                        $image = upload_url('posts', $item->image, 'images/blog_1.jpg');
                        $time  = strtotime($item->created_at);
                        $link  = post_url($item);
                        ?>
                        <div class="blog_post">
                            <div class="blog_post_image">
                                <img src="<?php echo $image; ?>" alt="<?php echo html_escape($item->title); ?>">
                                <div class="blog_post_date d-flex flex-column align-items-center justify-content-center">
                                    <div class="blog_post_day"><?php echo date('d', $time); ?></div>
                                    <div class="blog_post_month"><?php echo date('M, Y', $time); ?></div>
                                </div>
                            </div>
                            <div class="blog_post_title"><a href="<?php echo $link; ?>"><?php echo html_escape($item->title); ?></a></div>
                            <?php if (!empty($item->author_name)): ?>
                                <div class="blog_post_author"><i class="fa fa-user"></i> by <?php echo html_escape($item->author_name); ?></div>
                            <?php endif; ?>
                            <div class="blog_post_text">
                                <p><?php echo html_escape(mb_strimwidth(strip_tags((string) $item->content), 0, 300, '...', 'UTF-8')); ?></p>
                            </div>
                            <div class="blog_post_link"><a href="<?php echo $link; ?>">read more</a></div>
                        </div>
                    <?php endforeach; ?>

                </div>

                <div class="mt-4">
                    <?php echo $pagination_links; ?>
                </div>
            </div>

            <!-- Thanh bên -->
            <div class="col-lg-4 sidebar_col">
                <?php $this->load->view('site/partials/latest_posts', ['latest_posts' => $latest_posts]); ?>
            </div>

        </div>
    </div>
</div>