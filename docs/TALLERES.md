# Talleres

Controlador: `TallerController`. Modelo: `TallerFormulario`. Helpers: `TallerPersonaSync`, `TallerPresentacionNinosSync`, `TallerAutorizacionSync`, `TallerPresentacionDocumentos`. Servicio Social (`TallerServicioSocialController`) es un submódulo aparte.

Hay tres tipos de formulario: general (Taller de Padres), Presentación de niños y Tour Levántate. El público no pide sesión. Los pagos se registran en el panel (`talleres/pago`) y también en Comunidad.

## Presentación / Fiesta de los niños: formulario fijo (26 oct 2026)

Aplica a **Presentación de niños** y **Fiesta de los niños** (`esInscripcionNinosFija`).

Campos fijos del formulario público:

- Nombre de los padres
- Nombre del niño(a)
- Edad
- Ministerio (lista desplegable + opción **Sin ministerio**)

Sigue el consentimiento/firma. **Sin bloque de subir documentos** (retirado el 26 oct 2026).

**Aislamiento:** la inscripción se guarda solo en tablas de talleres (`Datos_JSON`). El desplegable lee nombres del catálogo de ministerios, pero no crea ni actualiza fichas en Personas ni asigna ministerio fuera de Talleres. El ministerio elegido queda en el JSON (`persona_ministerio` / `_asignacion_ministerio`).

**Fiesta:** si el formulario aún tenía el bloque «persona», al abrirlo se migra a padres/niño sin borrar preguntas personalizadas. `Tipo_Plantilla` pasa a `fiesta_ninos`.

**Respuestas:** la tabla «Inscritos por ministerio» muestra la columna **Inscritos** (cantidad) en la fila principal; al abrir la fila se ven los nombres.

Archivos: `TallerPresentacionNinosSync.php`, `TallerController.php`, `TallerFormulario.php`, `views/talleres_publico/formulario.php`, `views/talleres/respuestas.php`, `views/talleres/editor.php`.

## Aislamiento del módulo (20 de octubre de 2026)

**Cualquiera puede inscribirse** sin tener ficha en Personas. Los datos de la inscripción viven **solo en tablas de talleres** (`talleres_formulario_respuesta` y JSON). No se crea, no se actualiza y no se vincula `Id_Persona` al guardar.

- Si la cédula ya existe en Personas o en el histórico (Nehemías), la búsqueda pública puede **autocompletar** el formulario. Eso no enlaza la inscripción a la ficha ni la modifica.
- Si no está en el registro, la persona completa los campos en el mismo formulario y se guarda igual.
- **Asignar ministerio** desde Respuestas solo escribe en el JSON de la inscripción (`_asignacion_ministerio`). No toca Personas ni Ministerios.
- Quien se registra solo por talleres **no debe aparecer** en Personas, Ministerios, Escalera ni otros módulos por ese registro.

Inscripciones antiguas que ya tenían `Id_Persona` no se desvinculan solas; las nuevas ya no se enlazan.

Archivos de este cambio: `TallerController.php`, `TallerPersonaSync.php`, `TallerPresentacionNinosSync.php`, `views/talleres_publico/formulario.php`.

El bloque fijo de datos personales **no pide** correo, dirección, estado civil ni ocupación (solo nombre, documento, fecha/edad, teléfono, líder y ministerio). Tampoco hay buscador por cédula: se completa a mano; al escribir el documento solo se avisa si ya está inscrito en esa actividad.

## Idea: un formulario, varias fechas

No se crea un formulario nuevo cada mes. Hay un formulario por tipo de actividad (Taller de Padres, Presentación de niños, Tour). El enlace y el QR son fijos.

Para la próxima fecha, en Respuestas se usa **Abrir nueva actividad**. Eso cierra la lista actual y abre otra. Las inscripciones viejas se quedan en la actividad anterior. La misma persona puede volver a inscribirse en la nueva.

Tabla `talleres_formulario_apertura`. Las respuestas llevan `Id_Apertura`. Al entrar al módulo, cada formulario existente recibe una «Actividad inicial» y las inscripciones viejas se enlazan ahí.

Los dos «Taller de Padres» que ya existen **no se unieron**. Mezclar inscripciones a ciegas sería peor. El listado avisa si hay nombres repetidos: deje uno, use Nueva actividad, y cierre o elimine el duplicado.

No se puede crear (ni renombrar) un formulario con el mismo nombre que otro.

## Cómo funciona

- Quien tiene cualquier permiso de Talleres entra al listado. Cerrar o abrir el enlace exige `talleres:editar`. Guardar un pago exige crear o editar.
- El formulario público busca por cédula/teléfono solo para autocompletar. La inscripción no exige ficha en Personas.
- No se puede inscribir dos veces en la **misma actividad** con el mismo documento. En la siguiente actividad sí.
- Presentación de niños permite, si el niño ya está inscrito en la actividad actual, solo subir más documentos.
- Editar el formulario no cambia el slug: el QR impreso sigue valiendo.

