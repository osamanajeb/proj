<?php
/**
 * فئة إدارة المستخدمين
 * User management class
 */

class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * تسجيل مستخدم جديد
     * Register new user
     */
    public function register($data) {
        // التحقق من وجود البريد الإلكتروني
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'البريد الإلكتروني مستخدم بالفعل'];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (first_name, last_name, email, password, phone, address, city) 
                  VALUES (:first_name, :last_name, :email, :password, :phone, :address, :city)";
        
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($data['password'], HASH_ALGO);
        
        $result = $stmt->execute([
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':password' => $hashed_password,
            ':phone' => $data['phone'] ?? null,
            ':address' => $data['address'] ?? null,
            ':city' => $data['city'] ?? null
        ]);

        if ($result) {
            return ['success' => true, 'message' => 'تم التسجيل بنجاح'];
        } else {
            return ['success' => false, 'message' => 'حدث خطأ أثناء التسجيل'];
        }
    }

    /**
     * تسجيل الدخول
     * User login
     */
    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // إنشاء جلسة المستخدم
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            return ['success' => true, 'message' => 'تم تسجيل الدخول بنجاح', 'user' => $user];
        } else {
            return ['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'];
        }
    }

    /**
     * تسجيل الخروج
     * User logout
     */
    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'تم تسجيل الخروج بنجاح'];
    }

    /**
     * التحقق من وجود البريد الإلكتروني
     * Check if email exists
     */
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * الحصول على بيانات المستخدم
     * Get user data
     */
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * تحديث بيانات المستخدم
     * Update user data
     */
    public function updateUser($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET first_name = :first_name, last_name = :last_name, 
                      phone = :phone, address = :address, city = :city 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':phone' => $data['phone'],
            ':address' => $data['address'],
            ':city' => $data['city']
        ]);
    }

    /**
     * تغيير كلمة المرور
     * Change password
     */
    public function changePassword($id, $old_password, $new_password) {
        // التحقق من كلمة المرور القديمة
        $user = $this->getUserById($id);
        if (!$user || !password_verify($old_password, $user['password'])) {
            return ['success' => false, 'message' => 'كلمة المرور القديمة غير صحيحة'];
        }

        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($new_password, HASH_ALGO);
        
        $result = $stmt->execute([
            ':id' => $id,
            ':password' => $hashed_password
        ]);

        if ($result) {
            return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح'];
        } else {
            return ['success' => false, 'message' => 'حدث خطأ أثناء تغيير كلمة المرور'];
        }
    }

    /**
     * الحصول على جميع المستخدمين (للمشرف)
     * Get all users (for admin)
     */
    public function getAllUsers() {
        $query = "SELECT id, first_name, last_name, email, phone, city, role, status, created_at 
                  FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * تحديث حالة المستخدم
     * Update user status
     */
    public function updateUserStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':status' => $status
        ]);
    }

    /**
     * تحديث دور المستخدم
     * Update user role
     */
    public function updateUserRole($id, $role) {
        $query = "UPDATE " . $this->table_name . " SET role = :role WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':id' => $id,
            ':role' => $role
        ]);
    }
}
?>
