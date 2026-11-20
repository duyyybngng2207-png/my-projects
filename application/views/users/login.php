<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Form đăng nhập trang quản trị - Login::index() (URL /login). Gửi POST username/password về chính /login.
// Sai tài khoản -> controller đặt flashdata 'error' rồi tải lại trang này để hiện lỗi.
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đăng nhập hệ thống</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous" media="print" onload="this.media='all'" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo base_url('assets/css/adminlte.css'); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/css/custom.css'); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/css/login.css'); ?>" />
</head>
<body class="bg-body-tertiary">

<section class="vh-100 d-flex align-items-center justify-content-center">
    <div class="col-11 col-md-8 col-lg-5 col-xl-4">

        <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger">
                <?php echo $this->session->flashdata('error'); ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="fw-normal mb-4">Đăng nhập hệ thống</h2>

                <?php echo form_open('login'); ?>
                    <div class="mb-4">
                        <input type="text" name="username" class="form-control form-control-lg"
                               placeholder="Tên đăng nhập" required />
                    </div>
                    <div class="mb-4">
                        <input type="password" name="password" class="form-control form-control-lg"
                               placeholder="Nhập mật khẩu" required />
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">Đăng nhập</button>
                <?php echo form_close(); ?>
            </div>
        </div>

    </div>
</section>

</body>
</html>