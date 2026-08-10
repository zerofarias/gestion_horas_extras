# Guía maestra — Paviotti RRHH (`gestion_horas_extras`)

**Documento de referencia obligatoria** para desarrolladores y agentes de IA.  
Antes de modificar código, leer las secciones relevantes. Tras un cambio, verificar las dependencias listadas en §6 y §7.

| Documentos relacionados | Uso |
|----------------------|-----|
| `MIGRATIONS.md` | Orden SQL, tablas nuevas |
| `app/config/config.php` | URLs, API, tolerancias asistencia |
| `.cursor/rules/guia-maestra.mdc` | Regla Cursor `alwaysApply` |

---

## 1. Qué es este sistema (negocio)

**Paviotti RRHH** es software de **gestión de recursos humanos** para un **grupo de empresas** (Ecofarma, Servicios Sociales, AMSSI, etc.). No es solo control de horas extras: es el **expediente laboral operativo** del empleado.

### Objetivos de negocio

1. **Planificar** turnos y calendario (qué debería hacer el empleado cada día).
2. **Contrastar** con **marcaciones** de relojes biométricos (qué hizo en la realidad).
3. **Registrar** solicitudes (licencias, permisos), **vacaciones** por convenio, **incidencias** disciplinarias, **horas extras** y **capacitación**.
4. **Unificar** la lectura en vistas de admin (calendario mensual, ficha empleado) — meta: **un solo roadmap por persona y día**.

### Entidades de negocio clave

| Entidad | Tabla / origen | Significado |
|---------|----------------|-------------|
| **Empresa** | `companies` | Ámbito de datos; admin opera una empresa activa en sesión |
| **Empleado** | `users` (`role = empleado`) | Persona con legajo en RRHH |
| **Admin** | `users` (`role = admin`) | RRHH; ve empleados de su `company_id` (o cambia empresa activa) |
| **Baja** | `users.is_active = 0` | Despido, renuncia, etc. (toggle en Usuarios). **No** es lo mismo que una incidencia “suspensión” |
| **Turno planificado** | `employee_schedules` | Bloques por día (`shift`, `custom`, `overtime`, `vacation`, `leave`) |
| **Marcación** | `marcaciones_cache` + `clock_events` | Fichada real (API relojes → caché → eventos por usuario mapeado) |
| **Asistencia del día** | `attendance_day_summary` | Resultado **plan vs fichado** (tarde, ausente, OK, etc.) |
| **Justificación** | `attendance_justifications` | RRHH explica/desbloquea un día (salida anticipada, etc.) |
| **Solicitud** | `requests` + `request_types` | Flujo empleado → aprobación admin (puede alimentar plan o licencia) |
| **Vacaciones (saldo)** | `vacation_balance_periods` | Períodos Oct–Sep, días pendientes, consumo **FIFO** |
| **Convenio** | `collective_agreements` | Default por empresa + override en `users.agreement_id` |
| **Incidencia** | `employee_incidents` | Registro disciplinario (llamado, sanción, **suspensión**, telegrama) — **histórico**, no automatiza baja |
| **Cambio de turno** | `shift_swaps` | Entre dos empleados, pendiente/aprobado |
| **Hora extra** | `overtime_entries` | Carga y cierre mensual (Ecofarma, Servicios Sociales, etc.) |
| **Extra por tarea (Casa Paviotti)** | `cp_task_entries`, `cp_external_entries` | Importe en pesos por actividad fúnebre; ver §8 |
| **Comisiones Ecofarma** | API externa Plex | Solo admin; facturación ACOS por operador (no es tabla local) |

---

## 2. Roles y permisos

### Admin (`role = admin`)

- Menú completo en `app/views/inc/header.php` (sección Personal, Operaciones, Sistema).
- `AdminController` exige `isStaffAdmin()`; acciones sensibles usan `requireAdminOnly()`.
- **Empresa activa:** `$_SESSION['user_company_id']` — funciones `requireAdminCompany()`, `adminResolveUser()` en `app/helpers/auth_helper.php`.
- Filtra listados por `company_id` salvo pantallas globales (ej. marcaciones de todos los relojes).

### Supervisor (`role = supervisor`)

- Menú reducido: calendario de su equipo, solicitudes, asistencia, alertas RRHH.
- Alcance por `area_id` del usuario supervisor (misma empresa). Helpers: `supervisorCanAccessUser()`, `filterEmployeesForStaff()`.
- Migración: `migration_role_supervisor.sql`. Asignar rol y área en Usuarios.

### Empleado (`role = empleado`)

- Menú “Mi Espacio”: inicio, **Mi mes** (`/employee/miMes`), horarios, solicitudes, sugerencias, cursos, recibos, **Reconocer compañeros** (`/peerStar`), **Encuestas** (`/survey`).
- **Casa Paviotti** (`extras_mode = casapav_tasks`): menú **Extras CP** (`/cpTask/index`) en lugar de horas extras 50%/100%. Acceso rápido en inicio empleado. `EmployeeController::dashboard` y `add` redirigen a `cpTask/index`.
- **Resto del grupo:** menú **Horas Extras** (`/employee/dashboard` → `overtime_entries`).
- `EmployeeController`, `RequestController`, `TrainingController` — redirigen si no es empleado.
- **Solo ve sus propios datos** (user_id de sesión).

### Estado activo (`users.is_active`)

