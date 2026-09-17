<div class="border-end text-white sidebar-wrapper shadow" id="sidebar-wrapper" style="width: 250px; min-height: 100vh;">
    <?php 
    $current_uri = $_SERVER['REQUEST_URI']; 
    function is_active($path) {
        global $current_uri;
        return strpos($current_uri, $path) !== false ? 'active' : '';
    }
    ?>
<div class="sidebar-heading px-4 py-4 d-flex align-items-center">
        <div class="bg-primary text-white rounded p-2 me-3 d-flex align-items-center justify-content-center shadow-sm">
            <i class="bi bi-box-seam fs-5"></i>
        </div>
        <span class="fs-5 fw-bold text-white" style="letter-spacing: -0.025em;">Fact-Kard</span>
    </div>
    
    <div class="list-group list-group-flush pt-2">
        
        <div class="px-4 text-uppercase fw-bold mb-2" style="font-size: 0.65rem; letter-spacing: 0.1em; color: #52525b;">Principal</div>
        <a href="<?php echo url('pages/dashboard.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('dashboard.php') ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        
        <div class="px-4 text-uppercase fw-bold mb-2 mt-4" style="font-size: 0.65rem; letter-spacing: 0.1em; color: #52525b;">Gestión</div>
        <a href="<?php echo url('pages/empresas/index.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('empresas') ? 'active' : ''; ?>">
            <i class="bi bi-buildings me-2"></i> Empresas
        </a>
        <a href="<?php echo url('pages/clientes/index.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('clientes') ? 'active' : ''; ?>">
            <i class="bi bi-people me-2"></i> Clientes
        </a>
        <a href="<?php echo url('pages/proveedores/index.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('proveedores') ? 'active' : ''; ?>">
            <i class="bi bi-truck me-2"></i> Proveedores
        </a>
        <a href="<?php echo url('pages/productos/index.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('productos') ? 'active' : ''; ?>">
            <i class="bi bi-box me-2"></i> Productos
        </a>
        <a href="<?php echo url('pages/almacenes/index.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('almacenes') ? 'active' : ''; ?>">
            <i class="bi bi-shop me-2"></i> Almacenes
        </a>
        
        <div class="px-4 text-uppercase fw-bold mb-2 mt-4" style="font-size: 0.65rem; letter-spacing: 0.1em; color: #52525b;">Operaciones</div>
        <a href="<?php echo url('pages/compras/index.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('compras') ? 'active' : ''; ?>">
            <i class="bi bi-cart-plus me-2"></i> Compras
        </a>
        <a href="<?php echo url('pages/ventas/index.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('ventas') ? 'active' : ''; ?>">
            <i class="bi bi-receipt me-2"></i> Ventas / Facturación
        </a>
        <a href="<?php echo url('pages/inventario/index.php'); ?>" class="list-group-item list-group-item-action <?php echo (is_active('inventario') && !is_active('kardex.php')) ? 'active' : ''; ?>">
            <i class="bi bi-boxes me-2"></i> Inventario
        </a>
        
        <div class="px-4 text-uppercase fw-bold mb-2 mt-4" style="font-size: 0.65rem; letter-spacing: 0.1em; color: #52525b;">Auditoría</div>
        <a href="<?php echo url('pages/inventario/kardex.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('kardex.php') ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i> Kardex Valorizado
        </a>
        <a href="<?php echo url('pages/kardex/importacion.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('importacion.php') ? 'active' : ''; ?>">
            <i class="bi bi-cloud-upload me-2"></i> Importación Histórica
        </a>
        
        <div class="px-4 text-uppercase fw-bold mb-2 mt-4" style="font-size: 0.65rem; letter-spacing: 0.1em; color: #52525b;">Reportes</div>
        <a href="<?php echo url('pages/reportes/index.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('reportes') ? 'active' : ''; ?>">
            <i class="bi bi-pie-chart me-2"></i> Reportes Generales
        </a>
        <a href="<?php echo url('pages/historial/index.php'); ?>" class="list-group-item list-group-item-action <?php echo is_active('historial') ? 'active' : ''; ?>">
            <i class="bi bi-clock-history me-2"></i> Historial
        </a>
        
    </div>
    
    <div class="mt-auto p-3 m-3 rounded text-start" style="font-size: 0.75rem; background-color: #27272a; border: 1px solid #3f3f46;">
        <div class="d-flex align-items-center mb-1">
            <i class="bi bi-shield-check text-success fs-5 me-2"></i>
            <strong class="text-light">Modo Prototipo</strong>
        </div>
        <span class="text-light d-block lh-sm mt-1">Prototipo para validación y pruebas</span>
    </div>
</div>
