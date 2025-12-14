<?php
require_once __DIR__ . '/../config/database.php';

class Database {
    protected $pdo;
    
    public function __construct() {
        $this->pdo = DatabaseConfig::getConnection();
    }
    
    public function getConnection() {
        return $this->pdo;
    }
}
?>