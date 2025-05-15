<?php
// views/admin/edit_ingredient.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/editRecipe/<?= $ingredient['recipe_id'] ?>" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i>Редагування інгредієнту</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Редагування інгредієнту для рецепту "<?= htmlspecialchars($ingredient['recipe_name']) ?>"</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/editIngredient/<?= $ingredient['id'] ?>" method="post">
                        <div class="form-group mb-3">
                            <label for="material_name">Сировина</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="material_name" 
                                   value="<?= htmlspecialchars($ingredient['material_name']) ?>" 
                                   disabled>
                            <small class="form-text text-muted">Тип сировини не можна змінити. Якщо потрібно замінити сировину, видаліть цей інгредієнт і додайте новий.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="quantity">Кількість (<?= htmlspecialchars($ingredient['unit']) ?>)</label>
                            <input type="number" 
                                   class="form-control <?= Util::getErrorClass($errors, 'quantity') ?>" 
                                   id="quantity" 
                                   name="quantity" 
                                   step="0.01" 
                                   min="0.01" 
                                   value="<?= $ingredient['quantity'] ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'quantity') ?>
                            <small class="form-text text-muted">Кількість на 1 кг готової продукції</small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/editRecipe/<?= $ingredient['recipe_id'] ?>" class="btn btn-secondary me-2">Скасувати</a>
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
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Інформація про інгредієнт</h5>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?= $ingredient['id'] ?></p>
                    <p><strong>Рецепт:</strong> <?= htmlspecialchars($ingredient['recipe_name']) ?></p>
                    <p><strong>Сировина:</strong> <?= htmlspecialchars($ingredient['material_name']) ?></p>
                    <p><strong>Одиниця виміру:</strong> <?= htmlspecialchars($ingredient['unit']) ?></p>
                    
                    <hr>

                    <?php
                        // Отримуємо поточну ціну сировини
                        $rawMaterialModel = new RawMaterial();
                        $material = $rawMaterialModel->getById($ingredient['raw_material_id']);
                        $itemCost = $ingredient['quantity'] * $material['price_per_unit'];
                    ?>
                    
                    <h6>Вартість:</h6>
                    <p><?= Util::formatQuantity($ingredient['quantity'], $ingredient['unit']) ?> × 
                       <?= Util::formatMoney($material['price_per_unit']) ?> = 
                       <?= Util::formatMoney($itemCost) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>