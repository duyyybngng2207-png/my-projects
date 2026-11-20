<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Symfony\Component\DomCrawler\Crawler;

/**
 * Crawler khách sạn từ trang chi tiết KAYAK (Symfony DomCrawler).
 *    /crawler/brand                 -> crawl URL mặc định
 *    /crawler/brand?url=<kayak-url> -> crawl URL tuỳ chọn (chỉ domain KAYAK)
 *    POST /crawler/brand            -> form "Crawl" trong admin khách sạn, quay lại admin/hotels
 *    /crawler/brand/place           -> crawl trang thành phố (kèm khách sạn)
 *    /crawler/brand/country         -> crawl trang quốc gia
 *    /crawler/brand/continent       -> crawl trang châu lục
 *    /crawler/brand/post            -> crawl 1 bài viết blog KAYAK (kayak.com/news/<slug>/)
 *    /crawler/brand/news            -> crawl danh sách bài mới nhất (trang /news/ hoặc /news/category/<x>/)
 *    /crawler/brand/author          -> crawl trang tác giả blog (kayak.com/news/author/<slug>/), kèm bài của tác giả
 *    Crawl bài viết cũng tự lưu tác giả của bài (bảng authors, posts.author_id).
 * Crawl khách sạn kèm tổng hợp đánh giá trên KAYAK (hotels.guest_insights - xem hotelInsights()).
 * Blog KAYAK đã tắt bình luận nên không có comment bài viết để crawl.
 * Dữ liệu chính lấy từ JSON-LD (schema.org); ảnh tải về assets/media/ (tối đa 13 ảnh/khách sạn).
 * Phân cấp: continents > countries (continent_id) > places (thành phố, country_id) > hotels (place_id).
 *
 * @property Posts_model      $Posts_model
 * @property Authors_model    $Authors_model
 * @property Hotels_model     $Hotels_model
 * @property Places_model     $Places_model
 * @property Countries_model  $Countries_model
 * @property Continents_model $Continents_model
 * @property Categories_model $Categories_model
 */
