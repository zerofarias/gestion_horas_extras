<?php require APPROOT . '/views/inc/header.php'; ?>
<div class="d-flex align-items-center justify-content-between mb-4">
  <div><h2 class="page-title mb-1">Relojes y sucursales</h2><p class="page-subtitle mb-0">Un reloj puede servir a varias sucursales. Las fichadas sin persona se filtran por este alcance.</p></div>
  <a href="<?php echo URLROOT; ?>/admin/marcacionesTodas" class="btn btn-outline-secondary">Ver marcaciones</a>
</div>
<div class="row g-4">
 <div class="col-lg-7"><div class="card shadow-sm"><div class="card-header"><strong>Dispositivos registrados</strong></div><div class="table-responsive"><table class="table mb-0 align-middle"><thead><tr><th>API / identificador</th><th>Nombre visible</th><th>Sucursales habilitadas</th><th>Estado</th></tr></thead><tbody>
 <?php foreach ($data['devices'] as $device): ?><tr><td><code><?php echo htmlspecialchars($device->external_name); ?></code></td><td><?php echo htmlspecialchars($device->display_name); ?></td><td class="small"><?php echo htmlspecialchars($device->scopes ?: 'Sin asignar'); ?></td><td><?php echo $device->is_active ? 'Activo' : 'Inactivo'; ?></td></tr><?php endforeach; ?>
 <?php if (empty($data['devices'])): ?><tr><td colspan="4" class="text-muted">Todavía no hay relojes. Al sincronizar se crearán automáticamente; también podés cargarlos aquí.</td></tr><?php endif; ?>
 </tbody></table></div></div></div>
 <div class="col-lg-5"><div class="card shadow-sm"><div class="card-header"><strong>Agregar reloj / actualizar alcance</strong></div><div class="card-body"><form method="post" action="<?php echo URLROOT; ?>/admin/saveClockDevice"><?php echo csrf_field(); ?>
 <div class="mb-3"><label class="form-label">Nombre exacto recibido de la API</label><input required class="form-control" name="external_name" placeholder="Ej. ECOFARMA"></div>
 <div class="mb-3"><label class="form-label">Nombre visible</label><input class="form-control" name="display_name" placeholder="Ej. Reloj Ecofarma Central"></div>
 <label class="form-label">Sucursales donde está habilitado</label><select name="branch_ids[]" class="form-select" multiple size="8">
 <?php foreach ($data['branches'] as $branch): ?><option value="<?php echo (int)$branch->id; ?>"><?php echo htmlspecialchars(($branch->company_name ?? '') . ' — ' . $branch->name . ' (' . $branch->locality . ')'); ?></option><?php endforeach; ?>
 </select><div class="form-text mb-3">Usá Ctrl/Cmd para seleccionar varias. Si no se asigna ninguna, el reloj no se adjudica automáticamente a una empresa.</div>
 <div class="form-check mb-3"><input checked class="form-check-input" type="checkbox" name="is_active" id="clock-active"><label class="form-check-label" for="clock-active">Activo</label></div>
 <button class="btn btn-primary">Guardar reloj</button></form></div></div></div>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
