<?php
/**
 * Класс для проверки целостности базы данных
 */
class DatabaseChecker {
    private $db;
    
    // Список обязательных таблиц
    private $requiredTables = [
        'users',
        'raw_materials', 
        'inventory',
        'recipes',
        'recipe_ingredients',
        'products',
        'orders',
        'order_items',
        'production_processes',
        'quality_standards',
        'quality_checks',
        'quality_check_items',
        'messages',
        'video_surveillance'
    ];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Проверка всех обязательных таблиц
     */
    public function checkAllTables() {
        $results = [];
        $allGood = true;
        
        foreach ($this->requiredTables as $table) {
            $exists = $this->db->tableExists($table);
            $results[$table] = [
                'exists' => $exists,
                'records' => $exists ? $this->getRecordCount($table) : 0,
                'structure' => $exists ? $this->checkTableStructure($table) : []
            ];
            
            if (!$exists) {
                $allGood = false;
            }
        }
        
        return [
            'all_tables_exist' => $allGood,
            'tables' => $results,
            'summary' => $this->generateSummary($results)
        ];
    }
    
    /**
     * Получение количества записей в таблице
     */
    private function getRecordCount($tableName) {
        try {
            $sql = "SELECT COUNT(*) as count FROM `$tableName`";
            $result = $this->db->single($sql);
            return $result ? (int)$result['count'] : 0;
        } catch (Exception $e) {
            return -1; // Ошибка при подсчете
        }
    }
    