| Valor | Efecto típico en código |
|-------|-------------------------|
| `1` | Aparece en listados, planificador, liquidación vacaciones masiva, asignación cursos/tareas |
| `0` | Badge “Inactivo” en usuarios; **excluido** de `getActiveEmployeesForVacationLiquidation()` y muchos contadores de activos |

**Importante:** al registrar incidencia `suspension` se puede opcionalmente **desactivar usuario** y **notificar por email** a admins (checkboxes en ficha). Sin esas opciones, la incidencia sigue siendo solo documentación.

### Reconocimiento entre pares (estrellas)

- Saldo **separado** de cursos/premios (`peer_star_scores`, `peer_star_ledger`). Migración #28.
- Empleados: dar/quitar 1–5 pts, anónimo entre pares, límite neto ±5 por persona por mes.
- Admin: ranking por empresa/área (`/peerStarAdmin/ranking`).

### Encuestas

- Admin: hub Notificaciones → Encuestas (`/surveyAdmin`), builder de preguntas, anónimas o identificadas. Migración #29 (tras #20 notificaciones).
- Empleado: `/survey` — campana con tipo `survey`.

### Planificador — repetir horario

- Por empleado: botón **Repetir** en planificador semanal → copia patrón de la semana visible a un rango de fechas (`/admin/applySchedulePattern`).

---

## 3. Arquitectura técnica

### Stack

- **PHP** MVC custom (no Laravel).
- **MySQL** — nombre habitual `paviotti_lanaturaleza` (ver `config.php`).
- **Front:** Bootstrap 5, jQuery, DataTables, FullCalendar.
- **Entrada:** `public/index.php` → `app/bootstrap.php` → `app/Core.php` (router).

### Enrutamiento

```
URL: /controlador/metodo/param1/param2
Archivo: app/controllers/{Controlador}Controller.php
Vista: app/views/{ruta}.php
```

Ejemplos:

| URL | Controlador | Vista / notas |
|-----|-------------|---------------|
| `/admin/calendar` | `AdminController::calendar` | `admin/calendar.php` |
| `/admin/employeeProfile/5` | `AdminController::employeeProfile` | `admin/employee_profile.php` |
| `/admin/marcacionesTodas` | `AdminController::marcacionesTodas` | Caché global API |
| `/vacationAdmin/agreements` | `VacationAdminController` | Convenios |
| `/ecofarma/index` | `EcofarmaController` | Comisiones API Plex |
| `/employee/index` | `EmployeeController` | Portal empleado |
| `/cpTask/index` | `CpTaskController` | Extras CP — empleado (mobile-first) |
| `/cpTask/create/{form_key}` | `CpTaskController::create` | Formulario por tarea |
| `/cpTaskAdmin/pending` | `CpTaskAdminController` | Pendientes + cierre (admin CP) |

### Carga de clases

`app/bootstrap.php` — autoload en `controllers/`, `models/`, `services/`, helpers en `helpers/*.php`.

### Patrones obligatorios

- **CSRF:** `csrf_field()` / `csrf_verify()` en POST.
- **Flash:** `$_SESSION['flash_success']`, `flash_error`.
- **Vistas admin:** `extract($data)` en `view()` — variables sueltas, no siempre `$data['key']`.
- **PDO:** no repetir el mismo nombre de placeholder en una query MySQL (error HY093). Usar nombres únicos o `?` posicionales.
- **Migraciones:** solo SQL en raíz; orden en `MIGRATIONS.md`; sin runner automático.

---

## 4. Mapa de módulos → archivos

### 4.1 Núcleo empleado / admin

| Módulo | Controlador | Modelos / servicios | Vistas principales |
|--------|-------------|---------------------|-------------------|
| Usuarios | `AdminController` | `User`, `Company`, `Area` | `admin/users.php`, `edit_user.php`, partials empleo |
| Ficha empleado | `AdminController::employeeProfile` | `Schedule`, `User`, incidencias, vacaciones | `employee_profile.php` + tabs |
| Calendario mensual | `AdminController::calendar` | `CalendarMonthService` | `admin/calendar.php`, `partials/calendar_day_detail.php` |
| Planificador | `AdminController::weeklyPlanner` | `WorkSchedule`, `Shift` | `weekly_planner.php` |
| Solicitudes admin | `AdminController::requests` | `Request`, `ShiftSwap` | `requests.php` |
| Solicitudes empleado | `RequestController` | `Request` | `employee/requests.php` |

### 4.2 Marcaciones y asistencia

| Módulo | Controlador | Modelos / servicios | Vistas |
|--------|-------------|---------------------|--------|
| Sync API | `AdminController::sync`, `runApiSync` | `SyncModel`, `ClockApiClient`, `MarcacionesCache` | `sync.php`, `mapeo_api.php` |
| Todas las marcaciones | `AdminController::marcacionesTodas` | `MarcacionesCache` | `marcaciones_todas.php` |
| Mapeo reloj ↔ usuario | `AdminController::mapeoApi` | `User::user_clock_mappings` | `mapeo_api.php` |
| Plan vs real | (interno) | `PlanVsActualService`, `AttendanceDaySummary` | Usado por calendario |
| Justificar día | `AdminController::saveAttendanceJustification` | `AttendanceJustification` | Modal en `calendar.php` |

