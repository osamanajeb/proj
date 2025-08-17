<?php
/**
 * فئة البحث المتقدم
 * Advanced search class
 */

class Search {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * البحث المتقدم في المنتجات
     * Advanced product search
     */
    public function advancedSearch($params) {
        $query = "SELECT DISTINCT p.*, c.name as category_name, 
                  MATCH(p.name, p.description) AGAINST(:search_term IN NATURAL LANGUAGE MODE) as relevance
                  FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 'active'";
        
        $search_params = [];
        
        // البحث النصي
        if (!empty($params['q'])) {
            $query .= " AND (MATCH(p.name, p.description) AGAINST(:search_term IN NATURAL LANGUAGE MODE) 
                        OR p.name LIKE :search_like 
                        OR p.description LIKE :search_like)";
            $search_params[':search_term'] = $params['q'];
            $search_params[':search_like'] = '%' . $params['q'] . '%';
        }
        
        // تصفية حسب الفئة
        if (!empty($params['category_id'])) {
            $query .= " AND p.category_id = :category_id";
            $search_params[':category_id'] = $params['category_id'];
        }
        
        // تصفية حسب نطاق السعر
        if (!empty($params['min_price'])) {
            $query .= " AND (COALESCE(p.discount_price, p.price) >= :min_price)";
            $search_params[':min_price'] = $params['min_price'];
        }
        
        if (!empty($params['max_price'])) {
            $query .= " AND (COALESCE(p.discount_price, p.price) <= :max_price)";
            $search_params[':max_price'] = $params['max_price'];
        }
        
