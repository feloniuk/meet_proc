<?php
// Обновленная навигация для layouts/main.php
$user_role = Auth::getCurrentUserRole();
$current_user_name = Auth::getCurrentUserName();

// Определяем активную страницу
$current_page = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Система управління виробництвом' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #2c3e50;
        }
        
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: #34495e;
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #3498db;
        }
        
        .navbar-brand {
            font-weight: bold;
        }
        
        .card-icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }
        
        .status-pending { background-color: #f39c12; }
        .status-accepted { background-color: #3498db; }
        .status-shipped { background-color: #9b59b6; }
        .status-delivered { background-color: #27ae60; }
        .status-canceled { background-color: #e74c3c; }
        
        .status-planned { background-color: #f39c12; }
        .status-in_progress { background-color: #3498db; }
        .status-completed { background-color: #27ae60; }
        
        .message-unread {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .stock-low { color: #e74c3c; font-weight: bold; }
        .stock-medium { color: #f39c12; }
        .stock-good { color: #27ae60; }
        
        .dashboard-stats .card {
            transition: transform 0.2s;
        }
        
        .dashboard-stats .card:hover {
            transform: translateY(-2px);
        }
        
        .quality-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        
        .quality-approved { background-color: #27ae60; }
        .quality-rejected { background-color: #e74c3c; }
        .quality-pending { background-color: #f39c12; }
        .quality-not-checked { background-color: #95a5a6; }
    </style>
</head>
<body>
    <!-- Верхня навігація -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= BASE_URL ?>/home">
                <i class="fas fa-industry me-2"></i>
                <?= BASE_NAME ?>
            </a>
            
            <div class="navbar-nav ms-auto">
                <!-- Повідомлення -->
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-envelope"></i>
                        <?php 
                        $messageModel = new Message();
                        $unread_count = $messageModel->countUnread(Auth::getCurrentUserId());
                        if ($unread_count > 0): 
                        ?>
                            <span class="badge bg-danger"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Повідомлення</h6></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/home/messages">
                            <i class="fas fa-inbox me-2"></i>Всі повідомлення
                        </a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/home/newMessage">
                            <i class="fas fa-plus me-2"></i>Нове повідомлення
                        </a></li>
                    </ul>
                </div>
                
                <!-- Профіль користувача -->
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($current_user_name) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?= Util::getUserRoleName($user_role) ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/home/profile">
                            <i class="fas fa-user-edit me-2"></i>Мій профіль
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout">
                            <i class="fas fa-sign-out-alt me-2"></i>Вихід
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Бічна навігація -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <!-- Загальні пункти для всіх ролей -->
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, '/home') !== false ? 'active' : '' ?>" 
                               href="<?= BASE_URL ?>/home">
                                <i class="fas fa-home me-2"></i>Головна
                            </a>
                        </li>
                        
                        <!-- Для адміністратора -->
                        <?php if ($user_role === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/admin/users') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/admin/users">
                                    <i class="fas fa-users me-2"></i>Користувачі
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/admin/recipes') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/admin/recipes">
                                    <i class="fas fa-book me-2"></i>Рецепти
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/admin/products') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/admin/products">
                                    <i class="fas fa-box me-2"></i>Продукція
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/admin/orders') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/admin/orders">
                                    <i class="fas fa-shopping-cart me-2"></i>Замовлення
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/admin/videoSurveillance') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/admin/videoSurveillance">
                                    <i class="fas fa-video me-2"></i>Відеоспостереження
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/admin/reports') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/admin/reports">
                                    <i class="fas fa-chart-bar me-2"></i>Звіти
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Для начальника складу -->
                        <?php if ($user_role === 'warehouse_manager'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/warehouse/inventory') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/warehouse/inventory">
                                    <i class="fas fa-boxes me-2"></i>Інвентаризація
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/warehouse/production') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/warehouse/production">
                                    <i class="fas fa-industry me-2"></i>Виробництво
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/warehouse/reports') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/warehouse/reports">
                                    <i class="fas fa-chart-bar me-2"></i>Звіти
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Для технолога -->
                        <?php if ($user_role === 'technologist'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/technologist/qualityChecks') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/technologist/qualityChecks">
                                    <i class="fas fa-clipboard-check me-2"></i>Перевірки якості
                                    <?php 
                                    // Показываем количество ожидающих проверок
                                    $qualityCheckModel = new QualityCheck();
                                    $pending_checks = $qualityCheckModel->getPendingChecks();
                                    if (count($pending_checks) > 0): 
                                    ?>
                                        <span class="badge bg-warning ms-2"><?= count($pending_checks) ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/technologist/qualityStandards') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/technologist/qualityStandards">
                                    <i class="fas fa-clipboard-list me-2"></i>Стандарти якості
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/technologist/reports') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/technologist/reports">
                                    <i class="fas fa-chart-bar me-2"></i>Звіти
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Для постачальника -->
                        <?php if ($user_role === 'supplier'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/supplier/orders') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/supplier/orders">
                                    <i class="fas fa-shopping-cart me-2"></i>Мої замовлення
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/supplier/materials') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/supplier/materials">
                                    <i class="fas fa-cubes me-2"></i>Моя сировина
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= strpos($current_page, '/supplier/reports') !== false ? 'active' : '' ?>" 
                                   href="<?= BASE_URL ?>/supplier/reports">
                                    <i class="fas fa-chart-bar me-2"></i>Звіти
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Загальні пункти -->
                        <li class="nav-item mt-3">
                            <hr class="text-secondary">
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, '/home/messages') !== false ? 'active' : '' ?>" 
                               href="<?= BASE_URL ?>/home/messages">
                                <i class="fas fa-envelope me-2"></i>Повідомлення
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge bg-danger ms-2"><?= $unread_count ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($current_page, '/home/profile') !== false ? 'active' : '' ?>" 
                               href="<?= BASE_URL ?>/home/profile">
                                <i class="fas fa-user me-2"></i>Профіль
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Основний контент -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="py-4">
                    <!-- Відображення повідомлень -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Контент страницы -->
                    <?= $content ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Дополнительные скрипты -->
    <script>
        // Автоматическое скрытие уведомлений через 5 секунд
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Подтверждение для опасных действий
        document.querySelectorAll('a[onclick*="confirm"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                if (!confirm('Ви впевнені?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>