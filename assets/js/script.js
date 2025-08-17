/**
 * ملف JavaScript للمتجر الإلكتروني العربي
 * JavaScript file for Arabic E-commerce Store
 */

// تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    initializeCart();
    initializeAlerts();
    initializeSearch();
    updateCartCount();
});

/**
 * تهيئة وظائف السلة
 * Initialize cart functions
 */
function initializeCart() {
    // أزرار إضافة إلى السلة
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const quantity = this.getAttribute('data-quantity') || 1;
            addToCart(productId, quantity);
        });
    });

    // أزرار تحديث الكمية في السلة
    const updateQuantityButtons = document.querySelectorAll('.update-quantity');
    updateQuantityButtons.forEach(button => {
        button.addEventListener('click', function() {
            const cartId = this.getAttribute('data-cart-id');
            const quantity = this.getAttribute('data-quantity');
            updateCartQuantity(cartId, quantity);
        });
    });

    // أزرار حذف من السلة
    const removeFromCartButtons = document.querySelectorAll('.remove-from-cart');
    removeFromCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const cartId = this.getAttribute('data-cart-id');
            removeFromCart(cartId);
        });
    });
}

/**
 * إضافة منتج إلى السلة
 * Add product to cart
 */
function addToCart(productId, quantity = 1) {
    // منع الإضافة المتكررة
    const button = document.querySelector(`[data-product-id="${productId}"]`);
    if (button && button.disabled) {
        return;
    }

    // تعطيل الزر مؤقتاً
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإضافة...';
    }

    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('تم إضافة المنتج إلى السلة بنجاح', 'success');
            updateCartCount();

            // تأثير بصري على الزر
            if (button) {
                button.style.background = '#28a745';
                button.innerHTML = '<i class="fas fa-check"></i> تم الإضافة';
                setTimeout(() => {
                    button.style.background = '';
                    button.innerHTML = '<i class="fas fa-cart-plus"></i> أضف إلى السلة';
                    button.disabled = false;
                }, 2000);
            }
        } else {
            showAlert(data.message || 'حدث خطأ أثناء إضافة المنتج', 'danger');
            if (button) {
                button.innerHTML = '<i class="fas fa-cart-plus"></i> أضف إلى السلة';
                button.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('حدث خطأ في الاتصال', 'danger');
        if (button) {
            button.innerHTML = '<i class="fas fa-cart-plus"></i> أضف إلى السلة';
            button.disabled = false;
        }
    });
}

/**
 * تحديث كمية المنتج في السلة
 * Update cart item quantity
 */
function updateCartQuantity(cartId, quantity) {
    fetch('ajax/update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `cart_id=${cartId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // إعادة تحميل الصفحة لتحديث الأسعار
        } else {
            showAlert(data.message || 'حدث خطأ أثناء تحديث السلة', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('حدث خطأ في الاتصال', 'danger');
    });
}

/**
 * حذف منتج من السلة
 * Remove product from cart
 */
function removeFromCart(cartId) {
    if (confirm('هل أنت متأكد من حذف هذا المنتج من السلة؟')) {
        fetch('ajax/remove_from_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cart_id=${cartId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showAlert(data.message || 'حدث خطأ أثناء حذف المنتج', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('حدث خطأ في الاتصال', 'danger');
        });
    }
}

/**
 * تحديث عداد السلة
 * Update cart count
 */
function updateCartCount() {
    fetch('ajax/get_cart_count.php')
    .then(response => response.json())
    .then(data => {
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = data.count || 0;
            cartCountElement.style.display = data.count > 0 ? 'flex' : 'none';
        }
    })
    .catch(error => {
        console.error('Error updating cart count:', error);
    });
}

/**
 * عرض التنبيهات - استخدام النظام الموحد من auto-dismiss.js
 * Show alerts - use unified system from auto-dismiss.js
 */
function showAlert(message, type = 'info') {
    // تحويل نوع الخطأ إلى النوع المناسب
    if (type === 'error') {
        type = 'danger';
    }

    // استخدام النظام الموحد من auto-dismiss.js
    if (typeof createAlert === 'function') {
        return createAlert(message, type);
    } else {
        // fallback في حالة عدم تحميل auto-dismiss.js
        console.warn('auto-dismiss.js not loaded, using fallback alert');
        alert(message);
    }
}

// تم نقل دالة dismissAlert إلى auto-dismiss.js لتجنب التكرار

// تم نقل معالج الإشعارات إلى auto-dismiss.js لتجنب التكرار

