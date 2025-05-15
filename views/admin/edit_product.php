<?php
// views/admin/edit_product.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/products" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i>Редагування продукту</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/editProduct/<?= $product['id'] ?>" method="post">
                        <div class="form-group mb-3">
                            <label for="name">Назва продукту</label>
                            <input type="text" 
                                   class="form-control <?= Util::getErrorClass($errors, 'name') ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($product['name']) ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'name') ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Опис продукту</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3"><?= htmlspecialchars($product['description'] ?: '') ?></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="recipe_id">Рецепт</label>
                            <select class="form-select <?= Util::getErrorClass($errors, 'recipe_id') ?>" 
                                    id="recipe_id" 
                                    name="recipe_id" 
                                    required>
                                <option value="">Виберіть рецепт</option>
                                <?php foreach ($recipes as $recipe): ?>
                                    <option value="<?= $recipe['id'] ?>" 
                                            data-cost="<?= (new Recipe())->calculateCost($recipe['id']) ?>"
                                            <?= $product['recipe_id'] == $recipe['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($recipe['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= Util::getErrorMessage($errors, 'recipe_id') ?>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="weight">Вага (кг)</label>
                                    <input type="number" 
                                           class="form-control <?= Util::getErrorClass($errors, 'weight') ?>" 
                                           id="weight" 
                                           name="weight" 
                                           step="0.01" 
                                           min="0.1" 
                                           value="<?= $product['weight'] ?>" 
                                           required>
                                    <?= Util::getErrorMessage($errors, 'weight') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price">Ціна (грн)</label>
                                    <input type="number" 
                                           class="form-control <?= Util::getErrorClass($errors, 'price') ?>" 
                                           id="price" 
                                           name="price" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="<?= $product['price'] ?>" 
                                           required>
                                    <?= Util::getErrorMessage($errors, 'price') ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/products" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Зберегти зміни
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Розрахунок прибутковості</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Собівартість за рецептом (на 1 кг):</label>
                        <div id="recipe-cost" class="form-control bg-light">0.00 грн</div>
                    </div>
                    
                    <div class="mb-3">
                        <label>Собівартість продукту:</label>
                        <div id="product-cost" class="form-control bg-light">0.00 грн</div>
                    </div>
                    
                    <div class="mb-3">
                        <label>Прибуток з продукту:</label>
                        <div id="product-profit" class="form-control bg-light">0.00 грн</div>
                    </div>
                    
                    <div class="mb-3">
                        <label>Рентабельність:</label>
                        <div id="product-margin" class="form-control bg-light">0%</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Рекомендована рентабельність: 20-30%
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Інформація про продукт</h5>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?= $product['id'] ?></p>
                    <p><strong>Дата створення:</strong> <?= Util::formatDate($product['created_at'], 'd.m.Y H:i') ?></p>
                    
                    <hr>
                    
                    <!-- Виробничі процеси для цього продукту -->
                    <?php
                        $productionModel = new Production();
                        $production = $productionModel->getByProduct($product['id']);
                        $completed = array_filter($production, function($p) { return $p['status'] === 'completed'; });
                        $totalProduced = array_sum(array_column($completed, 'quantity'));
                    ?>
                    
                    <h6>Статистика виробництва:</h6>
                    <ul>
                        <li>Всього вироблено: <?= $totalProduced ?> кг</li>
                        <li>Кількість процесів: <?= count($production) ?></li>
                        <li>Завершено: <?= count($completed) ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Розрахунок прибутковості продукту
document.addEventListener('DOMContentLoaded', function() {
    const recipeSelect = document.getElementById('recipe_id');
    const weightInput = document.getElementById('weight');
    const priceInput = document.getElementById('price');
    
    const recipeCostElement = document.getElementById('recipe-cost');
    const productCostElement = document.getElementById('product-cost');
    const productProfitElement = document.getElementById('product-profit');
    const productMarginElement = document.getElementById('product-margin');
    
    function formatMoney(amount) {
        return parseFloat(amount).toFixed(2) + ' грн';
    }
    
    function calculateProfit() {
        // Отримуємо вибраний рецепт та його вартість
        const selectedOption = recipeSelect.options[recipeSelect.selectedIndex];
        if (!selectedOption.value) {
            recipeCostElement.textContent = '0.00 грн';
            productCostElement.textContent = '0.00 грн';
            productProfitElement.textContent = '0.00 грн';
            productMarginElement.textContent = '0%';
            return;
        }
        
        const recipeCost = parseFloat(selectedOption.getAttribute('data-cost'));
        const weight = parseFloat(weightInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        
        // Розрахунок собівартості та прибутку
        const productCost = recipeCost * weight;
        const profit = price - productCost;
        const margin = price > 0 ? (profit / price) * 100 : 0;
        
        // Відображення результатів
        recipeCostElement.textContent = formatMoney(recipeCost);
        productCostElement.textContent = formatMoney(productCost);
        productProfitElement.textContent = formatMoney(profit);
        productMarginElement.textContent = margin.toFixed(1) + '%';
        
        // Встановлення кольору для рентабельності
        if (margin >= 30) {
            productMarginElement.className = 'form-control bg-success text-white';
        } else if (margin >= 15) {
            productMarginElement.className = 'form-control bg-primary text-white';
        } else if (margin > 0) {
            productMarginElement.className = 'form-control bg-warning';
        } else {
            productMarginElement.className = 'form-control bg-danger text-white';
        }
    }
    
    // Слухачі подій для перерахунку при зміні значень
    recipeSelect.addEventListener('change', calculateProfit);
    weightInput.addEventListener('input', calculateProfit);
    priceInput.addEventListener('input', calculateProfit);
    
    // Початковий розрахунок
    calculateProfit();
});
</script>