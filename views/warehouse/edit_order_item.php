<?php
// views/admin/edit_order_item.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/editOrder/<?= $item['order_id'] ?>" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i>Редагування позиції замовлення</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Редагування позиції</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/editOrderItem/<?= $item['id'] ?>" method="post">
                        <div class="form-group mb-3">
                            <label for="material_name">Сировина</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="material_name" 
                                   value="<?= htmlspecialchars($item['material_name']) ?> (<?= htmlspecialchars($item['unit']) ?>)" 
                                   disabled>
                            <small class="form-text text-muted">Тип сировини не можна змінити</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="quantity">Кількість (<?= htmlspecialchars($item['unit']) ?>)</label>
                                    <input type="number" 
                                           class="form-control <?= Util::getErrorClass($errors, 'quantity') ?>" 
                                           id="quantity" 
                                           name="quantity" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="<?= $item['quantity'] ?>" 
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
                                           value="<?= $item['price_per_unit'] ?>" 
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
                            <a href="<?= BASE_URL ?>/admin/editOrder/<?= $item['order_id'] ?>" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Зберегти зміни
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Інформація про замовлення</h5>
                </div>
                <div class="card-body">
                    <p><strong>Номер замовлення:</strong> #<?= $order['id'] ?></p>
                    <p><strong>Постачальник:</strong> <?= htmlspecialchars($order['supplier_name']) ?></p>
                    <p><strong>Дата створення:</strong> <?= Util::formatDate($order['created_at'], 'd.m.Y H:i') ?></p>
                    <p><strong>Статус:</strong> 
                        <span class="badge status-<?= $order['status'] ?>">
                            <?= Util::getOrderStatusName($order['status']) ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const quantityInput = document.getElementById('quantity');
    const priceInput = document.getElementById('price_per_unit');
    const totalAmountDisplay = document.getElementById('total-amount');
    
    function formatMoney(amount) {
        return parseFloat(amount).toFixed(2) + ' грн';
    }
    
    function calculateTotal() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const total = quantity * price;
        
        totalAmountDisplay.textContent = formatMoney(total);
    }
    
    quantityInput.addEventListener('input', calculateTotal);
    priceInput.addEventListener('input', calculateTotal);
    
    // Початковий розрахунок
    calculateTotal();
});
</script>