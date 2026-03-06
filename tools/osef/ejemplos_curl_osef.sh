# Ejemplos de cURL para Consultar OSEF

## Configuración Base

```bash
# Variables de configuración
export API_URL="http://localhost:8000"
export JWT_TOKEN="tu_token_jwt_aqui"
export USUARIO_OSEF="MI_USUARIO"
export PASSWORD_OSEF="MI_PASSWORD"

# Ejemplo de obtener token (ajusta a tu método de autenticación):
# export JWT_TOKEN=$(curl -X POST http://localhost:8000/login \
#   -d '{"email":"user@example.com","password":"pass"}' \
#   | jq -r '.token')
```

---

## Ejemplo 1: Consultar una Autorización

```bash
curl -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "usuario_osef": "'"${USUARIO_OSEF}"'",
    "password_osef": "'"${PASSWORD_OSEF}"'",
    "tipo_consulta": "autorizacion",
    "numero_autorizacion": 123456,
    "numero_afiliado": "001234567",
    "delegacion": 0
  }' | jq '.'
```

### Con Pretty Print (si tienes `jq`)
```bash
curl -s -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "usuario_osef": "USUARIO",
    "password_osef": "PASSWORD",
    "tipo_consulta": "autorizacion",
    "numero_autorizacion": 123456,
    "delegacion": 0
  }' | jq '.data.datos_afiliado'
```

---

## Ejemplo 2: Consultar un Afiliado

```bash
curl -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "usuario_osef": "USUARIO",
    "password_osef": "PASSWORD",
    "tipo_consulta": "afiliado",
    "numero_afiliado": "001234567",
    "plan": 10,
    "gravamen": 0
  }'
```

---

## Ejemplo 3: Con Variables de Entorno

```bash
#!/bin/bash

# vars_osef.sh - Script de configuración

export API_URL="http://tuapi.local:8000"
export JWT_TOKEN="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
export USUARIO_OSEF="TU_USUARIO_OSEF"
export PASSWORD_OSEF="TU_PASSWORD_OSEF"

# Función para consultar autorización
consultar_autorizacion() {
    local numero_auth="$1"
    local numero_afiliado="${2:-}"
    
    curl -s -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" \
      -H "Authorization: Bearer ${JWT_TOKEN}" \
      -H "Content-Type: application/json" \
      -d "{
        \"usuario_osef\": \"${USUARIO_OSEF}\",
        \"password_osef\": \"${PASSWORD_OSEF}\",
        \"tipo_consulta\": \"autorizacion\",
        \"numero_autorizacion\": ${numero_auth},
        \"numero_afiliado\": \"${numero_afiliado}\",
        \"delegacion\": 0
      }"
}

# Función para consultar afiliado
consultar_afiliado() {
    local numero_afiliado="$1"
    local plan="${2:-0}"
    
    curl -s -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" \
      -H "Authorization: Bearer ${JWT_TOKEN}" \
      -H "Content-Type: application/json" \
      -d "{
        \"usuario_osef\": \"${USUARIO_OSEF}\",
        \"password_osef\": \"${PASSWORD_OSEF}\",
        \"tipo_consulta\": \"afiliado\",
        \"numero_afiliado\": \"${numero_afiliado}\",
        \"plan\": ${plan}
      }"
}

# Uso:
# source vars_osef.sh
# consultar_autorizacion 123456 "001234567" | jq '.data'
# consultar_afiliado "001234567" 10 | jq '.data.datos_afiliado'
```

---

## Ejemplo 4: Parsear Respuesta con grep/sed

```bash
# Extrae solo el status
curl -s -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"usuario_osef":"U","password_osef":"P",...}' \
  | grep -o '"status":"[^"]*"'

# Extrae número de afiliado
curl -s -X POST ... | grep -o '"numero_afiliado":"[^"]*"'

# Con sed (más avanzado):
curl -s -X POST ... | sed -n 's/.*"numero_afiliado":"\([^"]*\)".*/\1/p'
```

---

## Ejemplo 5: Con jq Avanzado

```bash
# Extrae solo datos de afiliado y autorización
curl -s -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{...}' | jq '{
    status: .status,
    afiliado: .data.datos_afiliado,
    autorizacion: .data.datos_autorizacion,
    errores: .data.errores_cabecera
  }'

# Solo si hay errores
curl -s -X POST ... | jq 'if .data.errores_cabecera | length > 0 then .data.errores_cabecera else "Sin errores" end'

# Mostrar tabla de prácticas ambulatorias
curl -s -X POST ... | jq -r '.data.ambulatorio[] | "\(.codigo_practica) \(.alias_practica) \(.monto_autorizado)"' | column -t
```

---

## Ejemplo 6: Loop de Consultas

