<?php
// views/technologist/create_quality_check.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-plus me-2"></i>Створення перевірки якості</h1>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Форма створення перевірки</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/technologist/createQualityCheck" method="post">
                        <div class="form-group mb-3">
                            <label for="order_id">Замовлення для перевірки:</label>
                            <select class="form-control <?= isset($errors['order_id']) ? 'is-invalid' : '' ?>" 
                                   id="order_id" name="order_id" required>
                                <option value="">Виберіть замовлення</option>
                                <?php foreach ($orders as $order): ?>
                                    <option value="<?= $order['id'] ?>" 
                                           <?= isset($_POST['order_id']) && $_POST['order_id'] == $order['id'] ? 'selected' : '' ?>>
                                        Замовлення #<?= $order['id'] ?> - <?= htmlspecialchars($order['supplier_name']) ?> 
                                        (<?= $order['delivery_date'] ? date('d.m.Y', strtotime($order['delivery_date'])) : 'Без дати' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['order_id'])): ?>
                                <div class="invalid-feedback"><?= $errors['order_id'] ?></div>
                            <?php endif; ?>
                            <small class="form-text text-muted">
                                Відображаються тільки замовлення зі статусом "Відправлено", які потребують перевірки якості.
                            </small>
                        </div>

                        <div class="form-group mb-4">
                            <label for="notes">Початкові примітки:</label>
                            <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>" 
                                     id="notes" name="notes" rows="3" 
                                     placeholder="Додаткові примітки до перевірки..."><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
                            <?php if (isset($errors['notes'])): ?>
                                <div class="invalid-feedback"><?= $errors['notes'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Скасувати
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Створити перевірку
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($orders)): ?>
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Немає замовлень для перевірки.</strong> 
                Перевірки якості створюються автоматично для замовлень зі статусом "Відправлено". 
                Поки що немає таких замовлень, або всі вже мають створені перевірки.
            </div>
            <?php endif; ?>

            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Інформація</h5>
                </div>
                <div class="card-body">
                    <p>
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Перевірки якості зазвичай створюються автоматично, коли замовлення отримує статус "Відправлено".
                    </p>
                    <p>
                        <i class="fas fa-clock text-warning me-2"></i>
                        Після створення перевірки ви зможете провести детальний аналіз якості сировини.
                    </p>
                    <p>
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Тільки схвалена технологом сировина може бути прийнята на склад.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderSelect = document.getElementById('order_id');
    
    // Добавляем информацию о заказе при выборе
    orderSelect.addEventListener('change', function() {
        if (this.value) {
            // Можно добавить AJAX-запрос для получения деталей заказа
            console.log('Вибрано замовлення:', this.value);
        }
    });
});
</script>