<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit();
}

$usuario = $_SESSION['usuario'];

$pagina_actual = basename($_SERVER['PHP_SELF']);

if ($usuario['tipo'] == 'alumno' && strpos($pagina_actual, 'estudiante') === false) {
    header("Location: inicio_estudiante.php");
    exit();
}

?>