**Helpers:** `attendance_helper.php`, `marcaciones_helper.php`, `calendar_events_helper.php`.

### 4.3 Vacaciones y convenios

| Módulo | Controlador | Servicios | Vistas |
|--------|-------------|-----------|--------|
| Convenios ABM | `VacationAdminController` | `CollectiveAgreement`, `VacationEntitlementService` | `vacation/agreements.php`, `edit_agreement.php` |
| Setup / liquidación | `VacationAdminController` | `VacationEntitlementService`, `VacationLedgerService` | `vacation/setup.php`, `liquidate_batch.php` |
| Consumo al aprobar | `AdminController` (requests) | `VacationLedgerService::applyTake` | — |

**Helper:** `vacation_helper.php` — `vacation_module_ready()`, tipos planificador.

**Migraciones:** #22 convenios, #23 `vacation`/`leave` en schedules, #24 `probation_start_date`.

### 4.4 Incidencias disciplinarias

| Archivo | Rol |
|---------|-----|
| `EmployeeIncident` model | CRUD por `user_id` |
| `incidents_helper.php` | Tipos: llamado, sanción, **suspensión**, telegrama, otro |
| `employee_profile_incidents_tab.php` | Tab en ficha |
| `CalendarMonthService` | `incidentMap` por día en calendario |

### 4.5 Horas extras, capacitación, notificaciones, Ecofarma

| Módulo | Controlador |
|--------|-------------|
| Horas extras | `AdminController`, `EmployeeController` |
| Cursos / estrellas | `TrainingAdminController`, `TrainingController` |
| Notificaciones / recibos | `NotificationsAdminController` |
| Comisiones Ecofarma | `EcofarmaController` + `ClockApiClient` (endpoints `/api/v1/ecofarma/*`) |

### 4.6 Casa Paviotti — extras por tarea (`cp_*`)

| Capa | Archivos |
|------|----------|
| Controladores | `CpTaskController.php` (empleado), `CpTaskAdminController.php` (admin/supervisor) |
| Modelo | `CpTask.php` |
| Servicios | `CpTaskPricingService.php`, `DeceasedLookupService.php` (opcional, `EXTINTOS_DB_*`) |
| Helper | `cp_tasks_helper.php` — flags, dinero, catálogo UI, validación fechas |
| Vistas empleado | `app/views/employee/cp_tasks/` (`index.php`, `form_*.php`, partials `_form_*`) |
| Vistas admin | `app/views/admin/cp_tasks/` |
| Estilos | `public/css/style.css` — bloque `.cp-task-*`, `.cp-pending-*` |
| CLI | `scripts/import_cp_legacy.php` |
| Menú | `header.php` — bloques condicionales `company_uses_casapav_tasks()` / `current_user_uses_casapav_tasks()` |

**Feature flags:** `cp_tasks_is_ready()`, `company_uses_casapav_tasks($companyId)`, `require_cp_employee()`, `require_cp_staff()`.

**Marca por empresa:** `company_brand_helper.php` — logo en sidebar (`public/img/companies/{slug}.png` o `companies.logo_path`), nombre de empresa en cabecera, clase `body.portal-casapav` para empleados CP. Ver `public/img/companies/README.txt`.

**Móvil empleado:** barra inferior con **Salir** (logout), botón salir siempre visible en topbar; Casa Paviotti usa ítem **Extras** → `/cpTask/index` (no Horas+).

---

## 5. El empleado como centro — relaciones cruzadas

Esta sección define **qué significa cada dato del empleado** y **cómo se cruza** con el resto. Es la base del “roadmap unificado”.

### 5.1 Identidad (`users`)

| Campo | Uso | Si lo cambiás |
|-------|-----|----------------|
| `id` | FK en casi todas las tablas | **Rompe integridad** — no reasignar sin migrar FKs |
| `company_id` | Filtro admin, convenio default | Afecta listados, vacaciones, notificaciones |
| `role` | `admin` vs `empleado` | Cambia menú y acceso |
| `is_active` | Baja lógica | Excluye de liquidaciones y muchos reportes activos |
| `hire_date` | Antigüedad vacaciones | **Obligatorio** para liquidar períodos |
| `probation_start_date` | Solo informativo RRHH | **No** cuenta antigüedad vacaciones (solo `hire_date`) |
| `agreement_id` | Convenio individual | Override del default de empresa |
| `clock_id` / `user_clock_mappings` | Vínculo con marcaciones API | Sin mapeo: marcaciones en caché pero no en `clock_events` del usuario |
| `area_id` | Capacitación, filtros | Cursos/tareas por área |

### 5.2 Marcaciones (`marcacionesTodas` vs ficha)

```
API gpaviotti:6333 (/api/v1/marcaciones)
    → SyncModel::saveMarcacionesToCache
    → marcaciones_cache (todas, mapeadas o no)
    → si hay user_clock_mappings / clock_id
        → clock_events + Schedule (recálculo jornada)
        → attendance_day_summary (vía PlanVsActualService)
```

| Vista | Qué muestra | Relación empleado |
|-------|-------------|-------------------|
| **marcacionesTodas** | **Todas** las fichadas del caché (filtro empresa opcional, persona, fechas, reloj) | Puede incluir legajos **sin** usuario RRHH mapeado |
| **employeeProfile → tab Marcaciones** | Último mes de `clock_events` / schedule del **user_id** | Solo ese empleado |
| **Calendario** | Entrada/salida del día en `attendance` | Mismo usuario `user_id` del selector |

