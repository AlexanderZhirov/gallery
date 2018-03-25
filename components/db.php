<?php

class db {
    
    public static function getConnection()
    {
        $paramsPath = ROOT . '/config/db_params.php';
        $params = include($paramsPath);
        
        try {
            $dsn = "mysql:host={$params['host']};dbname={$params['dbname']}";
            $pdo = new PDO($dsn, $params['user'], $params['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('SET NAMES "utf8"');
        } catch (PDOException $e) {
            $error = 'Невозможно подключиться к серверу баз данных: ' . $e->getMessage();
            HelpLibrary::write_log('database.log', $error);
            exit();
        }
        
        return $pdo;
        
    }
    
}