```bash
#!/bin/bash

# Consultar múltiples afiliados desde un archivo CSV

if [ ! -f "afiliados.csv" ]; then
    echo "001234567,10"
    echo "001234568,10"
    echo "001234569,15"
fi > afiliados.csv

while IFS=',' read -r afiliado plan; do
    echo "Consultando afiliado: $afiliado"
    
    RESP=$(curl -s -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" \
      -H "Authorization: Bearer ${JWT_TOKEN}" \
      -H "Content-Type: application/json" \
      -d "{
        \"usuario_osef\": \"${USUARIO_OSEF}\",
        \"password_osef\": \"${PASSWORD_OSEF}\",
        \"tipo_consulta\": \"afiliado\",
        \"numero_afiliado\": \"${afiliado}\",
        \"plan\": ${plan}
      }")
    
    STATUS=$(echo "$RESP" | jq -r '.status')
    NOMBRE=$(echo "$RESP" | jq -r '.data.datos_afiliado.nombre_apellido // "N/A"')
    ESTADO=$(echo "$RESP" | jq -r '.data.datos_afiliado.estado')
    
    echo "$afiliado,$NOMBRE,$ESTADO,$STATUS" >> resultado.csv
    
    sleep 1  # Evitar saturar OSEF
    
done < afiliados.csv

cat resultado.csv
```

---

## Ejemplo 7: Con Validación de Token Expirado

```bash
#!/bin/bash

API_URL="http://localhost:8000"
EMAIL="usuario@example.com"
PASSWORD="su_password"

# Obtiene token
get_token() {
    curl -s -X POST "${API_URL}/login" \
      -H "Content-Type: application/json" \
      -d "{\"email\":\"${EMAIL}\",\"password\":\"${PASSWORD}\"}" \
      | jq -r '.token'
}

# Consulta con reintentos si token expira
consultar_con_reintentos() {
    local endpoint="$1"
    local payload="$2"
    local max_intentos=3
    local intento=0
    
    while [ $intento -lt $max_intentos ]; do
        local token=$(get_token)
        
        local response=$(curl -s -X POST "${API_URL}${endpoint}" \
          -H "Authorization: Bearer ${token}" \
          -H "Content-Type: application/json" \
          -d "${payload}")
        
        # Si la respuesta es un error de autenticación, reintentar
        if echo "$response" | jq -e '.error | contains("Unauthorized")' > /dev/null 2>&1; then
            echo "Token inválido, obteniendo nuevo..." >&2
            ((intento++))
            sleep 2
        else
            echo "$response"
            return 0
        fi
    done
    
    echo "Error: No se pudo obtener respuesta válida tras $max_intentos intentos" >&2
    return 1
}

# Uso
consultar_con_reintentos "/int/afiliaciones/afiliado/prueba-osef" '{
    "usuario_osef": "USUARIO",
    "password_osef": "PASSWORD",
    "tipo_consulta": "autorizacion",
    "numero_autorizacion": 123456
}'
```

---

## Ejemplo 8: Guardar en Base de Datos

```bash
#!/bin/bash

API_URL="http://localhost:8000"
JWT_TOKEN="token_aqui"
USUARIO_OSEF="USUARIO"
PASSWORD_OSEF="PASSWORD"

# Tabla SQLite para testing
sqlite3 consultas_osef.db "CREATE TABLE IF NOT EXISTS consultas (
    id INTEGER PRIMARY KEY,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo TEXT,
    parametro TEXT,
    status TEXT,
    respuesta TEXT
);"

# Consulta y guarda resultado
consultar_y_guardar() {
    local tipo="$1"
    local parametro="$2"
    
    local resp=$(curl -s -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" \
      -H "Authorization: Bearer ${JWT_TOKEN}" \
      -H "Content-Type: application/json" \
      -d "{
        \"usuario_osef\": \"${USUARIO_OSEF}\",
        \"password_osef\": \"${PASSWORD_OSEF}\",
        \"tipo_consulta\": \"${tipo}\",
        \"numero_autorizacion\": ${parametro}
      }")
    
    local status=$(echo "$resp" | jq -r '.status')
    
    sqlite3 consultas_osef.db "INSERT INTO consultas (tipo, parametro, status, respuesta) 
        VALUES ('${tipo}', '${parametro}', '${status}', '$(echo "$resp" | jq -c . | sed "s/'/''/g")');"
    
    echo "$resp"
}

# Uso
consultar_y_guardar "autorizacion" 123456
```

---

## Troubleshooting

### Error: "Unauthorized"
```bash
# Regenera el token:
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"email":"tu_email@example.com","password":"tu_password"}'

# O usa un token válido del navegador (DevTools > Application > Cookies)
```

### Error: "CORS"
```bash
# Si obtienes error de CORS, verifica headers en tu API
# El endpoint debe estar configurado en config/cors.php
```

### Timeout
```bash
# Aumenta timeout de curl
curl --max-time 120 -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" ...
```

### Error SOAP "Invalid credentials"
```bash
# Valida que usuario_osef y password_osef sean correctos
# Contacta a OSEF: validador.osef.gob.ar
```

### Guardar respuesta en archivo
```bash
curl -X POST "${API_URL}/int/afiliaciones/afiliado/prueba-osef" \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{...}' > respuesta_osef_$(date +%s).json
```

