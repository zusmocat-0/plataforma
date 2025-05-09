<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindBox - Login</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Estilos adicionales para las pestañas */
        .tabs {
            display: flex;
            background-color: var(--primary-color);
            flex-wrap: wrap;
        }
        
        .tab {
            flex: 1;
            min-width: 120px;
            text-align: center;
            padding: 1rem 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .btn-register {
            width: 100%;
            padding: 0.8rem;
            background-color: #2e95cc;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 1rem;
        }
        
        .btn-register:hover {
            background-color: #276fae;
        }
        .login-container {
            margin-top: 150px;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ModuleBox</h1>
    </div>
    <button class="theme-toggle" onclick="toggleTheme()" title="Cambiar modo claro/oscuro">
        <i class="fas fa-moon"></i>
    </button>
    <div class="login-container">
        <div class="tabs">
            <div class="tab active" onclick="openTab('estudiante')">Estudiante</div>
            <div class="tab" onclick="openTab('docente')">Docente</div>
            <div class="tab" onclick="openTab('administrativo')">Administrativo</div>
        </div>
        
        <div id="estudiante" class="tab-content active">
            <form action="login_estudiante.php" method="post">
                <div class="form-group">
                    <label for="estudiante-control">Número de control</label>
                    <input type="text" id="estudiante-control" name="idAlumno" required>
                </div>
                <div class="form-group">
                    <label for="estudiante-password">Contraseña</label>
                    <input type="password" id="estudiante-password" name="password" required>
                </div>
                <p>No tienes cuenta?, <a href="www.google.com">Inscribete ahora</a></p>
                
                <?php if (isset($_GET['error']) && $_GET['error'] == 'estudiante'): ?>
                    <p class="error-message">Usuario o contraseña incorrectos</p>
                <?php endif; ?>
                
                <button type="submit" class="btn-login">Iniciar sesión</button>
            </form>
        </div>
        
        <div id="docente" class="tab-content">
            <form action="login_docente.php" method="post">
                <div class="form-group">
                    <label for="docente-id">ID de docente</label>
                    <input type="text" id="docente-id" name="idDocente" required>
                </div>
                <div class="form-group">
                    <label for="docente-password">Contraseña</label>
                    <input type="password" id="docente-password" name="password" required>
                </div>
                
                <?php if (isset($_GET['error']) && $_GET['error'] == 'docente'): ?>
                    <p class="error-message">Usuario o contraseña incorrectos</p>
                <?php endif; ?>
                
                <button type="submit" class="btn-login">Iniciar sesión</button>
            </form>
        </div>
        
        <div id="administrativo" class="tab-content">
            <form action="login_administrativo.php" method="post">
                <div class="form-group">
                    <label for="admin-id">ID de personal</label>
                    <input type="text" id="admin-id" name="idAdmin" required>
                </div>
                <div class="form-group">
                    <label for="admin-password">Contraseña</label>
                    <input type="password" id="admin-password" name="password" required>
                </div>
                
                <?php if (isset($_GET['error']) && $_GET['error'] == 'administrativo'): ?>
                    <p class="error-message">Usuario o contraseña incorrectos</p>
                <?php endif; ?>
                
                <button type="submit" class="btn-login">Iniciar sesión</button>
            </form>
        </div>
    </div>
    
    <script src="scripts.js"></script>
</body>
</html>