class Brand extends Crawler_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Hotels_model');
        $this->load->model('Places_model');
        $this->load->model('Countries_model');
        $this->load->model('Continents_model');
        $this->load->model('Categories_model');
        $this->load->model('Posts_model');
        $this->load->model('Authors_model');

    }

    const DEFAULT_URL = 'https://www-kayak-com.translate.goog/Mexico-City-Hotels-NH-Collection-Mexico-City-Airport-T2.311998.ksp?_x_tr_sl=vi&_x_tr_tl=en&_x_tr_hl=vi&_x_tr_pto=wapp';

    /** Site hiển thị giá bằng $ -> chỉ nhận giá phòng tính bằng USD. */
    const PRICE_CURRENCY = 'USD';

    /** Các @type schema.org được coi là "khách sạn". */
    private $hotelTypes = ['Hotel', 'LodgingBusiness', 'Resort', 'Motel', 'Hostel', 'BedAndBreakfast'];

    /** Chỉ cho phép crawl các host này (tránh biến controller thành proxy mở). */
    private $allowedHosts = ['www.kayak.com', 'kayak.com', 'www-kayak-com.translate.goog'];

    /** Thư mục lưu ảnh crawl (tính từ FCPATH) và số ảnh tối đa mỗi khách sạn (gồm cả ảnh đại diện). */
    const MEDIA_DIR = 'assets/media/';
    const MAX_IMAGES = 13;
    const MAX_IMAGE_BYTES = 5242880; // 5MB

    /** Chỉ tải ảnh từ CDN ảnh của Kayak (khớp đuôi host). Ảnh bài viết blog nằm ở www.kayak.com/rimg/... */
    private $imageHosts = ['r9cdn.net', 'kayak.com'];

    /** File ảnh tạo mới trong lần crawl này -> xoá nếu lưu DB thất bại, tránh file mồ côi. */
    private $newMediaFiles = [];

    /** ---- Crawl địa điểm (thành phố): trang "<City>-Hotels.<id>.hotel.ksp" ---- */
    const PLACE_DEFAULT_URL = 'https://www.kayak.com/Phu-Quoc-Hotels.54054.hotel.ksp';

    /** places.image chỉ lưu tên file, ảnh nằm ở uploads/places/ (xem upload_url()). */
    const PLACE_MEDIA_DIR = 'uploads/places/';

    /** Số khách sạn tối đa crawl kèm mỗi lần crawl địa điểm (mỗi khách sạn ~3-6 giây vì tải ảnh). */
    const PLACE_MAX_HOTELS = 16;


    /** @type schema.org được coi là "địa điểm". */
    private $placeTypes = ['TouristDestination', 'City', 'Place'];

    /** ---- Crawl quốc gia: trang "<Country>-Hotels.<id>.dc.html" ---- */
    const COUNTRY_DEFAULT_URL = 'https://www.kayak.com/Vietnam-Hotels.259.dc.html';

    /** countries.image chỉ lưu tên file, ảnh nằm ở uploads/countries/ (xem upload_url()). */
    const COUNTRY_MEDIA_DIR = 'uploads/countries/';

    /** ---- Crawl châu lục: trang "Cheap-Hotels-in-<Name>.<xx>.htc.html" (as, eu, na, sa, af, me, ca, cb, sp) ---- */
    const CONTINENT_DEFAULT_URL = 'https://www.kayak.com/Cheap-Hotels-in-Asia.as.htc.html';
    const CONTINENT_MEDIA_DIR   = 'uploads/continents/';

    /** ---- Crawl bài viết blog: "https://www.kayak.com/news/<slug>/" (WordPress) ---- */
    const POST_DEFAULT_URL = 'https://www.kayak.com/news/christmas-markets-in-france/';
    const NEWS_DEFAULT_URL = 'https://www.kayak.com/news/';
    /** posts.image chỉ lưu tên file, ảnh nằm ở uploads/posts/ (xem upload_url()). */
    const POST_MEDIA_DIR = 'uploads/posts/';
    /** Số bài crawl mặc định / tối đa mỗi lần crawl danh sách (mỗi bài ~2-4 giây vì tải ảnh). */
    const NEWS_DEFAULT_LIMIT = 6;
    const NEWS_MAX_LIMIT     = 20;
    /** posts.content là TEXT (64KB) -> cắt bớt nội dung quá dài. */
    const POST_MAX_CONTENT = 60000;
    /** ---- Tác giả blog: "https://www.kayak.com/news/author/<slug>/" ---- */
    const AUTHOR_DEFAULT_URL = 'https://www.kayak.com/news/author/lydia-rajteric/';
    /** authors.avatar chỉ lưu tên file, ảnh nằm ở uploads/authors/ (xem upload_url()). */
    const AUTHOR_MEDIA_DIR = 'uploads/authors/';
    /** Đường dẫn dưới /news/ không phải bài viết. */
    private $newsReservedSlugs = ['feed', 'category', 'tag', 'author', 'page', 'wp-json', 'search'];

    // Dòng ghi nguồn lưu vào cột crawler_credit, hiện dưới nội dung trên site
    const CRAWLER_CREDIT = 'Nguồn: KAYAK';

    // ======================================================================
    // LUỒNG CHUNG CỦA MỌI HÀM CRAWL (index, place, country, continent, post...):
    //   1. Lấy URL: param('url') (form admin POST hoặc ?url= trên trình duyệt), không có thì dùng URL mặc định
    //   2. isAllowedUrl(): chỉ cho crawl domain KAYAK (không biến server thành proxy cho link lạ)
    //   3. curl_html(): tải HTML của trang về
    //   4. parseXxx(): đọc dữ liệu (chủ yếu từ JSON-LD schema.org trong thẻ <script type="application/ld+json">),
    //      tải ảnh về uploads/..., rồi THÊM MỚI hoặc CẬP NHẬT bản ghi trong DB (nhận diện theo crawler_href)
    //   5. respond(): form admin -> flash message + quay lại trang admin; GET/CLI -> in JSON kết quả
    // ======================================================================

    // Crawl 1 khách sạn: /crawler/brand?url=<link trang khách sạn .ksp>
    public function index()
    {
        // GET ?url=... hoặc POST từ form admin; không truyền thì dùng URL mặc định
        $url = $this->param('url') ?: self::DEFAULT_URL;
        // Chỉ cho phép link KAYAK
        if (!$this->isAllowedUrl($url)) {
            return $this->respond(['status' => false, 'message' => 'URL không hợp lệ: ' . $url], 'admin/hotels');
        }

        // Tải HTML; lỗi mạng / bị chặn -> false
        $html = $this->curl_html($url);
        if ($html === false) {
            return $this->respond(['status' => false, 'message' => 'Không tải được trang: ' . $url], 'admin/hotels');
        }

        // Admin chọn địa điểm trên form -> dùng; không chọn / id không tồn tại -> tự xác định theo thành phố
        $placeId = (int) $this->param('place_id');
        if ($placeId > 0 && !$this->Places_model->placeExists($placeId)) {
            $placeId = 0;
        }

        // Đọc dữ liệu + lưu DB
        $result = $this->parseHotel($html, $url, $placeId ?: null);


        // Báo kết quả (flash + quay lại admin/hotels, hoặc JSON)
        return $this->respond($result, 'admin/hotels');
    }

    /**
     *    /crawler/brand/place                 -> crawl URL mặc định
     *    /crawler/brand/place?url=<kayak-url> -> crawl URL tuỳ chọn
     *    POST /crawler/brand/place            -> form "Crawl" trong admin địa điểm
     */
    public function place()
    {
        @set_time_limit(0); // crawl kèm ~16 khách sạn có thể mất 1-2 phút
        $url = $this->param('url') ?: self::PLACE_DEFAULT_URL;
        if (!$this->isAllowedUrl($url)) {
            return $this->respond(['status' => false, 'message' => 'URL không hợp lệ: ' . $url], 'admin/places');
        }

        $html = $this->curl_html($url);
        if ($html === false) {
            return $this->respond(['status' => false, 'message' => 'Không tải được trang: ' . $url], 'admin/places');
        }

        // Bước 1: lưu thành phố, đồng thời lấy danh sách link khách sạn trên trang (hotel_urls)
        $result = $this->parsePlace($html, $url);
        // Bước 2: crawl lần lượt từng khách sạn và gắn vào thành phố vừa lưu
        // ?with_hotels=0 -> chỉ crawl địa điểm, không crawl khách sạn
        if (!empty($result['status']) && !empty($result['hotel_urls']) && $this->param('with_hotels') !== '0') {
            $hotels = $this->crawlHotels($result['hotel_urls'], $result['place_id']);
            $result['hotels']   = $hotels;
            $result['message'] .= sprintf('. Khách sạn: %d thành công, %d lỗi', count($hotels['success']), count($hotels['failed']));
        }
        // Danh sách link chỉ dùng nội bộ, không cần trả ra kết quả
        unset($result['hotel_urls']);

        return $this->respond($result, 'admin/places');
    }

    /**
     *    /crawler/brand/country                 -> crawl URL mặc định (Vietnam)
     *    /crawler/brand/country?url=<kayak-url> -> crawl trang quốc gia "<Country>-Hotels.<id>.dc.html"
     * Chỉ lưu quốc gia (tên, châu lục, ảnh, nội dung). Thành phố crawl riêng qua /crawler/brand/place:
     * danh sách link thành phố trên trang quốc gia lẫn cả thành phố nước khác (menu chung của Kayak).
     */
    public function country()
    {
        $url = $this->param('url') ?: self::COUNTRY_DEFAULT_URL;
        if (!$this->isAllowedUrl($url)) {
            return $this->respond(['status' => false, 'message' => 'URL không hợp lệ: ' . $url], 'admin/countries');
        }

        $html = $this->curl_html($url);
        if ($html === false) {
            return $this->respond(['status' => false, 'message' => 'Không tải được trang: ' . $url], 'admin/countries');
        }

        return $this->respond($this->parseCountry($html, $url), 'admin/countries');
    }

    /**
     *    /crawler/brand/continent                 -> crawl URL mặc định (Asia)
     *    /crawler/brand/continent?url=<kayak-url> -> crawl trang châu lục "Cheap-Hotels-in-<Name>.<xx>.htc.html"
     * Chỉ lưu châu lục (tên, ảnh, nội dung). Quốc gia crawl riêng qua /crawler/brand/country.
     */
    public function continent()
    {
        $url = $this->param('url') ?: self::CONTINENT_DEFAULT_URL;
        if (!$this->isAllowedUrl($url)) {
            return $this->respond(['status' => false, 'message' => 'URL không hợp lệ: ' . $url], 'admin/continents');
        }

        $html = $this->curl_html($url);
        if ($html === false) {
            return $this->respond(['status' => false, 'message' => 'Không tải được trang: ' . $url], 'admin/continents');
        }

        return $this->respond($this->parseContinent($html, $url), 'admin/continents');
    }

    /**
     *    /crawler/brand/post                 -> crawl bài mặc định
     *    /crawler/brand/post?url=<kayak-url> -> crawl bài "https://www.kayak.com/news/<slug>/"
     *    POST /crawler/brand/post            -> form "Crawl" trong admin bài viết
     * ?publish=0 -> lưu dạng nháp (mặc định đăng luôn khi thêm mới).
     */
    public function post()
    {
        $url = $this->param('url') ?: self::POST_DEFAULT_URL;
        if (!$this->isAllowedUrl($url)) {
            return $this->respond(['status' => false, 'message' => 'URL không hợp lệ: ' . $url], 'admin/posts');
        }
        // Dán nhầm link trang danh sách -> crawl danh sách
        if ($this->isNewsListHref($this->canonicalHref(null, $url))) {
            return $this->news();
        }

        $html = $this->curl_html($url);
        if ($html === false) {
            return $this->respond(['status' => false, 'message' => 'Không tải được trang: ' . $url], 'admin/posts');
        }

        return $this->respond($this->parsePost($html, $url), 'admin/posts');
    }

    /**
     *    /crawler/brand/news                          -> crawl 6 bài mới nhất ở kayak.com/news/
     *    /crawler/brand/news?url=<trang-danh-sách>    -> trang /news/ hoặc /news/category/<x>/
     *    /crawler/brand/news?limit=10                 -> số bài (tối đa 20)
     * Bài đã crawl trước đó sẽ được cập nhật, không tạo trùng.
     */
    public function news()
    {
        @set_time_limit(0);
        $url = $this->param('url') ?: self::NEWS_DEFAULT_URL;
        if (!$this->isAllowedUrl($url) || !$this->isNewsListHref($this->canonicalHref(null, $url))) {
            return $this->respond(['status' => false, 'message' => 'Không phải trang danh sách bài viết KAYAK (/news/ hoặc /news/category/...): ' . $url], 'admin/posts');
        }
        // Số bài cần crawl: ?limit=, không có thì 6, tối đa 20
        $limit = (int) $this->param('limit');
        $limit = $limit > 0 ? min($limit, self::NEWS_MAX_LIMIT) : self::NEWS_DEFAULT_LIMIT;

        // Tải trang danh sách
        $html = $this->curl_html($url);
        if ($html === false) {
            return $this->respond(['status' => false, 'message' => 'Không tải được trang: ' . $url], 'admin/posts');
        }

        // Lấy các link bài viết trên trang (bài mới nhất trước), giữ $limit link đầu
        $links = array_slice($this->newsLinks($html, $url), 0, $limit);
        if (!$links) {
            return $this->respond(['status' => false, 'message' => 'Không tìm thấy link bài viết nào trên trang: ' . $url], 'admin/posts');
        }

        // Crawl lần lượt từng bài, ghi lại bài thành công / thất bại để báo cáo
        $success = [];
        $failed  = [];
        foreach ($links as $i => $postUrl) {
            if ($i > 0) {
                sleep(1); // giãn request, tránh bị Kayak chặn bot
            }
            // Tải lỗi -> ghi lỗi; tải được -> parsePost() lưu bài
            $postHtml = $this->curl_html($postUrl);
            $r = $postHtml === false
                ? ['status' => false, 'message' => 'Không tải được trang']
                : $this->parsePost($postHtml, $postUrl);
            if (!empty($r['status'])) {
                $success[] = $r['message'];
            } else {
                $failed[] = ['url' => $postUrl, 'message' => isset($r['message']) ? $r['message'] : 'Lỗi không rõ'];
            }
        }

        return $this->respond([
            'status'  => count($success) > 0,
            'message' => sprintf('Crawl bài viết: %d thành công, %d lỗi', count($success), count($failed)),
            'success' => $success,
            'failed'  => $failed,
        ], 'admin/posts');
    }

    /**
     *    /crawler/brand/author                 -> crawl tác giả mặc định
     *    /crawler/brand/author?url=<kayak-url> -> trang tác giả "https://www.kayak.com/news/author/<slug>/"
     *    /crawler/brand/author?posts=5         -> crawl kèm tối đa 5 bài của tác giả (mặc định 0, tối đa 20)
     * Lưu tên, ảnh đại diện, chức danh, tiểu sử, mạng xã hội. Crawl lại cùng link sẽ cập nhật.
     */
    public function author()
    {
        @set_time_limit(0);
        $url = $this->param('url') ?: self::AUTHOR_DEFAULT_URL;
        if (!$this->isAllowedUrl($url) || !$this->isAuthorHref($this->canonicalHref(null, $url))) {
            return $this->respond(['status' => false, 'message' => 'Không phải trang tác giả KAYAK (kayak.com/news/author/<slug>/): ' . $url], 'admin/posts');
        }

        $html = $this->curl_html($url);
        if ($html === false) {
            return $this->respond(['status' => false, 'message' => 'Không tải được trang: ' . $url], 'admin/posts');
        }

        // Crawler (Symfony DomCrawler) = đối tượng để tìm thẻ HTML bằng CSS selector
        $crawler = new Crawler($html, $url);
        // Gom các khối JSON-LD trên trang theo @type (Person, BreadcrumbList...)
        $ld      = $this->extractJsonLd($crawler);
        // Link chuẩn của trang (thẻ og:url) -> dùng làm crawler_href để nhận diện khi crawl lại
        $href    = $this->canonicalHref($this->attr($crawler, 'meta[property="og:url"]', 'content'), $url);
        // Trang tác giả: node Person có url trùng trang đang crawl (fallback Person đầu tiên)
        $person  = $this->postPerson($ld, null, $href);
        if (!$person) {
            return $this->respond(['status' => false, 'message' => 'Không tìm thấy dữ liệu tác giả (JSON-LD Person) - có thể trang bị chặn bot: ' . $url], 'admin/posts');
        }

        // Thêm mới / cập nhật tác giả
        $result = $this->saveAuthor($person, $href);
        if (empty($result['status'])) {
            return $this->respond($result, 'admin/posts');
        }

        // Kèm bài viết của tác giả (link bài trên trang tác giả), gán luôn tác giả cho bài
        $limit = min((int) $this->param('posts'), self::NEWS_MAX_LIMIT);
        if ($limit > 0) {
            $success = [];
            $failed  = [];
            foreach (array_slice($this->newsLinks($html, $url), 0, $limit) as $postUrl) {
                sleep(1); // giãn request, tránh bị Kayak chặn bot
                $postHtml = $this->curl_html($postUrl);
                $r = $postHtml === false
                    ? ['status' => false, 'message' => 'Không tải được trang']
                    : $this->parsePost($postHtml, $postUrl);
                if (!empty($r['status'])) {
                    $success[] = $r['message'];
                } else {
                    $failed[] = ['url' => $postUrl, 'message' => isset($r['message']) ? $r['message'] : 'Lỗi không rõ'];
                }
            }
            $result['posts']    = ['success' => $success, 'failed' => $failed];
            $result['message'] .= sprintf('. Bài viết: %d thành công, %d lỗi', count($success), count($failed));
        }

        return $this->respond($result, 'admin/posts');
    }


    // ======================================================================
    // ĐỌC + LƯU 1 KHÁCH SẠN từ HTML trang khách sạn KAYAK (".../<Ten>.<id>.ksp")
    //   B1. Đọc JSON-LD: tên, địa chỉ, điểm, ảnh, giá phòng, FAQ, tiện ích
    //   B2. Tải ảnh về assets/media/
    //   B3. Xác định thành phố (place_id) của khách sạn
    //   B4. Chưa có trong DB (theo crawler_href / ID Kayak) -> INSERT, đã có -> UPDATE
    // $placeId: admin chọn sẵn thành phố trên form (hoặc crawlHotels truyền vào), NULL = tự xác định
    // Trả về ['status' => true/false, 'hotel_id' => ..., 'slug' => ...] hoặc thông báo lỗi
    // ======================================================================
    private function parseHotel($html, $url, $placeId = null)
    {
        // Mỗi khách sạn một danh sách riêng: khi crawl nhiều khách sạn liền nhau, nếu khách sạn
        // sau lỗi thì chỉ xoá ảnh của nó, không xoá nhầm ảnh của các khách sạn đã lưu trước đó.
        $this->newMediaFiles = [];

        $crawler = new Crawler($html, $url);
        $ld = $this->extractJsonLd($crawler);

        // Không có JSON-LD khách sạn -> thường là trang chặn bot/captcha (vẫn trả HTTP 200), không lưu rác.
        $hotel = $this->firstOf($ld, $this->hotelTypes);
        if (!$hotel) {
            return ['status' => false, 'message' => 'Không tìm thấy dữ liệu khách sạn (JSON-LD) - có thể trang bị chặn bot: ' . $url];
        }

        // Địa chỉ: thường là object PostalAddress {streetAddress, addressLocality...}; nếu chỉ là chuỗi thì gói lại
        $address = isset($hotel['address']) ? $hotel['address'] : [];
        if (!is_array($address)) {
            $address = ['streetAddress' => (string) $address];
        }

        // Điểm đánh giá tổng: {ratingValue: 8.3, bestRating: 10, ratingCount: 1829}
        $rating = (isset($hotel['aggregateRating']) && is_array($hotel['aggregateRating'])) ? $hotel['aggregateRating'] : [];

        // Ảnh: ưu tiên danh sách photo trong JSON-LD, fallback og:image
        $images = [];
        foreach ($this->asList(isset($hotel['photo']) ? $hotel['photo'] : null) as $photo) {
            $photoUrl = is_array($photo)
                ? (isset($photo['contentUrl']) ? $photo['contentUrl'] : (isset($photo['url']) ? $photo['url'] : null))
                : $photo;
            if (is_string($photoUrl) && $photoUrl !== '') {
                $images[] = [
                    'url'     => $photoUrl,
                    'caption' => (is_array($photo) && isset($photo['description'])) ? $photo['description'] : null,
                ];
            }
        }

        // Giá phòng: các block ItemList chứa Offer (có thể có nhiều ItemList, gom hết)
        $rooms = [];
        foreach ($this->asList(isset($ld['ItemList']) ? $ld['ItemList'] : null) as $list) {
            foreach ($this->asList(isset($list['itemListElement']) ? $list['itemListElement'] : null) as $el) {
                $offer = (is_array($el) && isset($el['item'])) ? $el['item'] : $el;
                if (is_array($offer) && in_array('Offer', (array) (isset($offer['@type']) ? $offer['@type'] : []), true)) {
                    $rooms[] = [
                        'name'     => isset($offer['itemOffered']['name']) ? $offer['itemOffered']['name'] : null,
                        'price'    => (isset($offer['price']) && is_numeric($offer['price'])) ? (float) $offer['price'] : null,
                        'currency' => isset($offer['priceCurrency']) ? strtoupper((string) $offer['priceCurrency']) : null,
                    ];
                }
            }
        }
        // Chỉ lấy giá đúng tiền tệ của site: Kayak định giá theo IP nên có thể trả VND/MXN...
        $prices = [];
        foreach ($rooms as $room) {
            if ($room['price'] > 0 && $room['currency'] === self::PRICE_CURRENCY) {
                $prices[] = $room['price'];
            }
        }

        // FAQ (check-in/check-out, vị trí...)
        $faqs = [];
        $faqPage = $this->firstOf($ld, ['FAQPage']);
        foreach ($this->asList($faqPage && isset($faqPage['mainEntity']) ? $faqPage['mainEntity'] : null) as $q) {
            if (!is_array($q)) {
                continue;
            }
            $faqs[] = [
                'question' => isset($q['name']) ? $this->cleanText($q['name']) : null,
                'answer'   => isset($q['acceptedAnswer']['text']) ? $this->cleanText($q['acceptedAnswer']['text']) : null,
            ];
        }

        // Review (Kayak hiện không đưa review vào JSON-LD; biến này chỉ còn dùng trong khối return đã comment bên dưới)
        $reviews = [];
        foreach ($this->asList(isset($ld['Review']) ? $ld['Review'] : null) as $r) {
            $reviews[] = [
                'author' => isset($r['author']['name']) ? $r['author']['name'] : null,
                'rating' => isset($r['reviewRating']['ratingValue']) ? $r['reviewRating']['ratingValue'] : null,
                'best'   => isset($r['reviewRating']['bestRating']) ? $r['reviewRating']['bestRating'] : null,
                'body'   => isset($r['reviewBody']) ? $r['reviewBody'] : null,
            ];
        }

        // Điểm tham quan lân cận
        $nearby = [];
        foreach ($this->asList(isset($ld['TouristAttraction']) ? $ld['TouristAttraction'] : null) as $a) {
            $nearby[] = [
                'name' => isset($a['name']) ? $a['name'] : null,
                'lat'  => isset($a['geo']['latitude']) ? $a['geo']['latitude'] : null,
                'lng'  => isset($a['geo']['longitude']) ? $a['geo']['longitude'] : null,
            ];
        }

        // Fallback từ HTML khi JSON-LD thiếu.
        // decode_unicode_escapes: Kayak đôi khi escape 2 lần nên còn sót "à" dạng chữ.
        $name = (isset($hotel['name']) && is_string($hotel['name'])) ? $hotel['name'] : $this->text($crawler, 'h1');
        $name = $this->cleanText($name);
        $description = isset($hotel['description'])
            ? $hotel['description']
            : $this->attr($crawler, 'meta[name="description"]', 'content');
        $description = decode_unicode_escapes($description);
        if (!$images && ($og = $this->attr($crawler, 'meta[property="og:image"]', 'content'))) {
            $images[] = ['url' => $og, 'caption' => null];
        }

        // return [
        //     'source_url'   => isset($hotel['url']) ? $hotel['url'] : $url,
        //     'name'         => $name,
        //     'description'  => $description,
        //     // 'star_rating'  => isset($hotel['starRating']) ? $hotel['starRating'] : null,
        //     // 'telephone'    => isset($hotel['telephone']) ? $hotel['telephone'] : null,
        //     'address'      => [
        //         'street'  => isset($address['streetAddress']) ? $address['streetAddress'] : null,
        //         'city'    => isset($address['addressLocality']) ? $address['addressLocality'] : null,
        //         'region'  => isset($address['addressRegion']) ? $address['addressRegion'] : null,
        //         'country' => isset($address['addressCountry']) ? $address['addressCountry'] : null,
        //         'postal'  => isset($address['postalCode']) ? $address['postalCode'] : null,
        //     ],
        //     'rating'       => isset($rating['ratingValue']) ? (float) $rating['ratingValue'] : null,
        //     'rating_best'  => isset($rating['bestRating']) ? (float) $rating['bestRating'] : null,
        //     'rating_count' => isset($rating['ratingCount']) ? $rating['ratingCount'] : null,
        //     'price_min'    => $prices ? min($prices) : null,
        //     'price_range'  => isset($hotel['priceRange']) ? $hotel['priceRange'] : null,
        //     'thumbnail'    => isset($hotel['image']) ? $hotel['image'] : ($images ? $images[0]['url'] : null),
        //     'images'       => $images,
        //     'amenities'    => isset($hotel['amenityFeature']) ? array_values($hotel['amenityFeature']) : [],
        //     'rooms'        => $rooms,
        //     'faqs'         => $faqs,
        //     'reviews'      => $reviews,
        //     'nearby'       => $nearby,
        // ];
        // dd(1);
        // (Khối return ở trên đã bị comment: trước đây dùng để xem thử dữ liệu crawl, không lưu DB)

        // Slug từ tên; tên rỗng -> không lưu
        $slug = $this->toSlug((string) $name);
        if (empty($slug)) {
            return ['status' => false, 'message' => 'Không lấy được tên khách sạn (slug rỗng), bỏ qua: ' . $url];
        }

        // Ảnh đại diện: hotel.image có thể là chuỗi hoặc mảng; không có thì lấy ảnh đầu tiên trong album
        $thumbnail = null;
        if (!empty($hotel['image'])) {
            $thumbnail = is_array($hotel['image']) ? reset($hotel['image']) : $hotel['image'];
            if (is_array($thumbnail)) {
                $thumbnail = isset($thumbnail['url']) ? $thumbnail['url'] : (isset($thumbnail['contentUrl']) ? $thumbnail['contentUrl'] : null);
            }
            if (!is_string($thumbnail) || $thumbnail === '') {
                $thumbnail = null;
            }
        }
        if (!$thumbnail && $images) {
            $thumbnail = $images[0]['url'];
        }

        // Tải tối đa MAX_IMAGES ảnh (ảnh đại diện + album) về assets/media, DB chỉ lưu link local
        // để ảnh không chết khi trang nguồn đổi/xoá link.
        $localImages = $this->downloadImages(array_merge([$thumbnail], array_column($images, 'url')));
        $thumbnail   = $localImages ? $localImages[0] : null;

        // Tiện ích: phần tử có thể là chuỗi hoặc object LocationFeatureSpecification {name: ...}
        $amenities = [];
        foreach ($this->asList(isset($hotel['amenityFeature']) ? $hotel['amenityFeature'] : null) as $feature) {
            $label = is_array($feature) ? (isset($feature['name']) ? $feature['name'] : null) : $feature;
            $label = is_scalar($label) ? $this->cleanText((string) $label) : '';
            if ($label !== '') {
                $amenities[] = $label;
            }
        }
        $amenities = array_values(array_unique($amenities));

        // 1) Nhận diện khách sạn theo nguồn crawl (link chuẩn hoá + ID Kayak), không theo tên:
        //    nhiều khách sạn chuỗi trùng tên ở các thành phố khác nhau.
        $crawlerHref = $this->canonicalHref(isset($hotel['url']) ? $hotel['url'] : null, $url);
        $sourceId    = $this->kayakId($crawlerHref) ?: $this->kayakId($url);

        // 2) Thành phố: dùng thành phố truyền vào, không có thì tự tìm theo breadcrumb / địa chỉ
        $place_id = $placeId ?: $this->resolveHotelPlace($ld, $address);
        if (!$place_id) {
            return ['status' => false, 'message' => 'Không xác định được địa điểm (thành phố) của khách sạn: ' . $name];
        }


        $params_brand = [
            'place_id'     => $place_id,
            'name'         => $name,
            'slug'         => $slug,
            'description'  => $description,
            'crawler_href' => $crawlerHref,
            'phone'        => (isset($hotel['telephone']) && is_scalar($hotel['telephone'])) ? (string) $hotel['telephone'] : null,
            'address'      => isset($address['streetAddress']) ? $this->cleanText($address['streetAddress']) : null,
            // Giá thấp nhất (USD) trong các phòng; không có giá thì để NULL, lúc insert mới quy về 0
            'price'        => $prices ? round(min($prices)) : null,
            // Kayak chấm theo thang bestRating (thường là 10) -> quy về 1..5 sao như các khách sạn khác
            'rating'       => $this->toStars($rating),
            'rating_count' => isset($rating['ratingCount']) && is_numeric($rating['ratingCount']) ? $rating['ratingCount'] : null,
            'thumbnail'    => $thumbnail,
            // Các cột TEXT lưu dạng JSON
            'albums'       => $localImages ? $this->toJson($localImages) : null,
            'amenities'    => $amenities ? $this->toJson($amenities) : null,
            'faqs'         => $faqs ? $this->toJson($faqs) : null,
            // Tổng hợp đánh giá (điểm theo hạng mục, tóm tắt, Q&A, Tips) - xem hotelInsights()
            'guest_insights' => $this->hotelInsights($crawler),
        ];



        // 3) Đã crawl khách sạn này chưa? (theo link nguồn / ID Kayak)
        $existingHotelCrawlerHref  = $this->Hotels_model->getHotelByCrawlerHref($crawlerHref , $sourceId);
        // Có khách sạn KHÁC trùng slug không? (khách sạn chuỗi trùng tên ở thành phố khác)
        $existingHotelSlug = $this->Hotels_model->getHotelBySlug($slug);
        // ---- Chưa có -> THÊM MỚI ----
        if(!$existingHotelCrawlerHref) {
            $finalSlug = $slug;
            // Trùng slug -> thêm đuôi ID Kayak để slug không trùng
            if($existingHotelSlug) {
                $finalSlug = $slug . '-' . ($sourceId ?: uniqid());
            }

            $params_brand['slug'] = $finalSlug;
            $hotel_id = $this->Hotels_model->insertHotel($params_brand);
            if(!$hotel_id) {
                // Lưu DB lỗi -> xoá ảnh vừa tải để không thành file rác
                $this->deleteMediaFiles($this->newMediaFiles);
                return [
                    'status'   => false,
                    'message' => 'Insert khách sạn thất bại: ' . $name
                ];
            }
        } else {
            // ---- Đã có -> CẬP NHẬT (giữ tên, slug, mô tả, trạng thái trending admin đã sửa) ----
            $hotel_id     = $existingHotelCrawlerHref->id;
            $finalSlug    = $existingHotelCrawlerHref->slug;

            // Chỉ cập nhật các cột trong $fields
            // place_id luôn cập nhật: crawl lại sẽ chuyển khách sạn từ place quốc gia (cũ) sang place thành phố
            $fields = ['phone', 'address', 'price', 'rating', 'rating_count', 'thumbnail', 'albums', 'amenities', 'faqs', 'place_id', 'guest_insights'];
            $update = array_intersect_key($params_brand, array_flip($fields));
            // Trang đổi giao diện / không có khối đánh giá -> giữ tổng hợp đã crawl trước đó
            if ($update['guest_insights'] === null) {
                unset($update['guest_insights']);
            }


            if($update) {
                // Lấy ảnh cũ trước khi ghi đè để dọn file không còn dùng
                $old = isset($update['albums']) ? $this->Hotels_model->getHotel($hotel_id) : null;

                if (!$this->Hotels_model->updateHotel($hotel_id, $update)) {
                    $this->deleteMediaFiles($this->newMediaFiles);
                    return [
                        'status'  => false,
                        'message' => 'Cập nhật khách sạn thất bại: ' . $name
                    ];
                }

                // Xoá các ảnh cũ không còn nằm trong bộ ảnh mới
                if ($old) {
                    $oldImages = array_merge([$old->thumbnail], (array) json_decode((string) $old->albums, true));
                    $this->deleteMediaFiles(array_diff(array_filter($oldImages, 'is_string'), $localImages));
                }
            }
        }

        return [
            'status'       => true, 
            'hotel_id'     => $hotel_id, 
            'slug'         => $finalSlug,
        ];   
    }

    /**
     * Tổng hợp đánh giá trong khối [data-section-name="reviews"] của trang khách sạn Kayak.
     * Nội dung từng review được Kayak tải bằng JS (không có trong HTML) nên chỉ lấy phần tổng hợp:
     *   điểm + phân bố (hrp2/OkUG), "Guests say" + điểm hạng mục (pfDx), điểm theo nguồn (O1q2),
     *   từ khoá (Unr_-tag), Q&A / Tips (tabpanel -> qvba).
     * Class của Kayak là tên rút gọn tự sinh, có thể đổi -> phần nào không thấy thì bỏ qua.
     * @return string|null JSON, NULL nếu không lấy được gì
     */
    private function hotelInsights(Crawler $crawler)
    {
        $section = $crawler->filter('[data-section-name="reviews"]');
        if (!$section->count()) {
            return null;
        }
        $section = $section->first();
        // 3 hàm nhỏ dùng lại nhiều lần trong hàm này:
        // $clean: gộp khoảng trắng thừa + giải mã ký tự HTML
        $clean = function ($text) {
            return trim((string) $this->cleanText(preg_replace('/\s+/u', ' ', (string) $text)));
        };
        // $number: lấy số đầu tiên trong chuỗi ("9.5 out of 10" -> 9.5), không có -> null
        $number = function ($text) {
            return preg_match('/(\d+(?:[.,]\d+)?)/', (string) $text, $m) ? (float) str_replace(',', '.', $m[1]) : null;
        };
        // $nodeText: chữ của phần tử đầu tiên khớp selector bên trong $root ('' nếu không có)
        $nodeText = function (Crawler $root, $selector) use ($clean) {
            $node = $root->filter($selector);
            return $node->count() ? $clean($node->first()->text('', true)) : '';
        };

        $data = [];

        // 1) Điểm tổng + phân bố điểm
        $score = $number($nodeText($section, '.hrp2-score'));
        if ($score !== null) {
            $data['score'] = $score;
            $data['label'] = $nodeText($section, '.hrp2-category');
            $data['count'] = (int) preg_replace('/\D/', '', $nodeText($section, '.hrp2-count'));
        }
        $histogram = [];
        $section->filter('.OkUG')->each(function (Crawler $row) use (&$histogram, $nodeText) {
            $label = $nodeText($row, '.OkUG-title');
            $count = preg_replace('/\D/', '', $nodeText($row, '.OkUG-count'));
            if ($label !== '' && $count !== '') {
                $histogram[] = ['label' => $label, 'count' => (int) $count];
            }
        });
        if ($histogram) {
            $data['histogram'] = $histogram;
        }

        // 2) Tóm tắt "Guests say" (text trực tiếp trong div chứa .pfDx-title, bỏ tiêu đề + dòng "AI-Generated...")
        $title = $section->filter('.pfDx-title');
        if ($title->count()) {
            $box = $title->first()->getNode(0)->parentNode;
            $summary = '';
            foreach ($box->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $summary .= $child->textContent;
                }
            }
            $summary = $clean($summary);
            if ($summary !== '') {
                $data['summary'] = $summary;
            }
        }

        // 3) Điểm theo hạng mục: "Bed Comfort" + sr-only "9.5 out of 10"
        $categories = [];
        $section->filter('.pfDx-tag-label')->each(function (Crawler $tag) use (&$categories, $clean, $number) {
            $sr    = $tag->filter('.pfDx-sr-only');
            $score = $sr->count() ? $number($sr->text('', true)) : null;
            $label = '';
            foreach ($tag->getNode(0)->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $label .= $child->textContent;
                }
            }
            $label = $clean($label);
            if ($label !== '' && $score !== null) {
                $categories[] = ['label' => $label, 'score' => $score];
            }
        });
        if ($categories) {
            $data['categories'] = $categories;
        }

        // 4) Điểm theo nguồn: aria-label "Booking.com, 8.3 out of 10" + "1612 reviews"
        $providers = [];
        $section->filter('.O1q2-source')->each(function (Crawler $src) use (&$providers, $clean, $number) {
            $aria = $clean($src->attr('aria-label'));
            if (!preg_match('/^(.+?),\s*([\d.]+)\s+out of\s+10/i', $aria, $m)) {
                return;
            }
            // Số review nằm ở div con ngoài .O1q2-header (gộp text cả khối sẽ dính "out of 10" + "1612 reviews")
            $reviews = null;
            $src->children()->each(function (Crawler $child) use (&$reviews) {
                if (strpos((string) $child->attr('class'), 'O1q2-header') === false
                    && preg_match('/^([\d,]+)\s+reviews?$/i', trim($child->text('', true)), $r)) {
                    $reviews = (int) str_replace(',', '', $r[1]);
                }
            });
            $providers[] = ['name' => $m[1], 'score' => (float) $m[2], 'reviews' => $reviews];
        });
        if ($providers) {
            $data['providers'] = $providers;
        }

        // 5) Từ khoá hay được nhắc trong review
        $mentions = [];
        $section->filter('.Unr_-tag')->each(function (Crawler $tag) use (&$mentions, $clean) {
            $text = $clean($tag->text('', true));
            if ($text !== '' && !in_array($text, $mentions, true)) {
                $mentions[] = $text;
            }
        });
        if ($mentions) {
            $data['mentions'] = $mentions;
        }

        // 6) Q&A và Tips: tabpanel nào là Q&A / Tips xác định theo tên tab (aria-labelledby -> tab)
        $tabNames = [];
        $section->filter('[role="tab"]')->each(function (Crawler $tab) use (&$tabNames, $clean) {
            $id = $tab->attr('id');
            if ($id) {
                $tabNames[$id] = strtolower(preg_replace('/\(\d+\)/', '', $clean($tab->text('', true))));
            }
        });
        $section->filter('[role="tabpanel"]')->each(function (Crawler $panel) use (&$data, $tabNames, $nodeText) {
            $name = isset($tabNames[$panel->attr('aria-labelledby')]) ? trim($tabNames[$panel->attr('aria-labelledby')]) : '';
            $key  = $name === 'q&a' ? 'qa' : ($name === 'tips' ? 'tips' : null);
            if (!$key) {
                return;
            }
            $items = [];
            $panel->filter('.qvba')->each(function (Crawler $item) use (&$items, $key, $nodeText) {
                $head = $nodeText($item, '.qvba-title');
                $body = $nodeText($item, '.qvba-content');
                if ($head !== '' && $body !== '') {
                    $items[] = $key === 'qa' ? ['question' => $head, 'answer' => $body] : ['title' => $head, 'text' => $body];
                }
            });
            if ($items) {
                $data[$key] = $items;
            }
        });

        // Không lấy được phần nào -> NULL (parseHotel sẽ giữ dữ liệu cũ)
        if (!$data) {
            return null;
        }
        // Ghi lại thời điểm crawl rồi trả chuỗi JSON để lưu vào hotels.guest_insights
        $data['crawled_at'] = date('Y-m-d H:i:s');
        return $this->toJson($data);
    }

    // ======================================================================
    // ĐỌC + LƯU 1 THÀNH PHỐ từ trang "<Thanh-Pho>-Hotels.<id>.hotel.ksp"
    //   tên, quốc gia + châu lục (từ breadcrumb), nội dung (FAQ), ảnh (og:image)
    //   -> savePlace() thêm mới / cập nhật; trả kèm 'hotel_urls' (link khách sạn trên trang)
    //   để hàm place() crawl tiếp các khách sạn
    // ======================================================================
    private function parsePlace($html, $url)
    {
        $crawler = new Crawler($html, $url);
        $ld = $this->extractJsonLd($crawler);

        // Không có node địa điểm -> trang bị chặn bot / không phải trang thành phố
        $dest = $this->firstOf($ld, $this->placeTypes);
        if (!$dest) {
            return ['status' => false, 'message' => 'Không tìm thấy dữ liệu địa điểm (JSON-LD) - có thể không phải trang thành phố: ' . $url];
        }

        // 1) Tên: TouristDestination.name, fallback h1 "Phu Quoc hotels"
        $name = (isset($dest['name']) && is_string($dest['name']))
            ? $dest['name']
            : preg_replace('/\s+hotels$/i', '', (string) $this->text($crawler, 'h1'));
        $name = $this->cleanText($name);

        $slug = $this->toSlug((string) $name);
        if ($slug === '') {
            return ['status' => false, 'message' => 'Không lấy được tên địa điểm (slug rỗng), bỏ qua: ' . $url];
        }

        // 2) Quốc gia từ breadcrumb (tìm hoặc tạo bản ghi countries); địa chỉ = "Thành phố, Quốc gia" (cột NOT NULL)
        //    Châu lục cũng lấy từ breadcrumb ("Hotels > North America > Mexico > ...") để gán cho quốc gia
        $breadcrumb = $this->firstOf($ld, ['BreadcrumbList']);
        $country    = $this->breadcrumbCountry($breadcrumb);
        $countryId  = $country ? $this->resolveCountry($country['name'], $country['href'], $this->resolveBreadcrumbContinent($breadcrumb)) : 0;
        $address    = implode(', ', array_filter([$name, $country ? $country['name'] : null]));

        // 3) Nội dung: các câu hỏi/đáp FAQ
        $content = $this->faqContent($ld);

        // 4) Ảnh: og:image -> tải về uploads/places/, DB chỉ lưu tên file
        $image = null;
        $ogImage = $this->attr($crawler, 'meta[property="og:image"]', 'content');
        if ($ogImage) {
            $local = $this->downloadImage($ogImage, self::PLACE_MEDIA_DIR);
            $image = $local ? basename($local) : null;
        }

        // 5) Nguồn
        $crawlerHref = $this->canonicalHref($this->attr($crawler, 'meta[property="og:url"]', 'content'), $url);
        $cityId      = $this->kayakCityId($crawlerHref) ?: $this->kayakCityId($url);

        // 6) Danh mục chọn trên form (không chọn / không tồn tại -> NULL)
        $categoryId = (int) $this->param('category_id');
        if ($categoryId > 0 && !$this->Categories_model->getCategory($categoryId)) {
            $categoryId = 0;
        }

        $data = [
            'name'           => $name,
            'address'        => mb_substr($address, 0, 255),
            'content'        => $content,
            'image'          => $image,
            'crawler_href'   => $crawlerHref,
            'crawler_credit' => self::CRAWLER_CREDIT,
        ];
        if ($categoryId > 0) {
            $data['category_id'] = $categoryId;
        }
        // Không xác định được quốc gia -> không ghi đè country_id đang có (bản ghi cũ / admin đã chọn)
        if ($countryId > 0) {
            $data['country_id'] = $countryId;
        }

                // Thêm mới / cập nhật thành phố
                $result = $this->savePlace($data, $slug, $cityId);

        // Link khách sạn trong ItemList: ".../Phu-Quoc-Hotels-Seashells-Phu-Quoc-Hotel-Spa.3008708.ksp"
        $hotelUrls = [];
        foreach ($this->asList(isset($ld['ItemList']) ? $ld['ItemList'] : null) as $list) {
            foreach ($this->asList(isset($list['itemListElement']) ? $list['itemListElement'] : null) as $el) {
                if (!is_array($el)) {
                    continue;
                }
                $hotelUrl = isset($el['url']) ? $el['url'] : (isset($el['item']['url']) ? $el['item']['url'] : null);
                // Chỉ nhận link trang chi tiết khách sạn (có ID ".<số>.ksp")
                if (is_string($hotelUrl) && $this->kayakId($hotelUrl)) {
                    $hotelUrls[] = $hotelUrl;
                }
            }
        }
        // Bỏ link trùng, đánh lại chỉ số mảng 0, 1, 2...
        $result['hotel_urls'] = array_values(array_unique($hotelUrls));

        return $result;

    }

        /**
     * Thứ tự nhận diện:
     *   1) Cùng crawler_href          -> update (crawl lại)
     *   2) Cùng slug, chưa có nguồn   -> "nhận" bản ghi đó (vd place quốc gia do crawl hotel tạo,
     *                                    hoặc admin tự tạo tay) thay vì tạo trùng
     *   3) Không có                   -> insert; trùng slug thì thêm hậu tố ID thành phố
     */
    private function savePlace(array $data, $slug, $cityId)
    {
        // Bước 1: tìm theo link nguồn
        $existing = $this->Places_model->getPlaceByCrawlerHref($data['crawler_href']);
        if (!$existing) {
            // Bước 2: tìm theo slug, chỉ nhận bản ghi CHƯA có nguồn (tránh ghi đè thành phố cùng tên ở nước khác)
            $bySlug = $this->Places_model->getPlaceBySlug($slug);
            if ($bySlug && empty($bySlug->crawler_href)) {
                $existing = $bySlug;
            }
        }

        // ---- Thêm mới ----
        if (!$existing) {
            // Slug đã có người dùng -> thêm đuôi ID thành phố của Kayak
            $data['slug'] = $this->Places_model->getPlaceBySlug($slug)
                ? $slug . '-' . ($cityId ?: uniqid())
                : $slug;
            $data['is_featured'] = 0;
            $data['created_at']  = date('Y-m-d H:i:s');   // cột NOT NULL, không có default

            $id = $this->Places_model->insertPlace($data);
            if (!$id) {
                $this->deleteMediaFiles($this->newMediaFiles, self::PLACE_MEDIA_DIR);
                return ['status' => false, 'message' => 'Insert địa điểm thất bại: ' . $data['name']];
            }
            return ['status' => true, 'message' => 'Đã thêm địa điểm: ' . $data['name'], 'place_id' => $id, 'slug' => $data['slug']];
        }

        // ---- Cập nhật: giữ tên, slug, is_featured, rating admin đã sửa; bỏ giá trị NULL ----
        unset($data['name']);
        // array_filter: bỏ các cột NULL (crawl không lấy được) -> không xoá mất dữ liệu đang có
        $data = array_filter($data, function ($v) { return $v !== null; });
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->Places_model->updatePlace($existing->id, $data);

        // Ảnh cũ do crawler tải (kayak_*) mà khác ảnh mới -> xoá file
        if (!empty($data['image']) && !empty($existing->image) && $existing->image !== $data['image']) {
            $this->deleteMediaFiles([base_url(self::PLACE_MEDIA_DIR . $existing->image)], self::PLACE_MEDIA_DIR);
        }

        return ['status' => true, 'message' => 'Đã cập nhật địa điểm: ' . $existing->name, 'place_id' => $existing->id, 'slug' => $existing->slug];
    }

    /**
     * Trang quốc gia "<Country>-Hotels.<id>.dc.html":
     *   tên       <- TouristDestination.name (fallback breadcrumb)
     *   châu lục  <- BreadcrumbList, mục ".<xx>.htc.html" ("Asia", "North America"...) -> continent_id
     *   ảnh       <- og:image -> uploads/countries/
     *   nội dung  <- FAQPage
     */
    private function parseCountry($html, $url)
    {
        $crawler = new Crawler($html, $url);
        $ld = $this->extractJsonLd($crawler);

        // Trang thành phố cũng có TouristDestination -> chỉ nhận link quốc gia ".dc.html"
        $crawlerHref = $this->canonicalHref($this->attr($crawler, 'meta[property="og:url"]', 'content'), $url);
        if (!$this->isCountryHref($crawlerHref)) {
            return ['status' => false, 'message' => 'Không phải trang quốc gia KAYAK (".dc.html"): ' . $url];
        }

        // Node địa điểm (TouristDestination) + breadcrumb "Hotels > Asia > Vietnam"
        $dest       = $this->firstOf($ld, $this->placeTypes);
        $breadcrumb = $this->firstOf($ld, ['BreadcrumbList']);
        $crumb      = $this->breadcrumbCountry($breadcrumb);
        // Không có cả 2 -> trang lỗi / bị chặn bot
        if (!$dest && !$crumb) {
            return ['status' => false, 'message' => 'Không tìm thấy dữ liệu quốc gia (JSON-LD) - có thể trang bị chặn bot: ' . $url];
        }

        // Tên quốc gia: ưu tiên TouristDestination.name, không có thì lấy từ breadcrumb
        $name = ($dest && isset($dest['name']) && is_string($dest['name'])) ? $this->cleanText($dest['name']) : '';
        if ($name === '' && $crumb) {
            $name = $crumb['name'];
        }
        $slug = $this->toSlug($name);
        if ($slug === '') {
            return ['status' => false, 'message' => 'Không lấy được tên quốc gia (slug rỗng), bỏ qua: ' . $url];
        }

        // Ảnh đại diện (og:image) -> uploads/countries/, chỉ lưu tên file
        $this->newMediaFiles = [];
        $image   = null;
        $ogImage = $this->attr($crawler, 'meta[property="og:image"]', 'content');
        if ($ogImage) {
            $local = $this->downloadImage($ogImage, self::COUNTRY_MEDIA_DIR);
            $image = $local ? basename($local) : null;
        }

        // Thêm mới / cập nhật quốc gia
        return $this->saveCountry([
            'name'           => $name,
            // Không xác định được châu lục -> NULL -> bị bỏ khi cập nhật, giữ châu lục đang có
            'continent_id'   => $this->resolveBreadcrumbContinent($breadcrumb) ?: null,
            'image'          => $image,
            'content'        => $this->faqContent($ld),
            'crawler_href'   => $crawlerHref,
            'crawler_credit' => self::CRAWLER_CREDIT,
        ], $slug);
    }

    /**
     * Lưu quốc gia crawl từ trang ".dc.html". Nhận diện: crawler_href -> slug
     * (vd quốc gia admin nhập tay, hoặc do crawl thành phố / khách sạn tạo trước chỉ có tên).
     */
    private function saveCountry(array $data, $slug)
    {
        // Tìm theo link nguồn, không thấy thì theo slug ("?:" = lấy vế phải khi vế trái rỗng)
        $existing = $this->Countries_model->getCountryByCrawlerHref($data['crawler_href'])
            ?: $this->Countries_model->getCountryBySlug($slug);

        // ---- Thêm mới ----
        if (!$existing) {
            $data['slug'] = $slug;
            $id = $this->Countries_model->insertCountry($data);
            if (!$id) {
                $this->deleteMediaFiles($this->newMediaFiles, self::COUNTRY_MEDIA_DIR);
                return ['status' => false, 'message' => 'Insert quốc gia thất bại: ' . $data['name']];
            }
            return ['status' => true, 'message' => 'Đã thêm quốc gia: ' . $data['name'], 'country_id' => $id, 'slug' => $slug];
        }

        // ---- Cập nhật: giữ tên, slug, code, is_featured admin đã sửa; bỏ giá trị NULL ----
        unset($data['name']);
        $data = array_filter($data, function ($v) { return $v !== null; });
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->Countries_model->updateCountry($existing->id, $data)) {
            $this->deleteMediaFiles($this->newMediaFiles, self::COUNTRY_MEDIA_DIR);
            return ['status' => false, 'message' => 'Cập nhật quốc gia thất bại: ' . $existing->name];
        }

        // Ảnh cũ do crawler tải (kayak_*) mà khác ảnh mới -> xoá file
        if (!empty($data['image']) && !empty($existing->image) && $existing->image !== $data['image']) {
            $this->deleteMediaFiles([base_url(self::COUNTRY_MEDIA_DIR . $existing->image)], self::COUNTRY_MEDIA_DIR);
        }

        return ['status' => true, 'message' => 'Đã cập nhật quốc gia: ' . $existing->name, 'country_id' => $existing->id, 'slug' => $existing->slug];
    }

    /**
     * Quốc gia của thành phố / khách sạn: tìm theo link trang quốc gia -> theo slug tên,
     * chưa có thì tạo bản ghi chỉ có tên + link (ảnh, nội dung bổ sung khi crawl /crawler/brand/country).
     * @param int $continentId châu lục lấy từ breadcrumb (0 = không rõ); bản ghi đã có chỉ được bổ sung khi còn trống
     * @return int country_id, 0 nếu tên rỗng / insert lỗi
     */
    private function resolveCountry($name, $href = null, $continentId = 0)
    {
        $name = $this->cleanText((string) $name);
        $slug = $this->toSlug($name);
        if ($slug === '') {
            return 0;
        }
        // Chỉ nhận link đúng dạng trang quốc gia ".<số>.dc.html"
        $href = ($href && $this->isCountryHref($href)) ? $href : null;

        // Đã có quốc gia này -> chỉ BỔ SUNG chỗ còn trống (không ghi đè dữ liệu admin đã nhập)
        $existing = $this->Countries_model->getCountryByCrawlerHref($href)
            ?: $this->Countries_model->getCountryBySlug($slug);
        if ($existing) {
            $update = [];
            // Bản ghi nhập tay / từ dữ liệu cũ chưa có nguồn -> gắn link để lần sau khớp theo link
            if ($href && empty($existing->crawler_href)) {
                $update['crawler_href']   = $href;
                $update['crawler_credit'] = self::CRAWLER_CREDIT;
            }
            if ($continentId && empty($existing->continent_id)) {
                $update['continent_id'] = $continentId;
            }
            if ($update) {
                $update['updated_at'] = date('Y-m-d H:i:s');
                $this->Countries_model->updateCountry($existing->id, $update);
            }
            return (int) $existing->id;
        }

        // Chưa có -> tạo quốc gia tối giản (tên + link); ảnh / nội dung có sau khi crawl trang quốc gia
        return (int) $this->Countries_model->insertCountry([
            'name'           => $name,
            'slug'           => $slug,
            'continent_id'   => $continentId ?: null,
            'crawler_href'   => $href,
            'crawler_credit' => $href ? self::CRAWLER_CREDIT : null,
        ]);
    }

    /**
     * Trang châu lục "Cheap-Hotels-in-<Name>.<xx>.htc.html":
     *   tên <- TouristDestination.name (fallback breadcrumb), ảnh <- og:image -> uploads/continents/, nội dung <- FAQPage
     */
    private function parseContinent($html, $url)
    {
        $crawler = new Crawler($html, $url);
        $ld = $this->extractJsonLd($crawler);

        // Link chuẩn của trang; phải đúng dạng trang châu lục ".<xx>.htc.html"
        $crawlerHref = $this->canonicalHref($this->attr($crawler, 'meta[property="og:url"]', 'content'), $url);
        if (!$this->isContinentHref($crawlerHref)) {
            return ['status' => false, 'message' => 'Không phải trang châu lục KAYAK (".htc.html"): ' . $url];
        }

        // Cách lấy tên / ảnh / lưu DB giống parseCountry()
        $dest  = $this->firstOf($ld, $this->placeTypes);
        $crumb = $this->breadcrumbContinent($this->firstOf($ld, ['BreadcrumbList']));
        if (!$dest && !$crumb) {
            return ['status' => false, 'message' => 'Không tìm thấy dữ liệu châu lục (JSON-LD) - có thể trang bị chặn bot: ' . $url];
        }

        $name = ($dest && isset($dest['name']) && is_string($dest['name'])) ? $this->cleanText($dest['name']) : '';
        if ($name === '' && $crumb) {
            $name = $crumb['name'];
        }
        $slug = $this->toSlug($name);
        if ($slug === '') {
            return ['status' => false, 'message' => 'Không lấy được tên châu lục (slug rỗng), bỏ qua: ' . $url];
        }

        $this->newMediaFiles = [];
        $image   = null;
        $ogImage = $this->attr($crawler, 'meta[property="og:image"]', 'content');
        if ($ogImage) {
            $local = $this->downloadImage($ogImage, self::CONTINENT_MEDIA_DIR);
            $image = $local ? basename($local) : null;
        }

        $data = [
            'name'           => mb_substr($name, 0, 100),
            'image'          => $image,
            'content'        => $this->faqContent($ld),
            'crawler_href'   => $crawlerHref,
            'crawler_credit' => self::CRAWLER_CREDIT,
        ];

        $existing = $this->Continents_model->getContinentByCrawlerHref($crawlerHref)
            ?: $this->Continents_model->getContinentBySlug($slug);

        // ---- Thêm mới ----
        if (!$existing) {
            $data['slug'] = $slug;
            $id = $this->Continents_model->insertContinent($data);
            if (!$id) {
                $this->deleteMediaFiles($this->newMediaFiles, self::CONTINENT_MEDIA_DIR);
                return ['status' => false, 'message' => 'Insert châu lục thất bại: ' . $name];
            }
            return ['status' => true, 'message' => 'Đã thêm châu lục: ' . $name, 'continent_id' => $id, 'slug' => $slug];
        }

        // ---- Cập nhật: giữ tên, slug, thứ tự admin đã sửa; bỏ giá trị NULL ----
        unset($data['name']);
        $data = array_filter($data, function ($v) { return $v !== null; });
        $data['updated_at'] = date('Y-m-d H:i:s');

        if (!$this->Continents_model->updateContinent($existing->id, $data)) {
            $this->deleteMediaFiles($this->newMediaFiles, self::CONTINENT_MEDIA_DIR);
            return ['status' => false, 'message' => 'Cập nhật châu lục thất bại: ' . $existing->name];
        }
        if (!empty($data['image']) && !empty($existing->image) && $existing->image !== $data['image']) {
            $this->deleteMediaFiles([base_url(self::CONTINENT_MEDIA_DIR . $existing->image)], self::CONTINENT_MEDIA_DIR);
        }

        return ['status' => true, 'message' => 'Đã cập nhật châu lục: ' . $existing->name, 'continent_id' => $existing->id, 'slug' => $existing->slug];
    }

    /** Châu lục trong breadcrumb -> continent_id (tìm theo link -> slug, chưa có thì tạo chỉ có tên + link). 0 nếu không có. */
    private function resolveBreadcrumbContinent($breadcrumb)
    {
        $crumb = $this->breadcrumbContinent($breadcrumb);
        if (!$crumb) {
            return 0;
        }
        $slug = $this->toSlug($crumb['name']);
        if ($slug === '') {
            return 0;
        }

        // Đã có -> gắn link nguồn nếu còn thiếu, trả id; chưa có -> tạo châu lục chỉ có tên + link
        $existing = $this->Continents_model->getContinentByCrawlerHref($crumb['href'])
            ?: $this->Continents_model->getContinentBySlug($slug);
        if ($existing) {
            if (empty($existing->crawler_href)) {
                $this->Continents_model->updateContinent($existing->id, [
                    'crawler_href'   => $crumb['href'],
                    'crawler_credit' => self::CRAWLER_CREDIT,
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]);
            }
            return (int) $existing->id;
        }

        return (int) $this->Continents_model->insertContinent([
            'name'           => mb_substr($crumb['name'], 0, 100),
            'slug'           => $slug,
            'crawler_href'   => $crumb['href'],
            'crawler_credit' => self::CRAWLER_CREDIT,
        ]);
    }

    /**
     * Bài viết blog "https://www.kayak.com/news/<slug>/":
     *   tiêu đề  <- h1.entry-title (fallback Article.headline, og:title)
     *   nội dung <- .entry-content (đoạn văn, tiêu đề mục, danh sách) -> text thuần, site in bằng nl2br
     *   ảnh      <- og:image -> uploads/posts/
     *   ngày     <- Article.datePublished -> created_at
     */
    private function parsePost($html, $url)
    {
        $crawler = new Crawler($html, $url);
        $ld = $this->extractJsonLd($crawler);

        $crawlerHref = $this->canonicalHref($this->attr($crawler, 'meta[property="og:url"]', 'content'), $url);
        if (!$this->isPostHref($crawlerHref)) {
            return ['status' => false, 'message' => 'Không phải trang bài viết KAYAK (kayak.com/news/<slug>/): ' . $url];
        }

        // Node bài viết trong JSON-LD (có ngày đăng, tác giả)
        $article = $this->firstOf($ld, ['Article', 'BlogPosting', 'NewsArticle']);
        // Tiêu đề: thử lần lượt h1 -> headline -> og:title (bỏ đuôi " - KAYAK")
        $title   = $this->cleanText($this->text($crawler, 'h1'));
        if (!$title && $article && isset($article['headline']) && is_string($article['headline'])) {
            $title = $this->cleanText($article['headline']);
        }
        if (!$title) {
            $title = preg_replace('/\s+-\s+KAYAK$/i', '', (string) $this->cleanText($this->attr($crawler, 'meta[property="og:title"]', 'content')));
        }
        $title = mb_substr(trim((string) $title), 0, 255);
        if ($title === '') {
            return ['status' => false, 'message' => 'Không lấy được tiêu đề bài viết - có thể trang bị chặn bot: ' . $url];
        }

        // Nội dung bài -> text thuần
        $content = $this->postContent($crawler);
        if ($content === '') {
            return ['status' => false, 'message' => 'Không lấy được nội dung bài viết (.entry-content): ' . $url];
        }

        // Ảnh bìa (og:image) -> uploads/posts/
        $this->newMediaFiles = [];
        $image   = null;
        $ogImage = $this->attr($crawler, 'meta[property="og:image"]', 'content');
        if ($ogImage) {
            $local = $this->downloadImage($ogImage, self::POST_MEDIA_DIR);
            $image = $local ? basename($local) : null;
        }

        // Ngày đăng gốc (chuỗi ISO 8601) -> timestamp; không có -> false (sẽ dùng giờ hiện tại)
        $published = ($article && !empty($article['datePublished'])) ? strtotime((string) $article['datePublished']) : false;

        // Tác giả: node Person mà Article.author trỏ tới -> bảng authors (lỗi thì bài vẫn được lưu, chỉ không có tác giả)
        $person   = $this->postPerson($ld, $article);
        $authorId = null;
        $author   = null;
        if ($person) {
            $saved = $this->saveAuthor($person);
            if (!empty($saved['status'])) {
                $authorId = (int) $saved['author_id'];
                $author   = $saved['name'];
            }
        }

        $data = [
            'content'        => $content,
            'image'          => $image,
            'author_id'      => $authorId,
            'crawler_href'   => $crawlerHref,
            'crawler_credit' => mb_substr(self::CRAWLER_CREDIT . ($author ? ' - ' . $author : ''), 0, 500),
        ];

        // Đã crawl bài này chưa (theo link nguồn)
        /** @var object|null $existing */
        $existing = $this->Posts_model->getPostByCrawlerHref($crawlerHref);

        // ---- Thêm mới ----
        if (!$existing) {
            $data['title']        = $title;
            $data['slug']         = unique_slug('posts', $title);
            // ?publish=0 -> lưu nháp, mặc định đăng luôn
            $data['is_published'] = $this->param('publish') === '0' ? 0 : 1;
            $data['created_at']   = date('Y-m-d H:i:s', $published ?: time());
            $id = $this->Posts_model->insertPost($data);
            if (!$id) {
                $this->deleteMediaFiles($this->newMediaFiles, self::POST_MEDIA_DIR);
                return ['status' => false, 'message' => 'Insert bài viết thất bại: ' . $title];
            }
            return ['status' => true, 'message' => 'Đã thêm bài viết: ' . $title, 'post_id' => $id, 'slug' => $data['slug']];
        }

        // ---- Cập nhật: giữ tiêu đề, slug, trạng thái đăng admin đã sửa; bỏ giá trị NULL ----
        $data = array_filter($data, function ($v) { return $v !== null; });
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (!$this->Posts_model->updatePost($existing->id, $data)) {
            // Update lỗi -> xoá ảnh vừa tải, giữ nguyên ảnh cũ của bài
            $this->deleteMediaFiles($this->newMediaFiles, self::POST_MEDIA_DIR);
            return ['status' => false, 'message' => 'Cập nhật bài viết thất bại: ' . $existing->title];
        }
        // Ảnh đổi -> xoá file ảnh cũ do crawler tải (deleteMediaFiles chỉ xoá file kayak_*)
        if (!empty($data['image']) && !empty($existing->image) && $existing->image !== $data['image']) {
            $this->deleteMediaFiles([base_url(self::POST_MEDIA_DIR . $existing->image)], self::POST_MEDIA_DIR);
        }

        return ['status' => true, 'message' => 'Đã cập nhật bài viết: ' . $existing->title, 'post_id' => (int) $existing->id, 'slug' => $existing->slug];
    }

    /**
     * Nội dung bài từ .entry-content -> text thuần: mỗi đoạn / tiêu đề mục cách nhau 1 dòng trống,
     * các mục danh sách liền nhau ("• ..."). Bỏ nút CTA ("Find flights...") và mục lục (link "#section").
     */
    private function postContent(Crawler $crawler)
    {
        $root = $crawler->filter('.entry-content');
        if (!$root->count()) {
            return '';
        }

        $blocks = [];
        // .accordion-panel__header-text: câu hỏi trong khối FAQ (accordion), không nằm trong thẻ heading
        foreach ($root->first()->filter('h2, h3, h4, p, li, blockquote, .accordion-panel__header-text') as $node) {
            /** @var DOMElement $node */
            $tag = strtolower($node->nodeName);

            // Đoạn văn nằm trong li / blockquote đã được lấy cùng phần tử cha
            if ($tag === 'p' && $this->hasAncestor($node, ['li', 'blockquote'])) {
                continue;
            }
            // Mục lục: li chỉ chứa link nhảy tới "#section"
            if ($tag === 'li') {
                $links = $node->getElementsByTagName('a');
                if ($links->length === 1 && strpos((string) $links->item(0)->getAttribute('href'), '#') === 0
                    && trim($links->item(0)->textContent) === trim($node->textContent)) {
                    continue;
                }
            }

            // Chữ của khối, gộp khoảng trắng; khối rỗng thì bỏ
            $text = $this->cleanText(preg_replace('/\s+/u', ' ', (string) $node->textContent));
            if ($text === '' || $text === null) {
                continue;
            }
            // Ghi nhớ khối này có phải mục danh sách không (để quyết định cách xuống dòng ở dưới)
            $blocks[] = ['li' => $tag === 'li', 'text' => $tag === 'li' ? '• ' . $text : $text];
        }

        // Ghép các khối: 2 mục danh sách liền nhau cách 1 dòng, còn lại cách 1 dòng trống
        $content = '';
        foreach ($blocks as $i => $block) {
            if ($i > 0) {
                $content .= ($block['li'] && $blocks[$i - 1]['li']) ? "\n" : "\n\n";
            }
            $content .= $block['text'];
        }
        // Cắt bớt cho vừa cột TEXT
        return mb_substr($content, 0, self::POST_MAX_CONTENT);
    }

    /**
     * Node Person của tác giả trong JSON-LD (Yoast @graph). Thứ tự ưu tiên:
     *   1) Person có @id = Article.author[0].@id (bài nhiều tác giả -> lấy tác giả đầu)
     *   2) Person có url = $href (trang tác giả)
     *   3) Person đầu tiên
     */
    private function postPerson(array $ld, $article = null, $href = null)
    {
        // Gom các node Person có tên
        $people = [];
        foreach ($this->asList(isset($ld['Person']) ? $ld['Person'] : null) as $p) {
            if (is_array($p) && !empty($p['name'])) {
                $people[] = $p;
            }
        }
        if (!$people) {
            return null;
        }

        // Article.author có thể là 1 object hoặc mảng object {"@id": "..."} -> lấy @id của tác giả đầu
        $authorRef = ($article && isset($article['author'])) ? $article['author'] : null;
        if (is_array($authorRef) && $this->isList($authorRef)) {
            $authorRef = isset($authorRef[0]) ? $authorRef[0] : null;
        }
        $authorId = is_array($authorRef) && isset($authorRef['@id']) ? $authorRef['@id'] : null;

        foreach ($people as $p) {
            if ($authorId && isset($p['@id']) && $p['@id'] === $authorId) {
                return $p;
            }
        }
        foreach ($people as $p) {
            if ($href && isset($p['url']) && is_string($p['url']) && $this->canonicalHref($p['url'], $href) === $href) {
                return $p;
            }
        }
        // Bài có Article.author trỏ tới @id không tìm thấy -> không đoán bừa
        return $authorId ? null : $people[0];
    }

    /**
     * Lưu tác giả từ node Person: tên, ảnh (-> uploads/authors/), chức danh, tiểu sử, mạng xã hội (sameAs).
     * Nhận diện: link trang tác giả (crawler_href) -> slug. Bản ghi đã có: giữ tên/slug admin đã sửa,
     * chỉ cập nhật các trường crawl được (bỏ giá trị rỗng).
     * @return array ['status' => bool, 'message' => ..., 'author_id' => int, 'name' => string]
     */
    private function saveAuthor(array $person, $href = null)
    {
        // Tên (tối đa 150 ký tự theo cột DB) + slug
        $name = mb_substr(trim((string) $this->cleanText(is_string($person['name']) ? $person['name'] : '')), 0, 150);
        $slug = $this->toSlug($name);
        if ($name === '' || $slug === '') {
            return ['status' => false, 'message' => 'Không lấy được tên tác giả'];
        }

        // Link trang tác giả: truyền vào (khi crawl trang tác giả) hoặc lấy từ Person.url (khi crawl bài)
        if (!$href && isset($person['url']) && is_string($person['url'])) {
            $href = $this->canonicalHref($person['url'], self::AUTHOR_DEFAULT_URL);
        }
        $href = ($href && $this->isAuthorHref($href)) ? $href : null;

        // Ảnh: ImageObject {url|contentUrl} hoặc chuỗi URL
        $imageUrl = null;
        $img = isset($person['image']) ? $person['image'] : null;
        if (is_string($img)) {
            $imageUrl = $img;
        } elseif (is_array($img)) {
            $imageUrl = !empty($img['contentUrl']) ? $img['contentUrl'] : (!empty($img['url']) ? $img['url'] : null);
        }
        // Tải ảnh đại diện về uploads/authors/
        $avatar = null;
        if (is_string($imageUrl) && $imageUrl !== '') {
            $local  = $this->downloadImage($imageUrl, self::AUTHOR_MEDIA_DIR);
            $avatar = $local ? basename($local) : null;
        }

        // Link mạng xã hội (sameAs): chỉ giữ link http/https
        $social = [];
        foreach ($this->asList(isset($person['sameAs']) ? $person['sameAs'] : null) as $link) {
            if (is_string($link) && preg_match('#^https?://#i', $link)) {
                $social[] = mb_substr(trim($link), 0, 255);
            }
        }

        // Chức danh + tiểu sử (bỏ thẻ HTML)
        $jobTitle = isset($person['jobTitle']) && is_string($person['jobTitle']) ? trim((string) $this->cleanText($person['jobTitle'])) : '';
        $bio      = isset($person['description']) && is_string($person['description']) ? trim((string) $this->cleanText(strip_tags($person['description']))) : '';

        // Giá trị rỗng -> NULL để khi cập nhật bị array_filter bỏ qua (không xoá dữ liệu cũ)
        $data = [
            'avatar'         => $avatar,
            'job_title'      => $jobTitle !== '' ? mb_substr($jobTitle, 0, 150) : null,
            'bio'            => $bio !== '' ? $bio : null,
            'social_links'   => $social ? $this->toJson(array_values(array_unique($social))) : null,
            'crawler_href'   => $href,
            'crawler_credit' => self::CRAWLER_CREDIT,
        ];

        // Tìm tác giả đã có: theo link trang tác giả, không thấy thì theo slug tên
        $existing = ($href ? $this->Authors_model->getAuthorByCrawlerHref($href) : null)
            ?: $this->Authors_model->getAuthorBySlug($slug);

        // ---- Thêm mới ----
        if (!$existing) {
            $data['name'] = $name;
            $data['slug'] = $slug;
            $id = $this->Authors_model->insertAuthor($data);
            if (!$id) {
                return ['status' => false, 'message' => 'Insert tác giả thất bại: ' . $name];
            }
            return ['status' => true, 'message' => 'Đã thêm tác giả: ' . $name, 'author_id' => $id, 'name' => $name, 'slug' => $slug];
        }

        // ---- Cập nhật ----
        $data = array_filter($data, function ($v) { return $v !== null; });
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (!$this->Authors_model->updateAuthor($existing->id, $data)) {
            return ['status' => false, 'message' => 'Cập nhật tác giả thất bại: ' . $existing->name];
        }
        if (!empty($data['avatar']) && !empty($existing->avatar) && $existing->avatar !== $data['avatar']) {
            $this->deleteMediaFiles([base_url(self::AUTHOR_MEDIA_DIR . $existing->avatar)], self::AUTHOR_MEDIA_DIR);
        }
        return ['status' => true, 'message' => 'Đã cập nhật tác giả: ' . $existing->name, 'author_id' => (int) $existing->id, 'name' => $existing->name, 'slug' => $existing->slug];
    }

    /** Link trang tác giả: "https://www.kayak.com/news/author/<slug>/". */
    private function isAuthorHref($href)
    {
        return (bool) preg_match('#^https://www\.kayak\.com/news/author/[a-z0-9-]+/?$#', (string) $href);
    }

    // Phần tử HTML $node có nằm bên trong 1 thẻ thuộc $tags (vd li, blockquote) không
    private function hasAncestor(DOMNode $node, array $tags)
    {
        // Đi ngược lên từng thẻ cha cho tới gốc tài liệu
        for ($p = $node->parentNode; $p instanceof DOMElement; $p = $p->parentNode) {
            if (in_array(strtolower($p->nodeName), $tags, true)) {
                return true;
            }
        }
        return false;
    }

    /** Link bài viết: "https://www.kayak.com/news/<slug>/" (1 cấp, không phải feed/category/...). */
    private function isPostHref($href)
    {
        // $m[1] = phần slug sau /news/
        if (!preg_match('#^https://www\.kayak\.com/news/([a-z0-9-]+)/?$#', (string) $href, $m)) {
            return false;
        }
        // Loại các đường dẫn hệ thống (feed, category...)
        return !in_array($m[1], $this->newsReservedSlugs, true);
    }

    /** Trang danh sách bài: "/news/" hoặc "/news/category/<x>/". */
    private function isNewsListHref($href)
    {
        return (bool) preg_match('#^https://www\.kayak\.com/news(/category/[a-z0-9-]+)?/?$#', (string) $href);
    }

    /** Link bài viết trên trang danh sách, theo thứ tự xuất hiện (bài mới nhất trước), bỏ trùng. */
    private function newsLinks($html, $url)
    {
        $crawler = new Crawler($html, $url);
        $links   = [];
        // Duyệt mọi thẻ <a> có href, chuẩn hoá link rồi chỉ giữ link bài viết
        foreach ($crawler->filter('a[href]') as $a) {
            /** @var DOMElement $a */
            $href = $this->canonicalHref(trim((string) $a->getAttribute('href')), $url);
            if ($this->isPostHref($href) && !in_array($href, $links, true)) {
                $links[] = $href;
            }
        }
        return $links;
    }

    /** Nội dung từ FAQPage: "Câu hỏi\nTrả lời" cách nhau 1 dòng trống (site in bằng nl2br, không dùng HTML). */
    private function faqContent(array $ld)
    {
        $paragraphs = [];
        // FAQPage.mainEntity = danh sách câu hỏi {name: câu hỏi, acceptedAnswer: {text: câu trả lời}}
        $faqPage = $this->firstOf($ld, ['FAQPage']);
        foreach ($this->asList($faqPage && isset($faqPage['mainEntity']) ? $faqPage['mainEntity'] : null) as $q) {
            $question = (is_array($q) && isset($q['name'])) ? $this->cleanText($q['name']) : '';
            $answer   = (is_array($q) && isset($q['acceptedAnswer']['text'])) ? $this->cleanText(strip_tags($q['acceptedAnswer']['text'])) : '';
            if ($answer !== '') {
                $paragraphs[] = $question !== '' ? $question . "\n" . $answer : $answer;
            }
        }
        return $paragraphs ? implode("\n\n", $paragraphs) : null;
    }

        /**
     * Crawl lần lượt các khách sạn và gán vào $placeId.
     * @return array ['success' => [slug...], 'failed' => [['url' => ..., 'message' => ...]]]
     */
    private function crawlHotels(array $urls, $placeId)
    {
        $success = [];
        $failed  = [];

        foreach (array_slice($urls, 0, self::PLACE_MAX_HOTELS) as $i => $hotelUrl) {
            if ($i > 0) {
                sleep(1); // giãn request, tránh bị Kayak chặn bot
            }
            if (!$this->isAllowedUrl($hotelUrl)) {
                $failed[] = ['url' => $hotelUrl, 'message' => 'URL không hợp lệ'];
                continue;
            }
            $html = $this->curl_html($hotelUrl);
            if ($html === false) {
                $failed[] = ['url' => $hotelUrl, 'message' => 'Không tải được trang'];
                continue;
            }

            // Lưu khách sạn, gắn thẳng vào thành phố vừa crawl ($placeId)
            $r = $this->parseHotel($html, $hotelUrl, $placeId);
            if (!empty($r['status'])) {
                $success[] = $r['slug'];
            } else {
                $failed[] = ['url' => $hotelUrl, 'message' => isset($r['message']) ? $r['message'] : 'Lỗi không rõ'];
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }



    // ======================================================================
    // CÁC HÀM TIỆN ÍCH DÙNG CHUNG
    // ======================================================================

    // Đọc mọi thẻ <script type="application/ld+json"> trên trang, gom các node theo @type:
    //   ['Hotel' => [node, ...], 'BreadcrumbList' => [...], 'FAQPage' => [...], ...]
    // JSON-LD = dữ liệu có cấu trúc (schema.org) mà trang web nhúng sẵn cho Google -> dễ đọc hơn HTML
    private function extractJsonLd(Crawler $crawler)
    {
        $result = [];

        $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) use (&$result) {
            // Nội dung thẻ script -> mảng PHP; JSON hỏng thì bỏ qua thẻ này
            $json = json_decode(trim($node->text('', false)), true);
            if (!is_array($json)) {
                return;
            }

            // 3 kiểu viết JSON-LD: {"@graph": [...]} | [node, node] | 1 node đơn -> đưa hết về mảng node
            if (isset($json['@graph']) && is_array($json['@graph'])) {
                $items = $json['@graph'];
            } elseif ($this->isList($json)) {
                $items = $json;
            } else {
                $items = [$json];
            }

            foreach ($items as $item) {
                if (!is_array($item) || !isset($item['@type'])) {
                    continue;
                }
                // 1 node có thể có nhiều @type (vd ["Hotel", "LodgingBusiness"]) -> xếp vào cả 2 nhóm
                foreach ((array) $item['@type'] as $type) {
                    if (is_string($type) && $type !== '') {
                        $result[$type][] = $item;
                    }
                }
            }
        });

        return $result;
    }

    /** Node đầu tiên thuộc một trong các type. */
    private function firstOf(array $ld, array $types)
    {
        foreach ($types as $type) {
            if (!empty($ld[$type][0]) && is_array($ld[$type][0])) {
                return $ld[$type][0];
            }
        }
        return null;
    }

    /** Chuẩn hoá giá trị JSON-LD về list: null -> [], object/chuỗi đơn -> [giá trị], list giữ nguyên. */
    private function asList($value)
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }
        if (!is_array($value) || !$this->isList($value)) {
            return [$value];
        }
        return $value;
    }

    // Mảng dạng danh sách (key 0, 1, 2...) hay dạng object (key là chữ)?
    private function isList(array $arr)
    {
        return $arr === [] || array_keys($arr) === range(0, count($arr) - 1);
    }

    // Chữ của thẻ đầu tiên khớp CSS selector (vd 'h1'), không có -> null
    private function text(Crawler $crawler, $selector)
    {
        $node = $crawler->filter($selector);
        return $node->count() ? trim($node->first()->text()) : null;
    }

    // Giá trị 1 thuộc tính của thẻ đầu tiên khớp selector (vd content của meta og:image), không có -> null
    private function attr(Crawler $crawler, $selector, $attr)
    {
        $node = $crawler->filter($selector);
        return $node->count() ? $node->first()->attr($attr) : null;
    }

    // URL hợp lệ, dùng http/https và host nằm trong $allowedHosts (chỉ KAYAK)
    private function isAllowedUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);
        return isset($parts['scheme'], $parts['host'])
            && in_array($parts['scheme'], ['http', 'https'], true)
            && in_array(strtolower($parts['host']), $this->allowedHosts, true);
    }

    // In JSON ra trình duyệt (hàm cũ, hiện các hàm crawl dùng respond() của Crawler_Controller)
    private function json($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    // Tải HTML của 1 trang bằng cURL; lỗi / HTTP >= 400 / bị chuyển hướng ra ngoài KAYAK -> false
    public function curl_html($url)
    {
        $pointer = curl_init();
        curl_setopt($pointer, CURLOPT_URL, $url);
        curl_setopt($pointer, CURLOPT_TIMEOUT, 40);                 // tối đa 40 giây
        curl_setopt($pointer, CURLOPT_RETURNTRANSFER, true);        // trả HTML về biến thay vì in ra
        // Giả làm trình duyệt Chrome (không có User-Agent dễ bị chặn)
        curl_setopt($pointer, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36');
        curl_setopt($pointer, CURLOPT_HTTPHEADER, ['Accept-Language: en-US,en;q=0.9']);
        curl_setopt($pointer, CURLOPT_ENCODING, ''); // nhận gzip/br
        // Kiểm tra chứng chỉ SSL (CA bundle cấu hình trong php.ini: curl.cainfo)
        curl_setopt($pointer, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($pointer, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($pointer, CURLOPT_HEADER, false);               // không lấy phần header HTTP
        curl_setopt($pointer, CURLOPT_FOLLOWLOCATION, true);        // tự đi theo chuyển hướng (301/302)
        curl_setopt($pointer, CURLOPT_MAXREDIRS, 5);                // tối đa 5 lần chuyển hướng
        curl_setopt($pointer, CURLOPT_AUTOREFERER, true);
        // Chỉ http/https, kể cả khi redirect
        curl_setopt($pointer, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($pointer, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

        // Gửi request; lấy mã HTTP và URL cuối cùng (sau khi chuyển hướng)
        $html = curl_exec($pointer);
        $http_code = curl_getinfo($pointer, CURLINFO_HTTP_CODE);
        $final_url = curl_getinfo($pointer, CURLINFO_EFFECTIVE_URL);
        curl_close($pointer);

        if ($html === false || $http_code >= 400) {
            log_message('error', "Crawler Brand: HTTP {$http_code} khi tải {$url}");
            return false;
        }
        // Redirect ra ngoài whitelist -> không dùng nội dung đó
        if ($final_url && !$this->isAllowedUrl($final_url)) {
            log_message('error', "Crawler Brand: {$url} redirect ra host không cho phép: {$final_url}");
            return false;
        }
        // Kayak đôi khi lẫn ký tự NUL (vd trong link hreflang tiếng Thái) -> libxml dừng parse tại đó,
        // mất toàn bộ JSON-LD phía sau. Bỏ NUL trước khi đưa vào DomCrawler.
        return str_replace("\0", '', $html);
    }

    public function toSlug($doc)
    {
        $str = html_entity_decode((string) $doc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $str = $this->toNormal($str);
        $str = $this->transliterate($str);
        $str = strtolower($str);
        // Giữ chữ, số, khoảng trắng, "_" và "-"; các ký tự khác (/ + | & ' ...) bị bỏ như trước
        $str = preg_replace('/[^a-z0-9 _-]/', '', $str);
        // Khoảng trắng / "_" / chuỗi gạch liên tiếp -> một dấu "-"
        $str = preg_replace('/[\s_-]+/', '-', $str);
        return trim($str, '-');
    }

    /** Bỏ dấu các ngôn ngữ khác tiếng Việt (ñ, ü, ç, ø...) để slug không bị mất chữ. */
    private function transliterate($str)
    {
        if (function_exists('transliterator_transliterate')) {
            $out = transliterator_transliterate('Any-Latin; Latin-ASCII', $str);
            if ($out !== false && $out !== null) {
                return $out;
            }
        }
        if (function_exists('iconv')) {
            $out = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
            if ($out !== false) {
                return $out;
            }
        }
        return $str;
    }

    // Bỏ dấu tiếng Việt (cả chữ thường và chữ hoa): "Đà Nẵng" -> "Da Nang"
    public function toNormal($str)
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);
        return $str;
    }

    /** Bỏ escape \uXXXX còn sót và HTML entity (&amp;...) trong chuỗi text ngắn. */
    private function cleanText($text)
    {
        if ($text === null) {
            return null;
        }
        $text = decode_unicode_escapes((string) $text);
        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    // Chuẩn hoá link để so sánh / lưu crawler_href: luôn "https://www.kayak.com/<đường dẫn>", bỏ ?query và #...
    // $hotelUrl: link đọc được trên trang (og:url, JSON-LD url...), rỗng thì dùng $url đang crawl
    private function canonicalHref($hotelUrl, $url)
    {
        $candidate = (is_string($hotelUrl) && $hotelUrl !== '') ? $hotelUrl : $url;
        // Link tương đối "/abc" -> thêm tên miền
        if (strpos($candidate, '/') === 0 && strpos($candidate, '//') !== 0) {
            $candidate = 'https://www.kayak.com' . $candidate;
        }
        $parts = parse_url($candidate);
        // Link hỏng -> dùng link đang crawl
        if (empty($parts['host']) || empty($parts['path'])) {
            $parts = parse_url($url);
        }
        // Các tên miền phụ (bản dịch Google, không có www) -> quy về www.kayak.com
        $host = strtolower($parts['host']);
        if (in_array($host, ['www-kayak-com.translate.goog', 'kayak.com'], true)) {
            $host = 'www.kayak.com';
        }
        $path = isset($parts['path']) ? $parts['path'] : '/';
        // Cắt 255 ký tự cho vừa cột crawler_href
        return mb_substr('https://' . $host . $path, 0, 255);
    }

    /** ID khách sạn của Kayak trong URL: ".../Hotel-Name.311998.ksp" -> "311998". */
    private function kayakId($url)
    {
        return preg_match('/\.(\d+)\.ksp\b/', (string) $url, $m) ? $m[1] : null;
    }

        /** ID thành phố Kayak: ".../Phu-Quoc-Hotels.54054.hotel.ksp" -> "54054". */
    private function kayakCityId($url)
    {
        return preg_match('/\.(\d+)\.hotel\.ksp\b/', (string) $url, $m) ? $m[1] : null;
    }

    /**
     * Quốc gia lấy từ BreadcrumbList: phần tử có @id dạng "/Vietnam-Hotels.259.dc.html"
     * (".dc." = trang quốc gia; ".dr." là khu vực, ".as." là châu lục), tên "Vietnam" hoặc "Hotels in Vietnam".
     * @return array|null ['name' => 'Vietnam', 'href' => 'https://www.kayak.com/Vietnam-Hotels.259.dc.html']
     */
    private function breadcrumbCountry($breadcrumb)
    {
        // Duyệt từng mục breadcrumb, mục có link ".dc.html" là quốc gia
        foreach ($this->breadcrumbItems($breadcrumb) as $item) {
            $id = isset($item['@id']) ? (string) $item['@id'] : '';
            if (preg_match('/\.dc\.html$/', $id) && !empty($item['name']) && is_string($item['name'])) {
                $name = $this->cleanText(preg_replace('/^Hotels in\s+(the\s+)?/i', '', $item['name']));
                if ($name !== '') {
                    // @id là đường dẫn tương đối -> canonicalHref tự thêm https://www.kayak.com
                    return ['name' => $name, 'href' => $this->canonicalHref($id, self::COUNTRY_DEFAULT_URL)];
                }
            }
        }
        return null;
    }

    /**
     * Châu lục trong BreadcrumbList: @id dạng "/Cheap-Hotels-in-<Name>.<xx>.htc.html"
     * (as = Asia, na = North America, eu = Europe...), tên "Asia" hoặc "Hotels in North America".
     * @return array|null ['name' => 'North America', 'href' => 'https://www.kayak.com/Cheap-Hotels-in-North-America.na.htc.html']
     */
    private function breadcrumbContinent($breadcrumb)
    {
        foreach ($this->breadcrumbItems($breadcrumb) as $item) {
            $id = isset($item['@id']) ? (string) $item['@id'] : '';
            if ($this->isContinentHref($id) && !empty($item['name']) && is_string($item['name'])) {
                $name = $this->cleanText(preg_replace('/^(Cheap\s+)?Hotels in\s+(the\s+)?/i', '', $item['name']));
                if ($name !== '') {
                    return ['name' => mb_substr($name, 0, 100), 'href' => $this->canonicalHref($id, self::CONTINENT_DEFAULT_URL)];
                }
            }
        }
        return null;
    }

    /** Link trang châu lục Kayak: ".../Cheap-Hotels-in-Asia.as.htc.html". */
    private function isContinentHref($href)
    {
        return (bool) preg_match('/\.[a-z]{2}\.htc\.html$/', (string) $href);
    }

    /** Các item của BreadcrumbList (phần tử có thể bọc trong "item" hoặc không). */
    private function breadcrumbItems($breadcrumb)
    {
        $items  = ($breadcrumb && isset($breadcrumb['itemListElement'])) ? $breadcrumb['itemListElement'] : null;
        $result = [];
        foreach ($this->asList($items) as $el) {
            $item = (is_array($el) && isset($el['item'])) ? $el['item'] : $el;
            if (is_array($item)) {
                $result[] = $item;
            }
        }
        return $result;
    }

    /** Link trang quốc gia Kayak: ".../Vietnam-Hotels.259.dc.html". */
    private function isCountryHref($href)
    {
        return (bool) preg_match('/\.\d+\.dc\.html$/', (string) $href);
    }

        /** Link trang thành phố trong BreadcrumbList của trang khách sạn: "/Phu-Quoc-Hotels.54054.hotel.ksp". */
    private function breadcrumbCityHref(array $ld)
    {
        $breadcrumb = $this->firstOf($ld, ['BreadcrumbList']);
        $items = ($breadcrumb && isset($breadcrumb['itemListElement'])) ? $breadcrumb['itemListElement'] : null;
        foreach ($this->asList($items) as $el) {
            $item = (is_array($el) && isset($el['item'])) ? $el['item'] : $el;
            $id   = isset($item['@id']) ? (string) $item['@id'] : '';
            if ($this->kayakCityId($id)) {
                // @id là đường dẫn tương đối -> canonicalHref tự thêm https://www.kayak.com
                return $this->canonicalHref($id, self::PLACE_DEFAULT_URL);
            }
        }
        return null;
    }

        /**
     * Place (thành phố) của khách sạn khi không được chỉ định sẵn.
     * @return int place_id, 0 nếu không xác định được
     */
    private function resolveHotelPlace(array $ld, array $address)
    {
        // a) Theo link trang thành phố trong breadcrumb
        $cityHref = $this->breadcrumbCityHref($ld);
        if ($cityHref) {
            $place = $this->Places_model->getPlaceByCrawlerHref($cityHref);
            if ($place) {
                return (int) $place->id;
            }
            // Chưa có -> crawl trang thành phố (chỉ địa điểm, KHÔNG crawl kèm khách sạn)
            $html = $this->curl_html($cityHref);
            if ($html !== false) {
                $r = $this->parsePlace($html, $cityHref);
                if (!empty($r['status'])) {
                    return (int) $r['place_id'];
                }
            }
        }

        // b) Dự phòng: theo tên thành phố trong địa chỉ khách sạn
        $city = isset($address['addressLocality']) ? $this->schemaName($address['addressLocality']) : '';
        $slug = $this->toSlug($city);
        if ($slug === '') {
            return 0;
        }

        // Quốc gia: breadcrumb trang khách sạn (có link .dc.html) -> addressCountry (chỉ có tên)
        $breadcrumb  = $this->firstOf($ld, ['BreadcrumbList']);
        $crumb       = $this->breadcrumbCountry($breadcrumb);
        $countryName = $crumb ? $crumb['name'] : (isset($address['addressCountry']) ? $this->schemaName($address['addressCountry']) : '');
        $countryId   = $countryName !== ''
            ? $this->resolveCountry($countryName, $crumb ? $crumb['href'] : null, $this->resolveBreadcrumbContinent($breadcrumb))
            : 0;

        // Đã có thành phố cùng slug -> dùng luôn (bổ sung quốc gia nếu còn trống)
        $place = $this->Places_model->getPlaceBySlug($slug);
        if ($place) {
            if ($countryId && empty($place->country_id)) {
                $this->Places_model->updatePlace($place->id, ['country_id' => $countryId]);
            }
            return (int) $place->id;
        }

        // Chưa có -> tạo thành phố tối giản (tên + quốc gia + địa chỉ)
        return (int) $this->Places_model->insertPlace([
            'name'           => $city,
            'slug'           => $slug,
            'country_id'     => $countryId ?: null,
            'address'        => implode(', ', array_filter([$city, $countryName])),
            // Lưu link thành phố -> lần sau crawl địa điểm sẽ cập nhật đúng bản ghi này (ảnh, nội dung)
            'crawler_href'   => $cityHref,
            'crawler_credit' => $cityHref ? self::CRAWLER_CREDIT : null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    /** Giá trị schema.org có thể là chuỗi hoặc object {name: ...} (vd addressCountry). */
    private function schemaName($value)
    {
        if (is_array($value)) {
            $value = isset($value['name']) ? $value['name'] : null;
        }
        return is_scalar($value) ? $this->cleanText((string) $value) : '';
    }

    /** aggregateRating {ratingValue, bestRating} -> số sao 1..5 (NULL nếu không có điểm). */
    private function toStars($rating)
    {
        if (empty($rating['ratingValue']) || !is_numeric($rating['ratingValue'])) {
            return null;
        }
        // Thang điểm tối đa (thường 10); quy đổi: 8.3/10 -> 4.15 -> làm tròn 4 sao
        $best  = (!empty($rating['bestRating']) && is_numeric($rating['bestRating'])) ? (float) $rating['bestRating'] : 10.0;
        $stars = round((float) $rating['ratingValue'] / $best * 5);
        return max(1, min(5, $stars));
    }

    /**
     * Tải danh sách ảnh về assets/media, bỏ trùng, tối đa MAX_IMAGES ảnh tải thành công.
     * @return array URL local (base_url) theo đúng thứ tự nguồn
     */
    private function downloadImages(array $urls)
    {
        $result   = [];
        $hashes   = []; // md5 nội dung: cùng 1 ảnh nhưng khác URL (vd ảnh đại diện = ảnh album đầu) chỉ giữ 1
        $attempts = 0;
        foreach (array_unique(array_filter($urls, 'is_string')) as $url) {
            // Giới hạn số lần thử để 1 trang có nhiều ảnh lỗi không làm request quá lâu
            if (count($result) >= self::MAX_IMAGES || $attempts >= self::MAX_IMAGES * 2) {
                break;
            }
            $attempts++;
            // Tải 1 ảnh; lỗi hoặc đã có trong kết quả -> bỏ qua
            $local = $this->downloadImage($url);
            if (!$local || in_array($local, $result, true)) {
                continue;
            }
            // Dấu vân tay nội dung file để phát hiện 2 URL khác nhau nhưng cùng 1 ảnh
            $hash = @md5_file(FCPATH . self::MEDIA_DIR . basename($local));
            if ($hash === false) {
                // Không đọc được (file vừa ghi đang bị Windows/antivirus khoá) -> coi là ảnh riêng,
                // không để mọi ảnh lỗi cùng rơi vào hash false rồi bị xoá nhầm như "ảnh trùng".
                $hash = 'url:' . $local;
            }
            if (isset($hashes[$hash])) {
                // Ảnh trùng nội dung: file vừa tải mới thì xoá luôn
                if (in_array($local, $this->newMediaFiles, true)) {
                    $this->deleteMediaFiles([$local]);
                    $this->newMediaFiles = array_values(array_diff($this->newMediaFiles, [$local]));
                }
                continue;
            }
            $hashes[$hash] = true;
            $result[]      = $local;
        }
        return $result;
    }

    /**
     * Tải 1 ảnh về assets/media. Tên file = kayak_md5(url) nên crawl lại không tải trùng.
     * @return string|null URL local, NULL nếu không tải được / không phải ảnh
     */
    private function downloadImage($url, $mediaDir = self::MEDIA_DIR)
    {
        $url = trim($url);
        // Link dạng "//cdn..." -> thêm https:
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }
        $parts  = parse_url($url);
        $host   = isset($parts['host']) ? strtolower($parts['host']) : '';
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        // Chỉ tải ảnh từ host trong $imageHosts (khớp đúng tên hoặc đuôi ".r9cdn.net", ".kayak.com")
        $hostOk = false;
        foreach ($this->imageHosts as $allowed) {
            if ($host === $allowed || substr($host, -strlen('.' . $allowed)) === '.' . $allowed) {
                $hostOk = true;
                break;
            }
        }
        if (!$hostOk || !in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        // Tên file cố định theo URL: cùng 1 URL ảnh -> cùng 1 tên file
        $dir  = FCPATH . $mediaDir;
        $base = 'kayak_' . md5($url);

        // Đã tải ở lần crawl trước -> dùng lại
        foreach (['jpg', 'png', 'webp'] as $ext) {
            if (is_file($dir . $base . '.' . $ext)) {
                return base_url($mediaDir . $base . '.' . $ext);
            }
        }

        // Tải nội dung ảnh (giới hạn dung lượng MAX_IMAGE_BYTES = 5MB)
        $pointer = curl_init();
        curl_setopt_array($pointer, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_MAXFILESIZE    => self::MAX_IMAGE_BYTES,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        ]);
        $body = curl_exec($pointer);
        $code = curl_getinfo($pointer, CURLINFO_HTTP_CODE);
        curl_close($pointer);

        if (!is_string($body) || $code !== 200 || $body === '' || strlen($body) > self::MAX_IMAGE_BYTES) {
            log_message('error', "Crawler Brand: không tải được ảnh (HTTP {$code}) {$url}");
            return null;
        }

        // Đuôi file lấy theo nội dung thật, không theo URL (tránh ghi file .php...)
        $info = @getimagesizefromstring($body);
        $map  = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
        $ext  = ($info && isset($map[$info[2]])) ? $map[$info[2]] : null;
        if (!$ext) {
            log_message('error', "Crawler Brand: file tải về không phải ảnh JPG/PNG/WEBP {$url}");
            return null;
        }

        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            log_message('error', "Crawler Brand: không tạo được thư mục {$dir}");
            return null;
        }
        $file = $base . '.' . $ext;
        if (file_put_contents($dir . $file, $body, LOCK_EX) === false) {
            log_message('error', "Crawler Brand: không ghi được ảnh {$dir}{$file}");
            return null;
        }

        // Ghi nhớ file mới tạo trong lần crawl này (lưu DB lỗi thì xoá đi)
        $this->newMediaFiles[] = base_url($mediaDir . $file);
        return base_url($mediaDir . $file);
    }

    /** Xoá các ảnh trong assets/media do crawler tải (tên kayak_<md5>), không đụng ảnh khác. */
    private function deleteMediaFiles(array $urls, $mediaDir = self::MEDIA_DIR)
    {
        $prefix = base_url($mediaDir);
        foreach ($urls as $url) {
            // Chỉ xử lý URL nằm trong đúng thư mục ảnh này
            if (!is_string($url) || strpos($url, $prefix) !== 0) {
                continue;
            }
            // Lấy tên file; chỉ xoá file đúng mẫu crawler đặt tên (không xoá nhầm ảnh admin upload)
            $file = substr($url, strlen($prefix));
            if (preg_match('/^kayak_[0-9a-f]{32}\.(jpg|png|webp)$/', $file) && is_file(FCPATH . $mediaDir . $file)) {
                @unlink(FCPATH . $mediaDir . $file);
            }
        }
    }

    // Mảng -> chuỗi JSON để lưu DB (giữ nguyên chữ có dấu và dấu "/" cho dễ đọc)
    private function toJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}