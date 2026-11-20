<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
// ======================================================================
// BẢNG ĐỊNH TUYẾN CỦA DỰ ÁN
// Mỗi dòng: $route['<đường dẫn trên trình duyệt>'] = '<controller>/<hàm>/<tham số>';
//   (:num)            = một số (id)
//   ([a-zA-Z0-9-]+)   = một slug (chữ thường/hoa, số, dấu gạch ngang)
//   $1, $2            = giá trị bắt được ở nhóm thứ 1, 2 -> truyền vào hàm làm tham số
// URL không có trong bảng thì CI tự hiểu theo dạng /controller/hàm/tham-số
// (vd /crawler/brand/place -> controllers/crawler/Brand.php, hàm place()).
// Nếu 2 dòng trùng đường dẫn thì dòng khai báo SAU sẽ thắng.
// ======================================================================

// Trang chủ (http://travelvn.test/) -> controllers/Home.php, hàm index()
$route['default_controller'] = 'home';
// Không dùng trang 404 tuỳ chỉnh -> dùng trang 404 mặc định của CI
$route['404_override'] = '';
// Không tự đổi dấu "-" trong URL thành "_" khi tìm controller
$route['translate_uri_dashes'] = FALSE;


// ----------------------------------------------------------------------
// KHU VỰC QUẢN TRỊ: các controller này kế thừa Admin_Controller
// (core/MY_Controller.php) nên bắt buộc đăng nhập với quyền admin.
// Mẫu chung mỗi bảng: danh sách / phân trang (offset) / thêm / sửa / xoá (POST)
// ----------------------------------------------------------------------

// /admin -> mặc định mở danh sách địa điểm
$route['admin'] = 'places/index';
//Login: form đăng nhập (GET hiện form, POST kiểm tra tài khoản)
$route['login'] = 'login/index';

//Logout: xoá session rồi quay về trang login
$route['logout'] = 'logout/index';

//Admin Places (thành phố / địa điểm)
$route['admin/places'] = 'places/index';                              // danh sách, trang đầu
$route['admin/places/(:num)'] = 'places/index/$1';                    // danh sách, $1 = offset phân trang
$route['admin/places/add'] = 'places/add';                            // GET: form thêm, POST: lưu
$route['admin/places/editPlace/(:num)'] = 'places/editPlace/$1';      // GET: form sửa, POST: lưu
$route['admin/places/deletePlace/(:num)'] = 'places/deletePlace/$1';  // xoá (chỉ nhận POST)

//Admin Continents (châu lục)
$route['admin/continents'] = 'continents/index';
$route['admin/continents/(:num)'] = 'continents/index/$1';
$route['admin/continents/add'] = 'continents/add';
$route['admin/continents/editContinent/(:num)'] = 'continents/editContinent/$1';
$route['admin/continents/deleteContinent/(:num)'] = 'continents/deleteContinent/$1';

//Admin Countries (quốc gia)
$route['admin/countries'] = 'countries/index';
$route['admin/countries/(:num)'] = 'countries/index/$1';
$route['admin/countries/add'] = 'countries/add';
$route['admin/countries/editCountry/(:num)'] = 'countries/editCountry/$1';
$route['admin/countries/deleteCountry/(:num)'] = 'countries/deleteCountry/$1';

//Admin Categories (danh mục)
$route['admin/categories'] = 'categories/index';
$route['admin/categories/(:num)'] = 'categories/index/$1';
$route['admin/categories/add'] = 'categories/add';
$route['admin/categories/editCategory/(:num)'] = 'categories/editCategory/$1';
$route['admin/categories/deleteCategory/(:num)'] = 'categories/deleteCategory/$1';

//Admin Bookings (đơn đặt lịch)
$route['admin/bookings'] = 'bookings/index';
$route['admin/bookings/(:num)'] = 'bookings/index/$1';
$route['admin/bookings/updateStatus/(:num)'] = 'bookings/updateStatus/$1';   // đổi trạng thái Pending -> Confirmed...
$route['admin/bookings/deleteBooking/(:num)'] = 'bookings/deleteBooking/$1';
$route['admin/bookings/detail/(:num)'] = 'bookings/detail/$1';               // xem chi tiết 1 đơn

