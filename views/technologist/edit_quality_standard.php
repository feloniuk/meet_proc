<?php
// views/technologist/edit_quality_standard.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/technologist/qualityStandards" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i>Редагування стандарту якості</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Редагування стандарту для: <?= htmlspecialchars($standard['material_name']) ?></h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/technologist/editQualityStandard/<?= $standard['id'] ?>" method="post">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Матеріал:</strong> <?= htmlspecialchars($standard['material_name']) ?>
                            <br><small>Змінити матеріал можна тільки при створенні нового стандарту</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="parameter_name">Назва параметру:</label>
                            <input type="text" class="form-control <?= isset($errors['parameter_name']) ? 'is-invalid' : '' ?>" 
                                  id="parameter_name" name="parameter_name" 
                                  value="<?= isset($_POST['parameter_name']) ? htmlspecialchars($_POST['parameter_name']) : htmlspecialchars($standard['parameter_name']) ?>" 
                                  placeholder="Наприклад: Температура, pH, Вологість" required>
                            <?php if (isset($errors['parameter_name'])): ?>
                                <div class="invalid-feedback"><?= $errors['parameter_name'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="min_value">Мінімальне значення:</label>
                                    <input type="number" step="0.01" class="form-control <?= isset($errors['min_value']) ? 'is-invalid' : '' ?>" 
                                          id="min_value" name="min_value" 
                                          value="<?= isset($_POST['min_value']) ? htmlspecialchars($_POST['min_value']) : $standard['min_value'] ?>" 
                                          placeholder="Мінімум">
                                    <?php if (isset($errors['min_value'])): ?>
                                        <div class="invalid-feedback"><?= $errors['min_value'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="max_value">Максимальне значення:</label>
                                    <input type="number" step="0.01" class="form-control <?= isset($errors['max_value']) ? 'is-invalid' : '' ?>" 
                                          id="max_value" name="max_value" 
                                          value="<?= isset($_POST['max_value']) ? htmlspecialchars($_POST['max_value']) : $standard['max_value'] ?>" 
                                          placeholder="Максимум">
                                    <?php if (isset($errors['max_value'])): ?>
                                        <div class="invalid-feedback"><?= $errors['max_value'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="unit">Одиниця виміру:</label>
                                    <input type="text" class="form-control <?= isset($errors['unit']) ? 'is-invalid' : '' ?>" 
                                          id="unit" name="unit" 
                                          value="<?= isset($_POST['unit']) ? htmlspecialchars($_POST['unit']) : htmlspecialchars($standard['unit']) ?>" 
                                          placeholder="°C, %, pH" maxlength="20">
                                    <?php if (isset($errors['unit'])): ?>
                                        <div class="invalid-feedback"><?= $errors['unit'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Опис стандарту:</label>
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                     id="description" name="description" rows="3" 
                                     placeholder="Детальний опис параметру та методу вимірювання..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : htmlspecialchars($standard['description']) ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= $errors['description'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="is_critical" name="is_critical" value="1" 
                                   <?= (isset($_POST['is_critical']) ? $_POST['is_critical'] : $standard['is_critical']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_critical">
                                <strong>Критичний параметр</strong>
                                <small class="text-muted d-block">Невідповідність цьому параметру може бути підставою для автоматичного відхилення сировини</small>
                            </label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/technologist/qualityStandards" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Скасувати
                            </a>
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
                    <h5 class="mb-0">Поточні значення</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Параметр:</th>
                            <td><?= htmlspecialchars($standard['parameter_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Мінімум:</th>
                            <td><?= $standard['min_value'] !== null ? $standard['min_value'] : '-' ?></td>
                        </tr>
                        <tr>
                            <th>Максимум:</th>
                            <td><?= $standard['max_value'] !== null ? $standard['max_value'] : '-' ?></td>
                        </tr>
                        <tr>
                            <th>Одиниця:</th>
                            <td><?= htmlspecialchars($standard['unit']) ?></td>
                        </tr>
                        <tr>
                            <th>Критичний:</th>
                            <td><?= $standard['is_critical'] ? 'Так' : 'Ні' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Історія змін</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        <i class="fas fa-clock me-2"></i>
                        Створено: <?= date('d.m.Y H:i', strtotime($standard['created_at'])) ?>
                    </p>
                    <small class="text-muted">
                        Для перегляду повної історії змін стандартів звертайтесь до адміністратора системи.
                    </small>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Небезпечна зона</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Видалення стандарту може вплинути на майбутні перевірки якості цього матеріалу.
                    </p>
                    <a href="<?= BASE_URL ?>/technologist/deleteQualityStandard/<?= $standard['id'] ?>" 
                       class="btn btn-outline-danger btn-sm w-100"
                       onclick="return confirm('Ви впевнені, що хочете видалити цей стандарт якості? Ця дія незворотна.');">
                        <i class="fas fa-trash me-1"></i>Видалити стандарт
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Валідація форми
    const form = document.querySelector('form');
    const minValue = document.getElementById('min_value');
    const maxValue = document.getElementById('max_value');
    
    function validateRange() {
        const min = parseFloat(minValue.value);
        const max = parseFloat(maxValue.value);
        
        if (min && max && min >= max) {
            maxValue.setCustomValidity('Максимальне значення повинно бути більше мінімального');
        } else {
            maxValue.setCustomValidity('');
        }
    }
    
    minValue.addEventListener('input', validateRange);
    maxValue.addEventListener('input', validateRange);
    
    // При загрузке проверяем
    validateRange();
});
</script>