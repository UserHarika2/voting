<?php
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'votesphere_db';
    $user = 'root';
    $pass = '@Harika$$1305'; // set your MySQL password
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die(json_encode(['success'=>false,'message'=>'DB connection failed']));
    }
}
?>