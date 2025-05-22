<?php
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Помилка підключення до бази даних. Спробуйте пізніше.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Выполнение запроса с параметрами
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            return $result;
        } catch(PDOException $e) {
            error_log("Database query error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    // Получение одной записи
    public function single($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Database single error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    // Получение множества записей
    public function resultSet($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Database resultSet error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    // Получение количества строк
    public function rowCount($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Database rowCount error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }
    
    // Получение ID последней вставленной записи
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    // Начало транзакции
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    // Подтверждение транзакции
    public function commit() {
        return $this->pdo->commit();
    }
    
    // Откат транзакции
    public function rollBack() {
        return $this->pdo->rollBack();
    }
    
    // Проверка активной транзакции
    public function inTransaction() {
        return $this->pdo->inTransaction();
    }
    
    // Получение прямого доступа к PDO (осторожно использовать)
    public function getPDO() {
        return $this->pdo;
    }
    
    // Escape строки (для старых случаев, лучше использовать prepared statements)
    public function escape($value) {
        return $this->pdo->quote($value);
    }
    
    // Проверка подключения
    public function isConnected() {
        return $this->pdo !== null;
    }
    
    // Закрытие соединения
    public function close() {
        $this->pdo = null;
    }
    
    // Деструктор
    public function __destruct() {
        $this->close();
    }
    
    // Запрет клонирования
    private function __clone() {}
    
    // Запрет десериализации
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    // Проверка существования таблицы
    public function tableExists($tableName) {
        try {
            $sql = "SHOW TABLES LIKE ?";
            $result = $this->single($sql, [$tableName]);
            return $result !== false;
        } catch(PDOException $e) {
            error_log("Database tableExists error: " . $e->getMessage());
            return false;
        }
    }
    
    // Получение списка всех таблиц
    public function getAllTables() {
        try {
            $sql = "SHOW TABLES";
            return $this->resultSet($sql);
        } catch(PDOException $e) {
            error_log("Database getAllTables error: " . $e->getMessage());
            return [];
        }
    }
    
    // Проверка существования столбца в таблице
    public function columnExists($tableName, $columnName) {
        try {
            $sql = "SHOW COLUMNS FROM `$tableName` LIKE ?";
            $result = $this->single($sql, [$columnName]);
            return $result !== false;
        } catch(PDOException $e) {
            error_log("Database columnExists error: " . $e->getMessage());
            return false;
        }
    }
    
    // Получение структуры таблицы
    public function getTableStructure($tableName) {
        try {
            $sql = "DESCRIBE `$tableName`";
            return $this->resultSet($sql);
        } catch(PDOException $e) {
            error_log("Database getTableStructure error: " . $e->getMessage());
            return [];
        }
    }
    
    // Выполнение множественных запросов
    public function multiQuery($queries) {
        if (!is_array($queries)) {
            return false;
        }
        
        $this->beginTransaction();
        
        try {
            foreach ($queries as $sql => $params) {
                if (is_numeric($sql)) {
                    // Если ключ числовой, значит параметры не переданы
                    $this->query($params);
                } else {
                    // Если ключ строковый, это SQL с параметрами
                    $this->query($sql, $params);
                }
            }
            
            $this->commit();
            return true;
        } catch(PDOException $e) {
            $this->rollBack();
            error_log("Database multiQuery error: " . $e->getMessage());
            return false;
        }
    }
    
    // Получение информации о базе данных
    public function getDatabaseInfo() {
        try {
            $info = [];
            
            // Название базы данных
            $info['database_name'] = DB_NAME;
            
            // Версия MySQL
            $sql = "SELECT VERSION() as version";
            $result = $this->single($sql);
            $info['mysql_version'] = $result ? $result['version'] : 'Unknown';
            
            // Количество таблиц
            $tables = $this->getAllTables();
            $info['tables_count'] = count($tables);
            
            // Размер базы данных
            $sql = "SELECT 
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb 
                    FROM information_schema.tables 
                    WHERE table_schema = ?";
            $result = $this->single($sql, [DB_NAME]);
            $info['size_mb'] = $result ? $result['size_mb'] : 0;
            
            return $info;
        } catch(PDOException $e) {
            error_log("Database getDatabaseInfo error: " . $e->getMessage());
            return [];
        }
    }
    
    // Резервное копирование таблицы
    public function backupTable($tableName) {
        try {
            $backupTableName = $tableName . '_backup_' . date('Y_m_d_H_i_s');
            $sql = "CREATE TABLE `$backupTableName` AS SELECT * FROM `$tableName`";
            
            if ($this->query($sql)) {
                return $backupTableName;
            }
            
            return false;
        } catch(PDOException $e) {
            error_log("Database backupTable error: " . $e->getMessage());
            return false;
        }
    }
    
    // Очистка таблицы
    public function truncateTable($tableName) {
        try {
            $sql = "TRUNCATE TABLE `$tableName`";
            return $this->query($sql);
        } catch(PDOException $e) {
            error_log("Database truncateTable error: " . $e->getMessage());
            return false;
        }
    }
    
    // Проверка подключения к базе данных
    public function testConnection() {
        try {
            $sql = "SELECT 1";
            $result = $this->single($sql);
            return $result !== false;
        } catch(PDOException $e) {
            error_log("Database connection test failed: " . $e->getMessage());
            return false;
        }
    }
    
    // Получение последней ошибки
    public function getLastError() {
        if ($this->pdo) {
            $errorInfo = $this->pdo->errorInfo();
            return $errorInfo[2] ?? 'No error';
        }
        return 'No PDO connection';
    }
    
    // Экспорт данных в CSV
    public function exportToCSV($sql, $params = [], $filename = null) {
        try {
            if (!$filename) {
                $filename = 'export_' . date('Y_m_d_H_i_s') . '.csv';
            }
            
            $data = $this->resultSet($sql, $params);
            
            if (empty($data)) {
                return false;
            }
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // BOM для корректного отображения UTF-8 в Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Заголовки
            fputcsv($output, array_keys($data[0]));
            
            // Данные
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
        } catch(PDOException $e) {
            error_log("Database exportToCSV error: " . $e->getMessage());
            return false;
        }
    }
}