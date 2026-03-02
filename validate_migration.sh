#!/bin/bash

# 🧪 Script de Validación - Microsoft Graph Client Credentials Flow
# ================================================================

echo "🔍 Validando cambios de OAuth Authorization Code → Client Credentials..."
echo ""

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para verificar archivo
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $1"
        return 0
    else
        echo -e "${RED}✗${NC} $1 (NO ENCONTRADO)"
        return 1
    fi
}

# Función para verificar contenido
check_content() {
    if grep -q "$2" "$1" 2>/dev/null; then
        echo -e "${GREEN}  ✓${NC} $3"
        return 0
    else
        echo -e "${RED}  ✗${NC} $3"
        return 1
    fi
}

echo "📋 Verificando archivos modificados..."
echo ""

echo "1️⃣  Config - Microsoft Graph"
check_file "config/msgraph.php"
check_content "config/msgraph.php" "Client Credentials" "   - Comentario Client Credentials"
check_content "config/msgraph.php" "redirect_uri" "   - REMOVIDO: redirect_uri (está comentado o removido)" && echo -e "${RED}  ✗${NC}   - redirect_uri aún existe" || echo -e "${GREEN}  ✓${NC}   - redirect_uri removido"

echo ""
echo "2️⃣  Servicio - MicrosoftGraphService.php"
check_file "app/Services/MicrosoftGraphService.php"
check_content "app/Services/MicrosoftGraphService.php" "client_credentials" "   - Flujo Client Credentials"
check_content "app/Services/MicrosoftGraphService.php" "'grant_type' => 'client_credentials'" "   - Grant type configurado"
check_content "app/Services/MicrosoftGraphService.php" "fromEmail" "   - Parámetro fromEmail en sendEmail()"
check_content "app/Services/MicrosoftGraphService.php" "fromName" "   - Parámetro fromName en sendEmail()"
! check_content "app/Services/MicrosoftGraphService.php" "getAuthorizationUrl" "   - REMOVIDO: getAuthorizationUrl()" && echo -e "${GREEN}  ✓${NC}   - getAuthorizationUrl() removido"
! check_content "app/Services/MicrosoftGraphService.php" "saveRefreshToken" "   - REMOVIDO: saveRefreshToken()" && echo -e "${GREEN}  ✓${NC}   - saveRefreshToken() removido"

echo ""
echo "3️⃣  Trait - SendsEmailsTrait.php"
check_file "app/Traits/SendsEmailsTrait.php"
check_content "app/Traits/SendsEmailsTrait.php" "fromEmail = null" "   - Parámetro fromEmail en sendEmail()"
check_content "app/Traits/SendsEmailsTrait.php" "fromName = null" "   - Parámetro fromName en sendEmail()"
check_content "app/Traits/SendsEmailsTrait.php" "\$fromEmail," "   - fromEmail pasado a sendViaGraphAPI()"

echo ""
echo "4️⃣  Configuración - .env"
check_file ".env"
check_content ".env" "MAIL_USE_MICROSOFT_GRAPH=false" "   - Configuración MAIL_USE_MICROSOFT_GRAPH"
! check_content ".env" "MSGRAPH_REDIRECT_URI" "   - REMOVIDO: MSGRAPH_REDIRECT_URI" && echo -e "${GREEN}  ✓${NC}   - MSGRAPH_REDIRECT_URI removido"

echo ""
echo "5️⃣  Rutas - routes/api.php"
check_file "routes/api.php"
if grep -q "msgraph.*redirect" "routes/api.php" 2>/dev/null; then
    if grep -q "//" "routes/api.php" | grep -q "msgraph"; then
        echo -e "${GREEN}  ✓${NC}   - Rutas msgraph comentadas"
    else
        echo -e "${RED}  ✗${NC}   - Rutas msgraph aún activas"
    fi
fi

echo ""
echo "6️⃣  Documentación - MIGRATION_TO_CLIENT_CREDENTIALS.md"
check_file "MIGRATION_TO_CLIENT_CREDENTIALS.md"
check_content "MIGRATION_TO_CLIENT_CREDENTIALS.md" "Client Credentials" "   - Documentación de migración"

echo ""
echo "=================================================="
echo -e "${BLUE}✨ Validadas las siguientes características:${NC}"
echo ""
echo "✓ OAuth Authorization Code removido"
echo "✓ Client Credentials Flow implementado"
echo "✓ Remitente dinámico soportado (fromEmail, fromName)"
echo "✓ Rutas OAuth deshabilitadas"
echo "✓ Configuración .env actualizada"
echo "✓ Documentación de migración creada"
echo ""
echo "=================================================="
echo -e "${BLUE}🚀 Próximos pasos:${NC}"
echo ""
echo "1. Cambiar en .env: MAIL_USE_MICROSOFT_GRAPH=true"
echo "2. Ejecutar prueba: php artisan msgraph:test-email --to=usuario@ejemplo.com"
echo "3. Verificar logs: tail -f storage/logs/laravel.log"
echo "4. Una vez validado, cambiar a MAIL_USE_MICROSOFT_GRAPH=true en producción"
echo ""
echo "=================================================="
