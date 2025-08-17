        </div>
    </main>
    
    <!-- التذييل -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-store"></i> <?php echo get_site_name(); ?></h3>
                    <p><?php echo get_site_name(); ?> - متجرك الإلكتروني المتكامل للتسوق الآمن والمريح. نوفر لك أفضل المنتجات بأسعار منافسة وخدمة عملاء متميزة.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>روابط سريعة</h3>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>">الرئيسية</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/products.php">المنتجات</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/about.php">من نحن</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php">اتصل بنا</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/privacy.php">سياسة الخصوصية</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>الفئات</h3>
                    <ul>
                        <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                            <li>
                                <a href="<?php echo SITE_URL; ?>/products.php?category=<?php echo $category['id']; ?>">
                                    <?php echo $category['name']; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>خدمة العملاء</h3>
                    <ul>
                        <li><i class="fas fa-phone"></i> +966 50 123 4567</li>
                        <li><i class="fas fa-envelope"></i> <?php echo get_admin_email(); ?></li>
                        <li><i class="fas fa-clock"></i> الأحد - الخميس: 9:00 - 18:00</li>
                        <li><i class="fas fa-map-marker-alt"></i> الرياض، المملكة العربية السعودية</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo get_site_name(); ?>. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
    
    <!-- Additional scripts -->
    <?php if (isset($additional_scripts)): ?>
        <?php echo $additional_scripts; ?>
    <?php endif; ?>
</body>
</html>
