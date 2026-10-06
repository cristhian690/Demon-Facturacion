<?php
$sidebar_uri = $_SERVER['REQUEST_URI'] ?? '';
$sidebar_path = parse_url($sidebar_uri, PHP_URL_PATH) ?: '';
$sidebar_query = [];
parse_str(parse_url($sidebar_uri, PHP_URL_QUERY) ?: '', $sidebar_query);
function sidebar_has($fragment) { global $sidebar_path; return strpos($sidebar_path, $fragment) !== false; }
function sidebar_class($condition) { return $condition ? 'active' : ''; }
$ventas_open = sidebar_has('/ventas/') || sidebar_has('/cuentas_cobrar/');
$inventario_open = sidebar_has('/inventario/index.php');
$kardex_open = sidebar_has('/inventario/kardex.php') || sidebar_has('/kardex/');
$sunat_open = sidebar_has('/sunat/');
?>
<aside class="border-end text-white sidebar-wrapper shadow" id="sidebar-wrapper">
    <div class="sidebar-heading px-4 py-4 d-flex align-items-center">
        <div class="sidebar-brand-icon"><i class="bi bi-box-seam"></i></div>
        <div><span class="sidebar-brand-name">Fact-Kard</span><small>Facturación + Inventario</small></div>
    </div>
    <nav class="list-group list-group-flush sidebar-nav" aria-label="Navegación principal">
        <div class="sidebar-section-label">Principal</div>
        <a href="<?php echo url('pages/dashboard.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class(sidebar_has('/dashboard.php')); ?>"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>

        <div class="sidebar-section-label">Gestión</div>
        <a href="<?php echo url('pages/empresas/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class(sidebar_has('/empresas/')); ?>"><i class="bi bi-buildings"></i><span>Empresas</span></a>
        <a href="<?php echo url('pages/clientes/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class(sidebar_has('/clientes/')); ?>"><i class="bi bi-people"></i><span>Clientes</span></a>
        <a href="<?php echo url('pages/proveedores/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class(sidebar_has('/proveedores/')); ?>"><i class="bi bi-truck"></i><span>Proveedores</span></a>
        <a href="<?php echo url('pages/productos/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class(sidebar_has('/productos/')); ?>"><i class="bi bi-box"></i><span>Productos</span></a>
        <a href="<?php echo url('pages/almacenes/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class(sidebar_has('/almacenes/')); ?>"><i class="bi bi-shop"></i><span>Almacenes</span></a>

        <div class="sidebar-section-label">Operaciones</div>
        <a href="<?php echo url('pages/compras/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class(sidebar_has('/compras/')); ?>"><i class="bi bi-cart-plus"></i><span>Compras</span></a>
        <div class="sidebar-module <?php echo $ventas_open ? 'open' : ''; ?>">
            <a href="<?php echo url('pages/ventas/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class($ventas_open); ?>"><i class="bi bi-receipt"></i><span>Ventas / Facturación</span><i class="bi bi-chevron-down sidebar-chevron"></i></a>
            <div class="sidebar-submenu">
                <a href="<?php echo url('pages/ventas/nueva.php'); ?>" class="<?php echo sidebar_class(sidebar_has('/ventas/nueva.php')); ?>"><i class="bi bi-plus-circle"></i><span>Nuevo comprobante</span></a>
                <a href="<?php echo url('pages/ventas/index.php'); ?>" class="<?php echo sidebar_class(sidebar_has('/ventas/index.php') && empty($sidebar_query['entrega'])); ?>"><i class="bi bi-list-ul"></i><span>Comprobantes</span></a>
                <a href="<?php echo url('pages/ventas/index.php?entrega=Pendiente'); ?>" class="<?php echo sidebar_class(sidebar_has('/ventas/index.php') && ($sidebar_query['entrega'] ?? '') === 'Pendiente'); ?>"><i class="bi bi-truck"></i><span>Entregas pendientes</span></a>
                <a href="<?php echo url('pages/cuentas_cobrar/index.php'); ?>" class="<?php echo sidebar_class(sidebar_has('/cuentas_cobrar/')); ?>"><i class="bi bi-cash-coin"></i><span>Cuentas por cobrar</span></a>
            </div>
        </div>
        <div class="sidebar-module <?php echo $inventario_open ? 'open' : ''; ?>">
            <a href="<?php echo url('pages/inventario/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class($inventario_open); ?>"><i class="bi bi-boxes"></i><span>Inventario</span><i class="bi bi-chevron-down sidebar-chevron"></i></a>
            <div class="sidebar-submenu">
                <a href="<?php echo url('pages/inventario/index.php'); ?>" class="<?php echo sidebar_class($inventario_open); ?>"><i class="bi bi-box-seam"></i><span>Stock actual</span></a>
                <a href="<?php echo url('pages/inventario/kardex.php'); ?>"><i class="bi bi-arrow-left-right"></i><span>Movimientos</span></a>
                <span class="sidebar-coming"><i class="bi bi-arrow-repeat"></i><span>Transferencias</span><em>En desarrollo</em></span>
            </div>
        </div>

        <div class="sidebar-section-label">Kardex</div>
        <div class="sidebar-module <?php echo $kardex_open ? 'open' : ''; ?>">
            <a href="<?php echo url('pages/inventario/kardex.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class($kardex_open); ?>"><i class="bi bi-file-earmark-spreadsheet"></i><span>Kardex Valorizado</span><i class="bi bi-chevron-down sidebar-chevron"></i></a>
            <div class="sidebar-submenu">
                <a href="<?php echo url('pages/inventario/kardex.php'); ?>" class="<?php echo sidebar_class($kardex_open); ?>"><i class="bi bi-search"></i><span>Consulta Kardex</span></a>
                <a href="<?php echo url('pages/inventario/kardex.php#movimientos'); ?>"><i class="bi bi-list-check"></i><span>Movimientos</span></a>
                <a href="<?php echo url('actions/exportar_kardex.php?empresa_id='.(int)($_SESSION['empresa_id']??0)); ?>"><i class="bi bi-download"></i><span>Exportar Kardex</span></a>
            </div>
        </div>

        <div class="sidebar-section-label">SUNAT</div>
        <div class="sidebar-module <?php echo $sunat_open ? 'open' : ''; ?> sidebar-future">
            <a href="<?php echo url('pages/sunat/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class($sunat_open); ?>"><i class="bi bi-cloud-check"></i><span>Facturación electrónica</span><span class="sidebar-badge">En desarrollo</span><i class="bi bi-chevron-down sidebar-chevron"></i></a>
            <div class="sidebar-submenu">
                <a href="<?php echo url('pages/sunat/documentos.php'); ?>" class="<?php echo sidebar_class(sidebar_has('/sunat/documentos.php')); ?>"><i class="bi bi-file-earmark-check"></i><span>Documentos electrónicos</span></a>
                <a href="<?php echo url('pages/sunat/nota_credito.php'); ?>" class="<?php echo sidebar_class(sidebar_has('/sunat/nota_credito.php')); ?>"><i class="bi bi-file-earmark-minus"></i><span>Notas de crédito</span></a>
                <a href="<?php echo url('pages/sunat/nota_debito.php'); ?>" class="<?php echo sidebar_class(sidebar_has('/sunat/nota_debito.php')); ?>"><i class="bi bi-file-earmark-plus"></i><span>Notas de débito</span></a>
                <a href="<?php echo url('pages/sunat/estado.php'); ?>" class="<?php echo sidebar_class(sidebar_has('/sunat/estado.php')); ?>"><i class="bi bi-broadcast"></i><span>Estado SUNAT</span></a>
                <a href="<?php echo url('pages/sunat/configuracion.php'); ?>" class="<?php echo sidebar_class(sidebar_has('/sunat/configuracion.php')); ?>"><i class="bi bi-sliders"></i><span>Configuración</span></a>
            </div>
        </div>

        <div class="sidebar-section-label">Reportes</div>
        <a href="<?php echo url('pages/reportes/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class(sidebar_has('/reportes/')); ?>"><i class="bi bi-pie-chart"></i><span>Reportes generales</span></a>
        <a href="<?php echo url('pages/historial/index.php'); ?>" class="list-group-item list-group-item-action <?php echo sidebar_class(sidebar_has('/historial/')); ?>"><i class="bi bi-clock-history"></i><span>Auditoría</span></a>
    </nav>
</aside>
