<?php
// views/admin/orders.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-shopping-cart me-2"></i>Замовлення сировини</h1>
        <a href="<?= BASE_URL ?>/warehouse/createOrder" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Створити замовлення
        </a>
    </div>

    <!-- Фільтри -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="<?= BASE_URL ?>/warehouse/orders" method="get" class="row g-3">
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
                    <label for="supplier_id" class="form-label">Постачальник</label>
                    <select name="supplier_id" id="supplier_id" class="form-select">
                        <option value="">Всі постачальники</option>
                        <?php 
                            $userModel = new User();
                            $suppliers = $userModel->getSuppliers();
                            foreach ($suppliers as $supplier):
                        ?>
                            <option value="<?= $supplier['id'] ?>" <?= isset($_GET['supplier_id']) && $_GET['supplier_id'] == $supplier['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($supplier['name']) ?>
                            </option>
                        <?php endforeach; ?>
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
                <div class="col-12 d-flex justify-content-end">
                    <a href="<?= BASE_URL ?>/warehouse/orders" class="btn btn-outline-secondary me-2">Скинути</a>
                    <button type="submit" class="btn btn-primary">
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
                            <th>Постачальник</th>
                            <th>Дата замовлення</th>
                            <th>Дата доставки</th>
                            <th>Статус</th>
                            <th>Сума</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                                <?php
                                // Розрахунок прибутку
                                $ordersModel = new Order();
                                $orders = $ordersModel->getAll();
                                ?>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">Немає замовлень</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['supplier_name']) ?></td>
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
                                            <a href="<?= BASE_URL ?>/warehouse/viewOrder/<?= $order['id'] ?>" class="btn btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <a href="<?= BASE_URL ?>/warehouse/editOrder/<?= $order['id'] ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($order['status'] === 'shipped'): ?>
                                                <a href="<?= BASE_URL ?>/warehouse/deliverOrder/<?= $order['id'] ?>" 
                                                   class="btn btn-outline-success" 
                                                   onclick="return confirm('Ви впевнені, що хочете підтвердити отримання замовлення?');">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'canceled'): ?>
                                                <a href="<?= BASE_URL ?>/warehouse/cancelOrder/<?= $order['id'] ?>" 
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