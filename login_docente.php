<?php
session_start();
include 'conexion.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idDocente = $_POST['idDocente'];
    $password = $_POST['password'];

    $stmt = $conexion->prepare("SELECT * FROM docente WHERE `idDocente(RFC)` = ?");
    $stmt->bind_param("s", $idDocente);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $docente = $resultado->fetch_assoc();
        
        // Verificar contraseña (ajusta según cómo estén almacenadas)
        if ($password === $docente['password']) { // Cambia esto por password_verify si usas hash
            $_SESSION['idDocente'] = $docente['idDocente(RFC)'];
            $_SESSION['Nombre'] = $docente['Nombre'];
            $_SESSION['Correo'] = $docente['Correo'];
            
            header("Location: /DOCENTE/docente_inicio.php");
            exit();
        }
    }
    
    header("Location: index.php?error=docente");
    exit();
}

$conexion->close();
?>