<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=aistory;charset=utf8mb4", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$tables = $pdo->query("SHOW TABLES LIKE 'cache%'")->fetchAll(PDO::FETCH_COLUMN);
echo "Cache tables: " . (empty($tables) ? "NONE" : implode(", ", $tables)) . "\n";
