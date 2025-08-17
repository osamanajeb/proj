-- إنشاء قاعدة البيانات للمتجر الإلكتروني العربي
-- Arabic E-commerce Store Database

CREATE DATABASE IF NOT EXISTS arabic_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE arabic_store;

-- جدول الفئات (Categories)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المنتجات (Products)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    stock_quantity INT DEFAULT 0,
    category_id INT,
    main_image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    featured BOOLEAN DEFAULT FALSE,
    sales_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول صور المنتجات (Product Images)
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_main BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المستخدمين (Users)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الطلبات (Orders)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    notes TEXT,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول تفاصيل الطلبات (Order Items)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سلة المشتريات (Shopping Cart)
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(255),
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول التقييمات (Reviews)
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج بيانات تجريبية
-- Sample Data Insertion

-- إدراج الفئات
INSERT INTO categories (name, description, image) VALUES
('الإلكترونيات', 'أجهزة إلكترونية متنوعة', 'electronics.jpg'),
('الملابس', 'ملابس رجالية ونسائية', 'clothing.jpg'),
('المنزل والحديقة', 'أدوات منزلية ومستلزمات الحديقة', 'home.jpg'),
('الكتب', 'كتب متنوعة في جميع المجالات', 'books.jpg'),
('الرياضة', 'معدات رياضية ولياقة بدنية', 'sports.jpg');

-- إدراج مستخدم مشرف
INSERT INTO users (first_name, last_name, email, password, role) VALUES
('المشرف', 'العام', 'admin@store.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- إدراج منتجات تجريبية
INSERT INTO products (name, description, price, discount_price, stock_quantity, category_id, main_image, featured) VALUES
('هاتف ذكي سامسونج', 'هاتف ذكي بمواصفات عالية وكاميرا متطورة', 2500.00, 2200.00, 50, 1, 'samsung_phone.jpg', TRUE),
('لابتوب ديل', 'لابتوب عالي الأداء للعمل والألعاب', 4500.00, NULL, 25, 1, 'dell_laptop.jpg', TRUE),
('قميص قطني رجالي', 'قميص قطني عالي الجودة ومريح', 150.00, 120.00, 100, 2, 'mens_shirt.jpg', FALSE),
('فستان نسائي أنيق', 'فستان نسائي أنيق ومناسب للمناسبات', 300.00, NULL, 75, 2, 'womens_dress.jpg', TRUE),
('طقم أواني طبخ', 'طقم أواني طبخ من الستانلس ستيل', 800.00, 650.00, 30, 3, 'cookware_set.jpg', FALSE),
('كتاب البرمجة', 'كتاب تعليم البرمجة للمبتدئين', 80.00, NULL, 200, 4, 'programming_book.jpg', FALSE),
('دراجة هوائية', 'دراجة هوائية للرياضة والتنقل', 1200.00, 1000.00, 15, 5, 'bicycle.jpg', TRUE);

-- إدراج صور إضافية للمنتجات
INSERT INTO product_images (product_id, image_path, is_main) VALUES
(1, 'samsung_phone_1.jpg', FALSE),
(1, 'samsung_phone_2.jpg', FALSE),
(2, 'dell_laptop_1.jpg', FALSE),
(2, 'dell_laptop_2.jpg', FALSE),
(3, 'mens_shirt_1.jpg', FALSE),
(4, 'womens_dress_1.jpg', FALSE),
(5, 'cookware_set_1.jpg', FALSE),
(7, 'bicycle_1.jpg', FALSE);
