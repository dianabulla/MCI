# Escalera del éxito y Almas ganadas

Registro del cambio del 9 de octubre de 2026.

## Entrada del módulo

El menú y la tarjeta del inicio se llaman **Escalera del éxito** y abren `personas`.

Quien solo tiene permiso de Almas ganadas entra directo a `personas/ganar`.

## Escalera del éxito (`personas`)

Arriba, **Por conectar a célula o líder**: personas con ministerio que les falta célula o líder.

Abajo, **Etapas de la escalera**: personas activas que no son de liderazgo, con filtro por etapa (Ganar, Consolidar, Discipular, Enviar o sin etapa).

Quien no tiene ministerio no aparece aquí. Sigue en Almas ganadas.

## Almas ganadas (`personas/ganar`)

Solo personas nuevas. Quedan fuera Escuelas de Formación y el Tour Levántate y Resplandece, también en la búsqueda.

Si la semana se deja vacía, se ven todas las que siguen pendientes. Elegir una fecha limita a esa semana (lunes a domingo). El historial muestra el año elegido, incluidas las que ya están ubicadas.

El número de cada tarjeta y las filas de la tabla usan la misma clasificación:

| Tarjeta | Quién entra |
| --- | --- |
| Ganados en célula | Origen Célula |
| Ganados en iglesia | Origen Domingo o Iglesia |
| Somos Uno | Origen Somos Uno |
| Otros | Origen Otros |
| Asignados | Sin origen de ganancia, con líder o ministerio |
| Reasignados | Cambio de líder, o pasaron 48 horas sin contacto o sin célula |
| No se dispone | Marcados como no concretados |
| En seguimiento | Personas nuevas de esta misma pantalla marcadas en seguimiento |

Un enlace antiguo `origen=iglesia` abre Ganados en iglesia.

## Regla de 48 horas

El plazo empieza cuando la persona tiene líder y ministerio (`Fecha_Asignacion_Lider`).

Si en 48 horas no hubo primer contacto o no quedó en una célula, vuelve a **Reasignados**: se le quita el líder y el ministerio para asignarle otro. La marca queda en el checklist como `reasignado_automatico`.

No se mueve a quien está en «No se dispone», ni a quien ya fue contactado y ya tiene célula. Quien nunca tuvo fecha de asignación no entra en esta regla.

La revisión corre al abrir Almas ganadas. La primera visita puede mover asignaciones que ya superaron las 48 horas.

## Aviso de personas nuevas

La campana cuenta la misma población pendiente de Almas ganadas: persona nueva, sin escuela ni tour, sin «No se dispone», y con ministerio, líder o célula faltante.

## Informe aparte

El botón **Reporte almas ganadas** abre el informe de personas. Ahí «Ganados en iglesia» todavía suma Domingo, Somos Uno y Otros. Esa cifra puede ser mayor que la tarjeta Domingo de esta pantalla.
