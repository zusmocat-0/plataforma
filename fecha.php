<?php
require_once 'conexion.php';

function verificarPeriodo($tipo) {
    global $conexion;
    
    $query = "SELECT fecha_inicio, fecha_fin FROM fechas WHERE tipo = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $tipo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false; // No hay fechas definidas para este tipo
    }
    
    $fecha = $result->fetch_assoc();
    $hoy = date('Y-m-d');
    $inicio = $fecha['fecha_inicio'];
    $fin = $fecha['fecha_fin'];
    
    return ($hoy >= $inicio && $hoy <= $fin);
}

function obtenerPeriodoActivo($tipo) {
    global $conexion;
    
    $query = "SELECT fecha_inicio, fecha_fin FROM fechas WHERE tipo = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $tipo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

function diasRestantes($tipo) {
    $periodo = obtenerPeriodoActivo($tipo);
    
    if (!$periodo) {
        return 0;
    }
    
    $hoy = new DateTime(date('Y-m-d'));
    $fin = new DateTime($periodo['fecha_fin']);
    
    if ($hoy > $fin) {
        return 0;
    }
    
    $diferencia = $hoy->diff($fin);
    return $diferencia->days;
}
?>