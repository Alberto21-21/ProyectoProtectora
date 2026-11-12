export async function obtenerPerfilVeterinario(userId, token) {
    const respuesta = await fetch(
        `http://127.0.0.1:8000/api/veterinarios/usuario/${userId}`,
        {
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo consultar el perfil veterinario."
        );
    }

    return resultado;
}

export async function crearPerfilVeterinario(datos, token) {
    const respuesta = await fetch("http://127.0.0.1:8000/api/veterinarios", {
        method: "POST",
        headers: {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Authorization": "Bearer " + token
        },
        body: JSON.stringify(datos)
    });

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo crear el perfil veterinario."
        );
    }

    return resultado;
}

export async function actualizarPerfilVeterinario(userId, datos, token) {
    const respuesta = await fetch(
        `http://127.0.0.1:8000/api/veterinarios/usuario/${userId}`,
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
            resultado.message || "No se pudo actualizar el perfil veterinario."
        );
    }

    return resultado;
}
