<?php
// views/admin/add_order_item.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/editOrder/<?= $order['id'] ?>" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-plus me-2"></i>Додавання позиції замовлення</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Додати позицію до замовлення #<?= $order['id'] ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/addOrderItem/<?= $order['id'] ?>" method="post">
                        <div class="form-group mb-3">
                            <label for="raw_material_id">Сировина</label>
                            <select class="form-select <?= Util::getErrorClass($errors, 'raw_material_id') ?>" 
                                    id="raw_material_id" 
                                    name="raw_material_id" 
                                    required>
                                <option value="">Виберіть сировину</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?= $material['id'] ?>" 
                                            data-unit="<?= htmlspecialchars($material['unit']) ?>"
                                            data-price="<?= $material['price_per_unit'] ?>"
                                            <?= (isset($_POST['raw_material_id']) && $_POST['raw_material_id'] == $material['id']) || 
                                                (isset($_GET['material_id']) && $_GET['material_id'] == $material['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($material['name']) ?> (<?= htmlspecialchars($material['unit']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= Util::getErrorMessage($errors, 'raw_material_id') ?>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="quantity">Кількість <span id="unit-display"></span></label>
                                    <input type="number" 
                                           class="form-control <?= Util::getErrorClass($errors, 'quantity') ?>" 
                                           id="quantity" 
                                           name="quantity" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>" 
                                           required>
                                    <?= Util::getErrorMessage($errors, 'quantity') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price_per_unit">Ціна за одиницю (грн)</label>
                                    <input type="number" 
                                           class="form-control <?= Util::getErrorClass($errors, 'price_per_unit') ?>" 
                                           id="price_per_unit" 
                                           name="price_per_unit" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="<?= isset($_POST['price_per_unit']) ? htmlspecialchars($_POST['price_per_unit']) : '' ?>" 
                                           required>
                                    <?= Util::getErrorMessage($errors, 'price_per_unit') ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Загальна сума</label>
                            <div id="total-amount" class="form-control bg-light">0.00 грн</div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/editOrder/<?= $order['id'] ?>" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Додати позицію
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Інформація про замовлення -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Інформація про замовлення</h5>
                </div>
                <div class="card-body">
                    <p><strong>Номер замовлення:</strong> <?= $order['id'] ?></p>
                    <p><strong>Постачальник:</strong> <?= htmlspecialchars($order['supplier_name']) ?></p>
                    <p><strong>Дата створення:</strong> <?= Util::formatDate($order['created_at'], 'd.m.Y H:i') ?></p>
                    <p><strong>Очікувана доставка:</strong> <?= $order['delivery_date'] ? date('d.m.Y', strtotime($order['delivery_date'])) : 'Не вказано' ?></p>
                    <p><strong>Статус:</strong> 
                        <span class="badge status-<?= $order['status'] ?>">
                            <?= Util::getOrderStatusName($order['status']) ?>
                        </span>
                    </p>
                    <p><strong>Поточна сума:</strong> <?= Util::formatMoney($order['total_amount']) ?></p>
                </div>
            </div>
            
            <!-- Підказки -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Підказки</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Вибір сировини</h6>
                        <p class="small">Виберіть сировину з наявної у постачальника. Ціна за одиницю буде автоматично заповнена, але ви можете змінити її.</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Кількість</h6>
                        <p class="small">Вкажіть необхідну кількість сировини в одиницях виміру, які використовує постачальник.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const materialSelect = document.getElementById('raw_material_id');
    const unitDisplay = document.getElementById('unit-display');
    const priceInput = document.getElementById('price_per_unit');
    const quantityInput = document.getElementById('quantity');
    const totalAmountDisplay = document.getElementById('total-amount');
    
    function formatMoney(amount) {
        return parseFloat(amount).toFixed(2) + ' грн';
    }
    
    function updateMaterialInfo() {
        const selectedOption = materialSelect.options[materialSelect.selectedIndex];
        
        // Оновлюємо відображення одиниці виміру
        if (selectedOption.value) {
            const unit = selectedOption.getAttribute('data-unit');
            unitDisplay.textContent = `(${unit})`;
            
            // Автоматично заповнюємо ціну, якщо поле порожнє або змінився матеріал
            const price = selectedOption.getAttribute('data-price');
            if (!priceInput.value || materialSelect.dataset.lastSelected !== selectedOption.value) {
                priceInput.value = price;
            }
            
            // Запам'ятовуємо останній вибраний матеріал
            materialSelect.dataset.lastSelected = selectedOption.value;
        } else {
            unitDisplay.textContent = '';
            priceInput.value = '';
        }
        
        calculateTotal();
    }
    
    function calculateTotal() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const total = quantity * price;
        
        totalAmountDisplay.textContent = formatMoney(total);
    }
    
    // Слухачі подій
    materialSelect.addEventListener('change', updateMaterialInfo);
    quantityInput.addEventListener('input', calculateTotal);
    priceInput.addEventListener('input', calculateTotal);
    
    // Ініціалізація при завантаженні сторінки
    updateMaterialInfo();
});
</script>