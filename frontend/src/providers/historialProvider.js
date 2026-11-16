// CONSULTAR EL HISTORIAL GENERAL DE UN ANIMAL (T18)
export async function obtenerHistorial(idAnimal, token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales/" + idAnimal + "/historial",
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
            resultado.message || "No se pudo consultar el historial."
        );
    }

    return resultado;
}

// REGISTRAR UNA OBSERVACIÓN GENERAL (T18)
export async function registrarObservacion(idAnimal, descripcion, token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales/" + idAnimal + "/observaciones",
        {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            },
            body: JSON.stringify({ descripcion: descripcion })
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        const error = new Error(
            resultado.message || "No se pudo registrar la observación."
        );
        error.errores = resultado.errors || null;
        throw error;
    }

    return resultado;
}
