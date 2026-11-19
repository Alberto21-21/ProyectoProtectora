// PETICIÓN COMÚN A LA API DE INSTALACIONES (T23)
async function peticion(ruta, metodo, datos, token) {
    // Todas las llamadas usan el mismo formato y mandan el token en la cabecera.
    const opciones = {
        method: metodo,
        headers: {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Authorization": "Bearer " + token
        }
    };

    if (datos) {
        opciones.body = JSON.stringify(datos);
    }

    const respuesta = await fetch("http://127.0.0.1:8000/api/" + ruta, opciones);
    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        const error = new Error(resultado.message || "No se pudo completar la operación.");
        error.errores = resultado.errors || null;
        throw error;
    }

    return resultado;
}

// ZONAS
export function obtenerZonas(token) {
    return peticion("zonas", "GET", null, token);
}

export function crearZona(datos, token) {
    return peticion("zonas", "POST", datos, token);
}

export function actualizarZona(id, datos, token) {
    return peticion("zonas/" + id, "PUT", datos, token);
}

export function cambiarActivoZona(id, token) {
    return peticion("zonas/" + id + "/activo", "PATCH", null, token);
}

// HABITÁCULOS
export function obtenerHabitaculos(filtros, token) {
    const parametros = new URLSearchParams();

    if (filtros.zona_id) {
        parametros.append("zona_id", filtros.zona_id);
    }

    if (filtros.estado_limpieza) {
        parametros.append("estado_limpieza", filtros.estado_limpieza);
    }

    return peticion("habitaculos?" + parametros.toString(), "GET", null, token);
}

// FICHA DEL HABITÁCULO: ANIMALES, PLAZAS, RESTRICCIONES Y ACTUACIONES (T24)
export function obtenerFichaHabitaculo(id, token) {
    return peticion("habitaculos/" + id + "/ficha", "GET", null, token);
}

export function crearHabitaculo(datos, token) {
    return peticion("habitaculos", "POST", datos, token);
}

export function actualizarHabitaculo(id, datos, token) {
    return peticion("habitaculos/" + id, "PUT", datos, token);
}

export function cambiarActivoHabitaculo(id, token) {
    return peticion("habitaculos/" + id + "/activo", "PATCH", null, token);
}
