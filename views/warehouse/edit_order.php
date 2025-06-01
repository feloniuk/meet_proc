<?php
// views/admin/edit_order.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/warehouse/orders" class="btn btn-outline-primary me-2">
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
                    <a href="<?= BASE_URL ?>/warehouse/addOrderItem/<?= $order['id'] ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Додати позицію
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0" id="orderItemsTable">
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
                                    <tr id="noItemsRow">
                                        <td colspan="5" class="text-center py-3">Немає доданих позицій</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr data-item-id="<?= $item['id'] ?>">
                                            <td class="item-name"><?= htmlspecialchars($item['material_name']) ?></td>
                                            <td class="item-quantity"><?= Util::formatQuantity($item['quantity'], $item['unit']) ?></td>
                                            <td class="item-price"><?= Util::formatMoney($item['price_per_unit']) ?></td>
                                            <td class="item-total"><?= Util::formatMoney($item['quantity'] * $item['price_per_unit']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary edit-item-btn" 
                                                            data-item-id="<?= $item['id'] ?>"
                                                            data-unit="<?= htmlspecialchars($item['unit']) ?>"
                                                            title="Редагувати">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="<?= BASE_URL ?>/warehouse/deleteOrderItem/<?= $item['id'] ?>" 
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
                                        <th id="totalAmount"><?= Util::formatMoney($order['total_amount']) ?></th>
                                        <td></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <a href="<?= BASE_URL ?>/warehouse/orders" class="btn btn-secondary me-2">
                        Скасувати
                    </a>
                    <a href="<?= BASE_URL ?>/warehouse/viewOrder/<?= $order['id'] ?>" class="btn btn-primary">
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
                                                <a href="<?= BASE_URL ?>/warehouse/addOrderItem/<?= $order['id'] ?>?material_id=<?= $material['id'] ?>" 
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

<!-- Модальне вікно для редагування позиції -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editItemModalLabel">Редагування позиції</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    <input type="hidden" id="edit_item_id" name="item_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Сировина</label>
                        <input type="text" class="form-control" id="edit_material_name" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_quantity" class="form-label">Кількість <span id="edit_unit"></span></label>
                                <input type="number" class="form-control" id="edit_quantity" name="quantity" step="0.01" min="0.01" required>
                                <div class="invalid-feedback">Кількість повинна бути більше нуля</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_price_per_unit" class="form-label">Ціна за одиницю (грн)</label>
                                <input type="number" class="form-control" id="edit_price_per_unit" name="price_per_unit" step="0.01" min="0.01" required>
                                <div class="invalid-feedback">Ціна повинна бути більше нуля</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Загальна сума</label>
                        <div id="edit_total_amount" class="form-control bg-light">0.00 грн</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="saveItemBtn">
                    <i class="fas fa-save me-1"></i>Зберегти зміни
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editItemModal = new bootstrap.Modal(document.getElementById('editItemModal'));
    const editItemForm = document.getElementById('editItemForm');
    const saveItemBtn = document.getElementById('saveItemBtn');
    
    // Функція форматування грошей
    function formatMoney(amount) {
        return parseFloat(amount).toFixed(2) + ' грн';
    }
    
    // Обробник кнопки редагування
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            const unit = this.getAttribute('data-unit');
            
            // Завантажуємо дані позиції
            fetch(`<?= BASE_URL ?>/warehouse/ajaxGetOrderItem?id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.item;
                        
                        // Заповнюємо форму
                        document.getElementById('edit_item_id').value = item.id;
                        document.getElementById('edit_material_name').value = item.material_name;
                        document.getElementById('edit_quantity').value = item.quantity;
                        document.getElementById('edit_price_per_unit').value = item.price_per_unit;
                        document.getElementById('edit_unit').textContent = `(${unit})`;
                        
                        // Розраховуємо суму
                        updateEditTotal();
                        
                        // Відкриваємо модальне вікно
                        editItemModal.show();
                    } else {
                        alert('Помилка: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Помилка при завантаженні даних');
                });
        });
    });
    
    // Розрахунок загальної суми при редагуванні
    function updateEditTotal() {
        const quantity = parseFloat(document.getElementById('edit_quantity').value) || 0;
        const price = parseFloat(document.getElementById('edit_price_per_unit').value) || 0;
        const total = quantity * price;
        
        document.getElementById('edit_total_amount').textContent = formatMoney(total);
    }
    
    // Слухачі для автоматичного розрахунку
    document.getElementById('edit_quantity').addEventListener('input', updateEditTotal);
    document.getElementById('edit_price_per_unit').addEventListener('input', updateEditTotal);
    
    // Збереження змін
    saveItemBtn.addEventListener('click', function() {
        if (!editItemForm.checkValidity()) {
            editItemForm.classList.add('was-validated');
            return;
        }
        
        const formData = new FormData(editItemForm);
        
        // Відправляємо AJAX запит
        fetch('<?= BASE_URL ?>/warehouse/ajaxUpdateOrderItem', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Оновлюємо рядок в таблиці
                const itemId = formData.get('item_id');
                const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
                
                if (row) {
                    const item = data.item;
                    row.querySelector('.item-quantity').textContent = 
                        parseFloat(item.quantity).toFixed(2) + ' ' + item.unit;
                    row.querySelector('.item-price').textContent = formatMoney(item.price_per_unit);
                    row.querySelector('.item-total').textContent = 
                        formatMoney(parseFloat(item.quantity) * parseFloat(item.price_per_unit));
                }
                
                // Оновлюємо загальну суму
                document.getElementById('totalAmount').textContent = formatMoney(data.totalAmount);
                
                // Закриваємо модальне вікно
                editItemModal.hide();
                
                // Показуємо повідомлення про успіх
                showSuccessMessage(data.message);
                
                // Очищаємо форму
                editItemForm.classList.remove('was-validated');
            } else {
                alert('Помилка: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Помилка при збереженні даних');
        });
    });
    
    // Функція для показу повідомлення про успіх
    function showSuccessMessage(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
        alert.style.zIndex = '9999';
        alert.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(alert);
        
        // Автоматично приховуємо через 3 секунди
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }
    
    // Очищаємо форму при закритті модального вікна
    document.getElementById('editItemModal').addEventListener('hidden.bs.modal', function () {
        editItemForm.classList.remove('was-validated');
        editItemForm.reset();
    });
});
</script>