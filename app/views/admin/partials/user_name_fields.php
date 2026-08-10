<?php
/**
 * Nombres y apellidos separados (crear / editar usuario).
 * $nameSource: user, array POST o $data.
 */
$names = resolve_name_fields($nameSource ?? ($data['user'] ?? $data ?? null));
$errFirst = isset($data['errors']['first_name']) ? $data['errors']['first_name'] : '';
$errLast  = isset($data['errors']['last_name']) ? $data['errors']['last_name'] : '';
?>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="first_name" class="form-label">Nombres <span class="text-danger">*</span></label>
        <input type="text" name="first_name" id="first_name"
               class="form-control <?php echo $errFirst ? 'is-invalid' : ''; ?>"
               value="<?php echo htmlspecialchars($names['first_name']); ?>"
               autocomplete="off" required>
        <?php if ($errFirst): ?>
        <div class="invalid-feedback"><?php echo htmlspecialchars($errFirst); ?></div>
        <?php endif; ?>
    </div>
    <div class="col-md-6 mb-3">
        <label for="last_name" class="form-label">Apellidos <span class="text-danger">*</span></label>
        <input type="text" name="last_name" id="last_name"
               class="form-control <?php echo $errLast ? 'is-invalid' : ''; ?>"
               value="<?php echo htmlspecialchars($names['last_name']); ?>"
               autocomplete="off" required>
        <?php if ($errLast): ?>
        <div class="invalid-feedback"><?php echo htmlspecialchars($errLast); ?></div>
        <?php endif; ?>
        <small class="text-muted">Si tiene dos apellidos, escribilos en este campo.</small>
    </div>
</div>