//Admin Hotels (khách sạn)
$route['admin/hotels'] = 'hotels/index';
$route['admin/hotels/(:num)'] = 'hotels/index/$1';
$route['admin/hotels/add'] = 'hotels/add';
$route['admin/hotels/editHotel/(:num)'] = 'hotels/editHotel/$1';
$route['admin/hotels/deleteHotel/(:num)'] = 'hotels/deleteHotel/$1';

//Admin Posts (bài viết / tin tức)
$route['admin/posts'] = 'posts/index';
$route['admin/posts/(:num)'] = 'posts/index/$1';
$route['admin/posts/add'] = 'posts/add';
$route['admin/posts/editPost/(:num)'] = 'posts/editPost/$1';
$route['admin/posts/deletePost/(:num)'] = 'posts/deletePost/$1';

//Admin reviews (đánh giá của khách)
$route['admin/reviews'] = 'reviews/index';
$route['admin/reviews/(:num)'] = 'reviews/index/$1';
$route['admin/reviews/setApproval/(:num)'] = 'reviews/setApproval/$1';   // duyệt / bỏ duyệt (chỉ đánh giá đã duyệt mới hiện ra site)
$route['admin/reviews/deleteReview/(:num)'] = 'reviews/deleteReview/$1';


// ----------------------------------------------------------------------
// TRANG CÔNG KHAI (khách xem): controller kế thừa Site_Controller
// ----------------------------------------------------------------------

//Home-Contact (route cũ - bị 2 dòng 'contact' ở cuối file ghi đè vì khai báo sau)
$route['contact'] = 'home/contact';
$route['contact/send'] = 'home/contact/send';

//Continents: continent/<slug> -> danh sách quốc gia của châu lục
$route['continent/([a-zA-Z0-9-]+)'] = 'home/continent/$1';

//Countries: country/<slug> -> danh sách thành phố của quốc gia
$route['country/([a-zA-Z0-9-]+)'] = 'home/country/$1';

//Details: place/<slug> -> trang chi tiết thành phố (kèm khách sạn, đánh giá)
$route['place/([a-zA-Z0-9-]+)'] = 'home/detail/$1';

//Hotels: hotel/<id>-<slug-ten> -> chi tiết khách sạn (thiếu / sai slug vẫn vào được, sẽ 301 về URL chuẩn)
$route['hotel/(:num)'] = 'home/hotel/$1';
$route['hotel/(:num)-([a-zA-Z0-9-]+)'] = 'home/hotel/$1/$2';

//Bookings: book/<slug-thành-phố> -> GET hiện form, POST lưu đơn, AJAX ?more=1 tải thêm khách sạn
$route['book/([a-zA-Z0-9-]+)'] = 'home/book/$1';

//Categories: link cũ category/<slug> -> 301 về trang chủ (để link cũ không bị 404)
$route['category/([a-zA-Z0-9-]+)'] = 'home/category/$1';

//Blogs/Posts: danh sách tin, phân trang, chi tiết bài
$route['news'] = 'blog/index';
$route['news/page/(:num)'] = 'blog/index/$1';
$route['news/([a-zA-Z0-9-]+)'] = 'blog/detail/$1';

//Reviews: viết đánh giá cho khách sạn, review/<id>-<slug-ten> (giống hotel/...)
$route['review/(:num)'] = 'home/review/$1';
$route['review/(:num)-([a-zA-Z0-9-]+)'] = 'home/review/$1/$2';

//About + Contact
$route['about']        = 'about/index';     // trang giới thiệu
$route['contact']      = 'contact/index';   // trang liên hệ (ghi đè route 'home/contact' phía trên)
$route['contact/send'] = 'contact/send';    // form liên hệ gửi về đây (POST thường hoặc AJAX)

//Search
$route['search/suggest'] = 'search/suggest';    // gợi ý khi đang gõ ô tìm kiếm (AJAX, trả JSON)
$route['search'] = 'search/index';              // trang kết quả tìm kiếm

// Crawler: không cần khai báo, CI tự map /crawler/brand/<hàm> -> controllers/crawler/Brand.php
// (index, place, country, continent, post, news, author)



