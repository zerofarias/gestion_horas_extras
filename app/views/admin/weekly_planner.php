<?php 
require APPROOT . '/views/inc/header.php'; 

function generateEntryHtml($userId, $date, $index, $entry, $shifts) {
    ob_start();
    $type = $entry ? $entry->type : 'shift';
    $color = '#6c757d';
    if ($type == 'shift' && $entry && !empty($entry->color)) { $color = $entry->color; } 
    elseif ($type == 'overtime') { $color = '#ffc107'; }
?>
<div class="schedule-entry type-<?php echo $type; ?>" style="border-left-color: <?php echo htmlspecialchars($color); ?>;">
    <input type="hidden" name="schedules[<?php echo $userId; ?>][<?php echo $date; ?>][<?php echo $index; ?>][type]" value="<?php echo $type; ?>">
    <button type="button" class="btn-close" aria-label="Close" onclick="this.parentElement.remove()"></button>
    <div class="shift-selector" style="<?php echo ($type != 'shift') ? 'display:none;' : ''; ?>">
        <select class="form-select form-select-sm" name="schedules[<?php echo $userId; ?>][<?php echo $date; ?>][<?php echo $index; ?>][shift_id]">
            <?php if (!empty($shifts)): foreach($shifts as $s): ?>
            <option value="<?php echo $s->id; ?>" <?php echo ($entry && $entry->shift_id == $s->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s->shift_name); ?></option>
            <?php endforeach; endif; ?>
        </select>
    </div>
    <div class="time-inputs" style="<?php echo ($type == 'shift') ? 'display:none;' : ''; ?>">
        <input type="time" class="form-control form-control-sm" name="schedules[<?php echo $userId; ?>][<?php echo $date; ?>][<?php echo $index; ?>][start_time]" value="<?php echo $entry ? $entry->start_time : ''; ?>">
        <input type="time" class="form-control form-control-sm mt-1" name="schedules[<?php echo $userId; ?>][<?php echo $date; ?>][<?php echo $index; ?>][end_time]" value="<?php echo $entry ? $entry->end_time : ''; ?>">
    </div>
    <input type="text" class="form-control form-control-sm mt-1" name="schedules[<?php echo $userId; ?>][<?php echo $date; ?>][<?php echo $index; ?>][notes]" placeholder="Notas..." value="<?php echo $entry ? htmlspecialchars($entry->notes) : ''; ?>">
</div>
<?php return ob_get_clean(); } ?>

