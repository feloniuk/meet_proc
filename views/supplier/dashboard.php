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
                                <?= count(array_filter($active_orders, function($order) {
                                    return $order['status'] !== 'delivered' && $order['status'] !== 'canceled';
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
                            <h6 class="card-title text-muted mb-0">Повідомлення</h6>
                            <h2 class="mt-2 mb-0"><?= $unread_messages ?></h2>
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
                            <h2 class="mt-2 mb-0"><?= count($materials) ?></h2>
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
                                        <td colspan="6" class="text-center py-3">Немає активних замовлень</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($active_orders, 0, 5) as $order): ?>
                                        <tr>
                                            <td><?= $order['id'] ?></td>
                                            <td><?= htmlspecialchars($order['ordered_by_name']) ?></td>
                                            <td>
                                                <span class="badge status-<?= $order['status'] ?>">
                                                    <?= Util::getOrderStatusName($order['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= Util::formatMoney($order['total_amount']) ?></td>
                                            <td><?= $order['delivery_date'] ? date('d.m.Y', strtotime($order['delivery_date'])) : '-' ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/supplier/viewOrder/<?= $order['id'] ?>" 
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
                                        <td colspan="3" class="text-center py-3">Немає повідомлень</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $message): ?>
                                        <tr class="<?= $message['is_read'] ? '' : 'message-unread' ?>">
                                            <td><?= htmlspecialchars($message['sender_name']) ?></td>
                                            <td><?= htmlspecialchars($message['subject']) ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/home/viewMessage/<?= $message['id'] ?>" 
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($materials)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3">У вас ще немає доданої сировини</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($materials, 0, 5) as $material): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($material['name']) ?></td>
                                            <td><?= htmlspecialchars(mb_substr($material['description'], 0, 50)) . (mb_strlen($material['description']) > 50 ? '...' : '') ?></td>
                                            <td><?= htmlspecialchars($material['unit']) ?></td>
                                            <td><?= Util::formatMoney($material['price_per_unit']) ?></td>
                                            <td><?= $material['min_stock'] ?> <?= htmlspecialchars($material['unit']) ?></td>
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
</div>