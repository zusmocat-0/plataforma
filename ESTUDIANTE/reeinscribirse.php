<?php
require_once '../fecha.php';

if (!verificarPeriodo('inscripcion')) {
    $periodo = obtenerPeriodoActivo('inscripcion');
    $dias_restantes = diasRestantes('inscripcion');
    $mensaje = "El periodo de inscripción está cerrado.";
    
    if ($periodo) {
        $inicio = date('d/m/Y', strtotime($periodo['fecha_inicio']));
        $fin = date('d/m/Y', strtotime($periodo['fecha_fin']));
        
        if ($dias_restantes > 0) {
            $mensaje = "El periodo de inscripción comienza el $inicio.";
        } else {
            $mensaje = "El periodo de inscripción finalizó el $fin.";
        }
    }
    
    die("<div class='alert alert-warning' style='margin: 20px;'>
            <i class='fas fa-calendar-times'></i> $mensaje
            <br><br>
            <a href='inicio_estudiante.php' class='btn btn-primary'>
                <i class='fas fa-arrow-left'></i> Volver al inicio
            </a>
        </div>");
}
?>
<?php
session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'alumno') {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

$alumno = $_SESSION['usuario'];
$error = '';
$success = '';

// Procesar el pago ficticio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['realizar_pago'])) {
    // Verificar que el alumno esté en estatus "pendiente"
    $query_estatus = "SELECT estatus, Semestre FROM alumno WHERE idAlumno = ?";
    $stmt = $conexion->prepare($query_estatus);
    $stmt->bind_param("s", $alumno['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['estatus'] != 'pendiente') {
            $error = "No puedes reinscribirte porque tu estatus actual es: " . htmlspecialchars($row['estatus']);
        } else {
            // Realizar la actualización
            try {
                $conexion->begin_transaction();
                
                // Actualizar estatus y semestre
                $nuevo_semestre = $row['Semestre'] + 1;
                $query_update = "UPDATE alumno SET estatus = 'activo', Semestre = ? WHERE idAlumno = ?";
                $stmt_update = $conexion->prepare($query_update);
                $stmt_update->bind_param("is", $nuevo_semestre, $alumno['id']);
                $stmt_update->execute();
                
                // Registrar el pago ficticio (podrías crear una tabla para esto si lo necesitas)
                // $query_pago = "INSERT INTO pagos (id_alumno, monto, concepto, fecha) VALUES (?, 0, 'Reinscripción semestral', NOW())";
                // $stmt_pago = $conexion->prepare($query_pago);
                // $stmt_pago->bind_param("s", $alumno['id']);
                // $stmt_pago->execute();
                
                $conexion->commit();
                
                // Actualizar los datos en la sesión
                $_SESSION['usuario']['semestre'] = $nuevo_semestre;
                $alumno = $_SESSION['usuario'];
                $success = "¡Reinscripción exitosa! Ahora estás inscrito en el semestre " . $nuevo_semestre;
            } catch (Exception $e) {
                $conexion->rollback();
                $error = "Error al procesar la reinscripción: " . $e->getMessage();
            }
        }
    } else {
        $error = "No se encontró el registro del alumno.";
    }
}

