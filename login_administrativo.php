<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idAdmin = $_POST['idAdmin'];
    $password = $_POST['password'];

    // Preparar consulta usando el nombre exacto de la columna de la tabla administrativo
    $stmt = $conexion->prepare("SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ?");
    $stmt->bind_param("s", $idAdmin);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $admin = $resultado->fetch_assoc();
        
        // Comparación directa de contraseña (igual que en login_estudiante.php)
        if ($password === $admin['password']) {
            // Almacenar datos en sesión
            $_SESSION['idAdmin'] = $admin['idAdministrativo(RFC)'];
            $_SESSION['Nombre'] = $admin['Nombre'];
            $_SESSION['Correo'] = $admin['Correo'];
            $_SESSION['Telefono'] = $admin['Telefono'];
            
            // Redirigir al panel de administración
            header("Location: ADMINISTRATIVO/inicio_admin.php");
            exit();
        }
    }
    
    // Si las credenciales son incorrectas, redirigir con error
    header("Location: index.php?error=administrativo");
    exit();
}

$conexion->close();
?>