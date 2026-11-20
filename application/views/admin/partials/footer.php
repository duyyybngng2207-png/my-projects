<?php
// [VIEW] Phần cuối chung của mọi trang admin: đóng các thẻ mở ở header.php + nạp file JS.
// Controller admin gọi: load->view('admin/partials/footer') sau view nội dung.
?>
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <strong>Copyright &copy; 2026 Travel VietNam.</strong> All rights reserved.
    </footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="<?php echo base_url('assets/js/adminlte.js'); ?>"></script>

</body>
</html>