<?php
// ----------------------------------------------------------------------
// ARCHIVO 4: app/views/employee/index.php (NUEVO ARCHIVO)
// Esta es la nueva página principal para el empleado, con un menú de botones.
// Debes CREAR este archivo en la ruta app/views/employee/.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<style>
    .menu-button {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 150px;
        font-size: 1.2rem;
        font-weight: bold;
        text-decoration: none;
        color: white;
        border-radius: 0.75rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .menu-button:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        color: white;
    }
    .menu-button i {
        font-size: 3rem;
        margin-bottom: 0.5rem;
    }
</style>

<div class="text-center mb-4">
    <h2>Bienvenido, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_full_name'])[0]); ?></h2>
    <p class="lead text-muted">¿Qué te gustaría hacer hoy?</p>
</div>

<div class="row">
    <div class="col-md-6 col-lg-3 mb-4">
        <a href="<?php echo URLROOT; ?>/schedule/index" class="menu-button bg-success">
            <i class="fas fa-user-clock"></i>
            <span>Mis Horarios</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
        <a href="<?php echo URLROOT; ?>/employee/dashboard" class="menu-button bg-primary">
            <i class="fas fa-history"></i>
            <span>Cargar Horas Extras</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
        <a href="<?php echo URLROOT; ?>/request/index" class="menu-button bg-info">
            <i class="fas fa-calendar-check"></i>
            <span>Mis Solicitudes</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
        <a href="<?php echo URLROOT; ?>/suggestion/index" class="menu-button bg-secondary">
            <i class="fas fa-lightbulb"></i>
            <span>Sugerencias</span>
        </a>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>