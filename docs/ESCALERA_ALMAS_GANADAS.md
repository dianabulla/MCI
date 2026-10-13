# Escalera del éxito y Almas ganadas

Registro al 13 de octubre de 2026.

## Entrada del módulo

El menú y la tarjeta del inicio se llaman **Escalera del éxito** y abren `personas`. La pantalla antigua `personas/escalera` ya no existe.

Quien solo tiene permiso de Almas ganadas entra directo a `personas/ganar`.

## Escalera del éxito (`personas`)

**Por conectar a célula o líder.** Personas con ministerio a las que les falta la célula, el líder, o los dos. La lista se abre al pulsar la tarjeta. La columna **Falta** dice qué les falta. Quien ya tiene líder sigue aquí si no tiene célula.

**En seguimiento.** Salen de Por conectar cuando se les guarda una nota de seguimiento. También entran quienes vienen de Almas ganadas y solo asisten a la iglesia (aún sin ministerio). Cuando ya tienen líder y célula, salen de esta lista.

**Etapas de la escalera.** Personas activas que no son de liderazgo, con filtro por etapa (Ganar, Consolidar, Discipular, Enviar o sin etapa). Esta lista no cambia al marcar seguimiento.

Quien no tiene ministerio y no está en seguimiento sigue en Almas ganadas.

## Almas ganadas (`personas/ganar`)

Solo personas nuevas. Quedan fuera Escuelas de Formación y el Tour Levántate y Resplandece, también en la búsqueda.

Si la semana se deja vacía, se ven todas las pendientes, de cualquier fecha. Elegir una fecha limita a esa semana (lunes a domingo). El botón Historial ya no está: la lista de trabajo es la de pendientes, no el archivo del año.

El origen (Célula, Domingo, Somos Uno u Otros) queda guardado en la ficha y se ve en el reporte. En esta pantalla la lista general es **Nuevos**. Las otras tarjetas filtran aparte y no se mezclan con Nuevos:

| Tarjeta | Quién entra | Sale de Nuevos |
| --- | --- | --- |
| Nuevos | Les falta líder o ministerio | — |
| Asignados | Sin origen de ganancia, con líder o ministerio | Siguen en Nuevos si aún les falta el líder o el ministerio |
| Reasignados | Cambio de líder, o pasaron 4 días sin contacto o sin célula | Sí |
| No se dispone | Marcados como no concretados | Sí |

Una persona sale de **Nuevos** cuando ya tiene líder y ministerio, o cuando pasa a En seguimiento, Reasignados o No se dispone. La célula no la retiene en Nuevos: si ya tiene líder y ministerio y le falta célula, sigue en Discípulos, en Por conectar.

## Célula, líder y ministerio

Si la persona está en una célula y el líder o el ministerio están vacíos, se llenan con el líder de esa célula y el ministerio de ese líder (o el ministerio guardado en la célula). No se reemplaza un líder o ministerio que ya esté elegido, ni se asigna a la persona como su propio líder.

Al guardar una ficha nueva o editada con célula, ocurre lo mismo.

## Regla de 4 días

El plazo empieza cuando la persona tiene líder y ministerio (`Fecha_Asignacion_Lider`).

Si en 4 días no quedó en una célula, vuelve a **Reasignados**: se le quita el líder y el ministerio para asignarle otro. La marca queda en el checklist como `reasignado_automatico`.

No se mueve a quien está en «No se dispone», a quien está en seguimiento, ni a quien ya tiene célula. Quien nunca tuvo fecha de asignación no entra en esta regla.

La revisión corre al abrir Almas ganadas.

## Aviso de personas nuevas

La campana de **Almas ganadas** cuenta personas nuevas, sin escuela ni tour, a las que les falta líder o ministerio. No entran seguimiento, reasignados ni «No se dispone».

La campana de **Discípulos** cuenta personas que ya tienen ministerio y les falta líder o célula. En seguimiento no entra en ese aviso.

Quien tiene ministerio y todavía no tiene líder puede verse en las dos listas: sigue en Nuevos hasta tener líder y ministerio, y en Discípulos porque le falta el líder.

## Informe aparte

El botón **Reporte almas ganadas** abre el informe de personas. Ahí el origen sigue separado:

- **Ganados en iglesia** cuenta solo Domingo o Iglesia.
- **Somos Uno** cuenta ese origen.
- **Otros** cuenta el origen Otros y lo que antes entraba en iglesia sin ser Domingo ni Somos Uno.

La cifra del informe puede seguir siendo mayor que la tarjeta de Almas ganadas: el informe cuenta las personas nuevas registradas en el rango, y la tarjeta cuenta las que siguen pendientes. El informe ministerial sigue agrupando Domingo, Somos Uno y Otros como ganados en iglesia.

## Formularios de talleres

Cada taller se edita por su cuenta. El bloque de datos personales, líder y ministerio va fijo en todos y no se arma como pregunta del taller. En el formulario público, líder y ministerio salen de la ficha al buscar la cédula y quedan solo de lectura. Lo que sí se guarda por taller es el nombre, la descripción, el mensaje al enviar, el texto de autorización, si el enlace está activo o cerrado, y las preguntas de los bloques de abajo.

Guardar exige permiso de crear o de editar. El nombre y las preguntas se escriben juntos: si las preguntas no entran, el nombre tampoco queda a medias. Al terminar bien aparece «Formulario guardado correctamente», arriba y junto al botón. Si el servidor lo rechaza, el aviso rojo queda en los dos sitios e incluye el motivo. Si falta el título de un bloque o el texto de una pregunta, el navegador indica cuál es y no envía el formulario.

En Presentación de niños, los bloques de padres, niño y documentos no se borran al guardar. Las preguntas adicionales se conservan al volver a abrir el editor.

Los botones de guardar, ver respuestas y abrir formulario van al final del editor, en el flujo de la página, para no tapar el enlace para compartir.

## Creación de personas desde talleres y escuelas

Talleres, Universidad de la Vida y Capacitación Destino no crean la ficha por su cuenta. Si la persona no está en el registro, abren `personas/crear` con lo que ya se escribió: nombre, apellidos, tipo y número de documento, teléfono, dirección, género, fecha de nacimiento y, si venía elegido, ministerio y líder. Al guardar, se vuelve al formulario de origen y se busca de nuevo por cédula.

Ese formulario sigue exigiendo los mismos datos: nombre, apellidos, tipo de documento, número de documento, fecha de nacimiento, género, teléfono y tratamiento de datos. Dirección, barrio, célula, ministerio y líder siguen siendo opcionales.

Hace falta estar dentro de la aplicación con permiso para crear personas. Quien no ha iniciado sesión llega primero al login.

En un taller, incluido el Tour Levántate y Resplandece, la inscripción no se guarda hasta que la persona exista. Si desde las respuestas se asigna ministerio a una inscripción sin ficha, se abre el mismo formulario de personas, con el ministerio ya elegido.

En presentación de niños, el acudiente y el niño que no estén en el registro tienen el enlace al mismo formulario. Esa inscripción puede guardarse en el taller: el niño queda en la inscripción y no como ficha nueva.
