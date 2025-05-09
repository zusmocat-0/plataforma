<?php
session_start();

// Verificar sesión de estudiante
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'alumno') {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Función para calcular el promedio general de una materia
function calcularPromedioGeneral($unidades) {
    $totalPuntos = 0;
    $totalMaximos = 0;

    foreach ($unidades as $unidad) {
        $totalPuntos += $unidad['puntos'];
        $totalMaximos += $unidad['maximos'];
    }

    return $totalMaximos > 0 ? round(($totalPuntos / $totalMaximos) * 100, 1) : 0;
}

// Obtener información del alumno
$idAlumno = $_SESSION['usuario']['id'];
$alumno = [];
$materias = [];

// Consulta para obtener los datos del alumno
$query = "SELECT Cursos, Carrera FROM alumno WHERE idAlumno = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("s", $idAlumno);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $alumno = $row;
    // Obtener los IDs de las materias del alumno (separados por comas)
    $idsMaterias = !empty($alumno['Cursos']) ? explode(',', $alumno['Cursos']) : [];

    // Obtener información de cada materia
    if (!empty($idsMaterias)) {
        $placeholders = implode(',', array_fill(0, count($idsMaterias), '?'));

        $query = "SELECT m.idMateria, m.Nombre, m.Descripcionmateria, m.NumeroUnidades, m.Unidades
                  FROM materia m
                  WHERE m.idMateria IN ($placeholders)";
        $stmt = $conexion->prepare($query);

        $types = str_repeat('s', count($idsMaterias));
        $stmt->bind_param($types, ...$idsMaterias);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Manejo robusto de unidades - VERSIÓN FINAL CORREGIDA
            $unidades = [];
            
            // 1. Decodificar JSON de unidades si existe
            if (!empty($row['Unidades'])) {
                $decoded = json_decode($row['Unidades'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Eliminar posibles duplicados en el JSON
                    $unidades = array_values(array_unique($decoded, SORT_REGULAR));
                }
            }
            
            // 2. Completar unidades faltantes manteniendo el orden original
            $unidadesExistentes = count($unidades);
            $unidadesNecesarias = $row['NumeroUnidades'];
            
            if ($unidadesExistentes < $unidadesNecesarias) {
                // Crear array de nombres de unidades existentes para comparación
                $nombresUnidades = array_map(function($u) {
                    return is_array($u) ? ($u['nombre'] ?? $u['name'] ?? '') : $u;
                }, $unidades);
                
                for ($i = $unidadesExistentes; $i < $unidadesNecesarias; $i++) {
                    $nombrePropuesto = 'Unidad ' . ($i + 1);
                    
                    // Si el nombre ya existe, añadir sufijo
                    if (in_array($nombrePropuesto, $nombresUnidades)) {
                        $nombrePropuesto = 'Unidad ' . ($i + 1) . ' - ' . ($i + 1);
                    }
                    
                    $unidades[] = ['nombre' => $nombrePropuesto];
                    $nombresUnidades[] = $nombrePropuesto;
                }
            } elseif ($unidadesExistentes > $unidadesNecesarias) {
                // Si hay más unidades de las necesarias, recortar
                $unidades = array_slice($unidades, 0, $unidadesNecesarias);
            }
            
            // 3. Verificación final de unidades únicas
            $unidadesUnicas = [];
            $nombresVistos = [];
            
            foreach ($unidades as $unidad) {
                $nombre = is_array($unidad) ? ($unidad['nombre'] ?? $unidad['name'] ?? '') : $unidad;
                
                if (!in_array($nombre, $nombresVistos)) {
                    $unidadesUnicas[] = $unidad;
                    $nombresVistos[] = $nombre;
                }
            }
            
            $unidades = $unidadesUnicas;

            // Obtener actividades para esta materia
            $queryActividades = "SELECT idActividades, TituloActividades, unidad, calificacion_maxima,
                                        calificacion, Entregas
                                        FROM actividades
                                        WHERE idcurso = ?";
            $stmtActividades = $conexion->prepare($queryActividades);
            $stmtActividades->bind_param("s", $row['idMateria']);
            $stmtActividades->execute();
            $resultActividades = $stmtActividades->get_result();

            $actividades = [];
            while ($actividad = $resultActividades->fetch_assoc()) {
                $actividades[] = $actividad;
            }

            // Buscar profesor
            $queryProfesor = "SELECT d.Nombre
                              FROM docente d
                              WHERE FIND_IN_SET(?, d.Cursos)
                              LIMIT 1";
            $stmtProfesor = $conexion->prepare($queryProfesor);
            $stmtProfesor->bind_param("s", $row['idMateria']);
            $stmtProfesor->execute();
            $resultProfesor = $stmtProfesor->get_result();

            $profesor = 'Sin asignar';
            if ($profesorRow = $resultProfesor->fetch_assoc()) {
                $profesor = $profesorRow['Nombre'];
            }

            // Inicializar unidades con índices basados en 1
            $calificacionesUnidades = [];
            foreach ($unidades as $index => $unidadData) {
                $numUnidad = $index + 1; // Convertir a índice basado en 1
                $nombreUnidad = is_array($unidadData) ? 
                               ($unidadData['nombre'] ?? $unidadData['name'] ?? "Unidad $numUnidad") : 
                               "Unidad $numUnidad";

                $calificacionesUnidades[$numUnidad] = [
                    'nombre' => $nombreUnidad,
                    'actividades' => 0,
                    'entregadas' => 0,
                    'puntos' => 0,
                    'maximos' => 0,
                    'promedio' => 0
                ];
            }

            // Procesar actividades
            foreach ($actividades as $actividad) {
                $unidad = $actividad['unidad'];
                if (isset($calificacionesUnidades[$unidad])) {
                    $calificacionesUnidades[$unidad]['actividades']++;

                    $entregas = $actividad['Entregas'] ? explode(',', $actividad['Entregas']) : [];
                    $calificaciones = $actividad['calificacion'] ? explode(',', $actividad['calificacion']) : [];
                    $indice = array_search($idAlumno, $entregas);

                    if ($indice !== false) {
                        $calificacionesUnidades[$unidad]['entregadas']++;

                        if (isset($calificaciones[$indice]) && is_numeric($calificaciones[$indice])) {
                            $calificacionesUnidades[$unidad]['puntos'] += $calificaciones[$indice];
                            $calificacionesUnidades[$unidad]['maximos'] += $actividad['calificacion_maxima'];
                        }
                    }
                }
            }

            // Calcular promedios
            foreach ($calificacionesUnidades as &$unidad) {
                if ($unidad['entregadas'] > 0 && $unidad['maximos'] > 0) {
                    $unidad['promedio'] = round(($unidad['puntos'] / $unidad['maximos']) * 100, 1);
                }
            }

            $materias[] = [
                'id' => $row['idMateria'],
                'nombre' => $row['Nombre'],
                'descripcion' => $row['Descripcionmateria'],
                'profesor' => $profesor,
                'unidades' => $calificacionesUnidades,
                'total_actividades' => count($actividades),
                'actividades_entregadas' => array_sum(array_column($calificacionesUnidades, 'entregadas')),
                'promedio_general' => calcularPromedioGeneral($calificacionesUnidades)
            ];
        }
    }
}

