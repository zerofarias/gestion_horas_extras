<?php
// ----------------------------------------------------------------------
// ARCHIVO 5: app/views/admin/monthly_schedules.php (ACTUALIZADO)
// Ahora, cada celda de la grilla tiene un menú desplegable con los turnos.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
                <table class="table table-bordered table-striped" style="font-size: 0.8rem;">
                    <thead class="table-dark">
                        <tr>
                            <th>Empleado</th>
                            <?php for($i = 1; $i <= $data['days_in_month']; $i++): ?>
                                <th class="text-center"><?php echo $i; ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['users'] as $user): if($user->is_active): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user->full_name); ?></td>
                                <?php for($i = 1; $i <= $data['days_in_month']; $i++): ?>
                                    <td>
                                        <?php $current_schedule = isset($data['schedules'][$user->id][$i]) ? $data['schedules'][$user->id][$i] : ''; ?>
                                        <select class="form-select form-select-sm" name="schedules[<?php echo $user->id; ?>][<?php echo $i; ?>]">
                                            <option value="" <?php echo ($current_schedule == '') ? 'selected' : ''; ?>>-</option>
                                            <option value="FRANCO" <?php echo ($current_schedule == 'FRANCO') ? 'selected' : ''; ?>>FRANCO</option>
                                            <optgroup label="Turnos Predefinidos">
                                                <?php foreach($data['shifts'] as $shift): ?>
                                                    <option value="<?php echo htmlspecialchars($shift->shift_name); ?>" <?php echo ($current_schedule == $shift->shift_name) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($shift->shift_name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            </select>
                                        </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endif; endforeach; ?>
                    </tbody>
                </table>
            </div>
            </form>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
