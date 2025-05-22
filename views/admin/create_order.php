<?php
// views/admin/create_order.php - ИСПРАВЛЕННАЯ ВЕРСИЯ
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/orders" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-plus me-2"></i>Створення замовлення</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/createOrder" method="post">
                        <div class="form-group mb-3">
                            <label for="supplier_id">Постачальник</label>
                            <select class="form-select <?= Util::getErrorClass($errors, 'supplier_id') ?>" 
                                    id="supplier_id" 
                                    name="supplier_id" 
                                    required>
                                <option value="">Виберіть постачальника</option>
                                <?php if (!empty($suppliers)): ?>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <option value="<?= $supplier['id'] ?>" 
                                                <?= isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($supplier['name']) ?> (<?= htmlspecialchars($supplier['email']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Немає доступних постачальників</option>
                                <?php endif; ?>
                            </select>
                            <?= Util::getErrorMessage($errors, 'supplier_id') ?>
                            
                            <?php if (empty($suppliers)): ?>
                                <div class="alert alert-warning mt-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Немає зареєстрованих постачальників. 
                                    <a href="<?= BASE_URL ?>/admin/addUser" class="alert-link">Додайте спочатку постачальника</a>.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="delivery_date">Бажана дата доставки</label>
                            <input type="date" 
                                   class="form-control <?= Util::getErrorClass($errors, 'delivery_date') ?>" 
                                   id="delivery_date" 
                                   name="delivery_date" 
                                   min="<?= date('Y-m-d') ?>" 
                                   value="<?= isset($_POST['delivery_date']) ? htmlspecialchars($_POST['delivery_date']) : date('Y-m-d', strtotime('+7 days')) ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'delivery_date') ?>
                            <small class="form-text text-muted">Рекомендується вказувати дату доставки не менше ніж через 7 днів від поточної дати.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="notes">Примітки до замовлення</label>
                            <textarea class="form-control" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3"><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
                            <small class="form-text text-muted">Вкажіть додаткову інформацію для постачальника (за необхідності).</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Після створення замовлення ви зможете додати до нього позиції.
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/orders" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary" <?= empty($suppliers) ? 'disabled' : '' ?>>
                                <i class="fas fa-save me-1"></i>Створити замовлення
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Подсказки -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Підказки</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Вибір постачальника</h6>
                        <p class="small">Виберіть постачальника, який має необхідну вам сировину. Після створення замовлення ви зможете додати позиції лише з сировини цього постачальника.</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Дата доставки</h6>
                        <p class="small">Вкажіть реалістичну дату доставки, враховуючи терміни виробництва та доставки.</p>
                    </div>
                    
                    <?php if (empty($suppliers)): ?>
                        <div class="mb-3">
                            <h6><i class="fas fa-user-plus text-primary me-2"></i>Додавання постачальників</h6>
                            <p class="small">Для створення замовлення потрібен хоча б один постачальник в системі.</p>
                            <a href="<?= BASE_URL ?>/admin/addUser" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i>Додати постачальника
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Сырье с низким запасом -->
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">Сировина з критично низьким запасом</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                        try {
                            $inventoryModel = new Inventory();
                            $lowStock = $inventoryModel->getCriticalLowStock();
                        } catch (Exception $e) {
                            $lowStock = [];
                        }
                    ?>
                    
                    <?php if (empty($lowStock)): ?>
                        <div class="p-3 text-center">
                            <p class="text-success mb-0">
                                <i class="fas fa-check-circle me-1"></i>
                                Всі матеріали мають достатній запас
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Матеріал</th>
                                        <th>Запас</th>
                                        <th>Постачальник</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($lowStock, 0, 5) as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['material_name']) ?></td>
                                            <td class="text-danger"><?= Util::formatQuantity($item['quantity'], $item['unit']) ?></td>
                                            <td><?= htmlspecialchars($item['supplier_name'] ?: 'Не вказано') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (count($lowStock) > 5): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">
                                                <small class="text-muted">...та ще <?= count($lowStock) - 5 ?> позицій</small>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($lowStock)): ?>
                    <div class="card-footer">
                        <a href="<?= BASE_URL ?>/admin/inventoryReport" class="btn btn-sm btn-outline-danger w-100">
                            <i class="fas fa-chart-bar me-1"></i>Детальний звіт по запасам
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем наличие поставщиков и показываем соответствующие сообщения
    const supplierSelect = document.getElementById('supplier_id');
    const submitButton = document.querySelector('button[type="submit"]');
    
    if (supplierSelect.options.length <= 1) {
        // Только опция "Выберите поставщика", поставщиков нет
        submitButton.disabled = true;
        supplierSelect.disabled = true;
    }
    
    // Автоматически устанавливаем дату доставки через неделю
    const deliveryDateInput = document.getElementById('delivery_date');
    if (!deliveryDateInput.value) {
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        deliveryDateInput.value = nextWeek.toISOString().split('T')[0];
    }
});
</script>