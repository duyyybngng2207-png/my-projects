<?php defined('BASEPATH') OR exit('No direct script access allowed'); 
// [VIEW] Phần cuối chung của MỌI trang công khai: giới thiệu, 3 bài viết mới ($footer_posts), 12 quốc gia ($footer_countries),
// thông tin liên hệ, rồi nạp JS chung + JS riêng của trang ($page_js) + js/site.js.
// $footer_posts / $footer_countries được nạp sẵn ở Site_Controller (core/MY_Controller.php).
?>
<?php /** @var array $places_list */ ?>
<?php
$footer_posts      = isset($footer_posts) ? $footer_posts : [];
$footer_countries  = isset($footer_countries) ? $footer_countries : [];
?>
<footer class="footer">
    <div class="container">
        <div class="row">

            <!-- Footer Column -->
            <div class="col-lg-3 footer_column">
                <div class="footer_col">
                    <div class="footer_content footer_about">
                        <div class="logo_container footer_logo">
                            <div class="logo"><a href="<?php echo base_url(); ?>"><img src="<?php echo base_url(); ?>images/logo.png" alt="">travelix</a></div>
                        </div>
                        <p class="footer_about_text">Thank you for placing your trust in us. We created this website to provide a simple, efficient, and reliable booking experience, helping you enjoy memorable trips at the best possible prices. We would greatly appreciate it if you could leave a positive review or feedback, as this helps us continue delivering the highest quality service.</p>
                        <!-- <ul class="footer_social_list">
                            <li class="footer_social_item"><a href="#"><i class="fa fa-pinterest"></i></a></li>
                            <li class="footer_social_item"><a href="#"><i class="fa fa-facebook-f"></i></a></li>
                            <li class="footer_social_item"><a href="#"><i class="fa fa-twitter"></i></a></li>
                            <li class="footer_social_item"><a href="#"><i class="fa fa-dribbble"></i></a></li>
                            <li class="footer_social_item"><a href="#"><i class="fa fa-behance"></i></a></li>
                        </ul> -->
                    </div>
                </div>
            </div>

            <!-- Footer Column -->
            <div class="col-lg-3 footer_column">
                <div class="footer_col">
                    <div class="footer_title">blog posts</div>
                    <div class="footer_content footer_blog">
                        <?php if (empty($footer_posts)): ?>
                            <p class="footer_about_text">There are no posts yet.</p>
                        <?php endif; ?>
                        <?php foreach ($footer_posts as $fp): ?>
                            <?php
                            $fp_image = upload_url('posts', $fp->image, 'images/footer_blog_1.jpg');
                            $fp_link  = post_url($fp);
                            ?>
                            <div class="footer_blog_item clearfix">
                                <div class="footer_blog_image"><a href="<?php echo $fp_link; ?>"><img src="<?php echo $fp_image; ?>" alt=""></a></div>
                                <div class="footer_blog_content">
                                    <div class="footer_blog_title"><a href="<?php echo $fp_link; ?>"><?php echo html_escape($fp->title); ?></a></div>
                                    <div class="footer_blog_date"><?php echo date('d/m/Y', strtotime($fp->created_at)); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Footer Column -->
            <div class="col-lg-3 footer_column">
                <div class="footer_col">
                    <div class="footer_title">countries</div>
                    <div class="footer_content footer_tags">
                        <ul class="tags_list clearfix">
                            <?php foreach ($footer_countries as $fc): ?>
                                <li class="tag_item"><a href="<?php echo country_url($fc); ?>"><?php echo html_escape($fc->name); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Footer Column -->
            <div class="col-lg-3 footer_column">
                <div class="footer_col">
                    <div class="footer_title">contact info</div>
                    <div class="footer_content footer_contact">
                        <ul class="contact_info_list">
                            <li class="contact_info_item d-flex flex-row">
                                <div><div class="contact_info_icon"><img src="<?php echo base_url(); ?>images/placeholder.svg" alt=""></div></div>
                                <div class="contact_info_text"></div>
                            </li>
                            <li class="contact_info_item d-flex flex-row">
                                <div><div class="contact_info_icon"><img src="<?php echo base_url(); ?>images/phone-call.svg" alt=""></div></div>
                                <div class="contact_info_text">0329452922</div>
                            </li>
                            <li class="contact_info_item d-flex flex-row">
                                <div><div class="contact_info_icon"><img src="<?php echo base_url(); ?>images/message.svg" alt=""></div></div>
                                <div class="contact_info_text"><a href="mailto:contactme@gmail.com?Subject=Hello" target="_top">duyyybngng2207@gmail.com</a></div>
                            </li>
                            <li class="contact_info_item d-flex flex-row">
                                <div><div class="contact_info_icon"><img src="<?php echo base_url(); ?>images/planet-earth.svg" alt=""></div></div>
                                <div class="contact_info_text"><a href="https://colorlib.com">www.colorlib.com</a></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</footer>

<!-- Copyright -->

<div class="copyright">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 order-lg-1 order-2  ">
                <div class="copyright_content d-flex flex-row align-items-center">
                    <div><!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | This template is made with <i class="fa fa-heart-o" aria-hidden="true"></i> by <a href="https://colorlib.com" target="_blank">Colorlib</a>
<!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. --></div>
                </div>
            </div>
            <div class="col-lg-9 order-lg-2 order-1">
                <div class="footer_nav_container d-flex flex-row align-items-center justify-content-lg-end">
                    <div class="footer_nav">
                        <ul class="footer_nav_list">
                            <li class="footer_nav_item"><a href="<?php echo base_url(); ?>">home</a></li>
                            <li class="footer_nav_item"><a href="about.html">about us</a></li>
                            <li class="footer_nav_item"><a href="<?php echo base_url('#offers'); ?>">offers</a></li>
                            <li class="footer_nav_item"><a href="<?php echo base_url('news'); ?>">news</a></li>
                            <li class="footer_nav_item"><a href="contact.html">contact</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<script src="<?php echo base_url(); ?>js/jquery-3.2.1.min.js"></script>
<script src="<?php echo base_url(); ?>styles/bootstrap4/popper.js"></script>
<script src="<?php echo base_url(); ?>styles/bootstrap4/bootstrap.min.js"></script>
<script src="<?php echo base_url(); ?>plugins/OwlCarousel2-2.2.1/owl.carousel.js"></script>
<script src="<?php echo base_url(); ?>plugins/easing/easing.js"></script>
<?php
$page_js = isset($page_js) ? $page_js : ['js/custom.js'];
foreach ($page_js as $js): ?>
<script src="<?php echo base_url($js); ?>"></script>
<script>var BASE_URL = '<?php echo base_url(); ?>';</script>
<?php endforeach; ?>
<script src="<?php echo base_url('js/site.js'); ?>"></script>
</body>

</html>