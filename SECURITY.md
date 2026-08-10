# Seguridad — Paviotti RRHH

## Archivos sensibles en `public/uploads/`

Las carpetas `pay_stubs`, `pay_stub_signatures`, `request_certificates`, `justifications`, `courses`, `announcements` y `employee_incidents` tienen `.htaccess` que niega el acceso HTTP directo. Los archivos se sirven solo por controladores autenticados (`stream*` / `download*`).

| Tipo | Ver (inline) | Descargar |
|------|----------------|-----------|
| Incidencia RRHH | `/admin/streamEmployeeIncident/{userId}/{id}` | `/admin/downloadEmployeeIncident/{userId}/{id}` |

Los avatares (`uploads/avatars/`) siguen siendo públicos.

## Configuración y secretos

- Valores por defecto: `app/config/config.php`
- Overrides locales (no commitear): copiar `app/config/config.example.php` → `app/config/config.local.php`
- Correo SMTP: `app/config/mail.local.php` (ver `mail_settings` en BD)

## Sesión y CSRF

- Cookies de sesión: `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS
- Regeneración de ID al iniciar sesión
- Formularios POST administrativos: token CSRF (`csrf_field()` / `csrf_verify()`)

## Excepciones documentadas (por decisión del proyecto)

- Login sin CSRF (compatibilidad con otro sistema)
- Credenciales sensibles aún editables solo en `config.php` / `config.local.php` (no en panel Config)
