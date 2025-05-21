<?php
// views/supplier/add_material.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/supplier/materials" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-plus me-2"></i>Додавання сировини</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/supplier/addMaterial" method="post">
                        <div class="form-group mb-3">
                            <label for="name">Назва сировини</label>
                            <input type="text" 
                                   class="form-control <?= Util::getErrorClass($errors, 'name') ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'name') ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Опис сировини</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                            <small class="form-text text-muted">Детальний опис сировини, її характеристики та особливості</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit">Одиниця виміру</label>
                                    <select class="form-select <?= Util::getErrorClass($errors, 'unit') ?>" 
                                            id="unit" 
                                            name="unit" 
                                            required>
                                        <option value="">Виберіть одиницю виміру</option>
                                        <option value="кг" <?= isset($_POST['unit']) && $_POST['unit'] === 'кг' ? 'selected' : '' ?>>кг (кілограм)</option>
                                        <option value="г" <?= isset($_POST['unit']) && $_POST['unit'] === 'г' ? 'selected' : '' ?>>г (грам)</option>
                                        <option value="л" <?= isset($_POST['unit']) && $_POST['unit'] === 'л' ? 'selected' : '' ?>>л (літр)</option>
                                        <option value="мл" <?= isset($_POST['unit']) && $_POST['unit'] === 'мл' ? 'selected' : '' ?>>мл (мілілітр)</option>
                                        <option value="м" <?= isset($_POST['unit']) && $_POST['unit'] === 'м' ? 'selected' : '' ?>>м (метр)</option>
                                        <option value="см" <?= isset($_POST['unit']) && $_POST['unit'] === 'см' ? 'selected' : '' ?>>см (сантиметр)</option>
                                        <option value="шт" <?= isset($_POST['unit']) && $_POST['unit'] === 'шт' ? 'selected' : '' ?>>шт (штука)</option>
                                        <option value="упак" <?= isset($_POST['unit']) && $_POST['unit'] === 'упак' ? 'selected' : '' ?>>упак (упаковка)</option>
                                    </select>
                                    <?= Util::getErrorMessage($errors, 'unit') ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price_per_unit">Ціна за одиницю (грн)</label>
                                    <input type="number" 
                                           class="form-control <?= Util::getErrorClass($errors, 'price_per_unit') ?>" 
                                           id="price_per_unit" 
                                           name="price_per_unit" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="<?= isset($_POST['price_per_unit']) ? htmlspecialchars($_POST['price_per_unit']) : '' ?>" 
                                           required>
                                    <?= Util::getErrorMessage($errors, 'price_per_unit') ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="min_stock">Мінімальний запас</label>
                            <input type="number" 
                                   class="form-control <?= Util::getErrorClass($errors, 'min_stock') ?>" 
                                   id="min_stock" 
                                   name="min_stock" 
                                   step="0.01" 
                                   min="0.01" 
                                   value="<?= isset($_POST['min_stock']) ? htmlspecialchars($_POST['min_stock']) : '' ?>" 
                                   required>
                            <?= Util::getErrorMessage($errors, 'min_stock') ?>
                            <small class="form-text text-muted">Мінімальна кількість, нижче якої запас вважається критичним</small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/supplier/materials" class="btn btn-secondary me-2">Скасувати</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Додати сировину
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Поради щодо додавання сировини</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Назва сировини</h6>
                        <p class="small">Використовуйте чіткі та зрозумілі назви, які допоможуть легко ідентифікувати сировину.</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Ціноутворення</h6>
                        <p class="small">Встановлюйте конкурентоспроможні ціни з урахуванням якості та ринкових умов.</p>
                    </div>
                    
                    <div>
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Мінімальний запас</h6>
                        <p class="small">Мінімальний запас допомагає клієнтам планувати закупівлі та уникати дефіциту.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>