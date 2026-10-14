# Programas — Pagos Universidad de la Vida

Cambio del 14 de octubre de 2026. Pantalla: Pagos de Universidad de la Vida, sin red elegida (`escuelas_formacion/pagos/consolidar`). Vista: `views/programas/pagos_redes.php`. Datos: `EscuelaFormacionRegistroController`.

## Qué se ve

Una sola tabla, con dos filas: **Encuentros** y **Libros**. Cada una muestra personas y valor del semestre.

Un clic en la fila abre, en la misma tabla, hombres, mujeres, jóvenes y teens, con personas y valor. Otro clic la cierra. Si alguien no tiene red, aparece **Sin red**.

**Ver listado** en una red abre el listado de personas de esa red. Ese listado sigue mezclando libro y encuentro; la separación está en la tabla de arriba.

## Cómo se cuenta

Se usa lo que ya guardaba el pago. No se cambió el registro, el ticket, el umbral de $180.000 ni las asistencias.

- Encuentros: el total de encuentro de la persona (`total_pagado` / abonos / registros de encuentro).
- Libros: `total_libros` o la marca de libro.
- La red sale de `SegmentoRedUv`: segmento preferido, si no la edad (teens 9–13, jóvenes 14–28) y, para 29+ o sin edad, el género. Un joven no se cuenta también como hombre o mujer.
- Quien pagó las dos cosas aparece en las dos filas. No se suman las personas de las dos filas como si fueran gente distinta. El dinero no se duplica: cada peso queda en su fila.
- Pagos viejos sin categoría siguen la regla que ya había: si hay total de libro, es libro; si no, encuentro.

El semestre es el mismo filtro de la pantalla (fecha de inscripción).

## Despliegue

Subido a producción el 14 de octubre de 2026. No hay SQL.

Tras el despliegue: Pagos de Universidad de la Vida muestra la tabla de Encuentros y Libros, sin las cuatro tarjetas. Un clic abre las redes. Ver listado sigue abriendo las personas de esa red.

## Qué no se cambió

- Guardar, editar o borrar un pago.
- El umbral de pago completo ($180.000).
- Asistencias y el listado persona por persona.
- Las tarjetas de redes de Asistencias (esa pantalla es otra).
