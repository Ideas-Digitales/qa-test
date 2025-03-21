# Prueba técnica QA para Ideas Digitales

## Setup

1. Clonar el repositorio
2. Levantar el devcontainer (se recomienda utilizar GitHub Codespaces)
3. Verificar que el servidor esté funcionando en http://localhost:8080

## Pruebas a desarrollar

### Prueba 1

Desarrollar test de validación de campos para el endpoint POST http://localhost:8080/api/v1/register
   - Headers: Accept: application/json
   - El cuerpo de la solicitud es JSON
   - El campo rut es requerido
   - El campo email es requerido
   - El campo password es requerido
   - El código http de error de validación es 422
   - La implementación del endpoint requiere de una conexión con Amazon Cognito,
     por lo cual en entorno local, es normal que al mandar todos los datos
     correctamente se obtenga un error 500
     
     Estructura de la respuesta en un error de validación:
     
     {
       "message": "general message",
       "errors": {
         "field1": [
           "field1 message"
         ],
         "field2": [
           "field2 message"
         ],
       }
     }

### Prueba 2

Completar el test "test_register_user" en tests/Feature/CognitoControllerTest.php
   - Se trata de un test de características en laravel
   - Este test no necesita ni debe ejecutar solicitudes http reales
   
   Ejecutar el test en consola con el comando:
   ```
   php artisan test --filter register_user
   ```
   
   La salida debe ser similar a la siguiente:
   ```
   PASS  Tests\Feature\CognitoControllerTest
   ✓ register user

   Tests:    1 passed (4 assertions)
   Duration: 0.30s
   ```