<style>
    .planner-table th, .planner-table td { vertical-align: top; text-align: center; padding: 0.5rem !important; }
    .planner-table .user-col { text-align: left; width: 230px; font-weight: 500; background-color: #f8f9fa; position: sticky; left: 0; z-index: 2; }
    .planner-table thead th { position: sticky; top: 0; z-index: 3; background-color: #e9ecef; }
    .is-holiday { background-color: #fff0f5 !important; }
    .hours-summary .badge { margin-top: 5px; font-size: 0.9em; padding: 0.4em 0.7em; }
    .request-badge { display: block; border-left: 5px solid; padding: 4px 8px; margin-bottom: 8px; border-radius: 5px; text-align: left; font-weight: 500; font-size: 0.8em; }
    .schedule-block { padding: 6px 8px; margin-bottom: 5px; border-radius: 5px; text-align: left; color: #fff; font-weight: 500; font-size: 0.85em; }
    .day-cell .entries-container { display: none; }
    .day-cell.is-editing .entries-container { display: block; }
    .day-cell.is-editing .blocks-container { display: none; }
    .cell-actions { margin-top: 8px; position: relative; }
    .btn-icon { width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; margin: 0 3px; border: none; color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.15); transition: all 0.2s ease; }
    .btn-icon:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
    .edit-btn { background-color: #0d6efd; }
    .btn-success { background-color: #198754; }
    .copy-btn { background-color: #6c757d; }
    .add-entry-menu { display: none; position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); margin-bottom: 10px; background: white; border-radius: 50px; z-index: 10; padding: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); gap: 5px; }
    .add-entry-menu.is-active { display: flex; }
    .planner-nav { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
    .date-picker-container { position: relative; }
    #date-picker-icon { cursor: pointer; font-size: 1.5rem; color: #6c757d; }
    #date-picker { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); opacity: 0; width: 40px; height: 40px; cursor: pointer; }
    .template-form { display: flex; gap: 10px; align-items: center; }
</style>

<div class="planner-nav mb-3">
    <a href="<?php echo URLROOT; ?>/admin/weeklyPlanner?week=<?php echo $data['prev_week_string']; ?>" class="btn btn-outline-secondary">&laquo; Semana Anterior</a>
    
    <form action="<?php echo URLROOT; ?>/admin/applyTemplate" method="post" class="template-form">
        <input type="hidden" name="target_week" value="<?php echo $data['current_week_string']; ?>">
        <select name="template_id" class="form-select form-select-sm" required>
            <option value="" disabled selected>Aplicar Plantilla...</option>
            <?php if(!empty($data['templates'])): ?>
                <?php foreach($data['templates'] as $template): ?>
                    <option value="<?php echo $template->id; ?>"><?php echo htmlspecialchars($template->template_name); ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Aplicar</button>
    </form>
    
    <div class="d-flex align-items-center">
        <div class="date-picker-container">
            <label for="date-picker" id="date-picker-icon" title="Seleccionar fecha"><i class="fas fa-calendar-alt"></i></label>
            <input type="date" id="date-picker">
        </div>
        <a href="<?php echo URLROOT; ?>/admin/weeklyPlanner?week=<?php echo $data['next_week_string']; ?>" class="btn btn-outline-secondary ms-2">Semana Siguiente &raquo;</a>
    </div>
</div>

<form id="planner-form" action="<?php echo URLROOT; ?>/admin/weeklyPlanner" method="post">
    <input type="hidden" name="current_week" value="<?php echo $data['current_week_string']; ?>">
    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered planner-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="user-col">Empleado / Horas</th>
                            <?php foreach($data['week_dates'] as $day): ?>
                                <th class="<?php echo isset($data['holidays'][$day['full_date']]) ? 'is-holiday' : ''; ?>"><?php echo $day['display']; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['users'] as $user): ?>
                            <tr data-user-row-id="<?php echo $user->id; ?>">
                                <td class="user-col">
                                    <?php echo htmlspecialchars($user->full_name); ?>
                                    <div class="hours-summary">
                                        <?php
                                            // --- LÓGICA DE ALERTA DE HORAS EXTRAS ---
                                            $weekly_total = $data['weekly_totals'][$user->id];
                                            $limit = $user->weekly_hour_limit;
                                            $badge_class = 'bg-primary'; // Color por defecto
                                            if ($limit > 0 && $weekly_total > $limit) {
                                                $badge_class = 'bg-danger'; // Límite excedido
                                            } elseif ($limit > 0 && $weekly_total > $limit * 0.9) {
                                                $badge_class = 'bg-warning text-dark'; // Cerca del límite
                                            }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?> weekly-total" title="Límite Semanal: <?php echo $limit; ?> hs">
                                            Sem: <?php echo number_format($weekly_total, 2); ?> hs
                                        </span>

                                        <span class="badge bg-info monthly-total-<?php echo $data['month1_num']; ?>"><?php echo $data['nombres_mes'][$data['month1_num']]; ?>: <?php echo number_format($data['monthly_totals'][$user->id][$data['month1_num']], 2); ?> hs</span>
                                        <?php if($data['month1_num'] != $data['month2_num']): ?>
                                        <span class="badge bg-secondary monthly-total-<?php echo $data['month2_num']; ?>"><?php echo $data['nombres_mes'][$data['month2_num']]; ?>: <?php echo number_format($data['monthly_totals'][$user->id][$data['month2_num']], 2); ?> hs</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <?php foreach($data['week_dates'] as $day_index => $day): 
                                    $date = $day['full_date'];
                                    $entries = isset($data['schedules'][$user->id][$date]) ? $data['schedules'][$user->id][$date] : [];
                                    $request = isset($data['requests'][$user->id][$date]) ? $data['requests'][$user->id][$date] : null;
                                    if ($request && $request->status !== 'Aprobado') { $request = null; }
                                ?>
                                    <td class="day-cell <?php echo isset($data['holidays'][$date]) ? 'is-holiday' : ''; ?>" data-user-id="<?php echo $user->id; ?>" data-date="<?php echo $date; ?>" data-day-index="<?php echo $day_index; ?>">
                                        <?php if($request): ?>
                                            <div class="request-badge" style="border-left-color: <?php echo htmlspecialchars($request->color); ?>; background-color: <?php echo htmlspecialchars($request->color) . '20'; ?>; color: #333;">
                                                <strong><?php echo htmlspecialchars($request->type_name); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        <div class="blocks-container">
                                            <?php if(empty($entries)): ?><span class="text-muted fst-italic">Libre</span><?php else: ?>
                                                <?php foreach($entries as $entry): 
                                                    $color = '#6c757d'; $name_display = 'Personalizado';
                                                    if ($entry->type == 'shift' && !empty($entry->shift_name)) { $color = $entry->color; $name_display = $entry->shift_name; } 
                                                    elseif ($entry->type == 'overtime') { $color = '#ffc107'; $name_display = 'Horas Extras'; }
                                                ?>
                                                <div class="schedule-block" style="background-color: <?php echo htmlspecialchars($color); ?>;">
                                                    <span><?php echo htmlspecialchars($name_display); ?></span>
                                                    <?php if(!empty($entry->notes)): ?><small class="block-notes d-block"><?php echo htmlspecialchars($entry->notes); ?></small><?php endif; ?>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="entries-container"><?php foreach($entries as $index => $entry): ?><?php echo generateEntryHtml($user->id, $date, $index, $entry, $data['shifts']); ?><?php endforeach; ?></div>
                                        <div class="cell-actions">
                                            <button type="button" class="btn btn-sm btn-icon edit-btn" onclick="toggleEditMode(this)" title="Editar Día"><i class="fas fa-pencil-alt"></i></button>
                                            <button type="button" class="btn btn-sm btn-icon copy-btn" onclick="copyPreviousDay(this)" title="Copiar Día Anterior"><i class="fas fa-copy"></i></button>
                                            <div class="add-entry-menu">
                                                <button type="button" class="btn btn-sm btn-icon btn-primary" onclick="addEntry(this, 'shift')" title="Añadir Turno"><i class="fas fa-tags"></i></button>
                                                <button type="button" class="btn btn-sm btn-icon btn-secondary" onclick="addEntry(this, 'custom')" title="Añadir Personalizado"><i class="fas fa-user-clock"></i></button>
                                                <button type="button" class="btn btn-sm btn-icon btn-warning" onclick="addEntry(this, 'overtime')" title="Añadir Horas Extras"><i class="fas fa-plus-circle"></i></button>
                                            </div>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="text-center mt-3"><button type="submit" class="btn btn-primary btn-lg">Guardar Toda la Semana</button></div>
</form>

<div id="templates" style="display: none;"><?php echo generateEntryHtml('{userId}', '{date}', '{index}', null, $data['shifts']); ?></div>

<script>
var monthNames = <?php echo json_encode($data['nombres_mes']); ?>;

document.getElementById('date-picker').addEventListener('change', function(e) {
    if(!e.target.value) return;
    var selectedDate = new Date(e.target.value);
    selectedDate.setMinutes(selectedDate.getMinutes() + selectedDate.getTimezoneOffset());
    
    Date.prototype.getWeek = function() {
        var date = new Date(this.getTime());
        date.setHours(0, 0, 0, 0);
        date.setDate(date.getDate() + 3 - (date.getDay() + 6) % 7);
        var week1 = new Date(date.getFullYear(), 0, 4);
        return 1 + Math.round(((date.getTime() - week1.getTime()) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
    }

    var year = selectedDate.getFullYear();
    var week = selectedDate.getWeek();
    var weekString = String(week).padStart(2, '0');

    var newUrl = '<?php echo URLROOT; ?>/admin/weeklyPlanner?week=' + year + '-W' + weekString;
    window.location.href = newUrl;
});

function toggleEditMode(button) {
    var cell = button.closest('.day-cell');
    var isEditing = cell.classList.toggle('is-editing');
    var copyBtn = button.nextElementSibling;
    var addMenu = button.parentElement.querySelector('.add-entry-menu');

    if (isEditing) {
        button.innerHTML = '<i class="fas fa-save"></i>';
        button.title = 'Guardar Día';
        button.classList.remove('edit-btn');
        button.classList.add('btn-success');
        button.onclick = function() { saveDay(this); };
        copyBtn.style.display = 'none';
        addMenu.classList.add('is-active');
    } else {
        button.innerHTML = '<i class="fas fa-pencil-alt"></i>';
        button.title = 'Editar Día';
        button.classList.remove('btn-success');
        button.classList.add('edit-btn');
        button.onclick = function() { toggleEditMode(this); };
        copyBtn.style.display = 'inline-flex';
        addMenu.classList.remove('is-active');
    }
}

function saveDay(button) {
    var cell = button.closest('.day-cell');
    var form = document.getElementById('planner-form');
    var formData = new FormData(form);
    
    formData.append('is_ajax', '1');
    formData.append('user_id', cell.dataset.userId);
    formData.append('date', cell.dataset.date);

    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('<?php echo URLROOT; ?>/admin/saveDayAjax', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload(); 
        } else {
            alert('Error: ' + (data.message || 'Ocurrió un error desconocido.'));
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-save"></i>';
        }
    })
    .catch(error => {
        console.error('Error en la petición AJAX:', error);
        alert('Ocurrió un error de conexión. Inténtalo de nuevo.');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-save"></i>';
    });
}

function copyPreviousDay(button) {
    var cell = button.closest('.day-cell');
    var dayIndex = parseInt(cell.dataset.dayIndex);
    if (dayIndex === 0) { alert('No hay día anterior para copiar.'); return; }
    
    var prevCell = cell.parentElement.children[dayIndex];
    if (prevCell.querySelector('.request-block')) { alert('No se puede copiar desde un día con una solicitud (ej. vacaciones).'); return; }

    var sourceContainer = prevCell.querySelector('.entries-container');
    var targetContainer = cell.querySelector('.entries-container');
    if (!sourceContainer) return;
    
    targetContainer.innerHTML = sourceContainer.innerHTML.replace(new RegExp(prevCell.dataset.date, 'g'), cell.dataset.date);
    
    if (!cell.classList.contains('is-editing')) {
        toggleEditMode(cell.querySelector('.edit-btn'));
    }
}

function addEntry(button, type) {
    var menu = button.closest('.add-entry-menu');
    var cell = button.closest('.day-cell');
    var container = cell.querySelector('.entries-container');
    var userId = cell.dataset.userId;
    var date = cell.dataset.date;
    var newIndex = new Date().getTime();
    var template = document.getElementById('templates').innerHTML;
    var newHtml = template.replace(/{userId}/g, userId).replace(/{date}/g, date).replace(/{index}/g, newIndex);
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = newHtml;
    var newEntry = tempDiv.children[0];
    
    if (!newEntry) return;

    newEntry.className = 'schedule-entry type-' + type;
    newEntry.querySelector('input[type=hidden]').value = type;
    if (type === 'shift') {
        newEntry.querySelector('.time-inputs').style.display = 'none';
        newEntry.querySelector('.shift-selector').style.display = 'block';
    } else {
        newEntry.querySelector('.shift-selector').style.display = 'none';
        newEntry.querySelector('.time-inputs').style.display = 'block';
    }
    container.appendChild(newEntry);
    if(menu) { menu.classList.remove('is-active'); }
}
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
