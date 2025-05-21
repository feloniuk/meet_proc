<?php
// views/supplier/orders.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-shopping-cart me-2"></i>Мої замовлення</h1>
        <a href="<?= BASE_URL ?>/supplier/ordersReport" class="btn btn-outline-primary">
            <i class="fas fa-chart-bar me-1"></i>Звіт по замовленнях
        </a>
    </div>

    <!-- Фільтри -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="<?= BASE_URL ?>/supplier/orders" method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Статус</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Всі статуси</option>
                        <option value="pending" <?= isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : '' ?>>Очікує підтвердження</option>
                        <option value="accepted" <?= isset($_GET['status']) && $_GET['status'] === 'accepted' ? 'selected' : '' ?>>Прийнято</option>
                        <option value="shipped" <?= isset($_GET['status']) && $_GET['status'] === 'shipped' ? 'selected' : '' ?>>Відправлено</option>
                        <option value="delivered" <?= isset($_GET['status']) && $_GET['status'] === 'delivered' ? 'selected' : '' ?>>Доставлено</option>
                        <option value="canceled" <?= isset($_GET['status']) && $_GET['status'] === 'canceled' ? 'selected' : '' ?>>Скасовано</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Дата від</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?= isset($_GET['date_from']) ? $_GET['date_from'] : '' ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Дата до</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?= isset($_GET['date_to']) ? $_GET['date_to'] : '' ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Застосувати
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Замовник</th>
                            <th>Дата замовлення</th>
                            <th>Дата доставки</th>
                            <th>Статус</th>
                            <th>Сума</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">Немає замовлень</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['ordered_by_name']) ?></td>
                                    <td><?= Util::formatDate($order['created_at'], 'd.m.Y') ?></td>
                                    <td><?= $order['delivery_date'] ? date('d.m.Y', strtotime($order['delivery_date'])) : '-' ?></td>
                                    <td>
                                        <span class="badge status-<?= $order['status'] ?>">
                                            <?= Util::getOrderStatusName($order['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= Util::formatMoney($order['total_amount']) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>/supplier/viewOrder/<?= $order['id'] ?>" class="btn btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <a href="<?= BASE_URL ?>/supplier/acceptOrder/<?= $order['id'] ?>" 
                                                   class="btn btn-outline-success" 
                                                   onclick="return confirm('Ви впевнені, що хочете прийняти замовлення?');">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($order['status'] === 'accepted'): ?>
                                                <a href="<?= BASE_URL ?>/supplier/shipOrder/<?= $order['id'] ?>" 
                                                   class="btn btn-outline-primary" 
                                                   onclick="return confirm('Ви впевнені, що хочете відправити замовлення?');">
                                                    <i class="fas fa-truck"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'canceled'): ?>
                                                <a href="<?= BASE_URL ?>/supplier/cancelOrder/<?= $order['id'] ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Ви впевнені, що хочете скасувати замовлення?');">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
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