<?php
// views/admin/view_recipe.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/recipes" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-book me-2"></i>Перегляд рецепту</h1>
        <div class="ms-auto">
            <a href="<?= BASE_URL ?>/admin/editRecipe/<?= $recipe['id'] ?>" class="btn btn-primary me-2">
                <i class="fas fa-edit me-1"></i>Редагувати
            </a>
            <a href="<?= BASE_URL ?>/admin/deleteRecipe/<?= $recipe['id'] ?>" 
               class="btn btn-danger" 
               onclick="return confirm('Ви впевнені, що хочете видалити цей рецепт?');">
                <i class="fas fa-trash me-1"></i>Видалити
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Основна інформація про рецепт -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?= htmlspecialchars($recipe['name']) ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recipe['description'])): ?>
                        <p class="text-muted">Немає опису рецепту</p>
                    <?php else: ?>
                        <h6>Опис:</h6>
                        <p><?= nl2br(htmlspecialchars($recipe['description'])) ?></p>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between">
                        <p><strong>Автор:</strong> <?= htmlspecialchars($recipe['creator_name']) ?></p>
                        <p><strong>Створено:</strong> <?= Util::formatDate($recipe['created_at'], 'd.m.Y H:i') ?></p>
                    </div>
                </div>
            </div>

            <!-- Список інгредієнтів -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Інгредієнти</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th>Кількість</th>
                                    <th>Ціна за одиницю</th>
                                    <th>Вартість</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ingredients)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">Немає доданих інгредієнтів</td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                        $totalCost = 0;
                                        $rawMaterialModel = new RawMaterial();
                                    ?>
                                    <?php foreach ($ingredients as $ingredient): ?>
                                        <?php
                                            $material = $rawMaterialModel->getById($ingredient['raw_material_id']);
                                            $itemCost = $ingredient['quantity'] * $material['price_per_unit'];
                                            $totalCost += $itemCost;
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ingredient['material_name']) ?></td>
                                            <td><?= Util::formatQuantity($ingredient['quantity'], $ingredient['unit']) ?></td>
                                            <td><?= Util::formatMoney($material['price_per_unit']) ?></td>
                                            <td><?= Util::formatMoney($itemCost) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <!-- Загальна вартість -->
                                    <tr class="table-primary">
                                        <th colspan="3" class="text-end">Загальна собівартість:</th>
                                        <th><?= Util::formatMoney($totalCost) ?></th>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Продукти на основі цього рецепту -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Продукти на основі рецепту</h5>
                </div>
                <div class="card-body">
                    <?php
                        $productModel = new Product();
                        $products = $productModel->getByRecipe($recipe['id']);
                    ?>
                    <?php if (empty($products)): ?>
                        <p class="text-center text-muted">Немає продуктів на основі цього рецепту</p>
                        <div class="d-grid">
                            <a href="<?= BASE_URL ?>/admin/addProduct" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i>Створити продукт
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($products as $product): ?>
                                <a href="<?= BASE_URL ?>/admin/editProduct/<?= $product['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($product['name']) ?>
                                    <span class="badge bg-primary rounded-pill"><?= Util::formatMoney($product['price']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Аналіз прибутковості -->
            <?php if (!empty($products)): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Аналіз прибутковості</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Продукт</th>
                                        <th>Ціна</th>
                                        <th>Рентабельність</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <?php
                                            $profit = $productModel->calculateProfit($product['id']);
                                            $profitMargin = ($profit['profit'] / $profit['price']) * 100;
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td><?= Util::formatMoney($product['price']) ?></td>
                                            <td class="<?= $profitMargin >= 30 ? 'text-success' : ($profitMargin >= 15 ? 'text-primary' : 'text-danger') ?>">
                                                <?= number_format($profitMargin, 1) ?>%
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Рекомендована рентабельність: 20-30%
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>