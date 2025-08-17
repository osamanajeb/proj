<?php
/**
 * إدارة المستخدمين - لوحة التحكم
 * Users management - Admin panel
 */

$page_title = 'إدارة المستخدمين';
$page_description = 'إدارة حسابات المستخدمين';

require_once '../config/config.php';
require_once '../classes/User.php';

// التحقق من صلاحيات المشرف
if (!is_logged_in() || !is_admin()) {
    redirect(SITE_URL . '/login.php');
}

// الحصول على اتصال قاعدة البيانات
$db = getDBConnection();

// إنشاء كائن المستخدمين
$userObj = new User($db);

// معالجة تحديث حالة المستخدم
if (isset($_POST['update_status']) && isset($_POST['user_id']) && isset($_POST['status'])) {
    $user_id = (int)$_POST['user_id'];
    $status = sanitize_input($_POST['status']);
    
    $result = $userObj->updateUserStatus($user_id, $status);
    
    if ($result) {
        $_SESSION['message'] = ['text' => 'تم تحديث حالة المستخدم بنجاح', 'type' => 'success'];
    } else {
        $_SESSION['message'] = ['text' => 'حدث خطأ أثناء تحديث حالة المستخدم', 'type' => 'error'];
    }
    
    redirect(SITE_URL . '/admin/users.php');
}

// معالجة تحديث دور المستخدم
if (isset($_POST['update_role']) && isset($_POST['user_id']) && isset($_POST['role'])) {
    $user_id = (int)$_POST['user_id'];
    $role = sanitize_input($_POST['role']);
    
    $result = $userObj->updateUserRole($user_id, $role);
    
    if ($result) {
        $_SESSION['message'] = ['text' => 'تم تحديث دور المستخدم بنجاح', 'type' => 'success'];
    } else {
        $_SESSION['message'] = ['text' => 'حدث خطأ أثناء تحديث دور المستخدم', 'type' => 'error'];
    }
    
    redirect(SITE_URL . '/admin/users.php');
}

// الحصول على المستخدمين
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

$users = $userObj->getAllUsers();

// تصفية المستخدمين
if ($search) {
    $users = array_filter($users, function($user) use ($search) {
        return stripos($user['first_name'] . ' ' . $user['last_name'], $search) !== false ||
               stripos($user['email'], $search) !== false;
    });
}

if ($role_filter) {
    $users = array_filter($users, function($user) use ($role_filter) {
        return $user['role'] === $role_filter;
    });
}

if ($status_filter) {
    $users = array_filter($users, function($user) use ($status_filter) {
        return $user['status'] === $status_filter;
    });
}

// تضمين رأس المشرف
include 'includes/admin_header.php';
?>

<div class="users-management">
    <div class="page-header">
        <h1><i class="fas fa-users"></i> إدارة المستخدمين</h1>
    </div>
    
    <!-- أدوات التصفية والبحث -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">البحث</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="ابحث بالاسم أو البريد الإلكتروني...">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">الدور</label>
                    <select name="role" class="form-select">
                        <option value="">جميع الأدوار</option>
                        <option value="customer" <?php echo $role_filter == 'customer' ? 'selected' : ''; ?>>عميل</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>مشرف</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>نشط</option>
                        <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> بحث
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <?php
        $all_users = $userObj->getAllUsers();
        $stats = [
            'total' => count($all_users),
            'customers' => count(array_filter($all_users, fn($u) => $u['role'] == 'customer')),
            'admins' => count(array_filter($all_users, fn($u) => $u['role'] == 'admin')),
            'active' => count(array_filter($all_users, fn($u) => $u['status'] == 'active'))
        ];
        ?>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>إجمالي المستخدمين</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-info">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $stats['customers']; ?></h3>
                    <p>العملاء</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-warning">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $stats['admins']; ?></h3>
                    <p>المشرفين</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon bg-success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $stats['active']; ?></h3>
                    <p>النشطين</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- جدول المستخدمين -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> قائمة المستخدمين (<?php echo count($users); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h5>لا توجد مستخدمين</h5>
                    <p>لم يتم العثور على مستخدمين يطابقون معايير البحث</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الهاتف</th>
                                <th>المدينة</th>
                                <th>الدور</th>
                                <th>الحالة</th>
                                <th>تاريخ التسجيل</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr data-id="<?php echo $user['id']; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?: 'غير محدد'); ?></td>
                                    <td><?php echo htmlspecialchars($user['city'] ?: 'غير محددة'); ?></td>
                                    <td>
                                        <?php if ($user['role'] == 'admin'): ?>
                                            <span class="badge bg-warning">مشرف</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">عميل</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['status'] == 'active'): ?>
                                            <span class="badge bg-success">نشط</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">غير نشط</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="updateUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')" 
                                                        title="تغيير الحالة">
                                                    <i class="fas fa-toggle-on"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        onclick="updateUserRole(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')" 
                                                        title="تغيير الدور">
                                                    <i class="fas fa-user-cog"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="badge bg-primary">أنت</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- نموذج تحديث حالة المستخدم -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تحديث حالة المستخدم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="statusUserId">
                    <div class="form-group">
                        <label for="status" class="form-label">الحالة الجديدة</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="update_status" class="btn btn-primary">تحديث</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نموذج تحديث دور المستخدم -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تحديث دور المستخدم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="roleUserId">
                    <div class="form-group">
                        <label for="role" class="form-label">الدور الجديد</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="customer">عميل</option>
                            <option value="admin">مشرف</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        تحذير: تغيير دور المستخدم إلى مشرف سيمنحه صلاحيات كاملة في النظام.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="update_role" class="btn btn-warning">تحديث</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateUserStatus(userId, currentStatus) {
    document.getElementById('statusUserId').value = userId;
    document.getElementById('status').value = currentStatus;
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

function updateUserRole(userId, currentRole) {
    document.getElementById('roleUserId').value = userId;
    document.getElementById('role').value = currentRole;
    const modal = new bootstrap.Modal(document.getElementById('roleModal'));
    modal.show();
}

// البحث المباشر
document.querySelector('input[name="search"]').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?php
// تضمين تذييل المشرف
include 'includes/admin_footer.php';
?>
