<?php
session_start();

require_once '../conexion.php';

// Verificar sesión y privilegios
if (!isset($_SESSION['idAdmin']) || !isset($_SESSION['privileged_auth']) || !$_SESSION['privileged_auth']) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

// Verificar que se recibió el ID
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit();
}

$id = intval($_POST['id']);
$adminId = $_SESSION['idAdmin'];

// Verificar nuevamente la contraseña para operaciones sensibles
if (isset($_POST['password'])) {
    $password = $_POST['password'];
    
    $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ? AND password = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ss", $adminId, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
        exit();
    }
}

// Eliminar el día inhábil
$query = "DELETE FROM dias_inhabiles WHERE id = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $conexion->error]);
}

$stmt->close();
$conexion->close();
?>