**Regla:** una marcación visible en “Todas” **no implica** que sume en asistencia del empleado hasta que exista **mapeo** en `user_clock_mappings` o `users.clock_id`.

### 5.3 Planificación (`employee_schedules`)

Tipos (`migration_schedule_vacation_types.sql`):

| `type` | Significado | Impacto asistencia |
|--------|-------------|-------------------|
| `shift` / `custom` | Turno normal | Se espera fichada; `PlanVsActualService` calcula tarde/ausente |
| `overtime` | Hora extra planificada | Relacionado con módulo HE |
| `vacation` | Vacaciones gozadas/planificadas | Chip “Vacaciones”; no exige fichada |
| `leave` | Licencia | Chip “Licencia”; no exige fichada |

**Planificador semanal** escribe estos bloques. **Vacaciones aprobadas** pueden insertar chips `vacation` vía `VacationLedgerService`.

### 5.4 Solicitudes (`requests`)

- Tipos en `request_types` (nombre configurable en BD).
- Flujo: empleado crea → admin aprueba/rechaza en `admin/requests`.
- Al aprobar vacaciones: debe pasar por `VacationLedgerService` (descuento FIFO de períodos).
- Pueden solaparse con `requests` en calendario (`CalendarMonthService::buildRequestMap`).

**No confundir:**

| Concepto | Dónde vive |
|----------|------------|
| Vacaciones (días de saldo) | `vacation_balance_periods` + chips `vacation` |
| Licencia (día planificado) | `employee_schedules.type = leave` o solicitud aprobada |
| Incidencia suspensión | `employee_incidents` — **no** quita turnos automáticamente |

### 5.5 Vacaciones (convenio + saldo)

```
collective_agreements + agreement_seniority_rules
    → VacationEntitlementService (antigüedad por hire_date, período Oct–Sep)
    → vacation_balance_periods (por user, label 2025-2026, etc.)
    → VacationLedgerService.applyTake (FIFO al consumir)
    → employee_schedules (chips vacation en planificador/calendario)
```

| Acción admin | Archivo clave |
|--------------|---------------|
| Configurar convenio empresa | `VacationAdminController::saveCompanyDefault` |
| Liquidar período (uno o masivo) | `VacationEntitlementService::liquidatePeriod`, `liquidateCompanyBatch` |
| Ver saldo | `employee_profile_vacation_tab.php`, toolbar `calendar.php` |

**Empleados inactivos:** excluidos de liquidación masiva (`User::getActiveEmployeesForVacationLiquidation`).

### 5.6 Licencias

- **Planificación:** `type = leave` en `employee_schedules`.
- **Asistencia:** estado `on_leave` en `attendance_day_summary` posible según reglas de `PlanVsActualService`.
- **Calendario:** `EmployeeDayContext` → chip “Licencia”, sin exigir fichada en detalle día.

### 5.7 Suspensiones e incidencias

Tipos en `employee_incident_types()` (`incidents_helper.php`).

| Tipo | Naturaleza en el sistema |
|------|-------------------------|
| `suspension` | Registro en `employee_incidents` con fecha y adjunto |
| `telegrama_despido` | Registro disciplinario |
| Baja real | `users.is_active = 0` manual |

**Hoy:** suspensión **no** bloquea login, **no** borra turnos, **no** filtra marcaciones. Aparece en calendario/ficha como contexto. Si negocio pide automatizar baja, es cambio **explícito** (no existe aún).

### 5.8 Cambios de turno (`shift_swaps`)

- Requiere migraciones #8–#10 (`shift_swaps` con `proposer_schedule_id`, etc.).
- Pendientes suman en badge del menú Solicitudes.
- `ShiftSwap` model — `isSchemaReady()` protege installs sin migrar.

### 5.9 Vista unificada (meta producto)

| Vista | Servicio | Qué integra hoy |
|-------|----------|-----------------|
| **Calendario mensual** | `CalendarMonthService` + `EmployeeDayContext` | Plan, asistencia, solicitudes, swaps, incidencias, feriados, justificaciones, **extras CP** (chip verde si `casapav_tasks`) |
| **Ficha empleado** | Tabs separados | Marcaciones, horario FC, HE, solicitudes, incidencias, vacaciones |

**Roadmap unificado:** pestaña **Roadmap** en ficha empleado (`employee_profile` + `CalendarMonthService`), calendario admin, portal **Mi mes** (`employee/miMes`), centro **Alertas RRHH** (`admin/hrAlerts`). Extender siempre `CalendarMonthService` / `EmployeeDayContext`.

**Operación automática:** `cli/sync_marcaciones.php` (cron), panel `admin/mapeoIncompleto`, exports `admin/exportAttendanceMonthCsv`, `vacationAdmin/exportVacationBalancesCsv`.

