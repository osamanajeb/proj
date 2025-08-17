<?php
/**
 * فئة إدارة الإشعارات
 * Notification management class
 */

class Notification {
    private $conn;
    private $table_name = "notifications";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * إنشاء إشعار جديد
     * Create new notification
     */
    public function createNotification($user_id, $title, $message, $type = 'info', $data = null) {
        $query = "INSERT INTO " . $this->table_name . " (user_id, title, message, type, data) 
                  VALUES (:user_id, :title, :message, :type, :data)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':user_id' => $user_id,
            ':title' => $title,
            ':message' => $message,
            ':type' => $type,
            ':data' => $data ? json_encode($data) : null
        ]);
    }

    /**
     * إنشاء إشعار لجميع المستخدمين
     * Create notification for all users
     */
    public function createBroadcastNotification($title, $message, $type = 'info', $data = null) {
        $query = "INSERT INTO " . $this->table_name . " (user_id, title, message, type, data, is_broadcast) 
                  VALUES (NULL, :title, :message, :type, :data, 1)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':title' => $title,
            ':message' => $message,
            ':type' => $type,
            ':data' => $data ? json_encode($data) : null
        ]);
    }

    /**
     * الحصول على إشعارات المستخدم
     * Get user notifications
     */
    public function getUserNotifications($user_id, $limit = 20, $unread_only = false) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE (user_id = :user_id OR is_broadcast = 1)";
        
        if ($unread_only) {
            $query .= " AND is_read = 0";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * عدد الإشعارات غير المقروءة
     * Count unread notifications
     */
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE (user_id = :user_id OR is_broadcast = 1) AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * تحديد الإشعار كمقروء
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id = null) {
        if ($user_id) {
            // للإشعارات الشخصية
            $query = "UPDATE " . $this->table_name . " SET is_read = 1 
                      WHERE id = :id AND user_id = :user_id";
            $params = [':id' => $notification_id, ':user_id' => $user_id];
        } else {
            // للإشعارات العامة - إنشاء سجل قراءة
            $query = "INSERT IGNORE INTO notification_reads (notification_id, user_id) 
                      VALUES (:notification_id, :user_id)";
            $params = [':notification_id' => $notification_id, ':user_id' => $user_id];
        }
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * تحديد جميع الإشعارات كمقروءة
     * Mark all notifications as read
     */
    public function markAllAsRead($user_id) {
        // تحديث الإشعارات الشخصية
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':user_id' => $user_id]);
        
        // إضافة سجلات قراءة للإشعارات العامة
        $query = "INSERT IGNORE INTO notification_reads (notification_id, user_id) 
                  SELECT id, :user_id FROM " . $this->table_name . " WHERE is_broadcast = 1";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':user_id' => $user_id]);
    }

    /**
     * حذف إشعار
     * Delete notification
     */
    public function deleteNotification($notification_id, $user_id = null) {
        if ($user_id) {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
            $params = [':id' => $notification_id, ':user_id' => $user_id];
        } else {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $params = [':id' => $notification_id];
        }
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * إشعارات الطلبات
     * Order notifications
     */
    public function createOrderNotification($user_id, $order_id, $status) {
        $messages = [
            'confirmed' => 'تم تأكيد طلبك وسيتم معالجته قريباً',
            'shipped' => 'تم شحن طلبك وهو في الطريق إليك',
            'delivered' => 'تم تسليم طلبك بنجاح',
            'cancelled' => 'تم إلغاء طلبك'
        ];
        
        $title = "تحديث حالة الطلب #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
        $message = $messages[$status] ?? 'تم تحديث حالة طلبك';
        
        return $this->createNotification(
            $user_id, 
            $title, 
            $message, 
            'order', 
            ['order_id' => $order_id, 'status' => $status]
        );
    }

    /**
     * إشعارات المنتجات
     * Product notifications
     */
    public function createProductNotification($user_id, $product_id, $type, $message) {
        $titles = [
            'stock_available' => 'المنتج متوفر الآن',
            'price_drop' => 'انخفاض في السعر',
            'new_review' => 'تقييم جديد'
        ];
        
        $title = $titles[$type] ?? 'تحديث المنتج';
        
        return $this->createNotification(
            $user_id, 
            $title, 
            $message, 
            'product', 
            ['product_id' => $product_id, 'type' => $type]
        );
    }

    /**
     * إشعارات النظام
     * System notifications
     */
    public function createSystemNotification($title, $message, $type = 'system') {
        return $this->createBroadcastNotification($title, $message, $type);
    }

    /**
     * تنظيف الإشعارات القديمة
     * Clean old notifications
     */
    public function cleanOldNotifications($days = 30) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':days' => $days]);
    }

    /**
     * الحصول على إحصائيات الإشعارات
     * Get notification statistics
     */
    public function getNotificationStats() {
        $stats = [];
        
        // إجمالي الإشعارات
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // الإشعارات غير المقروءة
        $query = "SELECT COUNT(*) as unread FROM " . $this->table_name . " WHERE is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['unread'] = $stmt->fetch(PDO::FETCH_ASSOC)['unread'];
        
        // الإشعارات حسب النوع
        $query = "SELECT type, COUNT(*) as count FROM " . $this->table_name . " GROUP BY type";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['by_type'] = [];
        foreach ($types as $type) {
            $stats['by_type'][$type['type']] = $type['count'];
        }
        
        return $stats;
    }

    /**
     * إرسال إشعار فوري (WebSocket/Push)
     * Send real-time notification
     */
    public function sendRealTimeNotification($user_id, $notification_data) {
        // هنا يمكن إضافة كود إرسال الإشعارات الفورية
        // عبر WebSocket أو Push Notifications
        
        // مثال: حفظ في ملف مؤقت للمعالجة
        $temp_file = sys_get_temp_dir() . '/notifications_' . $user_id . '.json';
        $notifications = [];
        
        if (file_exists($temp_file)) {
            $notifications = json_decode(file_get_contents($temp_file), true) ?: [];
        }
        
        $notifications[] = array_merge($notification_data, ['timestamp' => time()]);
        file_put_contents($temp_file, json_encode($notifications));
        
        return true;
    }

    /**
     * الحصول على الإشعارات الفورية
     * Get real-time notifications
     */
    public function getRealTimeNotifications($user_id) {
        $temp_file = sys_get_temp_dir() . '/notifications_' . $user_id . '.json';
        
        if (file_exists($temp_file)) {
            $notifications = json_decode(file_get_contents($temp_file), true) ?: [];
            // حذف الملف بعد القراءة
            unlink($temp_file);
            return $notifications;
        }
        
        return [];
    }
}
?>
