# Limpieza y validación de personas — 24 nov 2026

Trabajo hecho en **producción** (`mcimadridcolombia.com`) y en el código de la app.

## 1. Detección de duplicados

Se consultó la tabla `persona` en producción y se buscaron posibles repetidos por:

- mismo documento (normalizado),
- mismo teléfono (normalizado),
- mismo nombre + apellido (sin acentos).

**Resultado inicial:** ~1465 personas; 0 documentos exactamente iguales; muchos teléfonos compartidos (familia); 28 grupos por nombre.

Se armó un listado de **candidatos fuertes a fusionar**, excluyendo teléfonos familiares (adulto + menores RC/TI).

## 2. Fusiones en producción

Se fusionaron fichas duplicadas (se conservó la más completa, se reasignaron relaciones y se eliminó la otra):

### Prioridad alta (8)
Sara Nicolle Dias Gualteros, Alejandra Gualguan, Emmanuel Martinez Martinez, Elkin Puentes, Erica Milena Gonzalez Rubio, Alejandra Trillos, Kevin Quintero, Isabella Rubio Salom.

### Prioridad media (11)
Luz Helena Pérez Nausan, Aura María Palacios, Viviana Duran, Dinael Montealegre, Yuli Cordoba, Claudia Contreras, Kelly Ríos, Joel Santiago Suárez Ortiz, Sofía Bastidas, Janeth González Cárdenas (cédula corregida a `35495194`), Sebastian Larota.

### Dudosos confirmados (4)
David Torres, Martín Elías Gómez Alfonso, Jaime Chaves; Barbara XXX (la segunda ya no existía). Luego **Coudy Samara / Samara Alfonso Murcia** → quedó `#10689`.

Tras fusiones el total bajó hacia ~1441 personas.

## 3. Barrido de datos basura + 0 asistencias

Criterio:

1. Cédula y/o teléfono **vacíos o basura** (ceros, `XXXX`, placeholders, cédula = celular, etc.).
2. **Y** 0 asistencias reales a célula (`asistencia_celula.Asistio = 1`).

Se clasificaron ~70 fichas; **66 prioritarias** (basura clara + viejas ≥90 días sin asistencia) se **eliminaron definitivamente** en producción, limpiando también referencias hijas (asistencias en 0, inscripciones, accesos, etc.).

**Total en producción tras la limpieza:** 1371 personas.

No se tocaron las 4 restantes (incompletas recientes o con célula asignada sin basura fuerte).

## 4. Prevención a futuro (código)

Nuevo helper `app/Helpers/PersonaValidacion.php`, usado en:

- `PersonaController` (crear/editar interno),
- `RegistroPersonaController` (registro público),
- formularios `views/personas/formulario.php` y `views/personas_publico/formulario.php`.

Reglas:

- Documento: obligatorio, no ceros/X/placeholders, mínimo 5 caracteres, no igual al teléfono, no “parece celular” (`3XXXXXXXXX`).
- Teléfono: obligatorio, ≥7 dígitos, no placeholders.
- Fecha de nacimiento: obligatoria, **no puede ser hoy ni futura** (tope HTML = ayer); sí permite menores nacidos este año con fecha real.

Validación en servidor y aviso en cliente al enviar el formulario interno.

## 5. Celugrama (acciones de miembros)

En la tabla de miembros del Celugrama:

- Botón **Escalera** con ícono SVG de escalera (Bootstrap Icons 1.8.1 no trae `bi-stairs`).
- Botón **No se dispone** (misma lógica que en reporte de asistencias: observación obligatoria, marca escalera, saca de célula, inactiva).

Archivos: `views/celulas/partials/celugrama.php`, `views/celulas/detalle.php`.

## Notas

- Producción se actualiza por **FTP** a Hostinger, no solo con `git push`.
- Los scripts temporales de diagnóstico/fusión usados por FTP se borraron del servidor tras usarse.
- No versionar dumps ni `tools/_prod_db_check` con credenciales de producción.
