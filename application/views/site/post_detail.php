<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Chi tiết bài viết /news/<slug> - Blog::detail(). Nội dung, khung tác giả, ghi nguồn. Biến: $post, $other_posts.
?>
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
                        <?php // Dòng meta: ngày đăng + tên tác giả (bấm nhảy xuống khung tác giả #post_author) ?>
                        <div class="post_detail_meta">
                            <i class="fa fa-calendar"></i> <?php echo date('d/m/Y', $time); ?>
                            <?php if (!empty($post->author_name)): ?>
                                <span class="post_detail_meta_sep">&middot;</span>
                                <i class="fa fa-user"></i> <a href="#post_author"><?php echo html_escape($post->author_name); ?></a>
                            <?php endif; ?>
                        </div>

                        <?php // Ảnh bìa: upload_url trả '' khi không có ảnh -> không in thẻ img ?>
                        <?php if (upload_url('posts', $post->image) !== ''): ?>
                            <div class="post_detail_image">
                                <img src="<?php echo upload_url('posts', $post->image); ?>" alt="<?php echo html_escape($post->title); ?>">
                            </div>
                        <?php endif; ?>

                        <?php // Nội dung lưu dạng text thuần: html_escape chống chèn mã, nl2br đổi xuống dòng thành thẻ br ?>
                        <div class="post_detail_content">
                            <?php echo nl2br(html_escape($post->content)); ?>
                        </div>
                        <?php // ---- Khung tác giả "Written by" (chỉ khi bài có tác giả) ---- ?>
                        <?php if (!empty($post->author_name)): ?>
                            <?php
                            $author_avatar = upload_url('authors', $post->author_avatar);
                            $author_social = json_decode((string) $post->author_social_links, TRUE);
                            $author_social = is_array($author_social) ? $author_social : [];
                            // Icon Font Awesome 4.7 theo domain mạng xã hội, còn lại là website
                            $social_icon = function ($url) {
                                $host = strtolower((string) parse_url($url, PHP_URL_HOST));
                                foreach (['instagram' => 'fa-instagram', 'linkedin' => 'fa-linkedin', 'facebook' => 'fa-facebook',
                                          'twitter' => 'fa-twitter', 'x.com' => 'fa-twitter', 'youtube' => 'fa-youtube-play', 'pinterest' => 'fa-pinterest'] as $key => $icon) {
                                    if (strpos($host, $key) !== FALSE) {
                                        return $icon;
                                    }
                                }
                                return 'fa-globe';
                            };
                            ?>
                            <div class="post_author" id="post_author">
                                <div class="post_author_avatar">
                                    <?php if ($author_avatar !== ''): ?>
                                        <img src="<?php echo $author_avatar; ?>" alt="<?php echo html_escape($post->author_name); ?>">
                                    <?php else: ?>
                                        <span><?php echo html_escape(mb_strtoupper(mb_substr($post->author_name, 0, 1))); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="post_author_body">
                                    <div class="post_author_label">Written by</div>
                                    <div class="post_author_name"><?php echo html_escape($post->author_name); ?></div>
                                    <?php if (!empty($post->author_job_title)): ?>
                                        <div class="post_author_job"><?php echo html_escape($post->author_job_title); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($post->author_bio)): ?>
                                        <p class="post_author_bio"><?php echo html_escape($post->author_bio); ?></p>
                                    <?php endif; ?>
                                    <?php if ($author_social): ?>
                                        <div class="post_author_social">
                                            <?php foreach ($author_social as $link): ?>
                                                <?php if (is_string($link) && preg_match('#^https?://#i', $link)): ?>
                                                    <a href="<?php echo html_escape($link); ?>" target="_blank" rel="noopener noreferrer nofollow" aria-label="<?php echo html_escape(parse_url($link, PHP_URL_HOST)); ?>"><i class="fa <?php echo $social_icon($link); ?>"></i></a>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
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