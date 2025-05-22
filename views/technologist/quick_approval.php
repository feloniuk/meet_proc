<?php
// views/technologist/quick_approval.php
?>
<div class="container-fluid">
    <div class="d-flex align-items-center mb-4">
        <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="btn btn-outline-primary me-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h3 mb-0"><i class="fas fa-microscope me-2"></i>Швидка перевірка якості - Замовлення #<?= $check['order_id'] ?></h1>
    </div>

    <div class="row">
        <!-- Інформація про замовлення -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Інформація про замовлення</h5>
                </div>
                <div class="card-body">
                    <p><strong>Замовлення:</strong> #<?= $check['order_id'] ?></p>
                    <p><strong>Постачальник:</strong> <?= htmlspecialchars($check['supplier_name']) ?></p>
                    <p><strong>Дата доставки:</strong> <?= $check['delivery_date'] ? date('d.m.Y', strtotime($check['delivery_date'])) : '-' ?></p>
                    <p><strong>Загальна сума:</strong> <?= Util::formatMoney($check['total_amount']) ?></p>
                </div>
            </div>

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
        </div>

        <!-- Форма перевірки -->
        <div class="col-md-8">
            <form action="<?= BASE_URL ?>/technologist/quickApproval/<?= $check['id'] ?>" method="post">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Параметри якості</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="temperature">Температура (°C):</label>
                                <input type="number" step="0.1" min="-10" max="10" 
                                       class="form-control <?= isset($errors['temperature']) ? 'is-invalid' : '' ?>" 
                                       id="temperature" name="temperature" 
                                       value="<?= isset($_POST['temperature']) ? htmlspecialchars($_POST['temperature']) : '' ?>">
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
                                       value="<?= isset($_POST['ph_level']) ? htmlspecialchars($_POST['ph_level']) : '' ?>">
                                <small class="text-muted">Рекомендовано: 5.5-6.5</small>
                                <?php if (isset($errors['ph_level'])): ?>
                                    <div class="invalid-feedback"><?= $errors['ph_level'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="visual_grade">Візуальна оцінка:</label>
                                <select class="form-control" id="visual_grade" name="visual_grade">
                                    <option value="">Виберіть оцінку</option>
                                    <option value="5" <?= isset($_POST['visual_grade']) && $_POST['visual_grade'] == '5' ? 'selected' : '' ?>>5 - Відмінно</option>
                                    <option value="4" <?= isset($_POST['visual_grade']) && $_POST['visual_grade'] == '4' ? 'selected' : '' ?>>4 - Добре</option>
                                    <option value="3" <?= isset($_POST['visual_grade']) && $_POST['visual_grade'] == '3' ? 'selected' : '' ?>>3 - Задовільно</option>
                                    <option value="2" <?= isset($_POST['visual_grade']) && $_POST['visual_grade'] == '2' ? 'selected' : '' ?>>2 - Незадовільно</option>
                                    <option value="1" <?= isset($_POST['visual_grade']) && $_POST['visual_grade'] == '1' ? 'selected' : '' ?>>1 - Неприйнятно</option>
                                </select>
                                <small class="text-muted">Колір, текстура, наявність дефектів</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="smell_grade">Оцінка запаху:</label>
                                <select class="form-control" id="smell_grade" name="smell_grade">
                                    <option value="">Виберіть оцінку</option>
                                    <option value="5" <?= isset($_POST['smell_grade']) && $_POST['smell_grade'] == '5' ? 'selected' : '' ?>>5 - Відмінно</option>
                                    <option value="4" <?= isset($_POST['smell_grade']) && $_POST['smell_grade'] == '4' ? 'selected' : '' ?>>4 - Добре</option>
                                    <option value="3" <?= isset($_POST['smell_grade']) && $_POST['smell_grade'] == '3' ? 'selected' : '' ?>>3 - Задовільно</option>
                                    <option value="2" <?= isset($_POST['smell_grade']) && $_POST['smell_grade'] == '2' ? 'selected' : '' ?>>2 - Незадовільно</option>
                                    <option value="1" <?= isset($_POST['smell_grade']) && $_POST['smell_grade'] == '1' ? 'selected' : '' ?>>1 - Неприйнятно</option>
                                </select>
                                <small class="text-muted">Свіжість, відсутність сторонніх запахів</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes">Додаткові примітки:</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Стандарти якості -->
                <?php if (!empty($standards)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Стандарти якості</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Матеріал</th>
                                        <th>Параметр</th>
                                        <th>Норма</th>
                                        <th>Критичний</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($standards as $standard): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($standard['material_name']) ?></td>
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
                                                <?= $standard['is_critical'] ? '<span class="badge bg-danger">Так</span>' : '<span class="badge bg-secondary">Ні</span>' ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Рішення -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Рішення технолога</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="action" id="approve" value="approve" required>
                                    <label class="form-check-label text-success" for="approve">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <strong>СХВАЛИТИ</strong> - сировина відповідає стандартам
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="action" id="reject" value="reject" required>
                                    <label class="form-check-label text-danger" for="reject">
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
                                     placeholder="Детально опишіть причини відхилення сировини..."><?= isset($_POST['rejection_reason']) ? htmlspecialchars($_POST['rejection_reason']) : '' ?></textarea>
                            <?php if (isset($errors['rejection_reason'])): ?>
                                <div class="invalid-feedback"><?= $errors['rejection_reason'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-check me-1"></i>Підтвердити рішення
                            </button>
                            <a href="<?= BASE_URL ?>/technologist/qualityChecks" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-1"></i>Скасувати
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
    const approveRadio = document.getElementById('approve');
    const rejectRadio = document.getElementById('reject');
    const rejectionReasonBlock = document.getElementById('rejection_reason_block');
    const rejectionReasonTextarea = document.getElementById('rejection_reason');
    
    // Показ/скрытие поля причины отклонения
    function toggleRejectionReason() {
        if (rejectRadio.checked) {
            rejectionReasonBlock.style.display = 'block';
            rejectionReasonTextarea.required = true;
        } else {
            rejectionReasonBlock.style.display = 'none';
            rejectionReasonTextarea.required = false;
        }
    }
    
    approveRadio.addEventListener('change', toggleRejectionReason);
    rejectRadio.addEventListener('change', toggleRejectionReason);
    
    // Автоматическая проверка стандартов
    const temperatureInput = document.getElementById('temperature');
    const phInput = document.getElementById('ph_level');
    
    function checkStandards() {
        const temp = parseFloat(temperatureInput.value);
        const ph = parseFloat(phInput.value);
        
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
    }
    
    temperatureInput.addEventListener('input', checkStandards);
    phInput.addEventListener('input', checkStandards);
    
    // Предупреждение при низких оценках
    const visualGrade = document.getElementById('visual_grade');
    const smellGrade = document.getElementById('smell_grade');
    
    function checkGrades() {
        const visual = parseInt(visualGrade.value);
        const smell = parseInt(smellGrade.value);
        
        if ((visual && visual < 3) || (smell && smell < 3)) {
            if (!rejectRadio.checked) {
                alert('Увага! При низьких оцінках рекомендується відхилити сировину.');
            }
        }
    }
    
    visualGrade.addEventListener('change', checkGrades);
    smellGrade.addEventListener('change', checkGrades);
});
</script>