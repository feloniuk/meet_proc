<?php
// views/warehouse/inventory.php - UPDATED WITH NEW COLUMNS
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-warehouse me-2"></i>
                        Інвентаризація складу
                    </h5>
                    <div>
                        <button class="btn btn-success btn-sm" onclick="generateDiscrepancyReport()">
                            <i class="fas fa-balance-scale"></i> Звіт про розбіжності
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="exportInventory()">
                            <i class="fas fa-download"></i> Експорт
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($inventory)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Інвентаризація порожня
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Назва сировини</th>
                                        <th>Штрих-код</th>
                                        <th>Кількість планова</th>
                                        <th>Кількість по факту</th>
                                        <th>Розбіжність</th>
                                        <th>Одиниці</th>
                                        <th>Останнє оновлення</th>
                                        <th>Дії</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory as $index => $item): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['material_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo $item['barcode'] ?: 'Немає'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo number_format($item['quantity'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $actualQty = $item['actual_quantity'] ?? $item['quantity'];
                                                $colorClass = 'bg-primary';
                                                if ($actualQty != $item['quantity']) {
                                                    $colorClass = $actualQty > $item['quantity'] ? 'bg-success' : 'bg-warning';
                                                }
                                                ?>
                                                <span class="badge <?php echo $colorClass; ?>">
                                                    <?php echo number_format($actualQty, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $difference = $actualQty - $item['quantity'];
                                                if ($difference != 0): 
                                                ?>
                                                    <span class="badge <?php echo $difference > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo ($difference > 0 ? '+' : '') . number_format($difference, 2); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d.m.Y H:i', strtotime($item['last_updated'])); ?>
                                                </small>
                                                <?php if ($item['manager_name']): ?>
                                                    <br><small class="text-info"><?php echo htmlspecialchars($item['manager_name']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editQuantity(<?php echo $item['raw_material_id']; ?>)"
                                                            title="Редагувати кількість">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info" 
                                                            onclick="editFactual(<?php echo $item['raw_material_id']; ?>)"
                                                            title="Редагувати фактичну кількість">
                                                        <i class="fas fa-calculator"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" 
                                                            onclick="editBarcode(<?php echo $item['raw_material_id']; ?>)"
                                                            title="Редагувати штрих-код">
                                                        <i class="fas fa-barcode"></i>
                                                    </button>
                                                </div>
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

<!-- Модальное окно для редактирования фактической количества -->
<div class="modal fade" id="editFactualModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редагувати фактичну кількість</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editFactualForm">
                    <input type="hidden" id="factual_material_id">
                    <div class="mb-3">
                        <label class="form-label">Назва сировини:</label>
                        <p id="factual_material_name" class="fw-bold"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Планова кількість:</label>
                        <p id="factual_planned_qty" class="text-info"></p>
                    </div>
                    <div class="mb-3">
                        <label for="actual_quantity" class="form-label">Фактична кількість:</label>
                        <input type="number" step="0.01" class="form-control" id="actual_quantity" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" onclick="saveFactualQuantity()">Зберегти</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для редактирования штрих-кода -->
<div class="modal fade" id="editBarcodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редагувати штрих-код</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editBarcodeForm">
                    <input type="hidden" id="barcode_material_id">
                    <div class="mb-3">
                        <label class="form-label">Назва сировини:</label>
                        <p id="barcode_material_name" class="fw-bold"></p>
                    </div>
                    <div class="mb-3">
                        <label for="barcode" class="form-label">Штрих-код:</label>
                        <input type="text" class="form-control" id="barcode" maxlength="50">
                        <div class="form-text">Залиште порожнім для автоматичної генерації</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-warning" onclick="generateBarcode()">Генерувати</button>
                <button type="button" class="btn btn-primary" onclick="saveBarcode()">Зберегти</button>
            </div>
        </div>
    </div>
</div>

<script>
// Редагування планової кількості (існуюча функція)
function editQuantity(materialId) {
    window.location.href = '<?php echo BASE_URL; ?>/warehouse/updateQuantity/' + materialId;
}

// Редагування фактичної кількості
function editFactual(materialId) {
    // Получаем данные о материале
    fetch('<?php echo BASE_URL; ?>/api/getMaterial/' + materialId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('factual_material_id').value = materialId;
                document.getElementById('factual_material_name').textContent = data.material.name;
                document.getElementById('factual_planned_qty').textContent = data.material.quantity + ' ' + data.material.unit;
                document.getElementById('actual_quantity').value = data.material.actual_quantity || data.material.quantity;
                
                new bootstrap.Modal(document.getElementById('editFactualModal')).show();
            }
        });
}

// Сохранение фактической количества
function saveFactualQuantity() {
    const materialId = document.getElementById('factual_material_id').value;
    const actualQuantity = document.getElementById('actual_quantity').value;
    
    if (!actualQuantity) {
        alert('Вкажіть фактичну кількість');
        return;
    }
    
    fetch('<?php echo BASE_URL; ?>/warehouse/updateActualQuantity', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'material_id=' + encodeURIComponent(materialId) + 
              '&actual_quantity=' + encodeURIComponent(actualQuantity)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Помилка: ' + data.message);
        }
    });
}

// Редагування штрих-коду
function editBarcode(materialId) {
    fetch('<?php echo BASE_URL; ?>/api/getMaterial/' + materialId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('barcode_material_id').value = materialId;
                document.getElementById('barcode_material_name').textContent = data.material.name;
                document.getElementById('barcode').value = data.material.barcode || '';
                
                new bootstrap.Modal(document.getElementById('editBarcodeModal')).show();
            }
        });
}

// Генерация штрих-кода
function generateBarcode() {
    const materialId = document.getElementById('barcode_material_id').value;
    document.getElementById('barcode').value = 'BC' + materialId.padStart(6, '0');
}

// Сохранение штрих-кода
function saveBarcode() {
    const materialId = document.getElementById('barcode_material_id').value;
    const barcode = document.getElementById('barcode').value;
    
    fetch('<?php echo BASE_URL; ?>/warehouse/updateBarcode', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'material_id=' + encodeURIComponent(materialId) + 
              '&barcode=' + encodeURIComponent(barcode)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Помилка: ' + data.message);
        }
    });
}

// Отчет о расхождениях
function generateDiscrepancyReport() {
    window.open('<?php echo BASE_URL; ?>/warehouse/discrepancyReport', '_blank');
}

// Экспорт инвентаризации
function exportInventory() {
    window.open('<?php echo BASE_URL; ?>/warehouse/exportInventory', '_blank');
}
</script>