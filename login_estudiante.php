<?php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idAlumno = $_POST['idAlumno'];
    $password = $_POST['password'];

    $stmt = $conexion->prepare("SELECT * FROM alumno WHERE idAlumno = ?");
    $stmt->bind_param("s", $idAlumno);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $alumno = $resultado->fetch_assoc();
        
        if ($password === $alumno['password']) {
            // Guardar TODOS los datos importantes en sesión
            $_SESSION['usuario'] = [
                'id' => $alumno['idAlumno'],
                'nombre' => $alumno['Nombre'],
                'carrera' => $alumno['Carrera'],
                'semestre' => $alumno['Semestre'],
                'tipo' => 'alumno'
            ];
            
            header("Location: ESTUDIANTE/inicio_estudiante.php");
            exit();
        }
    }
    
    header("Location: index.php?error=estudiante");
    exit();
}

$conexion->close();
?>