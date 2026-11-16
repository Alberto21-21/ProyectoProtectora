const URL_ASIGNACIONES = "http://127.0.0.1:8000/api/animal-asignaciones";

async function procesarRespuesta(respuesta, mensajeError) {
    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(resultado.message || mensajeError);
    }

    return resultado;
}

export async function obtenerVeterinariosDisponibles(token) {
    const respuesta = await fetch(`${URL_ASIGNACIONES}/veterinarios`, {
        headers: {
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        }
    });

    return procesarRespuesta(
        respuesta,
        "No se pudieron consultar los veterinarios disponibles."
    );
}

export async function obtenerAsignacionesAnimal(animalId, token) {
    const parametros = new URLSearchParams();

    if (animalId) {
        parametros.set("animal_id", animalId);
    }

    const consulta = parametros.toString();
    const url = consulta
        ? `${URL_ASIGNACIONES}?${consulta}`
        : URL_ASIGNACIONES;

    const respuesta = await fetch(url, {
        headers: {
            "Accept": "application/json",
            "Authorization": "Bearer " + token
        }
    });

    return procesarRespuesta(
        respuesta,
        "No se pudieron consultar las asignaciones."
    );
}

export async function obtenerMisAsignaciones(token) {
    return obtenerAsignacionesAnimal(null, token);
}

export async function crearAsignacionAnimal(datos, token) {
    const respuesta = await fetch(URL_ASIGNACIONES, {
        method: "POST",
        headers: {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Authorization": "Bearer " + token
        },
        body: JSON.stringify(datos)
    });

    return procesarRespuesta(
        respuesta,
        "No se pudo crear la asignación."
    );
}

export async function retirarAsignacionAnimal(asignacionId, token) {
    const respuesta = await fetch(
        `${URL_ASIGNACIONES}/${encodeURIComponent(asignacionId)}`,
        {
            method: "DELETE",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    return procesarRespuesta(
        respuesta,
        "No se pudo retirar la asignación."
    );
}