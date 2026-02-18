# Importación de Datos Nehemías desde Excel

## Pasos para importar los datos

### Opción 1: Usando el script de importación web

1. **Preparar el archivo Excel:**
   - Abra su archivo Excel con los datos
   - Vaya a `Archivo > Guardar como`
   - Seleccione el formato `CSV (delimitado por tabulaciones) (*.txt)`
   - Guarde el archivo

2. **Subir el archivo al servidor:**
   - Suba el archivo `importar_nehemias.php` a la raíz del proyecto
   - Acceda desde el navegador: `http://localhost/mcimadrid/importar_nehemias.php`
   - O en producción: `https://www.mcimadridcolombia.com/importar_nehemias.php`

3. **Importar:**
   - Seleccione el archivo CSV
   - Haga clic en "Importar Datos"
   - Revise el resumen de la importación

### Opción 2: Importación directa desde SQL (más rápida para archivos grandes)

1. **Convertir Excel a CSV:**
   - Guardar el Excel como CSV (UTF-8 delimitado por comas)

2. **Subir el CSV al servidor:**
   - Coloque el archivo en `c:\xampp\htdocs\mcimadrid\datos_nehemias.csv`

3. **Ejecutar el script de importación directa:**
   - Acceda a: `http://localhost/mcimadrid/importar_nehemias_directo.php`

## Estructura del archivo CSV/Excel

El archivo debe tener las siguientes columnas en este orden:

1. NOMBRES
2. APELLIDOS
3. NUMERO DE CEDULA
4. TELEFONO
5. LIDER NEHEMIAS
6. LIDER
7. Subido link de nehemias
8. EN BOGOTA SE LE SUBIO
9. PUESTO DE VOTACION
10. MESA DE VOTACIÓN

**Nota:** La primera fila debe contener los nombres de las columnas (encabezados).

## Comportamiento del script

- **Registros duplicados:** Si una cédula ya existe en la base de datos, el registro se omitirá
- **Validación:** Se verifican los campos obligatorios (Nombres, Apellidos, Cédula)
- **Fecha:** Se registra automáticamente la fecha y hora de importación

## Solución de problemas

### El archivo Excel es muy grande
- Divida el archivo en varios archivos más pequeños (500-1000 registros cada uno)
- Importe cada archivo por separado

### Errores de caracteres especiales
- Asegúrese de guardar el CSV con codificación UTF-8
- En Excel: `Guardar como > CSV UTF-8 (delimitado por comas)`

### Timeout durante la importación
- El script tiene un límite de 5 minutos
- Si el archivo es muy grande, use la opción 2 (importación directa)

## Verificación post-importación

Después de importar, verifique:
1. Acceda a la lista: `http://localhost/mcimadrid/public/?url=nehemias/lista`
2. Revise que los registros se hayan importado correctamente
3. Exporte a Excel para validar los datos

## Contacto

Para soporte técnico, contacte al administrador del sistema.
