<?php
session_start();
if (!isset($_SESSION['idAdmin'])) {
    header("Location: /index.php");
    exit();
}

require_once '../conexion.php';

// Obtener carreras con ID y Nombre
$carreras = [];
$query = "SELECT idCarrera, Nombre FROM carrera";
$result = $conexion->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $carreras[$row['idCarrera']] = $row['Nombre'];
    }
}

// Obtener cursos por carrera con nombres
$cursos_por_carrera = [];
$query = "SELECT cm.Carrera, cm.Materia, m.Nombre as nombre_materia 
          FROM carrera_materia cm
          JOIN materia m ON cm.Materia = m.idMateria";
$result = $conexion->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Buscar el ID de la carrera correspondiente al nombre
        $idCarrera = array_search($row['Carrera'], $carreras);
        if ($idCarrera !== false) {
            $cursos_por_carrera[$idCarrera][] = [
                'id' => $row['Materia'],
                'nombre' => $row['nombre_materia']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Usuario - MindBox</title>
    <link rel="stylesheet" href="/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
                        /* Añadir al estilo existente */
            .checkbox-item {
                margin: 5px 0;
                padding: 5px;
                border-radius: 4px;
                transition: background-color 0.2s;
            }

            .checkbox-item:hover {
                background-color: rgba(0,0,0,0.05);
            }

            #cursos-container {
                background-color: var(--secondary-color);
            }

            #cursos-container label {
                cursor: pointer;
            }
                .registration-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--secondary-color);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .user-type-selector {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--dark-gray);
        }
        
        .user-type-btn {
            padding: 0.8rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            background: none;
            border: none;
            font-size: 1rem;
            color: var(--text-color);
        }
        
        .user-type-btn.active {
            border-bottom-color: var(--accent-color);
            font-weight: bold;
            color: var(--accent-color);
        }
        
        .registration-form {
            display: none;
        }
        
        .registration-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--dark-gray);
            border-radius: 4px;
            background-color: var(--secondary-color);
            color: var(--text-color);
        }
        
        .btn-submit {
            background-color: var(--accent-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
        }
        
        .btn-submit:hover {
            background-color: var(--accent-dark);
        }
        
        .preview-card {
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: var(--secondary-color);
            border-radius: 8px;
            border-left: 4px solid var(--accent-color);
            display: none;
        }
        
        .preview-title {
            color: var(--accent-color);
            margin-bottom: 1rem;
        }
        .form-group select[multiple] {
            height: auto;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <ul class="sidebar-menu">
        <li><a href="inicio_admin.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="administrador_gestionar usuarios.php"><i class="fas fa-user-plus"></i> <span>Registrar Usuario</span></a></li>
            <li class="active"><a href="buscar_usuario.php"><i class="fas fa-search"></i> <span>Buscar Usuario</span></a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Registrar Nuevo Usuario</h1>
            <p>Complete los datos del usuario a registrar</p>
        </div>
        
        <div class="registration-container">
            <div class="user-type-selector">
                <button class="user-type-btn active" onclick="showForm('student')">
                    <i class="fas fa-graduation-cap"></i> Alumno
                </button>
                <button class="user-type-btn" onclick="showForm('teacher')">
                    <i class="fas fa-chalkboard-teacher"></i> Docente
                </button>
            </div>
            
            <!-- Formulario para Alumno -->
            <form id="student-form" class="registration-form active" action="/ADMINISTRATIVO/registrar_estudiante.php" method="POST">

                <div class="form-group">
                    <label for="idAlumno">Número de Control</label>
                    <input type="text" id="idAlumno" name="idAlumno" required maxlength="45">
                </div>
                
                <div class="form-group">
                    <label for="Nombre">Nombre Completo</label>
                    <input type="text" id="Nombre" name="Nombre" required maxlength="45">
                </div>
                
                <div class="form-group">
                    <label for="Telefono">Teléfono</label>
                    <input type="tel" id="Telefono" name="Telefono" maxlength="11">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required maxlength="255">
                </div>
                
                <div class="form-group">
                    <label for="FechaNacimiento">Fecha de Nacimiento</label>
                    <input type="date" id="FechaNacimiento" name="FechaNacimiento">
                </div>
                
                <div class="form-group">
                    <label for="Sexo">Sexo</label>
                    <select id="Sexo" name="Sexo">
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="Domicilio">Domicilio</label>
                    <input type="text" id="Domicilio" name="Domicilio" maxlength="45">
                </div>
                
                <div class="form-group">
                    <label for="Curp">CURP</label>
                    <input type="text" id="Curp" name="Curp" maxlength="45">
                </div>
                
                <div class="form-group">
        <label for="Carrera">Carrera*</label>
        <select id="Carrera" name="Carrera" required onchange="actualizarCursos()">
            <option value="">Seleccione una carrera</option>
            <?php foreach ($carreras as $id => $nombre): ?>
                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nombre); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

<div class="form-group">
    <label for="Semestre">Semestre*</label>
    <input type="text" id="Semestre" name="Semestre" required maxlength="45">
</div>

<div class="form-group">
        <label>Cursos (opcional)</label>
        <div id="cursos-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 4px;">
            <p id="sin-cursos" style="color: #666; font-style: italic;">Seleccione una carrera primero</p>
        </div>
    </div>
                
                <button type="submit" class="btn-submit">Registrar Alumno</button>
            </form>
            
           
            <form id="teacher-form" class="registration-form" action="registrar_docente.php" method="POST">
    <div class="form-group">
        <label for="idDocente">RFC*</label>
        <input type="text" id="idDocente" name="idDocente" required maxlength="45">
    </div>
    
    <div class="form-group">
        <label for="Nombre">Nombre Completo*</label>
        <input type="text" id="Nombre" name="Nombre" required maxlength="45">
    </div>
    
    <div class="form-group">
        <label for="Telefono">Teléfono</label>
        <input type="tel" id="Telefono" name="Telefono" maxlength="45">
    </div>
    
    <div class="form-group">
        <label for="password">Contraseña*</label>
        <input type="password" id="password" name="password" required maxlength="255">
    </div>
    
    <div class="form-group">
        <label for="Domicilio">Domicilio</label>
        <input type="text" id="Domicilio" name="Domicilio" maxlength="45">
    </div>
    
    <div class="form-group">
        <label for="Sexo">Sexo</label>
        <select id="Sexo" name="Sexo">
            <option value="">Seleccionar...</option>
            <option value="Masculino">Masculino</option>
            <option value="Femenino">Femenino</option>
            <option value="Otro">Otro</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="FechasNacimiento">Fecha de Nacimiento</label>
        <input type="date" id="FechasNacimiento" name="FechasNacimiento">
    </div>
    
    <div class="form-group">
        <label for="Correo">Correo Electrónico</label>
        <input type="email" id="Correo" name="Correo" maxlength="45">
    </div>
    
    <div class="form-group">
        <label for="carrera-docente">Seleccionar Carrera para ver cursos</label>
        <select id="carrera-docente" name="carrera-docente" onchange="actualizarCursosDocente()">
            <option value="">Seleccione una carrera</option>
            <?php foreach ($carreras as $id => $nombre): ?>
                <option value="<?php echo htmlspecialchars($nombre); ?>"><?php echo htmlspecialchars($nombre); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label>Cursos que imparte (opcional)</label>
        <div id="cursos-docente-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 4px;">
            <p id="sin-cursos-docente" style="color: #666; font-style: italic;">Seleccione una carrera primero</p>
        </div>
    </div>
    
    <button type="submit" class="btn-submit">Registrar Docente</button>
</form>
        </div>
    </div>

    <script>
    // Mostrar el formulario correspondiente
    function showForm(userType) {
        // Cambiar botones activos
        document.querySelectorAll('.user-type-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Cambiar formularios visibles
        document.getElementById('student-form').classList.remove('active');
        document.getElementById('teacher-form').classList.remove('active');
        document.getElementById(`${userType}-form`).classList.add('active');
    }
    
    // Datos de cursos por carrera (convertimos el array PHP a JavaScript)
    const cursosPorCarrera = <?php echo json_encode($cursos_por_carrera); ?>;
    const carreras = <?php echo json_encode($carreras); ?>;

    function actualizarCursos() {
        const carreraSelect = document.getElementById('Carrera');
        const cursosContainer = document.getElementById('cursos-container');
        const sinCursosMsg = document.getElementById('sin-cursos');
        const carreraId = carreraSelect.value;
        
        // Limpiar container
        cursosContainer.innerHTML = '';
        
        // Mostrar mensaje si no hay carrera seleccionada
        if (!carreraId) {
            sinCursosMsg.style.display = 'block';
            cursosContainer.appendChild(sinCursosMsg);
            return;
        }
        
        // Buscar los cursos correspondientes a la carrera seleccionada
        if (cursosPorCarrera[carreraId]) {
            cursosPorCarrera[carreraId].forEach(curso => {
                const checkboxDiv = document.createElement('div');
                checkboxDiv.className = 'checkbox-item';
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.id = `curso-${curso.id}`;
                checkbox.name = 'Cursos[]';
                checkbox.value = curso.id;
                
                const label = document.createElement('label');
                label.htmlFor = `curso-${curso.id}`;
                label.textContent = `${curso.id} - ${curso.nombre}`;
                label.style.marginLeft = '5px';
                
                checkboxDiv.appendChild(checkbox);
                checkboxDiv.appendChild(label);
                cursosContainer.appendChild(checkboxDiv);
            });
            sinCursosMsg.style.display = 'none';
        } else {
            sinCursosMsg.textContent = 'No hay cursos disponibles para esta carrera';
            sinCursosMsg.style.display = 'block';
            cursosContainer.appendChild(sinCursosMsg);
        }
    }

    function actualizarCursosDocente() {
        const carreraSelect = document.getElementById('carrera-docente');
        const cursosContainer = document.getElementById('cursos-docente-container');
        const sinCursosMsg = document.getElementById('sin-cursos-docente');
        const carreraNombre = carreraSelect.value;
        
        // Limpiar container
        cursosContainer.innerHTML = '';
        
        // Mostrar mensaje si no hay carrera seleccionada
        if (!carreraNombre) {
            sinCursosMsg.style.display = 'block';
            cursosContainer.appendChild(sinCursosMsg);
            return;
        }
        
        // Buscar el ID de la carrera seleccionada
        let carreraId = null;
        for (const [id, nombre] of Object.entries(carreras)) {
            if (nombre === carreraNombre) {
                carreraId = id;
                break;
            }
        }
        
        if (!carreraId) {
            sinCursosMsg.textContent = 'Carrera no encontrada';
            sinCursosMsg.style.display = 'block';
            cursosContainer.appendChild(sinCursosMsg);
            return;
        }
        
        // Buscar los cursos correspondientes a la carrera seleccionada
        if (cursosPorCarrera[carreraId]) {
            cursosPorCarrera[carreraId].forEach(curso => {
                const checkboxDiv = document.createElement('div');
                checkboxDiv.className = 'checkbox-item';
                
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.id = `curso-docente-${curso.id}`;
                checkbox.name = 'Cursos[]';
                checkbox.value = curso.id;
                
                const label = document.createElement('label');
                label.htmlFor = `curso-docente-${curso.id}`;
                label.textContent = `${curso.id} - ${curso.nombre}`;
                label.style.marginLeft = '5px';
                
                checkboxDiv.appendChild(checkbox);
                checkboxDiv.appendChild(label);
                cursosContainer.appendChild(checkboxDiv);
            });
            sinCursosMsg.style.display = 'none';
        } else {
            sinCursosMsg.textContent = 'No hay cursos disponibles para esta carrera';
            sinCursosMsg.style.display = 'block';
            cursosContainer.appendChild(sinCursosMsg);
        }
    }
</script>
</body>
</html>