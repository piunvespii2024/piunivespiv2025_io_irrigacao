<?php
// conexao.php (versão MySQLi)

$host = "localhost";
$user = "root";
$pass = "";
$db   = "irrigacao";

$conn = new mysqli($host, $user, $pass, $db);

// Verifica erro
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Define charset
$conn->set_charset("utf8mb4");
