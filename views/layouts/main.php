<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($data['title']) ? $data['title'] . ' - ' : '' ?>Автоматизація забезпечення виробництва ковбасної продукції</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Верхня навігаційна панель -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= BASE_URL ?>/home">
                <i class="fas fa-utensils me-2"></i>Ковбасна продукція
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (Auth::isLoggedIn()): ?>
                        <!-- Повідомлення -->
                        <?php 
                            $messageModel = new Message();
                            $unreadCount = $messageModel->countUnread(Auth::getCurrentUserId());
                        ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/home/messages">
                                <i class="fas fa-envelope me-1"></i>Повідомлення
                                <?php if ($unreadCount > 0): ?>
                                    <span class="badge badge-pill bg-danger"><?= $unreadCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        
                        <!-- Профіль -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i><?= Auth::getCurrentUserName() ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/home/profile">
                                    <i class="fas fa-id-card me-2"></i>Мій профіль
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Вихід
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/auth/login">
                                <i class="fas fa-sign-in-alt me-1"></i>Вхід
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL ?>/auth/register">
                                <i class="fas fa-user-plus me-1"></i>Реєстрація
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <?php if (Auth::isLoggedIn()): ?>
        <!-- Бокова панель -->
        <div class="sidebar">
            <div class="p-3">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-user-circle me-2" style="font-size: 2rem;"></i>
                    <div>
                        <div class="fw-bold"><?= Auth::getCurrentUserName() ?></div>
                        <small><?= Util::getUserRoleName(Auth::getCurrentUserRole()) ?></small>
                    </div>
                </div>
            </div>
            
            <hr class="bg-light">
            
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $controller === 'HomeController' && $method === 'index' ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>/home">
                        <i class="fas fa-tachometer-alt"></i> Головна панель
                    </a>
                </li>
                
                <!-- Меню для адміністратора -->
                <?php if (Auth::hasRole('admin')): ?>
                    <div class="sidebar-heading">Адміністрування</div>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'AdminController' && $method === 'users' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/admin/users">
                            <i class="fas fa-users"></i> Користувачі
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'AdminController' && $method === 'recipes' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/admin/recipes">
                            <i class="fas fa-book"></i> Рецепти
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'AdminController' && $method === 'products' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/admin/products">
                            <i class="fas fa-drumstick-bite"></i> Продукція
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'AdminController' && $method === 'orders' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/admin/orders">
                            <i class="fas fa-shopping-cart"></i> Замовлення сировини
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'AdminController' && ($method === 'videoSurveillance' || $method === 'cameras') ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/admin/videoSurveillance">
                            <i class="fas fa-video"></i> Відеоспостереження
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'AdminController' && $method === 'reports' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/admin/reports">
                            <i class="fas fa-chart-bar"></i> Звіти
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Меню для начальника складу -->
                <?php if (Auth::hasRole('warehouse_manager')): ?>
                    <div class="sidebar-heading">Управління складом</div>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'WarehouseController' && $method === 'inventory' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/warehouse/inventory">
                            <i class="fas fa-boxes"></i> Інвентаризація
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'WarehouseController' && $method === 'production' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/warehouse/production">
                            <i class="fas fa-industry"></i> Виробництво
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'WarehouseController' && $method === 'reports' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/warehouse/reports">
                            <i class="fas fa-chart-bar"></i> Звіти
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Меню для постачальника -->
                <?php if (Auth::hasRole('supplier')): ?>
                    <div class="sidebar-heading">Постачальник</div>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'SupplierController' && $method === 'orders' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/supplier/orders">
                            <i class="fas fa-shopping-cart"></i> Замовлення
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'SupplierController' && $method === 'materials' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/supplier/materials">
                            <i class="fas fa-cubes"></i> Сировина
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $controller === 'SupplierController' && $method === 'reports' ? 'active' : '' ?>" 
                           href="<?= BASE_URL ?>/supplier/reports">
                            <i class="fas fa-chart-bar"></i> Звіти
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Спільні елементи меню -->
                <div class="sidebar-heading">Спільне</div>
                
                <li class="nav-item">
                    <a class="nav-link <?= $controller === 'HomeController' && $method === 'messages' ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>/home/messages">
                        <i class="fas fa-envelope"></i> Повідомлення
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge badge-pill bg-danger"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= $controller === 'HomeController' && $method === 'profile' ? 'active' : '' ?>" 
                       href="<?= BASE_URL ?>/home/profile">
                        <i class="fas fa-id-card"></i> Мій профіль
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/auth/logout">
                        <i class="fas fa-sign-out-alt"></i> Вихід
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Основний вміст сторінки -->
        <div class="content-wrapper">
            <!-- Відображення повідомлень про помилки/успіх -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <!-- Вміст сторінки -->
            <?= $content ?>
        </div>
    <?php else: ?>
        <!-- Вміст для неавторизованих користувачів -->
        <div class="container mt-5">
            <!-- Відображення повідомлень про помилки/успіх -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <!-- Вміст сторінки -->
            <?= $content ?>
        </div>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Отримуємо поточний контролер і метод для активації відповідних пунктів меню
        var controller = '<?= $controller ?>';
        var method = '<?= $method ?>';
        
        $(document).ready(function() {
            // Автоматичне закриття повідомлень через 5 секунд
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
            
            // Мобільне меню - закриття при кліку на пункт меню
            $('.sidebar .nav-link').on('click', function() {
                if ($(window).width() < 768) {
                    $('.sidebar').toggleClass('d-none');
                }
            });
            
            // Кнопка для показу/приховування бокової панелі на мобільних пристроях
            $('.navbar-toggler').on('click', function() {
                $('.sidebar').toggleClass('d-none d-lg-block');
            });
            
            // Ініціалізація підказок
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>