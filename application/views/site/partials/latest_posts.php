<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php /** @var array $latest_posts */ ?>
<?php $sidebar_title = isset($sidebar_title) ? $sidebar_title : 'Latest Posts'; ?>
<?php if (!empty($latest_posts)): ?>
    <div class="sidebar_latest_posts">
        <div class="sidebar_title"><?php echo html_escape($sidebar_title); ?></div>
        <div class="latest_posts_container">
            <ul>
                <?php foreach ($latest_posts as $p): ?>
                    <?php
                    $p_image = upload_url('posts', $p->image, 'images/latest_1.jpg');
                    $p_link  = post_url($p);
                    ?>
                    <li class="latest_post clearfix">
                        <div class="latest_post_image">
                            <a href="<?php echo $p_link; ?>"><img src="<?php echo $p_image; ?>" alt=""></a>
                        </div>
                        <div class="latest_post_content">
                            <div class="latest_post_title trans_200"><a href="<?php echo $p_link; ?>"><?php echo html_escape($p->title); ?></a></div>
                            <div class="latest_post_meta">
                                <div class="latest_post_date trans_200"><a href="<?php echo $p_link; ?>"><?php echo date('d/m/Y', strtotime($p->created_at)); ?></a></div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>