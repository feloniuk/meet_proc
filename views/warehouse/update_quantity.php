<?php
// views/warehouse/update_quantity.php - Обновленная форма редактирования
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title ?></h1>
        <a href="<?= BASE_URL ?>/warehouse/inventory" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Повернутися до інвентаризації
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Редагування: <?= htmlspecialchars($material['name']) ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <!-- Основная информация о товаре -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="font-weight-bold">Назва товару:</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($material['name']) ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Количество по учету -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Кількість (облік):</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="quantity" class="form-control <?= Util::getErrorClass($errors, 'quantity') ?>" 
                                               value="<?= $inventory['quantity'] ?>" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text"><?= htmlspecialchars($material['unit']) ?></span>
                                        </div>
                                    </div>
                                    <?= Util::getErrorMessage($errors, 'quantity') ?>
                                    <small class="text-muted">
                                        Поточна кількість за обліком
                                    </small>
                                </div>
                            </div>

                            <!-- Фактическое количество -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">Кількість по факту:</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="actual_quantity" class="form-control <?= Util::getErrorClass($errors, 'actual_quantity') ?>" 
                                               value="<?= $inventory['actual_quantity'] ?? '' ?>" placeholder="Фактична кількість">
                                        <div class="input-group-append">
                                            <span class="input-group-text"><?= htmlspecialchars($material['unit']) ?></span>
                                        </div>
                                    </div>
                                    <?= Util::getErrorMessage($errors, 'actual_quantity') ?>
                                    <small class="text-muted">
                                        Кількість виявлена при інвентаризації
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Штрих-код -->
                        <div class="form-group">
                            <label class="font-weight-bold">Штрих-код:</label>
                            <div class="input-group">
                                <input type="text" name="barcode" class="form-control" 
                                       value="<?= htmlspecialchars($inventory['barcode'] ?? '') ?>" 
                                       placeholder="Введіть або сканируйте штрих-код">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-info" onclick="generateBarcode()">
                                        <i class="fas fa-magic"></i> Генерувати
                                    </button>
                                    <?php if (!empty($inventory['barcode'])): ?>
                                    <button type="button" class="btn btn-outline-primary" onclick="printBarcode()">
                                        <i class="fas fa-print"></i> Друк
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <small class="text-muted">
                                Унікальний ідентифікатор для сканування
                            </small>
                        </div>

                        <!-- Расчет разности -->
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-calculator"></i> Розрахунок розбіжності:</h6>
                                    <div id="difference-calculation">
                                        <span class="font-weight-bold">Розбіжність: </span>
                                        <span id="quantity-difference">--</span> <?= htmlspecialchars($material['unit']) ?>
                                        <br>
                                        <span class="font-weight-bold">Вартість розбіжності: </span>
                                        <span id="value-difference">--</span> грн
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Зберегти зміни
                            </button>
                            <a href="<?= BASE_URL ?>/warehouse/inventory" class="btn btn-secondary">
                                Скасувати
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar с дополнительной информацией -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Додаткова інформація</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Мінімальний запас:</strong><br>
                        <?= number_format($material['min_stock'], 2) ?> <?= htmlspecialchars($material['unit']) ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Ціна за одиницю:</strong><br>
                        <?= number_format($material['price_per_unit'], 2) ?> грн
                    </div>
                    
                    <div class="mb-3">
                        <strong>Постачальник:</strong><br>
                        <?= htmlspecialchars($material['supplier_name'] ?? 'Не вказано') ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Останнє оновлення:</strong><br>
                        <?= Util::formatDate($inventory['last_updated'], 'd.m.Y H:i') ?>
                    </div>
                    
                    <?php if (isset($inventory['manager_name']) && $inventory['manager_name']): ?>
                    <div class="mb-3">
                        <strong>Відповідальний:</strong><br>
                        <?= htmlspecialchars($inventory['manager_name']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Карточка статуса запаса -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Статус запасу</h6>
                </div>
                <div class="card-body">
                    <?php
                    $current_quantity = $inventory['quantity'];
                    $min_stock = $material['min_stock'];
                    
                    if ($current_quantity < $min_stock) {
                        $status_class = 'danger';
                        $status_text = 'Критично мало';
                        $status_icon = 'exclamation-triangle';
                    } elseif ($current_quantity < $min_stock * 2) {
                        $status_class = 'warning';
                        $status_text = 'Потребує поповнення';
                        $status_icon = 'exclamation-circle';
                    } else {
                        $status_class = 'success';
                        $status_text = 'Достатній запас';
                        $status_icon = 'check-circle';
                    }
                    ?>
                    
                    <div class="text-center">
                        <div class="mb-3">
                            <i class="fas fa-<?= $status_icon ?> fa-3x text-<?= $status_class ?>"></i>
                        </div>
                        <h5 class="text-<?= $status_class ?>"><?= $status_text ?></h5>
                        
                        <?php if ($current_quantity < $min_stock): ?>
                        <div class="alert alert-<?= $status_class ?> mt-3">
                            <small>
                                <strong>Рекомендація:</strong><br>
                                Необхідно замовити <?= number_format($min_stock * 2 - $current_quantity, 2) ?> <?= htmlspecialchars($material['unit']) ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Данные товара для JavaScript
const materialData = {
    id: <?= $material['id'] ?>,
    pricePerUnit: <?= $material['price_per_unit'] ?>,
    unit: '<?= htmlspecialchars($material['unit']) ?>'
};

// Расчет разности при изменении полей
function calculateDifference() {
    const quantityField = document.querySelector('input[name="quantity"]');
    const actualQuantityField = document.querySelector('input[name="actual_quantity"]');
    
    const quantity = parseFloat(quantityField.value) || 0;
    const actualQuantity = parseFloat(actualQuantityField.value) || 0;
    
    const difference = quantity - actualQuantity;
    const valueDifference = Math.abs(difference * materialData.pricePerUnit);
    
    const differenceElement = document.getElementById('quantity-difference');
    const valueDifferenceElement = document.getElementById('value-difference');
    
    if (actualQuantity > 0) {
        differenceElement.textContent = difference.toFixed(2);
        differenceElement.className = difference > 0 ? 'text-danger font-weight-bold' : 
                                     difference < 0 ? 'text-warning font-weight-bold' : 
                                     'text-success font-weight-bold';
        
        valueDifferenceElement.textContent = valueDifference.toFixed(2);
        valueDifferenceElement.className = Math.abs(difference) > 0.01 ? 'text-danger font-weight-bold' : 'text-success font-weight-bold';
    } else {
        differenceElement.textContent = '--';
        valueDifferenceElement.textContent = '--';
        differenceElement.className = '';
        valueDifferenceElement.className = '';
    }
}

// Генерация штрих-кода
function generateBarcode() {
    fetch(`<?= BASE_URL ?>/warehouse/generateBarcode/<?= $material['id'] ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector('input[name="barcode"]').value = data.barcode;
            } else {
                alert('Помилка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Помилка генерації штрих-коду');
        });
}

// Печать штрих-кода
function printBarcode() {
    const barcode = document.querySelector('input[name="barcode"]').value;
    
    if (!barcode) {
        alert('Штрих-код не вказано');
        return;
    }
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head><title>Штрих-код: ${barcode}</title></head>
        <body style="text-align: center; font-family: monospace;">
            <h2><?= htmlspecialchars($material['name']) ?></h2>
            <div style="font-size: 24px; font-weight: bold; margin: 20px;">
                ${barcode}
            </div>
            <div style="font-size: 12px; margin: 10px;">
                ${new Date().toLocaleDateString('uk-UA')}
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Добавляем обработчики событий
document.addEventListener('DOMContentLoaded', function() {
    const quantityField = document.querySelector('input[name="quantity"]');
    const actualQuantityField = document.querySelector('input[name="actual_quantity"]');
    
    quantityField.addEventListener('input', calculateDifference);
    actualQuantityField.addEventListener('input', calculateDifference);
    
    // Первоначальный расчет
    calculateDifference();
});
</script>