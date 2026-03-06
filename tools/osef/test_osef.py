#!/usr/bin/env python3
"""
Script de prueba para consultar el servicio SOAP de OSEF
Uso: python3 test_osef.py --tipo autorizacion --numero 123456 --usuario MI_USER --password MI_PASS
"""

import requests
import json
import argparse
import sys
from urllib3.exceptions import InsecureRequestWarning

# Desactiva warning de SSL si es necesario (NO en producción)
# urllib3.disable_warnings(InsecureRequestWarning)

# URL del endpoint de tu API BPay
API_BASE_URL = "http://localhost:8000"  # Cambia a tu URL real
ENDPOINT = f"{API_BASE_URL}/int/afiliaciones/afiliado/prueba-osef"

# Token JWT - obtén uno válido de tu sistema
# En desarrollo, puedes usar un token válido del usuario autenticado
AUTH_TOKEN = "tu_token_jwt_aqui"

def consultar_autorizacion(usuario, password, numero_autorizacion, delegacion=0, numero_afiliado="", jwt_token=AUTH_TOKEN):
    """
    Consulta una autorización en OSEF
    """
    payload = {
        "usuario_osef": usuario,
        "password_osef": password,
        "tipo_consulta": "autorizacion",
        "numero_autorizacion": numero_autorizacion,
        "numero_afiliado": numero_afiliado,
        "delegacion": delegacion
    }
    
    headers = {
        "Authorization": f"Bearer {jwt_token}",
        "Content-Type": "application/json"
    }
    
    print(f"\n=== Consultando Autorización ===")
    print(f"URL: {ENDPOINT}")
    print(f"Payload: {json.dumps({**payload, 'password_osef': '***'}, indent=2)}")
    
    try:
        response = requests.post(ENDPOINT, json=payload, headers=headers, timeout=60)
        
        print(f"\nStatus Code: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            print(f"Response:\n{json.dumps(data, indent=2, ensure_ascii=False)}")
            
            # Análisis de la respuesta
            if data.get('status') == 'ok':
                resp_data = data.get('data', {})
                print("\n=== RESPUESTA EXITOSA ===")
                
                if 'datos_afiliado' in resp_data:
                    print("\nDatos del Afiliado:")
                    for key, value in resp_data['datos_afiliado'].items():
                        print(f"  {key}: {value}")
                
                if 'datos_autorizacion' in resp_data:
                    print("\nDatos de Autorización:")
                    for key, value in resp_data['datos_autorizacion'].items():
                        print(f"  {key}: {value}")
                
                if 'errores_cabecera' in resp_data and resp_data['errores_cabecera']:
                    print("\nErrores Cabecera:")
                    for err in resp_data['errores_cabecera']:
                        print(f"  - {err}")
                
                if 'ambulatorio' in resp_data and resp_data['ambulatorio']:
                    print("\nPrácticas Ambulatorias:")
                    for amb in resp_data['ambulatorio']:
                        print(f"  - {amb.get('alias_practica', 'N/A')}: ${amb.get('monto_autorizado', 0)}")
            else:
                print("\n=== ERROR EN RESPUESTA ===")
                print(f"Mensaje: {data.get('message')}")
                print(f"Errores: {data.get('errors')}")
                
        else:
            print(f"\nError HTTP: {response.status_code}")
            print(f"Response: {response.text}")
            
    except requests.exceptions.Timeout:
        print("Error: Timeout en la conexión")
        sys.exit(1)
    except requests.exceptions.ConnectionError as e:
        print(f"Error de conexión: {e}")
        print(f"¿Está el servidor en {API_BASE_URL}?")
        sys.exit(1)
    except Exception as e:
        print(f"Error inesperado: {e}")
        sys.exit(1)


def consultar_afiliado(usuario, password, numero_afiliado, plan=0, gravamen=0, jwt_token=AUTH_TOKEN):
    """
    Consulta datos de un afiliado en OSEF
    """
    payload = {
        "usuario_osef": usuario,
        "password_osef": password,
        "tipo_consulta": "afiliado",
        "numero_afiliado": numero_afiliado,
        "plan": plan,
        "gravamen": gravamen
    }
    
    headers = {
        "Authorization": f"Bearer {jwt_token}",
        "Content-Type": "application/json"
    }
    
    print(f"\n=== Consultando Afiliado ===")
    print(f"URL: {ENDPOINT}")
    print(f"Payload: {json.dumps({**payload, 'password_osef': '***'}, indent=2)}")
    
    try:
        response = requests.post(ENDPOINT, json=payload, headers=headers, timeout=60)
        
        print(f"\nStatus Code: {response.status_code}")
        
        if response.status_code == 200:
            data = response.json()
            print(f"Response:\n{json.dumps(data, indent=2, ensure_ascii=False)}")
            
            if data.get('status') == 'ok':
                resp_data = data.get('data', {})
                print("\n=== RESPUESTA EXITOSA ===")
                
                if 'datos_afiliado' in resp_data:
                    print("\nDatos del Afiliado:")
                    for key, value in resp_data['datos_afiliado'].items():
                        print(f"  {key}: {value}")
        else:
            print(f"\nError HTTP: {response.status_code}")
            print(f"Response: {response.text}")
            
    except Exception as e:
        print(f"Error: {e}")
        sys.exit(1)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(
        description='Prueba de consulta a servicio SOAP de OSEF',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Ejemplos de uso:

  # Consultar una autorización:
  python3 test_osef.py --tipo autorizacion --numero 123456 --usuario MIUSUARIO --password MIPASS

  # Consultar un afiliado:
  python3 test_osef.py --tipo afiliado --afiliado "001234567" --usuario MIUSUARIO --password MIPASS

  # Con token JWT personalizado:
  python3 test_osef.py --tipo autorizacion --numero 123456 --usuario MIUSUARIO --password MIPASS --token "eyJ0..."
        """
    )
    
    parser.add_argument('--tipo', choices=['autorizacion', 'afiliado'], 
                        default='autorizacion',
                        help='Tipo de consulta (default: autorizacion)')
    parser.add_argument('--numero', type=int, 
                        help='Número de autorización (requerido si --tipo=autorizacion)')
    parser.add_argument('--afiliado', '--numero-afiliado', type=str,
                        help='Número de afiliado')
    parser.add_argument('--usuario', '--usuario-osef', type=str, required=True,
                        help='Usuario OSEF')
    parser.add_argument('--password', '--password-osef', type=str, required=True,
                        help='Password OSEF')
    parser.add_argument('--delegacion', type=int, default=0,
                        help='Delegación (default: 0)')
    parser.add_argument('--plan', type=int, default=0,
                        help='Plan ID (default: 0)')
    parser.add_argument('--gravamen', type=int, default=0,
                        help='Gravamen (default: 0)')
    parser.add_argument('--token', type=str,
                        help='Token JWT de autenticación')
    parser.add_argument('--url', type=str,
                        help='URL base del API (default: http://localhost:8000)')
    
    args = parser.parse_args()
    
    # Actualiza variables globales si se pasaron parámetros
    if args.url:
        API_BASE_URL = args.url
        ENDPOINT = f"{API_BASE_URL}/int/afiliaciones/afiliado/prueba-osef"
    
    if args.token:
        AUTH_TOKEN = args.token
    
    # Validaciones
    if args.tipo == 'autorizacion' and not args.numero:
        parser.error("--numero es requerido cuando --tipo=autorizacion")
    
    if args.tipo == 'afiliado' and not args.afiliado:
        parser.error("--afiliado es requerido cuando --tipo=afiliado")
    
    # Ejecuta consulta según tipo
    if args.tipo == 'autorizacion':
        consultar_autorizacion(
            usuario=args.usuario,
            password=args.password,
            numero_autorizacion=args.numero,
            delegacion=args.delegacion,
            numero_afiliado=args.afiliado or "",
            jwt_token=AUTH_TOKEN
        )
    else:
        consultar_afiliado(
            usuario=args.usuario,
            password=args.password,
            numero_afiliado=args.afiliado,
            plan=args.plan,
            gravamen=args.gravamen,
            jwt_token=AUTH_TOKEN
        )
