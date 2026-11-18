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
$route['default_controller'] = 'home';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;


$route['admin'] = 'places/index';
//Login
$route['login'] = 'login/index';

//Logout
$route['logout'] = 'logout/index';

//Admin Places
$route['admin/places'] = 'places/index';
$route['admin/places/(:num)'] = 'places/index/$1';
$route['admin/places/add'] = 'places/add';
$route['admin/places/editPlace/(:num)'] = 'places/editPlace/$1';
$route['admin/places/deletePlace/(:num)'] = 'places/deletePlace/$1'; 

//Admin Categories
$route['admin/categories'] = 'categories/index';
$route['admin/categories/(:num)'] = 'categories/index/$1';
$route['admin/categories/add'] = 'categories/add';
$route['admin/categories/editCategory/(:num)'] = 'categories/editCategory/$1';
$route['admin/categories/deleteCategory/(:num)'] = 'categories/deleteCategory/$1';

//Admin Bookings
$route['admin/bookings'] = 'bookings/index';
$route['admin/bookings/(:num)'] = 'bookings/index/$1';
$route['admin/bookings/updateStatus/(:num)'] = 'bookings/updateStatus/$1';
$route['admin/bookings/deleteBooking/(:num)'] = 'bookings/deleteBooking/$1';
$route['admin/bookings/detail/(:num)'] = 'bookings/detail/$1';

//Admin Hotels 
$route['admin/hotels'] = 'hotels/index';
$route['admin/hotels/(:num)'] = 'hotels/index/$1';
$route['admin/hotels/add'] = 'hotels/add';
$route['admin/hotels/editHotel/(:num)'] = 'hotels/editHotel/$1';
$route['admin/hotels/deleteHotel/(:num)'] = 'hotels/deleteHotel/$1';

//Admin Posts
$route['admin/posts'] = 'posts/index';
$route['admin/posts/(:num)'] = 'posts/index/$1';
$route['admin/posts/add'] = 'posts/add';
$route['admin/posts/editPost/(:num)'] = 'posts/editPost/$1';
$route['admin/posts/deletePost/(:num)'] = 'posts/deletePost/$1';

//Adminb reviews 
$route['admin/reviews'] = 'reviews/index';
$route['admin/reviews/(:num)'] = 'reviews/index/$1';
$route['admin/reviews/setApproval/(:num)'] = 'reviews/setApproval/$1';
$route['admin/reviews/deleteReview/(:num)'] = 'reviews/deleteReview/$1';

//Home-Contact
$route['contact'] = 'home/contact';
$route['contact/send'] = 'home/contact/send';

//Details
$route['place/([a-zA-Z0-9-]+)'] = 'home/detail/$1';

//Hotels: hotel/<id>-<slug-ten>
$route['hotel/(:num)'] = 'home/hotel/$1';
$route['hotel/(:num)-([a-zA-Z0-9-]+)'] = 'home/hotel/$1/$2';

//Bookings
$route['book/([a-zA-Z0-9-]+)'] = 'home/book/$1';

//Categories
$route['category/([a-zA-Z0-9-]+)'] = 'home/category/$1';

//Blogs/Posts
$route['news'] = 'blog/index';
$route['news/page/(:num)'] = 'blog/index/$1';
$route['news/([a-zA-Z0-9-]+)'] = 'blog/detail/$1';

//Reviews: viết đánh giá cho khách sạn, review/<id>-<slug-ten> (giống hotel/...)
$route['review/(:num)'] = 'home/review/$1';
$route['review/(:num)-([a-zA-Z0-9-]+)'] = 'home/review/$1/$2';

//Contact
$route['contact']      = 'contact/index';
$route['contact/send'] = 'contact/send';

//Search
$route['search/suggest'] = 'search/suggest';
$route['search'] = 'search/index';



