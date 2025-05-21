<?php
// Извлекаем переменные из массива $data, если они переданы таким образом
if (isset($data) && is_array($data)) {
    extract($data);
}

// Инициализируем переменные, если они не установлены
$products = $products ?? [];
$errors = $errors ?? [];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-calendar-plus me-2"></i>Планування виробництва</h1>
        <a href="<?= BASE_URL ?>/warehouse/production" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Назад до виробництва
        </a>
    </div>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Форма планування виробництва</h5>
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>/warehouse/planProduction" method="post">
                    <div class="form-group mb-3">
                        <label for="product_id">Продукт:</label>
                        <select class="form-control <?= isset($errors['product_id']) ? 'is-invalid' : '' ?>" 
                               id="product_id" name="product_id" required>
                            <option value="">Виберіть продукт</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>" 
                                       <?= isset($_POST['product_id']) && $_POST['product_id'] == $product['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['recipe_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['product_id'])): ?>
                            <div class="invalid-feedback"><?= $errors['product_id'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="quantity">Кількість:</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01" class="form-control <?= isset($errors['quantity']) ? 'is-invalid' : '' ?>" 
                                  id="quantity" name="quantity" 
                                  value="<?= isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : '' ?>" required>
                            <span class="input-group-text">кг</span>
                            <?php if (isset($errors['quantity'])): ?>
                                <div class="invalid-feedback"><?= $errors['quantity'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="started_at">Дата і час початку:</label>
                        <input type="datetime-local" class="form-control <?= isset($errors['started_at']) ? 'is-invalid' : '' ?>" 
                              id="started_at" name="started_at" 
                              value="<?= isset($_POST['started_at']) ? htmlspecialchars($_POST['started_at']) : date('Y-m-d\TH:i') ?>" required>
                        <?php if (isset($errors['started_at'])): ?>
                            <div class="invalid-feedback"><?= $errors['started_at'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label for="notes">Примітки:</label>
                        <textarea class="form-control <?= isset($errors['notes']) ? 'is-invalid' : '' ?>" 
                                 id="notes" name="notes" rows="3"><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
                        <?php if (isset($errors['notes'])): ?>
                            <div class="invalid-feedback"><?= $errors['notes'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/warehouse/production" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Скасувати
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-1"></i>Запланувати
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card shadow-sm mt-4">
            <div class="card-header">
                <h5 class="mb-0">Інформація</h5>
            </div>
            <div class="card-body">
                <p>
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    Після планування виробничого процесу вам потрібно буде розпочати його, коли всі необхідні матеріали будуть підготовлені.
                </p>
                <p>
                    <i class="fas fa-exclamation-circle text-warning me-2"></i>
                    Перед початком виробничого процесу система перевірить наявність всіх необхідних матеріалів на складі.
                </p>
                <p>
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Після завершення виробничого процесу не забудьте позначити його як "Завершено" в системі.
                </p>
            </div>
        </div>
    </div>
</div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Устанавливаем минимальную дату начала - текущая дата и время
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
        document.getElementById('started_at').min = minDateTime;
    });
</script>