<?php
// views/supplier/edit_material.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/supplier/materials" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i>Редагування сировини</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/supplier/editMaterial/<?= $material['id'] ?>" method="post">
                        <div class="form-group mb-3">
                            <label for="name">Назва сировини</label>
                            <input type="text" 
                                   class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   id="name" 
                                   name="name" 
                                   value="<?= htmlspecialchars($material['name']) ?>" 
                                   required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Опис сировини</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="3"><?= htmlspecialchars($material['description']) ?></textarea>
                            <small class="form-text text-muted">Детальний опис сировини, її характеристики та особливості</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit">Одиниця виміру</label>
                                    <select class="form-select <?= isset($errors['unit']) ? 'is-invalid' : '' ?>" 
                                            id="unit" 
                                            name="unit" 
                                            required>
                                        <option value="">Виберіть одиницю виміру</option>
                                        <option value="кг" <?= $material['unit'] === 'кг' ? 'selected' : '' ?>>кг (кілограм)</option>
                                        <option value="г" <?= $material['unit'] === 'г' ? 'selected' : '' ?>>г (грам)</option>
                                        <option value="л" <?= $material['unit'] === 'л' ? 'selected' : '' ?>>л (літр)</option>
                                        <option value="мл" <?= $material['unit'] === 'мл' ? 'selected' : '' ?>>мл (мілілітр)</option>
                                        <option value="м" <?= $material['unit'] === 'м' ? 'selected' : '' ?>>м (метр)</option>
                                        <option value="см" <?= $material['unit'] === 'см' ? 'selected' : '' ?>>см (сантиметр)</option>
                                        <option value="шт" <?= $material['unit'] === 'шт' ? 'selected' : '' ?>>шт (штука)</option>
                                        <option value="упак" <?= $material['unit'] === 'упак' ? 'selected' : '' ?>>упак (упаковка)</option>
                                    </select>
                                    <?php if (isset($errors['unit'])): ?>
                                        <div class="invalid-feedback"><?= $errors['unit'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price_per_unit">Ціна за одиницю (грн)</label>
                                    <input type="number" 
                                           class="form-control <?= isset($errors['price_per_unit']) ? 'is-invalid' : '' ?>" 
                                           id="price_per_unit" 
                                           name="price_per_unit" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="<?= $material['price_per_unit'] ?>" 
                                           required>
                                    <?php if (isset($errors['price_per_unit'])): ?>
                                        <div class="invalid-feedback"><?= $errors['price_per_unit'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="min_stock">Мінімальний запас</label>
                            <input type="number" 
                                   class="form-control <?= isset($errors['min_stock']) ? 'is-invalid' : '' ?>" 
                                   id="min_stock" 
                                   name="min_stock" 
                                   step="0.01" 
                                   min="0.01" 
                                   value="<?= $material['min_stock'] ?>" 
                                   required>
                            <?php if (isset($errors['min_stock'])): ?>
                                <div class="invalid-feedback"><?= $errors['min_stock'] ?></div>
                            <?php endif; ?>
                            <small class="form-text text-muted">Мінімальна кількість, нижче якої запас вважається критичним</small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/supplier/materials" class="btn btn-secondary me-2">Скасувати</a>
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
                <div class="card-header">
                    <h5 class="card-title mb-0">Інформація про сировину</h5>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?= $material['id'] ?></p>
                    <p><strong>Дата створення:</strong> <?= Util::formatDate($material['created_at'], 'd.m.Y H:i') ?></p>
                    <p><strong>Поточна назва:</strong> <?= htmlspecialchars($material['name']) ?></p>
                    <p><strong>Поточна ціна:</strong> <?= Util::formatMoney($material['price_per_unit']) ?></p>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Поради щодо редагування</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Зміна ціни</h6>
                        <p class="small">При зміні ціни враховуйте поточні замовлення та договірні зобов'язання.</p>
                    </div>
                    
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Мінімальний запас</h6>
                        <p class="small">Коригуйте мінімальний запас залежно від сезонності та попиту.</p>
                    </div>
                    
                    <div>
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i>Опис</h6>
                        <p class="small">Детальний опис допомагає клієнтам краще розуміти вашу продукцію.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>