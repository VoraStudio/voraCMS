<?php
try {
    $pdo = new PDO('sqlite:C:/xampp/htdocs/VoraStudio/voracms/var/voracms.db');
    $stmt = $pdo->query('SELECT id, email, roles, client_id FROM users');
    foreach ($stmt as $row) { echo json_encode($row) . PHP_EOL; }
} catch (Exception $e) { echo 'Error: ' . $e->getMessage(); }
