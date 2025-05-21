<?php
class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die('Помилка підключення до бази даних: ' . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Выполнение запроса
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Util::log('Database error: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    // Получение одной записи
    public function single($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }
    
    // Получение множества записей
    public function resultSet($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }
    
    // Получение количества строк
    public function rowCount() {
        return $this->pdo->lastInsertId();
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
    
    // Проверка активности транзакции
    public function inTransaction() {
        return $this->pdo->inTransaction();
    }
    
    // Получение объекта PDO
    public function getPdo() {
        return $this->pdo;
    }
    
    // Закрытие соединения
    public function close() {
        $this->pdo = null;
    }
    
    // Деструктор
    public function __destruct() {
        $this->close();
    }
}