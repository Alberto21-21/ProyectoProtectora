





export async function obtenerEmpleadosParaDisponibilidad(token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/disponibilidades/empleados",
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
            resultado.message || "No se pudieron consultar los empleados."
        );
    }

    return resultado;
}

export async function obtenerJornadaEmpleado(userId, token) {
    const respuesta = await fetch(
        `http://127.0.0.1:8000/api/empleados/usuario/${userId}/jornada`,
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
            resultado.message || "No se pudo consultar la jornada."
        );
    }

    return resultado;
}

export async function actualizarJornadaEmpleado(userId, datos, token) {
    const respuesta = await fetch(
        `http://127.0.0.1:8000/api/empleados/usuario/${userId}/jornada`,
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
            resultado.message || "No se pudo guardar la jornada."
        );
    }

    return resultado;
}