    /**
     * Проверка структуры таблицы
     */
    private function checkTableStructure($tableName) {
        $expectedStructures = [
            'users' => ['id', 'username', 'password', 'role', 'name', 'email', 'phone', 'created_at'],
            'raw_materials' => ['id', 'name', 'description', 'unit', 'price_per_unit', 'min_stock', 'supplier_id', 'created_at'],
            'inventory' => ['id', 'raw_material_id', 'quantity', 'last_updated', 'warehouse_manager_id'],
            'orders' => ['id', 'supplier_id', 'ordered_by', 'status', 'created_at', 'updated_at', 'delivery_date', 'total_amount', 'notes', 'quality_check_required', 'quality_status'],
            'messages' => ['id', 'sender_id', 'receiver_id', 'subject', 'message', 'is_read', 'created_at']
        ];
        
        if (!isset($expectedStructures[$tableName])) {
            return ['status' => 'unknown', 'message' => 'Структура не проверяется'];
        }
        
        try {
            $actualStructure = $this->db->getTableStructure($tableName);
            $actualColumns = array_column($actualStructure, 'Field');
            $expectedColumns = $expectedStructures[$tableName];
            
            $missing = array_diff($expectedColumns, $actualColumns);
            $extra = array_diff($actualColumns, $expectedColumns);
            
            if (empty($missing) && empty($extra)) {
                return ['status' => 'ok', 'message' => 'Структура корректна'];
            } else {
                return [
                    'status' => 'warning',
                    'message' => 'Структура отличается от ожидаемой',
                    'missing' => $missing,
                    'extra' => $extra
                ];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Ошибка при проверке структуры: ' . $e->getMessage()];
        }
    }
    
    /**
     * Генерация сводки результатов
     */
    private function generateSummary($results) {
        $total = count($results);
        $existing = 0;
        $withData = 0;
        $totalRecords = 0;
        
        foreach ($results as $table => $info) {
            if ($info['exists']) {
                $existing++;
                if ($info['records'] > 0) {
                    $withData++;
                }
                $totalRecords += max(0, $info['records']);
            }
        }
        
        return [
            'total_tables' => $total,
            'existing_tables' => $existing,
            'tables_with_data' => $withData,
            'total_records' => $totalRecords,
            'percentage_complete' => round(($existing / $total) * 100, 1)
        ];
    }
    
    /**
     * Быстрая проверка подключения и основных таблиц
     */
    public function quickCheck() {
        try {
            // Проверка подключения
            if (!$this->db->testConnection()) {
                return [
                    'status' => 'error',
                    'message' => 'Не удается подключиться к базе данных',
                    'details' => $this->db->getLastError()
                ];
            }
            
            // Проверка основных таблиц
            $criticalTables = ['users', 'raw_materials', 'inventory', 'orders'];
            $missingTables = [];
            
            foreach ($criticalTables as $table) {
                if (!$this->db->tableExists($table)) {
                    $missingTables[] = $table;
                }
            }
            
            if (!empty($missingTables)) {
                return [
                    'status' => 'error',
                    'message' => 'Отсутствуют критические таблицы: ' . implode(', ', $missingTables),
                    'missing_tables' => $missingTables
                ];
            }
            
            // Проверка наличия данных в таблице users
            $userCount = $this->getRecordCount('users');
            if ($userCount === 0) {
                return [
                    'status' => 'warning',
                    'message' => 'База данных настроена, но нет пользователей. Запустите скрипт инициализации.',
                    'suggestion' => 'Выполните SQL скрипт для создания тестовых данных'
                ];
            }
            
            return [
                'status' => 'ok',
                'message' => 'База данных работает корректно',
                'user_count' => $userCount
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка при проверке базы данных: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Создание HTML отчета о состоянии БД
     */
    public function generateHTMLReport() {
        $checkResults = $this->checkAllTables();
        $quickCheck = $this->quickCheck();
        $dbInfo = $this->db->getDatabaseInfo();
        
        $html = '<!DOCTYPE html>';
        $html .= '<html><head><meta charset="utf-8"><title>Отчет о состоянии базы данных</title>';
        $html .= '<style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .status-ok { color: green; }
            .status-warning { color: orange; }
            .status-error { color: red; }
            table { border-collapse: collapse; width: 100%; margin: 10px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .summary { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0; }
        </style></head><body>';
        
        $html .= '<h1>Отчет о состоянии базы данных</h1>';
        $html .= '<p>Дата создания: ' . date('d.m.Y H:i:s') . '</p>';
        
        // Быстрая проверка
        $html .= '<div class="summary">';
        $html .= '<h2>Быстрая проверка</h2>';
        $statusClass = 'status-' . $quickCheck['status'];
        $html .= '<p class="' . $statusClass . '"><strong>Статус:</strong> ' . $quickCheck['message'] . '</p>';
        $html .= '</div>';
        
        // Информация о БД
        if (!empty($dbInfo)) {
            $html .= '<div class="summary">';
            $html .= '<h2>Информация о базе данных</h2>';
            $html .= '<p><strong>Название:</strong> ' . ($dbInfo['database_name'] ?? 'N/A') . '</p>';
            $html .= '<p><strong>Версия MySQL:</strong> ' . ($dbInfo['mysql_version'] ?? 'N/A') . '</p>';
            $html .= '<p><strong>Количество таблиц:</strong> ' . ($dbInfo['tables_count'] ?? 0) . '</p>';
            $html .= '<p><strong>Размер:</strong> ' . ($dbInfo['size_mb'] ?? 0) . ' МБ</p>';
            $html .= '</div>';
        }
        
        // Сводка
        $summary = $checkResults['summary'];
        $html .= '<div class="summary">';
        $html .= '<h2>Сводка</h2>';
        $html .= '<p><strong>Всего таблиц:</strong> ' . $summary['total_tables'] . '</p>';
        $html .= '<p><strong>Существующих таблиц:</strong> ' . $summary['existing_tables'] . '</p>';
        $html .= '<p><strong>Таблиц с данными:</strong> ' . $summary['tables_with_data'] . '</p>';
        $html .= '<p><strong>Общее количество записей:</strong> ' . $summary['total_records'] . '</p>';
        $html .= '<p><strong>Процент готовности:</strong> ' . $summary['percentage_complete'] . '%</p>';
        $html .= '</div>';
        
        // Детали по таблицам
        $html .= '<h2>Детали по таблицам</h2>';
        $html .= '<table>';
        $html .= '<tr><th>Таблица</th><th>Существует</th><th>Записей</th><th>Структура</th><th>Примечания</th></tr>';
        
        foreach ($checkResults['tables'] as $tableName => $info) {
            $html .= '<tr>';
            $html .= '<td>' . $tableName . '</td>';
            $html .= '<td>' . ($info['exists'] ? '✓' : '✗') . '</td>';
            $html .= '<td>' . ($info['records'] >= 0 ? $info['records'] : 'Ошибка') . '</td>';
            
            if (!empty($info['structure'])) {
                $structStatus = $info['structure']['status'];
                $structClass = 'status-' . $structStatus;
                $html .= '<td class="' . $structClass . '">' . ucfirst($structStatus) . '</td>';
                $html .= '<td>' . ($info['structure']['message'] ?? '') . '</td>';
            } else {
                $html .= '<td>-</td><td>-</td>';
            }
            
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Сохранение отчета в файл
     */
    public function saveReport($filename = null) {
        if (!$filename) {
            $filename = 'database_report_' . date('Y_m_d_H_i_s') . '.html';
        }
        
        $html = $this->generateHTMLReport();
        $filepath = BASE_PATH . '/logs/' . $filename;
        
        // Создаем папку logs если её нет
        $logDir = dirname($filepath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        if (file_put_contents($filepath, $html)) {
            return $filepath;
        }
        
        return false;
    }
}