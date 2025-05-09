<?php
session_start();

require_once '../conexion.php';

// 1. Verificar sesión de administrador
if (!isset($_SESSION['idAdmin'])) {
    header("Location: ../index.php");
    exit();
}

// 2. Verificar si el usuario es privilegiado
$adminId = $_SESSION['idAdmin'];
$query = "SELECT tipo FROM administrativo WHERE `idAdministrativo(RFC)` = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("s", $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../index.php");
    exit();
}

$adminData = $result->fetch_assoc();
$esPrivilegiado = ($adminData['tipo'] === 'privilegiado');

// 3. Si no es privilegiado, mostrar mensaje de acceso denegado
if (!$esPrivilegiado) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Denegado</title>
        <link rel="stylesheet" href="/styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            .denied-container {
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                background-color: var(--background-color);
                text-align: center;
            }
            .denied-box {
                background-color: var(--secondary-color);
                padding: 2rem;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 400px;
            }
            .denied-box h2 {
                color: #e74c3c;
                margin-top: 0;
            }
            .denied-box p {
                margin-bottom: 1.5rem;
            }
            .btn-back {
                display: inline-block;
                background-color: var(--accent-color);
                color: white;
                padding: 0.75rem 1.5rem;
                text-decoration: none;
                border-radius: 4px;
                transition: background-color 0.3s;
            }
            .btn-back:hover {
                background-color: #2980b9;
            }
            .denied-icon {
                font-size: 3rem;
                color: #e74c3c;
                margin-bottom: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="denied-container">
            <div class="denied-box">
                <div class="denied-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <h2>Acceso Denegado</h2>
                <p>No tienes los privilegios necesarios para acceder a esta sección.</p>
                <a href="/ADMINISTRATIVO/inicio_admin.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Regresar
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
function generarHistorialHTML($alumno, $periodo, $conexion) {
    $periodoNombre = str_replace('-', '_', $periodo);
    $directorio = "../uploads/historial_academico/$periodoNombre/";
    
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $nombreArchivo = $alumno['idAlumno'] . "_" . $periodoNombre . ".html";
    $rutaArchivo = $directorio . $nombreArchivo;
    // 1. Obtener materias donde el alumno tiene actividades calificadas
    $queryMateriasActivas = "SELECT DISTINCT a.idcurso 
                           FROM actividades a 
                           WHERE (a.Entregas LIKE ? OR a.calificacion LIKE ?)
                           AND a.idcurso IS NOT NULL";
    $stmtMateriasActivas = $conexion->prepare($queryMateriasActivas);
    $alumnoPattern = '%' . $alumno['idAlumno'] . '%';
    $stmtMateriasActivas->bind_param("ss", $alumnoPattern, $alumnoPattern);
    $stmtMateriasActivas->execute();
    $resultMateriasActivas = $stmtMateriasActivas->get_result();
    
    $materiasInscritas = [];
    while ($materia = $resultMateriasActivas->fetch_assoc()) {
        if (!empty($materia['idcurso'])) {
            $materiasInscritas[] = $materia['idcurso'];
        }
    }

    // 2. Si no hay actividades, verificar cursos directos del alumno
    if (empty($materiasInscritas)) {
        $queryCursos = "SELECT idMateria FROM curso WHERE idAlumno = ?";
        $stmtCursos = $conexion->prepare($queryCursos);
        $stmtCursos->bind_param("s", $alumno['idAlumno']);
        $stmtCursos->execute();
        $resultCursos = $stmtCursos->get_result();
        
        while ($curso = $resultCursos->fetch_assoc()) {
            if (!empty($curso['idMateria'])) {
                $materiasInscritas[] = $curso['idMateria'];
            }
        }
    }

    // 3. Si aún no hay, obtener materias de la carrera del alumno
    if (empty($materiasInscritas)) {
        $queryMateriasCarrera = "SELECT cm.Materia 
                               FROM carrera_materia cm
                               JOIN carrera_alumno ca ON cm.Carrera = ca.Carrera
                               WHERE ca.alumno = ?";
        $stmtMaterias = $conexion->prepare($queryMateriasCarrera);
        $stmtMaterias->bind_param("s", $alumno['idAlumno']);
        $stmtMaterias->execute();
        $resultMaterias = $stmtMaterias->get_result();
        
        while ($materia = $resultMaterias->fetch_assoc()) {
            if (!empty($materia['Materia'])) {
                $materiasInscritas[] = $materia['Materia'];
            }
        }
    }

    // Si no hay materias, mostrar mensaje
    if (empty($materiasInscritas)) {
        $html = generarHTMLBasico($alumno, $periodo, false);
        file_put_contents($rutaArchivo, $html);
        return $rutaArchivo;
    }

    // Obtener detalles de las materias
    $materiasInfo = obtenerDetallesMaterias($materiasInscritas, $conexion);
    
    // Generar HTML
    $html = generarHTMLBasico($alumno, $periodo, true);
    $materiasConContenido = 0;
    
    foreach ($materiasInfo as $materia) {
        $contenidoMateria = generarContenidoMateria($materia, $alumno, $conexion);
        
        if (!empty($contenidoMateria)) {
            $html .= $contenidoMateria;
            $materiasConContenido++;
        }
    }
    
    if ($materiasConContenido === 0) {
        $html = generarHTMLBasico($alumno, $periodo, false);
    } else {
        $html .= '</body></html>';
    }
    
    file_put_contents($rutaArchivo, $html);
    return $rutaArchivo;
}

// Funciones auxiliares
function generarHTMLBasico($alumno, $periodo, $conContenido) {
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Historial Académico - ' . $alumno['Nombre'] . '</title>
        <style>
            body { font-family: Arial; margin: 20px; }
            h1 { color: #333; text-align: center; }
            .header-info { margin-bottom: 20px; }
            .materia-container { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
            .materia-header { background-color: #f5f5f5; padding: 10px; border-radius: 3px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .calificacion { font-weight: bold; }
            .aprobado { color: #28a745; }
            .reprobado { color: #dc3545; }
            .promedio-final { font-weight: bold; margin-top: 10px; }
            .no-courses { text-align: center; margin-top: 50px; color: #666; }
        </style>
    </head>
    <body>
        <h1>Historial Académico</h1>
        <div class="header-info">
            <p><strong>Alumno:</strong> ' . $alumno['Nombre'] . '</p>
            <p><strong>Número de Control:</strong> ' . $alumno['idAlumno'] . '</p>
            <p><strong>Carrera:</strong> ' . $alumno['Carrera'] . '</p>
            <p><strong>Periodo:</strong> ' . $periodo . '</p>
        </div>';
    
    if (!$conContenido) {
        $html .= '<div class="no-courses">
                <p>No se encontraron materias con actividades calificadas para este alumno.</p>
            </div>
        </body>
        </html>';
    }
    
    return $html;
}

function obtenerDetallesMaterias($materiasIds, $conexion) {
    if (empty($materiasIds)) return [];
    
    $placeholders = implode(',', array_fill(0, count($materiasIds), '?'));
    $query = "SELECT idMateria, Nombre, Descripcionmateria, NumeroUnidades, Unidades 
              FROM materia 
              WHERE idMateria IN ($placeholders)";
    $stmt = $conexion->prepare($query);
    
    $types = str_repeat('s', count($materiasIds));
    $stmt->bind_param($types, ...$materiasIds);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $materias = [];
    while ($row = $result->fetch_assoc()) {
        $materias[] = $row;
    }
    
    return $materias;
}

function generarContenidoMateria($materia, $alumno, $conexion) {
    $unidades = json_decode($materia['Unidades'], true) ?? [];
    $contenido = '';
    
    // Obtener actividades de la materia
    $queryActividades = "SELECT unidad, TituloActividades, calificacion, 
                        Entregas, calificacion_maxima
                        FROM actividades
                        WHERE idcurso = ?";
    $stmtActividades = $conexion->prepare($queryActividades);
    $stmtActividades->bind_param("s", $materia['idMateria']);
    $stmtActividades->execute();
    $resultActividades = $stmtActividades->get_result();
    
    $calificacionesUnidades = [];
    $totalPuntos = 0;
    $totalMaximos = 0;
    $actividadesTotales = 0;
    
    while ($actividad = $resultActividades->fetch_assoc()) {
        $unidad = $actividad['unidad'];
        if (!isset($calificacionesUnidades[$unidad])) {
            $calificacionesUnidades[$unidad] = [
                'nombre' => $unidades[$unidad-1]['nombre'] ?? "Unidad $unidad",
                'actividades' => 0,
                'entregadas' => 0,
                'puntos' => 0,
                'maximos' => 0
            ];
        }
        
        $calificacionesUnidades[$unidad]['actividades']++;
        $actividadesTotales++;
        
        // Verificar entregas del alumno
        $entregas = $actividad['Entregas'] ? explode(',', $actividad['Entregas']) : [];
        $calificaciones = $actividad['calificacion'] ? explode(',', $actividad['calificacion']) : [];
        $indice = array_search($alumno['idAlumno'], $entregas);
        
        if ($indice !== false) {
            $calificacionesUnidades[$unidad]['entregadas']++;
            
            if (isset($calificaciones[$indice]) && is_numeric($calificaciones[$indice])) {
                $puntos = $calificaciones[$indice];
                $maximos = $actividad['calificacion_maxima'];
                
                $calificacionesUnidades[$unidad]['puntos'] += $puntos;
                $calificacionesUnidades[$unidad]['maximos'] += $maximos;
                
                $totalPuntos += $puntos;
                $totalMaximos += $maximos;
            }
        }
    }
    
    // Solo mostrar si hay actividades
    if ($actividadesTotales > 0) {
        $contenido .= '<div class="materia-container">
                     <div class="materia-header">
                         <h2>' . $materia['Nombre'] . '</h2>
                         <p>' . $materia['Descripcionmateria'] . '</p>
                     </div>';
        
        foreach ($calificacionesUnidades as $unidadNum => $unidad) {
            $contenido .= '<h3>' . $unidad['nombre'] . '</h3>
                         <table>
                             <tr>
                                 <th>Actividades</th>
                                 <th>Entregadas</th>
                                 <th>Puntos Obtenidos</th>
                                 <th>Puntos Máximos</th>
                                 <th>Calificación</th>
                             </tr>
                             <tr>
                                 <td>' . $unidad['actividades'] . '</td>
                                 <td>' . $unidad['entregadas'] . '</td>
                                 <td>' . $unidad['puntos'] . '</td>
                                 <td>' . $unidad['maximos'] . '</td>
                                 <td class="calificacion ' . 
                                     ($unidad['maximos'] > 0 && ($unidad['puntos']/$unidad['maximos']*100) >= 70 ? 'aprobado' : 'reprobado') . '">' .
                                     ($unidad['maximos'] > 0 ? round($unidad['puntos']/$unidad['maximos']*100, 1) . '%' : 'N/A') .
                                 '</td>
                             </tr>
                         </table>';
        }
        
        $promedioFinal = $totalMaximos > 0 ? round($totalPuntos/$totalMaximos*100, 1) : 0;
        $contenido .= '<div class="promedio-final">
                     Promedio Final: <span class="' . ($promedioFinal >= 70 ? 'aprobado' : 'reprobado') . '">' . 
                     $promedioFinal . '%</span>
                     </div></div>';
    }
    
    return $contenido;
}
// 4. Procesar el formulario de cierre de periodo
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar contraseña
    $password = $_POST['password'] ?? '';
    $periodo = $_POST['periodo'] ?? date('Y') . '-' . (date('Y') + 1);

    $query = "SELECT * FROM administrativo WHERE `idAdministrativo(RFC)` = ? AND password = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ss", $adminId, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = "Contraseña incorrecta. Intente nuevamente.";
    } else {
        try {
            $conexion->begin_transaction();

            // Obtener todos los alumnos
            $queryAlumnos = "SELECT idAlumno, Nombre, Carrera FROM alumno";
            $resultAlumnos = $conexion->query($queryAlumnos);

            while ($alumno = $resultAlumnos->fetch_assoc()) {
                // Generar PDF/HTML del avance reticular
                $rutaArchivo = generarHistorialHTML($alumno, $periodo, $conexion);

                // Insertar en historial_academico
                $queryInsert = "INSERT INTO historial_academico
                                    (id_alumno, ruta_archivo, periodo, semestre, fecha_subida)
                                    VALUES (?, ?, ?, ?, NOW())";
                $stmtInsert = $conexion->prepare($queryInsert);
                $semestre = "Periodo " . $periodo;
                $stmtInsert->bind_param("ssss", $alumno['idAlumno'], $rutaArchivo, $periodo, $semestre);
                $stmtInsert->execute();
            }

            // Limpiar tablas
            $tables = [
                'actividades',
                'anuncios',
                'calificaciones',
                'comentario',
                'foro',
            ];

            foreach ($tables as $table) {
                $conexion->query("TRUNCATE TABLE $table");
            }

           // Actualizar alumnos: limpiar cursos y cambiar estatus a "pendiente"
$conexion->query("UPDATE alumno SET Cursos = NULL, estatus = 'pendiente'");

            $conexion->commit();
            $success = "Cierre de periodo realizado exitosamente. Se generaron los avances reticulares para todos los alumnos.";
        } catch (Exception $e) {
            $conexion->rollback();
            $error = "Error al realizar el cierre de periodo: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Cierre de Periodo</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .content {
            margin-left: 200px;
            padding: 100px;
            width: calc(100% - 250px);
            box-sizing: border-box;
        }

        .warning-box {
            background-color:--accent-color;
            border-left: 6px solid #ffc107;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 4px;
        }

        .warning-box h3 {
            color: #856404;
            margin-top: 0;
            display: flex;
            align-items: center;
        }

        .warning-box h3 i {
            margin-right: 10px;
        }

        .form-container {
            max-width: 500px;
            margin: 0 auto;
            background-color: var(--secondary-color);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .form-group input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        /* Agregar estilos para el campo de periodo */
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s;
        }

        .btn-danger:hover {
            background-color: #c82333;
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
        <li><a href="/ADMINISTRATIVO/inicio_admin.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="administrador_gestionar usuarios.php"><i class="fas fa-user-plus"></i> <span>Gestionar Usuarios</span></a></li>
            <li><a href="administrarforo_administrador.php"><i class="fas fa-comments"></i> <span>Administrar Foros</span></a></li>
            <li><a href="administrador_documentos.php"><i class="fas fa-file-alt"></i> <span>Gestión Documental</span></a></li>
            <li><a href="admin_cursos.php"><i class="fas fa-book"></i> <span>Cursos</span></a></li>
            <li><a href="admin_anuncios.php"><i class="fas fa-comment-dots"></i> <span>Anuncios</span></a></li>
            <li class="active"><a href="definir_horarios.php"><i class="fas fa-calendar"></i> <span>Definir Horarios</span></a></li>
            <li><a href="funciones_especiales.php"><i class="fas fa-star"></i> <span>Funciones adicionales</span></a></li>
            <li><a href="/index"><i class="fas fa-backward"></i> <span>Log-out</span></a></li>
        </ul>
    </div>

    <div class="header">
        <h1>Cierre de Periodo Escolar</h1>
        <p>Acción administrativa avanzada para finalizar un ciclo académico</p>
    </div>

    <div class="content">
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

        <div class="warning-box">
            <h3><i class="fas fa-exclamation-triangle"></i> Cierre de Periodo Escolar</h3>
            <p>Esta acción realizará lo siguiente:</p>
            <ol>
                <li>Generará un documento (PDF o HTML) del avance reticular para cada alumno.</li>
                <li>Guardará los documentos en el sistema con el formato [Número de Control]_[Periodo Escolar].pdf (o .html si la extensión PDF no está habilitada).</li>
                <li>Registrará la ruta de los documentos en el historial académico.</li>
                <li>Limpiará las tablas de actividades, foros y calificaciones.</li>
                <li>Reiniciará la asignación de cursos a los alumnos.</li>
            </ol>
            <p><strong>Esta operación es irreversible.</strong> </p>
        </div>

        <div class="form-container">
            <form method="POST" onsubmit="return confirm('¿Está seguro de realizar el cierre de periodo? Esta acción generará documentos y limpiará datos del periodo actual.');">
                <div class="form-group">
                    <label for="periodo">Periodo Escolar a Cerrar:</label>
                    <select id="periodo" name="periodo" required>
                        <?php
                        $currentYear = date('Y');
                        for ($i = -2; $i <= 2; $i++) {
                            $year = $currentYear + $i;
                            $periodo = $year . '-' . ($year + 1);
                            echo "<option value='$periodo'" . ($i === 0 ? ' selected' : '') . ">$periodo</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Confirme su contraseña para proceder:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn-danger">
                    <i class="fas fa-calendar-times"></i> Realizar Cierre de Periodo
                </button>
            </form>
        </div>
    </div>

    <script src="/scripts.js"></script>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('¿Está absolutamente seguro de realizar el cierre de periodo? Esta acción generará documentos y limpiará datos del periodo seleccionado.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>