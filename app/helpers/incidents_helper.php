<?php

/** Tipos de incidencia disponibles para RRHH (clave => etiqueta). */
function employee_incident_types() {
    return [
        'llamado_atencion'   => 'Llamado de atención',
        'sancion'            => 'Sanción',
        'suspension'         => 'Suspensión',
        'telegrama_despido'  => 'Telegrama de despido',
        'otro'               => 'Otro',
    ];
}

function employee_incident_type_label($type) {
    $types = employee_incident_types();
    return $types[$type] ?? 'Otro';
}

function employee_incident_type_badge($type) {
    $map = [
        'llamado_atencion'  => 'warning',
        'sancion'           => 'danger',
        'suspension'        => 'dark',
        'telegrama_despido' => 'danger',
        'otro'              => 'secondary',
    ];
    return $map[$type] ?? 'secondary';
}

function employee_incident_is_valid_type($type) {
    return array_key_exists($type, employee_incident_types());
}

/** Directorio absoluto de adjuntos para un empleado. */
function employee_incident_upload_dir($userId) {
    $uid = (int)$userId;
    uploads_ensure_private_directory('employee_incidents/' . $uid);
    $dir = APPROOT . '/../public/uploads/employee_incidents/' . $uid;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

/** Ruta relativa bajo public/uploads/ para un adjunto almacenado. */
function employee_incident_storage_relative($userId, $storedFilename) {
    $storedFilename = basename((string)$storedFilename);
    if ($storedFilename === '') {
        return '';
    }
    return 'employee_incidents/' . (int)$userId . '/' . $storedFilename;
}

function admin_employee_incident_stream_url($userId, $incidentId) {
    return URLROOT . '/admin/streamEmployeeIncident/' . (int)$userId . '/' . (int)$incidentId;
}

function admin_employee_incident_download_url($userId, $incidentId) {
    return URLROOT . '/admin/downloadEmployeeIncident/' . (int)$userId . '/' . (int)$incidentId;
}

/** @deprecated Usar admin_employee_incident_stream_url */
function employee_incident_attachment_url($userId, $incidentId) {
    return admin_employee_incident_stream_url($userId, $incidentId);
}

/**
 * Procesa adjunto opcional. Devuelve ['path' => ..., 'original' => ...] o null si no hay archivo.
 * Lanza RuntimeException con mensaje legible si falla validación.
 */
function employee_incident_store_upload($userId) {
    if (empty($_FILES['attachment']) || ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES['attachment'];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir el archivo adjunto.');
    }
    $maxBytes = 10 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('El adjunto no puede superar 10 MB.');
    }
    $original = basename((string)($file['name'] ?? 'adjunto'));
    $docMimes = uploads_document_mimes();
    $docMimes['doc'] = ['application/msword'];
    $docMimes['docx'] = ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $valid = uploads_validate_uploaded_file(
        $file,
        ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
        uploads_flat_mimes($docMimes),
        $maxBytes
    );
    if (!$valid['ok']) {
        throw new RuntimeException($valid['message']);
    }
    $stored = 'inc_' . (int)$userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $valid['ext'];
    $dir = employee_incident_upload_dir($userId);
    $dest = $dir . '/' . $stored;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Error al guardar el adjunto en el servidor.');
    }
    return ['path' => $stored, 'original' => $original];
}

function employee_incident_delete_file($userId, $filename) {
    if ($filename === '' || $filename === null) {
        return;
    }
    $path = employee_incident_upload_dir($userId) . '/' . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Sirve el adjunto de una incidencia (requiere permisos admin ya validados).
 */
function employee_incident_send_attachment($userId, $incidentId, $inline = true) {
    $userId = (int)$userId;
    $incidentId = (int)$incidentId;
    $model = new EmployeeIncident();
    if (!$model->isSchemaReady()) {
        http_response_code(404);
        exit;
    }
    $row = $model->getByIdForUser($incidentId, $userId);
    if (!$row || empty($row->attachment_path)) {
        http_response_code(404);
        exit;
    }
    $rel = employee_incident_storage_relative($userId, $row->attachment_path);
    if ($rel === '') {
        http_response_code(404);
        exit;
    }
    $name = $row->attachment_original_name ?: basename((string)$row->attachment_path);
    protected_upload_send($rel, $inline, $name);
}
