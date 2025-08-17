<?php
/**
 * فئة إدارة التقييمات
 * Review management class
 */

class Review {
    private $conn;
    private $table_name = "reviews";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * إضافة تقييم جديد
     * Add new review
     */
    public function addReview($product_id, $user_id, $rating, $comment) {
        // التحقق من وجود تقييم سابق
        if ($this->hasUserReviewed($product_id, $user_id)) {
            return ['success' => false, 'message' => 'لقد قمت بتقييم هذا المنتج من قبل'];
        }

        $query = "INSERT INTO " . $this->table_name . " (product_id, user_id, rating, comment) 
                  VALUES (:product_id, :user_id, :rating, :comment)";
        
        $stmt = $this->conn->prepare($query);
        $result = $stmt->execute([
            ':product_id' => $product_id,
            ':user_id' => $user_id,
            ':rating' => $rating,
            ':comment' => $comment
        ]);

        if ($result) {
            return ['success' => true, 'message' => 'تم إضافة التقييم بنجاح'];
        } else {
            return ['success' => false, 'message' => 'حدث خطأ أثناء إضافة التقييم'];
        }
    }

    /**
     * التحقق من تقييم المستخدم للمنتج
     * Check if user has reviewed product
     */
    public function hasUserReviewed($product_id, $user_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE product_id = :product_id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':product_id' => $product_id,
            ':user_id' => $user_id
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * الحصول على تقييمات المنتج
     * Get product reviews
     */
    public function getProductReviews($product_id, $status = 'approved') {
        $query = "SELECT r.*, u.first_name, u.last_name 
                  FROM " . $this->table_name . " r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.product_id = :product_id AND r.status = :status 
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':product_id' => $product_id,
            ':status' => $status
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على متوسط التقييم للمنتج
     * Get product average rating
     */
    public function getProductRating($product_id) {
        $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                  FROM " . $this->table_name . " 
                  WHERE product_id = :product_id AND status = 'approved'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':product_id' => $product_id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'average' => $result['avg_rating'] ? round($result['avg_rating'], 1) : 0,
            'total' => $result['total_reviews']
        ];
    }

    /**
     * الحصول على جميع التقييمات (للمشرف)
     * Get all reviews (for admin)
     */
    public function getAllReviews($status = null) {
        $query = "SELECT r.*, u.first_name, u.last_name, p.name as product_name 
                  FROM " . $this->table_name . " r 
                  JOIN users u ON r.user_id = u.id 
                  JOIN products p ON r.product_id = p.id";
        
        if ($status) {
            $query .= " WHERE r.status = :status";
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($status) {
            $stmt->execute([':status' => $status]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * تحديث حالة التقييم
     * Update review status
     */
    public function updateReviewStatus($review_id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $review_id,
            ':status' => $status
        ]);
    }

    /**
     * حذف تقييم
     * Delete review
     */
    public function deleteReview($review_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $review_id]);
    }

    /**
     * الحصول على تقييمات المستخدم
     * Get user reviews
     */
    public function getUserReviews($user_id) {
        $query = "SELECT r.*, p.name as product_name, p.main_image 
                  FROM " . $this->table_name . " r 
                  JOIN products p ON r.product_id = p.id 
                  WHERE r.user_id = :user_id 
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * الحصول على إحصائيات التقييمات
     * Get reviews statistics
     */
    public function getReviewsStats() {
        $stats = [];
        
        // إجمالي التقييمات
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // التقييمات المعلقة
        $query = "SELECT COUNT(*) as pending FROM " . $this->table_name . " WHERE status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];
        
        // متوسط التقييم العام
        $query = "SELECT AVG(rating) as avg_rating FROM " . $this->table_name . " WHERE status = 'approved'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['average_rating'] = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'] ?: 0, 1);
        
        return $stats;
    }

    /**
     * الحصول على توزيع التقييمات
     * Get rating distribution
     */
    public function getRatingDistribution($product_id = null) {
        $query = "SELECT rating, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE status = 'approved'";
        
        $params = [];
        if ($product_id) {
            $query .= " AND product_id = :product_id";
            $params[':product_id'] = $product_id;
        }
        
        $query .= " GROUP BY rating ORDER BY rating DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = 0;
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $result) {
            $distribution[$result['rating']] = $result['count'];
        }
        
        return $distribution;
    }
}
?>
