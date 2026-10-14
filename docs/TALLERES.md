# Talleres

Revisión y cambios del 14 de octubre de 2026. Controlador: `TallerController`. Modelo: `TallerFormulario`. Servicio Social (`TallerServicioSocialController`) es un submódulo aparte y queda para otra pasada.

Hay tres tipos de formulario: general (Taller de Padres), Presentación de niños y Tour Levántate. El público no pide sesión. Los pagos se registran en el panel (`talleres/pago`) y también en Comunidad.

**No está en producción** hasta que se pida desplegar.

## Idea: un formulario, varias fechas

No se crea un formulario nuevo cada mes. Hay un formulario por tipo de actividad (Taller de Padres, Presentación de niños, Tour). El enlace y el QR son fijos.

Para la próxima fecha, en Respuestas se usa **Abrir nueva actividad**. Eso cierra la lista actual y abre otra. Las inscripciones viejas se quedan en la actividad anterior. La misma persona puede volver a inscribirse en la nueva.

Tabla `talleres_formulario_apertura`. Las respuestas llevan `Id_Apertura`. Al entrar al módulo, cada formulario existente recibe una «Actividad inicial» y las inscripciones viejas se enlazan ahí.

Los dos «Taller de Padres» que ya existen **no se unieron**. Mezclar inscripciones a ciegas sería peor. El listado avisa si hay nombres repetidos: deje uno, use Nueva actividad, y cierre o elimine el duplicado.

No se puede crear (ni renombrar) un formulario con el mismo nombre que otro.

## Cómo funciona

- Quien tiene cualquier permiso de Talleres entra al listado. Cerrar o abrir el enlace exige `talleres:editar`. Guardar un pago exige crear o editar.
- El formulario público busca a la persona por cédula y no crea ficha: si no está, manda a Personas y al volver se busca de nuevo. Esa búsqueda se deja pública (igual que Teens).
- No se puede inscribir dos veces en la **misma actividad** con el mismo documento. En la siguiente actividad sí.
- Presentación de niños permite, si el niño ya está inscrito en la actividad actual, solo subir más documentos.
- Editar el formulario no cambia el slug: el QR impreso sigue valiendo.

## Arreglos de esta pasada

**Pagos.** `50.000` o `50,000` se leen como cincuenta mil (misma idea que Comunidad, más el caso de solo puntos con grupos de miles). La referencia del ticket se reintenta hasta que sea única. El campo muestra el ejemplo `50000 o 50.000`.

**Documentos de Presentación de niños.** `public/uploads/talleres_presentacion/.htaccess` niega la URL directa. Se sirven con sesión por `talleres/documento-presentacion`. Para adjuntar archivos a una inscripción ya hecha, el documento del niño en el formulario y en el POST deben coincidir y no ir vacíos.

**Nombres duplicados.** Crear o guardar con un título que ya existe se bloquea, con el aviso de editar y abrir nueva actividad.

## Qué no se cambió (a propósito)

- Búsqueda pública por cédula o teléfono: igual que Teens, para voluntarios en la iglesia.
- Quién ve las respuestas: `talleres:ver` sigue viendo todos los formularios e inscripciones (sin aislamiento por red). Pastor, Administrador, Células y Ganar ya tienen acceso completo a datos.
- El Tour no pide la autorización de datos que sí piden los demás.
- Exportar Excel lo puede hacer quien tiene `talleres:ver`.
- Si el niño de Presentación no está en Personas, la inscripción igual se guarda.
- El ticket de pago sigue en sesión (si dos personas registran a la vez en el mismo navegador, puede mostrarse el último).

## Pendiente

- Servicio Social (agendamiento, horarios, historia clínica): no se revisó en esta pasada.
- En producción, al desplegar, había tres formularios: Fiesta de los niños (abierto), Seminario de parejas y Presentación de niños (cerrados). No había dos «Taller de Padres». Si más adelante hace falta el de Padres, cree uno y use Nueva actividad en cada fecha.

## Despliegue

Subido a producción el 14 de octubre de 2026. La tabla `talleres_formulario_apertura` y la columna `Id_Apertura` se crearon al abrir el módulo (no hay SQL aparte).

Tras el despliegue: el QR y el enlace público deben seguir abriendo. En Respuestas debe verse el selector de actividad y «Abrir nueva actividad». Un archivo por URL directa en `/public/uploads/talleres_presentacion/` debe dar 403; desde Respuestas, con sesión, debe abrir.

Pruebas recomendadas: abrir una actividad nueva en Taller de Padres (sin crear otro formulario), inscribirse otra vez con el mismo documento, y guardar un pago como `50.000`. Si en producción hay dos «Taller de Padres», deje uno y cierre o elimine el duplicado.

## Cómo probar en local

1. Listado: un formulario muestra la actividad actual y el conteo de esa actividad (el histórico va al lado si hay más).
2. Respuestas: selector de actividad, Excel de esa actividad, «Abrir nueva actividad» con un nombre (ej. «Taller de padres — mayo 2026»).
3. El mismo QR / enlace público debe seguir abriendo. Inscribirse otra vez con el mismo documento debe pasar en la nueva actividad y seguir bloqueado en la anterior.
4. Pago: guardar `50.000` y ver 50000 en el ticket.
5. Presentación: un PDF por URL directa en `/public/uploads/talleres_presentacion/` debe dar 403; desde Respuestas (con sesión) debe abrir.
