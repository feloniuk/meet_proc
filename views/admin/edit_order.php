<?php
// views/admin/edit_order.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/orders" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i>Редагування замовлення</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Основна інформація про замовлення -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Інформація про замовлення #<?= $order['id'] ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Постачальник:</strong> <?= htmlspecialchars($order['supplier_name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($order['supplier_email']) ?></p>
                            <p><strong>Телефон:</strong> <?= htmlspecialchars($order['supplier_phone'] ?: 'Не вказано') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Дата створення:</strong> <?= Util::formatDate($order['created_at'], 'd.m.Y H:i') ?></p>
                            <p><strong>Очікувана доставка:</strong> <?= $order['delivery_date'] ? date('d.m.Y', strtotime($order['delivery_date'])) : 'Не вказано' ?></p>
                            <p><strong>Статус:</strong> 
                                <span class="badge status-<?= $order['status'] ?>">
                                    <?= Util::getOrderStatusName($order['status']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['notes'])): ?>
                        <div class="form-group mb-0">
                            <label><strong>Примітки:</strong></label>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Список позицій замовлення -->
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Позиції замовлення</h5>
                    <a href="<?= BASE_URL ?>/admin/addOrderItem/<?= $order['id'] ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Додати позицію
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Сировина</th>
                                    <th>Кількість</th>
                                    <th>Ціна за од.</th>
                                    <th>Загальна сума</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-3">Немає доданих позицій</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['material_name']) ?></td>
                                            <td><?= Util::formatQuantity($item['quantity'], $item['unit']) ?></td>
                                            <td><?= Util::formatMoney($item['price_per_unit']) ?></td>
                                            <td><?= Util::formatMoney($item['quantity'] * $item['price_per_unit']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>/admin/editOrderItem/<?= $item['id'] ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/admin/deleteOrderItem/<?= $item['id'] ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Ви впевнені, що хочете видалити цю позицію?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <!-- Загальна сума -->
                                    <tr class="table-primary">
                                        <th colspan="3" class="text-end">Загальна сума:</th>
                                        <th><?= Util::formatMoney($order['total_amount']) ?></th>
                                        <td></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <a href="<?= BASE_URL ?>/admin/orders" class="btn btn-secondary me-2">
                        Скасувати
                    </a>
                    <a href="<?= BASE_URL ?>/admin/viewOrder/<?= $order['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-check me-1"></i>Завершити редагування
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Доступна сировина постачальника -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Доступна сировина</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($materials)): ?>
                        <div class="p-3 text-center">
                            <p class="text-muted mb-0">Постачальник ще не додав жодної сировини</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Назва</th>
                                        <th>Ціна за од.</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($materials as $material): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($material['name']) ?> (<?= htmlspecialchars($material['unit']) ?>)</td>
                                            <td><?= Util::formatMoney($material['price_per_unit']) ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/admin/addOrderItem/<?= $order['id'] ?>?material_id=<?= $material['id'] ?>" 
                                                   class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-plus"></i>
                                                </a>
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
    </div>
</div>