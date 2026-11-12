export async function obtenerFichaEmpleado(userId, token) {
    const respuesta = await fetch(
        `http://127.0.0.1:8000/api/empleados/usuario/${userId}`,
        {
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(resultado.message || "No se pudo consultar la ficha.");
    }

    return resultado;
}

export async function crearFichaEmpleado(datos, token) {
    const respuesta = await fetch("http://127.0.0.1:8000/api/empleados", {
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
        throw new Error(resultado.message || "No se pudo crear la ficha.");
    }

    return resultado;
}

export async function actualizarFichaEmpleado(userId, datos, token) {
    const respuesta = await fetch(
        `http://127.0.0.1:8000/api/empleados/usuario/${userId}`,
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
        throw new Error(resultado.message || "No se pudo actualizar la ficha.");
    }

    return resultado;
}