// Obtener actividades pendientes
$actividadesPendientes = [];
$proximasEntregas = [];
$hoy = new DateTime();

if (!empty($idsMaterias)) {
    $placeholders = implode(',', array_fill(0, count($idsMaterias), '?'));

    $query = "SELECT a.idActividades, a.TituloActividades, a.fecha_fin, m.Nombre AS nombre_materia
              FROM actividades a
              JOIN materia m ON a.idcurso = m.idMateria
              WHERE a.idcurso IN ($placeholders)
              AND (a.Entregas IS NULL OR a.Entregas NOT LIKE ?)
              AND a.fecha_fin >= NOW()
              ORDER BY a.fecha_fin ASC
              LIMIT 5";

    $stmt = $conexion->prepare($query);
    $types = str_repeat('s', count($idsMaterias)) . 's';
    $params = array_merge($idsMaterias, ["%$idAlumno%"]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($actividad = $result->fetch_assoc()) {
        $fechaFin = new DateTime($actividad['fecha_fin']);
        $diasRestantes = $hoy->diff($fechaFin)->days;

        $actividadesPendientes[] = $actividad;
        if ($diasRestantes <= 7) {
            $proximasEntregas[] = $actividad;
        }
    }
}

// Generar PDF
if (isset($_POST['generar_pdf'])) {
    // Incluir la librería TCPDF
    require_once('../TCPDF/tcpdf.php');

    // Crear nuevo documento PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Información del documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('MindBox');
    $pdf->SetTitle('Avance Reticular');
    $pdf->SetSubject('Avance Académico del Alumno');
    $pdf->SetKeywords('MindBox, avance, reticular, alumno, calificaciones');

    // Márgenes
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Salto de página automático
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Establecer fuente
    $pdf->SetFont('helvetica', '', 12);

    // Añadir una página
    $pdf->AddPage();

    // Contenido del PDF
    $html = '<h1>Avance Reticular</h1>';
    $html .= '<p><strong>Alumno ID:</strong> ' . htmlspecialchars($idAlumno) . '</p>';
    $html .= '<p><strong>Carrera:</strong> ' . htmlspecialchars($alumno['Carrera'] ?? 'No asignada') . '</p><br>';

    if (!empty($materias)) {
        $html .= '<h2>Detalle de Materias</h2>';
        foreach ($materias as $materia) {
            $html .= '<h3>' . htmlspecialchars($materia['nombre']) . '</h3>';
            $html .= '<p><strong>Profesor:</strong> ' . htmlspecialchars($materia['profesor']) . '</p>';
            $html .= '<p><strong>Promedio General:</strong> <span style="color: ' . ($materia['promedio_general'] >= 70 ? 'green' : 'red') . '">' . $materia['promedio_general'] . '%</span></p>';
            $html .= '<h4>Avance por Unidad</h4>';
            $html .= '<table border="1" cellpadding="5">';
            $html .= '<thead><tr><th>Unidad</th><th>Actividades Entregadas</th><th>Total Actividades</th><th>Promedio</th></tr></thead><tbody>';
            foreach ($materia['unidades'] as $numUnidad => $unidad) {
                $html .= '<tr><td>' . htmlspecialchars($unidad['nombre']) . '</td>';
                $html .= '<td>' . $unidad['entregadas'] . '</td>';
                $html .= '<td>' . $unidad['actividades'] . '</td>';
                $html .= '<td>' . ($unidad['promedio'] > 0 ? $unidad['promedio'] . '%' : 'Sin calificar') . '</td></tr>';
            }
            $html .= '</tbody></table><br>';
        }
    } else {
        $html .= '<p>No hay información de materias disponibles.</p>';
    }

    $html .= '<h2>Actividades Pendientes</h2>';
    if (!empty($actividadesPendientes)) {
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<thead><tr><th>Materia</th><th>Actividad</th><th>Fecha de Vencimiento</th></tr></thead><tbody>';
        foreach ($actividadesPendientes as $actividad) {
            $html .= '<tr><td>' . htmlspecialchars($actividad['nombre_materia']) . '</td>';
            $html .= '<td>' . htmlspecialchars($actividad['TituloActividades']) . '</td>';
            $html .= '<td>' . (new DateTime($actividad['fecha_fin']))->format('d/m/Y H:i') . '</td></tr>';
        }
        $html .= '</tbody></table>';
    } else {
        $html .= '<p>No tienes actividades pendientes.</p>';
    }

    // Escribir el HTML en el PDF
    $pdf->writeHTML($html, true, false, true, false, '');

    // Cerrar y generar el PDF
    $pdf->Output('avance_reticular_' . $idAlumno . '.pdf', 'D');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Avance Reticular</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
:root {
    --primary-color: #2c3e50;
    --secondary-color: #34495e;
    --accent-color: #3498db;
    --text-color: #ecf0f1;
    --light-gray: #bdc3c7;
    --dark-gray: #7f8c8d;
    --sidebar-width: 250px;
    --sidebar-collapsed-width: 60px;
}

    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()" title="Cambiar modo claro/oscuro">
        <i class="fas fa-moon"></i>
    </button>

    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="inicio_estudiante.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="cursos_estudiante.php"><i class="fas fa-book"></i> <span>Cursos</span></a></li>
            <li><a href="calendario_estudiante.php"><i class="fas fa-calendar-alt"></i> <span>Calendario</span></a></li>
            <li class="active"><a href="avance_reticular.php"><i class="fas fa-tasks"></i> <span>Avance Reticular</span></a></li>
            <li><a href="foro_estudiante.php"><i class="fas fa-comments"></i> <span>Foro</span></a></li>
            <li><a href="servicios_estudiante.php"><i class="fas fa-school"></i> <span>Servicios Escolares</span></a></li>
            <li><a href="info_estudiante.php"><i class="fas fa-user"></i> <span>Información personal</span></a></li>
            <li><a href="\logout.php"><i class="fas fa-right-from-bracket"></i> <span>Cerrar sesión</span></a></li>
        </ul>
    </div>
    
    <div class="header">
        <h1>Avance reticular</h1>
        <p>Resumen de tu progreso académico</p>
    </div>
    
    <div class="main-content">
        <?php if (empty($materias)): ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>No estás inscrito en ninguna materia</h3>
                <p>Actualmente no tienes materias asignadas. Por favor, contacta a servicios escolares si crees que esto es un error.</p>
                <a href="servicios_estudiante.php" class="btn">Contactar servicios escolares</a>
            </div>
        <?php else: ?>
            <div class="dashboard-card">
                <h3>Mis Materias</h3>
                <?php foreach ($materias as $materia): ?>
                    <div class="materia-card">
                        <div class="materia-header">
                            <div>
                                <div class="materia-title"><?php echo htmlspecialchars($materia['nombre']); ?></div>
                                <div class="materia-profesor">Profesor: <?php echo htmlspecialchars($materia['profesor']); ?></div>
                            </div>
                            <div>
                                <span class="calificacion <?php echo ($materia['promedio_general'] >= 70) ? 'aprobado' : 'reprobado'; ?>">
                                    <?php echo $materia['promedio_general']; ?>%
                                </span>
                            </div>
                        </div>
                        
                        <div class="unidades-container">
    <?php foreach ($materia['unidades'] as $numUnidad => $unidad): ?>
        <?php 
        // Validación exhaustiva de la estructura de $unidad
        $nombreUnidad = "Unidad $numUnidad";
        $actividadesTotales = 0;
        $actividadesEntregadas = 0;
        $promedioUnidad = 0;
        
        if (is_array($unidad)) {
            $nombreUnidad = htmlspecialchars($unidad['nombre'] ?? "Unidad $numUnidad");
            $actividadesTotales = $unidad['actividades'] ?? 0;
            $actividadesEntregadas = $unidad['entregadas'] ?? 0;
            $promedioUnidad = $unidad['promedio'] ?? 0;
        }
        ?>
        
        <div class="unidad-item">
            <div class="unidad-header">
                <div class="unidad-title"><?php echo $nombreUnidad; ?></div>
                <div class="unidad-meta">
                    <?php echo $actividadesEntregadas; ?>/<?php echo $actividadesTotales; ?> actividades
                </div>
            </div>
            
            <?php if ($actividadesTotales > 0): ?>
                <div class="unidad-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo ($actividadesEntregadas / $actividadesTotales) * 100; ?>%"></div>
                    </div>
                    <div>
                        <?php if ($actividadesEntregadas > 0): ?>
                            <span class="calificacion <?php echo ($promedioUnidad >= 70) ? 'aprobado' : 'reprobado'; ?>">
                                <?php echo $promedioUnidad; ?>%
                            </span>
                        <?php else: ?>
                            <span class="calificacion pendiente">Sin calificar</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <p>No hay actividades asignadas para esta unidad.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
                        
                        <div class="materia-footer">
                            <div>
                                <span><?php echo $materia['actividades_entregadas']; ?>/<?php echo $materia['total_actividades']; ?> actividades entregadas</span>
                            </div>
                            <div class="materia-promedio">
                                Promedio: <?php echo $materia['promedio_general']; ?>%
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3><i class="fas fa-exclamation-circle"></i> Actividades Pendientes</h3>
                    <?php if (count($actividadesPendientes) > 0): ?>
                        <?php foreach ($actividadesPendientes as $actividad): ?>
                            <div class="activity-item">
                                <div class="activity-info">
                                    <strong><?php echo htmlspecialchars($actividad['TituloActividades']); ?></strong>
                                    <div class="activity-meta">
                                        <span><?php echo htmlspecialchars($actividad['nombre_materia']); ?></span>
                                        <span class="activity-date">
                                            <?php echo (new DateTime($actividad['fecha_fin']))->format('d/m/Y H:i'); ?>
                                        </span>
                                    </div>
                                </div>
                                <a href="entregar_actividad.php?id=<?php echo $actividad['idActividades']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-upload"></i> Entregar
                                </a>
                            </div>
                        <?php endforeach; ?>
                        <a href="actividades_estudiante.php" class="btn">Ver todas las actividades</a>
                    <?php else: ?>
                        <p>No tienes actividades pendientes.</p>
                    <?php endif; ?>
                </div>
                
                <div class="dashboard-card">
                    <h3><i class="fas fa-clock"></i> Próximas entregas (7 días)</h3>
                    <?php if (count($proximasEntregas) > 0): ?>
                        <?php foreach ($proximasEntregas as $actividad): ?>
                            <div class="activity-item">
                                <div class="activity-info">
                                    <strong><?php echo htmlspecialchars($actividad['TituloActividades']); ?></strong>
                                    <div class="activity-meta">
                                        <span><?php echo htmlspecialchars($actividad['nombre_materia']); ?></span>
                                        <span class="activity-date">
                                            <?php 
                                            $fechaFin = new DateTime($actividad['fecha_fin']);
                                            $diasRestantes = $hoy->diff($fechaFin)->days;
                                            echo "Vence en $diasRestantes días";
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                <a href="entregar_actividad.php?id=<?php echo $actividad['idActividades']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-upload"></i> Entregar
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No hay entregas próximas en los próximos 7 días.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Función para cambiar entre modo claro y oscuro
        function toggleTheme() {
            const html = document.documentElement;
            const themeToggle = document.querySelector('.theme-toggle i');
            
            if (html.classList.contains('light-mode')) {
                html.classList.remove('light-mode');
                themeToggle.classList.remove('fa-sun');
                themeToggle.classList.add('fa-moon');
                localStorage.setItem('theme', 'dark');
            } else {
                html.classList.add('light-mode');
                themeToggle.classList.remove('fa-moon');
                themeToggle.classList.add('fa-sun');
                localStorage.setItem('theme', 'light');
            }
        }
        
        // Verificar el tema al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const themeToggle = document.querySelector('.theme-toggle i');
            
            if (savedTheme === 'light') {
                document.documentElement.classList.add('light-mode');
                themeToggle.classList.remove('fa-moon');
                themeToggle.classList.add('fa-sun');
            }
            
            // Animación de las barras de progreso
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
    <script src="/scripts.js"></script>
</body>
</html>