<?php 

// 1. Database Connection Parameters
$host     = '127.0.0.1';
$db       = 'erp_masters';
$user     = 'postgres';
$pass     = 'admin123';
$port     = '5432';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
?>
