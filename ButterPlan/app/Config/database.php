<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    public static function getConnection() {
        // Configurações do WAMP (Padrão)
        $host = 'localhost';
        $db   = 'butterplan';
        $user = 'root';
        $pass = ''; // No WAMP a senha padrão é vazia
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        try {
            $pdo = new PDO($dsn, $user, $pass);
            
            // Configurações de erro e retorno
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            
            return $pdo;
        } catch (PDOException $e) {
            die("Erro de Conexão com MySQL: " . $e->getMessage());
        }
    }
}