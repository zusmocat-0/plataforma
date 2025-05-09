<?php
session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario'])) {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Verificar que se envió el formulario
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_actividad'])) {
    header("Location: actividades_estudiante.php");
    exit();
}

$idActividad = $_POST['id_actividad'];
$idAlumno = $_SESSION['usuario']['id'];

// Verificar si se subió un archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    header("Location: entregar_actividad.php?id=$idActividad&error=Error al subir el archivo");
    exit();
}

// Validar el archivo
$archivo = $_FILES['archivo'];
$extensionesPermitidas = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'txt','jpg', 'png'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if (!in_array($extension, $extensionesPermitidas)) {
    header("Location: entregar_actividad.php?id=$idActividad&error=Tipo de archivo no permitido");
    exit();
}

if ($archivo['size'] > 10 * 1024 * 1024) { // 10MB máximo
    header("Location: entregar_actividad.php?id=$idActividad&error=El archivo es demasiado grande (máximo 10MB)");
    exit();
}

// Crear directorio de entregas si no existe
$directorioEntregas = '../entregas/';
if (!file_exists($directorioEntregas)) {
    mkdir($directorioEntregas, 0777, true);
}

// Generar nombre único para el archivo
$nombreArchivo = 'entrega_' . $idAlumno . '_' . $idActividad . '_' . time() . '.' . $extension;
$rutaArchivo = $directorioEntregas . $nombreArchivo;

// Mover el archivo subido
if (!move_uploaded_file($archivo['tmp_name'], $rutaArchivo)) {
    header("Location: entregar_actividad.php?id=$idActividad&error=Error al guardar el archivo");
    exit();
}

// Obtener datos actuales de la actividad
$query = "SELECT Entregas, archivo_entregas FROM actividades WHERE idActividades = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $idActividad);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    unlink($rutaArchivo); // Eliminar el archivo subido
    header("Location: actividades_estudiante.php?error=Actividad no encontrada");
    exit();
}

$actividad = $result->fetch_assoc();

// Procesar entregas existentes
$entregas = $actividad['Entregas'] ? explode(",", $actividad['Entregas']) : [];
$archivos = $actividad['archivo_entregas'] ? explode(",", $actividad['archivo_entregas']) : [];

// Verificar si el alumno ya entregó
$indice = array_search($idAlumno, $entregas);

if ($indice !== false) {
    // Eliminar archivo anterior si existe
    $archivoAnterior = $archivos[$indice];
    if (!empty($archivoAnterior) && file_exists($directorioEntregas . $archivoAnterior)) {
        unlink($directorioEntregas . $archivoAnterior);
    }
    // Actualizar archivo
    $archivos[$indice] = $nombreArchivo;
} else {
    // Agregar nueva entrega
    $entregas[] = $idAlumno;
    $archivos[] = $nombreArchivo;
}

// Actualizar en la base de datos
$entregasStr = implode(",", $entregas);
$archivosStr = implode(",", $archivos);

$updateQuery = "UPDATE actividades SET Entregas = ?, archivo_entregas = ? WHERE idActividades = ?";
$updateStmt = $conexion->prepare($updateQuery);
$updateStmt->bind_param("ssi", $entregasStr, $archivosStr, $idActividad);

if ($updateStmt->execute()) {
    header("Location: entregar_actividad.php?id=$idActividad&success=Entrega realizada con éxito");
} else {
    // En caso de error, eliminar el archivo subido
    unlink($rutaArchivo);
    header("Location: entregar_actividad.php?id=$idActividad&error=Error al registrar la entrega");
}
exit();
?>