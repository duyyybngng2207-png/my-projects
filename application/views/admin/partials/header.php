<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php
// [VIEW] Phần đầu chung của MỌI trang admin: thẻ <head>, CSS (Bootstrap 5 / AdminLTE), menu bên trái, thanh trên cùng,
// và khung hiện flash message (success / error). Controller admin gọi: load->view('admin/partials/header').
?>
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Admin - Travel VietNam</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous" media="print" onload="this.media='all'" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="<?php echo base_url('assets/css/adminlte.css'); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/css/custom.css'); ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">

    <?php // ---- Thanh trên cùng: thông báo flash + menu tài khoản (đăng xuất) ---- ?>
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <?php // Thông báo do controller đặt bằng set_flashdata() trước khi redirect, chỉ hiện đúng 1 lần ?>
            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success">
                    <?php echo $this->session->flashdata('success'); ?>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger">
                    <?php echo $this->session->flashdata('error'); ?>
                </div>
            <?php endif; ?>
            <?php // Tên tài khoản lấy từ session (ghi lúc đăng nhập ở Login.php) ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <span class="d-none d-md-inline"><?php echo $this->session->userdata('username'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <li class="user-header text-bg-primary">
                            <p><?php echo $this->session->userdata('username'); ?></p>
                        </li>
                        <li class="user-footer">
                            <a href="<?php echo base_url('logout'); ?>" style="width:100%;" class="btn btn-default btn-flat float-end">Đăng xuất</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <?php // ---- Menu bên trái: mỗi mục là 1 link tới trang admin (route khai báo trong config/routes.php) ---- ?>
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="<?php echo base_url('admin'); ?>" class="brand-link">
                <span class="brand-text fw-light">Travel VietNam</span>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" aria-label="Main navigation" data-accordion="false" id="navigation">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon bi bi-globe"></i>
                            <p>Châu lục & Quốc gia<i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo base_url('admin/continents'); ?>" class="nav-link">
                                    <i class="nav-icon bi bi-circle"></i>
                                    <p>Danh sách châu lục</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="<?php echo base_url('admin/countries'); ?>" class="nav-link">
                                    <i class="nav-icon bi bi-circle"></i>
                                    <p>Danh sách quốc gia</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon bi bi-box-seam-fill"></i>
                            <p>Địa điểm<i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo base_url('admin/places'); ?>" class="nav-link">
                                    <i class="nav-icon bi bi-circle"></i>
                                    <p>Danh sách địa điểm</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="nav-icon bi bi-box-seam-fill"></i>
                            <p>Danh mục<i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="<?php echo base_url('admin/categories'); ?>" class="nav-link">
                                    <i class="nav-icon bi bi-circle"></i>
                                    <p>Danh sách danh mục</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo base_url('admin/bookings'); ?>" class="nav-link">
                            <i class="nav-icon bi bi-box-seam-fill"></i>
                            <p>Đặt lịch</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo base_url('admin/hotels'); ?>" class="nav-link">
                            <i class="nav-icon bi bi-building"></i>
                            <p>Khách sạn</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo base_url('admin/posts'); ?>" class="nav-link">
                            <i class="nav-icon bi bi-newspaper"></i>
                            <p>Bài viết</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo base_url('admin/reviews'); ?>" class="nav-link">
                            <i class="nav-icon bi bi-chat-square-text"></i>
                            <p>Đánh giá</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <?php // ---- Vùng nội dung: view của từng trang (vd admin/places/list) in tiếp vào đây, footer.php đóng thẻ ---- ?>
    <main class="app-main">
        <div class="app-content">
            <div class="container-fluid">