# Teens

Revisión del 14 de octubre de 2026. Módulo: `TeenController`, modelo `Teen`, vistas en `views/teen/`.

El módulo junta dos cosas: material PDF por mes/semana y el registro de niños (Kids y Teens) con código semanal para el domingo.

## Cómo funciona

- **Material:** tabla `teens`. Subida por semana (1-5) o decoración del mes. Quien tiene `teen:crear` o `teen:editar` ve todo el año. Quien solo ve material, ve la semana actual.
- **Niños:** tabla `teen_menores`. El listado interno (`teen/registro-menores`) pide `teen:ver`. El formulario de la puerta (`teen/registro-publico`) no pide sesión.
- **Código semanal:** tabla `teen_menores_asistencia`. Kids (0-9 años) reciben `KS` + 4 dígitos. Teens (10-17) reciben `TNS` + 4 dígitos. El código base permanente es `TN-yymmdd-######`. Los códigos viejos de 2 dígitos siguen valiendo en la consulta.
- Rutas públicas (se dejan así, decisión 14 oct 2026): registro, guardar, consulta por código, buscar por teléfono y buscar por documento. En la iglesia las abren solo los voluntarios.

## Arreglos del 14 oct 2026 (del punto 2 en adelante)

El punto 1 (formulario público con búsqueda por documento/teléfono y consulta de código) **no se tocó**.

**Código semanal.** De 2 a 4 dígitos (`TNS4821`, `KS1930`): 10.000 combinaciones por grupo y domingo. Si el niño ya tiene código de ese domingo, se conserva; volver a guardar no anula el impreso.

**Sin duplicar.** El guardado interno busca por documento o por nombre, fecha y teléfono. El público, si el documento existe, actualiza y reutiliza el código. Si el documento del niño o el teléfono del acudiente ya están en Personas, se unen (`id_persona`, `id_acudiente`). No se crea ficha nueva desde la puerta, igual que en talleres.

**Maestro.** La ruta `teen` acepta `puedeVerMaterialTeens` (`teen:ver` o el permiso general de material). El maestro sin `teen:ver` ve solo la semana actual y no ve Registro ni Códigos.

**PDF.** Los enlaces pasan por `teen/verPdf` (con sesión). `public/uploads/teens/.htaccess` niega la URL directa.

**Rutas.** `teen/buscar-menor-publico-documento` queda explícita como pública. `teen/qr-registro` exige `teen:ver`.

**Decoración.** Subir con semana 0 guarda en `decoracion`, no en `semana-0`.

## Despliegue

Subido a producción el 14 de octubre de 2026. Tras el despliegue, el formulario público y la consulta de código deben seguir abriendo sin sesión. Un PDF por URL directa en `/public/uploads/teens/` debe dar 403. El maestro con permiso de material debe entrar a Teens.

Pruebas recomendadas en la puerta: registrar un niño, volver a guardar el mismo y confirmar que el código no cambia; el siguiente niño nuevo debe salir con 4 dígitos (`TNS####` o `KS####`).

## Pendiente

- En Permisos existe “Exportar datos teens” y no hay exportación.
- Quien tiene `teen:ver` ve a todos los niños (hace falta en la puerta; no hay aislamiento).
- Kids y Teens siguen en el mismo módulo; solo cambia el prefijo del código.
- No se crean fichas nuevas en Personas desde este formulario. Si el niño no está en el directorio, hay que crearlo en Personas aparte.
