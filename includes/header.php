<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="empresa-id" content="<?php echo (int)($_SESSION['empresa_id'] ?? 0); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fact-Kard | Gestión empresarial</title>
    <!-- Bootstrap 5 CSS CDN (para simplificar en este paso, luego se puede mover a local) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo url('assets/css/app.css'); ?>">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper" class="flex-grow-1 bg-light">
            <!-- Top navigation-->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
                <div class="container-fluid">
                    <button class="btn btn-outline-secondary btn-sm" id="sidebarToggle">
                        <i class="bi bi-list"></i>
                    </button>
                    
                    <div class="d-flex align-items-center ms-auto">
                        <!-- Selector de Empresa -->
                        <form action="<?php echo url('actions/cambiar_empresa.php'); ?>" method="POST" class="d-flex align-items-center me-4">
                            <?php if (empty($_SESSION['form_token'])) $_SESSION['form_token'] = bin2hex(random_bytes(32)); ?>
                            <input type="hidden" name="form_token" value="<?php echo htmlspecialchars($_SESSION['form_token']); ?>">
                            <i class="bi bi-building me-2 text-primary"></i>
                            <?php 
                            $todas_empresas = get_data('empresas'); 
                            $emp_activa = get_empresa_activa();
                            ?>
                            <select name="empresa_id" class="form-select form-select-sm border-0 bg-light" onchange="this.form.submit()">
                                <?php foreach($todas_empresas as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo ($emp['id'] == $emp_activa['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['razon_social']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                        
                        <!-- Usuario -->
                        <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 32px; height: 32px;">
                                    <?php echo htmlspecialchars(substr($_SESSION['usuario']['nombre'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div class="fw-bold" style="font-size: 0.85rem;"><?php echo htmlspecialchars($_SESSION['usuario']['nombre']); ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem; line-height: 1;"><?php echo htmlspecialchars($_SESSION['usuario']['rol']); ?></div>
                                </div>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Main Content Area -->
            <main class="container-fluid app-content p-4">
