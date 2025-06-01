<?php
// views/warehouse/discrepancy_report.php
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-balance-scale me-2"></i>
                        Звіт про розбіжності в інвентаризації
                    </h5>
                    <div>
                        <button class="btn btn-primary btn-sm" onclick="window.print()">
                            <i class="fas fa-print"></i> Друкувати
                        </button>
                        <a href="<?php echo BASE_URL; ?>/warehouse/inventory" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Назад
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="text-muted">
                            Дата формування: <?php echo date('d.m.Y H:i'); ?><br>
                            Відповідальний: <?php echo Auth::getCurrentUserName(); ?>
                        </p>
                    </div>

                    <?php if (empty($discrepancies)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Розбіжностей не виявлено. Всі дані інвентаризації збігаються!
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Виявлено <strong><?php echo count($discrepancies); ?></strong> позицій з розбіжностями
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Назва сировини</th>
                                        <th>Штрих-код</th>
                                        <th>Планова кількість</th>
                                        <th>Фактична кількість</th>
                                        <th>Розбіжність</th>
                                        <th>Одиниці</th>
                                        <th>Тип розбіжності</th>
                                        <th>Останнє оновлення</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalSurplus = 0;
                                    $totalShortage = 0;
                                    
                                    foreach ($discrepancies as $index => $item): 
                                        $actualQty = $item['actual_quantity'] ?? 0;
                                        $difference = $actualQty - $item['quantity'];
                                        
                                        if ($difference > 0) {
                                            $totalSurplus += $difference;
                                        } else {
                                            $totalShortage += abs($difference);
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($item['material_name']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo $item['barcode'] ?: 'Немає'; ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <?php echo number_format($item['quantity'], 2); ?>
                                            </td>
                                            <td class="text-end">
                                                <?php echo number_format($actualQty, 2); ?>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge <?php echo $difference >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo ($difference >= 0 ? '+' : '') . number_format($difference, 2); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                            <td>
                                                <?php 
                                                $typeMap = [
                                                    'surplus' => '<span class="badge bg-success">Надлишок</span>',
                                                    'shortage' => '<span class="badge bg-danger">Нестача</span>',
                                                    'match' => '<span class="badge bg-secondary">Збіг</span>'
                                                ];
                                                echo $typeMap[$item['discrepancy_type']] ?? '';
                                                ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('d.m.Y H:i', strtotime($item['last_updated'])); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Підсумки -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-success">Надлишки</h5>
                                        <h3 class="text-success"><?php echo number_format($totalSurplus, 2); ?></h3>
                                        <p class="text-muted">позицій</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-danger">Нестачі</h5>
                                        <h3 class="text-danger"><?php echo number_format($totalShortage, 2); ?></h3>
                                        <p class="text-muted">позицій</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-warning">
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-warning">Всього розбіжностей</h5>
                                        <h3 class="text-warning"><?php echo count($discrepancies); ?></h3>
                                        <p class="text-muted">позицій</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h6>Рекомендації:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Провести повторну інвентаризацію для позицій з великими розбіжностями</li>
                                <li><i class="fas fa-check text-success"></i> Перевірити правильність введення даних</li>
                                <li><i class="fas fa-check text-success"></i> Встановити причини виникнення розбіжностей</li>
                                <li><i class="fas fa-check text-success"></i> Оновити планові показники на основі фактичних даних</li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 pt-3 border-top">
                        <div class="row">
                            <div class="col-6">
                                <p>Начальник складу: ___________________</p>
                                <small class="text-muted"><?php echo Auth::getCurrentUserName(); ?></small>
                            </div>
                            <div class="col-6 text-end">
                                <p>Дата: <?php echo date('d.m.Y'); ?></p>
                                <p>Підпис: ___________________</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .no-print {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .container-fluid {
        width: 100% !important;
        max-width: none !important;
        padding: 0 !important;
    }
}</style>