// CONSULTAR LA COMPATIBILIDAD DEL ADOPTANTE
export async function obtenerCompatibilidad(token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/compatibilidad",
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
            resultado.message || "No se pudo consultar la compatibilidad."
        );
    }

    return resultado;
}