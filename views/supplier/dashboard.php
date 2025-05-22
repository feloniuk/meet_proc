<?php
// views/supplier/dashboard.php
// Все переменные доступны напрямую благодаря BaseController::render()
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-tachometer-alt me-2"></i>Панель постачальника</h1>
        <div class="btn-group">
            <a href="<?= BASE_URL ?>/supplier/reports" class="btn btn-outline-primary">
                <i class="fas fa-chart-bar me-1"></i>Звіти
            </a>
            <a href="<?= BASE_URL ?>/supplier/orders" class="btn btn-outline-primary">
                <i class="fas fa-shopping-cart me-1"></i>Замовлення
            </a>
        </div>
    </div>
    
    <!-- Основні показники -->
    <div class="row dashboard-stats mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-0">Активні замовлення</h6>
                            <h2 class="mt-2 mb-0">
                                <?= count(array_filter($active_orders ?? [], function($order) {
                                    return isset($order['status']) && $order['status'] !== 'delivered' && $order['status'] !== 'canceled';
                                })) ?>
                            </h2>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <a href="<?= BASE_URL ?>/supplier/orders" class="text-decoration-none">
                            Деталі <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-0">Нові повідомлення</h6>
                            <h2 class="mt-2 mb-0"><?= $unread_messages ?? 0 ?></h2>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <a href="<?= BASE_URL ?>/home/messages" class="text-decoration-none">
                            Деталі <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-0">Моя сировина</h6>
                            <h2 class="mt-2 mb-0"><?= count($materials ?? []) ?></h2>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-cubes"></i>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <a href="<?= BASE_URL ?>/supplier/materials" class="text-decoration-none">
                            Деталі <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Останні замовлення -->
        <div class="col-md-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Останні замовлення</h5>
                    <a href="<?= BASE_URL ?>/supplier/orders" class="btn btn-sm btn-outline-primary">
                        Всі замовлення
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Замовник</th>
                                    <th>Статус</th>
                                    <th>Сума</th>
                                    <th>Дата доставки</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($active_orders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                                <p class="mb-0">Немає активних замовлень</p>
                                                <small>Замовлення з'являться тут після їх створення</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($active_orders, 0, 5) as $order): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($order['id'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($order['ordered_by_name'] ?? 'Невідомо') ?></td>
                                            <td>
                                                <span class="badge status-<?= $order['status'] ?? 'unknown' ?>">
                                                    <?= Util::getOrderStatusName($order['status'] ?? 'unknown') ?>
                                                </span>
                                            </td>
                                            <td><?= Util::formatMoney($order['total_amount'] ?? 0) ?></td>
                                            <td>
                                                <?= isset($order['delivery_date']) && $order['delivery_date'] 
                                                    ? date('d.m.Y', strtotime($order['delivery_date'])) 
                                                    : '-' ?>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/supplier/viewOrder/<?= $order['id'] ?? '' ?>" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Останні повідомлення -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Останні повідомлення</h5>
                    <a href="<?= BASE_URL ?>/home/messages" class="btn btn-sm btn-outline-primary">
                        Всі повідомлення
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Відправник</th>
                                    <th>Тема</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-envelope fa-2x mb-2"></i>
                                                <p class="mb-0">Немає повідомлень</p>
                                                <small>Нові повідомлення будуть відображатися тут</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $message): ?>
                                        <tr class="<?= isset($message['is_read']) && !$message['is_read'] ? 'message-unread' : '' ?>">
                                            <td><?= htmlspecialchars($message['sender_name'] ?? 'Невідомо') ?></td>
                                            <td><?= htmlspecialchars(mb_substr($message['subject'] ?? '', 0, 30)) ?><?= mb_strlen($message['subject'] ?? '') > 30 ? '...' : '' ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/home/viewMessage/<?= $message['id'] ?? '' ?>" 
                                                   class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Моя сировина -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-cubes me-2"></i>Моя сировина</h5>
                    <div>
                        <a href="<?= BASE_URL ?>/supplier/addMaterial" class="btn btn-sm btn-success me-2">
                            <i class="fas fa-plus me-1"></i>Додати
                        </a>
                        <a href="<?= BASE_URL ?>/supplier/materials" class="btn btn-sm btn-outline-primary">
                            Управління
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th>Опис</th>
                                    <th>Одиниця виміру</th>
                                    <th>Ціна за одиницю</th>
                                    <th>Мін. запас</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($materials)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-cubes fa-2x mb-2"></i>
                                                <p class="mb-0">У вас ще немає доданої сировини</p>
                                                <small>
                                                    <a href="<?= BASE_URL ?>/supplier/addMaterial" class="text-decoration-none">
                                                        Додайте свою першу сировину
                                                    </a>
                                                </small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($materials, 0, 5) as $material): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($material['name'] ?? '') ?></td>
                                            <td>
                                                <?= htmlspecialchars(mb_substr($material['description'] ?? '', 0, 50)) ?>
                                                <?= mb_strlen($material['description'] ?? '') > 50 ? '...' : '' ?>
                                            </td>
                                            <td><?= htmlspecialchars($material['unit'] ?? '') ?></td>
                                            <td><?= Util::formatMoney($material['price_per_unit'] ?? 0) ?></td>
                                            <td><?= $material['min_stock'] ?? 0 ?> <?= htmlspecialchars($material['unit'] ?? '') ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/supplier/editMaterial/<?= $material['id'] ?? '' ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($materials) > 5): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-2">
                                                <a href="<?= BASE_URL ?>/supplier/materials" class="btn btn-outline-secondary btn-sm">
                                                    Переглянути всі (<?= count($materials) ?>)
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Швидкі дії -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-info shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>Швидкі дії</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <a href="<?= BASE_URL ?>/supplier/addMaterial" class="btn btn-outline-success btn-lg w-100">
                                <i class="fas fa-plus fa-2x mb-2"></i>
                                <br>Додати сировину
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= BASE_URL ?>/supplier/orders" class="btn btn-outline-primary btn-lg w-100">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                <br>Переглянути замовлення
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= BASE_URL ?>/home/messages" class="btn btn-outline-info btn-lg w-100">
                                <i class="fas fa-envelope fa-2x mb-2"></i>
                                <br>Повідомлення
                                <?php if ($unread_messages > 0): ?>
                                    <span class="badge bg-danger"><?= $unread_messages ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?= BASE_URL ?>/supplier/reports" class="btn btn-outline-warning btn-lg w-100">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                <br>Звіти
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-stats .card {
    transition: transform 0.2s ease-in-out;
}

.dashboard-stats .card:hover {
    transform: translateY(-2px);
}

.card-icon {
    font-size: 2rem;
    color: #6c757d;
}

.message-unread {
    background-color: #f8f9fa;
    font-weight: bold;
}

.status-pending {
    background-color: #ffc107;
    color: #000;
}

.status-accepted {
    background-color: #17a2b8;
    color: #fff;
}

.status-shipped {
    background-color: #007bff;
    color: #fff;
}

.status-delivered {
    background-color: #28a745;
    color: #fff;
}

.status-canceled {
    background-color: #dc3545;
    color: #fff;
}

.status-unknown {
    background-color: #6c757d;
    color: #fff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Анімація для карток статистики
    const cards = document.querySelectorAll('.dashboard-stats .card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Підказки для пустих таблиць
    const emptyMessages = document.querySelectorAll('.text-muted i.fa-2x');
    emptyMessages.forEach(icon => {
        icon.style.opacity = '0.5';
    });
});
</script>