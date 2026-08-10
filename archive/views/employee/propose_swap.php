<?php
require APPROOT . '/views/inc/header.php';
$viewData = $data ?? [];
$otherEmployees = $viewData['otherEmployees'] ?? [];
$proposerScheduleId = $viewData['proposer_schedule_id'] ?? '';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Proponer Intercambio de Turno</h5>
            </div>
            <div class="card-body p-4">
                <?php if(isset($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
                <?php endif; ?>
                <?php if(isset($_SESSION['flash_error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
                <?php endif; ?>

                <form action="<?php echo URLROOT; ?>/employee/proposeSwap" method="post">
                    <input type="hidden" name="proposer_schedule_id" value="<?php echo htmlspecialchars($proposerScheduleId); ?>">

                    <div class="mb-3">
                        <label for="accepter_user_id" class="form-label">Intercambiar con:</label>
                        <select name="accepter_user_id" id="accepter_user_id" class="form-select" required>
                            <option value="">-- Selecciona un empleado --</option>
                            <?php foreach($otherEmployees as $employee): ?>
                                <option value="<?php echo $employee->id; ?>"><?php echo htmlspecialchars($employee->full_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="accepter_schedule_id" class="form-label">Turno que quieres tomar:</label>
                        <select name="accepter_schedule_id" id="accepter_schedule_id" class="form-select" required>
                            <option value="">-- Selecciona un turno --</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas (opcional):</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Enviar Solicitud de Intercambio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const accepterUserIdSelect = document.getElementById('accepter_user_id');
    const accepterScheduleIdSelect = document.getElementById('accepter_schedule_id');

    accepterUserIdSelect.addEventListener('change', function() {
        const userId = this.value;
        accepterScheduleIdSelect.innerHTML = '<option value="">-- Cargando turnos --</option>';

        if (userId) {
            fetch(`<?php echo URLROOT; ?>/employee/getEmployeeSchedulesAjax/${userId}`)
                .then(response => response.json())
                .then(schedules => {
                    accepterScheduleIdSelect.innerHTML = '<option value="">-- Selecciona un turno --</option>';
                    if (Object.keys(schedules).length > 0) {
                        for (const date in schedules) {
                            schedules[date].forEach(entry => {
                                const option = document.createElement('option');
                                option.value = entry.id;
                                option.textContent = `${date}: ${entry.type === 'shift' ? entry.shift_name : (entry.type === 'overtime' ? 'Horas Extras' : 'Personalizado')} (${entry.start_time} - ${entry.end_time})`;
                                accepterScheduleIdSelect.appendChild(option);
                            });
                        }
                    } else {
                        accepterScheduleIdSelect.innerHTML = '<option value="">No hay turnos disponibles para este empleado.</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching schedules:', error);
                    accepterScheduleIdSelect.innerHTML = '<option value="">Error al cargar turnos.</option>';
                });
        } else {
            accepterScheduleIdSelect.innerHTML = '<option value="">-- Selecciona un empleado primero --</option>';
        }
    });
});
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>