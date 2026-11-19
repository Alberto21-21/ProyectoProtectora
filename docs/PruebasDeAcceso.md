# T11 — Verificación de accesos

## Ejecución

Desde backend:

php artisan test --filter=AccesosTest

Las pruebas utilizan SQLite en memoria y RefreshDatabase.

## Resultado

11 pruebas superadas y 31 comprobaciones.

## Casos comprobados

- Sin token: perfil y listado de usuarios devuelven 401.
- Token inválido: el perfil devuelve 401.
- Administrador con permisos: puede consultar el listado paginado.
- Adoptante, personal y veterinario: no pueden administrar usuarios; reciben 403.
- Administrador con token sin la ability necesaria: recibe 403.
- El perfil devuelve la cuenta autenticada aunque se envíe otro identificador.
- El perfil no devuelve password ni remember_token.
- Veterinario asignado: puede consultar la ficha clínica.
- Veterinario no asignado: recibe 403 y no recibe los datos clínicos.
- Desactivar una cuenta elimina sus tokens; el anterior devuelve 401.
- Cambiar el rol elimina sus tokens; el anterior devuelve 401.

## Alcance pendiente

No hay canales privados configurados en la versión revisada.
Su autorización deberá comprobarse cuando se implemente esa funcionalidad.

Estas pruebas cubren los casos descritos, no todos los endpoints del proyecto.