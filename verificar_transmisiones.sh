#!/bin/bash

# Script de verificación de instalación - Sistema de Transmisiones
# Uso: ./verificar_transmisiones.sh

echo "═══════════════════════════════════════════════════════════════"
echo "  VERIFICACIÓN DE INSTALACIÓN - SISTEMA DE TRANSMISIONES"
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Contador
total=0
ok=0

# Función para verificar
verificar() {
    total=$((total + 1))
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $1"
        ok=$((ok + 1))
    else
        echo -e "${RED}✗${NC} $1"
    fi
}

# Verificar archivos de modelo
echo "📁 Verificando Modelo..."
[ -f "app/Models/Transmision.php" ]
verificar "Modelo Transmision.php existe"

# Verificar archivos de controlador
echo ""
echo "📁 Verificando Controlador..."
[ -f "app/Controllers/TransmisionController.php" ]
verificar "Controlador TransmisionController.php existe"

# Verificar vistas privadas
echo ""
echo "📁 Verificando Vistas Privadas..."
[ -f "views/transmisiones/listar.php" ]
verificar "Vista listar.php existe"
[ -f "views/transmisiones/crear.php" ]
verificar "Vista crear.php existe"
[ -f "views/transmisiones/editar.php" ]
verificar "Vista editar.php existe"

# Verificar vista pública
echo ""
echo "📁 Verificando Vista Pública..."
[ -f "views/transmisiones/publico.php" ]
verificar "Vista publico.php existe"

# Verificar SQL
echo ""
echo "📁 Verificando Base de Datos..."
[ -f "agregar_transmisiones.sql" ]
verificar "Script SQL agregar_transmisiones.sql existe"

# Verificar rutas
echo ""
echo "📁 Verificando Configuración..."
grep -q "transmisiones.*TransmisionController" "app/Config/routes.php"
verificar "Rutas configuradas en routes.php"
grep -q "transmisiones-publico" "public/index.php"
verificar "Ruta pública en public/index.php"
grep -q "Transmisiones" "views/layout/header.php"
verificar "Enlace en menú (header.php)"

# Verificar documentación
echo ""
echo "📁 Verificando Documentación..."
[ -f "TRANSMISIONES_README.md" ]
verificar "Documentación TRANSMISIONES_README.md"
[ -f "INSTALACION_TRANSMISIONES.md" ]
verificar "Guía de instalación INSTALACION_TRANSMISIONES.md"
[ -f "IMPLEMENTACION_TRANSMISIONES.md" ]
verificar "Resumen IMPLEMENTACION_TRANSMISIONES.md"

# Resultado final
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "📊 RESULTADO: $ok / $total verificaciones pasadas"
echo "═══════════════════════════════════════════════════════════════"

if [ $ok -eq $total ]; then
    echo -e "${GREEN}✓ Sistema listo para instalar${NC}"
    echo ""
    echo "Próximos pasos:"
    echo "1. Importar agregar_transmisiones.sql en phpMyAdmin"
    echo "2. Acceder a: ?url=transmisiones (admin)"
    echo "3. Acceder a: ?url=transmisiones-publico (público)"
    exit 0
else
    echo -e "${RED}✗ Faltan archivos o configuraciones${NC}"
    exit 1
fi
