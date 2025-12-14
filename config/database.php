<?php

class DatabaseConfig {
    private const HOST = 'localhost';
    private const DB_NAME = 'gestion_evenements';
    private const USERNAME = 'root';
    private const PASSWORD = '';
    
    public static function getConnection() {
        try {
            $dsn = "mysql:host=" . self::HOST . ";dbname=" . self::DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, self::USERNAME, self::PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch(PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
}
?>