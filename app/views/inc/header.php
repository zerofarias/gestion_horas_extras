<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/views/inc/header.php (VERSIÓN COMPLETA Y CORREGIDA)
// ----------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITENAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- CSS para los botones de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <!-- Font Awesome (para iconos) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />
    <!-- Hoja de estilos personalizada -->
    <link rel="stylesheet" href="<?php echo URLROOT; ?>/css/style.css">
</head>
<body class="bg-light">

<?php if(isLoggedIn()): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
  <div class="container">
    <?php
        $home_url = URLROOT;
        if (isLoggedIn() && hasRole('empleado')) {
            $home_url = URLROOT . '/employee/index';
        } elseif (isLoggedIn() && hasRole('admin')) {
            $home_url = URLROOT . '/admin/dashboard';
        }
    ?>
    <a class="navbar-brand" href="<?php echo $home_url; ?>"><i class="fas fa-clock"></i> <?php echo SITENAME; ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      
      <!-- Menú para Empleado -->
      <?php if(hasRole('empleado')): ?>
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/employee/mySchedule">Mis Horarios</a>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/employee/dashboard">Cargar Horas Extras</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/request/index">Mis Solicitudes</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/suggestion/index">Sugerencias</a>
        </li>
      </ul>
      <?php endif; ?>

      <!-- Menú para Administrador (con Dropdowns) -->
      <?php if(hasRole('admin')): ?>
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="<?php echo URLROOT; ?>/admin/dashboard">Dashboard</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownPersonal" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Gestión de Personal
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownPersonal">
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/shiftManager">Planificador Mensual</a></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/schedules">Planificador Semanal</a></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/holidays">Gestionar Feriados</a></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/templates">Gestor de Plantillas</a></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/reports">Reportes de Horas</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/shiftManager">Gestor de Turnos</a></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/weeklyPlanner">Planificador Semanal</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/users">Gestionar Usuarios</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownOperaciones" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Operaciones
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownOperaciones">
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/requests">Gestionar Solicitudes</a></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/history">Historial de Cierres</a></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/suggestions">Buzón de Sugerencias</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/clockingsReport">Reporte de Fichajes</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownSistema" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Sistema
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownSistema">
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/sync">Sincronización Reloj</a></li>
            <li><a class="dropdown-item" href="<?php echo URLROOT; ?>/admin/companies">Gestionar Empresas</a></li>
          </ul>
        </li>
      </ul>
      <?php endif; ?>

      <!-- Menú de usuario (a la derecha) -->
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <span class="navbar-text me-3">
            Hola, <?php
                if (isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name'])) {
                    echo htmlspecialchars($_SESSION['user_full_name']);
                } elseif (isset($_SESSION['user_username'])) {
                    echo htmlspecialchars($_SESSION['user_username']);
                } else {
                    echo 'Usuario';
                }
            ?>
          </span>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-light" href="<?php echo URLROOT; ?>/login/logout">
            <i class="fas fa-sign-out-alt"></i> Salir
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<?php endif; ?>
<main class="container">
