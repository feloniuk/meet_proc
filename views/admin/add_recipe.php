<?php
// views/admin/add_recipe.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/admin/recipes" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-book me-2"></i>Додавання рецепту</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/addRecipe" method="post">
                        <div class="form-group mb-3">
                            <label for="name">Назва рецепту</label>
                            <input type="text" 
                                   class="form-control <?= Util::getErrorClass($errors, 'name') ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'name') ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Опис рецепту</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                            <small class="form-text text-muted">Детальний опис процесу приготування ковбаси</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Після створення рецепту ви зможете додати до нього інгредієнти.
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/recipes" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Створити рецепт
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Поради щодо створення рецепту</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Назва рецепту</h6>
                        <p class="small">Використовуйте короткі, але інформативні назви, які відображають особливості ковбаси.</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Опис рецепту</h6>
                        <p class="small">Опишіть процес приготування, включаючи температурні режими, тривалість етапів та особливості приготування.</p>
                    </div>
                    
                    <div>
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Інгредієнти</h6>
                        <p class="small">Після створення рецепту, вам потрібно буде додати всі необхідні інгредієнти з вказанням їх кількості.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>