// CONSULTAR Y BUSCAR USUARIOS
export async function obtenerUsuarios(filtros, token) {

    const parametros = new URLSearchParams(filtros);

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/usuarios?" + parametros.toString(),
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
            resultado.message || "No se pudieron consultar los usuarios."
        );
    }

    return resultado;
}

// CONSULTAR EL DETALLE DE UN USUARIO
export async function obtenerDetalleUsuario(id, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/usuarios/" + id,
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
            resultado.message || "No se pudo consultar el usuario."
        );
    }

    return resultado;
}