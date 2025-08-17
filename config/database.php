<?php
/**
 * إعدادات قاعدة البيانات للمتجر الإلكتروني
 * Database configuration for the e-commerce store
 */

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'arabic_store');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    public $conn;

    /**
     * الاتصال بقاعدة البيانات
     * Connect to database
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // تعيين الترميز للنصوص العربية
            $this->conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            
        } catch(PDOException $exception) {
            echo "خطأ في الاتصال بقاعدة البيانات: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

/**
 * دالة مساعدة للحصول على اتصال قاعدة البيانات
 * Helper function to get database connection
 */
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}
?>
