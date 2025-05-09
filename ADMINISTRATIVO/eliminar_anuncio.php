<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit();
}

require_once('../conexion.php');

if (!$conexion) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit();
}

if (isset($_POST['idAnuncio']) && is_numeric($_POST['idAnuncio'])) {
    $idAnuncio = $_POST['idAnuncio'];

    // Preparar la consulta para eliminar el anuncio
    $stmt = $conexion->prepare("DELETE FROM anuncios WHERE idAnuncio = ?");
    $stmt->bind_param("i", $idAnuncio);

    if ($stmt->execute()) {
        // Verificar si se eliminó alguna fila
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Anuncio eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el anuncio con el ID proporcionado']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el anuncio: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'ID de anuncio no válido']);
}

$conexion->close();
?>