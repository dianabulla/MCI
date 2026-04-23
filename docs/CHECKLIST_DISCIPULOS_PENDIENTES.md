# Checklist funcional: Discipulos vs Pendientes

Objetivo: validar que no se rompa la separacion de reglas entre tarjetas, campana y panel de notificaciones.

## Reglas esperadas

1. Tarjeta Discipulos:
- Debe mostrar personas nuevas o antiguas con asignacion completa.
- Asignacion completa = Tiene ministerio + lider + celula.

2. Tarjeta Pendientes por conectar a celula:
- Solo rol discipulo/disipulo.
- Solo personas antiguas.
- Debe tener al menos una asignacion faltante: ministerio o lider o celula.
- No deben aparecer personas nuevas.

3. Campana y panel lateral:
- Pendientes por conectar debe seguir la misma regla del punto 2.
- Nuevas en Almas ganadas se muestra aparte (categoria separada).

## Prueba manual rapida (5 minutos)

1. Ir a modulo Discipulos.
2. Click en tarjeta Discipulos:
- Confirmar que todas las filas visibles tienen ministerio, lider y celula.

3. Click en tarjeta Pendientes por conectar a celula:
- Confirmar que todas las filas son antiguas.
- Confirmar que todas son rol discipulo/disipulo.
- Confirmar que cada fila tiene al menos 1 faltante (ministerio/lider/celula).
- Confirmar que no aparece ninguna fila nueva.

4. Abrir campana (panel lateral):
- Revisar numero de Pendientes por conectar.
- Entrar al acceso de gestion y comprobar que coincide con lo visto en la tarjeta de pendientes.

5. Revisar Nuevas en Almas ganadas:
- Debe mantenerse separado de Pendientes por conectar.

## Casos borde que deben seguir funcionando

1. Roles pastor/lider de 12/lider de celula no deben contaminar Pendientes por conectar.
2. Personas marcadas como no disponible no deben aparecer en pendientes.
3. Cambiar filtros por ministerio o lider no debe romper la regla de clasificacion.

## Checklist de regresion tecnica

1. Ejecutar:
- C:\xampp\php\php.exe -l app/Controllers/PersonaController.php
- C:\xampp\php\php.exe -l views/personas/lista.php
- C:\xampp\php\php.exe -l views/layout/header.php
- C:\xampp\php\php.exe -l views/personas/notificaciones.php

2. Si un numero sube de forma inesperada:
- Validar primero si entraron personas nuevas en pendientes (no deberia).
- Validar si se incluyeron roles no discipulo/disipulo por error.
- Validar si se cambio la regla de incompleto (ministerio/lider/celula).
