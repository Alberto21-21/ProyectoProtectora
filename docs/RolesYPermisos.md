# Roles y permisos

## Roles del sistema

- admin: administra cuentas y supervisa la protectora.
- adoptante: consulta animales disponibles y gestiona su proceso de adopción.
- personal: realiza las operaciones de cuidado y trabajo autorizadas.
- veterinario: realiza las operaciones clínicas y consultas autorizadas.

## Matriz de acceso

| Operación | admin | adoptante | personal | veterinario |
|---|---|---|---|---|
| Consultar y editar su propio perfil | Sí | Sí | Sí | Sí |
| Cambiar su propia contraseña | Sí | Sí | Sí | Sí |
| Consultar animales disponibles e información pública | Sí | Sí | Sí | Sí |
| Consultar y gestionar cuentas | Sí | No | No | No |
| Crear cuentas internas | Sí | No | No | No |
| Cambiar roles | Sí | No | No | No |
| Desactivar otras cuentas | Sí | No | No | No |
| Gestionar fichas laborales y profesionales | Sí | No | No | No |
| Gestionar disponibilidad y ausencias del equipo | Sí | No | No | No |
| Consultar disponibilidad y ausencias autorizadas | Sí | No | Sí | Sí |
| Gestionar datos generales de animales | Sí | No | Sí | Sí |
| Asignar cuidadores a animales | Sí | No | No | No |
| Consultar animales asignados | Sí | No | Solo los propios | Solo los autorizados |
| Consultar y editar información clínica | Sí | No | No | Solo animales autorizados |

## Reglas generales

- Laravel comprueba los permisos en cada operación protegida.
- Ocultar botones o enlaces no sustituye la autorización del servidor.
- Una cuenta inactiva no puede utilizar las operaciones protegidas.
- El registro público siempre asigna el rol adoptante.
- El alta administrativa de cuentas internas solo permite personal o veterinario.
- Solo un administrador activo puede cambiar roles.
- Un administrador no puede cambiar su propio rol desde esta operación.
- Cambiar un rol revoca todos los tokens anteriores de la cuenta afectada.
- La persona debe iniciar sesión de nuevo para obtener un token actualizado.
- Las abilities del token las asigna el servidor, nunca el formulario.
- El acceso requiere comprobar las abilities y el rol vigente de la cuenta.
- Las restricciones por propietario, asignación o participación se comprueban además del rol.

## Fichas laborales y cambios de rol

- Cambiar el rol no elimina fichas laborales, perfiles veterinarios ni registros anteriores.
- Una ficha laboral no concede permisos de acceso por sí misma.
- El acceso depende del rol vigente y de las autorizaciones del recurso.
- Los datos profesionales y las asignaciones necesarios se gestionan desde sus módulos.
- Tener el rol veterinario no autoriza automáticamente a consultar cualquier paciente.

## Alcance

Esta matriz define el comportamiento esperado.
Las restricciones pendientes se implementan y verifican en T10, T11
y en las historias de cada módulo.

## Dependencia pendiente: T33

Las rutas clínicas comprueban cuenta activa, rol y ability.

La restricción del veterinario a sus animales asignados queda pendiente
de la relación que implementará T33. Actualmente no se comprueba
la asignación por animal.

Cuando T33 esté integrada, se aplicará esa comprobación a la consulta
y edición clínica, conservando el acceso del administrador.