# Aislamiento de Datos por Rol - Documentación

## Implementación completada

Se ha implementado un sistema de aislamiento de datos basado en roles para la aplicación MCI Madrid.

### Roles configurados

1. **Administrador del Sistema (Rol 6)**: Acceso total a todos los datos
2. **Líder de Célula (Rol 3)**: Ve solo personas y datos de su célula
3. **Líder de 12 (Rol 8)**: Ve solo personas que lidera directamente
4. **Otros roles**: Acceso restringido

### Cómo funciona

#### 1. Clase DataIsolation.php
Ubicación: `app/Helpers/DataIsolation.php`

Proporciona métodos para generar cláusulas WHERE dinámicas según el rol del usuario:

```php
// Obtener filtro para personas
$filtroPersonas = DataIsolation::generarFiltroPersonas();

// Obtener filtro para células
$filtroCelulas = DataIsolation::generarFiltroCelulas();

// Obtener filtro para asistencias
$filtroAsistencias = DataIsolation::generarFiltroAsistencias();
```

#### 2. Actualización del Modelo Persona
Se agregaron dos métodos nuevos:

- `getAllWithRole($filtroRol)`: Obtiene todas las personas aplicando el filtro de rol
- `getWithFiltersAndRole($idMinisterio, $idLider, $filtroRol)`: Obtiene personas con filtros adicionales

#### 3. Actualización del Controlador Persona
El método `index()` ahora:
- Genera el filtro de rol automáticamente
- Aplica el aislamiento de datos
- Mantiene los filtros adicionales (ministerio, líder)

### Ejemplo de uso en Controllers

```php
// En cualquier Controller
require_once APP . '/Helpers/DataIsolation.php';

// Obtener el filtro según el rol del usuario
$filtroPersonas = DataIsolation::generarFiltroPersonas();

// Usar en una consulta
$personas = $this->personaModel->getAllWithRole($filtroPersonas);
```

### Filtros aplicados por rol

#### Administrador (Rol 6)
- **Personas**: Ve todas
- **Células**: Ve todas
- **Asistencias**: Ve todas
- **Peticiones**: Ve todas
- **Eventos**: Ve todos

#### Líder de Célula (Rol 3)
- **Personas**: Ve solo personas de su célula (según `persona.Id_Celula`)
- **Células**: Ve solo su célula
- **Asistencias**: Ve solo asistencias de su célula
- **Peticiones**: Ve solo peticiones de personas de su célula
- **Eventos**: Ve todos

#### Líder de 12 (Rol 8)
- **Personas**: Ve solo personas que lidera (donde `persona.Id_Lider = usuario_id`)
- **Células**: Ve células donde es líder
- **Asistencias**: Ve asistencias de sus personas
- **Peticiones**: Ve peticiones de sus personas
- **Eventos**: Ve todos

### Próximos pasos para completar la implementación

1. **Actualizar AsistenciaController**
   - Aplicar filtro en método `listar()`
   - Usar `DataIsolation::generarFiltroAsistencias()`

2. **Actualizar CelulaController**
   - Aplicar filtro en método `index()`
   - Usar `DataIsolation::generarFiltroCelulas()`

3. **Actualizar PeticionController**
   - Aplicar filtro en método `listar()`
   - Usar `DataIsolation::generarFiltroPeticiones()`

4. **Agregar métodos similares en modelos**
   - Agregar a `Asistencia.php`
   - Agregar a `Celula.php`
   - Agregar a `Peticion.php`

5. **Actualizar vistas**
   - Ocultar elementos según permisos
   - Mostrar mensaje de acceso restringido si aplica

### Datos de prueba

- **Admin**: usuario: `admin`, password: `admin123` (Rol 6)
- **Líder de Célula**: Ver usuarios con Rol 3
- **Líder de 12**: Ver usuarios con Rol 8

### Configuración en session

El sistema detecta automáticamente:
- `$_SESSION['usuario_id']`: ID de la persona autenticada
- `$_SESSION['usuario_rol']`: ID del rol
- `$_SESSION['usuario_ministerio']`: Ministerio asignado

### Seguridad

✅ El aislamiento se aplica **en la base de datos** (cláusula WHERE)
✅ No solo en la presentación, previniendo acceso no autorizado
✅ Compatible con los permisos existentes del sistema
✅ Transparente: los usuarios solo ven lo que pueden ver

---

**Implementado**: 17/02/2026
**Estado**: Iniciado (PersonaController completo, falta aplicar en otros Controllers)
