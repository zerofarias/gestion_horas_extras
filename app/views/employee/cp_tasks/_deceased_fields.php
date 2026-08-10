<?php
$task = $task ?? ($data['task_type'] ?? null);
$fk = $task ? ($task->form_key ?? '') : '';
$deceasedList = $data['deceased_list'] ?? [];
$extReady = !empty($data['extintos_ready']);
$dupCheck = !isset($data['cp_duplicate_check']) || !empty($data['cp_duplicate_check']);
$taskLabel = $task ? cp_task_display_name($fk, $task->name ?? '') : 'esta tarea';
?>
<?php if (!$extReady || empty($deceasedList)): ?>
<div class="alert alert-warning py-2 small mb-3">
    <i class="fas fa-exclamation-triangle me-1"></i>
    No se pudo cargar el listado de extintos. RRHH debe configurarlo en <strong>Configuración → Casa Paviotti</strong>.
</div>
<?php else: ?>
<div class="emp-form-group">
    <label class="emp-label" for="cp_deceased_select">Extinto</label>
    <select name="deceased_code" id="cp_deceased_select" class="emp-input" required>
        <option value="">— Seleccioná el extinto —</option>
        <?php foreach ($deceasedList as $d):
            $code = (string)($d->code ?? '');
            $name = trim((string)($d->name ?? ''));
            if ($code === '') {
                continue;
            }
        ?>
        <option value="<?php echo htmlspecialchars($code); ?>" data-name="<?php echo htmlspecialchars($name); ?>">
            <?php echo htmlspecialchars($name !== '' ? $name : ('Código ' . $code)); ?>
        </option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="deceased_name" id="cp_deceased_name" value="">
    <p class="form-text text-muted small mb-0 mt-1" id="cp_deceased_hint">
        Últimos <?php echo count($deceasedList); ?> registros del sistema de extintos.
    </p>
    <?php if ($dupCheck): ?>
    <div class="alert alert-danger py-2 small mt-2 d-none" id="cp_deceased_dup_warn" role="alert"></div>
    <?php endif; ?>
</div>
<?php if ($dupCheck): ?>
<script>
(function(){
    var sel = document.getElementById('cp_deceased_select');
    var nameInp = document.getElementById('cp_deceased_name');
    var warn = document.getElementById('cp_deceased_dup_warn');
    var formKey = <?php echo json_script_safe($fk); ?>;
    var taskLabel = <?php echo json_script_safe($taskLabel); ?>;
    var checkUrl = <?php echo json_script_safe(URLROOT . '/cpTask/checkDeceasedDuplicate'); ?>;

    if (!sel || !nameInp) return;

    function syncName() {
        var o = sel.options[sel.selectedIndex];
        nameInp.value = o && o.value ? (o.getAttribute('data-name') || '') : '';
    }

    var form = sel.closest('form');
    var submitBtn = form ? form.querySelector('button[type="submit"]') : null;

    function setSubmitBlocked(block) {
        if (submitBtn) submitBtn.disabled = !!block;
    }

    function checkDup() {
        if (!warn) return;
        var code = sel.value.trim();
        warn.classList.add('d-none');
        warn.textContent = '';
        setSubmitBlocked(false);
        if (!code) return;
        fetch(checkUrl + '?form_key=' + encodeURIComponent(formKey) + '&deceased_code=' + encodeURIComponent(code))
            .then(function(r){ return r.json(); })
            .then(function(j){
                if (j.duplicate) {
                    warn.textContent = j.message || ('Ya cargaste «' + taskLabel + '» para este extinto.');
                    warn.classList.remove('d-none');
                    setSubmitBlocked(true);
                }
            })
            .catch(function(){});
    }

    sel.addEventListener('change', function(){
        syncName();
        checkDup();
    });

    if (form) {
        form.addEventListener('submit', function(ev){
            if (warn && !warn.classList.contains('d-none')) {
                ev.preventDefault();
            }
        });
    }
})();
</script>
<?php endif; ?>
<?php endif; ?>
