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
        if (themeToggle) {
            themeToggle.classList.remove('fa-moon');
            themeToggle.classList.add('fa-sun');
        }
    }
});

// Función para las notificaciones
function toggleNotifications() {
    const box = document.getElementById('notificationBox');
    if (box) {
        box.style.display = box.style.display === 'block' ? 'none' : 'block';
        box.innerHTML = '';
    }
}

// Función para cambiar pestañas en el login
function openTab(tabName) {
    // Oculta todos los contenidos de pestañas
    const tabContents = document.getElementsByClassName('tab-content');
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove('active');
    }
    
    // Desactiva todas las pestañas
    const tabs = document.getElementsByClassName('tab');
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }
    
    // Activa la pestaña seleccionada
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Función para generar el PDF de identificación
function generateID() {
    const element = document.getElementById('idCard');
    if (!element) return;
    
    // Mostrar temporalmente la tarjeta para capturarla
    element.style.display = 'block';
    
    // Configuración para el PDF
    const opt = {
        margin: 10,
        filename: 'identificacion_estudiante.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a7', orientation: 'portrait' }
    };
    
    // Generar el PDF
    html2pdf().set(opt).from(element).save().then(() => {
        // Ocultar la tarjeta después de generar el PDF
        element.style.display = 'none';
    });
}