# API REST - MCI Madrid Colombia

API REST para consumir desde aplicación móvil React Native.

## Base URL
```
https://www.mcimadridcolombia.com/api/
```

## Endpoints Disponibles

### 🔐 Autenticación

#### Login
```http
POST /api/auth.php
Content-Type: application/json

{
  "usuario": "admin",
  "password": "123456"
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "user": {
      "Id_Persona": 12,
      "Nombre": "Admin",
      "Apellido": "Sistema",
      "Usuario": "admin",
      "Codigo_Rol": "ADMIN"
    },
    "session_id": "abc123..."
  },
  "message": "Login exitoso"
}
```

#### Verificar Sesión
```http
GET /api/auth.php
Authorization: Bearer {session_id}
```

#### Logout
```http
DELETE /api/auth.php
Authorization: Bearer {session_id}
```

---

### 👥 Personas

#### Listar todas las personas
```http
GET /api/personas.php
Authorization: Bearer {session_id}
```

#### Filtrar por ministerio
```http
GET /api/personas.php?ministerio=6
Authorization: Bearer {session_id}
```

#### Filtrar por líder
```http
GET /api/personas.php?lider=43
Authorization: Bearer {session_id}
```

#### Obtener persona específica
```http
GET /api/personas.php?id=12
Authorization: Bearer {session_id}
```

#### Crear persona
```http
POST /api/personas.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "Nombre": "Juan",
  "Apellido": "Pérez",
  "Tipo_Documento": "Cedula de Ciudadania",
  "Numero_Documento": "1234567890",
  "Telefono": "3001234567",
  "Email": "juan@example.com",
  "Id_Rol": 3,
  "Id_Lider": 43
}
```

#### Actualizar persona
```http
PUT /api/personas.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "Id_Persona": 50,
  "Nombre": "Juan Carlos",
  "Telefono": "3009999999"
}
```

#### Eliminar persona
```http
DELETE /api/personas.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "id": 50
}
```

---

### 🏠 Células

#### Listar todas las células
```http
GET /api/celulas.php
Authorization: Bearer {session_id}
```

#### Obtener célula específica (con miembros)
```http
GET /api/celulas.php?id=1
Authorization: Bearer {session_id}
```

#### Crear célula
```http
POST /api/celulas.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "Nombre_Celula": "Célula Centro",
  "Direccion_Celula": "Calle 10 #5-20",
  "Dia_Reunion": "Viernes",
  "Hora_Reunion": "19:00:00",
  "Id_Lider": 43
}
```

#### Actualizar célula
```http
PUT /api/celulas.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "Id_Celula": 1,
  "Nombre_Celula": "Célula Centro Actualizada",
  "Hora_Reunion": "20:00:00"
}
```

#### Eliminar célula
```http
DELETE /api/celulas.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "id": 1
}
```

---

## Formato de Respuestas

Todas las respuestas siguen este formato:

```json
{
  "success": true|false,
  "data": {...} | [...] | null,
  "message": "Mensaje descriptivo"
}
```

### Códigos HTTP
- `200` - OK
- `201` - Created
- `400` - Bad Request (datos inválidos)
- `401` - Unauthorized (sin autenticación)
- `404` - Not Found
- `405` - Method Not Allowed
- `500` - Internal Server Error

---

## Pruebas con Postman/Thunder Client

1. Hacer login para obtener `session_id`
2. Usar el `session_id` como Bearer token en headers:
   ```
   Authorization: Bearer {session_id}
   ```
3. Todas las peticiones deben incluir:
   ```
   Content-Type: application/json
   ```

---

## 🏛️ Ministerios

#### Listar ministerios
```http
GET /api/ministerios.php
Authorization: Bearer {session_id}
```

#### Crear ministerio
```http
POST /api/ministerios.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "Nombre_Ministerio": "Ministerio de Jóvenes",
  "Descripcion_Ministerio": "Ministerio enfocado en la juventud"
}
```

---

## 🎭 Roles

#### Listar roles
```http
GET /api/roles.php
Authorization: Bearer {session_id}
```

#### Crear rol
```http
POST /api/roles.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "Nombre_Rol": "Diácono",
  "Codigo_Rol": "DIACONO",
  "Descripcion_Rol": "Rol de diácono"
}
```

---

## 🎉 Eventos

#### Listar eventos
```http
GET /api/eventos.php
Authorization: Bearer {session_id}
```

#### Crear evento
```http
POST /api/eventos.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "Nombre_Evento": "Culto Dominical",
  "Descripcion_Evento": "Servicio principal",
  "Fecha_Evento": "2025-12-14 10:00:00",
  "Lugar_Evento": "Iglesia Principal"
}
```

---

## ✅ Asistencias

#### Listar asistencias
```http
GET /api/asistencias.php
Authorization: Bearer {session_id}
```

#### Filtrar por evento
```http
GET /api/asistencias.php?evento=1
Authorization: Bearer {session_id}
```

#### Registrar asistencia
```http
POST /api/asistencias.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "Id_Persona": 50,
  "Id_Evento": 1,
  "Id_Celula": 2,
  "Estado": "Presente"
}
```

---

## 🙏 Peticiones

#### Listar peticiones
```http
GET /api/peticiones.php
Authorization: Bearer {session_id}
```

#### Filtrar por estado
```http
GET /api/peticiones.php?estado=Pendiente
Authorization: Bearer {session_id}
```

#### Crear petición
```http
POST /api/peticiones.php
Authorization: Bearer {session_id}
Content-Type: application/json

{
  "Id_Persona": 50,
  "Descripcion_Peticion": "Oración por salud",
  "Tipo_Peticion": "Salud",
  "Estado": "Pendiente"
}
```

---

## 📊 Reportes

#### Dashboard general
```http
GET /api/reportes.php?tipo=dashboard
Authorization: Bearer {session_id}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "total_personas": 120,
    "total_celulas": 10,
    "nuevos_miembros_mes": 15,
    "personas_por_rol": [...],
    "personas_por_ministerio": [...]
  }
}
```

#### Almas ganadas
```http
GET /api/reportes.php?tipo=almas_ganadas&fecha_inicio=2025-01-01&fecha_fin=2025-12-31
Authorization: Bearer {session_id}
```

#### Asistencias por período
```http
GET /api/reportes.php?tipo=asistencias_periodo&fecha_inicio=2025-12-01&fecha_fin=2025-12-31
Authorization: Bearer {session_id}
```

#### Personas por ministerio
```http
GET /api/reportes.php?tipo=personas_ministerio
Authorization: Bearer {session_id}
```

#### Personas por líder
```http
GET /api/reportes.php?tipo=personas_lider
Authorization: Bearer {session_id}
```

#### Estadísticas de células
```http
GET /api/reportes.php?tipo=celulas_stats
Authorization: Bearer {session_id}
```

---

## ✅ Endpoints Completados

- [x] `/api/auth.php` - Autenticación
- [x] `/api/personas.php` - Gestión de personas
- [x] `/api/celulas.php` - Gestión de células
- [x] `/api/ministerios.php` - Gestión de ministerios
- [x] `/api/roles.php` - Gestión de roles
- [x] `/api/eventos.php` - Gestión de eventos
- [x] `/api/asistencias.php` - Registro de asistencias
- [x] `/api/peticiones.php` - Gestión de peticiones
- [x] `/api/reportes.php` - Reportes y estadísticas
