// ENVIAR UNA SOLICITUD DE ADOPCIÓN
export async function crearSolicitud(datos, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/solicitudes",
        {
            method: "POST",
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
            resultado.message || "No se pudo enviar la solicitud."
        );
    }

    return resultado;
}

// CONSULTAR LAS SOLICITUDES DEL ADOPTANTE
export async function obtenerMisSolicitudes(token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/solicitudes",
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
            resultado.message || "No se pudieron consultar tus solicitudes."
        );
    }

    return resultado;
}

// CANCELAR UNA SOLICITUD PROPIA
export async function cancelarSolicitud(id, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/solicitudes/" + id + "/cancelar",
        {
            method: "PATCH",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo cancelar la solicitud."
        );
    }

    return resultado;
}

// CONSULTAR SOLICITUDES PARA SU REVISIÓN
export async function obtenerSolicitudesRevision(filtros, token) {

    const parametros = new URLSearchParams(filtros);

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/solicitudes/revision?"
            + parametros.toString(),
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
            resultado.message || "No se pudieron consultar las solicitudes."
        );
    }

    return resultado;
}

// CONSULTAR EL DETALLE DE UNA SOLICITUD PARA REVISIÓN
export async function obtenerDetalleSolicitudRevision(id, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/solicitudes/revision/" + id,
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
            resultado.message || "No se pudo consultar la solicitud."
        );
    }

    return resultado;
}

// GUARDAR LAS OBSERVACIONES INTERNAS
export async function guardarObservacionesSolicitud(id, datos, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/solicitudes/revision/"
            + id + "/observaciones",
        {
            method: "PATCH",
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
            resultado.message || "No se pudieron guardar las observaciones."
        );
    }

    return resultado;
}

// REGISTRAR UNA ENTREVISTA O DECISIÓN
export async function registrarSeguimiento(id, datos, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/solicitudes/revision/" +
        id + "/seguimientos",
        {
            method: "POST",
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
            resultado.message || "No se pudo registrar la actuación."
        );
    }

    return resultado;
}