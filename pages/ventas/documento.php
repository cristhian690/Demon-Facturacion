<?php
require_once '../../config.php';
require_once '../../includes/helpers.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de venta no proporcionado.");
}

$ventas = get_data('ventas');
$clientes = get_data('clientes');
$productos = get_data('productos');
$empresas = get_data('empresas');

$venta = null;
foreach ($ventas as $v) {
    if ($v['id'] == $id && $v['empresa_id'] == $_SESSION['empresa_id']) {
        $venta = $v;
        break;
    }
}

if (!$venta) {
    die("Venta no encontrada.");
}

$cliente = null;
foreach ($clientes as $c) {
    if ($c['id'] == $venta['cliente_id']) { $cliente = $c; break; }
}

$empresa = null;
foreach ($empresas as $e) {
    if ($e['id'] == $venta['empresa_id']) { $empresa = $e; break; }
}

$getProd = function($pid) use ($productos) {
    foreach($productos as $p) { if ($p['id'] == $pid) return $p; }
    return ['sku' => 'N/A', 'nombre' => 'Desconocido'];
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Comercial - <?php echo $venta['serie'] . '-' . $venta['numero']; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .a4-container {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 20px auto;
            padding: 20mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .document-box {
            border: 2px solid #000;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
        }
        .table-items th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #000;
        }
        
        @media print {
            body { background: white; margin: 0; }
            .a4-container {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 15mm;
                box-shadow: none;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <!-- Botonera flotante (no imprimible) -->
    <div class="container text-center mt-3 mb-2 no-print">
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir Documento
        </button>
        <button class="btn btn-secondary ms-2" onclick="window.close()">
            <i class="bi bi-x-circle"></i> Cerrar
        </button>
    </div>

    <!-- Contenedor del documento -->
    <div class="a4-container">
        
        <!-- Cabecera -->
        <div class="row mb-5">
            <div class="col-7">
                <h2 class="fw-bold mb-1 text-primary"><?php echo htmlspecialchars($empresa['razon_social']); ?></h2>
                <p class="mb-0 text-muted">Soluciones empresariales integrales</p>
                <div class="mt-3">
                    <strong>Dirección:</strong> <?php echo htmlspecialchars($empresa['direccion'] ?? 'Av. Principal 123, Ciudad'); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($empresa['email'] ?? 'contacto@empresa.com'); ?><br>
                    <strong>Teléfono:</strong> <?php echo htmlspecialchars($empresa['telefono'] ?? '999-888-777'); ?>
                </div>
            </div>
            <div class="col-5">
                <div class="document-box">
                    <h4 class="mb-2">RUC: <?php echo htmlspecialchars($empresa['ruc']); ?></h4>
                    <h5 class="mb-2 text-uppercase fw-bold bg-light py-2 border-top border-bottom">
                        <?php echo $venta['tipo_documento'] === 'Factura' ? 'FACTURA ELECTRÓNICA' : 'BOLETA DE VENTA ELECTRÓNICA'; ?>
                    </h5>
                    <h4 class="mb-0"><?php echo htmlspecialchars($venta['serie'] . ' - ' . $venta['numero']); ?></h4>
                </div>
            </div>
        </div>
        
        <!-- Datos del Cliente -->
        <div class="border rounded p-3 mb-4">
            <div class="row">
                <div class="col-8">
                    <strong>Cliente:</strong> <?php echo htmlspecialchars($cliente['nombre']); ?><br>
                    <strong><?php echo htmlspecialchars($cliente['tipo_documento']); ?>:</strong> <?php echo htmlspecialchars($cliente['numero_documento']); ?><br>
                    <strong>Dirección:</strong> <?php echo htmlspecialchars($cliente['direccion'] ?? '-'); ?>
                </div>
                <div class="col-4">
                    <strong>Fecha de Emisión:</strong> <?php echo date('d/m/Y', strtotime($venta['fecha'])); ?><br>
                    <strong>Moneda:</strong> SOLES (PEN)
                </div>
            </div>
        </div>
        
        <!-- Detalles -->
        <table class="table table-items mb-4">
            <thead>
                <tr>
                    <th width="80" class="text-center">Código</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-center">U.M.</th>
                    <th>Descripción</th>
                    <th width="120" class="text-end">V. Unitario</th>
                    <th width="120" class="text-end">Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($venta['detalles'] as $det): 
                    $prod = $getProd($det['producto_id']);
                ?>
                    <tr>
                        <td class="text-center"><?php echo htmlspecialchars($prod['sku']); ?></td>
                        <td class="text-center"><?php echo $det['cantidad']; ?></td>
                        <td class="text-center">UN</td>
                        <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                        <td class="text-end"><?php echo format_money($det['precio_unitario']); ?></td>
                        <td class="text-end"><?php echo format_money($det['subtotal']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totales -->
        <div class="row justify-content-end mt-5">
            <div class="col-5">
                <table class="table table-sm table-borderless border">
                    <tbody>
                        <tr>
                            <td class="fw-bold">Op. Gravadas:</td>
                            <td class="text-end">S/ <?php echo number_format($venta['subtotal'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">IGV (18%):</td>
                            <td class="text-end">S/ <?php echo number_format($venta['igv'], 2); ?></td>
                        </tr>
                        <tr class="border-top bg-light">
                            <td class="fw-bold fs-5">IMPORTE TOTAL:</td>
                            <td class="text-end fw-bold fs-5">S/ <?php echo number_format($venta['total'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pie de página -->
        <div class="mt-5 text-center text-muted border-top pt-3" style="font-size: 0.8rem;">
            <p class="mb-0">Representación impresa de la <?php echo $venta['tipo_documento']; ?> Electrónica.</p>
            <p class="mb-0">Generado desde el prototipo facturacion-mock.</p>
        </div>
        
    </div>
    
</body>
</html>
