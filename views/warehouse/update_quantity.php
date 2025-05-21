<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}

// Проверяем наличие необходимых данных
if (!isset($material) || !isset($inventory)) {
    echo '<div class="alert alert-danger">Матеріал не знайдено</div>';
    exit;
}

// Инициализируем переменные, если они не установлены
$errors = $errors ?? [];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-balance-scale me-2"></i>Оновлення кількості</h1>
        <a href="<?= BASE_URL ?>/warehouse/inventory" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Назад до інвентаризації
        </a>
    </div>
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Інформація про сировину</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Назва:</th>
                        <td><?= htmlspecialchars($material['name']) ?></td>
                    </tr>
                    <tr>
                        <th>Одиниця виміру:</th>
                        <td><?= htmlspecialchars($material['unit']) ?></td>
                    </tr>
                    <tr>
                        <th>Поточна кількість:</th>
                        <td>
                            <?= number_format($inventory['quantity'], 2) ?> <?= htmlspecialchars($material['unit']) ?>
                            <?php if ($inventory['quantity'] < $material['min_stock']): ?>
                                <span class="badge bg-danger ms-2">Критичний запас</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Мінімальний запас:</th>
                        <td><?= number_format($material['min_stock'], 2) ?> <?= htmlspecialchars($material['unit']) ?></td>
                    </tr>
                    <tr>
                        <th>Ціна за одиницю:</th>
                        <td><?= Util::formatMoney($material['price_per_unit']) ?></td>
                    </tr>
                    <tr>
                        <th>Загальна вартість:</th>
                        <td><?= Util::formatMoney($inventory['quantity'] * $material['price_per_unit']) ?></td>
                    </tr>
                    <tr>
                        <th>Останнє оновлення:</th>
                        <td><?= Util::formatDate($inventory['last_updated']) ?></td>
                    </tr>
                    <?php if (!empty($inventory['manager_name'])): ?>
                    <tr>
                        <th>Оновлено:</th>
                        <td><?= htmlspecialchars($inventory['manager_name']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">Оновлення кількості</h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/warehouse/updateQuantity/<?= $material['id'] ?>" method="post">
                    <div class="form-group mb-3">
                        <label for="current_quantity">Поточна кількість:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="current_quantity" value="<?= number_format($inventory['quantity'], 2) ?>" readonly>
                            <span class="input-group-text"><?= htmlspecialchars($material['unit']) ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="quantity">Нова кількість:</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" class="form-control <?= isset($errors['quantity']) ? 'is-invalid' : '' ?>" 
                                  id="quantity" name="quantity" value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : $inventory['quantity'] ?>" required>
                            <span class="input-group-text"><?= htmlspecialchars($material['unit']) ?></span>
                            <?php if (isset($errors['quantity'])): ?>
                                <div class="invalid-feedback"><?= $errors['quantity'] ?></div>
                            <?php endif; ?>
                        </div>
                        <small class="form-text text-muted">Введіть нову загальну кількість сировини на складі.</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="operation">Тип операції:</label>
                        <select class="form-control" id="operation">
                            <option value="set">Встановити</option>
                            <option value="add">Додати</option>
                            <option value="subtract">Відняти</option>
                        </select>
                        <small class="form-text text-muted">Виберіть тип операції для розрахунку нової кількості.</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="operation_quantity">Кількість для операції:</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" class="form-control" id="operation_quantity" value="0">
                            <span class="input-group-text"><?= htmlspecialchars($material['unit']) ?></span>
                            <button type="button" class="btn btn-outline-primary" id="applyOperation">Застосувати</button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/warehouse/inventory" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Скасувати
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Зберегти
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($inventory['quantity'] < $material['min_stock']): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Увага!</strong> Поточна кількість нижче мінімального запасу. Рекомендується замовити більше сировини.
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const quantityInput = document.getElementById('quantity');
        const operationSelect = document.getElementById('operation');
        const operationQuantityInput = document.getElementById('operation_quantity');
        const applyOperationButton = document.getElementById('applyOperation');
        const currentQuantity = parseFloat(document.getElementById('current_quantity').value.replace(/\s+/g, '').replace(',', '.'));
        
        // Функция применения операции
        applyOperationButton.addEventListener('click', function() {
            const operation = operationSelect.value;
            const operationQuantity = parseFloat(operationQuantityInput.value) || 0;
            let newQuantity = 0;
            
            switch (operation) {
                case 'set':
                    newQuantity = operationQuantity;
                    break;
                case 'add':
                    newQuantity = currentQuantity + operationQuantity;
                    break;
                case 'subtract':
                    newQuantity = Math.max(0, currentQuantity - operationQuantity);
                    break;
            }
            
            quantityInput.value = newQuantity.toFixed(2);
        });
    });
</script>