## Arreglos previos (14 de octubre de 2026)

**Pagos.** `50.000` o `50,000` se leen como cincuenta mil (misma idea que Comunidad, más el caso de solo puntos con grupos de miles). La referencia del ticket se reintenta hasta que sea única. El campo muestra el ejemplo `50000 o 50.000`.

**Documentos de Presentación de niños.** `public/uploads/talleres_presentacion/.htaccess` niega la URL directa. Se sirven con sesión por `talleres/documento-presentacion`. Para adjuntar archivos a una inscripción ya hecha, el documento del niño en el formulario y en el POST deben coincidir y no ir vacíos.

**Nombres duplicados.** Crear o guardar con un título que ya existe se bloquea, con el aviso de editar y abrir nueva actividad.

## Qué no se cambió (a propósito)

- Búsqueda pública por cédula o teléfono: igual que Teens, para autocompletar en la iglesia.
- Quién ve las respuestas: `talleres:ver` sigue viendo todos los formularios e inscripciones (sin aislamiento por red). Pastor, Administrador, Células y Ganar ya tienen acceso completo a datos.
- El Tour no pide la autorización de datos que sí piden los demás.
- Exportar Excel lo puede hacer quien tiene `talleres:ver`.
- El ticket de pago sigue en sesión (si dos personas registran a la vez en el mismo navegador, puede mostrarse el último).

## Pendiente

- Servicio Social (agendamiento, horarios, historia clínica): no se revisó en esta pasada.
- En producción, al desplegar la pasada del 14 de octubre, había tres formularios: Fiesta de los niños (abierto), Seminario de parejas y Presentación de niños (cerrados). No había dos «Taller de Padres». Si más adelante hace falta el de Padres, cree uno y use Nueva actividad en cada fecha.

## Despliegue

Subido a producción el 14 de octubre de 2026 (aperturas / `Id_Apertura`).

**Aislamiento de inscripciones** subido a producción el **20 de octubre de 2026**. No hay SQL: solo PHP y la vista pública. Tras el despliegue, actualizar `public/assets/.build` para que el JS del formulario público no quede en caché.

**Presentación / Fiesta de niños fija** (padres, niño, edad, ministerio + conteo en tabla): **26 de octubre de 2026**. `.build`: `20261026-fiesta-ninos-fijo`.

**Normalización de ministerios en inscripciones** (26 oct 2026): unifica variantes como «Sara y Michael» / «Michael y Sara» y «Elizabeth» → «Fabian y Elizabeth» contra el catálogo. Helper `TallerMinisterioNormalizer` (también al listar Respuestas). Script: `tools/normalizar_ministerios_talleres_web.php?clave=mci_admin_2026` (añadir `&aplicar=1` para guardar).

**Limpieza Fiesta de los niños** (26 oct 2026): se borraron 19 respuestas de producción (`Id_Formulario=7`) para empezar inscripciones nuevas. Script: `tools/limpiar_respuestas_taller_web.php?clave=mci_admin_2026&slug=fiesta`.

**Firma del formulario público** (26 oct 2026): se restauró el JS del canvas (`firma-canvas`) que se había perdido al simplificar Presentación/Fiesta.

**Botón Enviar inscripción** (26 oct 2026): se restauró el `</form>` y el botón submit que también se habían perdido en esa simplificación.

Tras este despliegue:

1. El enlace/QR público debe seguir abriendo.
2. Inscribirse con una cédula **que no esté** en Personas debe completar y guardar sin mandar a Personas.
3. Esa inscripción no debe crear ficha nueva ni aparecer fuera de Talleres.
4. Asignar ministerio en Respuestas debe quitarla de «Sin ministerio» solo en el listado del taller.
5. Un archivo por URL directa en `/public/uploads/talleres_presentacion/` debe dar 403; desde Respuestas, con sesión, debe abrir.

## Cómo probar en local

1. Formulario público: buscar una cédula inexistente → mensaje de completar a mano → enviar → ok.
2. Comprobar en BD que `talleres_formulario_respuesta.Id_Persona` quedó en 0 (o null) y que no hay fila nueva en `persona`.
3. Listado / Respuestas: ver la inscripción; asignar ministerio y confirmar que solo cambia el JSON del taller.
4. Abrir nueva actividad e inscribirse otra vez con el mismo documento.
5. Pago: guardar `50.000` y ver 50000 en el ticket.
6. Presentación: PDF por URL directa en uploads debe dar 403; desde Respuestas debe abrir.