// تم نقل دالة createAlertContainer إلى auto-dismiss.js لتجنب التكرار

/**
 * تهيئة التنبيهات
 * Initialize alerts
 */
function initializeAlerts() {
    const closeButtons = document.querySelectorAll('.btn-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.parentElement.remove();
        });
    });
}

/**
 * تهيئة البحث
 * Initialize search
 */
function initializeSearch() {
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('.search-input');
            if (searchInput.value.trim() === '') {
                e.preventDefault();
                showAlert('يرجى إدخال كلمة البحث', 'warning');
            }
        });
    }

    // البحث المباشر
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3) {
                    performLiveSearch(this.value);
                }
            }, 500);
        });
    }
}

/**
 * البحث المباشر
 * Live search
 */
function performLiveSearch(query) {
    fetch(`ajax/search.php?q=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
        displaySearchResults(data.products);
    })
    .catch(error => {
        console.error('Search error:', error);
    });
}

/**
 * عرض نتائج البحث
 * Display search results
 */
function displaySearchResults(products) {
    // يمكن تطوير هذه الوظيفة لعرض نتائج البحث في قائمة منسدلة
    console.log('Search results:', products);
}

/**
 * تأكيد الحذف
 * Confirm delete
 */
function confirmDelete(message = 'هل أنت متأكد من الحذف؟') {
    return confirm(message);
}

/**
 * تنسيق الأرقام
 * Format numbers
 */
function formatNumber(number) {
    return new Intl.NumberFormat('ar-SA').format(number);
}

/**
 * تنسيق السعر
 * Format price
 */
function formatPrice(price) {
    return formatNumber(price) + ' ر.س';
}

/**
 * التحقق من صحة البريد الإلكتروني
 * Validate email
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * التحقق من صحة رقم الهاتف
 * Validate phone number
 */
function validatePhone(phone) {
    const re = /^[0-9+\-\s()]+$/;
    return re.test(phone) && phone.length >= 10;
}

/**
 * تحميل المزيد من المنتجات
 * Load more products
 */
function loadMoreProducts(page, category = null) {
    const loadingButton = document.querySelector('.load-more-btn');
    if (loadingButton) {
        loadingButton.textContent = 'جاري التحميل...';
        loadingButton.disabled = true;
    }

    let url = `ajax/load_products.php?page=${page}`;
    if (category) {
        url += `&category=${category}`;
    }

    fetch(url)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.products.length > 0) {
            appendProducts(data.products);
            if (loadingButton) {
                loadingButton.textContent = 'تحميل المزيد';
                loadingButton.disabled = false;
            }
        } else {
            if (loadingButton) {
                loadingButton.style.display = 'none';
            }
            showAlert('لا توجد منتجات أخرى', 'info');
        }
    })
    .catch(error => {
        console.error('Error loading products:', error);
        if (loadingButton) {
            loadingButton.textContent = 'تحميل المزيد';
            loadingButton.disabled = false;
        }
        showAlert('حدث خطأ أثناء تحميل المنتجات', 'danger');
    });
}

/**
 * إضافة المنتجات إلى الشبكة
 * Append products to grid
 */
function appendProducts(products) {
    const productsGrid = document.querySelector('.products-grid');
    if (productsGrid) {
        products.forEach(product => {
            const productCard = createProductCard(product);
            productsGrid.appendChild(productCard);
        });
        initializeCart(); // إعادة تهيئة أزرار السلة للمنتجات الجديدة
    }
}

/**
 * إنشاء بطاقة منتج
 * Create product card
 */
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    
    const discountBadge = product.discount_price ? 
        `<div class="discount-badge">خصم ${Math.round((1 - product.discount_price / product.price) * 100)}%</div>` : '';
    
    const originalPrice = product.discount_price ? 
        `<span class="original-price">${formatPrice(product.price)}</span>` : '';
    
    const currentPrice = product.discount_price || product.price;
    
    card.innerHTML = `
        ${discountBadge}
        <img src="uploads/products/${product.main_image || 'default.jpg'}" alt="${product.name}" class="product-image">
        <div class="product-info">
            <h3 class="product-title">${product.name}</h3>
            <p class="product-description">${product.description.substring(0, 100)}...</p>
            <div class="product-price">
                <span class="current-price">${formatPrice(currentPrice)}</span>
                ${originalPrice}
            </div>
            <button class="add-to-cart" data-product-id="${product.id}">أضف إلى السلة</button>
        </div>
    `;
    
    return card;
}