// Obtener información actual del alumno
$query_alumno = "SELECT estatus, Semestre FROM alumno WHERE idAlumno = ?";
$stmt = $conexion->prepare($query_alumno);
$stmt->bind_param("s", $alumno['id']);
$stmt->execute();
$result = $stmt->get_result();
$datos_alumno = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Reinscripción</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 30px auto;
            background-color: var(--secondary-color);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .payment-header h2 {
            color: var(--accent-color);
            margin-bottom: 10px;
        }
        
        .payment-details {
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .payment-methods {
            margin-bottom: 30px;
        }
        
        .method {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .method:hover {
            border-color: var(--accent-color);
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .method input {
            margin-right: 15px;
        }
        
        .method-icon {
            font-size: 24px;
            margin-right: 15px;
            color: var(--accent-color);
        }
        
        .method-info h4 {
            margin: 0 0 5px 0;
        }
        
        .method-info p {
            margin: 0;
            color: var(--dark-gray);
            font-size: 0.9em;
        }
        
        .btn-pay {
            width: 100%;
            padding: 15px;
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-pay:hover {
            background-color: #0056b3;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 6px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 6px solid #c3e6cb;
        }
        
        .status-info {
            background-color: #e2e3e5;
            color: #383d41;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="notification-icon">
        <i class="fas fa-bell" onclick="toggleNotifications()"></i>
    </div>
    
    <button class="theme-toggle" onclick="toggleTheme()" title="Cambiar modo claro/oscuro">
        <i class="fas fa-moon"></i>
    </button>
    
    <div id="notificationBox" class="notification-box">
    </div>
    
    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="inicio_estudiante.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="cursos_estudiante.php"><i class="fas fa-book"></i> <span>Cursos</span></a></li>
            <li><a href="calendario_estudiante.php"><i class="fas fa-calendar-alt"></i> <span>Calendario</span></a></li>
            <li><a href="avance_reticular.php"><i class="fas fa-tasks"></i> <span>Avance Reticular</span></a></li>
            <li><a href="foro_estudiante.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li class="active"><a href="reinscripcion.php"><i class="fas fa-clipboard-check"></i> <span>Reinscripción</span></a></li>
            <li><a href="servicios_estudiante.php"><i class="fas fa-school"></i> <span>Servicios Escolares</span></a></li>
            <li><a href="info_estudiante.php"><i class="fas fa-user"></i> <span>Información personal</span></a></li>
            <li><a href="\logout.php"><i class="fas fa-right-from-bracket"></i> <span>Cerrar sesión</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Proceso de Reinscripción</h1>
        <p>Completa tu reinscripción para el siguiente semestre</p>
    </div>
    
    <div class="main-content">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="payment-container">
            <div class="payment-header">
                <h2><i class="fas fa-clipboard-check"></i> Reinscripción Semestral</h2>
                <p>Proceso para activar tu cuenta y avanzar al siguiente semestre</p>
            </div>
            
            <?php if ($datos_alumno['estatus'] == 'pendiente'): ?>
                <div class="payment-details">
                    <h3>Detalles de la Reinscripción</h3>
                    <div class="detail-row">
                        <span>Alumno:</span>
                        <span><?php echo htmlspecialchars($alumno['nombre']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Matrícula:</span>
                        <span><?php echo htmlspecialchars($alumno['id']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Carrera:</span>
                        <span><?php echo htmlspecialchars($alumno['carrera']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Semestre Actual:</span>
                        <span><?php echo htmlspecialchars($datos_alumno['Semestre']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Nuevo Semestre:</span>
                        <span><?php echo htmlspecialchars($datos_alumno['Semestre'] + 1); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Costo de Reinscripción:</span>
                        <span>$0.00 (Simulación)</span>
                    </div>
                </div>
                
                <div class="payment-methods">
                    <h3>Método de Pago Simulado</h3>
                    <div class="method">
                        <input type="radio" id="credit-card" name="payment-method" checked>
                        <div class="method-icon">
                            <i class="far fa-credit-card"></i>
                        </div>
                        <div class="method-info">
                            <h4>Tarjeta de Crédito/Débito</h4>
                            <p>Pago con tarjeta (simulación)</p>
                        </div>
                    </div>
                    
                    <div class="method">
                        <input type="radio" id="bank-transfer" name="payment-method">
                        <div class="method-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="method-info">
                            <h4>Transferencia Bancaria</h4>
                            <p>Transferencia electrónica (simulación)</p>
                        </div>
                    </div>
                </div>
                
                <form method="POST">
                    <button type="submit" name="realizar_pago" class="btn-pay">
                        <i class="fas fa-check-circle"></i> Confirmar Reinscripción
                    </button>
                </form>
            <?php else: ?>
                <div class="status-info">
                    <h3><i class="fas fa-info-circle"></i> Estado de tu cuenta</h3>
                    <p>Tu estatus actual es: <strong><?php echo htmlspecialchars($datos_alumno['estatus']); ?></strong></p>
                    <p>Semestre actual: <strong><?php echo htmlspecialchars($datos_alumno['Semestre']); ?></strong></p>
                    
                    <?php if ($datos_alumno['estatus'] == 'activo'): ?>
                        <p>Ya estás inscrito y activo para el semestre actual.</p>
                    <?php else: ?>
                        <p>No cumples con los requisitos para reinscribirte en este momento.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/scripts.js"></script>
</body>
</html>