<?php require_once __DIR__ . '/actions/configuracion_action.php'; ?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Configuración | SYSMOVILWT</title>


    <!-- =====================================================
         GOOGLE FONTS
    ====================================================== -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


    <!-- =====================================================
         CSS GENERAL
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/styles.css">

    <link
        rel="stylesheet"
        href="../assets/css/components.css">


    <!-- =====================================================
         CSS DEL MÓDULO
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/configuracion.css">

    <style>
        .config-status{padding:12px 16px;border-radius:8px;margin:0 0 18px}
        .config-status.success{background:#e9f8ef;color:#17663a}
        .config-status.error{background:#fff0f0;color:#a52323}
        .config-migration{padding:12px 16px;border-radius:8px;background:#fff8e6;color:#755515;margin-bottom:18px}
        .config-role-form{display:grid;gap:10px;max-width:520px}
        .config-role-form input,.config-role-form textarea{box-sizing:border-box;width:100%;padding:11px;border:1px solid #d8deea;border-radius:8px;font:inherit}
        .config-role-list{display:grid;gap:10px}
        .config-role-row{display:grid;grid-template-columns:minmax(120px,.8fr) minmax(140px,1.4fr) auto;gap:12px;align-items:center;padding:12px;border-bottom:1px solid #e8ebf0}
        .config-role-row span{color:#667085}
        .config-permission-note{margin-top:18px}
        @media(max-width:700px){.config-role-row{grid-template-columns:1fr}}
    </style>

</head>


<body>


    <!-- =====================================================
         SIDEBAR
         NO SE MODIFICA LA ESTRUCTURA GENERAL
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <img
                src="../assets/img/logo/logowt.png"
                alt="Logo WT SERVIPHONE"
                class="logo-sidebar">

            <div class="logo-text">

                <h2>SYSMOVILWT</h2>

                <span>
                    Sistema de Gestión Empresarial
                </span>

            </div>

        </div>


        <nav class="sidebar-menu">

            <ul>

                <li>
                    <a href="dashboard.php">

                        <i class="fa-solid fa-house"></i>

                        <span>Dashboard</span>

                    </a>
                </li>


                <li>
                    <a href="inventario.php">

                        <i class="fa-solid fa-boxes-stacked"></i>

                        <span>Inventario</span>

                    </a>
                </li>


                <li>
                    <a href="ventas.php">

                        <i class="fa-solid fa-cart-shopping"></i>

                        <span>Ventas</span>

                    </a>
                </li>


                <li>
                    <a href="compras.php">

                        <i class="fa-solid fa-bag-shopping"></i>

                        <span>Compras</span>

                    </a>
                </li>


                <li>
                    <a href="clientes.php">

                        <i class="fa-solid fa-users"></i>

                        <span>Clientes</span>

                    </a>
                </li>


                <li>
                    <a href="proveedores.php">

                        <i class="fa-solid fa-truck"></i>

                        <span>Proveedores</span>

                    </a>
                </li>


                <li>
                    <a href="usuarios.php">

                        <i class="fa-solid fa-user-shield"></i>

                        <span>Usuarios</span>

                    </a>
                </li>


                <li>
                    <a href="serviciotecnico.php">

                        <i class="fa-solid fa-screwdriver-wrench"></i>

                        <span>Servicio Técnico</span>

                    </a>
                </li>


                <li>
                    <a href="reportes.php">

                        <i class="fa-solid fa-chart-column"></i>

                        <span>Reportes</span>

                    </a>
                </li>


                <li class="active">

                    <a href="configuracion.php">

                        <i class="fa-solid fa-gear"></i>

                        <span>Configuración</span>

                    </a>

                </li>

            </ul>

        </nav>


        <button
            class="logout-button"
            id="btnLogout">

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>Cerrar sesión</span>

        </button>

    </aside>



    <!-- =====================================================
         TOPBAR
         SE MANTIENE IGUAL QUE DASHBOARD
    ====================================================== -->

    <header class="topbar">

        <div class="topbar-left">

            <button
                class="menu-toggle"
                type="button">

                <i class="fa-solid fa-bars"></i>

            </button>


            <div class="topbar-title">

                <h1>Configuración</h1>

                <span>
                    Configuración general del sistema
                </span>

            </div>

        </div>


        <div class="topbar-right">

            <div class="topbar-date">

                <i class="fa-regular fa-calendar"></i>

                <span id="currentDate"></span>

            </div>


            <button
                class="notification-button"
                type="button">

                <i class="fa-regular fa-bell"></i>

                <span class="notification-dot"></span>

            </button>


            <div class="topbar-profile">

                <div class="profile-avatar">
                    WT
                </div>


                <div class="profile-info">

                    <strong>Leonardo</strong>

                    <span>Administrador</span>

                </div>

            </div>

        </div>

    </header>



    <!-- =====================================================
         CONTENIDO PRINCIPAL
    ====================================================== -->

    <main class="configuracion-main">


        <section class="configuracion-page">


            <!-- =================================================
                 ENCABEZADO
            ================================================== -->

            <header class="configuracion-header">

                <div class="configuracion-header-content">

                    <h2 class="configuracion-title">

                        <i class="fa-solid fa-gear"></i>

                        Configuración del Sistema

                    </h2>


                    <p class="configuracion-subtitle">

                        Administra la configuración general,
                        seguridad, permisos y preferencias
                        de SYSMOVILWT.

                    </p>

                </div>

            </header>

            <?php if ($mensajeConfiguracion !== ''): ?>
                <div class="config-status <?= escaparConfiguracion($tipoMensajeConfiguracion) ?>" role="status">
                    <?= escaparConfiguracion($mensajeConfiguracion) ?>
                </div>
            <?php endif; ?>

            <?php if (!$tablaConfiguracionDisponible): ?>
                <div class="config-migration"><strong>Acción requerida:</strong> ejecuta <code>database/001_configuracion_sistema.sql</code> para guardar los datos generales de la empresa.</div>
            <?php endif; ?>



            <!-- =================================================
                 TARJETA PRINCIPAL
            ================================================== -->

            <section class="configuracion-card">


                <!-- =================================================
                     PESTAÑAS
                ================================================== -->

                <nav
                    class="config-tabs"
                    aria-label="Secciones de configuración">


                    <button
                        type="button"
                        class="config-tab config-tab-active"
                        data-tab="general">

                        <i class="fa-solid fa-building"></i>

                        <span>
                            Configuración general
                        </span>

                    </button>


                    <button
                        type="button"
                        class="config-tab"
                        data-tab="roles">

                        <i class="fa-solid fa-user-shield"></i>

                        <span>
                            Roles y permisos
                        </span>

                    </button>


                    <button
                        type="button"
                        class="config-tab"
                        data-tab="seguridad">

                        <i class="fa-solid fa-lock"></i>

                        <span>
                            Seguridad
                        </span>

                    </button>


                    <button
                        type="button"
                        class="config-tab"
                        data-tab="preferencias">

                        <i class="fa-solid fa-bell"></i>

                        <span>
                            Preferencias
                        </span>

                    </button>

                </nav>



                <!-- =================================================
                     CONTENIDO GENERAL
                ================================================== -->

                <div
                    id="tab-general"
                    class="config-tab-content config-tab-content-active">
<form method="post" action="configuracion.php" id="formConfiguracion">
    <input type="hidden" name="csrf_token" value="<?= escaparConfiguracion($_SESSION['csrf_configuracion']) ?>">
    <input type="hidden" name="accion" value="guardar_empresa">


                    <div class="config-section-header">

                        <div>

                            <h3>
                                <i class="fa-solid fa-building"></i>
                                Información de la empresa
                            </h3>

                            <p>
                                Configura los datos generales
                                de tu empresa.
                            </p>

                        </div>

                    </div>


                    <div class="config-divider"></div>


                    <div class="config-form-grid">


                        <!-- NOMBRE -->

                        <div class="config-form-group">

                            <label for="nombreEmpresa">

                                <i class="fa-solid fa-building"></i>

                                Nombre de la empresa

                            </label>

                            <input
                                type="text"
                                id="nombreEmpresa"
                                name="nombre_empresa" value="<?= escaparConfiguracion($empresa['nombre_empresa']) ?>" required
                                placeholder="Nombre de la empresa">

                        </div>


                        <!-- NIT -->

                        <div class="config-form-group">

                            <label for="nitEmpresa">

                                <i class="fa-solid fa-id-card"></i>

                                NIT / Identificación

                            </label>

                            <input
                                type="text"
                                id="nitEmpresa"
                                name="nit" value="<?= escaparConfiguracion($empresa['nit']) ?>"
                                placeholder="Ingrese NIT">

                        </div>


                        <!-- TELÉFONO -->

                        <div class="config-form-group">

                            <label for="telefonoEmpresa">

                                <i class="fa-solid fa-phone"></i>

                                Teléfono

                            </label>

                            <input
                                type="tel"
                                id="telefonoEmpresa"
                                name="telefono" value="<?= escaparConfiguracion($empresa['telefono']) ?>"
                                placeholder="Ingrese teléfono">

                        </div>


                        <!-- CORREO -->

                        <div class="config-form-group">

                            <label for="correoEmpresa">

                                <i class="fa-solid fa-envelope"></i>

                                Correo electrónico

                            </label>

                            <input
                                type="email"
                                id="correoEmpresa"
                                name="correo" value="<?= escaparConfiguracion($empresa['correo']) ?>"
                                placeholder="correo@empresa.com">

                        </div>


                        <!-- DIRECCIÓN -->

                        <div class="config-form-group config-form-full">

                            <label for="direccionEmpresa">

                                <i class="fa-solid fa-location-dot"></i>

                                Dirección de la empresa

                            </label>

                            <input
                                type="text"
                                id="direccionEmpresa"
                                name="direccion" value="<?= escaparConfiguracion($empresa['direccion']) ?>"
                                placeholder="Ingrese dirección de la empresa">

                        </div>


                        <!-- LOGO -->

                        <div class="config-form-group config-form-full">

                            <label for="logoEmpresa">

                                <i class="fa-solid fa-image"></i>

                                Logo de la empresa

                            </label>

                            <input
                                type="file"
                                id="logoEmpresa"
                                name="logoEmpresa"
                                accept=".png,.jpg,.jpeg,.webp">

                            <small>
                                Formatos permitidos: PNG, JPG o WEBP.
                            </small>

                        </div>


                    </div>


                    <div class="config-form-actions">

                        <button
                            type="submit"
                            class="config-btn config-btn-primary"
                            id="btnGuardarConfiguracion"
                            <?= !$tablaConfiguracionDisponible ? 'disabled' : '' ?>>

                            <i class="fa-solid fa-floppy-disk"></i>

                            Guardar cambios

                        </button>

                    </div>

                </form>

                </div>


<!-- =====================================================
     ROLES Y PERMISOS
====================================================== -->

<div
    id="tab-roles"
    class="config-tab-content">

    <!-- ENCABEZADO -->

    <div class="config-section-header">

        <div>

            <h3>
                <i class="fa-solid fa-user-shield"></i>
                Roles y permisos
            </h3>

            <p>
                Administra los permisos de acceso de cada tipo de usuario.
            </p>

        </div>

    </div>


    <div class="config-divider"></div>


    <!-- SELECTOR DE ROL -->

    <div class="roles-config-header">

        <div class="config-form-group">

            <label for="selectRol">

                <i class="fa-solid fa-users-gear"></i>

                Seleccionar rol

            </label>

            <select id="selectRol">

                <option value="administrador">
                    Administrador
                </option>

                <option value="vendedor">
                    Vendedor
                </option>

                <option value="tecnico">
                    Técnico
                </option>

                <option value="auxiliar">
                    Auxiliar
                </option>

            </select>

        </div>


        <div class="role-description">

            <strong id="rolNombre">
                Administrador
            </strong>

            <span id="rolDescripcion">
                Acceso completo a todas las funciones del sistema.
            </span>

        </div>

    </div>


    <!-- MATRIZ DE PERMISOS -->

    <div class="permissions-section">

        <div class="permissions-header">

            <div>

                <h4>
                    <i class="fa-solid fa-list-check"></i>
                    Permisos del rol
                </h4>

                <p>
                    Selecciona las acciones que podrá realizar este rol.
                </p>

            </div>

        </div>


        <div class="permissions-table-wrapper">

            <table class="permissions-table">

                <thead>

                    <tr>

                        <th>
                            Módulo
                        </th>

                        <th>
                            <i class="fa-solid fa-eye"></i>
                            Ver
                        </th>

                        <th>
                            <i class="fa-solid fa-plus"></i>
                            Crear
                        </th>

                        <th>
                            <i class="fa-solid fa-pen"></i>
                            Editar
                        </th>

                        <th>
                            <i class="fa-solid fa-trash"></i>
                            Eliminar
                        </th>

                        <th>
                            <i class="fa-solid fa-file-export"></i>
                            Exportar
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <!-- DASHBOARD -->

                    <tr>

                        <td>
                            <i class="fa-solid fa-house"></i>
                            Dashboard
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="dashboard_ver"
                                checked>
                        </td>

                        <td>—</td>
                        <td>—</td>
                        <td>—</td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="dashboard_exportar"
                                checked>
                        </td>

                    </tr>


                    <!-- INVENTARIO -->

                    <tr>

                        <td>
                            <i class="fa-solid fa-boxes-stacked"></i>
                            Inventario
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="inventario_ver"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="inventario_crear"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="inventario_editar"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="inventario_eliminar"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="inventario_exportar"
                                checked>
                        </td>

                    </tr>


                    <!-- VENTAS -->

                    <tr>

                        <td>
                            <i class="fa-solid fa-cart-shopping"></i>
                            Ventas
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="ventas_ver"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="ventas_crear"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="ventas_editar"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="ventas_eliminar">
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="ventas_exportar"
                                checked>
                        </td>

                    </tr>


                    <!-- COMPRAS -->

                    <tr>

                        <td>
                            <i class="fa-solid fa-bag-shopping"></i>
                            Compras
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="compras_ver"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="compras_crear"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="compras_editar"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="compras_eliminar">
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="compras_exportar"
                                checked>
                        </td>

                    </tr>


                    <!-- CLIENTES -->

                    <tr>

                        <td>
                            <i class="fa-solid fa-users"></i>
                            Clientes
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="clientes_ver"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="clientes_crear"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="clientes_editar"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="clientes_eliminar">
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="clientes_exportar"
                                checked>
                        </td>

                    </tr>


                    <!-- PROVEEDORES -->

                    <tr>

                        <td>
                            <i class="fa-solid fa-truck"></i>
                            Proveedores
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="proveedores_ver"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="proveedores_crear"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="proveedores_editar"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="proveedores_eliminar">
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="proveedores_exportar"
                                checked>
                        </td>

                    </tr>


                    <!-- USUARIOS -->

                    <tr>

                        <td>
                            <i class="fa-solid fa-user-shield"></i>
                            Usuarios
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="usuarios_ver"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="usuarios_crear"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="usuarios_editar"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="usuarios_eliminar"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="usuarios_exportar">
                        </td>

                    </tr>


                    <!-- SERVICIO TÉCNICO -->

                    <tr>

                        <td>
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            Servicio Técnico
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="servicio_ver"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="servicio_crear"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="servicio_editar"
                                checked>
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="servicio_eliminar">
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="servicio_exportar"
                                checked>
                        </td>

                    </tr>


                    <!-- REPORTES -->

                    <tr>

                        <td>
                            <i class="fa-solid fa-chart-column"></i>
                            Reportes
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="reportes_ver"
                                checked>
                        </td>

                        <td>—</td>
                        <td>—</td>
                        <td>—</td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="reportes_exportar"
                                checked>
                        </td>

                    </tr>


                    <!-- CONFIGURACIÓN -->

                    <tr>

                        <td>
                            <i class="fa-solid fa-gear"></i>
                            Configuración
                        </td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="configuracion_ver"
                                checked>
                        </td>

                        <td>—</td>

                        <td>
                            <input
                                type="checkbox" disabled
                                name="configuracion_editar"
                                checked>
                        </td>

                        <td>—</td>

                        <td>—</td>

                    </tr>

                </tbody>

            </table>

        </div>

    </div>


    <!-- ACCIONES -->

    <div class="config-form-actions">

        <button
            type="button"
            class="config-btn"
            disabled>

            <i class="fa-solid fa-rotate-left"></i>

            Restaurar permisos

        </button>


        <button
            type="button"
            class="config-btn config-btn-primary"
            id="btnGuardarPermisos"
            disabled>

            <i class="fa-solid fa-floppy-disk"></i>

            Guardar cambios

        </button>

    </div>

    <div class="config-divider"></div>
    <div class="config-section-header"><div><h3><i class="fa-solid fa-users-gear"></i> Administrar roles</h3><p>Crea o edita los roles guardados en MySQL. La eliminación se omite para proteger usuarios vinculados.</p></div></div>
    <div class="config-form-grid">
        <div class="config-form-group"><label for="nombreRol"><i class="fa-solid fa-user-shield"></i> <?= $rolEditar ? 'Editar rol' : 'Nuevo rol' ?></label>
            <form method="post" action="configuracion.php#tab-roles" class="config-role-form">
                <input type="hidden" name="csrf_token" value="<?= escaparConfiguracion($_SESSION['csrf_configuracion']) ?>"><input type="hidden" name="accion" value="guardar_rol"><input type="hidden" name="id_rol" value="<?= (int)($rolEditar['id_rol'] ?? 0) ?>">
                <input type="text" id="nombreRol" name="nombre_rol" maxlength="50" required value="<?= escaparConfiguracion($rolEditar['nombre_rol'] ?? '') ?>" placeholder="Nombre del rol">
                <textarea id="descripcionRol" name="descripcion_rol" maxlength="150" rows="3" placeholder="Descripción"><?= escaparConfiguracion($rolEditar['descripcion'] ?? '') ?></textarea>
                <button type="submit" class="config-btn config-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar rol</button>
                <?php if ($rolEditar): ?><a class="config-btn" href="configuracion.php#tab-roles">Cancelar edición</a><?php endif; ?>
            </form>
        </div>
        <div class="config-form-group"><label><i class="fa-solid fa-list"></i> Roles registrados</label>
            <div class="config-role-list">
                <?php if (!$rolesConfiguracion): ?><p>No hay roles registrados.</p><?php else: ?>
                    <?php foreach ($rolesConfiguracion as $rol): ?><div class="config-role-row"><strong><?= escaparConfiguracion($rol['nombre_rol']) ?></strong><span><?= escaparConfiguracion($rol['descripcion']) ?></span><a href="configuracion.php?editar_rol=<?= (int)$rol['id_rol'] ?>#tab-roles"><i class="fa-solid fa-pen"></i> Editar</a></div><?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="config-placeholder config-permission-note"><i class="fa-solid fa-circle-info"></i><p>Las casillas de permisos conservan la referencia visual de la maqueta, pero están deshabilitadas porque el esquema de MySQL todavía no almacena permisos por módulo.</p></div></div>



                <!-- =================================================
                     SEGURIDAD
                ================================================== -->

                <div
                    id="tab-seguridad"
                    class="config-tab-content">

                    <div class="config-section-header">

                        <div>

                            <h3>

                                <i class="fa-solid fa-lock"></i>

                                Seguridad

                            </h3>

                            <p>
                                Configura las opciones de seguridad
                                y protección del sistema.
                            </p>

                        </div>

                    </div>


                    <div class="config-placeholder">

                        <i class="fa-solid fa-shield-halved"></i>

                        <h4>
                            Configuración de seguridad
                        </h4>

                        <p>
                            Aquí estarán disponibles las opciones
                            relacionadas con la seguridad.
                        </p>

                    </div>

                </div>



                <!-- =================================================
                     PREFERENCIAS
                ================================================== -->

                <div
                    id="tab-preferencias"
                    class="config-tab-content">

                    <div class="config-section-header">

                        <div>

                            <h3>

                                <i class="fa-solid fa-bell"></i>

                                Preferencias

                            </h3>

                            <p>
                                Configura las preferencias y
                                notificaciones del sistema.
                            </p>

                        </div>

                    </div>


                    <div class="config-placeholder">

                        <i class="fa-solid fa-sliders"></i>

                        <h4>
                            Preferencias del sistema
                        </h4>

                        <p>
                            Aquí estarán disponibles las opciones
                            de preferencias y notificaciones.
                        </p>

                    </div>

                </div>


            </section>

        </section>

    </main>



    <!-- =====================================================
         JAVASCRIPT
    ====================================================== -->

    <script src="../assets/js/utils.js"></script>

    <script src="../assets/js/configuracion.js"></script>


</body>

</html>
