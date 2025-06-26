<?php
/**
 * Ya podrás conectarte desde pgAdmin o cualquier cliente PostgreSQL online o local usando:
* Host: 31.97.135.236
* Puerto: 5432
* Usuario: postgres (por defecto)
* Contraseña: 282e9442a6bc1e1852b5d2b2445bc900 (la que pusiste)
 */
function getDBConnection() {
    $host = '31.97.135.236';  
    $db = 'financial_app'; 
    $user = 'postgres';  
    $pass = '282e9442a6bc1e1852b5d2b2445bc900';
    $port = '5432'; // Puerto por defecto de PostgreSQL

    try {
        // Cambiar mysql por pgsql
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;  // Termina la ejecución si la conexión falla
    }
}
