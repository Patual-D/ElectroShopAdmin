<!-- Se establece conexión con al base de datos -->

<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "tienda_electronica";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
