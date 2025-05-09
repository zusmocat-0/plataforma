<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../conexion.php';

// Recoger datos del formulario
$idDocente = $_POST['idDocente'] ?? '';
$Nombre = $_POST['Nombre'] ?? '';
$Telefono = $_POST['Telefono'] ?? '';
$password = $_POST['password'] ?? '';
$Domicilio = $_POST['Domicilio'] ?? '';
$Sexo = $_POST['Sexo'] ?? '';
$FechasNacimiento = $_POST['FechasNacimiento'] ?? null;
$Correo = $_POST['Correo'] ?? '';
$CursosArray = $_POST['Cursos'] ?? []; // Cambiamos el nombre para claridad

// Convertir array de cursos a string separado por comas
$Cursos = !empty($CursosArray) ? implode(',', $CursosArray) : null;

// Validar campos obligatorios
if (empty($idDocente) || empty($Nombre) || empty($password)) {
    die("Error: Faltan campos obligatorios");
}

try {
    // Preparar la consulta SQL (nota el nombre de columna con paréntesis)
    $sql = "INSERT INTO docente (
                `idDocente(RFC)`, 
                Nombre, 
                Telefono, 
                password, 
                Domicilio, 
                Sexo, 
                FechasNacimiento, 
                Cursos, 
                Correo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    
    // Formatear fecha para MySQL (si existe)
    $fechaFormateada = !empty($FechasNacimiento) ? date('Y-m-d H:i:s', strtotime($FechasNacimiento)) : null;
    
    // Vincular parámetros
    $stmt->bind_param(
        "sssssssss",
        $idDocente,
        $Nombre,
        $Telefono,
        $password, // Contraseña sin encriptar
        $Domicilio,
        $Sexo,
        $fechaFormateada,
        $Cursos, // Ahora es un string, no un array
        $Correo
    );
    
    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Redirigir con mensaje de éxito
        header("Location: administrador_gestionar_usuarios.php?success=1&tipo=docente");
        exit();
    } else {
        // Redirigir con mensaje de error
        header("Location: administrador_gestionar_usuarios.php?error=" . urlencode($stmt->error));
        exit();
    }
} catch (Exception $e) {
    // Redirigir con mensaje de error
    header("Location: administrador_gestionar_usuarios.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>