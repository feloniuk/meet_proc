<?php
// views/admin/add_ingredient.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/editRecipe/<?= $recipe['id'] ?>" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-plus me-2"></i>Додавання інгредієнту</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Додати інгредієнт до рецепту "<?= htmlspecialchars($recipe['name']) ?>"</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/addIngredient/<?= $recipe['id'] ?>" method="post">
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
                                            <?= isset($_POST['raw_material_id']) && $_POST['raw_material_id'] == $material['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($material['name']) ?> (<?= htmlspecialchars($material['unit']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= Util::getErrorMessage($errors, 'raw_material_id') ?>
                        </div>

                        <div class="form-group mb-3">
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
                            <small class="form-text text-muted">Кількість на 1 кг готової продукції</small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/editRecipe/<?= $recipe['id'] ?>" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Додати інгредієнт
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Підказка</h5>
                </div>
                <div class="card-body">
                    <p>Вкажіть кількість сировини, яка використовується для виготовлення 1 кг готової продукції за цим рецептом.</p>
                    
                    <p>Наприклад:</p>
                    <ul>
                        <li>0.8 кг свинини</li>
                        <li>0.02 кг солі</li>
                        <li>0.005 кг перцю</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        Переконайтеся, що загальна вага інгредієнтів відповідає очікуваному виходу продукту з урахуванням усушки.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Відображення одиниці виміру біля кількості
document.addEventListener('DOMContentLoaded', function() {
    const materialSelect = document.getElementById('raw_material_id');
    const unitDisplay = document.getElementById('unit-display');
    
    function updateUnitDisplay() {
        const selectedOption = materialSelect.options[materialSelect.selectedIndex];
        if (selectedOption.value) {
            const unit = selectedOption.getAttribute('data-unit');
            unitDisplay.textContent = `(${unit})`;
        } else {
            unitDisplay.textContent = '';
        }
    }
    
    materialSelect.addEventListener('change', updateUnitDisplay);
    updateUnitDisplay(); // Ініціалізація при завантаженні сторінки
});
</script>