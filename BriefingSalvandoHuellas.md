# Briefing del proyecto: SalvandoHuellas

*2.º CFGS DAW · Desafío de desarrollo · Primera evaluación*

## 1. Resumen

Salvando Huelass es una protectora de animales que gestiona hoy sus animales, revisiones veterinarias, turnos y solicitudes de adopción con hojas de cálculo, documentos y mensajes sueltos. El resultado son historiales incompletos, tareas de limpieza olvidadas, turnos solapados y animales que esperan demasiado tiempo una familia.

El proyecto consiste en desarrollar una aplicación web que centralice la gestión de la protectora, mejore el cuidado de los animales y ayude a cada uno a encontrar el hogar que mejor encaje. No se pide solo almacenar datos: se espera una aplicación funcional, bien diseñada y con lógica de negocio propia.

## 2. Objetivos

- Sustituir la gestión dispersa actual por un sistema único y coherente.
- Garantizar que cada tarea, registro clínico y solicitud quede trazado y asignado a alguien.
- Recomendar adopciones según compatibilidad real entre animal y adoptante, no por orden de llegada ni por apariencia.
- Ofrecer información en tiempo real sobre instalaciones, tareas y mensajes.
- Demostrar dominio técnico en base de datos, backend, frontend y metodología de trabajo.

El objetivo no es construir la aplicación más grande posible, sino una solución coherente que resuelva problemas reales. Una aplicación que solo muestre pantallas sin lógica detrás no se considerará suficiente.

## 3. Alcance funcional

### Gestión de animales

Ficha completa de cada animal (especie, raza, edad, sexo, tamaño, peso, estado, carácter, compatibilidades, necesidades especiales y observaciones). Altas, ediciones y consultas, con una eliminación que respete historiales y adopciones asociados.

Cada animal tiene un **historial cronológico** formado por registros independientes (fecha, tipo de acontecimiento, descripción y usuario responsable), consultable y filtrable por tipo o por fechas. No puede ser un simple campo de texto.

### Instalaciones y jaulas

Zonas (perros, gatos, enfermería, cuarentena, animales pequeños) con sus habitáculos: capacidad, animales alojados, estado de limpieza y mantenimiento, y fechas de última y próxima limpieza. El sistema debe impedir superar la capacidad y evitar alojamientos incompatibles. Se pide además una vista general o plano con indicadores visuales (libre, ocupado, pendiente de limpieza, en mantenimiento, aislamiento) que se actualice en tiempo real.

### Personal y veterinarios

Gestión de empleados con datos de contacto, puesto, disponibilidad y estado. Los veterinarios añaden especialidad y se vinculan a los animales que atienden.

### Limpieza y mantenimiento

Tareas con tipo, instalación, fecha y hora, duración estimada, prioridad, trabajadores asignados, estado (pendiente, en progreso, finalizada, cancelada) y observaciones. Pueden crearse a mano o mediante reglas automáticas, por ejemplo al cumplirse un intervalo de limpieza o cuando un animal abandona una instalación. Los cambios de estado deben verse en tiempo real y debe conservarse el historial de limpieza de cada instalación.

### Adopciones

Los usuarios registrados rellenan un perfil de hogar y preferencias, reciben recomendaciones y pueden iniciar solicitudes. Cada solicitud pasa por estados (pendiente, en estudio, entrevista, aprobada, rechazada, cancelada, adopción formalizada) y es el personal quien decide. Al formalizar una adopción, el animal deja de figurar como disponible, y el sistema debe impedir que dos usuarios formalicen la adopción del mismo animal a la vez. Se admiten seguimientos posteriores.

### Chat y consultas veterinarias

Mensajería entre personal y veterinarios, vinculable a un animal, con texto y adjuntos opcionales, indicadores de lectura, notificaciones, control de acceso y actualización en tiempo real mediante WebSockets. Las consultas a distancia tienen estado propio (abierta, en revisión, respondida, cerrada) y sus recomendaciones quedan ligadas al animal sin sustituir el registro clínico.

### Informes y estadísticas

Panel para el administrador con, como mínimo: animales acogidos, disponibles y adoptados, ocupación, tareas pendientes y finalizadas, incidencias abiertas, revisiones pendientes, reparto de tareas, solicitudes por estado y tiempo de estancia. Con filtros por fecha, especie, zona o estado, y valoración positiva de los gráficos.

## 4. Perfiles de usuario

| Perfil | Qué puede hacer |
| --- | --- |
| Administrador | Gestionar usuarios, roles, animales, instalaciones y personal; supervisar tareas; revisar historiales y solicitudes; consultar estadísticas; lanzar simulaciones. |
| Personal | Ver y actualizar sus tareas, consultar instalaciones y animales asignados, registrar observaciones e incidencias, comunicarse con compañeros y veterinarios. Sin acceso a datos clínicos restringidos ni a acciones administrativas. |
| Veterinario | Consultar historiales clínicos, registrar revisiones, diagnósticos, tratamientos y vacunaciones, programar revisiones, configurar alertas y atender consultas a distancia. |
| Adoptante | Registrarse, mantener su perfil, ver recomendaciones y solicitar adopciones. |

