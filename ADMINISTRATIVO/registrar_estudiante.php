<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../conexion.php';

// Recoger datos del formulario
$idAlumno = $_POST['idAlumno'] ?? '';
$Nombre = $_POST['Nombre'] ?? '';
$Telefono = $_POST['Telefono'] ?? '';
$password = $_POST['password'] ?? '';
$FechaNacimiento = $_POST['FechaNacimiento'] ?? null;
$Sexo = $_POST['Sexo'] ?? '';
$Domicilio = $_POST['Domicilio'] ?? '';
$Curp = $_POST['Curp'] ?? '';
$CarreraId = $_POST['Carrera'] ?? '';
$Semestre = $_POST['Semestre'] ?? '';
$Cursos = isset($_POST['Cursos']) ? implode(',', $_POST['Cursos']) : null;

// Validar campos obligatorios (ajustados a los campos realmente requeridos)
if (empty($idAlumno) || empty($Nombre) || empty($password) || empty($CarreraId) || empty($Semestre)) {
    die("Error: Faltan campos obligatorios. Por favor complete todos los campos marcados como requeridos.");
}

// Obtener el nombre de la carrera usando el ID
$query = "SELECT Nombre FROM carrera WHERE idCarrera = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $CarreraId);
$stmt->execute();
$result = $stmt->get_result();
$carreraData = $result->fetch_assoc();

if (!$carreraData) {
    die("Error: La carrera seleccionada no existe.");
}

$nombreCarrera = $carreraData['Nombre'];

$conexion->begin_transaction();

try {
    // 1. Insertar en la tabla alumno
    $sql = "INSERT INTO alumno (
                idAlumno, 
                Nombre, 
                Telefono, 
                password, 
                FechaNacimiento, 
                Sexo, 
                Domicilio, 
                Curp, 
                Cursos, 
                Carrera, 
                Semestre
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    
    // Formatear fecha para MySQL (si existe)
    $fechaFormateada = !empty($FechaNacimiento) ? date('Y-m-d', strtotime($FechaNacimiento)) : null;
    
    // Vincular parámetros
    $stmt->bind_param(
        "sssssssssss",
        $idAlumno,
        $Nombre,
        $Telefono,
        $password,
        $fechaFormateada,
        $Sexo,
        $Domicilio,
        $Curp,
        $Cursos,
        $nombreCarrera, // Usamos el nombre de la carrera
        $Semestre
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al registrar alumno: " . $stmt->error);
    }
    
    // 2. Insertar en la tabla carrera_alumno usando el ID de carrera
    $sql_carrera_alumno = "INSERT INTO carrera_alumno (Carrera, alumno) VALUES (?, ?)";
    $stmt_ca = $conexion->prepare($sql_carrera_alumno);
    $stmt_ca->bind_param("ss", $nombreCarrera, $idAlumno);//cambiar por id carrera para que se registre el id en lugar del nombre
    
    if (!$stmt_ca->execute()) {
        throw new Exception("Error al registrar relación carrera-alumno: " . $stmt_ca->error);
    }
    
    $conexion->commit();
    header("Location: administrador_gestionar usuarios.php?success=1");
    exit();
} catch (Exception $e) {
    $conexion->rollback();
    header("Location: administrador_gestionar usuarios.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>