// DAR DE ALTA UN ANIMAL
export async function crearAnimal(datos, token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales",
        {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            },
            body: datos
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        const error = new Error(
            resultado.message || "No se pudo registrar el animal."
        );
        error.errores = resultado.errors;
        throw error;
    }

    return resultado;
}

// CONSULTAR LA FICHA DE UN ANIMAL
export async function obtenerAnimal(id) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales/" + id,
        {
            method: "GET",
            headers: {
                "Accept": "application/json"
            }
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo consultar el animal."
        );
    }

    return resultado;
}

// ACTUALIZAR LOS DATOS GENERALES DE UN ANIMAL
export async function actualizarAnimal(id, datos, token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales/" + id,
        {
            method: "PUT",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            },
            body: JSON.stringify(datos)
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo actualizar el animal."
        );
    }

    return resultado;
}

// ACTUALIZAR LA FOTOGRAFÍA DE UN ANIMAL
export async function actualizarImagenAnimal(id, imagen, token) {
    const datos = new FormData();
    datos.append("imagen", imagen);

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales/" + id + "/imagen",
        {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            },
            body: datos
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo actualizar la fotografía."
        );
    }

    return resultado;
}

// ACTUALIZAR EL ESTADO DE UN ANIMAL (T15)
export async function actualizarEstadoAnimal(id, estado, token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales/" + id + "/estado",
        {
            method: "PATCH",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            },
            body: JSON.stringify({ estado: estado })
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        const error = new Error(
            resultado.message || "No se pudo actualizar el estado del animal."
        );
        error.errores = resultado.errors || null;
        throw error;
    }

    return resultado;
}

// DAR DE BAJA UN ANIMAL - BAJA LÓGICA (T16)
export async function darDeBajaAnimal(id, motivo, token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales/" + id + "/baja",
        {
            method: "PATCH",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            },
            body: JSON.stringify({ motivo_baja: motivo })
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        const error = new Error(
            resultado.message || "No se pudo dar de baja el animal."
        );
        error.errores = resultado.errors || null;
        throw error;
    }

    return resultado;
}

// ELIMINAR UN ANIMAL DEFINITIVAMENTE (T16)
export async function eliminarAnimal(id, token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales/" + id,
        {
            method: "DELETE",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo eliminar el animal."
        );
    }

    return resultado;
}

// CONSULTAR LOS DATOS CLÍNICOS DE UN ANIMAL
export async function obtenerDatosClinicos(id, token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales/" + id + "/clinico",
        {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudieron consultar los datos clínicos."
        );
    }

    return resultado;
}

// ACTUALIZAR LOS DATOS CLÍNICOS DE UN ANIMAL
export async function actualizarDatosClinicos(id, datos, token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales/" + id + "/clinico",
        {
            method: "PUT",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            },
            body: JSON.stringify(datos)
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudieron actualizar los datos clínicos."
        );
    }

    return resultado;
}