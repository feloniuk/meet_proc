<?php
// views/technologist/edit_quality_check.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i>Редагування перевірки якості #<?= $check['id'] ?></h1>
    </div>

    <div class="row">
        <!-- Информация о заказе -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Інформація про замовлення</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Замовлення:</th>
                            <td>#<?= $check['order_number'] ?></td>
                        </tr>
                        <tr>
                            <th>Постачальник:</th>
                            <td><?= htmlspecialchars($check['supplier_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Дата доставки:</th>
                            <td><?= $check['delivery_date'] ? date('d.m.Y', strtotime($check['delivery_date'])) : '-' ?></td>
                        </tr>
                        <tr>
                            <th>Загальна сума:</th>
                            <td><?= Util::formatMoney($check['total_amount']) ?></td>
                        </tr>
                        <tr>
                            <th>Поточний статус:</th>
                            <td>
                                <span class="badge bg-<?= 
                                    $check['status'] === 'pending' ? 'warning' : 
                                    ($check['status'] === 'approved' ? 'success' : 'danger') 
                                ?>">
                                    <?= Util::getQualityStatusName($check['status']) ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Позиции заказа -->
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Позиції замовлення</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Сировина</th>
                                    <th>Кількість</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['material_name']) ?></td>
                                        <td><?= number_format($item['quantity'], 2) ?> <?= $item['unit'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Стандарты качества -->
            <?php if (!empty($standards)): ?>
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Стандарти якості</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Параметр</th>
                                    <th>Норма</th>
                                    <th>Критичний</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($standards as $standard): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($standard['parameter_name']) ?></td>
                                        <td>
                                            <?php if ($standard['min_value'] && $standard['max_value']): ?>
                                                <?= $standard['min_value'] ?> - <?= $standard['max_value'] ?> <?= $standard['unit'] ?>
                                            <?php elseif ($standard['min_value']): ?>
                                                ≥ <?= $standard['min_value'] ?> <?= $standard['unit'] ?>
                                            <?php elseif ($standard['max_value']): ?>
                                                ≤ <?= $standard['max_value'] ?> <?= $standard['unit'] ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $standard['is_critical'] ? '<i class="fas fa-exclamation-triangle text-danger"></i>' : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Форма редактирования -->
        <div class="col-md-8">
            <form action="<?= BASE_URL ?>/technologist/editQualityCheck/<?= $check['id'] ?>" method="post">
                <!-- Физико-химические параметры -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Фізико-хімічні параметри</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="temperature">Температура (°C):</label>
                                <input type="number" step="0.1" min="-10" max="10" 
                                       class="form-control <?= isset($errors['temperature']) ? 'is-invalid' : '' ?>" 
                                       id="temperature" name="temperature" 
                                       value="<?= isset($_POST['temperature']) ? htmlspecialchars($_POST['temperature']) : $check['temperature'] ?>">
                                <small class="text-muted">Рекомендовано: 0-4°C</small>
                                <?php if (isset($errors['temperature'])): ?>
                                    <div class="invalid-feedback"><?= $errors['temperature'] ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="ph_level">Рівень pH:</label>
                                <input type="number" step="0.1" min="4" max="8" 
                                       class="form-control <?= isset($errors['ph_level']) ? 'is-invalid' : '' ?>" 
                                       id="ph_level" name="ph_level" 
                                       value="<?= isset($_POST['ph_level']) ? htmlspecialchars($_POST['ph_level']) : $check['ph_level'] ?>">
                                <small class="text-muted">Рекомендовано: 5.5-6.5</small>
                                <?php if (isset($errors['ph_level'])): ?>
                                    <div class="invalid-feedback"><?= $errors['ph_level'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="moisture_content">Вологість (%):</label>
                                <input type="number" step="0.1" min="0" max="100" 
                                       class="form-control <?= isset($errors['moisture_content']) ? 'is-invalid' : '' ?>" 
                                       id="moisture_content" name="moisture_content" 
                                       value="<?= isset($_POST['moisture_content']) ? htmlspecialchars($_POST['moisture_content']) : $check['moisture_content'] ?>">
                                <small class="text-muted">Для м'яса: 70-78%</small>
                                <?php if (isset($errors['moisture_content'])): ?>
                                    <div class="invalid-feedback"><?= $errors['moisture_content'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Органолептическая оценка -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Органолептична оцінка</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="visual_assessment">Візуальна оцінка:</label>
                                <select class="form-control" id="visual_assessment" name="visual_assessment">
                                    <option value="">Виберіть оцінку</option>
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?= $i ?>" 
                                               <?= (isset($_POST['visual_assessment']) ? $_POST['visual_assessment'] : $check['visual_assessment']) == $i ? 'selected' : '' ?>>
                                            <?= $i ?> - <?= $i == 5 ? 'Відмінно' : ($i == 4 ? 'Добре' : ($i == 3 ? 'Задовільно' : ($i == 2 ? 'Незадовільно' : 'Неприйнятно'))) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <small class="text-muted">Колір, текстура, наявність дефектів</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="smell_assessment">Оцінка запаху:</label>
                                <select class="form-control" id="smell_assessment" name="smell_assessment">
                                    <option value="">Виберіть оцінку</option>
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?= $i ?>" 
                                               <?= (isset($_POST['smell_assessment']) ? $_POST['smell_assessment'] : $check['smell_assessment']) == $i ? 'selected' : '' ?>>
                                            <?= $i ?> - <?= $i == 5 ? 'Відмінно' : ($i == 4 ? 'Добре' : ($i == 3 ? 'Задовільно' : ($i == 2 ? 'Незадовільно' : 'Неприйнятно'))) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <small class="text-muted">Свіжість, відсутність сторонніх запахів</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="texture_assessment">Оцінка текстури:</label>
                                <select class="form-control" id="texture_assessment" name="texture_assessment">
                                    <option value="">Виберіть оцінку</option>
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?= $i ?>" 
                                               <?= (isset($_POST['texture_assessment']) ? $_POST['texture_assessment'] : $check['texture_assessment']) == $i ? 'selected' : '' ?>>
                                            <?= $i ?> - <?= $i == 5 ? 'Відмінно' : ($i == 4 ? 'Добре' : ($i == 3 ? 'Задовільно' : ($i == 2 ? 'Незадовільно' : 'Неприйнятно'))) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <small class="text-muted">Консистенція, пружність</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Примечания -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Додаткова інформація</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="notes">Примітки технолога:</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                     placeholder="Детальні спостереження, рекомендації..."><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : $check['notes'] ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Решение технолога -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Рішення технолога</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="pending" value="pending" 
                                          <?= (isset($_POST['status']) ? $_POST['status'] : $check['status']) === 'pending' ? 'checked' : '' ?>>
                                    <label class="form-check-label text-warning" for="pending">
                                        <i class="fas fa-hourglass-half me-1"></i>
                                        <strong>ОЧІКУЄ</strong> - потрібні додаткові дослідження
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="approved" value="approved" 
                                          <?= (isset($_POST['status']) ? $_POST['status'] : $check['status']) === 'approved' ? 'checked' : '' ?>>
                                    <label class="form-check-label text-success" for="approved">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <strong>СХВАЛИТИ</strong> - сировина відповідає стандартам
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="rejected" value="rejected" 
                                          <?= (isset($_POST['status']) ? $_POST['status'] : $check['status']) === 'rejected' ? 'checked' : '' ?>>
                                    <label class="form-check-label text-danger" for="rejected">
                                        <i class="fas fa-times-circle me-1"></i>
                                        <strong>ВІДХИЛИТИ</strong> - сировина не відповідає стандартам
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="rejection_reason_block" style="display: none;">
                            <label for="rejection_reason">Причина відхилення (обов'язково):</label>
                            <textarea class="form-control <?= isset($errors['rejection_reason']) ? 'is-invalid' : '' ?>" 
                                     id="rejection_reason" name="rejection_reason" rows="3" 
                                     placeholder="Детально опишіть причини відхилення сировини..."><?= isset($_POST['rejection_reason']) ? htmlspecialchars($_POST['rejection_reason']) : $check['rejection_reason'] ?></textarea>
                            <?php if (isset($errors['rejection_reason'])): ?>
                                <div class="invalid-feedback"><?= $errors['rejection_reason'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-save me-1"></i>Зберегти зміни
                            </button>
                            <a href="<?= BASE_URL ?>/technologist/viewQualityCheck/<?= $check['id'] ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-eye me-1"></i>Переглянути
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pendingRadio = document.getElementById('pending');
    const approvedRadio = document.getElementById('approved');
    const rejectedRadio = document.getElementById('rejected');
    const rejectionReasonBlock = document.getElementById('rejection_reason_block');
    const rejectionReasonTextarea = document.getElementById('rejection_reason');
    
    // Показ/скрытие поля причины отклонения
    function toggleRejectionReason() {
        if (rejectedRadio.checked) {
            rejectionReasonBlock.style.display = 'block';
            rejectionReasonTextarea.required = true;
        } else {
            rejectionReasonBlock.style.display = 'none';
            rejectionReasonTextarea.required = false;
        }
    }
    
    // При загрузке страницы проверяем состояние
    toggleRejectionReason();
    
    pendingRadio.addEventListener('change', toggleRejectionReason);
    approvedRadio.addEventListener('change', toggleRejectionReason);
    rejectedRadio.addEventListener('change', toggleRejectionReason);
    
    // Автоматическая проверка стандартов
    const temperatureInput = document.getElementById('temperature');
    const phInput = document.getElementById('ph_level');
    const moistureInput = document.getElementById('moisture_content');
    
    function checkStandards() {
        const temp = parseFloat(temperatureInput.value);
        const ph = parseFloat(phInput.value);
        const moisture = parseFloat(moistureInput.value);
        
        // Проверка температуры
        if (temp && (temp < 0 || temp > 4)) {
            temperatureInput.style.borderColor = '#dc3545';
            temperatureInput.title = 'Температура виходить за рекомендовані межі (0-4°C)';
        } else {
            temperatureInput.style.borderColor = '';
            temperatureInput.title = '';
        }
        
        // Проверка pH
        if (ph && (ph < 5.5 || ph > 6.5)) {
            phInput.style.borderColor = '#dc3545';
            phInput.title = 'pH виходить за рекомендовані межі (5.5-6.5)';
        } else {
            phInput.style.borderColor = '';
            phInput.title = '';
        }
        
        // Проверка влажности для мяса
        if (moisture && (moisture < 70 || moisture > 78)) {
            moistureInput.style.borderColor = '#ffc107';
            moistureInput.title = 'Вологість може виходити за рекомендовані межі для м\'яса (70-78%)';
        } else {
            moistureInput.style.borderColor = '';
            moistureInput.title = '';
        }
    }
    
    temperatureInput.addEventListener('input', checkStandards);
    phInput.addEventListener('input', checkStandards);
    moistureInput.addEventListener('input', checkStandards);
    
    // Проверяем при загрузке
    checkStandards();
    
    // Предупреждение при низких оценках
    const visualGrade = document.getElementById('visual_assessment');
    const smellGrade = document.getElementById('smell_assessment');
    const textureGrade = document.getElementById('texture_assessment');
    
    function checkGrades() {
        const visual = parseInt(visualGrade.value);
        const smell = parseInt(smellGrade.value);
        const texture = parseInt(textureGrade.value);
        
        if ((visual && visual < 3) || (smell && smell < 3) || (texture && texture < 3)) {
            if (!rejectedRadio.checked) {
                // Можно показать предупреждение
                console.log('Увага! При низьких оцінках рекомендується відхилити сировину.');
            }
        }
    }
    
    visualGrade.addEventListener('change', checkGrades);
    smellGrade.addEventListener('change', checkGrades);
    textureGrade.addEventListener('change', checkGrades);
});
</script>