<?php
/**
 * Texto de ayuda: importación API Relojes (sync, marcaciones vacías, etc.).
 * Opcional: $compact = true para versión corta.
 */
$compact = !empty($compact);
?>
<div class="small mb-0">
<?php if (!$compact): ?>
<ul class="mb-0 ps-3">
    <li>Se consulta la <strong>API Relojes</strong> con <strong>JWT</strong> y se importan <strong>todas</strong> las marcaciones al registro general.</li>
    <li>Las fichadas de empleados con <strong>ID de reloj mapeado</strong> también actualizan horarios (pares <strong>entrada P10 → salida P20</strong>).</li>
    <li>Al finalizar se <strong>recalculan</strong> todos los días del rango importado. Los <strong>duplicados se omiten</strong>.</li>
    <li>Los nombres enriquecidos (<code>nombreApellido</code>) vienen en cada marcación; si faltan, se resuelven con <code>/api/v1/legajos</code>.</li>
    <li><strong>Servicios Sociales:</strong> las fichadas deben bajarse antes en el panel API Relojes (reloj <em>Hikvision SS</em> / <em>SOCIALES</em>).</li>
    <li>Para vincular horas extras y turnos, asociá el <strong>ID de reloj</strong> en
        <a href="<?php echo URLROOT; ?>/admin/mapeoApi" style="color:var(--clr-primary);">Mapeo de Relojes</a>.</li>
</ul>
<?php else: ?>
    Importación vía API Relojes (JWT). Empleados mapeados actualizan horarios P10→P20.
    <a href="<?php echo URLROOT; ?>/admin/sync">Sincronizar</a> ·
    <a href="<?php echo URLROOT; ?>/admin/mapeoApi">Mapear legajos</a>
<?php endif; ?>
</div>
