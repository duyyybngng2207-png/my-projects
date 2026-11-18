<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Symfony\Component\DomCrawler\Crawler;

/**
 * Crawler khách sạn từ trang chi tiết KAYAK (Symfony DomCrawler).
 *    /crawler/brand                 -> crawl URL mặc định
 *    /crawler/brand?url=<kayak-url> -> crawl URL tuỳ chọn (chỉ domain KAYAK)
 *    POST /crawler/brand            -> form "Crawl" trong admin khách sạn, quay lại admin/hotels
 * Dữ liệu chính lấy từ JSON-LD (schema.org); ảnh tải về assets/media/ (tối đa 13 ảnh/khách sạn).
 *
 * @property Hotels_model $Hotels_model
 * @property Places_model $Places_model
 */
class Brand extends Crawler_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Hotels_model');
        $this->load->model('Places_model');
        $this->load->model('Categories_model');

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

    /** Chỉ tải ảnh từ CDN ảnh của Kayak (khớp đuôi host). */
    private $imageHosts = ['r9cdn.net'];

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


    public function index()
    {
        // GET ?url=... hoặc POST từ form admin; không truyền thì dùng URL mặc định
        $url = $this->param('url') ?: self::DEFAULT_URL;
        if (!$this->isAllowedUrl($url)) {
            return $this->respond(['status' => false, 'message' => 'URL không hợp lệ: ' . $url], 'admin/hotels');
        }

        $html = $this->curl_html($url);
        if ($html === false) {
            return $this->respond(['status' => false, 'message' => 'Không tải được trang: ' . $url], 'admin/hotels');
        }

        // Admin chọn địa điểm trên form -> dùng; không chọn / id không tồn tại -> tự xác định theo thành phố
        $placeId = (int) $this->param('place_id');
        if ($placeId > 0 && !$this->Places_model->placeExists($placeId)) {
            $placeId = 0;
        }

        $result = $this->parseHotel($html, $url, $placeId ?: null);


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

        $result = $this->parsePlace($html, $url);
        // ?with_hotels=0 -> chỉ crawl địa điểm, không crawl khách sạn
        if (!empty($result['status']) && !empty($result['hotel_urls']) && $this->param('with_hotels') !== '0') {
            $hotels = $this->crawlHotels($result['hotel_urls'], $result['place_id']);
            $result['hotels']   = $hotels;
            $result['message'] .= sprintf('. Khách sạn: %d thành công, %d lỗi', count($hotels['success']), count($hotels['failed']));
        }
        unset($result['hotel_urls']);

        return $this->respond($result, 'admin/places');
    }


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

        $address = isset($hotel['address']) ? $hotel['address'] : [];
        if (!is_array($address)) {
            $address = ['streetAddress' => (string) $address];
        }
        
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

        // Review
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
        ];

        

        $existingHotelCrawlerHref  = $this->Hotels_model->getHotelByCrawlerHref($crawlerHref , $sourceId);
        $existingHotelSlug = $this->Hotels_model->getHotelBySlug($slug);
        if(!$existingHotelCrawlerHref) {
            $finalSlug = $slug;
            if($existingHotelSlug) {
                $finalSlug = $slug . '-' . ($sourceId ?: uniqid());
            }

            $params_brand['slug'] = $finalSlug;
            $hotel_id = $this->Hotels_model->insertHotel($params_brand);
            if(!$hotel_id) {
                $this->deleteMediaFiles($this->newMediaFiles);
                return [
                    'status'   => false,
                    'message' => 'Insert khách sạn thất bại: ' . $name
                ];
            }
        } else {
            $hotel_id     = $existingHotelCrawlerHref->id;
            $finalSlug    = $existingHotelCrawlerHref->slug;

            // place_id luôn cập nhật: crawl lại sẽ chuyển khách sạn từ place quốc gia (cũ) sang place thành phố
            $fields = ['phone', 'address', 'price', 'rating', 'rating_count', 'thumbnail', 'albums', 'amenities', 'faqs', 'place_id'];
            $update = array_intersect_key($params_brand, array_flip($fields));


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

        // 2) Địa chỉ = "Thành phố, Quốc gia" (cột address NOT NULL)
        $country = $this->breadcrumbCountry($this->firstOf($ld, ['BreadcrumbList']));
        $address = implode(', ', array_filter([$name, $country]));

        // 3) Nội dung: các câu hỏi/đáp FAQ (site in content bằng nl2br -> dùng xuống dòng, không dùng HTML)
        $paragraphs = [];
        $faqPage = $this->firstOf($ld, ['FAQPage']);
        foreach ($this->asList($faqPage && isset($faqPage['mainEntity']) ? $faqPage['mainEntity'] : null) as $q) {
            $question = (is_array($q) && isset($q['name'])) ? $this->cleanText($q['name']) : '';
            $answer   = (is_array($q) && isset($q['acceptedAnswer']['text'])) ? $this->cleanText(strip_tags($q['acceptedAnswer']['text'])) : '';
            if ($answer !== '') {
                $paragraphs[] = $question !== '' ? $question . "\n" . $answer : $answer;
            }
        }
        $content = $paragraphs ? implode("\n\n", $paragraphs) : null;

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
            'crawler_credit' => 'Nguồn: KAYAK',
        ];
        if ($categoryId > 0) {
            $data['category_id'] = $categoryId;
        }

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
        $existing = $this->Places_model->getPlaceByCrawlerHref($data['crawler_href']);
        if (!$existing) {
            $bySlug = $this->Places_model->getPlaceBySlug($slug);
            if ($bySlug && empty($bySlug->crawler_href)) {
                $existing = $bySlug;
            }
        }

        // ---- Thêm mới ----
        if (!$existing) {
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

            $r = $this->parseHotel($html, $hotelUrl, $placeId);
            if (!empty($r['status'])) {
                $success[] = $r['slug'];
            } else {
                $failed[] = ['url' => $hotelUrl, 'message' => isset($r['message']) ? $r['message'] : 'Lỗi không rõ'];
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }



    private function extractJsonLd(Crawler $crawler)
    {
        $result = [];

        $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) use (&$result) {
            $json = json_decode(trim($node->text('', false)), true);
            if (!is_array($json)) {
                return;
            }

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

    private function isList(array $arr)
    {
        return $arr === [] || array_keys($arr) === range(0, count($arr) - 1);
    }

    private function text(Crawler $crawler, $selector)
    {
        $node = $crawler->filter($selector);
        return $node->count() ? trim($node->first()->text()) : null;
    }

    private function attr(Crawler $crawler, $selector, $attr)
    {
        $node = $crawler->filter($selector);
        return $node->count() ? $node->first()->attr($attr) : null;
    }

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

    private function json($data, $status = 200)
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function curl_html($url)
    {
        $pointer = curl_init();
        curl_setopt($pointer, CURLOPT_URL, $url);
        curl_setopt($pointer, CURLOPT_TIMEOUT, 40);
        curl_setopt($pointer, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($pointer, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36');
        curl_setopt($pointer, CURLOPT_HTTPHEADER, ['Accept-Language: en-US,en;q=0.9']);
        curl_setopt($pointer, CURLOPT_ENCODING, ''); // nhận gzip/br
        // Kiểm tra chứng chỉ SSL (CA bundle cấu hình trong php.ini: curl.cainfo)
        curl_setopt($pointer, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($pointer, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($pointer, CURLOPT_HEADER, false);
        curl_setopt($pointer, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($pointer, CURLOPT_MAXREDIRS, 5);
        curl_setopt($pointer, CURLOPT_AUTOREFERER, true);
        // Chỉ http/https, kể cả khi redirect
        curl_setopt($pointer, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($pointer, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

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

    private function canonicalHref($hotelUrl, $url)
    {
        $candidate = (is_string($hotelUrl) && $hotelUrl !== '') ? $hotelUrl : $url;
        if (strpos($candidate, '/') === 0 && strpos($candidate, '//') !== 0) {
            $candidate = 'https://www.kayak.com' . $candidate;
        }
        $parts = parse_url($candidate);
        if (empty($parts['host']) || empty($parts['path'])) {
            $parts = parse_url($url);
        }
        $host = strtolower($parts['host']);
        if (in_array($host, ['www-kayak-com.translate.goog', 'kayak.com'], true)) {
            $host = 'www.kayak.com';
        }
        $path = isset($parts['path']) ? $parts['path'] : '/';
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
     * (".dc." = trang quốc gia; ".dr." là khu vực, ".as." là châu lục), tên "Hotels in Vietnam".
     */
    private function breadcrumbCountry($breadcrumb)
    {
        $items = ($breadcrumb && isset($breadcrumb['itemListElement'])) ? $breadcrumb['itemListElement'] : null;
        foreach ($this->asList($items) as $el) {
            $item = (is_array($el) && isset($el['item'])) ? $el['item'] : $el;
            $id   = isset($item['@id']) ? (string) $item['@id'] : '';
            if (preg_match('/\.dc\.html$/', $id) && !empty($item['name'])) {
                return $this->cleanText(preg_replace('/^Hotels in\s+(the\s+)?/i', '', $item['name']));
            }
        }
        return null;
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
        $city    = isset($address['addressLocality']) ? $this->schemaName($address['addressLocality']) : '';
        $country = isset($address['addressCountry'])  ? $this->schemaName($address['addressCountry'])  : '';
        $slug    = $this->toSlug($city);
        if ($slug === '') {
            return 0;
        }
        $place = $this->Places_model->getPlaceBySlug($slug);
        if ($place) {
            return (int) $place->id;
        }

        return (int) $this->Places_model->insertPlace([
            'name'           => $city,
            'slug'           => $slug,
            'address'        => implode(', ', array_filter([$city, $country])),
            // Lưu link thành phố -> lần sau crawl địa điểm sẽ cập nhật đúng bản ghi này (ảnh, nội dung)
            'crawler_href'   => $cityHref,
            'crawler_credit' => $cityHref ? 'Nguồn: KAYAK' : null,
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
            $local = $this->downloadImage($url);
            if (!$local || in_array($local, $result, true)) {
                continue;
            }
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
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }
        $parts  = parse_url($url);
        $host   = isset($parts['host']) ? strtolower($parts['host']) : '';
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
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

        $dir  = FCPATH . $mediaDir;
        $base = 'kayak_' . md5($url);

        // Đã tải ở lần crawl trước -> dùng lại
        foreach (['jpg', 'png', 'webp'] as $ext) {
            if (is_file($dir . $base . '.' . $ext)) {
                return base_url($mediaDir . $base . '.' . $ext);
            }
        }

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

        $this->newMediaFiles[] = base_url($mediaDir . $file);
        return base_url($mediaDir . $file);
    }

    /** Xoá các ảnh trong assets/media do crawler tải (tên kayak_<md5>), không đụng ảnh khác. */
    private function deleteMediaFiles(array $urls, $mediaDir = self::MEDIA_DIR)
    {
        $prefix = base_url($mediaDir);
        foreach ($urls as $url) {
            if (!is_string($url) || strpos($url, $prefix) !== 0) {
                continue;
            }
            $file = substr($url, strlen($prefix));
            if (preg_match('/^kayak_[0-9a-f]{32}\.(jpg|png|webp)$/', $file) && is_file(FCPATH . $mediaDir . $file)) {
                @unlink(FCPATH . $mediaDir . $file);
            }
        }
    }

    private function toJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}