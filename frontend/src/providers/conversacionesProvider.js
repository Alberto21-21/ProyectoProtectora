const URL_CONVERSACIONES = "http://127.0.0.1:8000/api/conversaciones";

// PROCESAR LA RESPUESTA DE LA API
async function procesarRespuesta(respuesta, mensajeError) {
    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        const error = new Error(resultado.message || mensajeError);
        error.errores = resultado.errors;
        throw error;
    }

    return resultado;
}

function cabeceras(token, conCuerpo = false) {
    const cabeceras = {
        "Accept": "application/json",
        "Authorization": "Bearer " + token
    };

    if (conCuerpo) {
        cabeceras["Content-Type"] = "application/json";
    }

    return cabeceras;
}

// LISTAR LAS CONVERSACIONES DE LA CUENTA
export async function obtenerConversaciones(token) {
    const respuesta = await fetch(URL_CONVERSACIONES, {
        headers: cabeceras(token)
    });

    return procesarRespuesta(
        respuesta,
        "No se pudieron consultar las conversaciones."
    );
}

// CUENTAS CON LAS QUE SE PUEDE INICIAR UNA CONVERSACIÓN
export async function obtenerContactos(token) {
    const respuesta = await fetch(`${URL_CONVERSACIONES}/contactos`, {
        headers: cabeceras(token)
    });

    return procesarRespuesta(
        respuesta,
        "No se pudieron consultar los contactos."
    );
}

// INICIAR UNA CONVERSACIÓN
export async function crearConversacion(datos, token) {
    const respuesta = await fetch(URL_CONVERSACIONES, {
        method: "POST",
        headers: cabeceras(token, true),
        body: JSON.stringify(datos)
    });

    return procesarRespuesta(
        respuesta,
        "No se pudo iniciar la conversación."
    );
}

// DETALLE DE UNA CONVERSACIÓN
export async function obtenerConversacion(id, token) {
    const respuesta = await fetch(`${URL_CONVERSACIONES}/${id}`, {
        headers: cabeceras(token)
    });

    return procesarRespuesta(
        respuesta,
        "No se pudo consultar la conversación."
    );
}

// MENSAJES DE UNA CONVERSACIÓN
// filtros: { despues_de, limite }
export async function obtenerMensajes(id, token, filtros = {}) {
    const parametros = new URLSearchParams(filtros);

    const respuesta = await fetch(
        `${URL_CONVERSACIONES}/${id}/mensajes?${parametros.toString()}`,
        { headers: cabeceras(token) }
    );

    return procesarRespuesta(
        respuesta,
        "No se pudieron consultar los mensajes."
    );
}

// ENVIAR UN MENSAJE
export async function enviarMensaje(id, texto, token) {
    const respuesta = await fetch(`${URL_CONVERSACIONES}/${id}/mensajes`, {
        method: "POST",
        headers: cabeceras(token, true),
        body: JSON.stringify({ texto })
    });

    return procesarRespuesta(
        respuesta,
        "No se pudo enviar el mensaje."
    );
}
