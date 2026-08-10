# Archivo — código huérfano o duplicado

Vistas y scripts movidos aquí en la limpieza post-auditoría. **No están enlazados** desde el router actual.

| Ruta | Motivo |
|------|--------|
| `views/employee/propose_swap.php` | Flujo reemplazado por `request/index` + `shift_swaps` |
| `views/admin/monthly_schedules.php` | Sin controlador |
| `views/admin/schedules.php` | POST a ruta inexistente |
| `views/admin/employee_summary.php` | Sin controlador |
| `views/admin/sync_results.php` | Sin controlador |
| `views/admin/marcaciones.php` | Redirigía a `marcacionesTodas` |
| `views/admin/clockings_report.php` | Reemplazado por `marcacionesTodas` (`clockingsReport` redirige) |
| `cron/cron_sync.php`, `cron/cron_sync copy.php` | Duplicados; `$CLOCKS_CONFIG` no definido en `config.php` |

Para fichaje/sincronización usar `AdminController::sync` y `app/cron` si se configura correctamente.
