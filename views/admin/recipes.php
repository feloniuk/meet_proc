<?php
// views/admin/recipes.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-book me-2"></i>Управління рецептами</h1>
        <a href="<?= BASE_URL ?>/admin/addRecipe" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Додати рецепт
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Назва</th>
                            <th>Інгредієнти</th>
                            <th>Собівартість</th>
                            <th>Автор</th>
                            <th>Дата створення</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recipes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">Немає рецептів</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recipes as $recipe): ?>
                                <?php
                                // Отримуємо кількість інгредієнтів для кожного рецепта
                                $recipeModel = new Recipe();
                                $ingredients = $recipeModel->getIngredients($recipe['id']);
                                $ingredientCount = count($ingredients);
                                
                                // Розраховуємо собівартість рецепта
                                $cost = $recipeModel->calculateCost($recipe['id']);
                                ?>
                                <tr>
                                    <td><?= $recipe['id'] ?></td>
                                    <td><?= htmlspecialchars($recipe['name']) ?></td>
                                    <td><?= $ingredientCount ?> позицій</td>
                                    <td><?= Util::formatMoney($cost) ?></td>
                                    <td><?= htmlspecialchars($recipe['creator_name']) ?></td>
                                    <td><?= Util::formatDate($recipe['created_at'], 'd.m.Y') ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>/admin/viewRecipe/<?= $recipe['id'] ?>" class="btn btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/editRecipe/<?= $recipe['id'] ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/deleteRecipe/<?= $recipe['id'] ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Ви впевнені, що хочете видалити цей рецепт?');">
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
