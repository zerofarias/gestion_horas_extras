<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo — Adelanto #<?php echo (int)$row->salary_advance_id; ?></title>
    <style>
        body { font-family: Georgia, "Times New Roman", serif; color: #111; margin: 2rem; }
        .receipt { max-width: 720px; margin: 0 auto; border: 2px solid #222; padding: 2rem; }
        h1 { font-size: 1.25rem; text-align: center; margin: 0 0 1.5rem; letter-spacing: .04em; }
        .meta { font-size: .9rem; color: #444; margin-bottom: 1.5rem; }
        .body-text { font-size: 1.05rem; line-height: 1.7; text-align: justify; }
        .amount-box { font-size: 1.35rem; font-weight: bold; text-align: center; margin: 1.5rem 0; padding: .75rem; border: 1px dashed #666; }
        .signatures { display: flex; justify-content: space-between; gap: 2rem; margin-top: 3rem; }
        .sign-line { flex: 1; border-top: 1px solid #333; padding-top: .5rem; text-align: center; font-size: .85rem; }
        .no-print { margin-bottom: 1rem; text-align: right; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()">Imprimir</button>
        <button type="button" onclick="window.close()">Cerrar</button>
    </div>

    <div class="receipt">
        <h1>RECIBO DE DEVOLUCIÓN — ADELANTO DE SUELDO</h1>

        <div class="meta">
            <div><strong>Empresa:</strong> <?php echo htmlspecialchars($company_name); ?></div>
            <div><strong>Adelanto N°:</strong> <?php echo (int)$row->salary_advance_id; ?></div>
            <div><strong>Fecha de emisión:</strong> <?php echo htmlspecialchars($printed_at); ?></div>
        </div>

        <div class="body-text">
            Recibí de <strong><?php echo htmlspecialchars(mb_strtoupper($row->employee_name, 'UTF-8')); ?></strong>
            la suma de
        </div>

        <div class="amount-box"><?php echo salary_advance_format_money($row->amount); ?></div>

        <div class="body-text">
            correspondiente al adelanto de sueldo N° <strong><?php echo (int)$row->salary_advance_id; ?></strong>,
            <strong>cuota <?php echo (int)$row->installment_number; ?> de <?php echo (int)$total_installments; ?></strong>
            <?php if (!empty($is_final)): ?>
                — <strong>cuota final del adelanto</strong>
            <?php endif; ?>.
            <br><br>
            Período de descuento en liquidación: <strong><?php echo htmlspecialchars(salary_advance_month_label($row->due_month)); ?></strong>.
            <br><br>
            Total del adelanto: <?php echo salary_advance_format_money($row->advance_total); ?>.
            Fecha de solicitud original: <?php echo date('d/m/Y', strtotime($row->advance_created_at)); ?>.
            <?php if (!empty($row->is_deducted) && !empty($row->deducted_at)): ?>
            <br><br>
            <em>Descuento registrado en sistema el <?php echo date('d/m/Y H:i', strtotime($row->deducted_at)); ?>.</em>
            <?php endif; ?>
        </div>

        <div class="signatures">
            <div class="sign-line">Firma del empleado</div>
            <div class="sign-line">Firma y sello RRHH</div>
        </div>
    </div>
</body>
</html>
