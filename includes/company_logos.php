<?php
const COMPANY_LOGO_MAX_BYTES = 2097152;

function company_logo_directory() { return dirname(__DIR__) . '/assets/uploads/logos'; }
function valid_company_logo_reference($reference) {
    return is_string($reference) && (bool)preg_match('~^assets/uploads/logos/company-[1-9][0-9]*-[a-f0-9]{16}\.(?:png|jpg|webp)$~D', $reference);
}
function company_logo_path($reference, $directory = null) {
    if (!valid_company_logo_reference($reference)) return null;
    return rtrim($directory ?? company_logo_directory(), '/\\') . DIRECTORY_SEPARATOR . basename($reference);
}
function company_logo_url($company) {
    $reference = $company['logo'] ?? '';
    $path = company_logo_path($reference);
    return $path && is_file($path) ? url($reference) : null;
}
function delete_company_logo($reference, $directory = null) {
    $path = company_logo_path($reference, $directory);
    return !$path || !is_file($path) || unlink($path);
}
function store_company_logo($file, $companyId, $directory = null, $requireUploaded = true) {
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException('No se pudo recibir el logo. Intenta nuevamente.');
    $temporary = $file['tmp_name'] ?? '';
    $size = $file['size'] ?? -1;
    if (!is_string($temporary) || !is_file($temporary) || ($requireUploaded && !is_uploaded_file($temporary))) throw new InvalidArgumentException('El archivo del logo no es válido.');
    if (!is_int($size) && !ctype_digit((string)$size)) throw new InvalidArgumentException('No se pudo validar el tamaño del logo.');
    $size = (int)$size;
    if ($size <= 0 || $size > COMPANY_LOGO_MAX_BYTES) throw new InvalidArgumentException('El logo debe pesar como máximo 2 MB.');
    $image = @getimagesize($temporary);
    $mime = $image['mime'] ?? '';
    $extensions = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
    $originalExtension = strtolower(pathinfo(is_string($file['name'] ?? null) ? $file['name'] : '', PATHINFO_EXTENSION));
    if (!in_array($originalExtension, ['png','jpg','jpeg','webp'], true) || !isset($extensions[$mime])) throw new InvalidArgumentException('El logo debe ser una imagen PNG, JPG, JPEG o WEBP válida.');
    $directory = rtrim($directory ?? company_logo_directory(), '/\\');
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('No se pudo preparar la carpeta de logos.');
    $filename = 'company-' . (int)$companyId . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    $destination = $directory . DIRECTORY_SEPARATOR . $filename;
    $stored = $requireUploaded ? move_uploaded_file($temporary, $destination) : copy($temporary, $destination);
    if (!$stored) throw new RuntimeException('No se pudo guardar el logo.');
    return 'assets/uploads/logos/' . $filename;
}
function save_company_with_logo($input, $file = null, $directory = null, $requireUploaded = true) {
    unset($input['logo']); // Never trust a filename supplied as regular form text.
    $id = input_text($input, 'id');
    $existing = $id !== '' ? owned_record('empresas', $id) : null;
    $companyId = $existing['id'] ?? next_id('empresas');
    $remove = ($input['quitar_logo'] ?? '') === '1';
    $hasFile = is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if ($remove && $hasFile) throw new InvalidArgumentException('Elige entre reemplazar o quitar el logo, no ambas opciones.');
    $newReference = null;
    try {
        if ($hasFile) { $newReference = store_company_logo($file, $companyId, $directory, $requireUploaded); $input['logo'] = $newReference; }
        elseif ($remove) $input['logo'] = '';
        $result = save_company($input);
    } catch (Throwable $e) {
        if ($newReference) delete_company_logo($newReference, $directory);
        throw $e;
    }
    $oldReference = $existing['logo'] ?? '';
    if (($remove || $newReference) && $oldReference !== '' && $oldReference !== ($result['record']['logo'] ?? '')) delete_company_logo($oldReference, $directory);
    return $result;
}
