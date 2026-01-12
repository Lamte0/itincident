<?php
$conn = new mysqli('127.0.0.1', 'root', '', 'itincident');
if ($conn->connect_error) { 
    die('Connection failed: ' . $conn->connect_error); 
}

$result = $conn->query('SELECT id, name, email, role, is_active FROM users;');
if ($result) {
    echo '=== UTILISATEURS EN BASE DE DONNEES ===' . PHP_EOL;
    while ($row = $result->fetch_assoc()) {
        echo 'ID: ' . $row['id'] . ' | Name: ' . $row['name'] . ' | Email: ' . $row['email'] . ' | Role: ' . $row['role'] . ' | Active: ' . ($row['is_active'] ? 'OUI' : 'NON') . PHP_EOL;
    }
}
$conn->close();
?>
