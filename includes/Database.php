<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $conn;
    private $error;
    private static $instance;
    
    // Конструктор - приватний для Singleton шаблону
    private function __construct() {
        // Створення підключення
        try {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
            $options = array(
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            );
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            echo 'Помилка підключення до бази даних: ' . $this->error;
        }
    }
    
    // Метод для отримання єдиного екземпляра класу
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Метод для виконання запиту
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            echo 'Помилка виконання запиту: ' . $this->error;
            return false;
        }
    }
    
    // Метод для отримання одного рядка
    public function single($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return false;
    }
    
    // Метод для отримання всіх рядків
    public function resultSet($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return false;
    }
    
    // Метод для отримання кількості рядків
    public function rowCount($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->rowCount();
        }
        return false;
    }
    
    // Метод для отримання останнього вставленого ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    // Метод для початку транзакції
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    // Метод для завершення транзакції
    public function commit() {
        return $this->conn->commit();
    }
    
    // Метод для скасування транзакції
    public function rollBack() {
        return $this->conn->rollBack();
    }
}