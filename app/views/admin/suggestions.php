<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/views/admin/suggestions.php (VISTA REDISEÑADA)
// Reemplaza el contenido de tu vista de sugerencias con este nuevo código.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-header bg-dark text-white">
        <h4 class="mb-0"><i class="fas fa-inbox me-2"></i>Buzón de Sugerencias Anónimas</h4>
    </div>
    <div class="card-body" style="background-color: #f8f9fa;">
        <?php if(empty($data['suggestions'])): ?>
            <div class="text-center p-5">
                <i class="fas fa-envelope-open-text fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">El buzón está vacío</h5>
                <p class="text-muted">Cuando los empleados envíen sugerencias, aparecerán aquí.</p>
            </div>
        <?php else: ?>
            <div class="list-group">
                <?php 
                $color_index = 0;
                foreach($data['suggestions'] as $suggestion): 
                    // Se alterna entre 4 colores para los bordes
                    $color_class = 'border-color-' . (($color_index % 4) + 1);
                ?>
                    <div class="suggestion-item <?php echo $color_class; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <p class="mb-1 suggestion-text">
                                <i class="fas fa-quote-left fa-xs me-2 text-muted"></i>
                                <?php echo nl2br(htmlspecialchars($suggestion->suggestion_text)); ?>
                            </p>
                        </div>
                        <small class="suggestion-date">
                            <i class="fas fa-calendar-alt me-1"></i> Recibido el: <?php echo date('d/m/Y \a \l\a\s H:i', strtotime($suggestion->created_at)); ?> hs
                        </small>
                    </div>
                <?php 
                $color_index++;
                endforeach; 
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>