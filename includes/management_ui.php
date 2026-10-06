<?php
function ui_escape($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function management_status($row) { return ($row['estado'] ?? 'Activo') === 'Inactivo' ? 'Inactivo' : 'Activo'; }
function management_status_badge($row) {
    $status = management_status($row);
    return '<span class="badge ' . ($status === 'Activo' ? 'text-bg-success' : 'text-bg-secondary') . '">' . $status . '</span>';
}
function management_status_form($module, $row, $name) {
    $status = management_status($row); $next = $status === 'Activo' ? 'Inactivo' : 'Activo'; $verb = $next === 'Activo' ? 'Reactivar' : 'Desactivar';
    if ($module === 'empresas' && $next === 'Inactivo' && (string)$row['id'] === (string)($_SESSION['empresa_id'] ?? '')) return '<span class="text-muted small" title="Cambia primero a otra empresa activa">Empresa actual</span>';
    $message = $next === 'Activo' ? '¿Deseas reactivar ' . $name . '? Podrá utilizarse nuevamente en operaciones.' : '¿Deseas desactivar ' . $name . '? Ya no podrá utilizarse en nuevas operaciones, pero su historial se conservará.';
    return '<form class="js-status-form d-inline" action="' . ui_escape(url('actions/cambiar_estado_registro.php')) . '" method="post" data-confirm="' . ui_escape($message) . '">' . form_context() . '<input type="hidden" name="tabla" value="' . ui_escape($module) . '"><input type="hidden" name="id" value="' . (int)$row['id'] . '"><input type="hidden" name="estado" value="' . $next . '"><button class="btn btn-sm ' . ($next === 'Activo' ? 'btn-outline-success' : 'btn-outline-danger') . '" type="submit"><i class="bi ' . ($next === 'Activo' ? 'bi-arrow-clockwise' : 'bi-slash-circle') . '"></i> ' . $verb . '</button></form>';
}
function render_management_index($module, $title, $singular, $rows, $columns, $nameField = 'nombre') {
    echo '<div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="h3 mb-1">' . ui_escape($title) . '</h2><p class="text-muted mb-0">Consulta y administra registros activos e inactivos.</p></div><a href="' . ui_escape(url("pages/$module/form.php")) . '" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Nuevo ' . ui_escape($singular) . '</a></div><div class="card shadow-sm border-0"><div class="card-header bg-white py-3"><h3 class="h6 mb-0">Listado de ' . ui_escape($title) . '</h3></div><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr>';
    foreach ($columns as $label => $field) echo '<th>' . ui_escape($label) . '</th>';
    echo '<th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>';
    if (!$rows) echo '<tr><td colspan="' . (count($columns) + 2) . '" class="text-center text-muted py-4">No hay registros.</td></tr>';
    foreach ($rows as $row) {
        echo '<tr>'; foreach ($columns as $field) { $value=is_callable($field)?$field($row):($row[$field]??''); echo '<td>'.ui_escape($value).'</td>'; }
        $name=$row[$nameField]??$row['razon_social']??'este registro';
        echo '<td>'.management_status_badge($row).'</td><td class="text-end"><div class="management-actions"><a class="btn btn-sm btn-outline-secondary" href="'.ui_escape(url("pages/$module/detalle.php?id=".$row['id'])).'"><i class="bi bi-eye"></i> Ver</a><a class="btn btn-sm btn-outline-primary" href="'.ui_escape(url("pages/$module/form.php?id=".$row['id'])).'"><i class="bi bi-pencil"></i> Editar</a>'.management_status_form($module,$row,$name).'</div></td></tr>';
    }
    echo '</tbody></table></div></div></div>';
}
function render_management_detail($module, $title, $row, $fields, $nameField = 'nombre', $historyCount = 0) {
    $name=$row[$nameField]??$row['razon_social']??$title;
    echo '<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><a class="small text-decoration-none" href="'.ui_escape(url("pages/$module/index.php")).'"><i class="bi bi-arrow-left"></i> Volver al listado</a><h2 class="h3 mt-2 mb-1">'.ui_escape($name).'</h2>'.management_status_badge($row).'</div><div class="management-actions"><a class="btn btn-outline-primary" href="'.ui_escape(url("pages/$module/form.php?id=".$row['id'])).'"><i class="bi bi-pencil"></i> Editar</a>'.management_status_form($module,$row,$name).'</div></div><div class="card shadow-sm border-0"><div class="card-header bg-white py-3"><h3 class="h6 mb-0">Información de '.ui_escape($title).'</h3></div><div class="card-body"><dl class="row management-detail mb-0">';
    foreach ($fields as $label=>$field) { $value=is_callable($field)?$field($row):($row[$field]??''); echo '<dt class="col-sm-4 col-lg-3">'.ui_escape($label).'</dt><dd class="col-sm-8 col-lg-9">'.ui_escape($value!==''?$value:'—').'</dd>'; }
    echo '<dt class="col-sm-4 col-lg-3">Estado</dt><dd class="col-sm-8 col-lg-9">'.management_status_badge($row).'</dd><dt class="col-sm-4 col-lg-3">Historial relacionado</dt><dd class="col-sm-8 col-lg-9">'.(int)$historyCount.' registro(s). La desactivación conserva este historial.</dd></dl></div></div>';
}
