<?php
// views/technologist/add_quality_standard.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/technologist/qualityStandards" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-plus me-2"></i>Додавання стандарту якості</h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Форма додавання стандарту</h5>
                </div>
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/technologist/addQualityStandard" method="post">
                        <div class="form-group mb-3">
                            <label for="raw_material_id">Сировина:</label>
                            <select class="form-control <?= isset($errors['raw_material_id']) ? 'is-invalid' : '' ?>" 
                                   id="raw_material_id" name="raw_material_id" required>
                                <option value="">Виберіть сировину</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?= $material['id'] ?>" 
                                           <?= isset($_POST['raw_material_id']) && $_POST['raw_material_id'] == $material['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($material['name']) ?> (<?= htmlspecialchars($material['unit']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['raw_material_id'])): ?>
                                <div class="invalid-feedback"><?= $errors['raw_material_id'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label for="parameter_name">Назва параметру:</label>
                            <input type="text" class="form-control <?= isset($errors['parameter_name']) ? 'is-invalid' : '' ?>" 
                                  id="parameter_name" name="parameter_name" 
                                  value="<?= isset($_POST['parameter_name']) ? htmlspecialchars($_POST['parameter_name']) : '' ?>" 
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
                                          value="<?= isset($_POST['min_value']) ? htmlspecialchars($_POST['min_value']) : '' ?>" 
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
                                          value="<?= isset($_POST['max_value']) ? htmlspecialchars($_POST['max_value']) : '' ?>" 
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
                                          value="<?= isset($_POST['unit']) ? htmlspecialchars($_POST['unit']) : '' ?>" 
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
                                     placeholder="Детальний опис параметру та методу вимірювання..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= $errors['description'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="is_critical" name="is_critical" value="1" 
                                   <?= isset($_POST['is_critical']) ? 'checked' : '' ?>>
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
                                <i class="fas fa-plus me-1"></i>Додати стандарт
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Рекомендації</h5>
                </div>
                <div class="card-body">
                    <h6>Типові параметри для м'яса:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Температура:</strong> 0-4°C</li>
                        <li><strong>pH:</strong> 5.3-6.2</li>
                        <li><strong>Вологість:</strong> 70-78%</li>
                    </ul>
                    
                    <h6 class="mt-3">Для спецій та добавок:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Чистота солі:</strong> ≥99%</li>
                        <li><strong>Вологість спецій:</strong> ≤12%</li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Шаблони параметрів</h5>
                </div>
                <div class="card-body">
                    <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="fillTemplate('temperature')">
                        Температура зберігання
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="fillTemplate('ph')">
                        Рівень pH
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="fillTemplate('moisture')">
                        Вологість
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="fillTemplate('purity')">
                        Чистота
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillTemplate(type) {
    const parameterName = document.getElementById('parameter_name');
    const minValue = document.getElementById('min_value');
    const maxValue = document.getElementById('max_value');
    const unit = document.getElementById('unit');
    const description = document.getElementById('description');
    const isCritical = document.getElementById('is_critical');
    
    switch(type) {
        case 'temperature':
            parameterName.value = 'Температура';
            minValue.value = '0';
            maxValue.value = '4';
            unit.value = '°C';
            description.value = 'Температура зберігання охолодженого м\'яса повинна підтримуватись в межах 0-4°C для забезпечення свіжості та безпеки продукту.';
            isCritical.checked = true;
            break;
            
        case 'ph':
            parameterName.value = 'pH';
            minValue.value = '5.3';
            maxValue.value = '6.2';
            unit.value = '';
            description.value = 'Рівень кислотності м\'яса. Нормальні значення pH для свіжого м\'яса знаходяться в межах 5.3-6.2.';
            isCritical.checked = true;
            break;
            
        case 'moisture':
            parameterName.value = 'Вологість';
            minValue.value = '70';
            maxValue.value = '78';
            unit.value = '%';
            description.value = 'Вміст вологи в м\'ясі. Нормальний рівень вологості для якісного м\'яса становить 70-78%.';
            isCritical.checked = false;
            break;
            
        case 'purity':
            parameterName.value = 'Чистота';
            minValue.value = '99';
            maxValue.value = '';
            unit.value = '%';
            description.value = 'Рівень чистоти для харчової солі та інших добавок. Мінімально допустимий рівень - 99%.';
            isCritical.checked = true;
            break;
    }
}

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
});
</script>