// CONSULTAR EL HOGAR PROPIO
export async function obtenerHogar(token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/hogar",
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
            resultado.message || "No se pudo consultar el hogar."
        );
    }

    return resultado;
}

// GUARDAR O ACTUALIZAR EL HOGAR PROPIO
export async function guardarHogar(datos, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/hogar",
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
            resultado.message || "No se pudieron guardar los datos del hogar."
        );
    }

    return resultado;
}