Todos los usuarios pueden editar ciertos datos de su perfil (contraseña y fotografía) dentro de sus permisos.

## 5. Los tres algoritmos del proyecto

Estas piezas son las que aportan lógica de negocio y las que más se van a valorar. En los tres casos el diseño queda a elección del equipo, pero **los criterios deben estar justificados**.

1. **Compatibilidad animal–adoptante.** Calcula una puntuación para cada animal disponible a partir de factores como especie, tamaño, vivienda, espacio exterior, tiempo disponible, actividad, niños, otros animales, experiencia, necesidades médicas y carácter. Debe usar ponderaciones, explicar los motivos de cada resultado (no basta con un «85 %») y descartar las incompatibilidades importantes aunque el resto de factores puntúe alto. La recomendación nunca implica aprobación automática.
2. **Priorización de tareas.** Propone qué hacer primero según riesgo sanitario, tiempo desde la última limpieza, tipo de animal, presencia de animales enfermos, urgencia de la incidencia y disponibilidad del personal.
3. **Simulación de la protectora.** El administrador plantea escenarios (llegada de animales, mayor ocupación, incidencia sanitaria, bajas de personal, acumulación de tareas, traslados, instalaciones no disponibles) y el sistema calcula las consecuencias, detecta lo que queda sin cubrir y propone una redistribución del trabajo.

## 6. Requisitos técnicos por módulo

**Desarrollo Web Servidor**

- Base de datos relacional normalizada, con entidades y relaciones bien definidas.
- Migraciones, seeders y factories.
- Backend en Laravel con Eloquent y Query Builder.
- API para la comunicación con el frontend.
- Autenticación con tokens Sanctum y permisos por abilities.
- Validación de datos y separación de responsabilidades.
- WebSockets para el chat y las notificaciones.
- Un patrón de diseño visto en clase, con su justificación.
- Pruebas de integración y de funcionalidad.

**Desarrollo Web en Entorno Cliente**

- JavaScript vanilla estructurado con patrón módulo y Vite.
- Gestión correcta de eventos y formularios.
- Análisis del problema con POO.
- Peticiones HTTP organizadas en providers.
- Lógica del DOM separada del resto.
- Pruebas con Vitest.

**Proyecto Intermodular**

- Documentación de briefing y estudios de viabilidad.
- Historias de usuario en GitHub, con ramas vinculadas a cada una.
- Metodología Scrum, con tareas planificadas por unidad funcional y sprints que cumplan lo planificado.

**Transversal**

- Git con repositorios y ramas de desarrollo.
- Tablero de tareas.
- Documentación técnica y funcional.
- Atención especial a la seguridad: rutas protegidas, validación de entradas y acceso solo a lo autorizado.

## 7. Planificación previa

Antes de programar hay que entregar una planificación que incluya:

- Descomposición en funcionalidades concretas, con estimación de dificultad y duración.
- Tareas y dependencias.
- Diseño inicial de la base de datos.
- Wireframes, guía de estilos y mapa de navegación.
- Tablero de trabajo y reparto de responsabilidades si se trabaja en equipo.
- Definición de las pruebas que validarán cada funcionalidad.

Se valoran las tareas realistas, bien definidas y que permitan medir el avance.

## 8. Riesgos y puntos de atención

- **Alcance excesivo.** Es fácil querer cubrir todo. Conviene priorizar un núcleo funcional (animales, instalaciones, tareas, adopciones) y dejar lo demás para después.
- **Tiempo real.** Chat, estados de tareas y plano de instalaciones dependen de WebSockets; conviene abordarlos pronto para no dejarlos para el final.
- **Reglas de negocio.** Capacidad de jaulas, incompatibilidades y adopciones simultáneas exigen validaciones cuidadas en el backend, no solo en el cliente.
- **Permisos.** Cuatro perfiles con accesos distintos hacen fácil cometer errores de autorización; definir las abilities desde el principio ahorra problemas.
- **Algoritmos sin justificar.** Un resultado correcto sin criterios explicados pierde buena parte de su valor.
- **Coordinación entre módulos.** Un mismo proyecto se evalúa desde tres asignaturas, así que la documentación, el Git y el tablero deben estar al día en todo momento.

## 9. Criterios de éxito

Se considerará bien resuelto el proyecto si:

- La aplicación funciona de extremo a extremo para los cuatro perfiles, con permisos respetados.
- Los tres algoritmos están implementados, justificados y producen resultados explicables.
- El estado de instalaciones, tareas y mensajes se actualiza en tiempo real.
- El código está organizado, probado y versionado, y el trabajo es trazable desde la historia de usuario hasta la rama.
- La documentación permite entender qué se hizo y por qué.

Y, como recuerda el enunciado, detrás de cada registro hay un animal que necesita cuidados y una oportunidad de encontrar un hogar.