**Convenio por área:** `areas.agreement_id` (migración #26) — prioridad: usuario > área > empresa. **Ecofarma:** `users.plex_operator_name` enlaza comisiones desde la ficha.

---

## 6. Matriz de impacto — “si modificás X, revisá Y”

### Configuración y API

| Si tocás | Revisá / riesgo |
|----------|-----------------|
| `app/config/config.php` | `ClockApiClient`, `SyncModel`, `EcofarmaController`, login API JWT |
| `CLOCK_API_*` credenciales | `admin/sync`, marcaciones, mapeo, Ecofarma |
| `URLROOT` | Todas las vistas, redirects, JS `URLROOT` |

### Usuarios y empresa

| Si tocás | Revisá |
|----------|--------|
| `User.php` | Admin users, create/edit user, vacation batch, auth_helper, mapeo relojes |
| `user_employment_fields.php` | `hire_date`, `agreement_id`, aviso migración #22 |
| `auth_helper.php` | Todos los `requireAdminCompany`, multi-empresa |
| `header.php` | Menú, badges solicitudes, empresa activa |

### Marcaciones

| Si tocás | Revisá |
|----------|--------|
| `SyncModel.php` | `marcaciones_cache`, `clock_events`, logs sync |
| `MarcacionesCache.php` | `marcaciones_todas.php`, stats |
| `ClockApiClient.php` | Sync, mapeo API, **Ecofarma** |
| `Schedule.php` (clock_events) | Ficha marcaciones, recálculo asistencia |
| `marcaciones_helper.php` | Agrupación entradas/salidas en vista |

### Asistencia y calendario

| Si tocás | Revisá |
|----------|--------|
| `PlanVsActualService.php` | `AttendanceDaySummary`, calendario, colores celdas |
| `CalendarMonthService.php` | `calendar.php`, `calendar_day_detail.php`, `EmployeeDayContext` |
| `EmployeeDayContext.php` | Chips y estados en calendario |
| `attendance_helper.php` | Badges, clases CSS celdas, justificaciones |
| `AttendanceJustification.php` | POST `saveAttendanceJustification` |

### Vacaciones

| Si tocás | Revisá |
|----------|--------|
| `VacationEntitlementService.php` | Liquidación, preview AJAX, batch empresa |
| `VacationLedgerService.php` | Aprobación solicitudes, planificador vacation chips |
| `VacationBalance.php` | Modelo períodos, `vacation_helper` |
| `CollectiveAgreement.php` | ABM convenios, default empresa |
| `migration_collective_agreements.sql` | Toda la feature flag `vacation_module_ready()` |

### Solicitudes y turnos

| Si tocás | Revisá |
|----------|--------|
| `Request.php` | Empleado + admin requests, calendario requestMap |
| `ShiftSwap.php` | Solicitudes, notificaciones header |
| `WorkSchedule.php` | Planificador, calendario, vacation types |

### Incidencias

| Si tocás | Revisá |
|----------|--------|
| `EmployeeIncident.php` | Tab ficha, calendario incidentMap |
| `incidents_helper.php` | Tipos, badges, uploads en `public/uploads/employee_incidents/` |

### Front compartido

| Si tocás | Revisá |
|----------|--------|
| `footer.php` / `main.js` | DataTables en todas las tablas admin |
| `style.css` | `.page-content .btn-primary`, `.dt-buttons`, sidebar, **`.cp-task-*`** |
| `public/js/datatables-es-ES.json` | Idioma tablas (CORS local) |

### Casa Paviotti (`cp_*`)

| Si tocás | Revisá |
|----------|--------|
| `cp_tasks_helper.php` | Catálogo nombres, chips calendario, guards empleado/admin |
| `CpTask.php` | Pricing, cierre, duplicados, export, `getCalendarEntriesMap` |
| `CpTaskPricingService.php` | Cálculo por `form_key`, feriados, tarifas |
| `CpTaskController.php` | POST store, delete, redirect empleado CP |
| `CpTaskAdminController.php` | Cierre, aumento %, CSV, edición importes |
| `CalendarMonthService.php` / `EmployeeDayContext.php` | Chip `cp_task` en calendario |
| `EmployeeController.php` | Redirect dashboard/add si CP |
| `header.php` | Menú Extras CP vs Horas Extras |
| `migration_casapav_*.sql` | Orden #30 → #31; labels opcional |

---

## 7. Flujos de datos por rol (empleado)

### Empleado activo un día tipo “normal”

1. Tiene `employee_schedules` con turno.
2. Ficha en reloj → API → caché → (si mapeado) `clock_events`.
3. `PlanVsActualService` genera `attendance_day_summary` (`ok`, `late`, etc.).
4. Admin ve color en **Calendario**; detalle en `calendar_day_detail.php`.

### Empleado en vacaciones

1. Período liquidado en `vacation_balance_periods` con días pendientes.
2. Solicitud aprobada o planificador con chip `vacation` → `VacationLedgerService` descuenta FIFO.
3. Calendario: chip Vacaciones; sin alerta de ausencia por falta de fichada.

### Empleado en licencia

1. `leave` en planificación y/o solicitud aprobada.
2. Calendario: chip Licencia; mensaje “no se exige fichada” en detalle.

### Empleado con suspensión (incidencia)

1. Registro en `employee_incidents` (tipo `suspension`).
2. Visible en tab Incidencias y chip en calendario si la fecha cae en el rango.
3. **Sigue** pudiendo tener marcaciones y usuario activo salvo que RRHH marque `is_active = 0`.

### Empleado dado de baja (`is_active = 0`)

1. Desaparece de liquidación vacaciones masiva y contadores “activos”.
2. Marcaciones históricas **permanecen** en caché.
3. Login: según implementación actual (verificar `LoginController` si bloquea inactivos).

---

## 8. Casa Paviotti — extras por tarea (`cp_*`)

Módulo **independiente** de horas extras 50%/100%. Solo aplica si `companies.extras_mode = 'casapav_tasks'` (hoy **Casa Paviotti**, `company_id` vía `cp_casapav_company_id()`). Ecofarma y el resto del grupo siguen con `overtime_entries`.

### 8.1 Alcance por rol

| Rol | Menú | Alcance |
|-----|------|---------|
| **Empleado CP** | Extras CP | Solo sus `cp_task_entries` / `cp_external_entries` pendientes o histórico en calendario |
| **Admin** (empresa CP activa en sesión) | Extras CP, Tarifas CP, Aumento % CP, Reportes CP | Toda la empresa CP |
| **Supervisor** | Extras CP (pendientes) | Solo empleados de su `area_id` en pendientes; tarifas/cierre requieren admin |

Helpers de acceso: `require_cp_employee()`, `require_cp_staff()`, `requireAdminOnly()` en acciones admin sensibles.

### 8.2 Las 11 tareas (nombres UI y `form_key`)

Catálogo central en `cp_task_type_catalog()` (`cp_tasks_helper.php`). Grupos en pantalla empleado:

| Grupo | `form_key` | Nombre en app | Cálculo |
|-------|------------|---------------|---------|
| Sepelio | `armar` | Armar sepelio | Tarifas + localidad adicional + COVID; feriado ×2 |
| Sepelio | `realizar` | Realizar sepelio | Igual que armar |
| Traslados | `ambulancia` | Traslado en ambulancia | `ambu_localidades` / `ambu_vm` según localidad |
| Traslados | `viajes` | Viajes sanitarios | km × tarifa activa/pasiva |
| Servicios | `metalica` | Cambio de metálica | Tarifa fija |
| Servicios | `cremacion` | Cremación | Féretros × tarifas cremación |
| Servicios | `tanato` | Tanatopraxia | Tarifa fija |
| Servicios | `gestion` | Gestión y trámites | Tarifa fija |
| Otros | `mantenimiento` | Mantenimiento y tareas | **Manual** |
| Otros | `parcelas` | Comisión en parcela | **Manual** |
| Otros | `externas` | Otra empresa del grupo | **Manual** → `cp_external_entries` |

`legacy_code` en `cp_task_types` coincide con `id_tarea` del sistema CasaPavExtrasv11 (1, 7, 9, 2, 4, 10, 8, 5, 6, 3, 11).

Opcional en BD: `migration_casapav_labels.sql` alinea columna `name` con las etiquetas de la app.

### 8.3 Empleado — `/cpTask/index` (mobile-first)

- **Resumen:** total pendiente de cierre + cantidad de registros.
- **Nueva tarea:** grid de **cards** por grupo (1 columna en móvil, 2–3 en pantallas anchas). Icono, color, hint, badge “Manual”.
- **Mis pendientes:** cards (no tabla) con fecha `dd/mm/aaaa`, detalle extinto/empresa, importe, eliminar (solo `status = pending` propios).
- Aviso si **no hay tarifas** en `cp_employee_rates` (solo tareas manuales funcionan hasta que RRHH configure).
- CSS: clases `cp-summary-card`, `cp-task-grid`, `cp-pending-card` en `style.css`.

Formularios: `app/views/employee/cp_tasks/form_{form_key}.php` y partials `_form_sepulio.php`, `_form_manual.php`, `_deceased_fields.php`.

| Ruta empleado | Acción |
|---------------|--------|
| `/cpTask/index` | Hub principal |
| `/cpTask/create/{form_key}` | Alta de tarea |
| `/cpTask/store` | POST guardar (interna o externa) |
| `/cpTask/delete/{id}` | POST borrar pendiente propia |
| `/cpTask/deleteExternal/{id}` | POST borrar externa pendiente |
| `/cpTask/lookupDeceased/{code}` | JSON búsqueda extinto (si `EXTINTOS_DB_*`) |

### 8.4 Admin — rutas

| Ruta | Acción |
|------|--------|
| `/cpTaskAdmin/pending` | Listado pendientes + edición inline importe + enlaces |
| `/cpTaskAdmin/editAmount` | POST corrección importe (interna/externa) |
| `/cpTaskAdmin/rates` | Tarifas por empleado (`cp_employee_rates`, todas las columnas) |
| `/cpTaskAdmin/catalogs` | Localidades (`has_additional` 0/1/2), retiros, empresas externas |
| `/cpTaskAdmin/closeMonth` | Cierre simple (sin aumento) |
| `/cpTaskAdmin/rateIncrease` | Vista previa %, solo tarifas, o aumento + cierre |
| `/cpTaskAdmin/reports?mes=YYYY-MM` | Totales por empleado en el mes |
| `/cpTaskAdmin/history` | Lotes cerrados |
| `/cpTaskAdmin/closureDetail/{id}` | Detalle de lote |
| `/cpTaskAdmin/exportPending` | CSV pendientes (`;`, BOM UTF-8) |
| `/cpTaskAdmin/exportClosure/{id}` | CSV de un lote |

### 8.5 Reglas de negocio y validaciones

| Tema | Comportamiento |
|------|----------------|
| **Feriado** | `Holiday::isHolidayForCompany($companyId, $fecha)` — ×2 en tareas automáticas (`holiday_multiplier_eligible = 1`) |
| **Importe manual** | Mantenimiento, parcelas, externas: el empleado ingresa monto; **no** se duplica automático en feriado (aviso en formulario) |
| **Duplicado extinto** | Misma combinación `user_id` + `task_type_id` + `deceased_code` (único en BD). Validación en `CpTask::duplicateExists()` — usar retorno de `single()`, no `rowCount()` |
| **Fecha** | No futura: `cp_validate_activity_date()` en controller y `CpTaskPricingService` |
| **Tarifas** | Sin fila o todo en cero → error al guardar tareas automáticas; `ensureRatesRow()` al entrar al índice |
| **Cierre** | `closeAllPending` → lote en `cp_task_closures`, IVA 19%, marca `closed` entradas internas y externas |
| **Aumento %** | `closeAllPendingWithIncrease`: actualiza tarifas, multiplica pendientes, **excepto** legacy 3 (parcelas) y 6 (mantenimiento); luego cierra |
| **Supervisor** | Filtra pendientes por `area_id`; admin ve todo |

### 8.6 Calendario e import histórico

- `CalendarMonthService::buildCpTaskMap()` carga movimientos del mes.
- `EmployeeDayContext` agrega chip `cp_task` (verde) y línea en resumen del día.
- `calendar_day_detail.php` lista tareas con importe y enlace a pendientes admin.

**CLI legacy** (misma máquina MySQL, BD importada del sistema viejo):

```bash
php scripts/import_cp_legacy.php --legacy-db=paviotti_casapav --dry-run --limit=100
php scripts/import_cp_legacy.php --legacy-db=paviotti_casapav --limit=5000
```

- Por defecto: solo `datos` cerrados (`estado=1`, `lote>0`).
- `--include-pending`: también pendientes legacy.
- Mapeo `login.fullnombre` → `users.full_name` (normaliza orden apellido/nombre). Requiere empleados CP dados de alta en RRHH.

### 8.7 Migraciones y tablas

| # | Archivo |
|---|---------|
| 30 | `migration_casapav_tasks.sql` — `extras_mode`, `cp_task_types`, `cp_task_entries`, `cp_employee_rates`, `cp_task_closures`, catálogos base |
| 31 | `migration_casapav_phase_b.sql` — tarifas extendidas, `cp_external_*`, 11 tareas `mvp_enabled=1`, seeds |
| — | `migration_casapav_labels.sql` — nombres legibles en BD (opcional) |

Validación: `SHOW TABLES LIKE 'cp_%';` y `SELECT extras_mode FROM companies WHERE name = 'Casa Paviotti';` (ver `MIGRATIONS.md`).

---

## 9. APIs externas (misma base JWT)

Base: `http://gpaviotti.com.ar:6333` — ver `CLOCK_API_BASE_URL`.

| Módulo | Endpoints | Cliente PHP |
|--------|-----------|-------------|
| Relojes / marcaciones | `/login`, `/api/v1/marcaciones`, legajos | `ClockApiClient`, `SyncModel` |
| Ecofarma comisiones | `/api/v1/ecofarma/sucursales`, `facturacion-acos`, `resumen-operadores` | `ClockApiClient`, `EcofarmaController` |

**Principio:** llamadas desde **servidor PHP** (curl), no desde el navegador del empleado (evita mixed content y expone credenciales).

---

## 10. Reglas de implementación (agentes y devs)

1. **Leer** `MIGRATIONS.md` antes de asumir que una columna existe; usar `vacation_module_ready()`, `learning_is_ready()`, etc.
2. **No** mezclar `hire_date` con `probation_start_date` para vacaciones.
3. **No** duplicar placeholders PDO en la misma query.
4. **Admin multi-empresa:** usar `requireAdminCompany()` o `adminResolveUser()` en acciones por `user_id`.
5. **Marcaciones:** distinguir caché global vs eventos por usuario mapeado.
6. **Vacaciones:** consumo siempre vía `VacationLedgerService` (FIFO).
7. **CSS/DataTables:** idioma local `public/js/datatables-es-ES.json`, no CDN `//` en localhost.
8. **Commits:** solo si el usuario lo pide.
9. **Cambios mínimos:** no refactorizar módulos no relacionados con la tarea.
10. **Vista unificada:** preferir enriquecer `EmployeeDayContext` / `CalendarMonthService` antes de crear una quinta pantalla suelta.
11. **Casa Paviotti:** no mezclar `overtime_entries` con `cp_*`; usar `company_uses_casapav_tasks()` y helpers `cp_*`; etiquetas UI en `cp_task_type_catalog()`.

---

## 10.1 Panel Configuración del sistema

| Aspecto | Detalle |
|---------|---------|
| **Ruta** | `/systemConfig` (menú lateral → **Sistema → Configuración**) |
| **Quién** | Solo rol `admin` (no supervisor) |
| **PIN** | Clave aparte del login; sesión desbloqueada 30 min. PIN inicial tras migración #32: `lautaro` (cambiar en pestaña Seguridad) |
| **Migración** | `migration_system_settings.sql` |
| **Correo** | Sigue en tabla `mail_settings`; se edita en pestaña Correo (antes `notificationsAdmin/mailConfig`, redirige aquí) |
| **Casa Paviotti** | Límite `#cp_deceased_select`, credenciales BD extintos, tablas `extintosH`/`extintos`, validación duplicados |
| **Asistencia** | Tolerancias llegada tarde / salida anticipada (minutos) |
| **Integraciones** | URL y credenciales API Relojes; defaults Ecofarma (obra social, comisión %) |
| **Portal empleado** | Visibilidad por módulo: horas extras, extras CP, vacaciones, recibos, capacitación, encuestas, etc. |
| **Horas extras** | Por rol (admin/supervisor), empresa (`companies.show_overtime`), área (`areas.show_overtime`) y portal empleado |
| **Fuera del panel** | `DB_*`, `URLROOT`, `APPROOT` en `app/config/config.php` (infraestructura) |

Helper en código: `setting('clave')`, `setting_int()`, `app_name()`, `employee_portal_can('overtime')`.

### Claves portal empleado (`group_key = employee`)

Migración adicional: `migration_system_settings_employee_portal.sql` (tras #32).

| Clave | Efecto si está en `0` |
|-------|------------------------|
| `employee_show_overtime` | Sin menú ni `/employee/dashboard` |
| `employee_show_overtime_on_home` | Sin tarjeta/listado horas en inicio |
| `employee_show_cp_extras` | Sin `/cpTask` (empresas CP) |
| `employee_show_cp_extras_on_home` | Sin banner extras en inicio |
| `employee_show_vacation_balance` | Sin días pendientes en inicio, solicitudes y Mi mes |
| `employee_show_pay_stubs` | Sin recibos de sueldo |
| `employee_show_training` | Sin sección Aprendizaje |
| `employee_show_peer_stars` | Sin reconocimiento entre pares |
| `employee_show_surveys` | Sin encuestas |
| `employee_show_suggestions` | Sin sugerencias |
| `employee_show_mi_mes` | Sin `/employee/miMes` |

### Horas extras por empresa, área y rol (#33)

Migración: `migration_overtime_visibility.sql`.

| Nivel | Dónde | Efecto |
|-------|--------|--------|
| Rol admin | Configuración → **Horas extras** → `overtime_visible_admin` | Panel RRHH (dashboard, cierres, planificador) |
| Rol supervisor | `overtime_visible_supervisor` | Igual, acotado al área del supervisor |
| Empresa | Admin → **Empresas** → Editar → switch horas extras | Toda la empresa (no aplica si `extras_mode = casapav_tasks`) |
| Área | Capacitación → **Áreas** → Editar → Hereda / Visible / Oculto | Solo empleados de esa área |
| Empleado | Configuración → Portal empleado → `employee_show_overtime` | Portal + rutas `/employee/dashboard` |

Helpers: `overtime_staff_can_view()`, `company_uses_classic_overtime()`, `calendar_day_apply_visibility()`.

---

## 11. Checklist antes de entregar un cambio

- [ ] ¿Afecta admin, empleado o ambos? ¿Probaste el rol correcto?
- [ ] ¿Hay migración SQL nueva? ¿Actualizaste `MIGRATIONS.md`?
- [ ] ¿Tocaste vacaciones? ¿Liquidación, saldo y planificador siguen coherentes?
- [ ] ¿Tocaste marcaciones? ¿Sync + mapeo + asistencia?
- [ ] ¿Tocaste calendario? ¿`EmployeeDayContext` y partial de detalle?
- [ ] ¿Placeholders PDO únicos?
- [ ] ¿CSRF en formularios POST nuevos?
- [ ] ¿Empresa activa respetada en listados admin?
- [ ] ¿Tocaste Casa Paviotti? ¿Empleado CP ve Extras CP y no HE? ¿Feriado / manual / duplicado extinto?
- [ ] ¿Formularios CP con CSRF, fecha `max=hoy`, tarifas si aplica?
- [ ] ¿Cambiaste visibilidad portal empleado? ¿Probaste con empleado real (menú, inicio, rutas directas)?

---

## 12. Historial de decisiones (no revertir sin acuerdo)

| Tema | Decisión |
|------|----------|
| Período vacaciones | Oct–Sep (CEC); label `2025-2026` |
| Antigüedad | Solo `hire_date` |
| Consumo vacaciones | FIFO período más antiguo |
| Liquidación masiva | Solo `is_active=1` y `role=empleado` |
| Ecofarma vista principal | `resumen-operadores`; detalle bajo `vista=detalle` |
| Comisión default | 7 % (`ECOFARMA_DEFAULT_COMISION_PCT`) |
| Obra social default ACOS | `999900` |
| Extras Casa Paviotti | Módulo `cp_*` por importe en pesos, no horas 50/100% |
| Empresa CP | `companies.extras_mode = casapav_tasks` |
| UI empleado CP | `/cpTask/index` mobile-first con cards por grupo de tarea |
| Aumento % cierre | Parcelas (legacy 3) y mantenimiento (legacy 6) excluidos del multiplicador en pendientes |
| Nombres tareas UI | Fuente: `cp_task_type_catalog()` en `cp_tasks_helper.php` |
| Configuración global | `system_settings` + PIN; fallback a `config.php` si falta clave en BD |
| Portal empleado configurable | `employee_portal_can()`; admin en Configuración → Portal empleado |

---

*Última actualización: mayo 2026 — incluye módulo Casa Paviotti (`cp_*`), panel Configuración (sistema + portal empleado), calendario con chips Extras CP, admin cierre/reportes/import legacy y portal empleado mobile-first.*
