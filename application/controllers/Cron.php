<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tác vụ định kỳ, chỉ chạy từ dòng lệnh. Ví dụ crontab chạy lúc 00:05 mỗi ngày:
 *   5 0 * * * cd /duong-dan/travelvn && php index.php cron complete_bookings
 *
 * @property Bookings_model $Bookings_model
 */
class Cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) {
            show_404();
        }
    }

    /** Booking "Đã xác nhận" đã đến ngày đi -> "Đã hoàn thành". */
    public function complete_bookings()
    {
        $this->load->model('Bookings_model');
        $count = $this->Bookings_model->autoCompletePastBookings();
        echo date('Y-m-d H:i:s') . " - Đã chuyển {$count} booking sang 'Đã hoàn thành'." . PHP_EOL;
    }
}
