# Administración — roles, permisos, cuentas y aislamiento de datos

Actualizado: 14 oct 2026.

## Cómo darle a un usuario acceso a un módulo

Los permisos se dan **por rol**, no por usuario. Cambiar un rol cambia a todas las personas que lo tienen.

1. Mira si ya existe un rol que sirva. Si otras personas lo usan, no le cambies permisos solo por una.
2. Si necesita permisos distintos, crea un rol en *Administración → Roles*. Nace con todo denegado.
3. En *Administración → Permisos* elige el rol y activa solo lo necesario (Ver, Crear, Editar, Eliminar y acciones avanzadas).
4. En *Administración → Cuentas* crea o edita la cuenta con ese rol.
5. Pide al usuario que cierre sesión y vuelva a entrar (el menú se arma al iniciar sesión; los permisos se refrescan cada ~30 s).
6. Pruébalo con esa cuenta.

## Qué datos ve cada rol

- **Acceso total a los datos** (sin aislamiento, pero solo en los módulos que se les asignen): Administrador, Pastores, Ganar y Células.
- **Aislados a su red:** Líder de célula, Líder de 12, Líder de 144 y Asistente. El sistema los reconoce por el **nombre del rol**.
- Un rol personalizado con "Ver" en Personas o Células ve **toda** la iglesia. Para acceso limitado hay que usar los roles aislados.

### Líder de 144 (cambio del 14 oct 2026)

Antes veía las 50 células de la iglesia en el módulo Células (y podía editarlas) y 0 personas. Ahora queda aislado a su red:

- **Personas:** él, quienes lo tienen como líder, los líderes de su equipo con sus miembros, y las células propias o bajo su cobertura.
- **Células y asistencias:** solo las de esa red.
- No se amplía por ministerio completo.
- El resto de roles no cambió (se comparó antes y después con los datos locales).

Archivo: `app/Helpers/DataIsolation.php`.

## Protecciones nuevas

**Roles** (`RolController`):
- El nombre es obligatorio, de máximo 60 caracteres y único (sin distinguir mayúsculas ni acentos).
- Nadie puede crear ni renombrar un rol como "Administrador" o "Admin".
- El rol Administrador no se edita ni se elimina.
- Solo un administrador puede crear, usar o modificar roles cuyo nombre da acceso especial a los datos (contienen admin, pastor, ganar, asistente, o es "Células").
- No se elimina un rol con personas o cuentas asignadas, ni el rol con el que se está trabajando.

**Cuentas** (`CuentaController`):
- Solo un administrador puede crear una cuenta con rol Administrador, asignarlo o modificar una cuenta de administrador.
- Quien no es administrador no ve el rol Administrador en la lista de roles.
- Quien no es administrador no puede cambiar su propio rol.

**Permisos** (`PermisosController`):
- Solo un administrador puede conceder permisos de los módulos Permisos, Cuentas y Roles.
- Quien no es administrador no puede modificar los permisos de su propio rol.

## Otras protecciones (14 oct 2026, segunda parte)

**Solicitudes desde otro sitio (CSRF)** — `app/Helpers/OriginGuard.php`, llamado desde `public/index.php` en las rutas privadas:
- Todo POST y los GET que modifican datos (eliminar, generar/procesar cola, reintentar fallidos, migrar, etc.) deben venir de este mismo sitio (cabecera Origin o Referer).
- Un POST sin ninguna cabecera se permite (scripts propios). Un GET que modifica datos sin cabeceras se bloquea.
- Las rutas públicas (formularios, webhooks) no pasan por esta revisión.
- Si un usuario ve "Solicitud bloqueada por seguridad", debe recargar la página e intentar de nuevo desde la aplicación.

**Contraseñas** — `app/Helpers/PasswordPolicy.php`: las nuevas o cambiadas (Cuentas y Mi cuenta) deben tener mínimo 8 caracteres y combinar letras y números. Las existentes no se tocan.

**Actualizar edades** (`personas/sincronizar-edades`): ya no es pública ni usa clave. Solo la abre un administrador con sesión. La clave `mci_admin_2026` estaba escrita en el código y en GitHub; quedó sin uso.

**Filtro de rutas** (`app/Config/route_permissions.php`): las acciones con guion o mayúsculas (`asignar-cupo`, `guardar-metas`, `generar-cola`, `marcarEntregado`, etc.) se trataban como "ver". Ahora tienen reglas explícitas que replican lo que ya exigía cada controlador. Guardar un pago de taller ahora exige permiso de editar o crear en Talleres, no solo ver.

**Webhook de WhatsApp:** sin token de verificación configurado ya no acepta ninguna verificación. Si la tabla `whatsapp_config` tiene una columna `app_secret` con el secreto de la app de Meta, los POST deben venir firmados (`X-Hub-Signature-256`). Esa columna no existe hoy, así que la firma no se exige hasta que se agregue.

**Roles por nombre:** al crear o renombrar roles se bloquean los nombres que dan acceso especial (ver arriba). Los roles existentes no cambian.

## Despliegue

Subido a producción el 14 de octubre de 2026 (17 archivos, tamaños verificados). Tras el despliegue, el login carga y `personas/sincronizar-edades` pide sesión.

Pruebas recomendadas con usuarios reales: crear/editar una persona y eliminar un registro de prueba como administrador; entrar con un Líder de 144 y confirmar que solo ve su red; crear una cuenta probando la regla de contraseña.

## Pendiente

- Confirmar en producción qué roles tienen `permisos:editar` y `cuentas:*` (en la base local, Pastores tiene `permisos:editar`).
- Agregar `app_secret` a `whatsapp_config` y firmar el webhook de Meta.
- `personas/actualizarChecklistEscalera`, `talleres/pago` y `talleres/ticket-pago` siguen deduciéndose como "ver" en el filtro de rutas; sus controladores revisan el permiso por su cuenta.
- `personas/plantillas-whatsapp/programar` solo exige "ver" en plantillas de WhatsApp.