        // تصفية حسب التقييم
        if (!empty($params['min_rating'])) {
            $query .= " AND p.id IN (
                SELECT r.product_id FROM reviews r 
                WHERE r.status = 'approved' 
                GROUP BY r.product_id 
                HAVING AVG(r.rating) >= :min_rating
            )";
            $search_params[':min_rating'] = $params['min_rating'];
        }
        
        // تصفية حسب التوفر
        if (!empty($params['in_stock'])) {
            $query .= " AND p.stock_quantity > 0";
        }
        
        // تصفية المنتجات المميزة
        if (!empty($params['featured'])) {
            $query .= " AND p.featured = 1";
        }
        
        // تصفية المنتجات المخفضة
        if (!empty($params['on_sale'])) {
            $query .= " AND p.discount_price IS NOT NULL";
        }
        
        // الترتيب
        $order_by = "ORDER BY ";
        switch ($params['sort'] ?? 'relevance') {
            case 'price_low':
                $order_by .= "COALESCE(p.discount_price, p.price) ASC";
                break;
            case 'price_high':
                $order_by .= "COALESCE(p.discount_price, p.price) DESC";
                break;
            case 'newest':
                $order_by .= "p.created_at DESC";
                break;
            case 'name':
                $order_by .= "p.name ASC";
                break;
            case 'rating':
                $order_by .= "(SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved') DESC";
                break;
            default:
                if (!empty($params['q'])) {
                    $order_by .= "relevance DESC, p.featured DESC, p.created_at DESC";
                } else {
                    $order_by .= "p.featured DESC, p.created_at DESC";
                }
        }
        
        $query .= " " . $order_by;
        
        // الحد الأقصى للنتائج
        $limit = min($params['limit'] ?? 50, 100);
        $offset = ($params['page'] ?? 1 - 1) * $limit;
        $query .= " LIMIT :limit OFFSET :offset";
        
        $search_params[':limit'] = $limit;
        $search_params[':offset'] = $offset;
        
        $stmt = $this->conn->prepare($query);
        foreach ($search_params as $key => $value) {
            if (in_array($key, [':limit', ':offset', ':category_id', ':min_rating'])) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * البحث بالاقتراحات التلقائية
     * Search with auto-suggestions
     */
    public function getSearchSuggestions($term, $limit = 10) {
        $query = "SELECT DISTINCT name FROM products 
                  WHERE status = 'active' AND name LIKE :term 
                  ORDER BY name ASC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':term', $term . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name');
    }

    /**
     * البحث الصوتي (Phonetic Search)
     * Phonetic search
     */
    public function phoneticSearch($term) {
        // تحويل النص إلى صيغة صوتية مبسطة
        $phonetic = $this->arabicPhonetic($term);
        
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 'active' 
                  AND (SOUNDEX(p.name) = SOUNDEX(:term) 
                       OR p.name LIKE :phonetic_like)
                  ORDER BY p.featured DESC, p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':term' => $term,
            ':phonetic_like' => '%' . $phonetic . '%'
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * تحويل النص العربي إلى صيغة صوتية
     * Convert Arabic text to phonetic
     */
    private function arabicPhonetic($text) {
        $replacements = [
            'ا' => 'ا', 'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
            'ة' => 'ه', 'ت' => 'ت',
            'ث' => 'ت', 'س' => 'س', 'ص' => 'س',
            'ذ' => 'د', 'ز' => 'د', 'ظ' => 'د',
            'ض' => 'د', 'ط' => 'ت'
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * البحث بالباركود
     * Barcode search
     */
    public function searchByBarcode($barcode) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE p.barcode = :barcode AND p.status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':barcode' => $barcode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * البحث المرئي (بالصورة)
     * Visual search (by image)
     */
    public function visualSearch($image_features) {
        // هذه وظيفة متقدمة تتطلب معالجة الصور والذكاء الاصطناعي
        // يمكن تطويرها لاحقاً باستخدام خدمات مثل Google Vision API
        
        return [];
    }

    /**
     * حفظ استعلام البحث
     * Save search query
     */
    public function saveSearchQuery($user_id, $query, $results_count) {
        $sql = "INSERT INTO search_history (user_id, query, results_count) 
                VALUES (:user_id, :query, :results_count)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':user_id' => $user_id,
            ':query' => $query,
            ':results_count' => $results_count
        ]);
    }

    /**
     * الحصول على تاريخ البحث
     * Get search history
     */
    public function getSearchHistory($user_id, $limit = 20) {
        $query = "SELECT * FROM search_history 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على أشهر عمليات البحث
     * Get popular searches
     */
    public function getPopularSearches($limit = 10) {
        $query = "SELECT query, COUNT(*) as search_count 
                  FROM search_history 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                  GROUP BY query 
                  ORDER BY search_count DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * البحث الذكي بالمرادفات
     * Smart search with synonyms
     */
    public function smartSearch($term) {
        $synonyms = $this->getSynonyms($term);
        $all_terms = array_merge([$term], $synonyms);
        
        $placeholders = str_repeat('?,', count($all_terms) - 1) . '?';
        
        $query = "SELECT DISTINCT p.*, c.name as category_name 
                  FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE p.status = 'active' 
                  AND (p.name REGEXP CONCAT('(^|[[:space:]])(', REPLACE(?, '|', '|'), ')([[:space:]]|$)') 
                       OR p.description REGEXP CONCAT('(^|[[:space:]])(', REPLACE(?, '|', '|'), ')([[:space:]]|$)'))
                  ORDER BY p.featured DESC, p.created_at DESC";
        
        $search_pattern = implode('|', $all_terms);
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$search_pattern, $search_pattern]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على المرادفات
     * Get synonyms
     */
    private function getSynonyms($term) {
        // قاموس مرادفات مبسط
        $synonyms_map = [
            'هاتف' => ['جوال', 'موبايل', 'تليفون'],
            'حاسوب' => ['كمبيوتر', 'لابتوب', 'جهاز'],
            'ملابس' => ['أزياء', 'كساء', 'لباس'],
            'أحذية' => ['حذاء', 'نعال', 'صندل'],
            'كتاب' => ['مؤلف', 'مطبوعة', 'مجلد']
        ];
        
        $term_lower = strtolower($term);
        
        foreach ($synonyms_map as $key => $values) {
            if ($key === $term_lower || in_array($term_lower, $values)) {
                return array_merge([$key], $values);
            }
        }
        
        return [];
    }

    /**
     * تحليل نتائج البحث
     * Analyze search results
     */
    public function analyzeSearchResults($query, $results) {
        $analysis = [
            'total_results' => count($results),
            'categories' => [],
            'price_range' => ['min' => null, 'max' => null],
            'avg_rating' => 0,
            'in_stock_count' => 0
        ];
        
        if (empty($results)) {
            return $analysis;
        }
        
        $prices = [];
        $ratings = [];
        
        foreach ($results as $product) {
            // تحليل الفئات
            $category = $product['category_name'];
            $analysis['categories'][$category] = ($analysis['categories'][$category] ?? 0) + 1;
            
            // تحليل الأسعار
            $price = $product['discount_price'] ?: $product['price'];
            $prices[] = $price;
            
            // عدد المنتجات المتوفرة
            if ($product['stock_quantity'] > 0) {
                $analysis['in_stock_count']++;
            }
        }
        
        if (!empty($prices)) {
            $analysis['price_range']['min'] = min($prices);
            $analysis['price_range']['max'] = max($prices);
        }
        
        return $analysis;
    }
}
?>
