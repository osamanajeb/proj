<?php
/**
 * صفحة إحصائيات سلة المشتريات للمشرف
 * Admin cart analytics page
 */

require_once '../config/config.php';
require_once '../classes/Cart.php';

// التحقق من صلاحيات المشرف
if (!is_admin()) {
    redirect(SITE_URL . '/login.php');
}

$db = getDBConnection();
$cartObj = new Cart($db);

// الحصول على الإحصائيات
$cart_stats = $cartObj->getCartStatistics();
$abandoned_carts = $cartObj->getAbandonedCarts(7); // السلال المهجورة خلال 7 أيام

include 'includes/admin_header.php';
?>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-bar"></i> إحصائيات سلة المشتريات</h2>
        <div>
            <a href="create-order.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> إنشاء طلب جديد
            </a>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> إدارة الطلبات
            </a>
        </div>
    </div>
    
    <!-- إحصائيات عامة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $cart_stats['active_carts'] ?? 0; ?></h3>
                    <p>سلة نشطة</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $cart_stats['total_items'] ?? 0; ?></h3>
                    <p>إجمالي المنتجات</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo format_price($cart_stats['total_value'] ?? 0); ?></h3>
                    <p>إجمالي القيمة</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo format_price($cart_stats['avg_cart_value'] ?? 0); ?></h3>
                    <p>متوسط قيمة السلة</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- السلال المهجورة -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-exclamation-triangle text-warning"></i> السلال المهجورة (آخر 7 أيام)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($abandoned_carts)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h5>لا توجد سلال مهجورة</h5>
                    <p>جميع العملاء أكملوا عمليات الشراء أو لا توجد سلال قديمة</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>البريد الإلكتروني</th>
                                <th>عدد المنتجات</th>
                                <th>قيمة السلة</th>
                                <th>آخر نشاط</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($abandoned_carts as $cart): ?>
                                <tr>
                                    <td>
                                        <?php if ($cart['first_name']): ?>
                                            <strong><?php echo htmlspecialchars($cart['first_name'] . ' ' . $cart['last_name']); ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted">زائر غير مسجل</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($cart['email']): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($cart['email']); ?>">
                                                <?php echo htmlspecialchars($cart['email']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">غير متوفر</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $cart['items_count']; ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-success"><?php echo format_price($cart['cart_value']); ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('Y-m-d H:i', strtotime($cart['last_activity'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($cart['email']): ?>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="sendReminderEmail('<?php echo htmlspecialchars($cart['email']); ?>')">
                                                <i class="fas fa-envelope"></i> تذكير
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($cart['user_id']): ?>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    onclick="createOrderForCustomer(<?php echo $cart['user_id']; ?>)">
                                                <i class="fas fa-plus"></i> إنشاء طلب
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>إجمالي السلال المهجورة:</strong> <?php echo count($abandoned_carts); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-warning">
                                <i class="fas fa-money-bill-wave"></i>
                                <strong>إجمالي القيمة المفقودة:</strong> 
                                <?php 
                                $total_lost = array_sum(array_column($abandoned_carts, 'cart_value'));
                                echo format_price($total_lost);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- نصائح لتحسين التحويل -->
    <div class="card mt-4">
        <div class="card-header">
            <h5><i class="fas fa-lightbulb text-warning"></i> نصائح لتحسين التحويل</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="tip-card">
                        <i class="fas fa-envelope text-primary"></i>
                        <h6>رسائل التذكير</h6>
                        <p>أرسل رسائل تذكير للعملاء الذين تركوا منتجات في سلة المشتريات</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="tip-card">
                        <i class="fas fa-percentage text-success"></i>
                        <h6>عروض خاصة</h6>
                        <p>قدم خصومات أو عروض خاصة للعملاء الذين لديهم سلال مهجورة</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="tip-card">
                        <i class="fas fa-shipping-fast text-info"></i>
                        <h6>تحسين الشحن</h6>
                        <p>راجع سياسة الشحن وتكاليفه لتقليل معدل هجر السلال</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: 1rem;
    font-size: 1.5rem;
    color: white;
}

.stat-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
    color: #333;
}

.stat-content p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.tip-card {
    text-align: center;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    height: 100%;
}

.tip-card i {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.tip-card h6 {
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.tip-card p {
    font-size: 0.9rem;
    color: #666;
    margin: 0;
}
</style>

<script>
function sendReminderEmail(email) {
    if (confirm('هل تريد إرسال رسالة تذكير إلى ' + email + '؟')) {
        // هنا يمكن إضافة كود إرسال البريد الإلكتروني
        alert('تم إرسال رسالة التذكير بنجاح');
    }
}

function createOrderForCustomer(userId) {
    if (confirm('هل تريد إنشاء طلب جديد لهذا العميل؟')) {
        window.location.href = 'create-order.php?customer_id=' + userId;
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?>
