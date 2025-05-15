<?php
// views/admin/products.php
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><i class="fas fa-drumstick-bite me-2"></i>Управління продукцією</h1>
        <a href="<?= BASE_URL ?>/admin/addProduct" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Додати продукт
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
                            <th>Рецепт</th>
                            <th>Вага, кг</th>
                            <th>Ціна</th>
                            <th>Собівартість</th>
                            <th>Прибуток</th>
                            <th>Рентабельність</th>
                            <th>Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-3">Немає продуктів</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <?php
                                // Розрахунок прибутку
                                $productModel = new Product();
                                $profit = $productModel->calculateProfit($product['id']);
                                $profitMargin = ($profit['profit'] / $profit['price']) * 100;
                                ?>
                                <tr>
                                    <td><?= $product['id'] ?></td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['recipe_name']) ?></td>
                                    <td><?= $product['weight'] ?></td>
                                    <td><?= Util::formatMoney($product['price']) ?></td>
                                    <td><?= Util::formatMoney($profit['cost']) ?></td>
                                    <td><?= Util::formatMoney($profit['profit']) ?></td>
                                    <td class="<?= $profitMargin >= 30 ? 'text-success' : ($profitMargin >= 15 ? 'text-primary' : 'text-danger') ?>">
                                        <?= number_format($profitMargin, 1) ?>%
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>/admin/editProduct/<?= $product['id'] ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/deleteProduct/<?= $product['id'] ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Ви впевнені, що хочете видалити цей продукт?');">
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