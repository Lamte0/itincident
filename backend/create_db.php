<?php
$conn = new mysqli('127.0.0.1', 'root', '');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$sql = 'CREATE DATABASE IF NOT EXISTS itincident';
if ($conn->query($sql) === TRUE) {
    echo 'Database itincident created successfully' . PHP_EOL;
} else {
    echo 'Error: ' . $conn->error . PHP_EOL;
}
$conn->close();
?>
