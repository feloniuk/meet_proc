<?php
// views/admin/edit_recipe.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/recipes" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i>Редагування рецепту</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Основна інформація про рецепт -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Основна інформація</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/editRecipe/<?= $recipe['id'] ?>" method="post">
                        <div class="form-group mb-3">
                            <label for="name">Назва рецепту</label>
                            <input type="text" 
                                   class="form-control <?= Util::getErrorClass($errors, 'name') ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($recipe['name']) ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'name') ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Опис рецепту</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"><?= htmlspecialchars($recipe['description'] ?: '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/viewRecipe/<?= $recipe['id'] ?>" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Зберегти зміни
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Список інгредієнтів -->
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Інгредієнти</h5>
                    <a href="<?= BASE_URL ?>/admin/addIngredient/<?= $recipe['id'] ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Додати інгредієнт
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th>Кількість</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ingredients)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-3">Немає доданих інгредієнтів</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($ingredients as $ingredient): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ingredient['material_name']) ?></td>
                                            <td><?= Util::formatQuantity($ingredient['quantity'], $ingredient['unit']) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>/admin/editIngredient/<?= $ingredient['id'] ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/admin/deleteIngredient/<?= $ingredient['id'] ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Ви впевнені, що хочете видалити цей інгредієнт?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Інформація про рецепт</h5>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?= $recipe['id'] ?></p>
                    <p><strong>Створено:</strong> <?= Util::formatDate($recipe['created_at'], 'd.m.Y H:i') ?></p>
                    <p><strong>Автор:</strong> <?= htmlspecialchars($recipe['creator_name']) ?></p>
                    
                    <hr>
                    
                    <!-- Розрахункова собівартість -->
                    <?php
                        $recipeModel = new Recipe();
                        $cost = $recipeModel->calculateCost($recipe['id']);
                    ?>
                    <h6>Собівартість рецепту:</h6>
                    <h4 class="text-primary"><?= Util::formatMoney($cost) ?></h4>
                    
                    <!-- Продукти на основі цього рецепту -->
                    <?php
                        $productModel = new Product();
                        $products = $productModel->getByRecipe($recipe['id']);
                    ?>
                    <?php if (!empty($products)): ?>
                        <hr>
                        <h6>Продукти на основі цього рецепту:</h6>
                        <ul>
                            <?php foreach ($products as $product): ?>
                                <li><?= htmlspecialchars($product['name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>